<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id_biens, nom_biens FROM Biens WHERE LOWER(nom_biens) LIKE LOWER(?) ORDER BY nom_biens LIMIT 10");
    $stmt->execute(['%' . $query . '%']);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['id_biens'],
            'label' => $row['nom_biens'],
            'value' => $row['nom_biens']
        ];
    }

    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
