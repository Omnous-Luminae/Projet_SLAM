<?php
session_start();
require_once __DIR__ . '/Projet_HAP(House_After_Party)/config/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>À propos - House After Party</title>
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
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
            <a href="Projet_HAP(House_After_Party)/forms/blog.php"> Blog</a>
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
        <h1>À propos de House After Party</h1>
        <h2>La référence pour vos nuits inoubliables</h2>
        <p>
            Depuis notre création, HAP révolutionne l'expérience nocturne en offrant des logements d'exception<br>
            à proximité des meilleures boîtes de nuit. Découvrez notre histoire et notre vision.
        </p>
        <img src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80" alt="Soirée" class="hero-bg">
    </section>

    <section class="section">
        <h2>Notre histoire</h2>
        <p>
            Fondée en 2020, House After Party est née d'une passion pour la vie nocturne et d'une frustration commune :<br>
            trouver un logement de qualité près des lieux de fête les plus prisés. Notre fondateur, un passionné de musique<br>
            électronique et de soirées mémorables, a décidé de créer la plateforme ultime pour les noctambules.
        </p>
        <div class="cards">
            <div class="card">
                <span class="card-icon">🚀</span>
                <strong>2020</strong><br>
                Lancement de HAP avec 50 logements partenaires
            </div>
            <div class="card">
                <span class="card-icon">📈</span>
                <strong>2021</strong><br>
                Expansion à 200 biens dans 5 villes majeures
            </div>
            <div class="card">
                <span class="card-icon">🌟</span>
                <strong>2022</strong><br>
                Plus de 10 000 réservations et 4.8/5 de satisfaction
            </div>
            <div class="card">
                <span class="card-icon">🎯</span>
                <strong>2023</strong><br>
                Leader du marché avec 500+ biens premium
            </div>
        </div>
    </section>

    <section class="section" style="background: #f0f0f0;">
        <h2>Notre mission</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Chez HAP, nous croyons que chaque nuit devrait être exceptionnelle. Notre mission est de connecter<br>
            les amoureux de la fête avec les meilleurs logements pour des expériences nocturnes inoubliables.
        </p>
        <div class="steps">
            <div class="step">
                <span class="step-number">🎵</span>
                <h3>Qualité</h3>
                <p>Sélection rigoureuse de logements haut de gamme près des clubs les plus populaires.</p>
            </div>
            <div class="step">
                <span class="step-number">⚡</span>
                <h3>Rapidité</h3>
                <p>Réservation instantanée et processus simplifié pour ne pas perdre de temps.</p>
            </div>
            <div class="step">
                <span class="step-number">🤝</span>
                <h3>Confiance</h3>
                <p>Équipe dédiée et support 24/7 pour une tranquillité d'esprit totale.</p>
            </div>
        </div>
    </section>

    <section class="section">
        <h2>Nos valeurs</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Les principes qui guident chaque décision chez HAP.
        </p>
        <div class="cards">
            <div class="card">
                <span class="card-icon">🎉</span>
                <strong>Passion</strong><br>
                Nous vivons pour la musique et les soirées extraordinaires
            </div>
            <div class="card">
                <span class="card-icon">🔒</span>
                <strong>Sécurité</strong><br>
                Votre sécurité et celle de vos biens sont notre priorité absolue
            </div>
            <div class="card">
                <span class="card-icon">🌍</span>
                <strong>Communauté</strong><br>
                Nous construisons une communauté de noctambules partageant les mêmes valeurs
            </div>
            <div class="card">
                <span class="card-icon">💡</span>
                <strong>Innovation</strong><br>
                Nous repoussons constamment les limites pour améliorer votre expérience
            </div>
        </div>
    </section>

    <section class="section" style="background: #f8f9fa;">
        <h2>Notre équipe</h2>
        <p style="text-align: center; margin-bottom: 40px;">
            Rencontrez les passionnés derrière HAP.
        </p>
        <div class="testimonials">
            <div class="testimonial">
                <p>"Fondateur et CEO de HAP, je suis un DJ passionné qui a créé cette plateforme pour vivre des nuits magiques sans compromis."</p>
                <cite>- Alexandre D., Fondateur</cite>
            </div>
            <div class="testimonial">
                <p>"En tant que responsable des partenariats, je m'assure que chaque logement respecte nos standards d'excellence."</p>
                <cite>- Marie L., Partenariats</cite>
            </div>
            <div class="testimonial">
                <p>"Notre équipe support veille à ce que votre expérience soit parfaite, de la réservation à l'after."</p>
                <cite>- Thomas M., Support Client</cite>
            </div>
        </div>
    </section>

    <section class="section" style="background: #f8f9fa; margin-top: 0; border-radius: 0;">
        <h2 style="margin-bottom: 20px;">Rejoignez l'aventure HAP</h2>
        <p style="margin-bottom: 30px; font-size: 1.1em;">
            Prêt à vivre des nuits inoubliables ? Créez votre compte et réservez dès maintenant !
        </p>
        <div style="text-align: center;">
            <a href="Projet_HAP(House_After_Party)/auth/inscription.php" class="btn btn-primary" style="padding: 16px 32px; font-size: 1.2em; margin-right: 20px;">S'inscrire</a>
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
</body>
</html>
