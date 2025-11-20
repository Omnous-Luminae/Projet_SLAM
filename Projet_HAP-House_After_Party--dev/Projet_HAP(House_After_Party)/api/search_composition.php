<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_prestation, lib_prestation FROM Prestation WHERE LOWER(lib_prestation) LIKE LOWER(?) LIMIT 10");
    $stmt->execute(['%' . $query . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($results as $row) {
        $data[] = [
            'id' => $row['id_prestation'],
            'label' => $row['lib_prestation'],
            'value' => $row['lib_prestation']
        ];
    }

    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
