<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Biens/Biens.php';
require_once __DIR__ . '/../classes/Compose/Compose.php';
require_once __DIR__ . '/../classes/Tarif/Tarif.php';
require_once __DIR__ . '/../classes/Prestation/Prestation.php';
require_once __DIR__ . '/../classes/Saison/Saison.php';

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
                    // Récupérer l'id du bien ajouté
                    $id_biens = $pdo->lastInsertId();
                    // Traitement des photos
                    if (!empty($_FILES['photos']['name'][0])) {
                        foreach ($_FILES['photos']['name'] as $key => $name) {
                            $tmp_name = $_FILES['photos']['tmp_name'][$key];
                            $target_dir = __DIR__ . '/../images/uploads/';
                            $filename = uniqid() . '_' . basename($name);
                            $target_file = $target_dir . $filename;
                            if (move_uploaded_file($tmp_name, $target_file)) {
                                // Enregistrement en base
                                $stmt = $pdo->prepare('INSERT INTO Photos (lien_photo, id_biens) VALUES (?, ?)');
                                $stmt->execute(['Projet_HAP(House_After_Party)/images/uploads/' . $filename, $id_biens]);
                            }
                        }
                    }
                } else {
                    $bienMessage = "Erreur lors de l'ajout.";
                }
            }
        }

        // Gestion de la composition (liaison Prestation <-> Bien)
        if (isset($_POST['add_compose']) && isset($_POST['id_biens_compose'])) {
            $composeObj = new Compose(null, $pdo);
            $id_biens_compose = intval($_POST['id_biens_compose']);
            $id_prestation = intval($_POST['id_prestation'] ?? 0);
            $quantite = intval($_POST['quantite'] ?? 1);
            if ($id_biens_compose && $id_prestation) {
                if ($composeObj->addCompose($id_biens_compose, $id_prestation, $quantite)) {
                    $bienMessage = "Composition ajoutée.";
                } else {
                    $bienMessage = "Erreur lors de l'ajout de la composition.";
                }
            }
        }

        if (isset($_POST['delete_compose']) && isset($_POST['id_biens_compose'])) {
            $composeObj = new Compose(null, $pdo);
            $id_biens_compose = intval($_POST['id_biens_compose']);
            $id_prestation = intval($_POST['id_prestation_delete'] ?? 0);
            if ($id_biens_compose && $id_prestation) {
                if ($composeObj->deleteCompose($id_biens_compose, $id_prestation)) {
                    $bienMessage = "Composition supprimée.";
                } else {
                    $bienMessage = "Erreur lors de la suppression de la composition.";
                }
            }
        }

        // Gestion des tarifs
        if (isset($_POST['add_tarif']) && isset($_POST['id_biens_tarif'])) {
            $tarifObj = new Tarif(null, null, null, null, null, $pdo);
            $id_biens_tarif = intval($_POST['id_biens_tarif']);
            $semaine_tarif = intval($_POST['semaine_tarif'] ?? 0);
            $annee_tarif = intval($_POST['annee_tarif'] ?? 0);
            $tarif_value = floatval($_POST['tarif_value'] ?? 0);
            $id_saison = intval($_POST['id_saison'] ?? 0);
            if ($id_biens_tarif && $semaine_tarif > 0 && $annee_tarif > 0 && $tarif_value > 0 && $id_saison) {
                if ($tarifObj->createTarif($id_biens_tarif, $semaine_tarif, $annee_tarif, $tarif_value, $id_saison)) {
                    $bienMessage = "Tarif ajouté.";
                } else {
                    $bienMessage = "Erreur lors de l'ajout du tarif.";
                }
            }
        }

        if (isset($_POST['delete_tarif']) && isset($_POST['id_tarif_delete'])) {
            $tarifObj = new Tarif(null, null, null, null, null, $pdo);
            $id_tarif_delete = intval($_POST['id_tarif_delete']);
            if ($id_tarif_delete) {
                if ($tarifObj->deleteTarif($id_tarif_delete)) {
                    $bienMessage = "Tarif supprimé.";
                } else {
                    $bienMessage = "Erreur lors de la suppression du tarif.";
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

    // Search parameter
    $searchBien = trim($_GET['search_bien'] ?? '');

    // Récupération des biens
    if ($searchBien) {
        $stmt = $pdo->prepare("SELECT * FROM Biens WHERE nom_biens LIKE ?");
        $stmt->execute(['%' . $searchBien . '%']);
        $biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $biens = $bienObj->getAllBiens();
    }

    // Récupération des prestations et saisons pour sous-formulaires
    $prestationObj = new Prestation(null, null, $pdo);
    $prestations = $prestationObj->readAllPrestation();
    $saisonObj = new Saison(null, null, $pdo);
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
    <title>Gestion des Biens</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/forms.css">
    <style>
        .bien-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            align-items: end;
        }
        .bien-form input[type="text"],
        .bien-form input[type="file"] {
            min-width: 200px;
        }
        .bien-list {
            margin-top: 40px;
        }
        .bien-list table {
            border-collapse: collapse;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .bien-list th,
        .bien-list td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        .bien-list th {
            background: linear-gradient(135deg, #a100b8, #d100e8);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        .bien-list tr:nth-child(even) {
            background: #f8f9fa;
        }
        .bien-list tr:hover {
            background: rgba(161, 0, 184, 0.05);
            transition: background 0.3s ease;
        }
        .bien-list .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .manage-section {
            background: #fff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-top: 40px;
        }
        .manage-section h3 {
            color: #333;
            margin: 0 0 20px 0;
            font-size: 1.5em;
        }
        .manage-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            align-items: end;
        }
        .manage-form input,
        .manage-form select {
            min-width: 150px;
        }
        .manage-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .manage-table th,
        .manage-table td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        .manage-table th {
            background: linear-gradient(135deg, #a100b8, #d100e8);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        .manage-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .manage-table tr:hover {
            background: rgba(161, 0, 184, 0.05);
            transition: background 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container admin-form">
        <div class="header">
            <h2>🏠 Gestion des Biens</h2>
            <p>Gérez les biens disponibles sur la plateforme</p>
        </div>
        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <?php if ($bienMessage): ?>
            <div class="message success"><?= htmlspecialchars($bienMessage) ?></div>
        <?php endif; ?>
        <section class="form-section">
            <h3>Ajouter un nouveau bien</h3>
            <form method="post" enctype="multipart/form-data" class="bien-form">
                <input type="text" id="nom_biens" name="nom_biens" placeholder="Nom du bien" required>
                <input type="file" name="photos[]" multiple accept="image/*">
                <button type="submit" name="add_biens" class="btn btn-primary">Ajouter</button>
            </form>
        </section>
        <section class="data-section">
            <h3>Biens existants</h3>
            <!-- Search Form -->
            <form method="get" class="filter-form">
                <input type="text" name="search_bien" placeholder="Rechercher par nom du bien..." value="<?= htmlspecialchars($searchBien) ?>">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <?php if ($searchBien): ?>
                    <a href="Bien.form.php" class="btn btn-secondary">Effacer</a>
                <?php endif; ?>
            </form>
            <div class="bien-list">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($biens as $bien): ?>
                            <tr>
                                <td><?= htmlspecialchars($bien['id_biens']) ?></td>
                                <td>
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $bien['id_biens']): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id_biens" value="<?= htmlspecialchars($bien['id_biens']) ?>">
                                            <input type="text" name="nom_biens_edit" value="<?= htmlspecialchars($bien['nom_biens']) ?>" required>
                                            <button type="submit" name="edit_biens" class="btn btn-primary">Enregistrer</button>
                                        </form>
                                    <?php else: ?>
                                        <?= htmlspecialchars($bien['nom_biens']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $bien['id_biens']): ?>
                                        <!-- Rien, on est en mode édition -->
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id_biens" value="<?= htmlspecialchars($bien['id_biens']) ?>">
                                            <button type="submit" name="edit_mode" value="<?= htmlspecialchars($bien['id_biens']) ?>" class="btn btn-secondary">Modifier</button>
                                        </form>
                                        <a href="Compose.form.php?id_bien=<?= htmlspecialchars($bien['id_biens']) ?>" class="btn btn-secondary" title="Gérer les équipements de ce bien">⚙️ Composition</a>
                                        <a href="?manage=tarif&id=<?= htmlspecialchars($bien['id_biens']) ?>" class="btn btn-secondary">Gérer tarifs</a>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce bien ?');">
                                            <input type="hidden" name="id_biens" value="<?= htmlspecialchars($bien['id_biens']) ?>">
                                            <button type="submit" name="delete_biens" class="btn btn-danger">Supprimer</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (isset($_GET['manage']) && isset($_GET['id'])): ?>
            <?php $manage = $_GET['manage']; $manage_id = intval($_GET['id']); ?>
            <div style="max-width:900px;margin:24px auto;background:#fff;padding:18px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.04)">
                <h3>Gestion: <?= htmlspecialchars($manage) ?> — Bien #<?= $manage_id ?></h3>
                <?php if ($manage === 'compose'): ?>
                    <?php
                        $composeObj = new Compose(null, $pdo);
                        $compositions = $composeObj->getByBien($manage_id);
                    ?>
                    <form method="post" style="margin:10px 0;">
                        <input type="hidden" name="id_biens_compose" value="<?= $manage_id ?>">
                        <label>Prestation</label>
                        <select name="id_prestation" required>
                            <option value="">-- choisir --</option>
                            <?php foreach ($prestations as $p): ?>
                                <option value="<?= $p['id_prestation'] ?>"><?= htmlspecialchars($p['lib_prestation']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label style="margin-left:8px">Quantité</label>
                        <input type="number" name="quantite" value="1" min="1" style="width:80px;margin-left:6px">
                        <button type="submit" name="add_compose">Ajouter</button>
                    </form>
                    <table style="width:100%;border-collapse:collapse;margin-top:10px;">
                        <tr><th>Prestation</th><th>Quantité</th><th>Action</th></tr>
                        <?php foreach ($compositions as $c):
                            $pre = $prestationObj->readIdPrestation($c['id_prestation']);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($pre['lib_prestation'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($c['quantite']) ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="id_biens_compose" value="<?= $manage_id ?>">
                                        <input type="hidden" name="id_prestation_delete" value="<?= $c['id_prestation'] ?>">
                                        <button type="submit" name="delete_compose">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php elseif ($manage === 'tarif'): ?>
                    <?php
                        $tarifObj = new Tarif(null, null, null, null, null, $pdo);
                        $tarifs = $tarifObj->getTarifsByBien($manage_id);
                    ?>
                    <form method="post" style="margin:10px 0;">
                        <input type="hidden" name="id_biens_tarif" value="<?= $manage_id ?>">
                        <label>Saison</label>
                        <select name="id_saison" required>
                            <option value="">-- choisir --</option>
                            <?php foreach ($saisons as $s): ?>
                                <option value="<?= $s['id_saison'] ?>"><?= htmlspecialchars($s['lib_saison']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label style="margin-left:8px">Semaine</label>
                        <input type="number" name="semaine_tarif" min="1" required style="width:70px;margin-left:6px">
                        <label style="margin-left:8px">Année</label>
                        <input type="number" name="annee_tarif" min="2000" required style="width:90px;margin-left:6px">
                        <label style="margin-left:8px">Prix</label>
                        <input type="number" step="0.01" name="tarif_value" required style="width:110px;margin-left:6px">
                        <button type="submit" name="add_tarif">Ajouter</button>
                    </form>
                    <table style="width:100%;border-collapse:collapse;margin-top:10px;">
                        <tr><th>Saison</th><th>Semaine</th><th>Année</th><th>Tarif</th><th>Action</th></tr>
                        <?php foreach ($tarifs as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['lib_saison'] ?? '') ?></td>
                                <td><?= htmlspecialchars($t['semaine_Tarif'] ?? $t['semaine_tarif'] ?? '') ?></td>
                                <td><?= htmlspecialchars($t['année_Tarif'] ?? $t['annee_tarif'] ?? '') ?></td>
                                <td><?= htmlspecialchars($t['tarif']) ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="id_tarif_delete" value="<?= $t['id_tarif'] ?>">
                                        <button type="submit" name="delete_tarif">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        </div>
        </div>
        <script src="../js/confirm_delete.js"></script>
        </body>
        </html>