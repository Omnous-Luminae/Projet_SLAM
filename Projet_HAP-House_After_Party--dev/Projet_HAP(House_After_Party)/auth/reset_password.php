<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
$messageType = '';
$valid = false;
$email = $_GET['email'] ?? ($_POST['email'] ?? '');
$token = $_GET['token'] ?? ($_POST['token'] ?? '');

if ($email && $token) {
    // Lookup latest token for this email
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE email = :email ORDER BY id DESC LIMIT 1');
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if (new DateTime($row['expires_at']) < new DateTime()) {
            $message = 'Le lien a expiré. Veuillez demander un nouveau lien de réinitialisation.';
            $messageType = 'error';
        } elseif (hash('sha256', $token) !== $row['token_hash']) {
            $message = 'Token invalide. Vérifiez que vous utilisez le bon lien.';
            $messageType = 'error';
        } else {
            $valid = true;
        }
    } else {
        $message = 'Aucune demande de réinitialisation trouvée pour cet email.';
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    // Validation du mot de passe
    if ($password === '') {
        $message = 'Le mot de passe ne peut pas être vide.';
        $messageType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Le mot de passe doit contenir au moins 8 caractères.';
        $messageType = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Les mots de passe ne correspondent pas.';
        $messageType = 'error';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE email = :email ORDER BY id DESC LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || hash('sha256', $token) !== $row['token_hash'] || new DateTime($row['expires_at']) < new DateTime()) {
            $message = 'Le token est invalide ou expiré.';
            $messageType = 'error';
        } else {
            // Update user password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE Locataire SET password_locataire = :hash WHERE LOWER(email_locataire) = LOWER(:email)');
            $update->execute(['hash' => $hashed, 'email' => $email]);

            // Delete used tokens
            $del = $pdo->prepare('DELETE FROM password_resets WHERE email = :email');
            $del->execute(['email' => $email]);

            $message = '✅ Mot de passe réinitialisé avec succès !';
            $messageType = 'success';
            $valid = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .reset-container {
            background: var(--bg-primary);
            border-radius: 24px;
            padding: 50px 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #764ba2;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            transition: all 0.3s;
        }

        [data-theme="dark"] .back-link {
            color: #a78bfa;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        .icon-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .icon-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5em;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1em;
            font-family: inherit;
            transition: all 0.3s;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .form-group input:focus {
            border-color: #764ba2;
            outline: none;
            box-shadow: 0 0 0 4px rgba(118, 75, 162, 0.1);
        }

        .password-requirements {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 0.85em;
        }

        .password-requirements h4 {
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements ul {
            list-style: none;
            color: var(--text-secondary);
        }

        .password-requirements li {
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .password-requirements li::before {
            content: "•";
            color: #764ba2;
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
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .invalid-token {
            text-align: center;
            padding: 30px 0;
        }

        .invalid-token .icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        .invalid-token h3 {
            color: var(--text-primary);
            margin-bottom: 15px;
        }

        .invalid-token p {
            color: var(--text-secondary);
            margin-bottom: 25px;
        }

        .btn-link {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .success-box {
            text-align: center;
            padding: 20px 0;
        }

        .success-box .icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .security-note {
            margin-top: 25px;
            padding: 15px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 10px;
            font-size: 0.85em;
            color: var(--text-secondary);
            text-align: center;
        }

        .security-note strong {
            color: #764ba2;
        }

        [data-theme="dark"] .security-note strong {
            color: #a78bfa;
        }

        @media (max-width: 500px) {
            .reset-container {
                padding: 30px 25px;
            }

            .icon-header h1 {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="icon-header">
            <div class="icon">🔐</div>
            <h1>Nouveau mot de passe</h1>
            <p>Créez un mot de passe sécurisé pour votre compte</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($valid): ?>
            <form method="post">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="password">🔑 Nouveau mot de passe</label>
                    <input id="password" type="password" name="password" placeholder="Entrez votre nouveau mot de passe" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm">🔑 Confirmer le mot de passe</label>
                    <input id="confirm" type="password" name="confirm" placeholder="Confirmez votre mot de passe" required>
                </div>

                <div class="password-requirements">
                    <h4>🛡️ Exigences de sécurité</h4>
                    <ul>
                        <li>Au moins 8 caractères</li>
                        <li>Mélangez lettres et chiffres</li>
                        <li>Évitez les mots courants</li>
                    </ul>
                </div>

                <button type="submit" name="reset_password" class="btn-submit">
                    Réinitialiser mon mot de passe
                </button>

                <div class="security-note">
                    🔒 <strong>Sécurisé</strong> : Ce lien est unique et expire après 1 heure.
                </div>
            </form>
        <?php elseif ($messageType === 'success'): ?>
            <div class="success-box">
                <div class="icon">🎉</div>
                <p>Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.</p>
                <a href="connexion.php" class="btn-link">Se connecter</a>
            </div>
        <?php else: ?>
            <div class="invalid-token">
                <div class="icon">⚠️</div>
                <h3>Lien invalide ou expiré</h3>
                <p>Ce lien de réinitialisation n'est plus valide. Veuillez demander un nouveau lien.</p>
                <a href="forgot_password.php" class="btn-link">Nouvelle demande</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
