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
// Date limite pour être majeur (18 ans)
$maxDob = date('Y-m-d', strtotime('-18 years'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_animateur'])) {
    $nom_animateur = trim($_POST['nom_animateur'] ?? '');
    $prenom_animateur = trim($_POST['prenom_animateur'] ?? '');
    $email_animateur = trim($_POST['email_animateur'] ?? '');
    $telephone_animateur = trim($_POST['telephone_animateur'] ?? '');
    $date_naissance_animateur = trim($_POST['date_naissance_animateur'] ?? '');
    $password_animateur = trim($_POST['password_animateur'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    $errors = [];

    // Age check (must be 18+)
    if (!empty($date_naissance_animateur)) {
        try {
            $birth = new DateTime($date_naissance_animateur);
            $now = new DateTime();
            $age = $now->diff($birth)->y;
            if ($age < 18) {
                $errors[] = "Vous devez être majeur pour vous inscrire (18+).";
            }
        } catch (Exception $e) {
            $errors[] = "Date de naissance invalide.";
        }
    }

    $required_fields = [$nom_animateur, $prenom_animateur, $email_animateur, $telephone_animateur, $date_naissance_animateur, $password_animateur, $confirm_password];

    if (in_array('', $required_fields)) {
        $errors[] = "Veuillez remplir tous les champs obligatoires.";
    }

    if ($password_animateur !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!filter_var($email_animateur, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    }

    // Password policy (CNIL-like): min 8, upper, lower, digit, special
    $pw_ok = preg_match('/.{8,}/', $password_animateur)
        && preg_match('/[A-Z]/', $password_animateur)
        && preg_match('/[a-z]/', $password_animateur)
        && preg_match('/[0-9]/', $password_animateur)
        && preg_match('/[\W_]/', $password_animateur);
    if (!$pw_ok) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    // Vérifier si l'email est déjà utilisé
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id_animateur FROM Animateur WHERE LOWER(email_animateur) = LOWER(:email)");
        $stmt->execute(['email' => $email_animateur]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }

    if (empty($errors)) {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $hashed_password = password_hash($password_animateur, PASSWORD_DEFAULT);
            $animateurObj = new Animateur($pdo);
            if ($animateurObj->createAnimateur($nom_animateur, $prenom_animateur, $email_animateur, $telephone_animateur, $date_naissance_animateur, $hashed_password)) {
                $message = "Inscription animateur réussie. Vous pouvez maintenant vous connecter.";
            } else {
                $message = "Erreur lors de l'inscription.";
            }
        } else {
            $message = "Erreur de connexion à la base de données.";
        }
    } else {
        $message = implode('<br>', $errors);
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
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Inscription Administrateur</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'réussie') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" class="form-control" name="nom_animateur" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" class="form-control" name="prenom_animateur" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email_animateur" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" class="form-control" name="telephone_animateur" required>
            </div>
            <div class="form-group">
                <label>Date de Naissance</label>
                <input type="date" class="form-control" name="date_naissance_animateur" max="<?php echo $maxDob; ?>" required>
                <small>Vous devez avoir au moins 18 ans pour vous inscrire.</small>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" class="form-control" name="password_animateur" required>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <button type="submit" name="register_animateur" class="btn btn-primary">S'inscrire</button>
        </form>
        <div class="auth-link">
            <p>Déjà un compte ? <a href="connexion_admin.php?key=admin_access_2023">Connectez-vous ici</a>.</p>
        </div>
    </div>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
