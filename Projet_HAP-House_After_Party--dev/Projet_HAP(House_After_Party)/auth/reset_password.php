<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
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
            $message = 'Le lien a expiré.';
        } elseif (hash('sha256', $token) !== $row['token_hash']) {
            $message = 'Token invalide.';
        } else {
            $valid = true;
        }
    } else {
        $message = 'Token introuvable.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($password === '' || $password !== $confirm) {
        $message = 'Les mots de passe doivent correspondre et ne pas être vides.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE email = :email ORDER BY id DESC LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || hash('sha256', $token) !== $row['token_hash'] || new DateTime($row['expires_at']) < new DateTime()) {
            $message = 'Le token est invalide ou expiré.';
        } else {
            // Update user password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE Locataire SET password_locataire = :hash WHERE LOWER(email_locataire) = LOWER(:email)');
            $update->execute(['hash' => $hashed, 'email' => $email]);

            // Delete used tokens
            $del = $pdo->prepare('DELETE FROM password_resets WHERE email = :email');
            $del->execute(['email' => $email]);

            $message = 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.';
            $valid = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="../Css/style.css">
    <style>.container{max-width:600px;margin:60px auto;padding:24px;background:#fff;border-radius:12px;}</style>
</head>
<body>
    <div class="container">
        <a href="/../index.php">&larr; Retour</a>
        <h2>Réinitialiser le mot de passe</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($valid): ?>
            <form method="post">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required>
                <label for="confirm">Confirmer</label>
                <input id="confirm" type="password" name="confirm" required>
                <button type="submit" name="reset_password">Réinitialiser</button>
            </form>
        <?php else: ?>
            <p>Le lien de réinitialisation n'est pas valide. Demandez un nouveau lien si nécessaire.</p>
        <?php endif; ?>
    </div>
    <?php include '../theme_toggle.php'; ?>
</body>
</html>
