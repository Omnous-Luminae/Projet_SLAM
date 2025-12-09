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
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Types de Biens - House After Party</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="../Css/forms.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🏠 Gestion des Types de Biens</h2>
            <p>Gérez les différents types de biens disponibles</p>
        </div>

        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <?php if ($typeBienMessage): ?>
            <div class="message success"><?= htmlspecialchars($typeBienMessage) ?></div>
        <?php endif; ?>

        <section class="form-section">
            <h3>Ajouter un nouveau type de bien</h3>
            <form method="post" class="form-grid">
                <div class="form-group">
                    <label for="lib_type_bien">Nom du type de bien</label>
                    <input type="text" id="lib_type_bien" name="lib_type_bien" placeholder="Ex: Maison, Appartement..." required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_type_bien" class="btn btn-primary">Ajouter le type</button>
                </div>
            </form>
        </section>

        <section class="data-section">
            <h3>Types de biens existants</h3>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($typesBien as $typeBien): ?>
                            <tr>
                                <td><?= htmlspecialchars($typeBien['id_type_biens']) ?></td>
                                <td>
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $typeBien['id_type_biens']): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id_type_bien" value="<?= htmlspecialchars($typeBien['id_type_biens']) ?>">
                                            <input type="text" name="lib_type_bien_edit" value="<?= htmlspecialchars($typeBien['designation_type_bien']) ?>" required>
                                            <button type="submit" name="edit_type_bien" class="btn btn-primary">Enregistrer</button>
                                        </form>
                                    <?php else: ?>
                                        <?= htmlspecialchars($typeBien['designation_type_bien']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $typeBien['id_type_biens']): ?>
                                        <!-- Rien, on est en mode édition -->
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($typeBien['id_type_biens']) ?>">
                                            <button type="submit" class="btn btn-secondary">Modifier</button>
                                        </form>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type de bien ?');">
                                            <input type="hidden" name="id_type_bien" value="<?= htmlspecialchars($typeBien['id_type_biens']) ?>">
                                            <button type="submit" name="delete_type_bien" class="btn btn-danger">Supprimer</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="../js/confirm_delete.js"></script>
</body>
</html>
