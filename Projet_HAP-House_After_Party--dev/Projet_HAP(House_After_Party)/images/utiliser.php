<?php

require_once __DIR__ . '/../config/db.php';

// Récupérer toutes les photos avec les infos des biens
$sql = "SELECT p.*, b.nom_biens, b.id_biens, tb.designation_type_bien as lib_type_biens
        FROM Photos p
        JOIN Biens b ON p.id_biens = b.id_biens
        LEFT JOIN Type_Bien tb ON b.id_type_biens = tb.id_type_biens
        ORDER BY b.nom_biens, p.id_photo";
$stmt = $pdo->query($sql);
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Grouper les photos par bien
$photosByBien = [];
foreach ($photos as $photo) {
    $bienId = $photo['id_biens'];
    if (!isset($photosByBien[$bienId])) {
        $photosByBien[$bienId] = [
            'nom' => $photo['nom_biens'],
            'type' => $photo['lib_type_biens'],
            'photos' => []
        ];
    }
    $photosByBien[$bienId]['photos'][] = $photo;
}

// Compter les statistiques
$totalPhotos = count($photos);
$totalBiens = count($photosByBien);
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
    <style>
        /* Styles améliorés pour la galerie */
        .galerie-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.95em;
            opacity: 0.9;
        }
        
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            background: var(--bg-card, #fff);
            border: 2px solid #667eea;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .filter-tab:hover, .filter-tab.active {
            background: #667eea;
            color: white;
        }
        
        .bien-section {
            margin: 40px 0;
            padding: 30px;
            background: var(--bg-card, #fff);
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .bien-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .bien-title {
            font-size: 1.5em;
            color: var(--logo-color, #a100b8);
            margin: 0;
        }
        
        .bien-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85em;
        }
        
        .bien-photos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .photo-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            aspect-ratio: 4/3;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .photo-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .photo-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
        }
        
        .photo-card:hover img {
            transform: scale(1.1);
        }
        
        .photo-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .photo-card:hover .photo-overlay {
            opacity: 1;
        }
        
        .photo-name {
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .view-all-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 25px;
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .view-all-btn:hover {
            background: #667eea;
            color: white;
        }
        
        /* Mode sombre */
        [data-theme="dark"] .bien-section {
            background: var(--bg-card, #1e293b);
        }
        
        [data-theme="dark"] .filter-tab {
            background: var(--bg-card, #1e293b);
        }
        
        @media (max-width: 768px) {
            .galerie-stats {
                gap: 20px;
            }
            
            .stat-card {
                padding: 15px 25px;
            }
            
            .stat-number {
                font-size: 2em;
            }
            
            .bien-photos {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="galerie-container">
        <div class="galerie-header">
            <h2>🖼️ Galerie HAP</h2>
            <p>Découvrez l'ambiance unique de nos logements à travers ces photos authentiques</p>
            <a href="../../index.php" class="btn-retour">🏠 Retour à l'accueil</a>
        </div>
        
        <?php if ($photos): ?>
        
        <!-- Statistiques -->
        <div class="galerie-stats">
            <div class="stat-card">
                <span class="stat-number"><?= $totalPhotos ?></span>
                <span class="stat-label">📸 Photos</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $totalBiens ?></span>
                <span class="stat-label">🏠 Logements</span>
            </div>
        </div>
        
        <!-- Filtres par type -->
        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">Tous</button>
            <?php
            $types = [];
            foreach ($photosByBien as $bien) {
                if ($bien['type'] && !in_array($bien['type'], $types)) {
                    $types[] = $bien['type'];
                }
            }
            foreach ($types as $type):
            ?>
            <button class="filter-tab" data-filter="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></button>
            <?php endforeach; ?>
        </div>
        
        <!-- Photos groupées par bien -->
        <?php foreach ($photosByBien as $bienId => $bien): ?>
        <div class="bien-section" data-type="<?= htmlspecialchars($bien['type']) ?>">
            <div class="bien-header">
                <h3 class="bien-title">🏠 <?= htmlspecialchars($bien['nom']) ?></h3>
                <span class="bien-badge"><?= htmlspecialchars($bien['type'] ?: 'Non catégorisé') ?> • <?= count($bien['photos']) ?> photos</span>
            </div>
            <div class="bien-photos">
                <?php foreach ($bien['photos'] as $photo): 
                    $photoPath = '/' . $photo['lien_photo'];
                ?>
                <div class="photo-card">
                    <a href="<?= htmlspecialchars($photoPath) ?>" data-lightbox="bien-<?= $bienId ?>" data-title="<?= htmlspecialchars($bien['nom']) ?> - <?= htmlspecialchars($photo['nom_photos']) ?>">
                        <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($photo['nom_photos']) ?>" loading="lazy">
                        <div class="photo-overlay">
                            <span class="photo-name"><?= htmlspecialchars($photo['nom_photos']) ?></span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <a href="../forms/annonce_detail.php?id=<?= $bienId ?>" class="view-all-btn">Voir l'annonce →</a>
        </div>
        <?php endforeach; ?>
        
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
    
    <script>
    // Filtrage par type
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Retirer la classe active de tous les onglets
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            
            document.querySelectorAll('.bien-section').forEach(section => {
                if (filter === 'all' || section.dataset.type === filter) {
                    section.style.display = 'block';
                    section.style.animation = 'fadeIn 0.4s ease';
                } else {
                    section.style.display = 'none';
                }
            });
        });
    });
    
    // Animation fade in
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(style);
    </script>
    
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>

