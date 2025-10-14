<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'animateur') {
    header('Location: /auth/connexion.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard Animateur</title>
    <link rel="stylesheet" href="Css/dashboard.css" />
</head>
<body>
    <header>
        <h1>Dashboard Animateur</h1>
        <nav>
            <a href="index.php">Accueil</a>
            <a href="auth/logout.php">Déconnexion</a>
        </nav>
    </header>
    <main>
        <section>
            <h2>Formulaires de gestion</h2>
            <div class="forms-container">
                <?php
                // Include all form files here
                $forms = [
                    'forms/Compose.form.php',
                    'forms/Dispose.form.php',
                    'forms/Evenement.form.php',
                    'forms/Locataires.form.php',
                    'forms/Annonce.form.php',
                    'forms/Prestation.form.php',
                    'forms/PtsInteret.form.php',
                    'forms/Reservation.form.php',
                    'forms/Saison.form.php',
                    'forms/Tarif.form.php',
                    'forms/TypeBien.form.php',
                    'forms/TypeEvenement.form.php',
                    'forms/TypePtsInteret.form.php',
                    'forms/Commune.form.php',
                    'forms/Bien.form.php'
                ];
                foreach ($forms as $form) {
                    if (file_exists($form)) {
                        include $form;
                    } else {
                        echo "<p>Formulaire manquant : $form</p>";
                    }
                }
                ?>
            </div>
        </section>
    </main>
</body>
</html>
