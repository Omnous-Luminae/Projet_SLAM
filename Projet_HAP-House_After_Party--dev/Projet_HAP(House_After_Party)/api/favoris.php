<?php
/**
 * API pour gérer les favoris
 * Actions: toggle, list, check
 */

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['locataire_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté', 'login_required' => true]);
    exit;
}

$locataireId = $_SESSION['locataire_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Créer la table si elle n'existe pas
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Favoris (
            id_favori INT AUTO_INCREMENT PRIMARY KEY,
            id_locataire INT NOT NULL,
            id_biens INT NOT NULL,
            date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_favori (id_locataire, id_biens)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // Table existe probablement déjà
}

switch ($action) {
    case 'toggle':
        // Ajouter ou retirer un favori
        $bienId = intval($_POST['bien_id'] ?? 0);
        
        if ($bienId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID bien invalide']);
            exit;
        }
        
        try {
            // Vérifier si déjà en favori
            $stmt = $pdo->prepare("SELECT id_favori FROM Favoris WHERE id_locataire = ? AND id_biens = ?");
            $stmt->execute([$locataireId, $bienId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Retirer des favoris
                $stmt = $pdo->prepare("DELETE FROM Favoris WHERE id_locataire = ? AND id_biens = ?");
                $stmt->execute([$locataireId, $bienId]);
                echo json_encode(['success' => true, 'action' => 'removed', 'is_favorite' => false]);
            } else {
                // Ajouter aux favoris
                $stmt = $pdo->prepare("INSERT INTO Favoris (id_locataire, id_biens) VALUES (?, ?)");
                $stmt->execute([$locataireId, $bienId]);
                echo json_encode(['success' => true, 'action' => 'added', 'is_favorite' => true]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        }
        break;
        
    case 'check':
        // Vérifier si un bien est en favori
        $bienId = intval($_GET['bien_id'] ?? 0);
        
        if ($bienId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID bien invalide']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT id_favori FROM Favoris WHERE id_locataire = ? AND id_biens = ?");
            $stmt->execute([$locataireId, $bienId]);
            $isFavorite = $stmt->fetch() !== false;
            
            echo json_encode(['success' => true, 'is_favorite' => $isFavorite]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        }
        break;
        
    case 'list':
        // Lister tous les favoris de l'utilisateur
        try {
            $stmt = $pdo->prepare("
                SELECT b.*, f.date_ajout as date_favori,
                       (SELECT lien_photo FROM Photos WHERE id_biens = b.id_biens LIMIT 1) as photo
                FROM Favoris f
                JOIN Biens b ON f.id_biens = b.id_biens
                WHERE f.id_locataire = ?
                ORDER BY f.date_ajout DESC
            ");
            $stmt->execute([$locataireId]);
            $favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'favoris' => $favoris, 'count' => count($favoris)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        }
        break;
        
    case 'count':
        // Compter les favoris de l'utilisateur
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM Favoris WHERE id_locataire = ?");
            $stmt->execute([$locataireId]);
            $count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'count' => $count]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
}
