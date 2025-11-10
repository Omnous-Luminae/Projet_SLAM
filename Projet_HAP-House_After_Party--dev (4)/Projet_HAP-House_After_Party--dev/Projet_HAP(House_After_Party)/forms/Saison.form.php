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
                // Vérifier si la saison existe déjà
                $existing = $pdo->prepare('SELECT id_saison FROM Saison WHERE lib_saison = ?');
                $existing->execute([$lib_saison]);
                if ($existing->fetch()) {
                    $saisonMessage = "Cette saison existe déjà.";
                } elseif ($saisonObj->createSaison($lib_saison)) {
                    $saisonMessage = "Saison ajoutée avec succès.";
                } else {
                    $saisonMessage = "Erreur lors de l'ajout.";
                }
            } else {
                $saisonMessage = "Le nom de la saison ne peut pas être vide.";
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
                // Vérifier si la saison existe déjà (sauf pour elle-même)
                $existing = $pdo->prepare('SELECT id_saison FROM Saison WHERE lib_saison = ? AND id_saison != ?');
                $existing->execute([$lib_saison_edit, $id]);
                if ($existing->fetch()) {
                    $saisonMessage = "Cette saison existe déjà.";
                } elseif ($saisonObj->updateSaison($id, $lib_saison_edit)) {
                    $saisonMessage = "Saison modifiée avec succès.";
                } else {
                    $saisonMessage = "Erreur lors de la modification.";
                }
            } else {
                $saisonMessage = "Le nom de la saison ne peut pas être vide.";
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
    <link rel="stylesheet" href="../Css/forms.css">
</head>
<body>
    <div class="form-container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <div class="form-header">
            <h2>🌸 Gestion des Saisons</h2>
            <p>Ajoutez, modifiez et supprimez les saisons de location</p>
        </div>

        <?php if ($saisonMessage): ?>
            <div class="message success"><?= htmlspecialchars($saisonMessage) ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h3>Ajouter une nouvelle saison</h3>
            <form method="post">
                <div class="form-group">
                    <label for="lib_saison">Nom de la saison</label>
                    <input type="text" id="lib_saison" name="lib_saison" placeholder="Ex: Été 2024" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_saison" class="btn btn-primary">Ajouter la saison</button>
                </div>
            </form>
        </div>

        <section class="data-section">
            <h3>Saisons existantes</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($saisons as $saison): ?>
                        <tr>
                            <td><?= htmlspecialchars($saison['id_saison']) ?></td>
                            <td>
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $saison['id_saison']): ?>
                                    <input type="text" name="lib_saison_edit" value="<?= htmlspecialchars($saison['lib_saison']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($saison['lib_saison']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $saison['id_saison']): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="id_saison" value="<?= htmlspecialchars($saison['id_saison']) ?>">
                                        <button type="submit" name="edit_saison" class="btn btn-primary">Enregistrer</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="id_saison" value="<?= htmlspecialchars($saison['id_saison']) ?>">
                                        <button type="submit" name="edit_mode" value="<?= htmlspecialchars($saison['id_saison']) ?>" class="btn btn-secondary">Modifier</button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette saison ?');">
                                        <input type="hidden" name="id_saison" value="<?= htmlspecialchars($saison['id_saison']) ?>">
                                        <button type="submit" name="delete_saison" class="btn btn-danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
