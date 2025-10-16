<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Saison/Saison.php';

$saisonMessage = '';
$saisons = [];
try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        $saisonObj = new Saison(null, null, $pdo);

        // Ajout d'une saison
        if (isset($_POST['add_saison'])) {
            $lib_saison = trim($_POST['lib_saison'] ?? '');
            if ($lib_saison !== '') {
                if ($saisonObj->createSaison($lib_saison)) {
                    $saisonMessage = "Saison ajoutée avec succès.";
                } else {
                    $saisonMessage = "Erreur lors de l'ajout.";
                }
            }
        }

        // Suppression d'une saison
        if (isset($_POST['delete_saison']) && isset($_POST['id_saison'])) {
            $id = intval($_POST['id_saison']);
            if ($saisonObj->deleteSaison($id)) {
                $saisonMessage = "Saison supprimée avec succès.";
            } else {
                $saisonMessage = "Erreur lors de la suppression.";
            }
        }

        // Modification d'une saison
        if (isset($_POST['edit_saison']) && isset($_POST['id_saison']) && isset($_POST['lib_saison_edit'])) {
            $id = intval($_POST['id_saison']);
            $lib_saison_edit = trim($_POST['lib_saison_edit']);
            if ($lib_saison_edit !== '') {
                if ($saisonObj->updateSaison($id, $lib_saison_edit)) {
                    $saisonMessage = "Saison modifiée avec succès.";
                } else {
                    $saisonMessage = "Erreur lors de la modification.";
                }
            }
        }

        // Récupération des saisons
        $saisons = $saisonObj->readAllSaison();
    }
} catch (Exception $e) {
    $saisonMessage = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Saisons</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input[type="text"] { flex: 1; padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .saison-list { margin-top: 20px; }
        .saison-list table { border-collapse: collapse; width: 100%; }
        .saison-list th, .saison-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .saison-list th { background: #f3e6fa; }
        .saison-success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Saisons</h2>
        <?php if ($saisonMessage): ?>
            <div class="saison-success"><?= htmlspecialchars($saisonMessage) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" id="lib_saison" name="lib_saison" placeholder="Nom de la saison" required>
            <input type="submit" name="add_saison" value="Ajouter">
        </form>
        <div class="saison-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($saisons as $saison): ?>
                    <tr>
                        <td><?= htmlspecialchars($saison['id_saison']) ?></td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $saison['id_saison']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_saison" value="<?= htmlspecialchars($saison['id_saison']) ?>">
                                    <input type="text" name="lib_saison_edit" value="<?= htmlspecialchars($saison['lib_saison']) ?>" required>
                                    <button type="submit" name="edit_saison">Enregistrer</button>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($saison['lib_saison']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $saison['id_saison']): ?>
                                <!-- Rien, on est en mode édition -->
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_saison" value="<?= htmlspecialchars($saison['id_saison']) ?>">
                                    <button type="submit" name="edit_mode" value="<?= htmlspecialchars($saison['id_saison']) ?>">Modifier</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette saison ?');">
                                    <input type="hidden" name="id_saison" value="<?= htmlspecialchars($saison['id_saison']) ?>">
                                    <button type="submit" name="delete_saison">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
