<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id_bien = intval($_GET['id_bien'] ?? 0);
$week = intval($_GET['week'] ?? 0);
$year = intval($_GET['year'] ?? date('Y'));

if (!$id_bien || $week < 1 || $week > 52) {
    echo json_encode(['tarif' => null, 'saison' => null, 'is_default' => false]);
    exit;
}

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        echo json_encode(['tarif' => null, 'saison' => null, 'is_default' => false]);
        exit;
    }

    // Get specific tarif for this week/year
    $stmt = $pdo->prepare('
        SELECT t.tarif, t.semaine_Tarif, s.lib_saison, s.id_saison
        FROM Tarif t
        LEFT JOIN Saison s ON t.id_saison = s.id_saison
        WHERE t.id_biens = ? AND t.semaine_Tarif = ? AND t.année_Tarif = ?
        LIMIT 1
    ');
    $stmt->execute([$id_bien, $week, $year]);
    $tarif = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tarif) {
        echo json_encode([
            'tarif' => floatval($tarif['tarif']),
            'saison' => $tarif['lib_saison'],
            'is_default' => false
        ]);
        exit;
    }

    // Determine season from week number (approximation for northern hemisphere)
    $season_map = [
        'Hiver' => [1, 2, 3, 52],
        'Printemps' => [13, 14, 15, 16, 17],
        'Été' => [23, 24, 25, 26, 27],
        'Automne' => [35, 36, 37, 38, 39]
    ];

    $found_season = null;
    foreach ($season_map as $saison => $weeks) {
        if (in_array($week, $weeks)) {
            $found_season = $saison;
            break;
        }
    }

    // If season not found, get it from database
    if (!$found_season) {
        $seasonStmt = $pdo->prepare('SELECT id_saison FROM Saison LIMIT 1');
        $seasonStmt->execute();
        $defSeason = $seasonStmt->fetch(PDO::FETCH_ASSOC);
        $found_season = 'Été'; // fallback
    }

    // Get default tarif for this bien/saison
    $defaultStmt = $pdo->prepare('
        SELECT td.tarif_defaut, s.lib_saison
        FROM Tarif_Defaut td
        LEFT JOIN Saison s ON td.id_saison = s.id_saison
        WHERE td.id_biens = ? AND s.lib_saison LIKE ?
        LIMIT 1
    ');
    $defaultStmt->execute([$id_bien, '%' . $found_season . '%']);
    $defaultTarif = $defaultStmt->fetch(PDO::FETCH_ASSOC);

    if ($defaultTarif) {
        echo json_encode([
            'tarif' => floatval($defaultTarif['tarif_defaut']),
            'saison' => $defaultTarif['lib_saison'],
            'is_default' => true
        ]);
    } else {
        echo json_encode([
            'tarif' => null,
            'saison' => $found_season,
            'is_default' => false
        ]);
    }
} catch (Exception $e) {
    error_log('Error in get_tarifs_week.php: ' . $e->getMessage());
    echo json_encode(['tarif' => null, 'saison' => null, 'is_default' => false]);
}
?>
