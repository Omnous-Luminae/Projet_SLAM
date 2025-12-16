<?php

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../classes/ReservationArchive.php";

header('Content-Type: application/json; charset=utf-8');

$id_archive = intval($_GET['id'] ?? 0);

if ($id_archive <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID d\'archive invalide'
    ]);
    exit;
}

try {
    $archive_manager = new ReservationArchive($pdo);
    $donnees = $archive_manager->restoreArchive($id_archive);
    
    if ($donnees) {
        echo json_encode([
            'success' => true,
            'data' => $donnees
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Impossible de décrypter l\'archive'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}

?>
