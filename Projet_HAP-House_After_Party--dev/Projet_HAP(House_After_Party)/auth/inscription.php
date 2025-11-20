<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

$message = '';
// Vérifier le captcha côté serveur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!isset($_POST['captcha']) || !isset($_SESSION['captcha_answer']) || trim($_POST['captcha']) === '' || intval($_POST['captcha']) !== intval($_SESSION['captcha_answer'])) {
        $message = "Captcha incorrect. Veuillez répondre à la question.";
    }

    // Si captcha incorrect, on n'exécute pas la suite
    if (empty($message)) {
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
    $id_commune = intval($_POST['id_commune'] ?? 0);

    $errors = [];

    // Validation du numéro de téléphone français
    $tel_pattern = '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/';
    if (!preg_match($tel_pattern, $tel_locataire)) {
        $errors[] = "Numéro de téléphone invalide. Utilisez un format français valide (ex: 06 12 34 56 78).";
    }

    // Validation du SIRET pour personne morale
    if ($type === 'morale') {
        if (strlen($siret) !== 14 || !ctype_digit($siret)) {
            $errors[] = "Le numéro SIRET doit contenir exactement 14 chiffres.";
        }
    }

    $required_fields = [$nom_locataire, $prenom_locataire, $email_locataire, $password_locataire, $confirm_password, $date_naissance_locataire, $rue_locataire, $tel_locataire];
    if ($type === 'morale') {
        $required_fields[] = $raison_sociale;
        $required_fields[] = $siret;
    }

    if (in_array('', $required_fields) || $type === '' || $id_commune === 0) {
        $errors[] = "Veuillez remplir tous les champs obligatoires, y compris la commune.";
    }

    if ($password_locataire !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!filter_var($email_locataire, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    }

    // Vérifier si l'email est déjà utilisé
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT id_locataire FROM Locataire WHERE LOWER(email_locataire) = LOWER(:email)");
        $stmt->execute(['email' => $email_locataire]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé.";
        }
    }

    if (empty($errors)) {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $hashed_password = password_hash($password_locataire, PASSWORD_DEFAULT);
            $siret_value = $type === 'morale' ? $siret : null;
            $raison_sociale_value = $type === 'morale' ? $raison_sociale : null;

            $locataireObj = new Locataire(null, $nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire, $complement_rue_locataire, $pdo);
            if ($locataireObj->createLocataire($nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire, $complement_rue_locataire, $siret_value, $raison_sociale_value, $id_commune)) {
                $message = "Inscription réussie. Vous pouvez maintenant vous connecter.";
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
}

// Générer une nouvelle question captcha pour l'affichage (GET ou après POST)
$a = rand(2, 9);
$b = rand(2, 9);
$_SESSION['captcha_answer'] = $a + $b;
$_SESSION['captcha_question'] = "Combien font $a + $b ?";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="../Css/style.css">
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="../js/autocomplete.js"></script>
    <script>
        function toggleMoraleFields() {
            const type = document.querySelector('input[name="type"]:checked').value;
            document.getElementById('morale-fields').style.display = (type === 'morale') ? 'block' : 'none';
            document.getElementById('morale-siret').style.display = (type === 'morale') ? 'block' : 'none';
        }

        $(document).ready(function() {
            $("#commune").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "../api/search_communes.php",
                        dataType: "json",
                        data: {
                            q: request.term
                        },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $("#commune").val(ui.item.label);
                    $("#id_commune").val(ui.item.id);
                    return false;
                }
            });

            $("#commune").on('input', function() {
                $("#id_commune").val('');
            });

            $("#registerForm").on('submit', function(e) {
                if (!$("#id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</head>
<body>
    <div class="auth-container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Inscription</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'réussie') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="auth-form" id="registerForm">
            <div class="form-group">
                <label>Type de personne</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" id="physique" name="type" value="physique" required onchange="toggleMoraleFields()" <?php echo (isset($_POST['type']) && $_POST['type'] === 'physique') ? 'checked' : ''; ?>>
                        Personne Physique
                    </label>
                    <label>
                        <input type="radio" id="morale" name="type" value="morale" required onchange="toggleMoraleFields()" <?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'checked' : ''; ?>>
                        Personne Morale
                    </label>
                </div>
            </div>
            <div class="form-group">
                <label>Nom</label>
                <input type="text" class="form-control" name="nom_locataire" value="<?php echo htmlspecialchars($_POST['nom_locataire'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" class="form-control" name="prenom_locataire" value="<?php echo htmlspecialchars($_POST['prenom_locataire'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Date de Naissance</label>
                <input type="date" class="form-control" name="date_naissance_locataire" value="<?php echo htmlspecialchars($_POST['date_naissance_locataire'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email_locataire" value="<?php echo htmlspecialchars($_POST['email_locataire'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" class="form-control" name="tel_locataire" value="<?php echo htmlspecialchars($_POST['tel_locataire'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Rue</label>
                <input type="text" class="form-control" name="rue_locataire" value="<?php echo htmlspecialchars($_POST['rue_locataire'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Complément d'adresse</label>
                <input type="text" class="form-control" name="complement_rue_locataire" value="<?php echo htmlspecialchars($_POST['complement_rue_locataire'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Commune</label>
                <input type="text" id="commune" class="form-control" placeholder="Tapez le nom de votre commune" value="<?php echo htmlspecialchars($_POST['commune'] ?? ''); ?>" required>
                <input type="hidden" id="id_commune" name="id_commune" value="<?php echo htmlspecialchars($_POST['id_commune'] ?? ''); ?>">
            </div>
            <div class="form-group" id="morale-fields" style="display:<?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'block' : 'none'; ?>;">
                <label>Raison Sociale</label>
                <input type="text" class="form-control" name="raison_sociale" value="<?php echo htmlspecialchars($_POST['raison_sociale'] ?? ''); ?>">
            </div>
            <div class="form-group" id="morale-siret" style="display:<?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'block' : 'none'; ?>;">
                <label>SIRET</label>
                <input type="text" class="form-control" name="siret" value="<?php echo htmlspecialchars($_POST['siret'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password_locataire">Mot de passe</label>
                <input id="password_locataire" type="password" class="form-control" name="password_locataire" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input id="confirm_password" type="password" class="form-control" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="captcha_input"><?php echo htmlspecialchars($_SESSION['captcha_question'] ?? ''); ?></label>
                <input id="captcha_input" type="text" class="form-control" name="captcha" required>
            </div>
            <button type="submit" name="register" class="btn btn-primary">S'inscrire</button>
        </form>
        <div class="auth-link">
            <p>Déjà un compte ? <a href="connexion.php">Connectez-vous ici</a>.</p>
        </div>
    </div>
</body>
</html>
