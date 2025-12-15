<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Vérifier que c'est un admin (sécurité)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'animateur') {
    die("❌ Accès réservé aux administrateurs");
}

$message = '';

if (isset($_POST['valider_tous'])) {
    try {
        // Valider tous les avis
        $stmt = $pdo->prepare("UPDATE Reviews SET validated = 1, validated_at = NOW(), validated_by = ? WHERE validated = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $count = $stmt->rowCount();
        $message = "✅ $count avis ont été validés avec succès !";
    } catch (Exception $e) {
        $message = "❌ Erreur : " . $e->getMessage();
    }
}

if (isset($_POST['valider_un'])) {
    try {
        $id = intval($_POST['id_review']);
        $stmt = $pdo->prepare("UPDATE Reviews SET validated = 1, validated_at = NOW(), validated_by = ? WHERE id_review = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);
        $message = "✅ Avis #$id validé avec succès !";
    } catch (Exception $e) {
        $message = "❌ Erreur : " . $e->getMessage();
    }
}

// Récupérer les avis non validés
$avisNonValides = $pdo->query("
    SELECT r.*, b.nom_biens 
    FROM Reviews r 
    LEFT JOIN Biens b ON r.id_biens = b.id_biens 
    WHERE r.validated = 0 
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les avis validés
$avisValides = $pdo->query("
    SELECT r.*, b.nom_biens, a.user_name as validateur
    FROM Reviews r 
    LEFT JOIN Biens b ON r.id_biens = b.id_biens 
    LEFT JOIN Animateur a ON r.validated_by = a.id_animateur
    WHERE r.validated = 1 
    ORDER BY r.validated_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation des Avis</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #a100b8; }
        .message { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-weight: 600; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #a100b8; color: white; }
        tr:hover { background: #f9f9f9; }
        .stars { color: #f39c12; font-size: 1.2em; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-primary { background: #a100b8; color: white; }
        .btn-primary:hover { background: #8a00a0; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        .section { margin-top: 40px; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="Annonce.form.php" class="back-link">← Retour aux annonces</a>
        
        <h1>🔍 Validation des Avis</h1>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($avisNonValides) > 0): ?>
        <div class="section">
            <h2>⏳ Avis en attente de validation (<?= count($avisNonValides) ?>)</h2>
            
            <div class="warning-box">
                <strong>⚠️ Important :</strong> Les avis doivent être validés pour apparaître publiquement sur les annonces.
                <form method="post" style="margin-top: 10px;">
                    <button type="submit" name="valider_tous" class="btn btn-success" onclick="return confirm('Voulez-vous vraiment valider tous les avis en attente ?')">
                        ✅ Valider tous les avis (<?= count($avisNonValides) ?>)
                    </button>
                </form>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bien</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avisNonValides as $avis): ?>
                    <tr>
                        <td>#<?= $avis['id_review'] ?></td>
                        <td><?= htmlspecialchars($avis['nom_biens'] ?? 'Bien inconnu') ?></td>
                        <td class="stars">
                            <?php 
                            $rating = intval($avis['rating']);
                            echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                            echo " ($rating/5)";
                            ?>
                        </td>
                        <td><?= htmlspecialchars(substr($avis['content'] ?? 'Pas de commentaire', 0, 100)) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($avis['created_at'])) ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="id_review" value="<?= $avis['id_review'] ?>">
                                <button type="submit" name="valider_un" class="btn btn-primary">✓ Valider</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="message success">
            ✅ Aucun avis en attente de validation !
        </div>
        <?php endif; ?>
        
        <?php if (count($avisValides) > 0): ?>
        <div class="section">
            <h2>✅ Avis validés récents (<?= count($avisValides) ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Bien</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Validé le</th>
                        <th>Par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avisValides as $avis): ?>
                    <tr>
                        <td>#<?= $avis['id_review'] ?></td>
                        <td><?= htmlspecialchars($avis['nom_biens'] ?? 'Bien inconnu') ?></td>
                        <td class="stars">
                            <?php 
                            $rating = intval($avis['rating']);
                            echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                            echo " ($rating/5)";
                            ?>
                        </td>
                        <td><?= htmlspecialchars(substr($avis['content'] ?? 'Pas de commentaire', 0, 100)) ?></td>
                        <td><?= $avis['validated_at'] ? date('d/m/Y H:i', strtotime($avis['validated_at'])) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($avis['validateur'] ?? 'Admin') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
