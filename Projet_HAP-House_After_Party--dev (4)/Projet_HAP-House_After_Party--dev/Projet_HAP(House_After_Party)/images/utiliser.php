<?php

require_once __DIR__ . '/../config/db.inc.php';

$sql = "SELECT * FROM photos ORDER BY date_upload DESC";
$stmt = $pdo->query($sql);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Galerie de photos</title>
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
</head>
<body>
    <h2>Galerie d’images</h2>
    <?php if ($photos): ?>
        <?php foreach ($photos as $photo): ?>
            <div style="display:inline-block; margin:10px;">
                <a href="<?= htmlspecialchars($photo['chemin']) ?>" data-lightbox="galerie" data-title="Photo">
                    <img src="<?= htmlspecialchars($photo['chemin']) ?>" width="200" alt="photo">
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune photo enregistrée.</p>
    <?php endif; ?>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</body>
</html>

