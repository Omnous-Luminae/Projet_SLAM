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

    // Récupération des biens
    $biens = $bienObj->getAllBiens();

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
        <form method="post" enctype="multipart/form-data">
            <input type="text" id="nom_biens" name="nom_biens" placeholder="Nom du bien" required>
            <input type="file" name="photos[]" multiple accept="image/*">
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
                                <a href="?manage=compose&id=<?= htmlspecialchars($bien['id_biens']) ?>" style="margin-left:6px;background:#e9ecff;color:#0b2b8a;padding:6px 10px;border-radius:6px;text-decoration:none;">Gérer composition</a>
                                <a href="?manage=tarif&id=<?= htmlspecialchars($bien['id_biens']) ?>" style="margin-left:6px;background:#fff3e6;color:#7a4b00;padding:6px 10px;border-radius:6px;text-decoration:none;">Gérer tarifs</a>
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