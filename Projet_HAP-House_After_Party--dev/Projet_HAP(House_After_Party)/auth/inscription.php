<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/user_security.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

// Polyfills pour environnements sans certaines extensions
if (!function_exists('ctype_digit')) {
    function ctype_digit($text)
    {
        return preg_match('/^\d+$/', $text) === 1;
    }
}

if (!function_exists('random_int')) {
    function random_int($min, $max)
    {
        static $seed = null;
        if ($seed === null) {
            $seed = (int) (microtime(true) * 1000000);
        }
        $seed = ($seed * 1103515245 + 12345) % 0x7fffffff;
        $range = ($max - $min + 1);
        return $min + ($seed % $range);
    }
}

$message = '';
$messageType = '';
$isBlocked = false;
$cooldownRemaining = 0;

// Récupérer l'IP du client
$clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// Vérifier si l'IP est bloquée pour l'inscription
if ($pdo && isRegistrationBlocked($pdo, $clientIP)) {
    $isBlocked = true;
    $cooldownRemaining = getRegistrationCooldownRemaining($pdo, $clientIP);
}

// Générer le token CSRF
$csrfToken = generateUserCsrfToken();

if (isset($_GET['redirect_from']) && $_GET['redirect_from'] === 'reservation') {
    $message = "Vous devez être connecté pour effectuer une réservation.";
    $messageType = 'info';
}
if (isset($_SESSION['redirect_message'])) {
    $message = $_SESSION['redirect_message'];
    $messageType = 'info';
    unset($_SESSION['redirect_message']);
}

// Vérifier le captcha et CSRF côté serveur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Vérification CSRF
    if (!verifyUserCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = "Session expirée. Veuillez réessayer.";
        $messageType = 'error';
    } elseif ($isBlocked) {
        $message = "Trop de tentatives d'inscription. Réessayez dans " . ceil($cooldownRemaining / 60) . " minutes.";
        $messageType = 'error';
    } elseif (!isset($_POST['captcha']) || !isset($_SESSION['captcha_answer']) || trim($_POST['captcha']) === '' || intval($_POST['captcha']) !== intval($_SESSION['captcha_answer'])) {
        $message = "Captcha incorrect. Veuillez répondre à la question.";
        $messageType = 'error';
        // Enregistrer la tentative
        if ($pdo) {
            recordRegistrationAttempt($pdo, $clientIP, $_POST['email_locataire'] ?? null, false);
        }
    } else {
    // Si captcha correct, continuer
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

    // Validation basique de l'adresse : vérifier qu'elle contient au moins quelques caractères
    // La validation stricte via API a été supprimée car elle peut rejeter des adresses valides
    // L'autocomplétion aide l'utilisateur mais n'est pas obligatoire
    if (!empty($rue_locataire_only) && strlen(trim($rue_locataire_only)) < 3) {
        $errors[] = "❌ Le nom de la rue doit contenir au moins 3 caractères.";
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
        $errors[] = "❌ Mot de passe trop faible. Il doit contenir au minimum :<br>" .
                    "&nbsp;&nbsp;• 8 caractères<br>" .
                    "&nbsp;&nbsp;• 1 majuscule (A-Z)<br>" .
                    "&nbsp;&nbsp;• 1 minuscule (a-z)<br>" .
                    "&nbsp;&nbsp;• 1 chiffre (0-9)<br>" .
                    "&nbsp;&nbsp;• 1 caractère spécial (@, #, !, etc.)";
    }

    if (empty($errors)) {
        $pdo = $pdo ?? null;
        if ($pdo) {
            $hashed_password = password_hash($password_locataire, PASSWORD_DEFAULT);
            $siret_value = $type === 'morale' ? $siret : null;
            $raison_sociale_value = $type === 'morale' ? $raison_sociale : null;

            $locataireObj = new Locataire(null, $nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire_only, $complement_rue_locataire, $pdo);
            if ($locataireObj->createLocataire($nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $hashed_password, $rue_locataire_only, $complement_rue_locataire, $siret_value, $raison_sociale_value, $id_commune)) {
                // Enregistrer le succès
                if ($pdo) {
                    recordRegistrationAttempt($pdo, $clientIP, $email_locataire, true);
                }
                $message = "✅ Inscription réussie ! Vous pouvez maintenant vous connecter.";
                $messageType = 'success';
            } else {
                recordRegistrationAttempt($pdo, $clientIP, $email_locataire, false);
                $message = "Erreur lors de l'inscription.";
                $messageType = 'error';
            }
        } else {
            $message = "Erreur de connexion à la base de données.";
            $messageType = 'error';
        }
    } else {
        // Enregistrer la tentative échouée
        if ($pdo) {
            recordRegistrationAttempt($pdo, $clientIP, $email_locataire ?? null, false);
        }
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}
}

// Régénérer le token CSRF
$csrfToken = regenerateUserCsrfToken();

// Générer une nouvelle question captcha pour l'affichage (GET ou après POST)
$a = random_int(2, 9);
$b = random_int(2, 9);
$_SESSION['captcha_answer'] = $a + $b;
$_SESSION['captcha_question'] = "Combien font $a + $b ?";
// Date limite pour être majeur (18 ans)
$maxDob = date('Y-m-d', strtotime('-18 years'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="../js/autocomplete.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-primary: rgba(255, 255, 255, 0.95);
            --bg-secondary: #fafafa;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e0e0e0;
            --accent: #667eea;
            --accent-light: #764ba2;
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
        
        .register-container {
            background: var(--bg-primary);
            border-radius: 24px;
            padding: 40px;
            max-width: 550px;
            width: 100%;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .register-container::before {
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
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-link:hover { transform: translateX(-5px); }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
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
            font-size: 1.5em;
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
            margin-bottom: 20px;
            font-weight: 500;
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
            padding: 25px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .lockout-warning .timer {
            font-size: 2.5em;
            font-weight: 700;
            color: #dc3545;
            margin: 15px 0;
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
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95em;
            font-family: inherit;
            transition: all 0.3s;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input:disabled {
            background: #f0f0f0;
            cursor: not-allowed;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: var(--text-secondary);
            font-size: 0.8em;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            padding: 10px 0;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 12px 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s;
            flex: 1;
            justify-content: center;
        }
        
        .radio-group label:has(input:checked) {
            border-color: var(--accent);
            background: rgba(102, 126, 234, 0.1);
        }
        
        .radio-group input[type="radio"] {
            width: auto;
            margin: 0;
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
            margin-top: 10px;
            padding: 12px;
            background: var(--bg-secondary);
            border-radius: 10px;
            display: none;
            font-size: 0.85em;
        }
        
        .password-strength.visible {
            display: block;
        }
        
        .strength-bar {
            height: 5px;
            background: var(--border-color);
            border-radius: 3px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 3px;
        }
        
        .strength-bar-fill.weak { width: 20%; background: #dc3545; }
        .strength-bar-fill.fair { width: 40%; background: #ffc107; }
        .strength-bar-fill.good { width: 70%; background: #17a2b8; }
        .strength-bar-fill.strong { width: 100%; background: #28a745; }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 4px 0;
            color: var(--text-secondary);
            font-size: 0.85em;
        }
        
        .requirement.met { color: #28a745; }
        
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
            margin-top: 15px;
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
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
        }
        
        .auth-links p {
            color: var(--text-secondary);
            margin: 8px 0;
        }
        
        .auth-links a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-links a:hover { text-decoration: underline; }
        
        .security-note {
            margin-top: 15px;
            padding: 12px;
            background: var(--bg-secondary);
            border-radius: 10px;
            font-size: 0.8em;
            color: var(--text-secondary);
            text-align: center;
        }
        
        .security-note span { color: #28a745; }
        
        .section-title {
            font-size: 0.9em;
            font-weight: 600;
            color: var(--accent);
            margin: 20px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        #morale-fields,
        #morale-siren,
        #morale-siret {
            display: none;
        }
        
        .captcha-box {
            background: linear-gradient(135deg, #e8f4fd, #d1e9fc);
            padding: 15px;
            border-radius: 12px;
            border: 2px solid #667eea;
        }
        
        .captcha-box label {
            color: #333 !important;
            font-weight: 700;
        }
        
        /* jQuery UI Autocomplete Override */
        .ui-autocomplete {
            z-index: 9999 !important;
            max-height: 250px;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--bg-primary) !important;
            border: 2px solid var(--accent) !important;
            border-radius: 10px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
        }
        
        .ui-menu-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
        }
        
        .ui-menu-item:hover,
        .ui-menu-item.ui-state-active {
            background: rgba(102, 126, 234, 0.1) !important;
            color: var(--accent) !important;
        }
    </style>
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
            // Initialize autocomplete for commune
            initAddCommuneAutocomplete();

            // Commune-specific street features
            let streetsForCommuneFeatures = [];
            let streetsForCommune = [];
            let selectedCodeInsee = '';

            // Initialize rue field - disabled until commune is selected
            $('#rue_locataire').attr('placeholder', 'Sélectionnez d\'abord une commune...');
            $('#rue_locataire').prop('disabled', true);

            // Listen for commune selection to load streets
            $(document).on('commune-selected', '#commune', function(event, code_insee) {
                console.log('Commune sélectionnée avec code_insee:', code_insee);
                selectedCodeInsee = code_insee;
                
                if (code_insee) {
                    // Clear rue field when commune changes
                    $('#rue_locataire').val('');
                    $('#rue_validated').val('0');
                    
                    // Get commune info
                    const communeText = $('#commune').val();
                    const communeId = $('#id_commune').val();
                    
                    // Load streets for this commune
                    fetchStreetsForCommune(code_insee, communeText, communeId);
                }
            });

            // Watch commune input changes (when user types, reset commune_id)
            $(document).on('input', '#commune', function() {
                if (!$('#id_commune').val()) {
                    // Clear streets when commune not selected
                    streetsForCommune = [];
                    streetsForCommuneFeatures = [];
                    selectedCodeInsee = '';
                    $('#rue_locataire').val('');
                    $('#rue_validated').val('0');
                    $('#rue_locataire').prop('disabled', true);
                    $('#rue_locataire').attr('placeholder', 'Sélectionnez d\'abord une commune...');
                }
            });

            function fetchStreetsForCommune(code_insee, communeText, communeId) {
                if (!code_insee && !communeText) {
                    console.log('Pas de commune sélectionnée');
                    return;
                }
                
                console.log('🔍 Récupération des rues pour:', { code_insee, communeText, communeId });
                
                // Show loading state
                $('#rue_locataire').prop('disabled', true);
                $('#rue_locataire').attr('placeholder', 'Chargement des rues...');
                
                // Extract postal code from commune text (format: "COMMUNE (75001)")
                let postalCode = null;
                let communeName = communeText;
                if (communeText) {
                    const match = communeText.match(/^(.+?)\s*\((\d{5})\)$/);
                    if (match) {
                        communeName = match[1].trim();
                        postalCode = match[2];
                    }
                }
                
                console.log('Extracted:', { postalCode, communeName });
                
                // Use simple search - just enable real-time autocomplete
                console.log('Activation de l\'autocomplétion en temps réel pour', communeName);
                enableManualEntry('Saisie avec autocomplétion en temps réel');
                
                function processStreetResults(features, postalCode) {
                    streetsForCommuneFeatures = features;
                    
                    // Filter by postal code if available to ensure we only get streets from this commune
                    let filteredFeatures = features;
                    if (postalCode) {
                        filteredFeatures = features.filter(f => {
                            return f.properties && 
                                   (f.properties.postcode === postalCode ||
                                    (f.properties.citycode && f.properties.citycode === selectedCodeInsee));
                        });
                        console.log('Filtré par code postal/INSEE:', filteredFeatures.length);
                    }
                    
                    // Extract unique street names
                    const uniqueStreets = new Map();
                    filteredFeatures.forEach(f => {
                        if (f.properties && f.properties.name) {
                            const name = f.properties.name;
                            if (!uniqueStreets.has(name.toLowerCase())) {
                                uniqueStreets.set(name.toLowerCase(), {
                                    label: name,
                                    value: name,
                                    feature: f
                                });
                            }
                        }
                    });
                    
                    streetsForCommune = Array.from(uniqueStreets.values()).sort((a, b) => 
                        a.label.localeCompare(b.label)
                    );
                    
                    console.log('✅ Rues uniques:', streetsForCommune.length);
                    
                    if (streetsForCommune.length > 0) {
                        setRueAutocompleteFromStreets();
                        $('#rue_locataire').prop('disabled', false);
                        $('#rue_locataire').attr('placeholder', `${streetsForCommune.length} rue(s) disponible(s) - Tapez pour rechercher`);
                    } else {
                        enableManualEntry('Aucune rue trouvée pour cette commune');
                    }
                }
                
                function enableManualEntry(reason) {
                    console.log('Saisie manuelle activée:', reason);
                    streetsForCommune = []; // Pas de cache, utiliser API en temps réel
                    $('#rue_locataire').prop('disabled', false);
                    $('#rue_locataire').attr('placeholder', 'Tapez le nom de la rue...');
                    setRueAutocompleteFromStreets(); // Configure real-time autocomplete
                }
            }

            function setRueAutocompleteFromStreets() {
                try { $('#rue_locataire').autocomplete('destroy'); } catch (e) {}
                
                // Always use real-time API search for better results
                const communeName = $('#commune').val().split('(')[0].trim();
                const postalCode = $('#commune').val().match(/\((\d{5})\)/)?.[1];
                
                $('#rue_locataire').autocomplete({
                    source: function(request, response) {
                        // Build query: street name + commune name for better matching
                        const query = request.term + ' ' + communeName;
                        
                        $.ajax({
                            url: 'https://api-adresse.data.gouv.fr/search/',
                            data: {
                                q: query,
                                limit: 20
                            },
                            dataType: 'json',
                            success: function(data) {
                                if (data && data.features) {
                                    // Filter results to only show streets from the selected commune
                                    const streets = data.features
                                        .filter(f => {
                                            const props = f.properties;
                                            return props && 
                                                   props.type === 'street' &&
                                                   (props.postcode === postalCode || 
                                                    props.citycode === selectedCodeInsee ||
                                                    props.city === communeName);
                                        })
                                        .map(f => ({
                                            label: f.properties.name,
                                            value: f.properties.name
                                        }))
                                        // Remove duplicates
                                        .filter((item, index, self) => 
                                            index === self.findIndex(t => t.value === item.value)
                                        );
                                    
                                    response(streets);
                                } else {
                                    response([]);
                                }
                            },
                            error: function() {
                                response([]);
                            }
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        $('#rue_locataire').val(ui.item.value);
                        $('#rue_validated').val('1');
                        return false;
                    }
                });
            }

            // Reset validation flag when typing in rue field
            $(document).on('input', '#rue_locataire', function() {
                if ($('#rue_validated').length) $('#rue_validated').val('0');
            });

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

            $("#registerForm").on('submit', function(e) {
                const communeId = $('#id_commune').val();
                
                if (!communeId || isNaN(communeId) || parseInt(communeId) <= 0) {
                    alert("⚠️ Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }
                
                const rueValue = $('#rue_locataire').val();
                
                if (!rueValue || rueValue.trim() === '') {
                    alert("⚠️ Veuillez entrer une adresse (nom de rue).");
                    e.preventDefault();
                    return false;
                }
                
                // Validation souple - accepter toute rue avec au moins 3 caractères
                if (rueValue.length < 3) {
                    alert("⚠️ Le nom de la rue doit contenir au moins 3 caractères.");
                    e.preventDefault();
                    return false;
                }
                
                // Validation réussie - le formulaire peut être soumis
                console.log('✅ Validation réussie, envoi du formulaire...');
                return true;
            });

            // Validation en temps réel du mot de passe
            $('#password_locataire').on('input', function() {
                const password = $(this).val();
                const $strength = $('#password-strength');
                
                if (password.length === 0) {
                    $strength.hide();
                    return;
                }
                
                $strength.show();
                
                // Vérification de chaque critère
                const hasLength = password.length >= 8;
                const hasUpper = /[A-Z]/.test(password);
                const hasLower = /[a-z]/.test(password);
                const hasDigit = /[0-9]/.test(password);
                const hasSpecial = /[\W_]/.test(password);
                
                // Mise à jour des indicateurs visuels
                updateRequirement('req-length', hasLength);
                updateRequirement('req-upper', hasUpper);
                updateRequirement('req-lower', hasLower);
                updateRequirement('req-digit', hasDigit);
                updateRequirement('req-special', hasSpecial);
                
                // Calcul de la force du mot de passe
                const score = [hasLength, hasUpper, hasLower, hasDigit, hasSpecial].filter(Boolean).length;
                const $strengthText = $('#strength-text');
                
                if (score === 5) {
                    $strengthText.text('✓ Mot de passe fort').css('color', '#28a745');
                } else if (score >= 3) {
                    $strengthText.text('⚠ Mot de passe moyen').css('color', '#ffc107');
                } else {
                    $strengthText.text('✗ Mot de passe faible').css('color', '#dc3545');
                }
            });
            
            function updateRequirement(id, isValid) {
                const $elem = $('#' + id);
                if (isValid) {
                    $elem.html($elem.text().replace('⚪', '✅').replace(/^\u26aa/, '✅'));
                    $elem.css('color', '#28a745');
                } else {
                    $elem.html($elem.text().replace('✅', '⚪').replace(/^\u2705/, '⚪'));
                    $elem.css('color', '#666');
                }
            }

            // Vérification que les mots de passe correspondent
            $('#confirm_password').on('input', function() {
                const password = $('#password_locataire').val();
                const confirm = $(this).val();
                
                if (confirm.length > 0) {
                    if (password === confirm) {
                        $(this).css('border-color', '#28a745');
                    } else {
                        $(this).css('border-color', '#dc3545');
                    }
                } else {
                    $(this).css('border-color', '');
                }
            });
        });
    </script>
</head>
<body>
    <div class="register-container">
        <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="header">
            <div class="icon">📝</div>
            <h1>Créer un compte</h1>
            <p>Rejoignez House After Party</p>
        </div>
        
        <?php if ($isBlocked && $cooldownRemaining > 0): ?>
            <div class="lockout-warning">
                <div style="font-size: 2.5em;">🚫</div>
                <h3>Inscription temporairement bloquée</h3>
                <div class="timer" id="countdown"><?= gmdate('i:s', $cooldownRemaining) ?></div>
                <p>Trop de tentatives. Veuillez patienter.</p>
            </div>
            <script>
                let remaining = <?= $cooldownRemaining ?>;
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
            <div class="message <?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'error' : 'info') ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div class="form-group">
                <label>👤 Type de compte</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" id="physique" name="type" value="physique" required onchange="toggleMoraleFields()" <?php echo (isset($_POST['type']) && $_POST['type'] === 'physique') ? 'checked' : ''; ?>>
                        Particulier
                    </label>
                    <label>
                        <input type="radio" id="morale" name="type" value="morale" required onchange="toggleMoraleFields()" <?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'checked' : ''; ?>>
                        Entreprise
                    </label>
                </div>
            </div>
            
            <div class="section-title">📋 Informations personnelles</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Nom</label>
                    <input type="text" name="nom_locataire" value="<?php echo htmlspecialchars($_POST['nom_locataire'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Prénom</label>
                    <input type="text" name="prenom_locataire" value="<?php echo htmlspecialchars($_POST['prenom_locataire'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>🎂 Date de naissance</label>
                    <input id="date_naissance_locataire" type="date" name="date_naissance_locataire" value="<?php echo htmlspecialchars($_POST['date_naissance_locataire'] ?? ''); ?>" max="<?php echo $maxDob; ?>" required>
                    <small>18 ans minimum requis</small>
                </div>
                <div class="form-group">
                    <label>📧 Email</label>
                    <input type="email" name="email_locataire" value="<?php echo htmlspecialchars($_POST['email_locataire'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>📱 Téléphone</label>
                <div style="display: flex; gap: 10px;">
                    <select name="tel_nationalite" id="tel_nationalite" style="flex: 0 0 140px;">
                        <option value="FR" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'FR') ? 'selected' : ''; ?>>🇫🇷 +33</option>
                        <option value="BE" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'BE') ? 'selected' : ''; ?>>🇧🇪 +32</option>
                        <option value="DE" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'DE') ? 'selected' : ''; ?>>🇩🇪 +49</option>
                        <option value="ES" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'ES') ? 'selected' : ''; ?>>🇪🇸 +34</option>
                        <option value="IT" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'IT') ? 'selected' : ''; ?>>🇮🇹 +39</option>
                        <option value="CH" <?php echo (isset($_POST['tel_nationalite']) && $_POST['tel_nationalite'] === 'CH') ? 'selected' : ''; ?>>🇨🇭 +41</option>
                    </select>
                    <input type="tel" name="tel_locataire" id="tel_locataire" value="<?php echo htmlspecialchars($_POST['tel_locataire'] ?? ''); ?>" maxlength="20" required style="flex: 1;">
                </div>
            </div>
            
            <div class="section-title">📍 Adresse</div>
            
            <div class="form-group">
                <label>Commune</label>
                <input type="text" id="commune" placeholder="Tapez le nom de votre commune..." value="<?php echo htmlspecialchars($_POST['commune'] ?? ''); ?>" required>
                <input type="hidden" id="id_commune" name="id_commune" value="<?php echo htmlspecialchars($_POST['id_commune'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Rue</label>
                    <input id="rue_locataire" type="text" name="rue_locataire_only" value="<?php echo htmlspecialchars($_POST['rue_locataire_only'] ?? ''); ?>" placeholder="Sélectionnez d'abord une commune..." required disabled>
                    <input type="hidden" id="rue_validated" name="rue_validated" value="<?php echo htmlspecialchars($_POST['rue_validated'] ?? '0'); ?>">
                </div>
                <div class="form-group">
                    <label>Complément</label>
                    <input type="text" name="complement_rue_locataire" value="<?php echo htmlspecialchars($_POST['complement_rue_locataire'] ?? ''); ?>" placeholder="Apt, bâtiment...">
                </div>
            </div>
            
            <div id="morale-fields" style="display:<?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'block' : 'none'; ?>;">
                <div class="section-title">🏢 Informations entreprise</div>
                <div class="form-group">
                    <label>Raison Sociale</label>
                    <input type="text" name="raison_sociale" value="<?php echo htmlspecialchars($_POST['raison_sociale'] ?? ''); ?>" placeholder="Nom de l'entreprise">
                </div>
            </div>
            <div class="form-row" id="morale-siren" style="display:<?php echo (isset($_POST['type']) && $_POST['type'] === 'morale') ? 'flex' : 'none'; ?>;">
                <div class="form-group">
                    <label>SIREN</label>
                    <input type="text" name="siren" id="siren" value="<?php echo htmlspecialchars($_POST['siren'] ?? ''); ?>" maxlength="9" placeholder="9 chiffres">
                    <small id="siren-info"></small>
                </div>
                <div class="form-group" id="morale-siret">
                    <label>SIRET</label>
                    <input type="text" name="siret" value="<?php echo htmlspecialchars($_POST['siret'] ?? ''); ?>" maxlength="14" placeholder="14 chiffres">
                </div>
            </div>
            
            <div class="section-title">🔐 Sécurité</div>
            
            <div class="form-group">
                <label>🔑 Mot de passe</label>
                <div class="password-wrapper">
                    <input id="password_locataire" type="password" name="password_locataire" autocomplete="new-password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('password_locataire')">👁️</button>
                </div>
                <div class="password-strength" id="password-strength">
                    <div class="strength-bar">
                        <div class="strength-bar-fill" id="strength-bar"></div>
                    </div>
                    <div id="strength-text" style="font-weight: 600; margin-bottom: 8px;"></div>
                    <div class="requirements">
                        <div class="requirement" id="req-length"><span>○</span> Au moins 8 caractères</div>
                        <div class="requirement" id="req-upper"><span>○</span> Une majuscule (A-Z)</div>
                        <div class="requirement" id="req-lower"><span>○</span> Une minuscule (a-z)</div>
                        <div class="requirement" id="req-digit"><span>○</span> Un chiffre (0-9)</div>
                        <div class="requirement" id="req-special"><span>○</span> Un caractère spécial</div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>🔑 Confirmer le mot de passe</label>
                <div class="password-wrapper">
                    <input id="confirm_password" type="password" name="confirm_password" autocomplete="new-password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
            </div>
            
            <div class="form-group captcha-box">
                <label>🤖 Vérification : <?php echo htmlspecialchars($_SESSION['captcha_question'] ?? ''); ?></label>
                <input id="captcha_input" type="text" name="captcha" required>
            </div>
            
            <button type="submit" name="register" class="btn-submit">Créer mon compte</button>
        </form>
        
        <div class="auth-links">
            <p>Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
        </div>
        
        <div class="security-note">
            <span>🔒</span> Protection CSRF • Anti brute-force • Captcha
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
        
        // Password strength indicator
        document.getElementById('password_locataire')?.addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            
            if (password.length === 0) {
                strengthDiv.classList.remove('visible');
                return;
            }
            
            strengthDiv.classList.add('visible');
            
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasDigit = /[0-9]/.test(password);
            const hasSpecial = /[\W_]/.test(password);
            
            updateReq('req-length', hasLength);
            updateReq('req-upper', hasUpper);
            updateReq('req-lower', hasLower);
            updateReq('req-digit', hasDigit);
            updateReq('req-special', hasSpecial);
            
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
                el.querySelector('span').textContent = '✓';
            } else {
                el.classList.remove('met');
                el.querySelector('span').textContent = '○';
            }
        }
    </script>
    
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
