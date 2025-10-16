<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Tarif/Tarif.php';

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
        $tarifClass = new Tarif(null, null, null, null, $pdo);
        $tarifs = $tarifClass->getTarifsByBien($id_bien);

        // Récupération des locataires pour le select
        $locataires = $pdo->query('SELECT id_locataire, nom_locataire, prenom_locataire FROM Locataire')->fetchAll(PDO::FETCH_ASSOC);

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
        <a href="Annonce.form.php" class="back-link">&larr; Retour aux annonces</a>

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
                    <a href="/<?= htmlspecialchars($photo['lien_photo']) ?>" data-lightbox="gallery" data-title="<?= htmlspecialchars($photo['nom_photos']) ?>">
                        <img src="/<?= htmlspecialchars($photo['lien_photo']) ?>" alt="<?= htmlspecialchars($photo['nom_photos']) ?>" class="gallery-image">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
            <form method="post" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">
                <button type="submit" name="delete_bien">Supprimer cette annonce</button>
            </form>
        </div>
    </div>

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
        });
    </script>
</body>
</html>
