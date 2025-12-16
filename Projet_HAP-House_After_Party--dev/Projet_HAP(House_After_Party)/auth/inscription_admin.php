<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/admin_security.php';
require_once __DIR__ . '/../classes/Animateur/Animateur.php';

// Contrôle d'accès avec clé secrète depuis config
$secret_key = defined('ADMIN_SECRET_KEY') ? ADMIN_SECRET_KEY : 'admin_access_2023';
if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    http_response_code(404);
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The page you are looking for does not exist.</p></body></html>";
    exit;
}

// Vérifier les IPs autorisées si configurées
if (defined('ADMIN_ALLOWED_IPS') && !empty(ADMIN_ALLOWED_IPS)) {
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($clientIP, ADMIN_ALLOWED_IPS)) {
        http_response_code(403);
        exit('Accès refusé');
    }
}

$message = '';
$messageType = '';
$showForm = true;

// Date limite pour être majeur (18 ans)
$maxDob = date('Y-m-d', strtotime('-18 years'));

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_animateur'])) {
    // Vérifier CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Session expirée. Veuillez réessayer.";
        $messageType = 'error';
    } else {
        $nom_animateur = trim($_POST['nom_animateur'] ?? '');
        $prenom_animateur = trim($_POST['prenom_animateur'] ?? '');
        $email_animateur = trim($_POST['email_animateur'] ?? '');
        $telephone_animateur = trim($_POST['telephone_animateur'] ?? '');
        $date_naissance_animateur = trim($_POST['date_naissance_animateur'] ?? '');
        $password_animateur = trim($_POST['password_animateur'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        $errors = [];

        // Validation du nom et prénom (lettres, espaces, tirets uniquement)
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]{2,50}$/', $nom_animateur)) {
            $errors[] = "Le nom doit contenir uniquement des lettres (2-50 caractères).";
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]{2,50}$/', $prenom_animateur)) {
            $errors[] = "Le prénom doit contenir uniquement des lettres (2-50 caractères).";
        }

        // Age check (must be 18+)
        if (!empty($date_naissance_animateur)) {
            try {
                $birth = new DateTime($date_naissance_animateur);
                $now = new DateTime();
                $age = $now->diff($birth)->y;
                if ($age < 18) {
                    $errors[] = "Vous devez avoir au moins 18 ans pour vous inscrire.";
                }
                if ($age > 120) {
                    $errors[] = "Date de naissance invalide.";
                }
            } catch (Exception $e) {
                $errors[] = "Date de naissance invalide.";
            }
        } else {
            $errors[] = "La date de naissance est obligatoire.";
        }

        // Validation email
        if (!filter_var($email_animateur, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Adresse email invalide.";
        }

        // Validation téléphone (format français)
        $tel_clean = preg_replace('/\s+/', '', $telephone_animateur);
        if (!preg_match('/^(\+33|0)[1-9][0-9]{8}$/', $tel_clean)) {
            $errors[] = "Numéro de téléphone invalide (format: 0612345678 ou +33612345678).";
        }

        // Vérification des mots de passe
        if ($password_animateur !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }

        // Password policy (CNIL-like): min 12, upper, lower, digit, special
        $pw_ok = strlen($password_animateur) >= 12
            && preg_match('/[A-Z]/', $password_animateur)
            && preg_match('/[a-z]/', $password_animateur)
            && preg_match('/[0-9]/', $password_animateur)
            && preg_match('/[\W_]/', $password_animateur);
        if (!$pw_ok) {
            $errors[] = "Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
        }

        // Vérifier si l'email est déjà utilisé
        if ($pdo && empty(array_filter($errors, fn($e) => strpos($e, 'email') !== false))) {
            $stmt = $pdo->prepare("SELECT id_animateur FROM Animateur WHERE LOWER(email_animateur) = LOWER(:email)");
            $stmt->execute(['email' => $email_animateur]);
            if ($stmt->fetch()) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            }
        }

        if (empty($errors)) {
            if ($pdo) {
                $hashed_password = password_hash($password_animateur, PASSWORD_DEFAULT);
                $animateurObj = new Animateur($pdo);
                if ($animateurObj->createAnimateur($nom_animateur, $prenom_animateur, $email_animateur, $telephone_animateur, $date_naissance_animateur, $hashed_password)) {
                    $message = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
                    $messageType = 'success';
                    $showForm = false;
                } else {
                    $message = "Une erreur est survenue lors de l'inscription.";
                    $messageType = 'error';
                }
            } else {
                $message = "Erreur de connexion à la base de données.";
                $messageType = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }
    
    // Régénérer le token CSRF
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Administrateur - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-primary: rgba(255, 255, 255, 0.95);
            --bg-secondary: #fafafa;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e0e0e0;
        }
        
        [data-theme="dark"] {
            --bg-primary: rgba(30, 30, 40, 0.95);
            --bg-secondary: #2a2a3a;
            --text-primary: #f0f0f0;
            --text-secondary: #c0c0c0;
            --border-color: #444;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: var(--bg-primary);
            border-radius: 24px;
            padding: 40px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #e94560, #ff6b6b, #e94560);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            transform: translateX(-5px);
        }
        
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2em;
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.3);
        }
        
        .header h1 {
            font-size: 1.6em;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .header p {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .message.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
            text-align: center;
        }
        
        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 500px) {
            .form-row { grid-template-columns: 1fr; }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1em;
            font-family: inherit;
            transition: all 0.3s;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .form-group input:focus {
            border-color: #e94560;
            outline: none;
            box-shadow: 0 0 0 4px rgba(233, 69, 96, 0.1);
        }
        
        .form-group input.valid {
            border-color: #28a745;
        }
        
        .form-group input.invalid {
            border-color: #dc3545;
        }
        
        .form-group small {
            display: block;
            margin-top: 6px;
            color: var(--text-secondary);
            font-size: 0.8em;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
            color: var(--text-secondary);
        }
        
        .password-strength {
            margin-top: 12px;
            padding: 15px;
            background: var(--bg-secondary);
            border-radius: 10px;
            display: none;
        }
        
        .password-strength.visible {
            display: block;
        }
        
        .strength-bar {
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            margin-bottom: 12px;
            overflow: hidden;
        }
        
        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 3px;
        }
        
        .strength-bar-fill.weak { width: 25%; background: #dc3545; }
        .strength-bar-fill.fair { width: 50%; background: #ffc107; }
        .strength-bar-fill.good { width: 75%; background: #17a2b8; }
        .strength-bar-fill.strong { width: 100%; background: #28a745; }
        
        .strength-text {
            font-weight: 600;
            font-size: 0.85em;
            margin-bottom: 10px;
        }
        
        .requirements {
            font-size: 0.8em;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
            color: var(--text-secondary);
        }
        
        .requirement.met {
            color: #28a745;
        }
        
        .requirement .icon {
            width: 18px;
            text-align: center;
        }
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .auth-links {
            margin-top: 25px;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
            color: var(--text-secondary);
        }
        
        .auth-links a {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .success-box {
            text-align: center;
            padding: 30px 0;
        }
        
        .success-box .icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
        
        .success-box h2 {
            color: var(--text-primary);
            margin-bottom: 15px;
        }
        
        .success-box p {
            color: var(--text-secondary);
            margin-bottom: 25px;
        }
        
        .btn-login {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="admin-badge">🔐 Inscription Administrateur</div>
        
        <?php if ($messageType === 'success' && !$showForm): ?>
            <div class="success-box">
                <div class="icon">🎉</div>
                <h2>Inscription réussie !</h2>
                <p>Votre compte administrateur a été créé avec succès.</p>
                <a href="connexion_admin.php?key=<?= urlencode($secret_key) ?>" class="btn-login">Se connecter</a>
            </div>
        <?php else: ?>
            <div class="header">
                <div class="icon">📝</div>
                <h1>Créer un compte admin</h1>
                <p>Remplissez le formulaire pour devenir administrateur</p>
            </div>

            <?php if ($message && $messageType === 'error'): ?>
                <div class="message error"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom">👤 Nom</label>
                        <input type="text" id="nom" name="nom_animateur" required 
                               value="<?= htmlspecialchars($_POST['nom_animateur'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="prenom">👤 Prénom</label>
                        <input type="text" id="prenom" name="prenom_animateur" required
                               value="<?= htmlspecialchars($_POST['prenom_animateur'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">📧 Adresse email</label>
                    <input type="email" id="email" name="email_animateur" required
                           value="<?= htmlspecialchars($_POST['email_animateur'] ?? '') ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telephone">📱 Téléphone</label>
                        <input type="tel" id="telephone" name="telephone_animateur" required
                               value="<?= htmlspecialchars($_POST['telephone_animateur'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="dob">🎂 Date de naissance</label>
                        <input type="date" id="dob" name="date_naissance_animateur" max="<?= $maxDob ?>" required
                               value="<?= htmlspecialchars($_POST['date_naissance_animateur'] ?? '') ?>">
                        <small>Vous devez avoir au moins 18 ans</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">🔑 Mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password_animateur" autocomplete="new-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">👁️</button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-bar-fill" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                        <div class="requirements">
                            <div class="requirement" id="req-length"><span class="icon">○</span> Au moins 12 caractères</div>
                            <div class="requirement" id="req-upper"><span class="icon">○</span> Une lettre majuscule (A-Z)</div>
                            <div class="requirement" id="req-lower"><span class="icon">○</span> Une lettre minuscule (a-z)</div>
                            <div class="requirement" id="req-digit"><span class="icon">○</span> Un chiffre (0-9)</div>
                            <div class="requirement" id="req-special"><span class="icon">○</span> Un caractère spécial (!@#$%...)</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm">🔑 Confirmer le mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm" name="confirm_password" autocomplete="new-password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm')">👁️</button>
                    </div>
                </div>
                
                <button type="submit" name="register_animateur" class="btn-submit">
                    Créer mon compte administrateur
                </button>
            </form>
            
            <div class="auth-links">
                <p>Déjà un compte ? <a href="connexion_admin.php?key=<?= urlencode($secret_key) ?>">Se connecter</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const btn = input.parentElement.querySelector('.toggle-password');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }

        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthDiv.classList.remove('visible');
                return;
            }
            
            strengthDiv.classList.add('visible');
            
            // Check requirements
            const hasLength = password.length >= 12;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasDigit = /[0-9]/.test(password);
            const hasSpecial = /[\W_]/.test(password);
            
            // Update requirement indicators
            updateReq('req-length', hasLength);
            updateReq('req-upper', hasUpper);
            updateReq('req-lower', hasLower);
            updateReq('req-digit', hasDigit);
            updateReq('req-special', hasSpecial);
            
            // Calculate strength
            const score = [hasLength, hasUpper, hasLower, hasDigit, hasSpecial].filter(Boolean).length;
            
            strengthBar.className = 'strength-bar-fill';
            if (score <= 1) {
                strengthBar.classList.add('weak');
                strengthText.textContent = '❌ Très faible';
                strengthText.style.color = '#dc3545';
            } else if (score <= 2) {
                strengthBar.classList.add('fair');
                strengthText.textContent = '⚠️ Faible';
                strengthText.style.color = '#ffc107';
            } else if (score <= 4) {
                strengthBar.classList.add('good');
                strengthText.textContent = '👍 Correct';
                strengthText.style.color = '#17a2b8';
            } else {
                strengthBar.classList.add('strong');
                strengthText.textContent = '✅ Fort';
                strengthText.style.color = '#28a745';
            }
        });

        function updateReq(id, met) {
            const el = document.getElementById(id);
            if (met) {
                el.classList.add('met');
                el.querySelector('.icon').textContent = '✓';
            } else {
                el.classList.remove('met');
                el.querySelector('.icon').textContent = '○';
            }
        }

        document.getElementById('confirm')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            if (this.value.length > 0) {
                if (this.value === password) {
                    this.classList.add('valid');
                    this.classList.remove('invalid');
                } else {
                    this.classList.add('invalid');
                    this.classList.remove('valid');
                }
            } else {
                this.classList.remove('valid', 'invalid');
            }
        });

        // Format phone number
        document.getElementById('telephone')?.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) value = value.substr(0, 10);
            if (value.length >= 2) {
                value = value.replace(/(\d{2})(?=\d)/g, '$1 ');
            }
            this.value = value.trim();
        });
    </script>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
