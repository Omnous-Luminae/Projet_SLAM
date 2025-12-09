<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Pts_Interet/PtsInteret.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        $ptsInteretObj = new PtsInteret(null, null, null, null, null, $pdo);

        // Ajout d'un point d'intérêt
        if (isset($_POST['add_pts_interet'])) {
            $lib = trim($_POST['lib_pts_interet'] ?? '');
            $desc = trim($_POST['description_pts_interet'] ?? '');
            $type = intval($_POST['id_type_points_interet'] ?? 0);
            $commune = intval($_POST['id_commune'] ?? 0);
            if ($lib !== '' && $type > 0 && $commune > 0) {
                if ($ptsInteretObj->createPtsInteret($lib, $desc, $type, $commune)) {
                    $message = "Point d'intérêt ajouté avec succès.";
                } else {
                    $message = "Erreur lors de l'ajout.";
                }
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Modification d'un point d'intérêt
        if (isset($_POST['edit_pts_interet']) && isset($_POST['id_pts_interet'])) {
            $id = intval($_POST['id_pts_interet']);
            $lib = trim($_POST['lib_pts_interet_edit'] ?? '');
            $desc = trim($_POST['description_pts_interet_edit'] ?? '');
            if ($lib !== '') {
                if ($ptsInteretObj->updatePtsInteret($id, $lib, $desc)) {
                    $message = "Point d'intérêt modifié avec succès.";
                } else {
                    $message = "Erreur lors de la modification.";
                }
            } else {
                $message = "Le nom ne peut pas être vide.";
            }
        }

        // Suppression d'un point d'intérêt
        if (isset($_POST['delete_pts_interet']) && isset($_POST['id_pts_interet'])) {
            $id = intval($_POST['id_pts_interet']);
            if ($ptsInteretObj->deletePtsInteret($id)) {
                $message = "Point d'intérêt supprimé avec succès.";
            } else {
                $message = "Erreur lors de la suppression.";
            }
        }

        // Récupération des points d'intérêt
        $ptsInterets = $ptsInteretObj->getAllPtsInteret();

        // Récupération des types pour le dropdown
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet ORDER BY lib_type_points_interet')->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des communes pour le dropdown
        $communes = $pdo->query('SELECT * FROM Commune ORDER BY nom_commune')->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="../Css/forms.css">
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Points d'Intérêt</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" id="lib_pts_interet" name="lib_pts_interet" placeholder="Nom du point d'intérêt" required>
            <textarea id="description_pts_interet" name="description_pts_interet" placeholder="Description"></textarea>
            <select name="id_type_points_interet" required>
                <option value="">Sélectionner un type</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= htmlspecialchars($t['id_type_points_interet']) ?>"><?= htmlspecialchars($t['lib_type_points_interet']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_commune" required>
                <option value="">Sélectionner une commune</option>
                <?php foreach ($communes as $c): ?>
                    <option value="<?= htmlspecialchars($c['id_commune']) ?>"><?= htmlspecialchars($c['nom_commune']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="add_pts_interet" value="Ajouter">
        </form>
        <div class="pts-interet-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Commune</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($ptsInterets as $pi): ?>
                    <tr>
                        <td><?= htmlspecialchars($pi['id_pts_interet']) ?></td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $pi['id_pts_interet']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($pi['id_pts_interet']) ?>">
                                    <input type="text" name="lib_pts_interet_edit" value="<?= htmlspecialchars($pi['lib_pts_interet']) ?>" required>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($pi['lib_pts_interet']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $pi['id_pts_interet']): ?>
                                <textarea name="description_pts_interet_edit" form="edit_form_<?= $pi['id_pts_interet'] ?>"><?= htmlspecialchars($pi['description_pts_interet']) ?></textarea>
                            <?php else: ?>
                                <?= htmlspecialchars($pi['description_pts_interet']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($pi['lib_type_points_interet']) ?></td>
                        <td><?= htmlspecialchars($pi['nom_commune']) ?></td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $pi['id_pts_interet']): ?>
                                <form id="edit_form_<?= $pi['id_pts_interet'] ?>" method="post" style="display:inline;">
                                    <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($pi['id_pts_interet']) ?>">
                                    <button type="submit" name="edit_pts_interet">Enregistrer</button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($pi['id_pts_interet']) ?>">
                                    <button type="submit" name="edit_mode" value="<?= htmlspecialchars($pi['id_pts_interet']) ?>">Modifier</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce point d\'intérêt ?');">
                                    <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($pi['id_pts_interet']) ?>">
                                    <button type="submit" name="delete_pts_interet">Supprimer</button>
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
