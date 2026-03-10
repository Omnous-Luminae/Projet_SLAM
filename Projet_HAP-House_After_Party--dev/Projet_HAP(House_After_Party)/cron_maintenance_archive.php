<?php

/**
 * Script de maintenance du système d'archivage
 * Peut être exécuté régulièrement (ex: une fois par mois)
 * 
 * Fonctionnalités:
 * - Vérifier la santé du système
 * - Nettoyer les anciennes archives selon la politique de rétention
 * - Générer un rapport de maintenance
 * - Optimiser les tables
 */

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/config/archive_config.php";
require_once __DIR__ . "/classes/ReservationArchive.php";

date_default_timezone_set('Europe/Paris');
$config = require __DIR__ . "/config/archive_config.php";

echo "=== MAINTENANCE DU SYSTÈME D'ARCHIVAGE ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$archive_manager = new ReservationArchive($pdo);

// 1. Statistiques
echo "1. STATISTIQUES\n";
echo str_repeat("-", 50) . "\n";

try {
    // Nombre total d'archives
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Reservation_Archive");
    $archives_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Archives totales: $archives_count\n";
    
    // Archives par statut
    $stmt = $pdo->query("
        SELECT statut_archivage, COUNT(*) as count 
        FROM Reservation_Archive 
        GROUP BY statut_archivage
    ");
    $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statuts as $s) {
        echo "   - {$s['statut_archivage']}: {$s['count']}\n";
    }
    
    // Taille des données
    $stmt = $pdo->query("
        SELECT 
            ROUND(SUM(LENGTH(donnees_cryptees)) / 1024 / 1024, 2) as size_mb
        FROM Reservation_Archive
    ");
    $size = $stmt->fetch(PDO::FETCH_ASSOC)['size_mb'];
    echo "   Taille totale: {$size} MB\n";
    
    // Nombre de logs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM Archive_Log");
    $logs_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Logs enregistrés: $logs_count\n";
    
} catch (Exception $e) {
    echo "   ✗ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Vérifier la santé du système
echo "2. VÉRIFICATION DE LA SANTÉ\n";
echo str_repeat("-", 50) . "\n";

$health_checks = [
    'Fichier clé' => file_exists(__DIR__ . '/config/.encryption_key'),
    'Répertoire logs' => is_dir(__DIR__ . '/logs'),
    'Table Reservation_Archive' => checkTableExists('Reservation_Archive'),
    'Table Archive_Log' => checkTableExists('Archive_Log'),
];

foreach ($health_checks as $check => $result) {
    $status = $result ? '✓' : '✗';
    echo "   $status $check\n";
}

echo "\n";

// 3. Archivage des réservations passées
echo "3. ARCHIVAGE AUTO\n";
echo str_repeat("-", 50) . "\n";

try {
    $count = $archive_manager->archiveAllPastReservations($config['auto_archive']['days_after_end']);
    echo "   ✓ $count réservation(s) archivée(s)\n";
} catch (Exception $e) {
    echo "   ✗ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Nettoyage des anciennes archives (optionnel)
if ($config['retention']['auto_delete_old']) {
    echo "4. NETTOYAGE DES ANCIENNES ARCHIVES\n";
    echo str_repeat("-", 50) . "\n";
    
    try {
        $days = $config['retention']['keep_archives_days'];
        $date_limite = date('Y-m-d', strtotime("-$days days"));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM Reservation_Archive 
            WHERE date_archivage < :date_limite AND statut_archivage != 'supprimé'
        ");
        $stmt->execute([':date_limite' => $date_limite]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            $delete_stmt = $pdo->prepare("
                UPDATE Reservation_Archive 
                SET statut_archivage = 'supprimé'
                WHERE date_archivage < :date_limite AND statut_archivage != 'supprimé'
            ");
            $delete_stmt->execute([':date_limite' => $date_limite]);
            echo "   ✓ $count archive(s) marquée(s) comme supprimée(s)\n";
        } else {
            echo "   ✓ Aucune archive à supprimer\n";
        }
    } catch (Exception $e) {
        echo "   ✗ ERREUR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// 5. Optimisation des tables
echo "5. OPTIMISATION DES TABLES\n";
echo str_repeat("-", 50) . "\n";

try {
    $tables = ['Reservation_Archive', 'Archive_Log'];
    foreach ($tables as $table) {
        // OPTIMIZE TABLE returns a result set in MySQL; consume it to avoid pending cursor issues.
        $optStmt = $pdo->query("OPTIMIZE TABLE $table");
        $optStmt->fetchAll(PDO::FETCH_ASSOC);
        $optStmt->closeCursor();
        echo "   ✓ Table $table optimisée\n";
    }
} catch (Exception $e) {
    echo "   ✗ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. Rapport des erreurs
echo "6. LOGS D'ERREURS\n";
echo str_repeat("-", 50) . "\n";

try {
    $logs = $archive_manager->getLogs(['action' => 'erreur']);
    if (count($logs) > 0) {
        echo "   ⚠ " . count($logs) . " erreur(s) enregistrée(s)\n";
        foreach (array_slice($logs, 0, 5) as $log) {
            echo "   - [{$log['date_action']}] {$log['description']}\n";
        }
    } else {
        echo "   ✓ Aucune erreur\n";
    }
} catch (Exception $e) {
    echo "   ✗ Impossible de récupérer les logs\n";
}

echo "\n";

// 7. Recommandations
echo "7. RECOMMANDATIONS\n";
echo str_repeat("-", 50) . "\n";

$recommendations = [];

if ($size > 1000) {
    $recommendations[] = "L'espace utilisé est important ($size MB). Envisager un archivage externe.";
}

if ($logs_count > 10000) {
    $recommendations[] = "Beaucoup de logs enregistrés ($logs_count). Envisager un nettoyage.";
}

$stmt = $pdo->query("
    SELECT COUNT(*) as count FROM Reservation_Archive 
    WHERE statut_archivage = 'supprimé'
");
$deleted = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($deleted > 0) {
    $recommendations[] = "Il y a $deleted archive(s) marquée(s) comme supprimée(s). Considérer une suppression physique.";
}

if (count($recommendations) > 0) {
    foreach ($recommendations as $rec) {
        echo "   • " . $rec . "\n";
    }
} else {
    echo "   ✓ Aucune recommandation particulière\n";
}

echo "\n";

echo "=== FIN DE LA MAINTENANCE ===\n";
echo "Fin: " . date('Y-m-d H:i:s') . "\n";

// Fonction helper
function checkTableExists($table_name) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 1 FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table
        ");
        $stmt->execute([
            ':db' => 'Project_HAP',
            ':table' => $table_name
        ]);
        return (bool) $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

?>
