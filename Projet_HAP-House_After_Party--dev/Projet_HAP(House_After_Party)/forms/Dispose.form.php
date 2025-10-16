<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'une disposition
        if (isset($_POST['add_dispose'])) {
            $id_biens = intval($_POST['id_biens'] ?? 0);
            $id_pts_interet = intval($_POST['id_pts_interet'] ?? 0);
            $distance = trim($_POST['distance'] ?? '');

            if ($id_biens && $id_pts_interet && $distance !== '') {
                $stmt = $pdo->prepare('INSERT INTO Dispose (id_biens, id_pts_interet, distance) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE distance = VALUES(distance)');
                $stmt->execute([$id_biens, $id_pts_interet, $distance]);
                $message = "Disposition ajoutée avec succès.";
            }
        }

        // Suppression d'une disposition
        if (isset($_POST['delete_dispose']) && isset($_POST['id_biens_del']) && isset($_POST['id_pts_interet_del'])) {
            $id_biens = intval($_POST['id_biens_del']);
            $id_pts_interet = intval($_POST['id_pts_interet_del']);
            $stmt = $pdo->prepare('DELETE FROM Dispose WHERE id_biens = ? AND id_pts_interet = ?');
            $stmt->execute([$id_biens, $id_pts_interet]);
            $message = "Disposition supprimée avec succès.";
        }

        // Récupération des dispositions
        $disposes = $pdo->query('SELECT d.*, b.nom_biens, p.lib_pts_interet FROM Dispose d LEFT JOIN Biens b ON d.id_biens = b.id_biens LEFT JOIN Pts_Interet p ON d.id_pts_interet = p.id_pts_interet ORDER BY d.id_biens, d.id_pts_interet')->fetchAll(PDO::FETCH_ASSOC);

        // Biens et points d'intérêt
        $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
        $pts_interets = $pdo->query('SELECT id_pts_interet, lib_pts_interet FROM Pts_Interet')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Dispositions</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .dispose-list { margin-top: 20px; }
        .dispose-list table { border-collapse: collapse; width: 100%; }
        .dispose-list th, .dispose-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .dispose-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Dispositions (Biens - Points d'Intérêt)</h2>
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
            <select name="id_pts_interet" required>
                <option value="">-- Point d'Intérêt --</option>
                <?php foreach ($pts_interets as $p): ?>
                    <option value="<?= $p['id_pts_interet'] ?>"><?= htmlspecialchars($p['lib_pts_interet']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="distance" placeholder="Distance (ex: 5 km)" required>
            <input type="submit" name="add_dispose" value="Ajouter">
        </form>
        <div class="dispose-list">
            <table>
                <tr>
                    <th>Bien</th>
                    <th>Point d'Intérêt</th>
                    <th>Distance</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($disposes as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nom_biens']) ?></td>
                        <td><?= htmlspecialchars($d['lib_pts_interet']) ?></td>
                        <td><?= htmlspecialchars($d['distance']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette disposition ?');">
                                <input type="hidden" name="id_biens_del" value="<?= htmlspecialchars($d['id_biens']) ?>">
                                <input type="hidden" name="id_pts_interet_del" value="<?= htmlspecialchars($d['id_pts_interet']) ?>">
                                <button type="submit" name="delete_dispose">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
