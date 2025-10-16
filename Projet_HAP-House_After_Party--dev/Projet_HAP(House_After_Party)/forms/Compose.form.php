<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'une composition
        if (isset($_POST['add_compose'])) {
            $id_biens = intval($_POST['id_biens'] ?? 0);
            $id_prestation = intval($_POST['id_prestation'] ?? 0);
            $quantite = intval($_POST['quantite'] ?? 0);

            if ($id_biens && $id_prestation && $quantite > 0) {
                $stmt = $pdo->prepare('INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)');
                $stmt->execute([$id_biens, $id_prestation, $quantite]);
                $message = "Composition ajoutée avec succès.";
            }
        }

        // Suppression d'une composition
        if (isset($_POST['delete_compose']) && isset($_POST['id_biens_del']) && isset($_POST['id_prestation_del'])) {
            $id_biens = intval($_POST['id_biens_del']);
            $id_prestation = intval($_POST['id_prestation_del']);
            $stmt = $pdo->prepare('DELETE FROM Compose WHERE id_biens = ? AND id_prestation = ?');
            $stmt->execute([$id_biens, $id_prestation]);
            $message = "Composition supprimée avec succès.";
        }

        // Récupération des compositions
        $composes = $pdo->query('SELECT c.*, b.nom_biens, p.lib_prestation FROM Compose c LEFT JOIN Biens b ON c.id_biens = b.id_biens LEFT JOIN Prestation p ON c.id_prestation = p.id_prestation ORDER BY c.id_biens, c.id_prestation')->fetchAll(PDO::FETCH_ASSOC);

        // Biens et prestations
        $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
        $prestations = $pdo->query('SELECT id_prestation, lib_prestation FROM Prestation')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Compositions</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .compose-list { margin-top: 20px; }
        .compose-list table { border-collapse: collapse; width: 100%; }
        .compose-list th, .compose-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .compose-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Compositions (Biens - Prestations)</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <select name="id_biens" required>
                <option value="">-- Bien --</option>
                <?php foreach ($biens as $b): ?>
                    <option value="<?= $b['id_biens'] ?>"><?= htmlspecialchars($b['nom_biens']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_prestation" required>
                <option value="">-- Prestation --</option>
                <?php foreach ($prestations as $p): ?>
                    <option value="<?= $p['id_prestation'] ?>"><?= htmlspecialchars($p['lib_prestation']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="quantite" placeholder="Quantité" min="1" required>
            <input type="submit" name="add_compose" value="Ajouter">
        </form>
        <div class="compose-list">
            <table>
                <tr>
                    <th>Bien</th>
                    <th>Prestation</th>
                    <th>Quantité</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($composes as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nom_biens']) ?></td>
                        <td><?= htmlspecialchars($c['lib_prestation']) ?></td>
                        <td><?= htmlspecialchars($c['quantite']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette composition ?');">
                                <input type="hidden" name="id_biens_del" value="<?= htmlspecialchars($c['id_biens']) ?>">
                                <input type="hidden" name="id_prestation_del" value="<?= htmlspecialchars($c['id_prestation']) ?>">
                                <button type="submit" name="delete_compose">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
