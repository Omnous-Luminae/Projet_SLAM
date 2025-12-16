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
$isLocked = false;
$remainingTime = 0;

// Protection anti-brute force
function getLoginAttempts($pdo, $ip) {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) as cnt, MAX(attempt_time) as last_attempt 
                               FROM admin_login_attempts 
                               WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)');
        $stmt->execute([$ip, LOGIN_LOCKOUT_TIME]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return ['cnt' => 0, 'last_attempt' => null];
    }
}

function recordLoginAttempt($pdo, $ip, $email, $success) {
    try {
        // Créer la table si elle n'existe pas
        $pdo->exec('CREATE TABLE IF NOT EXISTS admin_login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45),
            email VARCHAR(255),
            success TINYINT(1) DEFAULT 0,
            attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_time (ip_address, attempt_time)
        )');
        
        $stmt = $pdo->prepare('INSERT INTO admin_login_attempts (ip_address, email, success) VALUES (?, ?, ?)');
        $stmt->execute([$ip, $email, $success ? 1 : 0]);
        
        // Nettoyer les anciennes tentatives (plus de 24h)
        $pdo->exec('DELETE FROM admin_login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    } catch (Exception $e) {
        // Silently fail
    }
}

function clearLoginAttempts($pdo, $ip) {
    try {
        $stmt = $pdo->prepare('DELETE FROM admin_login_attempts WHERE ip_address = ?');
        $stmt->execute([$ip]);
    } catch (Exception $e) {
        // Silently fail
    }
}

$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Vérifier si l'IP est bloquée
$attempts = getLoginAttempts($pdo, $clientIP);
$maxAttempts = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
$lockoutTime = defined('LOGIN_LOCKOUT_TIME') ? LOGIN_LOCKOUT_TIME : 900;

if ($attempts['cnt'] >= $maxAttempts) {
    $isLocked = true;
    $lastAttempt = strtotime($attempts['last_attempt']);
    $unlockTime = $lastAttempt + $lockoutTime;
    $remainingTime = max(0, $unlockTime - time());
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_admin'])) {
    // Vérifier CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Session expirée. Veuillez réessayer.";
        $messageType = 'error';
    } elseif ($isLocked) {
        $message = "Trop de tentatives. Réessayez dans " . ceil($remainingTime / 60) . " minutes.";
        $messageType = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email !== '' && $password !== '') {
            if ($pdo) {
                $animateurObj = new Animateur($pdo);
                $animateur = $animateurObj->authenticateAnimateur($email, $password);
                
                if ($animateur) {
                    // Succès - effacer les tentatives
                    clearLoginAttempts($pdo, $clientIP);
                    recordLoginAttempt($pdo, $clientIP, $email, true);
                    
                    // Régénérer l'ID de session pour prévenir la fixation de session
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $animateur['id_animateur'];
                    $_SESSION['user_name'] = $animateur['prenom_animateur'] . ' ' . $animateur['nom_animateur'];
                    $_SESSION['user_email'] = $animateur['email_animateur'];
                    $_SESSION['role'] = 'animateur';
                    $_SESSION['is_admin'] = true;
                    $_SESSION['login_time'] = time();
                    
                    // Redirection
                    $redirect_to = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '../../apropos.php';
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirect_to);
                    exit;
                } else {
                    // Échec - enregistrer la tentative
                    recordLoginAttempt($pdo, $clientIP, $email, false);
                    
                    // Recompter les tentatives
                    $attempts = getLoginAttempts($pdo, $clientIP);
                    $attemptsLeft = max(0, $maxAttempts - $attempts['cnt']);
                    
                    if ($attemptsLeft > 0) {
                        $message = "Email ou mot de passe incorrect. $attemptsLeft tentative(s) restante(s).";
                    } else {
                        $message = "Compte temporairement bloqué. Réessayez dans 15 minutes.";
                        $isLocked = true;
                        $remainingTime = $lockoutTime;
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
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - House After Party</title>
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
        
        .login-container {
            background: var(--bg-primary);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
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
            background: linear-gradient(90deg, #e94560, #ff6b6b, #e94560);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
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
            margin-bottom: 25px;
        }
        
        .icon-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .icon-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5em;
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.3);
        }
        
        .icon-header h1 {
            font-size: 1.8em;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .icon-header p {
            color: var(--text-secondary);
            font-size: 0.95em;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
        }
        
        .message.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .locked-message {
            background: linear-gradient(135deg, #fff3cd, #ffeeba);
            color: #856404;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 25px;
        }
        
        .locked-message .timer {
            font-size: 2em;
            font-weight: 700;
            color: #e94560;
            margin: 10px 0;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.95em;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
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
        
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2em;
            color: var(--text-secondary);
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
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
        }
        
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .security-note {
            margin-top: 25px;
            padding: 15px;
            background: var(--bg-secondary);
            border-radius: 10px;
            font-size: 0.85em;
            color: var(--text-secondary);
            text-align: center;
        }
        
        .security-note strong {
            color: #e94560;
        }
        
        .auth-links {
            margin-top: 25px;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
        }
        
        .auth-links a {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 500px) {
            .login-container {
                padding: 30px 25px;
            }
            
            .icon-header h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="admin-badge">🔐 Espace Administrateur</div>
        
        <div class="icon-header">
            <div class="icon">👤</div>
            <h1>Connexion Admin</h1>
            <p>Accédez à votre espace de gestion sécurisé</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($isLocked && $remainingTime > 0): ?>
            <div class="locked-message">
                <div>🔒 Compte temporairement bloqué</div>
                <div class="timer" id="countdown"><?= gmdate('i:s', $remainingTime) ?></div>
                <div>Trop de tentatives échouées</div>
            </div>
            <script>
                let remaining = <?= $remainingTime ?>;
                const countdown = document.getElementById('countdown');
                const timer = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        location.reload();
                    } else {
                        const min = Math.floor(remaining / 60);
                        const sec = remaining % 60;
                        countdown.textContent = min.toString().padStart(2, '0') + ':' + sec.toString().padStart(2, '0');
                    }
                }, 1000);
            </script>
        <?php else: ?>
            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="email">📧 Adresse email</label>
                    <input type="email" id="email" name="email" required autofocus autocomplete="new-email">
                </div>

                <div class="form-group">
                    <label for="password">🔑 Mot de passe</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>
                </div>

                <button type="submit" name="login_admin" class="btn-submit">
                    Se connecter
                </button>
            </form>
        <?php endif; ?>

        <div class="security-note">
            🛡️ <strong>Connexion sécurisée</strong> : Cette page est protégée contre les attaques par force brute. 
            Après <?= $maxAttempts ?> tentatives échouées, l'accès sera temporairement bloqué.
        </div>

        <div class="auth-links">
            <p>Pas encore de compte ? <a href="inscription_admin.php?key=<?= urlencode($secret_key) ?>">Créer un compte admin</a></p>
        </div>
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
