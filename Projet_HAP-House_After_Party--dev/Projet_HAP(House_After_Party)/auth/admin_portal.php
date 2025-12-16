<?php
session_start();
require_once __DIR__ . '/../config/admin_security.php';

$secret_key = defined('ADMIN_SECRET_KEY') ? ADMIN_SECRET_KEY : 'HAP_Admin_2024_Secure';

// Si la clé est fournie dans l'URL, on crée une session d'accès
if (isset($_GET['key']) && $_GET['key'] === $secret_key) {
    $_SESSION['admin_portal_access'] = true;
    $_SESSION['admin_portal_time'] = time();
    // Rediriger pour nettoyer l'URL
    header('Location: admin_portal.php');
    exit;
}

// Vérifier si l'accès est autorisé (session valide pendant 24h)
$hasAccess = isset($_SESSION['admin_portal_access']) 
    && $_SESSION['admin_portal_access'] === true
    && isset($_SESSION['admin_portal_time'])
    && (time() - $_SESSION['admin_portal_time']) < 86400; // 24 heures

// Si déjà connecté en tant qu'admin, accès automatique
if (isset($_SESSION['animateur_id'])) {
    $hasAccess = true;
}

if (!$hasAccess) {
    // Afficher le formulaire de clé d'accès
    $error = isset($_POST['access_key']) && $_POST['access_key'] !== $secret_key;
    
    if (isset($_POST['access_key']) && $_POST['access_key'] === $secret_key) {
        $_SESSION['admin_portal_access'] = true;
        $_SESSION['admin_portal_time'] = time();
        header('Location: admin_portal.php');
        exit;
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Administrateur</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .access-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
        }
        
        .lock-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2.5em;
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.3);
        }
        
        h1 {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 10px;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.95em;
        }
        
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1em;
            font-family: inherit;
            text-align: center;
            letter-spacing: 2px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: #e94560;
            outline: none;
            box-shadow: 0 0 0 4px rgba(233, 69, 96, 0.1);
        }
        
        .btn-access {
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
        
        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
        }
        
        .back-link {
            display: block;
            margin-top: 25px;
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
        }
        
        .back-link:hover {
            color: #e94560;
        }
    </style>
</head>
<body>
    <div class="access-container">
        <div class="lock-icon">🔐</div>
        <h1>Espace Administrateur</h1>
        <p>Entrez la clé d'accès pour continuer</p>
        
        <?php if ($error): ?>
            <div class="error-msg">❌ Clé d'accès incorrecte</div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="password" name="access_key" placeholder="Clé d'accès" required autofocus>
            </div>
            <button type="submit" class="btn-access">Accéder</button>
        </form>
        
        <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
    </div>
</body>
</html>
<?php
    exit;
}

// L'utilisateur a accès - Afficher le portail
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Administrateur - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-primary: rgba(255, 255, 255, 0.95);
            --text-primary: #333;
            --text-secondary: #666;
        }
        
        [data-theme="dark"] {
            --bg-primary: rgba(30, 30, 40, 0.95);
            --text-primary: #f0f0f0;
            --text-secondary: #c0c0c0;
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
        
        .portal-container {
            background: var(--bg-primary);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .portal-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #e94560, #ff6b6b, #e94560);
        }
        
        .logo {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 2.8em;
            box-shadow: 0 15px 40px rgba(233, 69, 96, 0.3);
        }
        
        h1 {
            font-size: 1.8em;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: var(--text-secondary);
            margin-bottom: 40px;
            font-size: 0.95em;
        }
        
        .portal-options {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .portal-btn {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 25px;
            border-radius: 16px;
            text-decoration: none;
            transition: all 0.3s;
            text-align: left;
        }
        
        .portal-btn.login {
            background: linear-gradient(135deg, #e94560, #ff6b6b);
            color: white;
        }
        
        .portal-btn.login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(233, 69, 96, 0.4);
        }
        
        .portal-btn.register {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .portal-btn.register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }
        
        .portal-btn .icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            flex-shrink: 0;
        }
        
        .portal-btn .text h3 {
            font-size: 1.1em;
            margin-bottom: 4px;
        }
        
        .portal-btn .text p {
            font-size: 0.85em;
            opacity: 0.9;
            margin: 0;
        }
        
        .footer-links {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid rgba(128, 128, 128, 0.2);
        }
        
        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9em;
            margin: 0 15px;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #e94560;
        }
        
        .session-info {
            margin-top: 20px;
            font-size: 0.8em;
            color: var(--text-secondary);
        }
        
        .bookmark-tip {
            background: rgba(233, 69, 96, 0.1);
            border: 1px solid rgba(233, 69, 96, 0.3);
            border-radius: 12px;
            padding: 15px;
            margin-top: 25px;
            font-size: 0.85em;
            color: var(--text-secondary);
        }
        
        .bookmark-tip strong {
            color: #e94560;
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="logo">🏠</div>
        <h1>Portail Admin</h1>
        <p class="subtitle">House After Party - Gestion</p>
        
        <div class="portal-options">
            <a href="connexion_admin.php?key=<?= urlencode($secret_key) ?>" class="portal-btn login">
                <div class="icon">🔑</div>
                <div class="text">
                    <h3>Se connecter</h3>
                    <p>Accéder à votre espace admin</p>
                </div>
            </a>
            
            <a href="inscription_admin.php?key=<?= urlencode($secret_key) ?>" class="portal-btn register">
                <div class="icon">📝</div>
                <div class="text">
                    <h3>Créer un compte</h3>
                    <p>Devenir administrateur</p>
                </div>
            </a>
        </div>
        
        <div class="bookmark-tip">
            💡 <strong>Astuce :</strong> Ajoutez cette page en favoris pour y accéder facilement !
        </div>
        
        <div class="footer-links">
            <a href="../../index.php">← Accueil</a>
            <a href="connexion.php">Espace locataire</a>
        </div>
        
        <div class="session-info">
            Session valide pendant 24h
        </div>
    </div>
    
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
