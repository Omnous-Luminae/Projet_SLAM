<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$code_insee = trim($_GET['code_insee'] ?? '');

if (empty($code_insee)) {
    echo json_encode(['success' => false, 'message' => 'Code INSEE manquant']);
    exit;
}

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion']);
        exit;
    }

    // Pad the code to 5 digits if needed
    $code_insee_padded = str_pad($code_insee, 5, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("SELECT id_commune, nom_commune, cp_commune FROM Commune WHERE code_insee = ? LIMIT 1");
    $stmt->execute([$code_insee_padded]);
    $commune = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($commune) {
        echo json_encode([
            'success' => true,
            'id_commune' => $commune['id_commune'],
            'nom_commune' => $commune['nom_commune'],
            'cp_commune' => $commune['cp_commune']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Commune non trouvée']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
