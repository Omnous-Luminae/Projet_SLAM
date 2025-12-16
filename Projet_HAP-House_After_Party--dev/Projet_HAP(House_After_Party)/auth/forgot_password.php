<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
$resetLink = '';
$showResetLink = false;

// Détecter si on est en environnement local
$isLocalEnv = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) 
              || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0
              || strpos($_SERVER['HTTP_HOST'], '127.0.0.1:') === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Email invalide.';
    } else {
        // Vérifier que l'utilisateur existe
        $stmt = $pdo->prepare('SELECT id_locataire FROM Locataire WHERE LOWER(email_locataire) = LOWER(:email)');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            // To avoid user enumeration, show generic message
            $message = 'Si cet email existe dans notre base, vous recevrez un lien pour réinitialiser le mot de passe.';
        } else {
            // Create password_resets table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Generate token and store hash (compatible avec anciennes versions PHP)
            if (function_exists('openssl_random_pseudo_bytes')) {
                $token = bin2hex(openssl_random_pseudo_bytes(16));
            } else {
                // Fallback sans fonctions crypto
                $token = hash('sha256', uniqid('', true) . time() . $_SERVER['REMOTE_ADDR'] . microtime(true));
            }
            $token_hash = hash('sha256', $token);
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $insert = $pdo->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)');
            $insert->execute([$email, $token_hash, $expires_at]);

            // Build reset link
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = $protocol . '://' . $host . '/Projet_HAP(House_After_Party)/auth/reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token);

            // En environnement local, ne pas essayer d'envoyer d'email
            if ($isLocalEnv) {
                $showResetLink = true;
                $message = '🔧 Mode développement local détecté. Utilisez le lien ci-dessous pour réinitialiser votre mot de passe :';
            } else {
                // Try to send email
                $subject = 'Réinitialisation de votre mot de passe';
                $body = "Bonjour,\n\nPour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous (valide 1 heure) :\n\n" . $resetLink . "\n\nSi vous n'avez pas demandé cette réinitialisation, ignorez ce message.";
                $headers = 'From: no-reply@' . $_SERVER['HTTP_HOST'] . "\r\n";

                $sent = false;
                try {
                    $sent = @mail($email, $subject, $body, $headers);
                } catch (Exception $e) {
                    $sent = false;
                }

                if ($sent) {
                    $message = 'Un email vous a été envoyé avec les instructions pour réinitialiser votre mot de passe.';
                } else {
                    // Fallback si l'envoi échoue
                    $showResetLink = true;
                    $message = '⚠️ Impossible d\'envoyer l\'email. Utilisez le lien ci-dessous :';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="../Css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f7f7f9 0%, #f3e6fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .container {
            max-width: 500px;
            width: 100%;
            margin: 20px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(80,0,80,0.1);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #a100b8 0%, #4b006e 100%);
        }
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #a100b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #4b006e;
        }
        .icon {
            font-size: 3em;
            color: #a100b8;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        h2 {
            margin-bottom: 10px;
            color: #a100b8;
            font-size: 2.2em;
            font-weight: 700;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1em;
            line-height: 1.5;
        }
        .message {
            margin-bottom: 25px;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95em;
            line-height: 1.4;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        form {
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1em;
        }
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e1e1e1;
            border-radius: 10px;
            font-size: 1em;
            box-sizing: border-box;
            transition: all 0.3s ease;
            margin-bottom: 25px;
            font-family: 'Montserrat', Arial, sans-serif;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #a100b8;
            box-shadow: 0 0 0 3px rgba(161, 0, 184, 0.1);
            transform: translateY(-1px);
        }
        button {
            width: 100%;
            background: linear-gradient(135deg, #a100b8 0%, #4b006e 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Montserrat', Arial, sans-serif;
            box-shadow: 0 2px 8px rgba(161, 0, 184, 0.3);
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(161, 0, 184, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .help-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 0.9em;
        }
        .help-text a {
            color: #a100b8;
            text-decoration: none;
            font-weight: 600;
        }
        .help-text a:hover {
            text-decoration: underline;
        }
        .reset-link-box {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border: 2px solid #4caf50;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .link-container {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        .link-container input {
            flex: 1;
            padding: 12px;
            border: 2px solid #4caf50;
            border-radius: 8px;
            font-size: 0.85em;
            background: #fff;
            color: #333;
        }
        .btn-copy {
            width: auto !important;
            padding: 12px 16px !important;
            background: #4caf50 !important;
            border-radius: 8px !important;
            font-size: 1.2em !important;
            box-shadow: none !important;
        }
        .btn-copy:hover {
            background: #388e3c !important;
        }
        .btn-reset-direct {
            display: inline-block;
            width: 100%;
            background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
            box-sizing: border-box;
        }
        .btn-reset-direct:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(76, 175, 80, 0.4);
        }
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                padding: 30px 20px;
            }
            h2 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="connexion.php" class="back-link">&larr; Retour à la connexion</a>
        <div class="icon">🔐</div>
        <h2>Mot de passe oublié</h2>
        <p class="subtitle">Entrez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</p>
        <?php if ($message): ?>
            <div class="message<?php echo $showResetLink ? ' success' : (strpos($message, 'succès') !== false || strpos($message, 'envoyé') !== false ? ' success' : ' error'); ?>"><?php echo $message; ?></div>
            
            <?php if ($showResetLink && $resetLink): ?>
            <div class="reset-link-box">
                <p style="margin-bottom: 15px; font-weight: 600; color: #333;">🔗 Lien de réinitialisation :</p>
                <div class="link-container">
                    <input type="text" id="reset-link" value="<?php echo htmlspecialchars($resetLink); ?>" readonly>
                    <button type="button" onclick="copyLink()" class="btn-copy" title="Copier le lien">📋</button>
                </div>
                <a href="<?php echo htmlspecialchars($resetLink); ?>" class="btn-reset-direct">
                    🔓 Réinitialiser mon mot de passe maintenant
                </a>
                <p style="margin-top: 15px; font-size: 0.85em; color: #666;">
                    ⏱️ Ce lien expire dans 1 heure
                </p>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!$showResetLink): ?>
        <form method="post">
            <label for="email">Adresse email</label>
            <input id="email" type="email" name="email" required placeholder="votre.email@exemple.com">
            <button type="submit">Envoyer le lien de réinitialisation</button>
        </form>
        <?php endif; ?>
        <p class="help-text">
            Vous vous souvenez de votre mot de passe ? <a href="connexion.php">Se connecter</a>
        </p>
    </div>
    <script>
        function copyLink() {
            const linkInput = document.getElementById('reset-link');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(linkInput.value).then(function() {
                alert('✅ Lien copié dans le presse-papier !');
            }).catch(function() {
                // Fallback for older browsers
                document.execCommand('copy');
                alert('✅ Lien copié !');
            });
        }
    </script>
</body>
</html>
