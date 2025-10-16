<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Type_Bien/TypeBien.php';

$typeBienMessage = '';
$typeBien = [];
try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        $typebienobj= new TypeBien(null, null, $pdo);

if(isset($_POST['add_type_bien'])){
    $lib_type_bien=trim($_POST['lib_type_bien'] ?? '');
    if($lib_type_bien !== ''){
        if($typebienobj->createTypeBien($lib_type_bien)){
            $typeBienMessage = "Type de bien ajouté avec succès.";
        } else {
            $typeBienMessage = "Erreur lors de l'ajout.";
        }
    }
}

if(isset($_POST['delete_type_bien']) && isset($_POST['id_type_bien'])){
    $id=intval($_POST['id_type_bien']);
    if($typebienobj->deleteTypeBien($id)){
        $typeBienMessage = "Type de bien supprimé avec succès.";
    } else {
        $typeBienMessage = "Erreur lors de la suppression.";
    }
}

        // Modification d'un type de bien
        if (isset($_POST['edit_type_bien']) && isset($_POST['id_type_bien']) && isset($_POST['lib_type_bien_edit'])) {
            $id = intval($_POST['id_type_bien']);
            $lib_type_bien_edit = trim($_POST['lib_type_bien_edit']);
            if ($lib_type_bien_edit !== '') {
                if ($typebienobj->updateTypeBien($id, $lib_type_bien_edit)) {
                    $typeBienMessage = "Type de bien modifié avec succès.";
                } else {
                    $typeBienMessage = "Erreur lors de la modification.";
                }
            }
        }
        
$typesBien = $typebienobj->readAllTypeBien();
    }
} catch (Exception $e) {
    $typeBienMessage = "Erreur : " . $e->getMessage();
}




?>
<div class="form-section">
    <h3>Gestion des Types de Bien</h3>
        <?php if ($typeBienMessage): ?>
            <div class="typebien-success"><?= htmlspecialchars($typeBienMessage) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" id="lib_type_bien" name="lib_type_bien" placeholder="Nom du type de bien" required>
            <input type="submit" name="add_type_bien" value="Ajouter">
        </form>
        <div class="typebien-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($typesBien as $typeBien): ?>
                    <tr>
                        <td><?= htmlspecialchars($typeBien['id_type_biens']) ?></td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $typeBien['id_type_biens']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_type_bien" value="<?= htmlspecialchars($typeBien['id_type_biens']) ?>">
                                    <input type="text" name="lib_type_bien_edit" value="<?= htmlspecialchars($typeBien['designation_type_bien']) ?>" required>
                                    <button type="submit" name="edit_type_bien">Enregistrer</button>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($typeBien['designation_type_bien']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $typeBien['id_type_biens']): ?>
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($typeBien['id_type_biens']) ?>">
                                    <button type="submit">Modifier</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type de bien ?');">
                                    <input type="hidden" name="id_type_bien" value="<?= htmlspecialchars($typeBien['id_type_biens']) ?>">
                                    <button type="submit" name="delete_type_bien">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
</div>
