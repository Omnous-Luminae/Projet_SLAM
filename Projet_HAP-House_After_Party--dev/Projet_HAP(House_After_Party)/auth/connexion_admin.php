<?php
// Contrôle d'accès discret : vérifier la clé secrète
$secret_key = 'admin_access_2023'; // Clé secrète à changer régulièrement
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The page you are looking for does not exist.</p></body></html>";
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Animateur/Animateur.php';

session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email !== '' && $password !== '') {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $animateurObj = new Animateur($pdo);
            $animateur = $animateurObj->authenticateAnimateur($email, $password);
            if ($animateur) {
                $_SESSION['user_id'] = $animateur['id_animateur'];
                $_SESSION['user_name'] = $animateur['nom_animateur'];
                $_SESSION['role'] = 'animateur';
                $_SESSION['is_admin'] = true;
                
                // Rediriger vers la page précédente si elle existe, sinon vers l'accueil
                $redirect_to = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../../index.php';
                unset($_SESSION['redirect_after_login']); // Nettoyer la session
                header('Location: ' . $redirect_to);
                exit;
            } else {
                $message = "Email ou mot de passe incorrect.";
            }
        } else {
            $message = "Erreur de connexion à la base de données.";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Administrateur</title>
    <link rel="stylesheet" href="../Css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Connexion Administrateur</h2>
        <?php if ($message): ?>
            <div class="message error">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="post" action="" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary connexion-btn" name="login_admin" value="Se connecter">Se connecter</button>
        </form>
            <div class="auth-link">
                <p>Pas encore de compte ? <a href="inscription_admin.php?key=<?= $secret_key ?>">Inscrivez-vous ici</a>.</p>
            </div>
        </div>
        <script>
            // Stocke le fait que l'utilisateur s'est connecté en tant qu'admin dans la session
            window.addEventListener('load', function() {
                if (document.querySelector('form').classList.contains('auth-form')) {
                    sessionStorage.setItem('isAdmin', 'true');
                }
            });
        </script>
    <?php include '../../theme_toggle.php'; ?>
    </body>
</html>
