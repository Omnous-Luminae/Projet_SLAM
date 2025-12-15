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
        $bien = $pdo->prepare('SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens WHERE b.id_biens = ? AND (b.is_hidden IS NULL OR b.is_hidden = FALSE) AND b.validated = 1');
        $bien->execute([$id_bien]);
        $bien = $bien->fetch(PDO::FETCH_ASSOC);

        // Vérifier si l'utilisateur peut modifier/supprimer
        $canEdit = false;
        if (isset($_SESSION['user_id'])) {
            $userId = intval($_SESSION['user_id']);
            $userName = $_SESSION['user_name'] ?? '';
            $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'animateur';
            if ($isAdmin || (isset($bien['created_by_id']) && $bien['created_by_id'] == $userId) || (isset($bien['created_by_name']) && $bien['created_by_name'] == $userName)) {
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

        // Fonction helper pour obtenir le tarif automatiquement basé sur les dates
        function getAutoTarif($pdo, $id_bien, $date_debut) {
            // Convertir la date au numéro de semaine ISO
            $dt = new DateTime($date_debut);
            $week = intval($dt->format('W'));
            $year = intval($dt->format('Y'));
            
            // Chercher un tarif spécial pour cette semaine
            $stmt = $pdo->prepare('SELECT id_Tarif FROM Tarif WHERE id_biens = ? AND semaine_Tarif = ? AND année_Tarif = ? LIMIT 1');
            $stmt->execute([$id_bien, $week, $year]);
            $specialTarif = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($specialTarif) {
                return $specialTarif['id_Tarif'];
            }
            
            // Si pas de tarif spécial, retourner le premier tarif disponible
            // (fallback - normalement il devrait y avoir un tarif par défaut)
            $stmt = $pdo->prepare('SELECT id_Tarif FROM Tarif WHERE id_biens = ? LIMIT 1');
            $stmt->execute([$id_bien]);
            $fallbackTarif = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $fallbackTarif ? $fallbackTarif['id_Tarif'] : 1; // Retourner 1 par défaut
        }

        // Ajout d'une réservation
        if (isset($_POST['add_reservation'])) {
            if (!isset($_SESSION['user_id'])) {
                header('Location: ../auth/connexion.php');
                exit;
            }
            $date_debut = trim($_POST['date_debut'] ?? '');
            $date_fin = trim($_POST['date_fin'] ?? '');
            $id_locataire = intval($_SESSION['user_id']);
            $id_tarif_input = trim($_POST['id_tarif'] ?? '');
            
            // Si le tarif est "auto", le calculer automatiquement
            if ($id_tarif_input === 'auto') {
                $id_tarif = getAutoTarif($pdo, $id_bien, $date_debut);
            } else {
                $id_tarif = intval($id_tarif_input);
            }

            if ($date_debut && $date_fin && $id_locataire && $id_tarif) {
                // Validate date format first
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin)) {
                    $message = "Format de date invalide. Utilisez le format YYYY-MM-DD.";
                } else {
                    // Validate dates: cannot reserve before today, and end >= start
                    try {
                        $dtStart = new DateTime($date_debut);
                        $dtEnd = new DateTime($date_fin);
                        $today = new DateTime();
                        $today->setTime(0,0,0);

                        if ($dtStart < $today) {
                            $message = "La date de début doit être aujourd'hui ou ultérieure.";
                        } elseif ($dtEnd < $dtStart) {
                            $message = "La date de fin doit être postérieure ou égale à la date de début.";
                        } else {
                            // Server-side overlap check with existing reservations for this bien
                            $overlapStmt = $pdo->prepare('SELECT 1 FROM Reservation WHERE id_biens = ? AND NOT (date_fin_reservation < ? OR date_debut_reservation > ?) LIMIT 1');
                            $overlapStmt->execute([$id_bien, $date_debut, $date_fin]);
                            $overlap = $overlapStmt->fetchColumn();
                            if ($overlap) {
                                $message = 'Les dates choisies se chevauchent avec une réservation existante.';
                            } else {
                                // Check unavailable weeks table if exists
                                try {
                                    // Build unique year-week pairs between start and end
                                    $pairs = [];
                                    $d = clone $dtStart;
                                    while ($d <= $dtEnd) {
                                        $w = intval($d->format('W'));
                                        $y = intval($d->format('Y'));
                                        $key = $y . '-' . $w;
                                        if (!isset($pairs[$key])) { $pairs[$key] = ['annee' => $y, 'semaine' => $w]; }
                                        $d->modify('+1 day');
                                    }

                                    if (!empty($pairs)) {
                                        $conds = [];
                                        $params = [$id_bien];
                                        foreach ($pairs as $p) {
                                            $conds[] = '(annee = ? AND semaine = ?)';
                                            $params[] = $p['annee'];
                                            $params[] = $p['semaine'];
                                        }
                                        $sql = 'SELECT 1 FROM semaine_indisponible WHERE id_biens = ? AND (' . implode(' OR ', $conds) . ') LIMIT 1';
                                        $uStmt = $pdo->prepare($sql);
                                        $uStmt->execute($params);
                                        $blocked = $uStmt->fetchColumn();
                                        if ($blocked) {
                                            $message = 'Les dates sélectionnées comprennent des semaines marquées comme indisponibles.';
                                        }
                                    }
                                } catch (Exception $e) {
                                    // If table missing or error, continue — previously we used Biens.unavailable_weeks client-side
                                }
                            }

                            if (empty($message)) {
                                $stmt = $pdo->prepare('INSERT INTO Reservation (date_debut_reservation, date_fin_reservation, id_locataire, id_biens, id_Tarif) VALUES (?, ?, ?, ?, ?)');
                                $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif]);
                                header('Location: annonce_detail.php?id=' . $id_bien . '&reservation=success');
                                exit;
                            }
                        }
                    } catch (Exception $e) {
                        $message = "Format de date invalide.";
                    }
                }
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Annuler une réservation (utilisateur courant ou admin)
        if (isset($_POST['cancel_reservation']) && isset($_POST['id_reservation'])) {
            if (!isset($_SESSION['user_id'])) {
                header('Location: ../auth/connexion.php');
                exit;
            }
            $id_res = intval($_POST['id_reservation']);
            $currentUser = intval($_SESSION['user_id']);
            $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'animateur';

            if ($isAdmin) {
                $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_reservation = ?');
                $stmt->execute([$id_res]);
                header('Location: annonce_detail.php?id=' . $id_bien . '&canceled=1');
                exit;
            } else {
                $stmtCheck = $pdo->prepare('SELECT id_locataire FROM Reservation WHERE id_reservation = ?');
                $stmtCheck->execute([$id_res]);
                $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if ($row && intval($row['id_locataire']) === $currentUser) {
                    $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_reservation = ?');
                    $stmt->execute([$id_res]);
                    header('Location: annonce_detail.php?id=' . $id_bien . '&canceled=1');
                    exit;
                } else {
                    $message = 'Action non autorisée.';
                }
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
                
                // Supprimer les tarifs par défaut pour ce bien
                $delTarifDefaut = $pdo->prepare('DELETE FROM Tarif_Defaut WHERE id_biens = ?');
                $delTarifDefaut->execute([$id_bien]);

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
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = basename($_FILES['photos']['name'][$key]);
                            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            if (in_array($fileExtension, $allowedExtensions)) {
                                $newFileName = uniqid('img_', true) . '.' . $fileExtension;
                                $destPath = $uploadDir . $newFileName;
                                if (move_uploaded_file($tmpName, $destPath)) {
                                    // Save photo reference to database (just filename, display code uses basename)
                                    $stmtPhoto = $pdo->prepare('INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (?, ?, ?)');
                                    $stmtPhoto->execute([$fileName, $newFileName, $id_bien]);
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

        // Récupérer les avis (reviews) pour ce bien - uniquement les validés
        $reviewsStmt = $pdo->prepare('SELECT r.*, l.nom_locataire, l.prenom_locataire FROM Reviews r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire WHERE r.id_biens = ? AND r.validated = 1 ORDER BY r.created_at DESC');
        $reviewsStmt->execute([$id_bien]);
        $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculer la note moyenne - uniquement sur les avis validés
        $avgStmt = $pdo->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as count_reviews FROM Reviews WHERE id_biens = ? AND validated = 1');
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
            // Supprimer les tarifs par défaut associés
            $stmt = $pdo->prepare('DELETE FROM Tarif_Defaut WHERE id_biens = ?');
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
            // Vérifier que l'utilisateur est connecté
            if (!isset($_SESSION['user_id'])) {
                header('Location: ../auth/connexion.php');
                exit;
            }
            
            $reviewBienId = intval($_POST['review_bien_id'] ?? 0);
            $rating = intval($_POST['rating'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $userId = intval($_SESSION['user_id']);
            
            if ($reviewBienId > 0 && ($rating > 0 || $content !== '')) {
                try {
                    $ins = $pdo->prepare('INSERT INTO Reviews (id_biens, id_locataire, rating, content, created_at, validated) VALUES (?, ?, ?, ?, NOW(), FALSE)');
                    $ins->execute([$reviewBienId, $userId, $rating > 0 ? $rating : null, $content]);
                    $message = "Votre avis a été soumis et sera validé par un administrateur.";
                } catch (PDOException $e) {
                    $message = 'Erreur lors de la publication de l\'avis : ' . $e->getMessage();
                }
            } else {
                $message = 'Veuillez saisir une note ou un commentaire.';
            }
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}

// Vérifier si l'utilisateur peut poster un avis (réservation passée obligatoire)
$canPostReview = false;
if (isset($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);
    $stmt = $pdo->prepare('SELECT 1 FROM Reservation WHERE id_locataire = ? AND id_biens = ? AND date_fin_reservation < NOW() LIMIT 1');
    $stmt->execute([$userId, $id_bien]);
    $canPostReview = (bool)$stmt->fetchColumn();
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
                    <?php
                    $photoLabel = trim(str_ireplace('photo', '', $photo['nom_photos']));
                    // Gérer les différents formats de chemin de photos
                    $lienPhoto = $photo['lien_photo'];
                    if (strpos($lienPhoto, 'Projet_HAP') !== false || strpos($lienPhoto, 'images/uploads/') !== false) {
                        // Ancien format avec chemin complet
                        $photoPath = '/' . $lienPhoto;
                    } else {
                        // Nouveau format avec juste le nom du fichier
                        $photoPath = '../images/uploads/' . basename($lienPhoto);
                    }
                    ?>
                    <a href="<?= htmlspecialchars($photoPath) ?>" data-lightbox="gallery" data-title="<?= htmlspecialchars($photoLabel) ?>">
                        <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($bien['nom_biens']) ?>" class="gallery-image">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($averageRating)): ?>
            <div class="rating-summary" style="margin-top:12px;">
                <strong>Note moyenne :</strong> <?= number_format($averageRating, 2) ?> / 5 (<?= intval($avgRow['count_reviews']) ?> avis)
            </div>
        <?php endif; ?>

        <!-- Détails de l'annonce -->
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
                    <h4>Réservation</h4>
                    <p style="font-size: 0.9em; color: #666; margin-bottom: 15px;">Sélectionnez vos dates sur le calendrier ci-dessous. Le tarif sera calculé automatiquement selon vos dates.</p>
                    
                    <?php if ($tarifs): ?>
                        <!-- Afficher les semaines spéciales -->
                        <div id="special-weeks-section" style="background: #f9f9f9; padding: 12px; border-radius: 4px; margin-bottom: 15px; display: none;">
                            <h5 style="margin-top: 0; font-size: 0.95em;">Semaines spéciales (tarifs différents des tarifs par défaut):</h5>
                            <div id="special-weeks-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; font-size: 0.85em;">
                            </div>
                        </div>
        
                        <!-- Le tarif est calculé automatiquement selon les saisons; l'utilisateur ne choisit pas le tarif -->
        
                        <!-- Formulaire de réservation -->
                        <div id="reservation-form" style="display: none; margin-top: 15px;">
                            <form method="post">
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px;">
                                    <input type="date" id="date_debut" name="date_debut" placeholder="Date début" required min="<?= date('Y-m-d') ?>">
                                    <input type="date" id="date_fin" name="date_fin" placeholder="Date fin" required min="<?= date('Y-m-d') ?>">
                                    <input type="hidden" name="id_tarif" id="hidden_id_tarif" value="auto">
                                </div>
                
                                <!-- Aperçu du coût -->
                                <div id="cost-preview" style="background: #e8f5e9; padding: 15px; border-radius: 4px; border: 1px solid #81c784; margin-bottom: 15px; display: none;">
                                    <h5 style="margin-top: 0; font-size: 0.95em; color: #2e7d32;">Récapitulatif du tarif</h5>
                                    <div id="cost-breakdown" style="font-size: 0.85em; margin-bottom: 12px;">
                                    </div>
                                    <div style="font-weight: bold; font-size: 1.1em; color: #1b5e20;">
                                        Montant total : <span id="total-cost">0,00</span> €
                                    </div>
                                </div>
                
                                <button type="submit" name="add_reservation" class="reserve-btn" id="confirm-btn">Confirmer la réservation</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p style="color: #d32f2f; font-weight: 500;">Aucun tarif disponible pour ce bien.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php
            // Afficher les réservations du user pour ce bien (avec bouton annuler)
            if (isset($_SESSION['user_id'])) {
                $currentUserId = intval($_SESSION['user_id']);
                $stmtMyRes = $pdo->prepare('SELECT id_reservation, date_debut_reservation, date_fin_reservation FROM Reservation WHERE id_biens = ? AND id_locataire = ? ORDER BY id_reservation DESC');
                $stmtMyRes->execute([$id_bien, $currentUserId]);
                $myReservations = $stmtMyRes->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($myReservations)) {
        ?>
                    <div class="my-reservations" style="margin-top:18px;">
                        <h4>Vos réservations pour ce bien</h4>
                        <ul style="list-style:none;padding:0;">
                            <?php foreach ($myReservations as $r): ?>
                                <li style="margin-bottom:8px;">
                                    <strong><?= htmlspecialchars($r['date_debut_reservation']) ?> → <?= htmlspecialchars($r['date_fin_reservation']) ?></strong>
                                    <form method="post" style="display:inline;margin-left:10px;" onsubmit="return confirm('Voulez-vous annuler cette réservation ?');">
                                        <input type="hidden" name="id_reservation" value="<?= intval($r['id_reservation']) ?>">
                                        <button type="submit" name="cancel_reservation" class="reserve-btn" style="background:#d32f2f;border:none;padding:6px 10px;color:#fff;border-radius:6px;">Annuler la réservation</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
        <?php
                }
            }
        ?>

        <?php if ($canEdit): ?>
        <div class="actions">
            <?php if ($canEdit): ?>
                <button type="button" id="edit-btn" class="reserve-btn">Modifier cette annonce</button>
                <a href="manage_tarifs.php?id_bien=<?= htmlspecialchars($id_bien) ?>" class="reserve-btn" style="display:inline-block;text-decoration:none;">Gérer tarifs & disponibilités</a>
                <form method="post" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                    <button type="submit" name="delete_bien">Supprimer cette annonce</button>
                </form>
            <?php endif; ?>
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
                        <label for="photos_input">Photos:</label>
                        <div id="photos-container"></div>
                        <div id="photo-previews" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;"></div>
                        <div style="margin-top:8px;">
                            <button type="button" id="add-photo-input">Sélectionner des photos</button>
                            <small style="display:block;color:#666;margin-top:6px;">Glissez-déposez vos photos ou sélectionnez plusieurs fichiers à la fois.</small>
                        </div>
                    </div>
                    <input type="submit" name="update_bien" value="Mettre à jour">
                </form>
            </div>
        </div>

        <!-- Calendrier FullCalendar -->
        <div class="calendar-section" style="margin-top: 40px;">
            <h3>Calendrier des disponibilités</h3>
            <div id="calendar-disponible" style="max-width: 900px; margin: 0 auto;"></div>
        </div>

        <!-- AVIS (Reviews) à la toute fin -->
        <section class="reviews-section" style="margin-top:48px;">
            <h3>Avis des locataires</h3>
            <form method="get" style="margin-bottom:18px;display:flex;gap:12px;align-items:center;">
                <input type="hidden" name="id" value="<?= $id_bien ?>">
                <label>Filtrer par note :</label>
                <select name="filter_note" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <?php for($i=5;$i>=1;$i--): ?>
                        <option value="<?= $i ?>" <?= (isset($_GET['filter_note']) && $_GET['filter_note']==$i)?'selected':''; ?>><?= $i ?> ★</option>
                    <?php endfor; ?>
                </select>
            </form>
            <?php
            $filteredReviews = $reviews;
            if (isset($_GET['filter_note']) && in_array($_GET['filter_note'], ['1','2','3','4','5'])) {
                $filteredReviews = array_filter($reviews, function($r) {
                    return intval($r['rating']) == intval($_GET['filter_note']);
                });
            }
            ?>
            <?php if (!empty($filteredReviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($filteredReviews as $rev): ?>
                        <div class="review-card" style="border:1px solid #eee;padding:12px;margin-bottom:12px;border-radius:6px;">
                            <div style="font-weight:600;margin-bottom:6px;">
                                <?= htmlspecialchars($rev['prenom_locataire'] . ' ' . strtoupper(substr($rev['nom_locataire'],0,1)).'.') ?>
                            </div>
                            <div style="color:#f39c12;margin-bottom:6px;">
                                <?= str_repeat('★', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])) ?>
                            </div>
                            <div style="margin-bottom:6px;"><?= nl2br(htmlspecialchars($rev['content'])) ?></div>
                            <div style="font-size:0.85em;color:#888;">Posté le <?= htmlspecialchars(date('d-m-Y à H:i', strtotime($rev['created_at']))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucun avis pour ce bien.</p>
            <?php endif; ?>

            <?php if ($canPostReview): ?>
            <div class="review-form-block" style="margin-top:32px;">
                <h4>Laisser un avis</h4>
                <form method="post" class="review-form">
                    <input type="hidden" name="review_bien_id" value="<?= $id_bien ?>">
                    <div class="form-group">
                        <label for="rating">Note</label>
                        <select name="rating" id="rating" required>
                            <option value="">Choisir une note</option>
                            <?php for($i=5;$i>=1;$i--): ?>
                                <option value="<?= $i ?>"><?= $i ?> ★</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="content">Commentaire</label>
                        <textarea name="content" id="content" rows="3" maxlength="500" style="width:100%;"></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="profile-button">Publier mon avis</button>
                </form>
            </div>
            <?php elseif(isset($_SESSION['user_id'])): ?>
                <p style="color:#888;">Vous devez avoir réservé et terminé un séjour pour ce bien pour pouvoir laisser un avis.</p>
            <?php else: ?>
                <p style="color:#888;">Connectez-vous pour laisser un avis.</p>
            <?php endif; ?>
        </section>


    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        $(document).ready(function() {
            // Removed: tarif-select change handler (now handled by calendar selection)

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

            // Photos : drag & drop + multiple selection handling
            const photoDT = new DataTransfer();
            const $photosInput = $('<input id="photos_input" type="file" name="photos[]" accept="image/*" multiple style="display:none;">');
            $('#photos-container').empty().append($photosInput);

            function refreshInputFiles() {
                const input = document.getElementById('photos_input');
                try { input.files = photoDT.files; } catch (e) { /* some browsers restrict setting files directly */ }
            }

            function renderPreviews() {
                $('#photo-previews').empty();
                for (let i = 0; i < photoDT.files.length; i++) {
                    const file = photoDT.files[i];
                    const idx = i;
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const $preview = $('<div class="photo-preview" data-preview-index="' + idx + '" style="position:relative;width:100px;height:70px;border:1px solid #ddd;padding:4px;display:flex;align-items:center;justify-content:center;background:#fff;border-radius:6px;overflow:hidden;margin-right:8px;margin-bottom:8px;"></div>');
                        const $img = $('<img src="' + evt.target.result + '" style="max-width:100%;max-height:100%;display:block;">');
                        const $remove = $('<button type="button" class="remove-preview" data-remove-index="' + idx + '" style="position:absolute;top:2px;right:2px;background:#fff;border:0;padding:2px 6px;border-radius:4px;cursor:pointer;">✕</button>');
                        $remove.on('click', function(){
                            const removeIndex = parseInt($(this).attr('data-remove-index'));
                            const newDT = new DataTransfer();
                            for (let j = 0; j < photoDT.files.length; j++) {
                                if (j === removeIndex) continue;
                                newDT.items.add(photoDT.files[j]);
                            }
                            // replace contents of photoDT by copying
                            while (photoDT.items.length > 0) photoDT.items.remove(0);
                            for (let k = 0; k < newDT.files.length; k++) photoDT.items.add(newDT.files[k]);
                            refreshInputFiles();
                            renderPreviews();
                        });
                        $preview.append($img).append($remove);
                        $('#photo-previews').append($preview);
                    };
                    reader.readAsDataURL(file);
                }
            }

            // Add files to DataTransfer and render previews
            function addFilesToDT(files) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
                let rejectedFiles = [];
                
                for (let f of files) {
                    // Validate file type
                    const fileName = f.name.toLowerCase();
                    const fileType = f.type.toLowerCase();
                    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                    const hasValidMimeType = allowedTypes.includes(fileType);
                    
                    if (!hasValidExtension || !hasValidMimeType) {
                        rejectedFiles.push(f.name);
                        continue;
                    }
                    
                    // skip duplicates (name+size)
                    let exists = false;
                    for (let i = 0; i < photoDT.files.length; i++) { if (photoDT.files[i].name === f.name && photoDT.files[i].size === f.size) { exists = true; break; } }
                    if (!exists) photoDT.items.add(f);
                }
                
                if (rejectedFiles.length > 0) {
                    alert('❌ Fichiers rejetés (format non autorisé) :\n' + rejectedFiles.join('\n') + '\n\nFormats acceptés : JPG, PNG, GIF, WEBP');
                }
                
                refreshInputFiles();
                renderPreviews();
            }

            // open file selector
            $('#add-photo-input').off('click').on('click', function() { $('#photos_input').trigger('click'); });

            // when user selects files via picker
            $(document).on('change', '#photos_input', function(e) {
                const files = e.target.files;
                addFilesToDT(files);
            });

            // drag & drop support on photos container area
            const $dropZone = $('<div id="photo-dropzone" style="border:2px dashed #ccc;border-radius:6px;padding:16px;text-align:center;cursor:pointer;background:#fafafa;">Glissez-déposez vos photos ici ou cliquez pour sélectionner</div>');
            $('#photos-container').prepend($dropZone);
            $dropZone.on('click', function(){ $('#photos_input').trigger('click'); });

            $dropZone.on('dragover', function(e){ e.preventDefault(); e.originalEvent.dataTransfer.dropEffect = 'copy'; $(this).css('border-color','#6aa6ff'); });
            $dropZone.on('dragleave', function(e){ e.preventDefault(); $(this).css('border-color','#ccc'); });
            $dropZone.on('drop', function(e){ e.preventDefault(); $(this).css('border-color','#ccc'); const dt = e.originalEvent.dataTransfer; if (dt && dt.files && dt.files.length) { addFilesToDT(dt.files); } });
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
        // Global array to hold tariff data (special weeks)
        var tariffData = <?= json_encode($tarifs) ?>;
        
        // Function to display special weeks
        function displaySpecialWeeks() {
            if (!tariffData || tariffData.length === 0) return;
            
            var specialWeeksHtml = '';
            tariffData.forEach(function(tarif) {
                specialWeeksHtml += '<div style="background: white; padding: 8px; border-left: 4px solid #1976d2; border-radius: 2px;">' +
                    '<strong>Semaine ' + tarif.semaine_Tarif + ' (' + tarif.lib_saison + ')</strong> : ' +
                    '<span style="color: #1976d2; font-weight: bold;">€' + parseFloat(tarif.tarif).toFixed(2) + '</span>/semaine' +
                    '</div>';
            });
            
            $('#special-weeks-list').html(specialWeeksHtml);
            $('#special-weeks-section').show();
        }
        
        // Function to calculate cost
        function calculateCost() {
            var dateDebut = document.getElementById('date_debut').value;
            var dateFin = document.getElementById('date_fin').value;
            
            if (!dateDebut || !dateFin) {
                $('#cost-preview').hide();
                $('#confirm-btn').hide();
                return;
            }
            
            // Call the cost calculation API
            fetch('../api/calculate_reservation_cost.php?id_bien=<?= $id_bien ?>&date_debut=' + dateDebut + '&date_fin=' + dateFin)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Erreur : ' + data.error);
                        return;
                    }
                    
                    // Display breakdown
                    var breakdownHtml = '';
                    var previousWeek = null;
                    
                    data.details.forEach(function(detail, index) {
                        var weekLabel = 'Semaine ' + detail.week + ' (' + detail.year + ')';
                        var rateInfo = detail.is_special ? 
                            '(tarif spécial, ' + detail.saison + ')' : 
                            '(tarif par défaut, ' + detail.saison + ')';
                        
                        breakdownHtml += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 1px solid #c8e6c9;">' +
                            '<div>' +
                                '<strong>' + weekLabel + '</strong> ' + rateInfo +
                                '<br><small style="color: #558b2f;">Tarif hebdomadaire: €' + parseFloat(detail.price_per_week).toFixed(2) + '/semaine</small>' +
                            '</div>' +
                            '<div style="font-weight: 500; color: #1976d2;">€' + parseFloat(detail.subtotal).toFixed(2) + '</div>' +
                            '</div>';
                    });
                    
                    $('#cost-breakdown').html(breakdownHtml);
                    $('#total-cost').text(parseFloat(data.total).toFixed(2).replace('.', ','));
                    $('#cost-preview').show();
                    $('#confirm-btn').show();
                    
                    // Set the hidden tarif field - we'll use a special flag "auto" since we compute it server-side
                    document.getElementById('hidden_id_tarif').value = 'auto';
                })
                .catch(error => {
                    console.error('Erreur lors du calcul du coût:', error);
                    alert('Erreur lors du calcul du montant.');
                });
        }
        
        // Set up event listeners for date inputs
        document.getElementById('date_debut').addEventListener('change', calculateCost);
        document.getElementById('date_fin').addEventListener('change', calculateCost);
        
        // Display special weeks on page load
        displaySpecialWeeks();
        
            // Handle tarif select for manual selection
            // '#tarif-select' removed: tarif is auto-calculated (hidden_id_tarif = 'auto')
            // Ensure date inputs have min today in case server-side rendering differs
            (function setDateInputMin() {
                var today = new Date().toISOString().split('T')[0];
                var d1 = document.getElementById('date_debut');
                var d2 = document.getElementById('date_fin');
                if (d1) d1.setAttribute('min', today);
                if (d2) d2.setAttribute('min', today);
            })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar-disponible');
            var today = new Date();
            var todayStr = today.toISOString().split('T')[0];
            var reservedDates = new Set();
            var unavailableWeeks = new Set();
            // Inject CSS to force visibility for unavailable weeks
            (function(){
                var s = document.createElement('style');
                s.type = 'text/css';
                s.textContent = '\n                    .fc-daygrid-day.fc-unavailable-week { background: linear-gradient(135deg,#ffb3b3,#ff6666) !important; color: #fff !important; }\n                    .fc-daygrid-day.fc-unavailable-week .fc-daygrid-day-number { color: #fff !important; font-weight: 800; }\n                    .fc-event.unavailable-week { background: #ff6666 !important; border-color: #cc0000 !important; color: #fff !important; font-weight: 800 !important; }\n                    .fc-event.unavailable-week .fc-event-title { text-transform: uppercase; }\n                ';
                document.head.appendChild(s);
            })();

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                selectable: true,
                editable: true,
                validRange: { start: todayStr },
                selectMinDistance: 0,
                events: function(fetchInfo, successCallback, failureCallback) {
                    fetch('../api/get_reservations_bien.php?id_bien=<?= $id_bien ?>')
                        .then(response => response.json())
                        .then(data => {
                            var events = data.map(reservation => ({
                                id: reservation.id,
                                title: reservation.title,
                                start: reservation.start,
                                end: reservation.end,
                                backgroundColor: reservation.backgroundColor,
                                borderColor: reservation.borderColor,
                                display: reservation.display,
                                className: reservation.className,
                                editable: reservation.editable
                            }));
                            // Collect reserved dates for strikethrough
                            data.forEach(reservation => {
                                let start = new Date(reservation.start);
                                let end = new Date(reservation.end);
                                for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                                    reservedDates.add(d.toISOString().split('T')[0]);
                                    // Also mark unavailable weeks
                                    if (reservation.className === 'unavailable-week') {
                                        let weekNum = Math.ceil((d - new Date(d.getFullYear(), 0, 1)) / 86400000 / 7);
                                        unavailableWeeks.add(weekNum);
                                    }
                                }
                            });
                            successCallback(events);
                        })
                        .catch(error => {
                            console.error('Erreur lors de la récupération des réservations:', error);
                            failureCallback(error);
                        });
                },
                eventDidMount: function(info) {
                    try {
                        var evt = info.event;
                        var el = info.el;
                        var isUnavailable = evt.classNames && evt.classNames.indexOf('unavailable-week') !== -1;
                        if (isUnavailable) {
                            // Strong inline styles for the event element
                            el.style.backgroundColor = '#ff6666';
                            el.style.borderColor = '#cc0000';
                            el.style.color = '#ffffff';
                            el.style.fontWeight = '700';
                            // add badge to title if possible
                            var titleEl = el.querySelector('.fc-event-title');
                            if (titleEl && !titleEl.querySelector('.fc-unavail-badge')) {
                                var badge = document.createElement('span');
                                badge.className = 'fc-unavail-badge';
                                badge.textContent = ' ⚠️ INDISPONIBLE';
                                badge.style.cssText = 'background:#cc0000;color:#fff;padding:2px 6px;margin-left:6px;border-radius:4px;font-size:0.75em;';
                                titleEl.appendChild(badge);
                            }

                            // Mark day cells in the range as unavailable (adds class to day cells)
                            var start = evt.start;
                            var end = evt.end;
                            if (start && end) {
                                var d = new Date(start);
                                while (d < end) {
                                    var dateStr = d.toISOString().split('T')[0];
                                    var dayEl = document.querySelector('.fc-daygrid-day[data-date="' + dateStr + '"]');
                                    if (dayEl) dayEl.classList.add('fc-unavailable-week');
                                    d.setDate(d.getDate() + 1);
                                }
                            }
                        }
                    } catch (e) {
                        console.error('eventDidMount error', e);
                    }
                },
                dayCellDidMount: function(info) {
                    if (reservedDates.has(info.dateStr)) {
                        var dayNumberEl = info.el.querySelector('.fc-daygrid-day-number');
                        if (dayNumberEl) {
                            dayNumberEl.style.textDecoration = 'line-through';
                        }
                    }
                },
                eventDrop: function(info) {
                    var event = info.event;
                    var newStart = event.startStr;
                    var newEnd = new Date(event.end);
                    newEnd.setDate(newEnd.getDate() - 1); // FullCalendar end is exclusive
                    var newEndStr = newEnd.toISOString().split('T')[0];

                    if (confirm('Voulez-vous modifier cette réservation ?')) {
                        fetch('../api/update_reservation.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                id_reservation: event.id,
                                date_debut: newStart,
                                date_fin: newEndStr,
                                id_bien: <?= $id_bien ?>
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Réservation mise à jour avec succès.');
                                calendar.refetchEvents();
                            } else {
                                alert('Erreur: ' + data.message);
                                info.revert();
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la mise à jour:', error);
                            alert('Erreur lors de la mise à jour de la réservation.');
                            info.revert();
                        });
                    } else {
                        info.revert();
                    }
                },
                select: function(info) {
                    // Check if selection includes unavailable dates
                    var start = new Date(info.startStr);
                    var end = new Date(info.endStr);
                    var hasUnavailable = false;

                    for (let d = new Date(start); d < end; d.setDate(d.getDate() + 1)) {
                        let dateStr = d.toISOString().split('T')[0];
                        if (reservedDates.has(dateStr)) {
                            hasUnavailable = true;
                            break;
                        }
                    }

                    if (hasUnavailable) {
                        alert('Ces dates contiennent des semaines réservées ou indisponibles.');
                        calendar.unselect();
                        return;
                    }

                    // Date range selected by user (drag & drop on calendar)
                    var startDate = info.startStr.split('T')[0]; // YYYY-MM-DD format
                    var endDate = new Date(info.end);
                    endDate.setDate(endDate.getDate() - 1); // FullCalendar end is exclusive
                    var endDateStr = endDate.toISOString().split('T')[0];

                    // Fill the reservation form with selected dates
                    if (document.getElementById('date_debut')) {
                        document.getElementById('date_debut').value = startDate;
                    }
                    if (document.getElementById('date_fin')) {
                        document.getElementById('date_fin').value = endDateStr;
                    }

                    // Show the reservation form if it exists
                    var formEl = document.getElementById('reservation-form');
                    if (formEl) {
                        formEl.style.display = 'block';
                        // Trigger cost calculation
                        calculateCost();
                        // Scroll to the form smoothly
                        setTimeout(function() {
                            formEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 100);
                    }

                    // Clear selection after processing
                    setTimeout(function() {
                        calendar.unselect();
                    }, 300);
                }
            });
            calendar.render();
        });
    </script>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
