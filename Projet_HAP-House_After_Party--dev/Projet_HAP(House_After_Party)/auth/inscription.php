<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $type = $_POST['type'] ?? '';
    $nom_locataire = trim($_POST['nom_locataire'] ?? '');
    $prenom_locataire = trim($_POST['prenom_locataire'] ?? '');
    $email_locataire = trim($_POST['email_locataire'] ?? '');
    $tel_locataire = trim($_POST['tel_locataire'] ?? '');
    $date_naissance_locataire = trim($_POST['date_naissance_locataire'] ?? '');
    $password_locataire = trim($_POST['password_locataire'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $rue_locataire = trim($_POST['rue_locataire'] ?? '');
    $complement_rue_locataire = trim($_POST['complement_rue_locataire'] ?? '');
    $raison_sociale = trim($_POST['raison_sociale'] ?? '');
    $siret = trim($_POST['siret'] ?? '');

    $required_fields = [$nom_locataire, $prenom_locataire, $email_locataire, $password_locataire, $confirm_password, $date_naissance_locataire, $rue_locataire, $tel_locataire];
    if ($type === 'morale') {
        $required_fields[] = $raison_sociale;
        $required_fields[] = $siret;
    }

    if (in_array('', $required_fields) || $type === '') {
        $message = "Veuillez remplir tous les champs obligatoires.";
    } elseif ($password_locataire !== $confirm_password) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (!filter_var($email_locataire, FILTER_VALIDATE_EMAIL)) {
        $message = "Email invalide.";
    } else {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $hashed_password = password_hash($password_locataire, PASSWORD_DEFAULT);
            $siret_value = $type === 'morale' ? $siret : null;
            $raison_sociale_value = $type === 'morale' ? $raison_sociale : null;

            $locataireObj = new Locataire(null, $nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire, $complement_rue_locataire, $pdo);
            if ($locataireObj->createLocataire($nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire, $complement_rue_locataire, $siret_value, $raison_sociale_value)) {
                $message = "Inscription réussie. Vous pouvez maintenant vous connecter.";
            } else {
                $message = "Erreur lors de l'inscription.";
            }
        } else {
            $message = "Erreur de connexion à la base de données.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="../Css/style.css">
    <script>
        function toggleMoraleFields() {
            const type = document.querySelector('input[name="type"]:checked').value;
            document.getElementById('morale-fields').style.display = (type === 'morale') ? 'block' : 'none';
            document.getElementById('morale-siret').style.display = (type === 'morale') ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="auth-container">
        <h2>Inscription</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'réussie') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label>Type de personne</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" id="physique" name="type" value="physique" required onchange="toggleMoraleFields()">
                        Personne Physique
                    </label>
                    <label>
                        <input type="radio" id="morale" name="type" value="morale" required onchange="toggleMoraleFields()">
                        Personne Morale
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Nom</label>
                <input type="text" class="form-control" name="nom_locataire" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" class="form-control" name="prenom_locataire" required>
            </div>
            <div class="form-group">
                <label>Date de Naissance</label>
                <input type="date" class="form-control" name="date_naissance_locataire" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email_locataire" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" class="form-control" name="tel_locataire" required>
            </div>
            <div class="form-group">
                <label>Rue</label>
                <input type="text" class="form-control" name="rue_locataire" required>
            </div>
            <div class="form-group">
                <label>Complément d'adresse</label>
                <input type="text" class="form-control" name="complement_rue_locataire">
            </div>
            <div class="form-group" id="morale-fields" style="display:none;">
                <label>Raison Sociale</label>
                <input type="text" class="form-control" name="raison_sociale">
            </div>
            <div class="form-group" id="morale-siret" style="display:none;">
                <label>SIRET</label>
                <input type="text" class="form-control" name="siret">
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" class="form-control" name="password_locataire" required>
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary">S'inscrire</button>
        </form>
        <div class="auth-link">
            <p>Déjà un compte ? <a href="connexion.php">Connectez-vous ici</a>.</p>
        </div>
    </div>
</body>
</html>
