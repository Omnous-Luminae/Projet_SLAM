<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un tarif
        if (isset($_POST['add_tarif'])) {
            $semaine = intval($_POST['semaine_tarif'] ?? 0);
            $annee = intval($_POST['annee_tarif'] ?? 0);
            $tarif = floatval($_POST['tarif'] ?? 0);
            $id_saison = intval($_POST['id_saison'] ?? 0);
            $id_biens = intval($_POST['id_biens'] ?? 0);

            if ($semaine && $annee && $tarif && $id_saison && $id_biens) {
                $stmt = $pdo->prepare('INSERT INTO Tarif (semaine_Tarif, année_Tarif, tarif, id_saison, id_biens) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$semaine, $annee, $tarif, $id_saison, $id_biens]);
                $message = "Tarif ajouté avec succès.";
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Suppression d'un tarif
        if (isset($_POST['delete_tarif']) && isset($_POST['id_tarif'])) {
            $id = intval($_POST['id_tarif']);
            $stmt = $pdo->prepare('DELETE FROM Tarif WHERE id_Tarif = ?');
            $stmt->execute([$id]);
            $message = "Tarif supprimé avec succès.";
        }

        // Récupération des tarifs
        $tarifs = [];
        $stmt = $pdo->query('SELECT t.*, s.lib_saison, b.nom_biens FROM Tarif t LEFT JOIN Saison s ON t.id_saison = s.id_saison LEFT JOIN Biens b ON t.id_biens = b.id_biens ORDER BY t.id_Tarif DESC');
        $tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des saisons et biens pour les selects
        $saisons = $pdo->query('SELECT id_saison, lib_saison FROM Saison')->fetchAll(PDO::FETCH_ASSOC);
        $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Tarifs</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .tarif-list { margin-top: 20px; }
        .tarif-list table { border-collapse: collapse; width: 100%; }
        .tarif-list th, .tarif-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .tarif-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Tarifs</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="number" name="semaine_tarif" placeholder="Semaine" min="1" max="52" required>
            <input type="number" name="annee_tarif" placeholder="Année" min="2020" required>
            <input type="number" step="0.01" name="tarif" placeholder="Tarif (€)" min="0" required>
            <select name="id_saison" required>
                <option value="">-- Saison --</option>
                <?php foreach ($saisons as $s): ?>
                    <option value="<?= $s['id_saison'] ?>"><?= htmlspecialchars($s['lib_saison']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_biens" required>
                <option value="">-- Bien --</option>
                <?php foreach ($biens as $b): ?>
                    <option value="<?= $b['id_biens'] ?>"><?= htmlspecialchars($b['nom_biens']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="add_tarif" value="Ajouter">
        </form>
        <div class="tarif-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Semaine</th>
                    <th>Année</th>
                    <th>Tarif (€)</th>
                    <th>Saison</th>
                    <th>Bien</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($tarifs as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['id_Tarif']) ?></td>
                        <td><?= htmlspecialchars($t['semaine_Tarif']) ?></td>
                        <td><?= htmlspecialchars($t['année_Tarif']) ?></td>
                        <td><?= htmlspecialchars($t['tarif']) ?></td>
                        <td><?= htmlspecialchars($t['lib_saison']) ?></td>
                        <td><?= htmlspecialchars($t['nom_biens']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce tarif ?');">
                                <input type="hidden" name="id_tarif" value="<?= htmlspecialchars($t['id_Tarif']) ?>">
                                <button type="submit" name="delete_tarif">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
