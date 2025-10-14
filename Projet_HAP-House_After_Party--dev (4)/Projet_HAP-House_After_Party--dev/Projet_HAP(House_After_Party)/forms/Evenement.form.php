<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un événement
        if (isset($_POST['add_evenement'])) {
            $nom = trim($_POST['nom_evenement'] ?? '');
            $date_debut = trim($_POST['date_debut_evenement'] ?? '');
            $date_fin = trim($_POST['date_fin_evenement'] ?? '');
            $desc = trim($_POST['description_evenement'] ?? '');
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type = intval($_POST['id_type_evenement'] ?? 0);

            if ($nom && $date_debut && $date_fin && $desc && $id_commune && $id_type) {
                $stmt = $pdo->prepare('INSERT INTO Evenement (nom_evenement, date_debut_evenement, date_fin_evenement, description_evenement, id_commune, id_type_evenement) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$nom, $date_debut, $date_fin, $desc, $id_commune, $id_type]);
                $message = "Événement ajouté avec succès.";
            }
        }

        // Suppression d'un événement
        if (isset($_POST['delete_evenement']) && isset($_POST['id_evenement'])) {
            $id = intval($_POST['id_evenement']);
            $stmt = $pdo->prepare('DELETE FROM Evenement WHERE id_evenement = ?');
            $stmt->execute([$id]);
            $message = "Événement supprimé avec succès.";
        }

        // Récupération des événements
        $evenements = $pdo->query('SELECT e.*, c.nom_commune, t.lib_type_evenement FROM Evenement e LEFT JOIN Commune c ON e.id_commune = c.id_commune LEFT JOIN Type_Evenement t ON e.id_type_evenement = t.id_type_evenement ORDER BY e.id_evenement DESC')->fetchAll(PDO::FETCH_ASSOC);

        // Communes et types
        $communes = $pdo->query('SELECT id_commune, nom_commune FROM Commune LIMIT 100')->fetchAll(PDO::FETCH_ASSOC); // Limit for performance
        $types = $pdo->query('SELECT * FROM Type_Evenement')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Événements</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select, textarea { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="text"], textarea { flex: 1; min-width: 200px; }
        textarea { min-height: 60px; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .evenement-list { margin-top: 20px; }
        .evenement-list table { border-collapse: collapse; width: 100%; }
        .evenement-list th, .evenement-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .evenement-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Événements</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="nom_evenement" placeholder="Nom de l'événement" required>
            <input type="date" name="date_debut_evenement" placeholder="Date début" required>
            <input type="date" name="date_fin_evenement" placeholder="Date fin" required>
            <textarea name="description_evenement" placeholder="Description" required></textarea>
            <select name="id_commune" required>
                <option value="">-- Commune --</option>
                <?php foreach ($communes as $c): ?>
                    <option value="<?= $c['id_commune'] ?>"><?= htmlspecialchars($c['nom_commune']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_type_evenement" required>
                <option value="">-- Type --</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id_type_evenement'] ?>"><?= htmlspecialchars($t['lib_type_evenement']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="add_evenement" value="Ajouter">
        </form>
        <div class="evenement-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Description</th>
                    <th>Commune</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($evenements as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['id_evenement']) ?></td>
                        <td><?= htmlspecialchars($e['nom_evenement']) ?></td>
                        <td><?= htmlspecialchars($e['date_debut_evenement']) ?></td>
                        <td><?= htmlspecialchars($e['date_fin_evenement']) ?></td>
                        <td><?= htmlspecialchars($e['description_evenement']) ?></td>
                        <td><?= htmlspecialchars($e['nom_commune']) ?></td>
                        <td><?= htmlspecialchars($e['lib_type_evenement']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cet événement ?');">
                                <input type="hidden" name="id_evenement" value="<?= htmlspecialchars($e['id_evenement']) ?>">
                                <button type="submit" name="delete_evenement">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
