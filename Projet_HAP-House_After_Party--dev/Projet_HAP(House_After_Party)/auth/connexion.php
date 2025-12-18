<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_security.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

$message = '';
$messageType = '';
$isLocked = false;
$lockoutRemaining = 0;

// Récupérer l'IP du client
$clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Vérifier si l'IP est bloquée
if ($pdo && isUserLoginBlocked($pdo, $clientIP)) {
    $isLocked = true;
    $lockoutRemaining = getUserLockoutRemaining($pdo, $clientIP);
}

// Messages de redirection
if (isset($_GET['redirect_from']) && $_GET['redirect_from'] === 'reservation') {
    $message = "Vous devez être connecté pour effectuer une réservation.";
    $messageType = 'info';
}
if (isset($_SESSION['redirect_message'])) {
    $message = $_SESSION['redirect_message'];
    $messageType = 'info';
    unset($_SESSION['redirect_message']);
}

// Génération du token CSRF
$csrfToken = generateUserCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Vérification CSRF
    if (!verifyUserCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Session expirée. Veuillez réessayer.";
        $messageType = 'error';
    } elseif ($isLocked) {
        $message = "Trop de tentatives. Réessayez dans " . ceil($lockoutRemaining / 60) . " minutes.";
        $messageType = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email !== '' && $password !== '') {
            if ($pdo) {
                // Respecter l'ordre du constructeur : id, nom, prenom, email, tel, date_naissance, mdp, rue, complement, pdo
                $locataireObj = new Locataire(null, null, null, $email, null, null, null, null, null, $pdo);
                $locataire = $locataireObj->authenticateLocataire($email, $password);
                
                if ($locataire) {
                    // Connexion réussie
                    clearUserLoginAttempts($pdo, $clientIP);
                    recordUserLoginAttempt($pdo, $clientIP, $email, true);
                    
                    // Régénérer l'ID de session
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $locataire['id_locataire'];
                    $_SESSION['user_name'] = $locataire['nom_locataire'];
                    $_SESSION['user_prenom'] = $locataire['prenom_locataire'];
                    $_SESSION['role'] = 'locataire';
                    
                    header('Location: ../../index.php');
                    exit;
                } else {
                    // Échec de connexion
                    recordUserLoginAttempt($pdo, $clientIP, $email, false);
                    $attempts = getUserLoginAttempts($pdo, $clientIP);
                    $remaining = USER_MAX_LOGIN_ATTEMPTS - $attempts;
                    
                    if ($remaining <= 0) {
                        $isLocked = true;
                        $lockoutRemaining = USER_LOGIN_LOCKOUT_TIME;
                        $message = "Compte temporairement bloqué. Réessayez dans 15 minutes.";
                    } else {
                        $message = "Email ou mot de passe incorrect. ($remaining tentative(s) restante(s))";
                    }
                    $messageType = 'error';
                }
            } else {
                $message = "Erreur de connexion à la base de données.";
                $messageType = 'error';
            }
        } else {
            $message = "Veuillez remplir tous les champs.";
            $messageType = 'error';
        }
    }
    
    // Régénérer le token CSRF
    $csrfToken = regenerateUserCsrfToken();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-primary: rgba(255, 255, 255, 0.95);
            --bg-secondary: #fafafa;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e0e0e0;
            --accent: #4f46e5;
            --accent-light: #6366f1;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: var(--bg-primary);
            border-radius: 24px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        
        .back-link:hover { transform: translateX(-5px); }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2em;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.info {
            background: linear-gradient(135deg, #cce5ff, #b8daff);
            color: #004085;
            border-left: 4px solid #007bff;
        }
        
        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .message.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .lockout-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeeba);
            color: #856404;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 25px;
            border-left: 4px solid #ffc107;
        }
        
        .lockout-warning .timer {
            font-size: 2em;
            font-weight: 700;
            color: #dc3545;
            margin: 10px 0;
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
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
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
        
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
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
        }
        
        .auth-links p {
            color: var(--text-secondary);
            margin: 10px 0;
        }
        
        .auth-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-links a:hover { text-decoration: underline; }
        
        .security-note {
            margin-top: 20px;
            padding: 15px;
            background: var(--bg-secondary);
            border-radius: 10px;
            font-size: 0.8em;
            color: var(--text-secondary);
            text-align: center;
        }
        
        .security-note span { color: #28a745; }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="header">
            <div class="icon">🏠</div>
            <h1>Connexion</h1>
            <p>Accédez à votre espace locataire</p>
        </div>
        
        <?php if ($isLocked && $lockoutRemaining > 0): ?>
            <div class="lockout-warning">
                <div>🔒 Compte temporairement bloqué</div>
                <div class="timer" id="countdown"><?= gmdate('i:s', $lockoutRemaining) ?></div>
                <div>Trop de tentatives échouées</div>
            </div>
            <script>
                let remaining = <?= $lockoutRemaining ?>;
                const countdown = document.getElementById('countdown');
                const timer = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        location.reload();
                    } else {
                        const min = Math.floor(remaining / 60).toString().padStart(2, '0');
                        const sec = (remaining % 60).toString().padStart(2, '0');
                        countdown.textContent = min + ':' + sec;
                    }
                }, 1000);
            </script>
        <?php else: ?>
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>"><?= $message ?></div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="form-group">
                    <label for="email">📧 Adresse email</label>
                    <input type="email" id="email" name="email" required autocomplete="new-email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">🔑 Mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn-submit">Se connecter</button>
            </form>
            
            <div class="auth-links">
                <p>Mot de passe oublié ? <a href="forgot_password.php">Réinitialiser</a></p>
                <p>Pas encore de compte ? <a href="inscription.php<?= isset($_GET['redirect_from']) ? '?redirect_from=' . urlencode($_GET['redirect_from']) : '' ?>">S'inscrire</a></p>
            </div>
            
            <div class="security-note">
                <span>🔒</span> Connexion sécurisée • Protection anti brute-force
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const btn = document.querySelector('.toggle-password');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }
    </script>
    
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
