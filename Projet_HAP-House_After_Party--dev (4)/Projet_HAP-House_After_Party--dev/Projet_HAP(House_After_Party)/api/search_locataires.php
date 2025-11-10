<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');
$q = $_GET['q'] ?? '';
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}
$stmt = $pdo->prepare('SELECT id_locataire, nom_locataire, prenom_locataire FROM Locataire WHERE nom_locataire LIKE ? OR prenom_locataire LIKE ? LIMIT 10');
$stmt->execute(["%$q%", "%$q%"]);
$results = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $results[] = [
        'id' => $row['id_locataire'],
        'label' => $row['nom_locataire'] . ' ' . $row['prenom_locataire']
    ];
}
echo json_encode($results);
