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

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_animateur'])) {
    $nom_animateur = trim($_POST['nom_animateur'] ?? '');
    $prenom_animateur = trim($_POST['prenom_animateur'] ?? '');
    $email_animateur = trim($_POST['email_animateur'] ?? '');
    $password_animateur = trim($_POST['password_animateur'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    $required_fields = [$nom_animateur, $prenom_animateur, $email_animateur, $password_animateur, $confirm_password];

    if (in_array('', $required_fields)) {
        $message = "Veuillez remplir tous les champs obligatoires.";
    } elseif ($password_animateur !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (!filter_var($email_animateur, FILTER_VALIDATE_EMAIL)) {
        $message = "Email invalide.";
    } else {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $hashed_password = password_hash($password_animateur, PASSWORD_DEFAULT);
            $animateurObj = new Animateur($pdo);
            if ($animateurObj->createAnimateur($nom_animateur, $prenom_animateur, $email_animateur, $hashed_password)) {
                $message = "Inscription animateur réussie. Vous pouvez maintenant vous connecter.";
            } else {
                $message = "Erreur lors de l'inscription.";
            }
        } else {
            $message = "Erreur de connexion à la base de données.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Administrateur</title>
    <link rel="stylesheet" href="../Css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Inscription Administrateur</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'réussie') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" class="form-control" name="nom_administrateur" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" class="form-control" name="prenom_administrateur" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email_administrateur" required>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" class="form-control" name="password_administrateur" required>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <button type="submit" name="register_admin" class="btn btn-primary">S'inscrire</button>
        </form>
        <div class="auth-link">
            <p>Déjà un compte ? <a href="connexion.php">Connectez-vous ici</a>.</p>
        </div>
    </div>
</body>
</html>
