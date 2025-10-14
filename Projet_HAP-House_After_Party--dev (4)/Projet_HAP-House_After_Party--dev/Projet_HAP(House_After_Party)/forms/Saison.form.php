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
<div class="form-section">
    <h3>Gestion des Saisons</h3>
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
