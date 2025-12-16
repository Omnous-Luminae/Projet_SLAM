<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'animateur') {
    header('Location: /auth/connexion.php');
    exit;
}
require_once 'Projet_HAP(House_After_Party)/config/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Administrateur - HAP</title>
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/dashboard.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <h1>Dashboard Administrateur</h1>
        <nav>
            <a href="index.php">🏠 Accueil</a>
            <a href="Projet_HAP(House_After_Party)/auth/logout.php">🚪 Déconnexion</a>
        </nav>
    </header>

    <?php include 'theme_toggle.php'; ?>
    <main>
        <section class="welcome-section">
            <h2>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?> !</h2>
            <p>Gérez efficacement votre plateforme House After Party depuis ce tableau de bord.</p>
            <!-- Quick Form Selector -->
            <div style="margin-top: 20px;">
                <label for="form_selector" style="font-weight: bold; margin-right: 10px;">Aller directement à un formulaire :</label>
                <select id="form_selector" onchange="if(this.value) window.location.href=this.value;" style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="">-- Choisir un formulaire --</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Bien.form.php">Gestion des Biens</option>
                    <option value="Projet_HAP(House_After_Party)/forms/validate_biens.php">🔍 Validation des Biens</option>
                    <option value="Projet_HAP(House_After_Party)/forms/validate_reviews.php">🎭 Validation des Avis</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Locataires.form.php">Gestion des Locataires</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Reservation.form.php">Gestion des Réservations</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Evenement.form.php">Gestion des Événements</option>
                    <option value="Projet_HAP(House_After_Party)/forms/PtsInteret.form.php">Points d'Intérêt</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Tarif.form.php">Gestion des Tarifs</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Annonce.form.php">Gestion des Annonces</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Saison.form.php">Gestion des Saisons</option>
                    <option value="Projet_HAP(House_After_Party)/forms/TypeBien.form.php">Types de Biens</option>
                    <option value="Projet_HAP(House_After_Party)/forms/TypeEvenement.form.php">Types d'Événements</option>
                    <option value="Projet_HAP(House_After_Party)/forms/TypePtsInteret.form.php">Types de Points d'Intérêt</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Commune.form.php">Communes</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Compose.form.php">Compositions</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Dispose.form.php">Dispositions</option>
                    <option value="Projet_HAP(House_After_Party)/forms/Prestation.form.php">Prestations</option>
                    <option value="Projet_HAP(House_After_Party)/forms/manage_archives.php">🔐 Archives Réservations</option>
                </select>
            </div>
        </section>

        <section class="stats-section">
            <h2>Statistiques Rapides</h2>
            <div class="stats-grid">
                <?php
                try {
                    // Nombre total de locataires
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Locataire");
                    $locataires = $stmt->fetch()['total'];

                    // Nombre total de biens
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Biens");
                    $biens = $stmt->fetch()['total'];

                    // Nombre total de réservations
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Reservation");
                    $reservations = $stmt->fetch()['total'];

                    // Nombre total d'événements
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Evenement");
                    $evenements = $stmt->fetch()['total'];

                    echo "<div class='stat-card'><h3>$locataires</h3><p>Locataires</p></div>";
                    echo "<div class='stat-card'><h3>$biens</h3><p>Biens</p></div>";
                    echo "<div class='stat-card'><h3>$reservations</h3><p>Réservations</p></div>";
                    echo "<div class='stat-card'><h3>$evenements</h3><p>Événements</p></div>";
                } catch (Exception $e) {
                    echo "<p>Erreur lors du chargement des statistiques.</p>";
                }
                ?>
            </div>
        </section>

        <section class="management-section">
            <h2>Outils de Gestion</h2>
            <div class="management-grid">
                <div class="management-card">
                    <h3>🏠 Gestion des Biens</h3>
                    <p>Ajoutez, modifiez ou supprimez les biens immobiliers.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Bien.form.php" class="btn">Gérer les Biens</a>
                </div>
                <div class="management-card">
                    <h3>🔍 Validation des Biens</h3>
                    <p>Validez ou refusez les nouveaux biens proposés.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/validate_biens.php" class="btn">Valider les Biens</a>
                </div>
                <div class="management-card">
                    <h3>🎭 Validation des Avis</h3>
                    <p>Modérez et validez les avis des locataires.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/validate_reviews.php" class="btn">Valider les Avis</a>
                </div>
                <div class="management-card">
                    <h3>👥 Gestion des Locataires</h3>
                    <p>Gérez les informations des locataires physiques et moraux.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Locataires.form.php" class="btn">Gérer les Locataires</a>
                </div>
                <div class="management-card">
                    <h3>📅 Gestion des Réservations</h3>
                    <p>Suivez et gérez toutes les réservations.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Reservation.form.php" class="btn">Gérer les Réservations</a>
                </div>
                <div class="management-card">
                    <h3>🎉 Gestion des Événements</h3>
                    <p>Organisez et gérez les événements spéciaux.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Evenement.form.php" class="btn">Gérer les Événements</a>
                </div>
                <div class="management-card">
                    <h3>🎵 Points d'Intérêt</h3>
                    <p>Gérez les boîtes de nuit et lieux festifs.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/PtsInteret.form.php" class="btn">Gérer les Points d'Intérêt</a>
                </div>
                <div class="management-card">
                    <h3>💰 Gestion des Tarifs</h3>
                    <p>Définissez les prix pour les différentes saisons.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Tarif.form.php" class="btn">Gérer les Tarifs</a>
                </div>
                <div class="management-card">
                    <h3>📢 Gestion des Annonces</h3>
                    <p>Créez et publiez des annonces pour les biens.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Annonce.form.php" class="btn">Gérer les Annonces</a>
                </div>
                <div class="management-card">
                    <h3>🗓️ Gestion des Saisons</h3>
                    <p>Définissez les périodes saisonnières.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Saison.form.php" class="btn">Gérer les Saisons</a>
                </div>
                <div class="management-card">
                    <h3>⚙️ Types et Configurations</h3>
                    <p>Gérez les types de biens, événements, etc.</p>
                    <div class="sub-links">
                        <a href="Projet_HAP(House_After_Party)/forms/TypeBien.form.php">Types de Biens</a> |
                        <a href="Projet_HAP(House_After_Party)/forms/TypeEvenement.form.php">Types d'Événements</a> |
                        <a href="Projet_HAP(House_After_Party)/forms/TypePtsInteret.form.php">Types de Points d'Intérêt</a> |
                        <a href="Projet_HAP(House_After_Party)/forms/Commune.form.php">Communes</a>
                    </div>
                </div>
                <div class="management-card">
                    <h3>🔗 Relations et Associations</h3>
                    <p>Gérez les compositions et dispositions des biens.</p>
                    <div class="sub-links">
                        <a href="Projet_HAP(House_After_Party)/forms/Compose.form.php">Compositions</a> |
                        <a href="Projet_HAP(House_After_Party)/forms/Dispose.form.php">Dispositions</a>
                    </div>
                </div>
                <div class="management-card">
                    <h3>🎭 Gestion des Prestations</h3>
                    <p>Configurez les services supplémentaires.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/Prestation.form.php" class="btn">Gérer les Prestations</a>
                </div>
                <div class="management-card">
                    <h3>🔐 Archives des Réservations</h3>
                    <p>Consultez et gérez les réservations archivées et cryptées.</p>
                    <a href="Projet_HAP(House_After_Party)/forms/manage_archives.php" class="btn">Gérer les Archives</a>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
