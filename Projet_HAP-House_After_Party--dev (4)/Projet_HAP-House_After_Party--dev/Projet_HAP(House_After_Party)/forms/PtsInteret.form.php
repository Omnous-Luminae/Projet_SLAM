<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un point d'intérêt
        if (isset($_POST['add_pts_interet'])) {
            $lib = trim($_POST['lib_pts_interet'] ?? '');
            $desc = trim($_POST['description_pts_interet'] ?? '');
            $id_type = intval($_POST['id_type_points_interet'] ?? 0);

            if ($lib && $desc && $id_type) {
                $stmt = $pdo->prepare('INSERT INTO Pts_Interet (lib_pts_interet, description_pts_interet, id_type_points_interet) VALUES (?, ?, ?)');
                $stmt->execute([$lib, $desc, $id_type]);
                $message = "Point d'intérêt ajouté avec succès.";
            }
        }

        // Suppression d'un point d'intérêt
        if (isset($_POST['delete_pts_interet']) && isset($_POST['id_pts_interet'])) {
            $id = intval($_POST['id_pts_interet']);
            $stmt = $pdo->prepare('DELETE FROM Pts_Interet WHERE id_pts_interet = ?');
            $stmt->execute([$id]);
            $message = "Point d'intérêt supprimé avec succès.";
        }

        // Récupération des points d'intérêt
        $pts_interets = $pdo->query('SELECT p.*, t.lib_type_points_interet FROM Pts_Interet p LEFT JOIN Type_Pts_Interet t ON p.id_type_points_interet = t.id_type_points_interet ORDER BY p.id_pts_interet DESC')->fetchAll(PDO::FETCH_ASSOC);

        // Types
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Points d'Intérêt</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select, textarea { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="text"], textarea { flex: 1; min-width: 200px; }
        textarea { min-height: 60px; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .pts-list { margin-top: 20px; }
        .pts-list table { border-collapse: collapse; width: 100%; }
        .pts-list th, .pts-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .pts-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Points d'Intérêt</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="lib_pts_interet" placeholder="Nom du point d'intérêt" required>
            <textarea name="description_pts_interet" placeholder="Description" required></textarea>
            <select name="id_type_points_interet" required>
                <option value="">-- Type --</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id_type_points_interet'] ?>"><?= htmlspecialchars($t['lib_type_points_interet']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="add_pts_interet" value="Ajouter">
        </form>
        <div class="pts-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($pts_interets as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id_pts_interet']) ?></td>
                        <td><?= htmlspecialchars($p['lib_pts_interet']) ?></td>
                        <td><?= htmlspecialchars($p['description_pts_interet']) ?></td>
                        <td><?= htmlspecialchars($p['lib_type_points_interet']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce point d\'intérêt ?');">
                                <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($p['id_pts_interet']) ?>">
                                <button type="submit" name="delete_pts_interet">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
