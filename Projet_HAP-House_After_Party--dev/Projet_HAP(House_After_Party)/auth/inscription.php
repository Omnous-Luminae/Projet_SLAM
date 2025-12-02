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
    $tel_nationalite = trim($_POST['tel_nationalite'] ?? 'FR');
    $date_naissance_locataire = trim($_POST['date_naissance_locataire'] ?? '');
    $password_locataire = trim($_POST['password_locataire'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $rue_locataire = trim($_POST['rue_locataire'] ?? '');
    $rue_locataire_only = trim($_POST['rue_locataire_only'] ?? '');
    $complement_rue_locataire = trim($_POST['complement_rue_locataire'] ?? '');
    $rue_validated = trim($_POST['rue_validated'] ?? '0');
    $raison_sociale = trim($_POST['raison_sociale'] ?? '');
    $siren = trim($_POST['siren'] ?? '');
    $siret = trim($_POST['siret'] ?? '');
    $id_commune = intval($_POST['id_commune'] ?? 0);

    $errors = [];

    // Validation du numéro de téléphone selon la nationalité sélectionnée
    function validatePhoneNumber($phone, $nationality) {
        $phone = preg_replace('/[\s.-]/', '', $phone);
        
        $patterns = [
            'FR' => '/^(?:(?:\+33|0033)33|0)[1-9]\d{8}$/',  // 9 digits after 0 or +33
            'BE' => '/^(?:\+32|0032|0)(?:2|475|47|494|497|498|499|8|9)\d{7,8}$/',  // Belgium
            'DE' => '/^(?:\+49|0049|0)[1-9]\d{4,12}$/',  // Germany
            'ES' => '/^(?:\+34|0034)?[6789]\d{8}$/',  // Spain
            'IT' => '/^(?:\+39|0039)(?:3[0-9]{8}|0[0-9]{1,10})$/',  // Italy
            'CH' => '/^(?:\+41|0041|0)[1-9]\d{8}$/',  // Switzerland
        ];
        
        $pattern = $patterns[$nationality] ?? $patterns['FR'];
        return preg_match($pattern, $phone) ? true : false;
    }
    
    if (!validatePhoneNumber($tel_locataire, $tel_nationalite)) {
        $errors[] = "Numéro de téléphone invalide pour la nationalité sélectionnée.";
    }

    // Age check (must be 18+)
    if (!empty($date_naissance_locataire)) {
        try {
            $birth = new DateTime($date_naissance_locataire);
            $now = new DateTime();
            $age = $now->diff($birth)->y;
            if ($age < 18) {
                $errors[] = "Vous devez être majeur pour vous inscrire (18+).";
            }
        } catch (Exception $e) {
            $errors[] = "Date de naissance invalide.";
        }
    }

    // Validation du SIREN pour personne morale
    if ($type === 'morale') {
        if (strlen($siren) !== 9 || !ctype_digit($siren)) {
            $errors[] = "Le numéro SIREN doit contenir exactement 9 chiffres.";
        }
        if (strlen($siret) !== 14 || !ctype_digit($siret)) {
            $errors[] = "Le numéro SIRET doit contenir exactement 14 chiffres.";
        }
    }

    // Vérification côté serveur que l'adresse existe réellement via adresse.data.gouv.fr
    if (!empty($rue_locataire_only)) {
        $query = $rue_locataire_only;
        $url = 'https://api-adresse.data.gouv.fr/search/?q=' . urlencode($query) . '&limit=1';
        if (!empty($id_commune)) {
            // include citycode to narrow the search when available
            $url .= '&citycode=' . urlencode($id_commune);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $apiResp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $apiData = $apiResp ? json_decode($apiResp, true) : null;
        if (!$apiData || empty($apiData['features'])) {
            $errors[] = "Veuillez sélectionner une adresse existante via l'autocomplétion (rue).";
        }
    }

    // Validate SIREN/SIRET if provided using Luhn
    function is_valid_siren($siren) {
        if (!preg_match('/^\d{9}$/', $siren)) return false;
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $n = intval($siren[$i]);
            if ($i % 2 == 0) {
                $n = $n * 2;
                if ($n > 9) $n -= 9;
            }
            $sum += $n;
        }
        return ($sum % 10) === 0;
    }
    if ($type === 'morale' && $siren !== '') {
        if (!is_valid_siren($siren)) {
            $errors[] = "Le SIREN est invalide.";
        }
    }

    $required_fields = [$nom_locataire, $prenom_locataire, $email_locataire, $password_locataire, $confirm_password, $date_naissance_locataire, $rue_locataire_only, $tel_locataire];
    if ($type === 'morale') {
        $required_fields[] = $raison_sociale;
        $required_fields[] = $siren;
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

    // Password policy (CNIL-like): min 8, upper, lower, digit, special
    $pw_ok = preg_match('/.{8,}/', $password_locataire)
        && preg_match('/[A-Z]/', $password_locataire)
        && preg_match('/[a-z]/', $password_locataire)
        && preg_match('/[0-9]/', $password_locataire)
        && preg_match('/[\W_]/', $password_locataire);
    if (!$pw_ok) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    if (empty($errors)) {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $hashed_password = password_hash($password_locataire, PASSWORD_DEFAULT);
            $siret_value = $type === 'morale' ? $siret : null;
            $raison_sociale_value = $type === 'morale' ? $raison_sociale : null;

            $locataireObj = new Locataire(null, $nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire_only, $complement_rue_locataire, $pdo);
            if ($locataireObj->createLocataire($nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire_only, $complement_rue_locataire, $siret_value, $raison_sociale_value, $id_commune)) {
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
// Date limite pour être majeur (18 ans)
$maxDob = date('Y-m-d', strtotime('-18 years'));
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
            document.getElementById('morale-siren').style.display = (type === 'morale') ? 'block' : 'none';
            document.getElementById('morale-siret').style.display = (type === 'morale') ? 'block' : 'none';
        }

        // Fetch company info from API.SIREN.fr when SIREN is entered
        function fetchSirenInfo(siren) {
            if (siren.length !== 9 || !/^\d+$/.test(siren)) {
                return;
            }
            
            fetch(`https://api.siren.fr/${siren}`)
                .then(r => r.json())
                .then(data => {
                    if (data && data.raison_sociale) {
                        document.querySelector('input[name="raison_sociale"]').value = data.raison_sociale;
                        document.getElementById('siren-info').textContent = `✓ Entreprise trouvée: ${data.raison_sociale}`;
                        document.getElementById('siren-info').style.color = 'green';
                    } else {
                        document.getElementById('siren-info').textContent = '';
                    }
                })
                .catch(err => {
                    document.getElementById('siren-info').textContent = '';
                });
        }

        $(document).ready(function() {
            // SIREN lookup when user finishes entering
            let sirenTimeout;
            $('#siren').on('input', function() {
                clearTimeout(sirenTimeout);
                const siren = $(this).val();
                if (siren.length === 9 && /^\d+$/.test(siren)) {
                    sirenTimeout = setTimeout(() => fetchSirenInfo(siren), 500);
                }
            });

            // Only allow digits in SIREN and SIRET
            $('#siren, input[name="siret"]').on('input', function() {
                $(this).val($(this).val().replace(/[^\d]/g, ''));
            });

            // Only allow digits and spaces in phone
            $('#tel_locataire').on('input', function() {
                let val = $(this).val();
                val = val.replace(/[^\d\s.-]/g, '');
                $(this).val(val);
            });

            // Commune autocomplete
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

            // When typing in street, mark address as not validated
            $(document).on('input', '#rue_locataire', function() {
                if ($('#rue_validated').length) $('#rue_validated').val('0');
            });

            // Initialize address autocomplete for the street input with separated display
            if (document.querySelector('#rue_locataire')) {
                initAddressAutocomplete('#rue_locataire', function(item) {
                    // Extract street name only
                    const streetName = item.label.split(',')[0].trim();
                    $('#rue_locataire').val(streetName);
                    $('#rue_locataire_full').val(item.label);
                    
                    // mark as validated so the form can be submitted
                    if ($('#rue_validated').length) $('#rue_validated').val('1');
                    if (item.city) {
                        $('#commune').val(item.city);
                    }
                    if (item.properties && item.properties.citycode) {
                        $('#id_commune').val(item.properties.citycode);
                    }
                });
            }

            $("#registerForm").on('submit', function(e) {
                if (!$("#id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }
                // Ensure the street was picked from the autocomplete
                if ($('#rue_validated').length && $('#rue_validated').val() !== '1') {
                    alert("Veuillez sélectionner une adresse valide dans la liste d'autocomplétion pour la rue.");
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
                <input id="date_naissance_locataire" type="date" class="form-control" name="date_naissance_locataire" value="<?php echo htmlspecialchars($_POST['date_naissance_locataire'] ?? ''); ?>" max="<?php echo $maxDob; ?>" required>
                <small>Vous devez avoir au moins 18 ans pour vous inscrire.</small>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email_locataire" value="<?php echo htmlspecialchars($_POST['email_locataire'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <div style="display: flex; gap: 10px;">
                    <select class="form-control" name="tel_nationalite" id="tel_nationalite" style="flex: 0 0 auto; width: auto;">
                        <option value="FR" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'FR') ? 'selected' : ''; ?>>🇫🇷 France (+33)</option>
                        <option value="BE" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'BE') ? 'selected' : ''; ?>>🇧🇪 Belgique (+32)</option>
                        <option value="DE" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'DE') ? 'selected' : ''; ?>>🇩🇪 Allemagne (+49)</option>
                        <option value="ES" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'ES') ? 'selected' : ''; ?>>🇪🇸 Espagne (+34)</option>
                        <option value="IT" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'IT') ? 'selected' : ''; ?>>🇮🇹 Italie (+39)</option>
                        <option value="CH" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'CH') ? 'selected' : ''; ?>>🇨🇭 Suisse (+41)</option>
                    </select>
                    <input type="tel" class="form-control" name="tel_locataire" id="tel_locataire" value="<?php echo htmlspecialchars($_POST['tel_locataire'] ?? ''); ?>" maxlength="20" placeholder="Numéro de téléphone" required>
                </div>
                <small>Format selon le pays sélectionné (ex: FR: 06 12 34 56 78)</small>
            </div>
            <div class="form-group">
                <label>Rue</label>
                <input id="rue_locataire" type="text" class="form-control" name="rue_locataire_only" value="<?php echo htmlspecialchars($_POST['rue_locataire_only'] ?? ''); ?>" placeholder="Sélectionnez une rue via l'autocomplétion" required>
                <input type="hidden" id="rue_locataire_full" name="rue_locataire" value="<?php echo htmlspecialchars($_POST['rue_locataire'] ?? ''); ?>">
                <input type="hidden" id="rue_validated" name="rue_validated" value="<?php echo htmlspecialchars($_POST['rue_validated'] ?? '0'); ?>">
                <small>Tapez pour voir les adresses disponibles</small>
            </div>
            <div class="form-group">
                <label>Complément d'adresse</label>
                <input type="text" class="form-control" name="complement_rue_locataire" value="<?php echo htmlspecialchars($_POST['complement_rue_locataire'] ?? ''); ?>" placeholder="Optionnel (app., bâtiment, etc.)">
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
            <div class="form-group" id="morale-siren" style="display:<?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'block' : 'none'; ?>;">
                <label>SIREN</label>
                <input type="text" class="form-control" name="siren" id="siren" value="<?php echo htmlspecialchars($_POST['siren'] ?? ''); ?>" maxlength="9" placeholder="9 chiffres">
                <small id="siren-info"></small>
            </div>
            <div class="form-group" id="morale-siret" style="display:<?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'block' : 'none'; ?>;">
                <label>SIRET</label>
                <input type="text" class="form-control" name="siret" value="<?php echo htmlspecialchars($_POST['siret'] ?? ''); ?>" maxlength="14" placeholder="14 chiffres">
                <small>Le SIRET contient le SIREN suivi du numéro d'établissement</small>
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
