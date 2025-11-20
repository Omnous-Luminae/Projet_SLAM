<?php
session_start();
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
        $bien = $pdo->prepare('SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens WHERE b.id_biens = ? AND (b.is_hidden IS NULL OR b.is_hidden = FALSE)');
        $bien->execute([$id_bien]);
        $bien = $bien->fetch(PDO::FETCH_ASSOC);

        // Vérifier si l'utilisateur peut modifier/supprimer
        $canEdit = false;
        if (isset($_SESSION['user_id'])) {
            $userId = intval($_SESSION['user_id']);
            $userName = $_SESSION['user_name'] ?? '';
            $userRole = $_SESSION['user_role'] ?? 'user'; // Assumer 'user' par défaut
            if ($userRole === 'admin' || (isset($bien['created_by_id']) && $bien['created_by_id'] == $userId) || (isset($bien['created_by_name']) && $bien['created_by_name'] == $userName)) {
                $canEdit = true;
            }
        }

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

    // Récupération des prestations pour le sous-formulaire composition
    $prestations = $pdo->query('SELECT id_prestation, lib_prestation FROM Prestation')->fetchAll(PDO::FETCH_ASSOC);

    // Récupération de la composition actuelle pour ce bien
    $composeClass = new Compose(null, $pdo);
    $compositionItems = $composeClass->getByBien($id_bien);

        // Récupération des communes et types de biens pour le formulaire de modification
        $communes = $pdo->query('SELECT id_commune, nom_commune FROM Commune')->fetchAll(PDO::FETCH_ASSOC);
        $typesBiens = $pdo->query('SELECT id_type_biens, designation_type_bien FROM Type_Bien')->fetchAll(PDO::FETCH_ASSOC);
    $saisons = $pdo->query('SELECT * FROM Saison')->fetchAll(PDO::FETCH_ASSOC);

    $seasonColors = [
        'Été' => '#FFD700', // Gold for summer
        'Hiver' => '#87CEEB', // Sky blue for winter
        'Printemps' => '#98FB98', // Pale green for spring
        'Automne' => '#FFA500', // Orange for autumn
        'Saison basse' => '#D3D3D3', // Light gray for low season
        'Saison haute' => '#FF6347', // Tomato red for high season
        'default' => '#add8e6' // Light blue default
    ];

        // Ajout d'une réservation
        if (isset($_POST['add_reservation'])) {
            if (!isset($_SESSION['user_id'])) {
                header('Location: ../auth/connexion.php');
                exit;
            }
            $date_debut = trim($_POST['date_debut'] ?? '');
            $date_fin = trim($_POST['date_fin'] ?? '');
            $id_locataire = intval($_SESSION['user_id']);
            $id_tarif = intval($_POST['id_tarif'] ?? 0);

            if ($date_debut && $date_fin && $id_locataire && $id_tarif) {
                $stmt = $pdo->prepare('INSERT INTO Reservation (date_debut_reservation, date_fin_reservation, id_locataire, id_biens, id_Tarif) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif]);
                header('Location: annonce_detail.php?id=' . $id_bien . '&reservation=success');
                exit;
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
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type = intval($_POST['id_type_biens'] ?? 0);

            if ($nom && $rue && $superficie > 0 && $desc && $nb_couchage > 0 && $id_commune && $id_type) {
                $stmt = $pdo->prepare('UPDATE Biens SET nom_biens = ?, rue_biens = ?, superficie_biens = ?, description_biens = ?, animal_biens = ?, nb_couchage = ?, id_commune = ?, id_type_biens = ? WHERE id_biens = ?');
                $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $id_commune, $id_type, $id_bien]);

                // Supprimer les réservations associées aux tarifs de ce bien
                $delRes = $pdo->prepare('DELETE FROM Reservation WHERE id_biens = ?');
                $delRes->execute([$id_bien]);

                // Supprimer les tarifs existants pour ce bien
                $delTarif = $pdo->prepare('DELETE FROM Tarif WHERE id_biens = ?');
                $delTarif->execute([$id_bien]);

                // Insérer les nouveaux tarifs depuis le tableau tarifs
                if (isset($_POST['tarifs']) && is_array($_POST['tarifs'])) {
                    foreach ($_POST['tarifs'] as $tarifData) {
                        $semaine_tarif = intval($tarifData['semaine_tarif'] ?? 0);
                        $annee_tarif = intval($tarifData['annee_tarif'] ?? 0);
                        $tarif_value = floatval($tarifData['tarif'] ?? 0);
                        $id_saison = intval($tarifData['id_saison'] ?? 0);
                        if ($semaine_tarif > 0 && $annee_tarif > 0 && $tarif_value > 0 && $id_saison > 0) {
                            $tarifClass = new Tarif(null, $semaine_tarif, $annee_tarif, $tarif_value, $id_saison, $pdo);
                            $tarifClass->createTarif($id_bien, $semaine_tarif, $annee_tarif, $tarif_value, $id_saison);
                        }
                    }
                }

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
                // Update composition: delete existing and insert new ones if provided (prestation_id directly)
                if (isset($_POST['composition']) && is_array($_POST['composition'])) {
                    // remove old composition rows for this bien
                    $del = $pdo->prepare('DELETE FROM Compose WHERE id_biens = ?');
                    $del->execute([$id_bien]);
                    $composeClass = new Compose(null, $pdo);
                    foreach ($_POST['composition'] as $comp) {
                        $id_prestation = intval($comp['prestation_id'] ?? 0);
                        $quantite = intval($comp['quantite'] ?? 0);
                        if ($id_prestation <= 0 || $quantite <= 0) { continue; }
                        $composeClass->addCompose($id_bien, $id_prestation, $quantite);
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
            // Supprimer les avis associés
            $stmt = $pdo->prepare('DELETE FROM Reviews WHERE id_biens = ?');
            $stmt->execute([$id_bien]);
            // Supprimer les réservations associées
            $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_biens = ?');
            $stmt->execute([$id_bien]);
            // Supprimer la composition associée
            $stmt = $pdo->prepare('DELETE FROM Compose WHERE id_biens = ?');
            $stmt->execute([$id_bien]);
            // Supprimer les tarifs associés
            $stmt = $pdo->prepare('DELETE FROM Tarif WHERE id_biens = ?');
            $stmt->execute([$id_bien]);
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
            $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
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
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../Css/annonce.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
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

        <?php
        if (isset($_GET['reservation']) && $_GET['reservation'] === 'success') {
            $message = "Réservation effectuée avec succès.";
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
                        <div style="font-weight:600;">
                            <?php
                            $displayName = '';
                            if (isset($_SESSION['user_id']) && $rev['id_locataire'] == $_SESSION['user_id']) {
                                $displayName = $_SESSION['user_name'];
                            } else {
                                $displayName = $rev['nom_locataire'] ? $rev['nom_locataire'] . ' ' . ($rev['prenom_locataire'] ?? '') : 'Anonyme';
                            }
                            echo htmlspecialchars($displayName);
                            ?>
                        </div>
                        <div style="color:#f39c12;"><?= str_repeat('★', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])) ?></div>
                        <div style="margin-top:6px;"><?= nl2br(htmlspecialchars($rev['content'])) ?></div>
                        <div style="font-size:0.85em;color:#888;margin-top:6px;">Posté le <?= htmlspecialchars(date('d-m-Y à H:i', strtotime($rev['created_at']))) ?></div>
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
                                    <input type="date" id="date_debut" name="date_debut" placeholder="Date début" required>
                                    <input type="date" id="date_fin" name="date_fin" placeholder="Date fin" required>
                                    <input type="hidden" name="id_tarif" id="hidden_id_tarif">
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

        <?php if ($canEdit): ?>
        <div class="actions">
            <button type="button" id="edit-btn" class="reserve-btn">Modifier cette annonce</button>
            <form method="post" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                <button type="submit" name="delete_bien">Supprimer cette annonce</button>
            </form>
        </div>
        <?php endif; ?>

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
                    <details class="subform">
                        <summary>Tarifs (cliquez pour ouvrir)</summary>
                        <div id="tarifs-container">
                            <?php if (!empty($tarifs)): ?>
                                <?php foreach ($tarifs as $index => $tarif): ?>
                                    <div class="tarif-item" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                                        <input type="number" name="tarifs[<?= $index ?>][semaine_tarif]" placeholder="Semaine" min="1" max="52" value="<?= htmlspecialchars($tarif['semaine_Tarif']) ?>" required style="width:80px;">
                                        <input type="number" name="tarifs[<?= $index ?>][annee_tarif]" placeholder="Année" min="2020" max="2030" value="<?= htmlspecialchars($tarif['année_Tarif']) ?>" required style="width:100px;">
                                        <input type="number" step="0.01" name="tarifs[<?= $index ?>][tarif]" placeholder="Tarif (€)" value="<?= htmlspecialchars($tarif['tarif']) ?>" required style="width:100px;">
                                        <select name="tarifs[<?= $index ?>][id_saison]" required style="min-width:120px;">
                                            <option value="">-- Sélectionnez une saison --</option>
                                            <?php foreach ($saisons as $saison): ?>
                                                <option value="<?= $saison['id_saison'] ?>" <?= $saison['id_saison'] == $tarif['id_saison'] ? 'selected' : '' ?>><?= htmlspecialchars($saison['lib_saison']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="remove-tarif">Supprimer</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-tarif">Ajouter un tarif</button>
                    </details>
                    <div class="form-group">
                        <label for="commune_input">Commune:</label>
                        <input type="text" id="commune_input" name="commune" placeholder="Tapez le nom d'une commune..." value="<?= htmlspecialchars($bien['nom_commune']) ?>" required>
                        <input type="hidden" id="commune_id" name="id_commune" value="<?= htmlspecialchars($bien['id_commune']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="edit-composition-container">Composition du bien:</label>
                        <div id="edit-composition-container">
                            <?php if (!empty($compositionItems)): foreach ($compositionItems as $idx => $ci): ?>
                                    <div class="composition-item" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                                        <select name="composition[<?= $idx ?>][prestation_id]" required>
                                            <option value="">-- Sélectionnez --</option>
                                            <?php foreach ($prestations as $p): ?>
                                                <option value="<?= $p['id_prestation'] ?>" <?= $p['id_prestation'] == $ci['id_prestation'] ? 'selected' : '' ?>><?= htmlspecialchars($p['lib_prestation']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="composition[<?= $idx ?>][quantite]" min="1" value="<?= intval($ci['quantite']) ?>" required style="width:80px;">
                                        <button type="button" class="remove-composition">Supprimer</button>
                                    </div>
                                <?php endforeach; endif; ?>
                        </div>
                        <button type="button" id="add-edit-composition">Ajouter un élément de composition</button>
                        <p style="font-size:0.9em;color:#666;margin-top:8px;">Choisissez un type de prestation et saisissez la quantité.</p>
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
                        <label for="photos-0">Photos:</label>
                        <div id="photos-container">
                            <div class="photo-input-row" data-index="0" style="display:flex;gap:8px;align-items:center;">
                                <input id="photos-0" type="file" name="photos[]" class="photo-input" accept="image/*">
                                <button type="button" class="remove-photo-input" style="display:none;">Supprimer</button>
                            </div>
                        </div>
                        <div id="photo-previews" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;"></div>
                        <div style="margin-top:8px;">
                            <button type="button" id="add-photo-input">Ajouter une photo</button>
                            <small style="display:block;color:#666;margin-top:6px;">Vous pouvez sélectionner plusieurs fichiers en une fois ou ajouter plusieurs champs. Les champs séparés conservent chaque sélection.</small>
                        </div>
                    </div>
                    <input type="submit" name="update_bien" value="Mettre à jour">
                </form>
            </div>
        </div>

        <!-- Calendrier FullCalendar -->
        <div class="calendar-section" style="margin-top: 40px;">
            <h3>Calendrier des disponibilités</h3>
            <div id="calendar" style="max-width: 900px; margin: 0 auto;"></div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tarif-select').change(function() {
                var selectedTarif = $(this).val();
                if (selectedTarif) {
                    $('#reservation-form').show();
                    $('#hidden_id_tarif').val(selectedTarif);
                } else {
                    $('#reservation-form').hide();
                    $('#hidden_id_tarif').val('');
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

            // Dynamic tarifs
            let tarifIndex = <?= !empty($tarifs) ? count($tarifs) : 0 ?>;
            $('#add-tarif').on('click', function() {
                const saisonOptions = `
                    <?php foreach ($saisons as $saison): ?>
                        <option value="<?= $saison['id_saison'] ?>"><?= htmlspecialchars($saison['lib_saison']) ?></option>
                    <?php endforeach; ?>
                `;
                const newTarif = `
                    <div class="tarif-item" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                        <input type="number" name="tarifs[${tarifIndex}][semaine_tarif]" placeholder="Semaine" min="1" max="52" required style="width:80px;">
                        <input type="number" name="tarifs[${tarifIndex}][annee_tarif]" placeholder="Année" min="2020" max="2030" required style="width:100px;">
                        <input type="number" step="0.01" name="tarifs[${tarifIndex}][tarif]" placeholder="Tarif (€)" required style="width:100px;">
                        <select name="tarifs[${tarifIndex}][id_saison]" required style="min-width:120px;">
                            <option value="">-- Sélectionnez une saison --</option>
                            ${saisonOptions}
                        </select>
                        <button type="button" class="remove-tarif">Supprimer</button>
                    </div>
                `;
                $('#tarifs-container').append(newTarif);
                tarifIndex++;
            });

            $(document).on('click', '.remove-tarif', function() {
                $(this).closest('.tarif-item').remove();
            });

            // Dynamic photo inputs
            let photoIndex = 1;
            $('#add-photo-input').on('click', function() {
                const newRow = `
                    <div class="photo-input-row" data-index="${photoIndex}" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                        <input id="photos-${photoIndex}" type="file" name="photos[]" class="photo-input" accept="image/*">
                        <button type="button" class="remove-photo-input">Supprimer</button>
                    </div>
                `;
                $('#photos-container').append(newRow);
                photoIndex++;
            });

            $(document).on('click', '.remove-photo-input', function() {
                $(this).closest('.photo-input-row').remove();
            });

            // Photo preview
            $(document).on('change', '.photo-input', function() {
                const file = this.files[0];
                const index = $(this).closest('.photo-input-row').data('index');
                const previewContainer = $('#photo-previews');
                const existingPreview = previewContainer.find(`.preview-${index}`);

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (existingPreview.length) {
                            existingPreview.attr('src', e.target.result);
                        } else {
                            const img = `<img src="${e.target.result}" alt="Preview" class="preview-${index}" style="max-width:100px;max-height:100px;border:1px solid #ccc;">`;
                            previewContainer.append(img);
                        }
                    };
                    reader.readAsDataURL(file);
                } else {
                    existingPreview.remove();
                }
            });
        });
    </script>
    <script src="../js/autocomplete.js"></script>
    <script>
        $(document).ready(function() {
            initEditCommuneAutocomplete();
        });
    </script>
    <script src="../js/confirm_delete.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var reservedDates = new Set();
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                events: function(fetchInfo, successCallback, failureCallback) {
                    fetch('../api/get_reservations_bien.php?id_bien=<?= $id_bien ?>')
                        .then(response => response.json())
                        .then(data => {
                            var events = data.map(reservation => ({
                                title: reservation.title,
                                start: reservation.start,
                                end: reservation.end,
                                backgroundColor: reservation.backgroundColor,
                                borderColor: reservation.borderColor,
                                display: reservation.display,
                                className: reservation.className
                            }));
                            // Collect reserved dates for strikethrough
                            data.forEach(reservation => {
                                let start = new Date(reservation.start);
                                let end = new Date(reservation.end);
                                for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                                    reservedDates.add(d.toISOString().split('T')[0]);
                                }
                            });
                            successCallback(events);
                        })
                        .catch(error => {
                            console.error('Erreur lors de la récupération des réservations:', error);
                            failureCallback(error);
                        });
                },
                dayCellDidMount: function(info) {
                    if (reservedDates.has(info.dateStr)) {
                        var dayNumberEl = info.el.querySelector('.fc-daygrid-day-number');
                        if (dayNumberEl) {
                            dayNumberEl.style.textDecoration = 'line-through';
                        }
                    }
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>
