<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'une prestation
        if (isset($_POST['add_prestation'])) {
            $lib = trim($_POST['lib_prestation'] ?? '');
            if ($lib !== '') {
                $stmt = $pdo->prepare('INSERT INTO Prestation (lib_prestation) VALUES (?)');
                $stmt->execute([$lib]);
                $message = "Prestation ajoutée avec succès.";
            }
        }

        // Suppression d'une prestation
        if (isset($_POST['delete_prestation']) && isset($_POST['id_prestation'])) {
            $id = intval($_POST['id_prestation']);
            $stmt = $pdo->prepare('DELETE FROM Prestation WHERE id_prestation = ?');
            $stmt->execute([$id]);
            $message = "Prestation supprimée avec succès.";
        }

        // Récupération des prestations
        $prestations = $pdo->query('SELECT * FROM Prestation ORDER BY id_prestation DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Prestations</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input[type="text"] { flex: 1; padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .prestation-list { margin-top: 20px; }
        .prestation-list table { border-collapse: collapse; width: 100%; }
        .prestation-list th, .prestation-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .prestation-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Prestations</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" id="lib_prestation" name="lib_prestation" placeholder="Nom de la prestation" required>
            <input type="submit" name="add_prestation" value="Ajouter">
        </form>
        <div class="prestation-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($prestations as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id_prestation']) ?></td>
                        <td><?= htmlspecialchars($p['lib_prestation']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette prestation ?');">
                                <input type="hidden" name="id_prestation" value="<?= htmlspecialchars($p['id_prestation']) ?>">
                                <button type="submit" name="delete_prestation">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
