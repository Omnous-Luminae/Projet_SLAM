<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Biens/Biens.php';

$bienMessage = '';
$bien = [];
try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        $bienObj = new Biens($pdo);

        // Ajout d'un bien
        if (isset($_POST['add_biens'])) {
            $nom_biens = trim($_POST['nom_biens'] ?? '');
            if ($nom_biens !== '') {
                if ($bienObj->createBiens($nom_biens, null, null, null, null, null)) {
                    $bienMessage = "Bien ajouté avec succès.";
                } else {
                    $bienMessage = "Erreur lors de l'ajout.";
                }
            }
        }

        // Suppression d'un bien
        if (isset($_POST['delete_biens']) && isset($_POST['id_biens'])) {
            $id = intval($_POST['id_biens']);
            if ($bienObj->deleteBiens($id)) {
                $bienMessage = "Bien supprimé avec succès.";
            } else {
                $bienMessage = "Erreur lors de la suppression.";
            }
        }

        // Modification d'un bien
        if (isset($_POST['edit_biens']) && isset($_POST['id_biens']) && isset($_POST['nom_biens_edit'])) {
            $id = intval($_POST['id_biens']);
            $nom_biens_edit = trim($_POST['nom_biens_edit']);
            if ($nom_biens_edit !== '') {
                if ($bienObj->updateBiens($id, $nom_biens_edit, null, null, null, null, null)) {
                    $bienMessage = "Bien modifié avec succès.";
                } else {
                    $bienMessage = "Erreur lors de la modification.";
                }
            }
        }

        // Récupération des biens
        $biens = $bienObj->getAllBiens();
    }
} catch (Exception $e) {
    $saisonMessage = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Biens</title>
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
        <h2>Gestion des biens</h2>
        <?php if ($bienMessage): ?>
            <div class="bien-success"><?= htmlspecialchars($bienMessage) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" id="nom_biens" name="nom_biens" placeholder="Nom du bien" required>
            <input type="submit" name="add_biens" value="Ajouter">
        </form>
        <div class="bien-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($biens as $bien): ?>
                    <tr>
                        <td><?= htmlspecialchars($bien['id_biens']) ?></td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $bien['id_biens']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_biens" value="<?= htmlspecialchars($bien['id_biens']) ?>">
                                    <input type="text" name="nom_biens_edit" value="<?= htmlspecialchars($bien['nom_biens']) ?>" required>
                                    <button type="submit" name="edit_biens">Enregistrer</button>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($bien['nom_biens']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $bien['id_biens']): ?>
                                <!-- Rien, on est en mode édition -->
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_biens" value="<?= htmlspecialchars($bien['id_biens']) ?>">
                                    <button type="submit" name="edit_mode" value="<?= htmlspecialchars($bien['id_biens']) ?>">Modifier</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce bien ?');">
                                    <input type="hidden" name="id_biens" value="<?= htmlspecialchars($bien['id_biens']) ?>">
                                    <button type="submit" name="delete_biens">Supprimer</button>
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