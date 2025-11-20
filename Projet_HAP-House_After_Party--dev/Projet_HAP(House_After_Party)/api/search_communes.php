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

    $stmt = $pdo->prepare("SELECT id_commune, nom_commune, cp_commune FROM Commune WHERE LOWER(nom_commune) LIKE LOWER(?) ORDER BY nom_commune LIMIT 10");
    $stmt->execute(['%' . $query . '%']);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'id' => $row['id_commune'],
            'label' => $row['nom_commune'] . ' (' . $row['cp_commune'] . ')',
            'value' => $row['nom_commune']
        ];
    }

    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
