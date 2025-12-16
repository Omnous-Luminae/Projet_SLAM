<?php
session_start();
require_once __DIR__ . '/Projet_HAP(House_After_Party)/config/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>House After Party</title>
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <a href="#" class="logo">
            <span class="logo-icon">🎵</span> HAP
        </a>
        <nav>
            <a href="#" class="active">🏠 Accueil</a>
            <a href="Projet_HAP(House_After_Party)/forms/Annonce.form.php">📅 Annonces</a>
            <a href="Projet_HAP(House_After_Party)/map.php">🗺️ Carte</a>
            <a href="Projet_HAP(House_After_Party)/forms/PtsInteret.form.php">🎵 Point d'Intérêt</a>
            <a href="Projet_HAP(House_After_Party)/forms/blog.php">� Blog</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="Projet_HAP(House_After_Party)/auth/profile.php">👤 Mon profil</a>
            <?php endif; ?>
        </nav>
        <?php
        if (isset($_SESSION['user_name'])) {
            echo '<span class="welcome-msg">Bienvenue, ' . htmlspecialchars($_SESSION['user_name']) . ' ' . '!</span>';
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'animateur') {
                echo '<a href="apropos.php" class="btn-admin">🛠️ Dashboard Admin</a>';
            }
            echo '<a href="Projet_HAP(House_After_Party)/auth/logout.php" class="btn-logout">Se déconnecter</a>';
        } else {
            echo '<a href="Projet_HAP(House_After_Party)/auth/connexion.php" class="btn-login">Se connecter</a>';
        }
        ?>
    </header>
    <section class="hero">
        <h1>House After Party</h1>
        <h2>Avec nous les soirées peuvent s'arroser</h2>
        <p>
            Découvrez des logements meublés exceptionnels à deux pas des meilleures boîtes de nuit.<br>
            Parfait pour vos befores et afters ! Réservez en quelques clics et profitez d'une expérience unique.
        </p>
        <div class="hero-btns">
            <a href="Projet_HAP(House_After_Party)/forms/Annonce.form.php">Voir les logements</a>
            <a href="Projet_HAP(House_After_Party)/forms/Evenement.form.php">Événements à proximité</a>
        </div>
        <img src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80" alt="Soirée" class="hero-bg">
    </section>
    <section class="section">
        <h2>Pourquoi choisir HAP&nbsp;?</h2>
        <p>
            Nous sélectionnons les meilleurs logements pour que vos soirées soient inoubliables.<br>
            Découvrez nos avantages exclusifs pour une expérience nocturne parfaite.
        </p>
        <div class="cards">
            <div class="card">
                <span class="card-icon">🏙️</span>
                <strong>Localisation idéale</strong><br>
                Logements à proximité immédiate des boîtes de nuit les plus populaires
            </div>
            <div class="card">
                <span class="card-icon">🎉</span>
                <strong>Ambiance garantie</strong><br>
                Environnements parfaits pour vos befores et afters mémorables
            </div>
            <div class="card">
                <span class="card-icon">🛎️</span>
                <strong>Réservation instantanée</strong><br>
                Processus simple en quelques clics, disponible 24/7
            </div>
            <div class="card">
                <span class="card-icon">💬</span>
                <strong>Support 24/7</strong><br>
                Équipe dédiée pour vous accompagner à toute heure
            </div>
            <div class="card" id="gallery-card" style="cursor:pointer;">
                <span class="card-icon">📸</span>
                <strong>Galerie photos</strong><br>
                Découvrez les lieux avant de réserver avec nos galeries complètes
            </div>
            <div class="card">
                <span class="card-icon">🔒</span>
                <strong>Sécurité assurée</strong><br>
                Logements vérifiés et sécurisés pour votre tranquillité
            </div>
        </div>
    </section>

    <section class="section">
        <h2>Comment ça marche ?</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Réservez votre logement en quelques étapes simples et profitez d'une nuit inoubliable.
        </p>
        <div class="steps">
            <div class="step">
                <span class="step-number">1</span>
                <h3>Choisissez votre destination</h3>
                <p>Parcourez nos annonces et sélectionnez le logement idéal près de votre boîte de nuit préférée.</p>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <h3>Réservez en ligne</h3>
                <p>Remplissez le formulaire de réservation et payez en toute sécurité.</p>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <h3>Profitez de votre soirée</h3>
                <p>Arrivez à votre logement et commencez votre aventure nocturne !</p>
            </div>
        </div>
    </section>

    <section class="section">
        <h2>Points d'intérêt à découvrir</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Découvrez les lieux incontournables pour vos sorties nocturnes.
        </p>
        <div class="destinations">
            <?php
            // Récupérer 3 points d'intérêt aléatoires avec leurs photos
            $sqlPoi = "SELECT p.id_pts_interet, p.lib_pts_interet, p.description_pts_interet,
                       t.lib_type_points_interet,
                       (SELECT lien_photo_pts FROM Photos_PtsInteret WHERE id_pts_interet = p.id_pts_interet LIMIT 1) as photo
                       FROM Pts_Interet p
                       JOIN Type_Pts_Interet t ON p.id_type_points_interet = t.id_type_points_interet
                       ORDER BY RAND()
                       LIMIT 3";
            $stmtPoi = $pdo->query($sqlPoi);
            $pointsInteret = $stmtPoi->fetchAll(PDO::FETCH_ASSOC);
            
            // Images par défaut selon le type
            $defaultImages = [
                'Boîte de nuit' => 'https://images.unsplash.com/photo-1566737236500-c8ac43014a67?auto=format&fit=crop&w=400&q=80',
                'Bar' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&w=400&q=80',
                'Restaurant' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=400&q=80',
                'default' => 'https://images.unsplash.com/photo-1431274172761-fca41d930114?auto=format&fit=crop&w=400&q=80'
            ];
            
            // Icônes selon le type
            $typeIcons = [
                'Boîte de nuit' => '🎵',
                'Bar' => '🍸',
                'Restaurant' => '🍽️',
                'Club' => '🎶',
                'default' => '📍'
            ];
            
            if (count($pointsInteret) > 0):
                foreach ($pointsInteret as $poi):
                    $photoUrl = $poi['photo'] ? '/' . $poi['photo'] : ($defaultImages[$poi['lib_type_points_interet']] ?? $defaultImages['default']);
                    $icon = $typeIcons[$poi['lib_type_points_interet']] ?? $typeIcons['default'];
                    $description = mb_substr($poi['description_pts_interet'], 0, 100);
            ?>
            <div class="destination">
                <a href="Projet_HAP(House_After_Party)/forms/pts_interet_detail.php?id=<?= $poi['id_pts_interet'] ?>">
                    <img src="<?= htmlspecialchars($photoUrl) ?>" alt="<?= htmlspecialchars($poi['lib_pts_interet']) ?>">
                    <span class="destination-badge"><?= $icon ?> <?= htmlspecialchars($poi['lib_type_points_interet']) ?></span>
                </a>
                <h3><?= htmlspecialchars($poi['lib_pts_interet']) ?></h3>
                <p><?= htmlspecialchars($description) ?><?= mb_strlen($poi['description_pts_interet']) > 100 ? '...' : '' ?></p>
            </div>
            <?php
                endforeach;
            else:
                // Points d'intérêt par défaut si la table est vide
            ?>
            <div class="destination">
                <img src="https://images.unsplash.com/photo-1566737236500-c8ac43014a67?auto=format&fit=crop&w=400&q=80" alt="Clubs">
                <h3>🎵 Clubs & Discothèques</h3>
                <p>Les meilleures boîtes de nuit pour danser jusqu'au bout de la nuit.</p>
            </div>
            <div class="destination">
                <img src="https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&w=400&q=80" alt="Bars">
                <h3>🍸 Bars tendance</h3>
                <p>Des bars branchés pour des soirées entre amis inoubliables.</p>
            </div>
            <div class="destination">
                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=400&q=80" alt="Restaurants">
                <h3>🍽️ Restaurants</h3>
                <p>Savourez une cuisine locale avant de faire la fête.</p>
            </div>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="Projet_HAP(House_After_Party)/forms/PtsInteret.form.php" class="btn btn-primary">Voir tous les points d'intérêt</a>
        </div>
    </section>

    <section class="section">
        <h2>Témoignages de nos clients</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Découvrez ce que disent nos locataires satisfaits.
        </p>
        <div class="testimonials">
            <?php
            // Récupérer 3 avis validés aléatoires avec note >= 4
            $sqlReviews = "SELECT r.content, r.rating, r.created_at,
                           CONCAT(l.prenom_locataire, ' ', SUBSTRING(l.nom_locataire, 1, 1), '.') as auteur,
                           c.nom_commune as ville
                           FROM Reviews r
                           LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire
                           LEFT JOIN Commune c ON l.id_commune = c.id_commune
                           WHERE r.validated = 1 AND r.rating >= 4 AND r.content IS NOT NULL AND r.content != ''
                           ORDER BY RAND()
                           LIMIT 3";
            $stmtReviews = $pdo->query($sqlReviews);
            $reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($reviews) > 0):
                foreach ($reviews as $review):
                    $stars = str_repeat('⭐', $review['rating']);
            ?>
            <div class="testimonial">
                <div class="testimonial-rating"><?= $stars ?></div>
                <p>"<?= htmlspecialchars(mb_substr($review['content'], 0, 200)) ?><?= mb_strlen($review['content']) > 200 ? '...' : '' ?>"</p>
                <cite>- <?= htmlspecialchars($review['auteur'] ?: 'Client anonyme') ?><?= $review['ville'] ? ', ' . htmlspecialchars($review['ville']) : '' ?></cite>
            </div>
            <?php
                endforeach;
            else:
                // Témoignages par défaut si pas d'avis validés
            ?>
            <div class="testimonial">
                <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                <p>"Logement parfait pour notre soirée ! À deux pas du club, propre et bien équipé. HAP a rendu notre week-end inoubliable."</p>
                <cite>- Marie L., Paris</cite>
            </div>
            <div class="testimonial">
                <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
                <p>"Service impeccable et réservation ultra-simple. L'appartement était exactement comme sur les photos. Je recommande !"</p>
                <cite>- Thomas D., Lyon</cite>
            </div>
            <div class="testimonial">
                <div class="testimonial-rating">⭐⭐⭐⭐</div>
                <p>"Idéal pour les afters. Le quartier est vivant et sécurisant. HAP comprend parfaitement les besoins des noctambules."</p>
                <cite>- Sophie M., Marseille</cite>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <section class="gallery-section modern-gallery-section">
        <h2>🖼️ Ambiance HAP en images</h2>
        <p style="text-align: center; color: var(--text-muted, #555); margin-bottom: 30px;">
            Découvrez l'ambiance unique de nos logements à travers ces photos authentiques
        </p>
        
        <?php
        // Récupérer les photos depuis la base de données
        $sqlPhotos = "SELECT p.*, b.nom_biens, b.id_biens
            FROM Photos p
            JOIN Biens b ON p.id_biens = b.id_biens
            ORDER BY RAND()
            LIMIT 6";
        $stmtPhotos = $pdo->query($sqlPhotos);
        $galleryPhotos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($galleryPhotos) > 0):
            $mainPhoto = $galleryPhotos[0];
            $otherPhotos = array_slice($galleryPhotos, 1);
        ?>
        
        <div class="gallery-mosaic">
            <!-- Photo principale (grande) -->
            <div class="gallery-main-item">
                <a href="<?= htmlspecialchars($mainPhoto['lien_photo']) ?>" data-lightbox="hap-gallery" data-title="<?= htmlspecialchars($mainPhoto['nom_biens']) ?>">
                    <img src="<?= htmlspecialchars($mainPhoto['lien_photo']) ?>" alt="<?= htmlspecialchars($mainPhoto['nom_photos']) ?>">
                    <div class="gallery-item-overlay">
                        <span class="gallery-item-title"><?= htmlspecialchars($mainPhoto['nom_biens']) ?></span>
                        <span class="gallery-item-action">🔍 Voir</span>
                    </div>
                </a>
            </div>
            
            <!-- Photos secondaires (grille) -->
            <div class="gallery-grid-items">
                <?php foreach ($otherPhotos as $index => $photo): ?>
                <div class="gallery-grid-item">
                    <a href="<?= htmlspecialchars($photo['lien_photo']) ?>" data-lightbox="hap-gallery" data-title="<?= htmlspecialchars($photo['nom_biens']) ?>">
                        <img src="<?= htmlspecialchars($photo['lien_photo']) ?>" alt="<?= htmlspecialchars($photo['nom_photos']) ?>">
                        <div class="gallery-item-overlay">
                            <span class="gallery-item-title"><?= htmlspecialchars($photo['nom_biens']) ?></span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                
                <?php
                // Compter le total de photos
                $sqlCount = "SELECT COUNT(*) as total FROM Photos";
                $totalPhotos = $pdo->query($sqlCount)->fetch()['total'];
                if ($totalPhotos > 6):
                ?>
                <div class="gallery-grid-item gallery-more">
                    <a href="Projet_HAP(House_After_Party)/images/utiliser.php">
                        <div class="gallery-more-overlay">
                            <span class="gallery-more-count">+<?= $totalPhotos - 6 ?></span>
                            <span class="gallery-more-text">Voir plus</span>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="gallery-empty">
            <span style="font-size: 4em;">📷</span>
            <p>Aucune photo disponible pour le moment.</p>
        </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="Projet_HAP(House_After_Party)/images/utiliser.php" class="btn btn-primary" style="padding: 12px 24px; font-size: 1.1em;">📸 Explorer toute la galerie</a>
        </div>
        
        <style>
            .modern-gallery-section {
                background: linear-gradient(135deg, var(--bg-light, #f8f9fa) 0%, var(--bg-card, #fff) 100%);
                padding: 60px 20px;
            }
            
            .gallery-mosaic {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }
            
            .gallery-main-item {
                grid-row: span 2;
                position: relative;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 15px 40px rgba(0,0,0,0.15);
                min-height: 400px;
            }
            
            .gallery-main-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.5s ease;
            }
            
            .gallery-main-item:hover img {
                transform: scale(1.05);
            }
            
            .gallery-grid-items {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .gallery-grid-item {
                position: relative;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                aspect-ratio: 4/3;
            }
            
            .gallery-grid-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transition: transform 0.4s ease;
            }
            
            .gallery-grid-item:hover img {
                transform: scale(1.1);
            }
            
            .gallery-item-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 20px 15px;
                background: linear-gradient(transparent, rgba(0,0,0,0.8));
                color: white;
                opacity: 0;
                transition: opacity 0.3s;
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .gallery-main-item:hover .gallery-item-overlay,
            .gallery-grid-item:hover .gallery-item-overlay {
                opacity: 1;
            }
            
            .gallery-item-title {
                font-weight: 600;
                font-size: 1em;
            }
            
            .gallery-item-action {
                font-size: 0.85em;
                opacity: 0.8;
            }
            
            .gallery-more {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .gallery-more a {
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .gallery-more-overlay {
                text-align: center;
                color: white;
            }
            
            .gallery-more-count {
                display: block;
                font-size: 2.5em;
                font-weight: 700;
            }
            
            .gallery-more-text {
                font-size: 1.1em;
                opacity: 0.9;
            }
            
            .gallery-more:hover {
                transform: scale(1.02);
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            }
            
            .gallery-empty {
                text-align: center;
                padding: 60px 20px;
                color: var(--text-muted, #666);
            }
            
            /* Dark mode */
            [data-theme="dark"] .modern-gallery-section {
                background: linear-gradient(135deg, var(--bg-dark, #1a1a2e) 0%, var(--bg-card, #16213e) 100%);
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .gallery-mosaic {
                    grid-template-columns: 1fr;
                }
                
                .gallery-main-item {
                    grid-row: span 1;
                    min-height: 250px;
                }
                
                .gallery-grid-items {
                    grid-template-columns: repeat(2, 1fr);
                }
                
                .gallery-item-overlay {
                    opacity: 1;
                    padding: 10px;
                }
            }
        </style>
    </section>
    <section class="section" style="margin-top: 0; border-radius: 0;">
        <h2 style="margin-bottom: 20px;">Prêt à vivre l'expérience HAP ?</h2>
        <p style="margin-bottom: 30px; font-size: 1.1em;">
            Rejoignez notre communauté de noctambules et réservez votre prochain logement dès maintenant !
        </p>
        <div style="text-align: center;">
            <a href="Projet_HAP(House_After_Party)/auth/inscription.php" class="btn btn-primary" style="padding: 16px 32px; font-size: 1.2em; margin-right: 20px;">S'inscrire maintenant</a>
            <a href="Projet_HAP(House_After_Party)/auth/connexion.php" class="btn" style="padding: 16px 32px; font-size: 1.2em; background: var(--btn-login-bg); color: var(--logo-color); border: 2px solid var(--logo-color);">Se connecter</a>
        </div>
    </section>
    <footer>
        <div style="display: flex; justify-content: center; gap: 40px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="about.php" style="color: #666; text-decoration: none;">À propos</a>
            <a href="Projet_HAP(House_After_Party)/forms/Annonce.form.php" style="color: #666; text-decoration: none;">Annonces</a>
            <a href="Projet_HAP(House_After_Party)/forms/Evenement.form.php" style="color: #666; text-decoration: none;">Événements</a>
            <a href="contact.php" style="color: #666; text-decoration: none;">Contact</a>
        </div>
        &copy; <?= date('Y') ?> House After Party &mdash; Tous droits réservés.<br>
        <small style="color: #999;">Fait avec ❤️ pour les amoureux des nuits blanches</small>
    </footer>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

    <?php include 'theme_toggle.php'; ?>
    <script>
    // Custom lightweight gallery modal to ensure reliable navigation and return-to-gallery behavior
    document.addEventListener('DOMContentLoaded', function() {
        const galleryCard = document.getElementById('gallery-card');
        const anchors = Array.from(document.querySelectorAll('.gallery a'));
        if (!galleryCard) return;

        // Build modal HTML/CSS
        const style = document.createElement('style');
        style.textContent = `
            #hap-modal { position: fixed; inset: 0; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.8); z-index: 9999; }
            #hap-modal.show { display: flex; }
            #hap-modal .modal-inner { position: relative; max-width: 90%; max-height: 90%; }
            #hap-modal img { max-width: 100%; max-height: 80vh; display: block; border-radius: 6px; }
            #hap-modal .caption { color: #fff; margin-top: 8px; text-align: center; }
            #hap-modal .close, #hap-modal .prev, #hap-modal .next { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: #fff; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; }
            #hap-modal .close { top: 12px; right: 12px; transform: none; }
            #hap-modal .prev { left: -50px; }
            #hap-modal .next { right: -50px; }
            @media (max-width: 600px) { #hap-modal .prev { left: 8px; } #hap-modal .next { right: 8px; } }
            #hap-modal .back-to-gallery { position: absolute; bottom: -40px; left: 50%; transform: translateX(-50%); color: #fff; text-decoration: underline; cursor: pointer; }
        `;
        document.head.appendChild(style);

        const modal = document.createElement('div');
        modal.id = 'hap-modal';
        modal.innerHTML = `
            <div class="modal-inner">
                <button class="close" aria-label="Fermer">✕</button>
                <button class="prev" aria-label="Précédent">◀</button>
                <button class="next" aria-label="Suivant">▶</button>
                <img src="" alt="" />
                <div class="caption"></div>
                <div class="back-to-gallery">Retour à la galerie</div>
            </div>
        `;
        document.body.appendChild(modal);

        const imgEl = modal.querySelector('img');
        const captionEl = modal.querySelector('.caption');
        const closeBtn = modal.querySelector('.close');
        const prevBtn = modal.querySelector('.prev');
        const nextBtn = modal.querySelector('.next');
        const backBtn = modal.querySelector('.back-to-gallery');

        let currentIndex = 0;

        function openModal(index) {
            currentIndex = index;
            const a = anchors[currentIndex];
            const href = a ? a.getAttribute('href') : null;
            const title = a ? a.getAttribute('data-title') || a.getAttribute('title') || '' : '';
            if (!href) return;
            imgEl.src = href;
            imgEl.alt = title;
            captionEl.textContent = title;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            // preload neighbors
            preload(currentIndex - 1);
            preload(currentIndex + 1);
        }

        function closeModal() {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            imgEl.src = '';
        }

        function preload(i) {
            if (i < 0 || i >= anchors.length) return;
            const href = anchors[i].getAttribute('href');
            const im = new Image(); im.src = href;
        }

        function showPrev() { currentIndex = (currentIndex - 1 + anchors.length) % anchors.length; openModal(currentIndex); }
        function showNext() { currentIndex = (currentIndex + 1) % anchors.length; openModal(currentIndex); }

        // Wire anchor clicks to open modal instead of navigating away
        anchors.forEach((a, idx) => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                openModal(idx);
            });
        });

        // Controls
        closeBtn.addEventListener('click', closeModal);
        prevBtn.addEventListener('click', showPrev);
        nextBtn.addEventListener('click', showNext);
        backBtn.addEventListener('click', closeModal);

        // Keyboard
        document.addEventListener('keydown', function(e) {
            if (!modal.classList.contains('show')) return;
            if (e.key === 'Escape') closeModal();
            if (e.key === 'ArrowLeft') showPrev();
            if (e.key === 'ArrowRight') showNext();
        });

        // Clicking outside image closes
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        // gallery card behavior: scroll to gallery and open first image
        galleryCard.addEventListener('click', function() {
            const gallerySection = document.querySelector('.gallery-section');
            if (gallerySection) gallerySection.scrollIntoView({ behavior: 'smooth' });
            if (anchors.length) {
                // open first after a short delay to allow scroll to finish
                setTimeout(() => openModal(0), 300);
            }
        });
    });
    </script>
</body>
</html>
