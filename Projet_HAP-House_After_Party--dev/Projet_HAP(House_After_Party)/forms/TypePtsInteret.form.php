<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un type de point d'intérêt
        if (isset($_POST['add_type_pts_interet'])) {
            $lib = trim($_POST['lib_type_points_interet'] ?? '');
            if ($lib !== '') {
                // Vérifier si le type existe déjà
                $existing = $pdo->prepare('SELECT id_type_points_interet FROM Type_Pts_Interet WHERE lib_type_points_interet = ?');
                $existing->execute([$lib]);
                if ($existing->fetch()) {
                    $message = "Ce type de point d'intérêt existe déjà.";
                } else {
                    $stmt = $pdo->prepare('INSERT INTO Type_Pts_Interet (lib_type_points_interet) VALUES (?)');
                    $stmt->execute([$lib]);
                    $message = "Type de point d'intérêt ajouté avec succès.";
                }
            } else {
                $message = "Le nom du type ne peut pas être vide.";
            }
        }

        // Modification d'un type de point d'intérêt
        if (isset($_POST['edit_type_pts_interet']) && isset($_POST['id_type_points_interet']) && isset($_POST['lib_type_points_interet_edit'])) {
            $id = intval($_POST['id_type_points_interet']);
            $lib_edit = trim($_POST['lib_type_points_interet_edit']);
            if ($lib_edit !== '') {
                // Vérifier si le type existe déjà (sauf pour lui-même)
                $existing = $pdo->prepare('SELECT id_type_points_interet FROM Type_Pts_Interet WHERE lib_type_points_interet = ? AND id_type_points_interet != ?');
                $existing->execute([$lib_edit, $id]);
                if ($existing->fetch()) {
                    $message = "Ce type de point d'intérêt existe déjà.";
                } else {
                    $stmt = $pdo->prepare('UPDATE Type_Pts_Interet SET lib_type_points_interet = ? WHERE id_type_points_interet = ?');
                    $stmt->execute([$lib_edit, $id]);
                    $message = "Type de point d'intérêt modifié avec succès.";
                }
            } else {
                $message = "Le nom du type ne peut pas être vide.";
            }
        }

        // Suppression d'un type de point d'intérêt
        if (isset($_POST['delete_type_pts_interet']) && isset($_POST['id_type_points_interet'])) {
            $id = intval($_POST['id_type_points_interet']);
            $stmt = $pdo->prepare('DELETE FROM Type_Pts_Interet WHERE id_type_points_interet = ?');
            $stmt->execute([$id]);
            $message = "Type de point d'intérêt supprimé avec succès.";
        }

        // Récupération des types
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet ORDER BY id_type_points_interet DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Types de Points d'Intérêt</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/forms.css">
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Types de Points d'Intérêt</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" id="lib_type_points_interet" name="lib_type_points_interet" placeholder="Nom du type" required>
            <input type="submit" name="add_type_pts_interet" value="Ajouter">
        </form>
        <div class="type-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($types as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['id_type_points_interet']) ?></td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $t['id_type_points_interet']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_type_points_interet" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                                    <input type="text" name="lib_type_points_interet_edit" value="<?= htmlspecialchars($t['lib_type_points_interet']) ?>" required>
                                    <button type="submit" name="edit_type_pts_interet">Enregistrer</button>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($t['lib_type_points_interet']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $t['id_type_points_interet']): ?>
                                <!-- Rien, on est en mode édition -->
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_type_points_interet" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                                    <button type="submit" name="edit_mode" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">Modifier</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce type ?');">
                                    <input type="hidden" name="id_type_points_interet" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                                    <button type="submit" name="delete_type_pts_interet">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <script src="../js/confirm_delete.js"></script>
</body>
</html>
