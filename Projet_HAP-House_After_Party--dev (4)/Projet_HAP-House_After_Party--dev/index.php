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
            <a href="Projet_HAP(House_After_Party)/forms/Reservation.form.php">Réserver maintenant</a>
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

    <section class="section" style="background: #f0f0f0;">
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
        <h2>Destinations populaires</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Découvrez les quartiers les plus prisés pour vos sorties nocturnes.
        </p>
        <div class="destinations">
            <div class="destination">
                <img src="https://images.unsplash.com/photo-1431274172761-fca41d930114?auto=format&fit=crop&w=400&q=80" alt="Paris Centre">
                <h3>Paris Centre</h3>
                <p>Le cœur battant de la nuit parisienne avec des clubs légendaires.</p>
            </div>
            <div class="destination">
                <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=400&q=80" alt="Montmartre">
                <h3>Montmartre</h3>
                <p>Ambiance bohème et artistique, parfaite pour une soirée romantique.</p>
            </div>
            <div class="destination">
                <img src="https://images.unsplash.com/photo-1509439581779-6298f75bf6e5?auto=format&fit=crop&w=400&q=80" alt="Bastille">
                <h3>Bastille</h3>
                <p>Vibrant et éclectique, avec une scène musicale diversifiée.</p>
            </div>
        </div>
    </section>

    <section class="section" style="background: #f8f9fa;">
        <h2>Témoignages de nos clients</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Découvrez ce que disent nos locataires satisfaits.
        </p>
        <div class="testimonials">
            <div class="testimonial">
                <p>"Logement parfait pour notre soirée ! À deux pas du club, propre et bien équipé. HAP a rendu notre week-end inoubliable."</p>
                <cite>- Marie L., Paris</cite>
            </div>
            <div class="testimonial">
                <p>"Service impeccable et réservation ultra-simple. L'appartement était exactement comme sur les photos. Je recommande !"</p>
                <cite>- Thomas D., Lyon</cite>
            </div>
            <div class="testimonial">
                <p>"Idéal pour les afters. Le quartier est vivant et sécurisant. HAP comprend parfaitement les besoins des noctambules."</p>
                <cite>- Sophie M., Marseille</cite>
            </div>
        </div>
    </section>
    <section class="gallery-section">
        <h2>Ambiance HAP en images</h2>
        <p style="text-align: center; color: #555; margin-bottom: 30px;">
            Découvrez l'ambiance unique de nos logements à travers ces photos authentiques
        </p>
        <div class="gallery">
            <?php
            // Récupérer les photos depuis la base de données
            $sql = "SELECT p.*, b.nom_biens
                FROM Photos p
                JOIN Biens b ON p.id_biens = b.id_biens
                ORDER BY RAND()
                LIMIT 5"; // Limite à 5 photos aléatoires
            $stmt = $pdo->query($sql);
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($photos as $photo) {
                echo '<a href="' . htmlspecialchars($photo['lien_photo']) . '" data-lightbox="hap-gallery" data-title="' . htmlspecialchars($photo['nom_biens']) . '">';
                echo '<img src="' . htmlspecialchars($photo['lien_photo']) . '" alt="' . htmlspecialchars($photo['nom_photos']) . '">';
                echo '</a>';
            }
            ?>
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="Projet_HAP(House_After_Party)/images/utiliser.php" class="btn btn-primary" style="padding: 12px 24px; font-size: 1.1em;">Voir toutes les photos</a>
        </div>
    </section>
    <section class="section" style="background: #f8f9fa; margin-top: 0; border-radius: 0;">
        <h2 style="margin-bottom: 20px;">Prêt à vivre l'expérience HAP ?</h2>
        <p style="margin-bottom: 30px; font-size: 1.1em;">
            Rejoignez notre communauté de noctambules et réservez votre prochain logement dès maintenant !
        </p>
        <div style="text-align: center;">
            <a href="Projet_HAP(House_After_Party)/auth/inscription.php" class="btn btn-primary" style="padding: 16px 32px; font-size: 1.2em; margin-right: 20px;">S'inscrire maintenant</a>
            <a href="Projet_HAP(House_After_Party)/auth/connexion.php" class="btn" style="padding: 16px 32px; font-size: 1.2em; background: #fff; color: #a100b8; border: 2px solid #a100b8;">Se connecter</a>
        </div>
    </section>
    <footer>
        <div style="display: flex; justify-content: center; gap: 40px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="about.php" style="color: #666; text-decoration: none;">À propos</a>
            <a href="Projet_HAP(House_After_Party)/forms/Annonce.form.php" style="color: #666; text-decoration: none;">Annonces</a>
            <a href="Projet_HAP(House_After_Party)/forms/Evenement.form.php" style="color: #666; text-decoration: none;">Événements</a>
            <a href="#" style="color: #666; text-decoration: none;">Contact</a>
        </div>
        &copy; <?= date('Y') ?> House After Party &mdash; Tous droits réservés.<br>
        <small style="color: #999;">Fait avec ❤️ pour les amoureux des nuits blanches</small>
    </footer>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
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
