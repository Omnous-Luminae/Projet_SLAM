<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

session_start();

$message = '';
if (isset($_GET['redirect_from']) && $_GET['redirect_from'] === 'reservation') {
    $message = "Vous devez être connecté pour effectuer une réservation.";
}
if (isset($_SESSION['redirect_message'])) {
    $message = $_SESSION['redirect_message'];
}
if ($message && !isset($_SESSION['redirect_message'])) {
    $_SESSION['redirect_message'] = $message;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email !== '' && $password !== '') {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $locataireObj = new Locataire(null, null, null, $email, null, null, null, null, null, $pdo);
            $locataire = $locataireObj->authenticateLocataire($email, $password);
            if ($locataire) {
                $_SESSION['user_id'] = $locataire['id_locataire'];
                $_SESSION['user_name'] = $locataire['nom_locataire'];
                $_SESSION['user_prenom'] = $locataire['prenom_locataire'];
                $_SESSION['role'] = 'locataire';
                header('Location: ../../index.php');
                exit;
            } else {
                // Debug: check if email exists
                $stmt = $pdo->prepare("SELECT * FROM Locataire WHERE LOWER(email_locataire) = LOWER(:email)");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    $message = "Email non trouvé dans la base de données.";
                } else {
                    $message = "Mot de passe incorrect. Hash en DB: " . $user['password_locataire'] . " (longueur: " . strlen($user['password_locataire']) . ")";
                }
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
    <title>Connexion</title>
    <link rel="stylesheet" href="../Css/style.css">
</head>
<body>
    <div class="auth-container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Connexion</h2>
        <?php if ($message): ?>
            <div class="message info">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="post" action="" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary connexion-btn" name="login" value="Se connecter">Se connecter</button>
        </form>
        <div class="auth-link">
            <p>Mot de passe oublié ? <a href="forgot_password.php">Réinitialiser ici</a>.</p>
            <p>Pas encore de compte ? <a href="inscription.php<?php echo isset($_GET['redirect_from']) ? '?redirect_from=' . urlencode($_GET['redirect_from']) : ''; ?>">Inscrivez-vous ici</a>.</p>
        </div>
    </div>

    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
