<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Tarif/Tarif.php';
require_once __DIR__ . '/../classes/Compose/Compose.php';
require_once __DIR__ . '/../classes/Prestation/Prestation.php';

$message = '';

$id_bien = intval($_GET['id'] ?? 0);

if (!$id_bien) {
    header('Location: Annonce.form.php');
    exit;
}

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Récupération du bien avec détails
        $bien = $pdo->prepare('SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens WHERE b.id_biens = ?');
        $bien->execute([$id_bien]);
        $bien = $bien->fetch(PDO::FETCH_ASSOC);

        if (!$bien) {
            header('Location: Annonce.form.php');
            exit;
        }

        // Récupération des photos
        $photos = $pdo->prepare('SELECT * FROM Photos WHERE id_biens = ?');
        $photos->execute([$id_bien]);
        $photos = $photos->fetchAll(PDO::FETCH_ASSOC);

        // Utilisation de la classe Tarif
        $tarifClass = new Tarif(null, null, null, null, null, $pdo);
        $tarifs = $tarifClass->getTarifsByBien($id_bien);

    // Récupération des locataires pour le select
    $locataires = $pdo->query('SELECT id_locataire, nom_locataire, prenom_locataire FROM Locataire')->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des prestations pour le sous-formulaire composition
    $prestations = $pdo->query('SELECT id_prestation, lib_prestation FROM Prestation')->fetchAll(PDO::FETCH_ASSOC);

    // Récupération de la composition actuelle pour ce bien
    $composeClass = new Compose(null, $pdo);
    $compositionItems = $composeClass->getByBien($id_bien);

        // Récupération des communes et types de biens pour le formulaire de modification
        $communes = $pdo->query('SELECT id_commune, nom_commune FROM Commune')->fetchAll(PDO::FETCH_ASSOC);
        $typesBiens = $pdo->query('SELECT id_type_biens, designation_type_bien FROM Type_Bien')->fetchAll(PDO::FETCH_ASSOC);
        $saisons = $pdo->query('SELECT * FROM Saison')->fetchAll(PDO::FETCH_ASSOC);

        // Ajout d'une réservation
        if (isset($_POST['add_reservation'])) {
            $date_debut = trim($_POST['date_debut'] ?? '');
            $date_fin = trim($_POST['date_fin'] ?? '');
            $id_locataire = intval($_POST['id_locataire'] ?? 0);
            $id_tarif = intval($_POST['id_tarif'] ?? 0);

            if ($date_debut && $date_fin && $id_locataire && $id_tarif) {
                $stmt = $pdo->prepare('INSERT INTO Reservation (date_debut_reservation, date_fin_reservation, id_locataire, id_biens, id_Tarif) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif]);
                $message = "Réservation ajoutée avec succès.";
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Modification d'un bien
    if (isset($_POST['update_bien'])) {
            $nom = trim($_POST['nom_biens'] ?? '');
            $rue = trim($_POST['rue_biens'] ?? '');
            $superficie = intval($_POST['superficie_biens'] ?? 0);
            $desc = trim($_POST['description_biens'] ?? '');
            $animal = isset($_POST['animal_biens']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 0);
            $semaine_tarif = intval($_POST['semaine_tarif'] ?? 0);
            $annee_tarif = intval($_POST['annee_tarif'] ?? 0);
            $tarif = floatval($_POST['tarif_biens'] ?? 0);
            $id_saison = intval($_POST['id_saison'] ?? 0);
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type = intval($_POST['id_type_biens'] ?? 0);

            if ($nom && $rue && $superficie > 0 && $desc && $nb_couchage > 0 && $semaine_tarif > 0 && $annee_tarif > 0 && $tarif > 0 && $id_saison > 0 && $id_commune && $id_type) {
                $stmt = $pdo->prepare('UPDATE Biens SET nom_biens = ?, rue_biens = ?, superficie_biens = ?, description_biens = ?, animal_biens = ?, nb_couchage = ?, id_commune = ?, id_type_biens = ? WHERE id_biens = ?');
                $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $id_commune, $id_type, $id_bien]);

                // Créer ou mettre à jour le tarif
                $tarifClass = new Tarif(null, $semaine_tarif, $annee_tarif, $tarif, $id_saison, $pdo);
                $tarifClass->createTarif($id_bien, $semaine_tarif, $annee_tarif, $tarif, $id_saison);

                // Upload des nouvelles images si présentes
                if (isset($_FILES['photos'])) {
                    $uploadDir = __DIR__ . '/../images/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = basename($_FILES['photos']['name'][$key]);
                            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            if (in_array($fileExtension, $allowedExtensions)) {
                                $newFileName = uniqid('img_', true) . '.' . $fileExtension;
                                $destPath = $uploadDir . $newFileName;
                                $lienPhoto = 'Projet_HAP(House_After_Party)/images/uploads/' . $newFileName;
                                if (move_uploaded_file($tmpName, $destPath)) {
                                    $stmtPhoto = $pdo->prepare('INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (?, ?, ?)');
                                    $stmtPhoto->execute([$fileName, $lienPhoto, $id_bien]);
                                }
                            }
                        }
                    }
                }
                // Update composition: delete existing and insert new ones if provided (labels -> presta IDs)
                if (isset($_POST['composition']) && is_array($_POST['composition'])) {
                    // remove old composition rows for this bien
                    $del = $pdo->prepare('DELETE FROM Compose WHERE id_biens = ?');
                    $del->execute([$id_bien]);
                    // prepare lookup/insert statements
                    $findPrestation = $pdo->prepare('SELECT id_prestation FROM Prestation WHERE LOWER(lib_prestation) = LOWER(?) LIMIT 1');
                    $insertPrestation = $pdo->prepare('INSERT INTO Prestation (lib_prestation) VALUES (?)');
                    $composeClass = new Compose(null, $pdo);
                    foreach ($_POST['composition'] as $comp) {
                        $label = trim($comp['label'] ?? '');
                        $quantite = intval($comp['quantite'] ?? 0);
                        if ($label === '' || $quantite <= 0) { continue; }

                        $findPrestation->execute([$label]);
                        $row = $findPrestation->fetch(PDO::FETCH_ASSOC);
                        if ($row && isset($row['id_prestation'])) {
                            $id_prestation = $row['id_prestation'];
                        } else {
                            $insertPrestation->execute([$label]);
                            $id_prestation = $pdo->lastInsertId();
                        }
                        if ($id_prestation > 0) {
                            $composeClass->addCompose($id_bien, $id_prestation, $quantite);
                        }
                    }
                }
                $message = "Bien mis à jour avec succès.";
                // Recharger les données
                $bien = $pdo->prepare('SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens WHERE b.id_biens = ?');
                $bien->execute([$id_bien]);
                $bien = $bien->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Veuillez remplir tous les champs correctement.";
            }
        }

        // Récupérer les avis (reviews) pour ce bien
        $reviewsStmt = $pdo->prepare('SELECT r.*, l.nom_locataire, l.prenom_locataire FROM Reviews r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire WHERE r.id_biens = ? ORDER BY r.created_at DESC');
        $reviewsStmt->execute([$id_bien]);
        $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculer la note moyenne
        $avgStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as count_reviews FROM Reviews WHERE id_biens = ?');
        $avgStmt->execute([$id_bien]);
        $avgRow = $avgStmt->fetch(PDO::FETCH_ASSOC);
        $averageRating = $avgRow ? round(floatval($avgRow['avg_rating']), 2) : null;

        // Suppression d'un bien
        if (isset($_POST['delete_bien'])) {
            // Supprimer les photos associées
            $stmt = $pdo->prepare('DELETE FROM Photos WHERE id_biens = ?');
            $stmt->execute([$id_bien]);
            // Supprimer le bien
            $stmt = $pdo->prepare('DELETE FROM Biens WHERE id_biens = ?');
            $stmt->execute([$id_bien]);
            header('Location: Annonce.form.php?deleted=1');
            exit;
        }
        // Handle review submission
        if (isset($_POST['submit_review'])) {
            $reviewBienId = intval($_POST['review_bien_id'] ?? 0);
            $rating = intval($_POST['rating'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
            if ($reviewBienId > 0 && ($rating > 0 || $content !== '')) {
                $ins = $pdo->prepare('INSERT INTO Reviews (id_biens, id_locataire, rating, content, created_at) VALUES (?, ?, ?, ?, NOW())');
                $ins->execute([$reviewBienId, $userId, $rating > 0 ? $rating : null, $content]);
                header('Location: annonce_detail.php?id=' . $id_bien);
                exit;
            } else {
                $message = 'Veuillez saisir une note ou un commentaire.';
            }
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($bien['nom_biens']) ?> - Détails de l'annonce</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Css/annonce.css">
</head>
<body>
    <div class="container">
        <?php
        // If we came from the map, show a back link that restores the map view
        if (isset($_GET['from']) && $_GET['from'] === 'map') {
            $mapLat = isset($_GET['map_lat']) ? htmlspecialchars($_GET['map_lat']) : '';
            $mapLng = isset($_GET['map_lng']) ? htmlspecialchars($_GET['map_lng']) : '';
            $mapZoom = isset($_GET['map_zoom']) ? htmlspecialchars($_GET['map_zoom']) : '';
            // include open_id so the map can open the same marker after returning
            $openId = htmlspecialchars($id_bien);
            // Use relative path back to the map (map.php is located one level up from forms/)
            $mapUrl = '../map.php?open_id=' . $openId;
            if ($mapLat !== '' && $mapLng !== '' && $mapZoom !== '') {
                $mapUrl .= '&map_lat=' . $mapLat . '&map_lng=' . $mapLng . '&map_zoom=' . $mapZoom;
            }
            echo '<a href="' . $mapUrl . '" class="back-link">&larr; Retour à la carte</a>';
        } else {
            echo '<a href="Annonce.form.php" class="back-link">&larr; Retour aux annonces</a>';
        }
        ?>

        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="annonce-header">
            <h1 class="annonce-title"><?= htmlspecialchars($bien['nom_biens']) ?></h1>
            <p class="annonce-location"><?= htmlspecialchars($bien['nom_commune']) ?>, <?= htmlspecialchars($bien['rue_biens']) ?></p>
        </div>

        <?php if ($photos): ?>
            <div class="image-gallery">
                <?php foreach ($photos as $photo): ?>
                    <?php $photoLabel = trim(str_ireplace('photo', '', $photo['nom_photos'])); ?>
                    <a href="/<?= htmlspecialchars($photo['lien_photo']) ?>" data-lightbox="gallery" data-title="<?= htmlspecialchars($photoLabel) ?>">
                        <img src="/<?= htmlspecialchars($photo['lien_photo']) ?>" alt="<?= htmlspecialchars($bien['nom_biens']) ?>" class="gallery-image">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($averageRating)): ?>
            <div class="rating-summary" style="margin-top:12px;">
                <strong>Note moyenne :</strong> <?= number_format($averageRating, 2) ?> / 5 (<?= intval($avgRow['count_reviews']) ?> avis)
            </div>
        <?php endif; ?>

        <?php if (!empty($reviews)): ?>
            <div class="reviews" style="margin-top:20px;">
                <h3>Avis des utilisateurs</h3>
                <?php foreach ($reviews as $rev): ?>
                    <div class="review-item" style="border-bottom:1px solid #eee;padding:8px 0;">
                        <div style="font-weight:600;"><?= htmlspecialchars($rev['nom_locataire'] ? $rev['nom_locataire'] . ' ' . ($rev['prenom_locataire'] ?? '') : 'Anonyme') ?></div>
                        <div style="color:#f39c12;"><?= str_repeat('★', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])) ?></div>
                        <div style="margin-top:6px;"><?= nl2br(htmlspecialchars($rev['content'])) ?></div>
                        <div style="font-size:0.85em;color:#888;margin-top:6px;">Posté le <?= htmlspecialchars($rev['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'ajout d'un avis -->
        <div class="form-section" style="margin-top:20px;">
            <h3>Laisser un avis</h3>
            <form method="post">
                <input type="hidden" name="review_bien_id" value="<?= htmlspecialchars($id_bien) ?>">
                <div class="form-group">
                    <label for="review_rating">Note :</label>
                    <div id="review_rating">
                        <?php for ($r = 5; $r >= 1; $r--): ?>
                            <label style="margin-right:6px;"><input type="radio" name="rating" value="<?= $r ?>"> <?= $r ?> ★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="review_content">Votre avis:</label>
                    <textarea id="review_content" name="content" rows="4" style="width:100%;"></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" name="submit_review">Envoyer l'avis</button>
                </div>
            </form>
        </div>

    <div class="annonce-details">
            <div class="detail-section">
                <h3>Caractéristiques</h3>
                <div class="detail-item">
                    <span class="detail-label">Superficie:</span>
                    <span class="detail-value"><?= htmlspecialchars($bien['superficie_biens']) ?> m²</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Nombre de couchages:</span>
                    <span class="detail-value"><?= htmlspecialchars($bien['nb_couchage']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Type de bien:</span>
                    <span class="detail-value"><?= htmlspecialchars($bien['designation_type_bien']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Animaux acceptés:</span>
                    <span class="detail-value"><?= $bien['animal_biens'] ? 'Oui' : 'Non' ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>Localisation</h3>
                <div class="detail-item">
                    <span class="detail-label">Commune:</span>
                    <span class="detail-value"><?= htmlspecialchars($bien['nom_commune']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Rue:</span>
                    <span class="detail-value"><?= htmlspecialchars($bien['rue_biens']) ?></span>
                </div>
            </div>

            <div class="detail-section description">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($bien['description_biens'])) ?></p>
                <?php if (!empty($compositionItems)): ?>
                    <div class="detail-section">
                        <h4>Composition</h4>
                        <ul>
                            <?php foreach ($compositionItems as $ci): ?>
                                <?php
                                    // find prestation label
                                    $label = '';
                                    foreach ($prestations as $p) { if ($p['id_prestation'] == $ci['id_prestation']) { $label = $p['lib_prestation']; break; } }
                                ?>
                                <li><?= intval($ci['quantite']) ?> × <?= htmlspecialchars($label) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <div class="tarif-section">
                    <h4>Tarifs disponibles</h4>
                    <?php if ($tarifs): ?>
                        <select id="tarif-select" name="id_tarif" required>
                            <option value="">-- Sélectionnez un tarif --</option>
                            <?php foreach ($tarifs as $tarif): ?>
                                <option value="<?= htmlspecialchars($tarif['id_Tarif']) ?>">
                                    Semaine <?= htmlspecialchars($tarif['semaine_Tarif']) ?> - <?= htmlspecialchars($tarif['année_Tarif']) ?> - <?= htmlspecialchars($tarif['lib_saison']) ?> : €<?= number_format($tarif['tarif'], 2) ?>/nuit
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="reservation-form" style="display: none; margin-top: 15px;">
                            <form method="post">
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <input type="date" name="date_debut" placeholder="Date début" required>
                                    <input type="date" name="date_fin" placeholder="Date fin" required>
                                    <select name="id_locataire" required>
                                        <option value="">-- Locataire --</option>
                                        <?php foreach ($locataires as $l): ?>
                                            <option value="<?= $l['id_locataire'] ?>"><?= htmlspecialchars($l['nom_locataire'] . ' ' . $l['prenom_locataire']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="add_reservation" class="reserve-btn">Confirmer la réservation</button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>Aucun tarif disponible pour ce bien.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="actions">
            <button type="button" id="edit-btn" class="reserve-btn">Modifier cette annonce</button>
            <form method="post" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                <button type="submit" name="delete_bien">Supprimer cette annonce</button>
            </form>
        </div>

        <!-- Formulaire de modification caché -->
        <div id="edit-form" style="display: none; margin-top: 40px;">
            <div class="form-section">
                <h3>Modifier l'annonce</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nom_biens">Nom du bien:</label>
                        <input type="text" id="nom_biens" name="nom_biens" value="<?= htmlspecialchars($bien['nom_biens']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="rue_biens">Rue:</label>
                        <input type="text" id="rue_biens" name="rue_biens" value="<?= htmlspecialchars($bien['rue_biens']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="superficie_biens">Superficie (m²):</label>
                        <input type="number" id="superficie_biens" name="superficie_biens" value="<?= htmlspecialchars($bien['superficie_biens']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description_biens">Description:</label>
                        <textarea id="description_biens" name="description_biens" required><?= htmlspecialchars($bien['description_biens']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="nb_couchage">Nombre de couchages:</label>
                        <input type="number" id="nb_couchage" name="nb_couchage" value="<?= htmlspecialchars($bien['nb_couchage']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="semaine_tarif">Semaine:</label>
                        <input type="number" id="semaine_tarif" name="semaine_tarif" min="1" max="52" value="<?= date('W') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="annee_tarif">Année:</label>
                        <input type="number" id="annee_tarif" name="annee_tarif" min="2020" max="2030" value="<?= date('Y') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tarif_biens">Tarif par nuit (€):</label>
                        <input type="number" step="0.01" id="tarif_biens" name="tarif_biens" required>
                    </div>
                    <div class="form-group">
                        <label for="id_saison">Saison:</label>
                        <select id="id_saison" name="id_saison" required>
                            <option value="">-- Sélectionnez une saison --</option>
                            <?php
                            foreach ($saisons as $saison): ?>
                                <option value="<?= $saison['id_saison'] ?>"><?= htmlspecialchars($saison['lib_saison']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_commune">Commune:</label>
                        <select id="id_commune" name="id_commune" required>
                            <option value="">-- Sélectionnez une commune --</option>
                            <?php foreach ($communes as $commune): ?>
                                <option value="<?= $commune['id_commune'] ?>" <?= $commune['id_commune'] == $bien['id_commune'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($commune['nom_commune']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit-composition-container">Composition du bien:</label>
                        <div id="edit-composition-container">
                            <?php if (!empty($compositionItems)): foreach ($compositionItems as $idx => $ci): ?>
                                    <?php
                                        // find prestation label
                                        $label = '';
                                        foreach ($prestations as $p) { if ($p['id_prestation'] == $ci['id_prestation']) { $label = $p['lib_prestation']; break; } }
                                    ?>
                                    <div class="composition-item" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                                        <input type="text" name="composition[<?= $idx ?>][label]" value="<?= htmlspecialchars($label) ?>" required style="flex:1;min-width:140px;padding:6px;border-radius:6px;border:1px solid #ccc;">
                                        <input type="number" name="composition[<?= $idx ?>][quantite]" min="1" value="<?= intval($ci['quantite']) ?>" required style="width:80px;">
                                        <button type="button" class="remove-composition">Supprimer</button>
                                    </div>
                                <?php endforeach; endif; ?>
                        </div>
                        <button type="button" id="add-edit-composition">Ajouter un élément de composition</button>
                    </div>
                    <div class="form-group">
                        <label for="id_type_biens">Type de bien:</label>
                        <select id="id_type_biens" name="id_type_biens" required>
                            <option value="">-- Sélectionnez un type --</option>
                            <?php foreach ($typesBiens as $type): ?>
                                <option value="<?= $type['id_type_biens'] ?>" <?= $type['id_type_biens'] == $bien['id_type_biens'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['designation_type_bien']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="animal_biens">Animaux acceptés:</label>
                        <input type="checkbox" id="animal_biens" name="animal_biens" <?= $bien['animal_biens'] ? 'checked' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label for="photos">Ajouter des photos (optionnel):</label>
                        <input type="file" id="photos" name="photos[]" multiple accept="image/*">
                    </div>
                    <input type="submit" name="update_bien" value="Mettre à jour">
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tarif-select').change(function() {
                if ($(this).val()) {
                    $('#reservation-form').show();
                } else {
                    $('#reservation-form').hide();
                }
            });

            $('#edit-btn').click(function() {
                $('#edit-form').toggle();
            });
            // Edit composition dynamic behavior (for annonce_detail)
            let editCompIndex = <?= !empty($compositionItems) ? count($compositionItems) : 0 ?>;
            $('#add-edit-composition').on('click', function() {
                const options = `
                    <?php foreach ($prestations as $p): ?>
                        <option value="<?= $p['id_prestation'] ?>"><?= htmlspecialchars($p['lib_prestation']) ?></option>
                    <?php endforeach; ?>
                `;
                const newComp = `
                    <div class="composition-item" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                        <select name="composition[${editCompIndex}][prestation_id]" required>
                            <option value="">-- Sélectionnez --</option>
                            ${options}
                        </select>
                        <input type="number" name="composition[${editCompIndex}][quantite]" min="1" value="1" required style="width:80px;">
                        <button type="button" class="remove-composition">Supprimer</button>
                    </div>
                `;
                $('#edit-composition-container').append(newComp);
                editCompIndex++;
            });

            $(document).on('click', '.remove-composition', function() {
                $(this).closest('.composition-item').remove();
            });
        });
    </script>
    <script src="../js/confirm_delete.js"></script>
</body>
</html>
