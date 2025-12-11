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

    // Check if code_insee column exists
    $hasCodeInsee = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM Commune LIKE 'code_insee'");
        $hasCodeInsee = $checkStmt->rowCount() > 0;
    } catch (Exception $e) {
        $hasCodeInsee = false;
    }

    if ($hasCodeInsee) {
        $stmt = $pdo->prepare("SELECT id_commune, nom_commune, cp_commune, code_insee FROM Commune WHERE LOWER(nom_commune) LIKE LOWER(?) ORDER BY nom_commune LIMIT 10");
    } else {
        $stmt = $pdo->prepare("SELECT id_commune, nom_commune, cp_commune FROM Commune WHERE LOWER(nom_commune) LIKE LOWER(?) ORDER BY nom_commune LIMIT 10");
    }
    
    $stmt->execute(['%' . $query . '%']);

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result = [
            'id' => $row['id_commune'],
            'label' => $row['nom_commune'] . ' (' . $row['cp_commune'] . ')',
            'value' => $row['nom_commune']
        ];
        
        // Add code_insee if it exists
        if ($hasCodeInsee && isset($row['code_insee'])) {
            $result['code_insee'] = $row['code_insee'];
        } else {
            // Use cp_commune as fallback (first 2 digits are department code, can be used for citycode approximation)
            $result['code_insee'] = $row['cp_commune'];
        }
        
        $results[] = $result;
    }

    echo json_encode($results);
} catch (Exception $e) {
    // Log error for debugging
    error_log("Error in search_communes.php: " . $e->getMessage());
    echo json_encode([]);
}
?>
