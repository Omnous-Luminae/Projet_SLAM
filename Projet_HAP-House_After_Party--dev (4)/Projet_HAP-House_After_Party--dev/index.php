<?php

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
            <a href="Projet_HAP(House_After_Party)/forms/TypePtsInteret.form.php">🗺️ Carte</a>
            <a href="Projet_HAP(House_After_Party)/forms/PtsInteret.form.php">🎵 Boîtes de nuit</a>
            <a href="Projet_HAP(House_After_Party)/forms/Locataires.form.php">👤 Blog</a>
            <a href="Projet_HAP(House_After_Party)/forms/Saison.form.php">🗓️ Saisons</a>
        </nav>
        <?php
        session_start();
        if (isset($_SESSION['user_name'])) {
            echo '<span class="welcome-msg">Bienvenue, ' . htmlspecialchars($_SESSION['user_name']) . '</span>';
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                echo '<a href="Projet_HAP(House_After_Party)/admin/dashboard.php" class="btn-admin">Admin Dashboard</a>';
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
            Parfait pour vos befores et afters !
        </p>
        <div class="hero-btns">
            <a href="#">Voir les logements</a>
            <a href="Projet_HAP(House_After_Party)/forms/Saison.form.php">Gérer les saisons</a>
        </div>
        <img src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?auto=format&fit=crop&w=800&q=80" alt="Soirée" class="hero-bg">
    </section>
    <section class="section">
        <h2>Pourquoi choisir HAP&nbsp;?</h2>
        <p>
            Nous sélectionnons les meilleurs logements pour que vos soirées soient inoubliables
        </p>
        <div class="cards">
            <div class="card">
                <span class="card-icon">🏙️</span>
                Logements proches des boîtes de nuit
            </div>
            <div class="card">
                <span class="card-icon">🎉</span>
                Ambiance garantie pour vos afters
            </div>
            <div class="card">
                <span class="card-icon">🛎️</span>
                Réservation simple et rapide
            </div>
            <div class="card">
                <span class="card-icon">💬</span>
                Service client réactif
            </div>
        </div>
    </section>
    <section class="gallery-section">
        <h2>Ambiance HAP en images</h2>
        <div class="gallery">
            <a href="Projet_HAP(House_After_Party)/images/image.png" data-lightbox="hap-gallery" data-title="Soirée 1">
                <img src="Projet_HAP(House_After_Party)/images/image.png" alt="Soirée 1">
            </a>
            <!-- Ajoute d'autres images ici si besoin -->
        </div>
    </section>
    <footer>
        &copy; <?= date('Y') ?> House After Party &mdash; Tous droits réservés.
    </footer>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</body>
</html>
