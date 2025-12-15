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
        $errors[] = "❌ Mot de passe trop faible. Il doit contenir au minimum :<br>" .
                    "&nbsp;&nbsp;• 8 caractères<br>" .
                    "&nbsp;&nbsp;• 1 majuscule (A-Z)<br>" .
                    "&nbsp;&nbsp;• 1 minuscule (a-z)<br>" .
                    "&nbsp;&nbsp;• 1 chiffre (0-9)<br>" .
                    "&nbsp;&nbsp;• 1 caractère spécial (@, #, !, etc.)";
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Validation en temps réel du mot de passe
            $('#password_animateur').on('input', function() {
                const password = $(this).val();
                const $strength = $('#password-strength');
                
                if (password.length === 0) {
                    $strength.hide();
                    return;
                }
                
                $strength.show();
                
                // Vérification de chaque critère
                const hasLength = password.length >= 8;
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasDigit = /[0-9]/.test(password);
                const hasSpecial = /[\W_]/.test(password);
                
                // Mise à jour des indicateurs visuels
                updateRequirement('req-length', hasLength);
                updateRequirement('req-upper', hasUpper);
                updateRequirement('req-lower', hasLower);
                updateRequirement('req-digit', hasDigit);
                updateRequirement('req-special', hasSpecial);
                
                // Calcul de la force du mot de passe
                const score = [hasLength, hasUpper, hasLower, hasDigit, hasSpecial].filter(Boolean).length;
                const $strengthText = $('#strength-text');
                
                if (score === 5) {
                    $strengthText.text('✓ Mot de passe fort').css('color', '#28a745');
                } else if (score >= 3) {
                    $strengthText.text('⚠ Mot de passe moyen').css('color', '#ffc107');
                } else {
                    $strengthText.text('✗ Mot de passe faible').css('color', '#dc3545');
                }
            });
            
            function updateRequirement(id, isValid) {
                const $elem = $('#' + id);
                if (isValid) {
                    $elem.html($elem.text().replace('⚪', '✅').replace(/^\u26aa/, '✅'));
                    $elem.css('color', '#28a745');
                } else {
                    $elem.html($elem.text().replace('✅', '⚪').replace(/^\u2705/, '⚪'));
                    $elem.css('color', '#666');
                }
            }

            // Vérification que les mots de passe correspondent
            $('#confirm_password').on('input', function() {
                const password = $('#password_animateur').val();
                const confirm = $(this).val();
                
                if (confirm.length > 0) {
                    if (password === confirm) {
                        $(this).css('border-color', '#28a745');
                    } else {
                        $(this).css('border-color', '#dc3545');
                    }
                } else {
                    $(this).css('border-color', '');
                }
            });
        });
    </script>
</head>
<body>
    <div class="auth-container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Inscription Administrateur</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'réussie') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
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
                <input id="password_animateur" type="password" class="form-control" name="password_animateur" autocomplete="new-password" required>
                <div id="password-strength" style="margin-top:8px;display:none;">
                    <div style="font-size:0.9em;margin-bottom:6px;">
                        <span id="strength-text" style="font-weight:600;"></span>
                    </div>
                    <div style="font-size:0.85em;color:#666;">
                        <div id="req-length" style="margin:3px 0;">⚪ Au moins 8 caractères</div>
                        <div id="req-upper" style="margin:3px 0;">⚪ Une majuscule (A-Z)</div>
                        <div id="req-lower" style="margin:3px 0;">⚪ Une minuscule (a-z)</div>
                        <div id="req-digit" style="margin:3px 0;">⚪ Un chiffre (0-9)</div>
                        <div id="req-special" style="margin:3px 0;">⚪ Un caractère spécial (@#!$%...)</div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input id="confirm_password" type="password" class="form-control" name="confirm_password" autocomplete="new-password" required>
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
