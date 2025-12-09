<?php

require_once __DIR__ . '/../config/db.php';

$sql = "SELECT * FROM Photos";
$stmt = $pdo->query($sql);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie de photos - House After Party</title>
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Galerie CSS personnalisé -->
    <link rel="stylesheet" href="../Css/galerie.css">
</head>
<body>
    <div class="galerie-container">
        <div class="galerie-header">
            <h2>🖼️ Galerie HAP</h2>
            <p>Découvrez l'ambiance unique de nos logements à travers ces photos authentiques</p>
            <a href="../../index.php" class="btn-retour">🏠 Retour à l'accueil</a>
        </div>
        <?php if ($photos): ?>
            <div class="galerie-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="galerie-item">
                        <a href="/<?= htmlspecialchars($photo['lien_photo']) ?>" data-lightbox="galerie" data-title="<?= htmlspecialchars($photo['nom_photos']) ?>">
                            <img src="/<?= htmlspecialchars($photo['lien_photo']) ?>" alt="<?= htmlspecialchars($photo['nom_photos']) ?>">
                        </a>
                        <div class="galerie-info">
                            <h3><?= htmlspecialchars($photo['nom_photos']) ?></h3>
                            <p>ID Bien: <?= htmlspecialchars($photo['id_biens']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="galerie-empty">
                <h3>📷 Aucune photo pour le moment</h3>
                <p>Les photos des logements seront bientôt disponibles. Revenez plus tard !</p>
                <a href="../../index.php" class="btn-primary">Retour à l'accueil</a>
            </div>
        <?php endif; ?>
    </div>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>

