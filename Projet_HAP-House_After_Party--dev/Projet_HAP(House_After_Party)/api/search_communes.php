<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id_commune, nom_commune, cp_commune FROM Commune WHERE nom_commune LIKE :query LIMIT 10");
        $stmt->execute(['query' => '%' . $query . '%']);
        $communes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = array_map(function($commune) {
            return [
                'id' => $commune['id_commune'],
                'label' => $commune['nom_commune'] . ' (' . $commune['cp_commune'] . ')'
            ];
        }, $communes);

        echo json_encode($results);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode([]);
}
?>
