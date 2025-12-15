<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

$message = '';
if (isset($_GET['redirect_from']) && $_GET['redirect_from'] === 'reservation') {
    $message = "Vous devez être connecté pour effectuer une réservation.";
}
if (isset($_SESSION['redirect_message'])) {
    $message = $_SESSION['redirect_message'];
    unset($_SESSION['redirect_message']);
}
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
    <style>
        /* Force jQuery UI autocomplete to be visible */
        .ui-autocomplete {
            z-index: 9999 !important;
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            background: white !important;
            border: 1px solid #ccc !important;
        }
        .ui-menu-item {
            padding: 5px 10px;
            cursor: pointer;
        }
        .ui-menu-item:hover {
            background-color: #f0f0f0;
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
    <div class="auth-container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Inscription</h2>
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'réussie') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
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
                <label>Commune</label>
                <input type="text" id="commune" class="form-control" placeholder="Tapez le nom de votre commune" value="<?php echo htmlspecialchars($_POST['commune'] ?? ''); ?>" required>
                <input type="hidden" id="id_commune" name="id_commune" value="<?php echo htmlspecialchars($_POST['id_commune'] ?? ''); ?>">
                <small>Sélectionnez une commune dans la liste pour charger les rues disponibles</small>
            </div>
            <div class="form-group">
                <label>Rue</label>
                <input id="rue_locataire" type="text" class="form-control" name="rue_locataire_only" value="<?php echo htmlspecialchars($_POST['rue_locataire_only'] ?? ''); ?>" placeholder="Sélectionnez d'abord une commune..." required disabled>
                <input type="hidden" id="rue_validated" name="rue_validated" value="<?php echo htmlspecialchars($_POST['rue_validated'] ?? '0'); ?>">
                <small>💡 Utilisez l'autocomplétion pour faciliter la saisie, ou tapez le nom de la rue directement</small>
            </div>
            <div class="form-group">
                <label>Complément d'adresse</label>
                <input type="text" class="form-control" name="complement_rue_locataire" value="<?php echo htmlspecialchars($_POST['complement_rue_locataire'] ?? ''); ?>" placeholder="Optionnel (app., bâtiment, etc.)">
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
                <input id="password_locataire" type="password" class="form-control" name="password_locataire" autocomplete="new-password" required>
                <div id="password-strength" style="margin-top:8px;display:none;">
                    <div style="font-size:0.9em;margin-bottom:6px;">
                        <span id="strength-text" style="font-weight:600;"></span>
                    </div>
                    <div style="font-size:0.85em;color:#666;">
                        <div id="req-length" style="margin:3px 0;">⚪ Au moins 8 caractères</div>
                        <div id="req-upper" style="margin:3px 0;">⚪ Une majuscule (A-Z)</div>
                        <div id="req-lower" style="margin:3px 0;">⚪ Une minuscule (a-z)</div>
                        <div id="req-digit" style="margin:3px 0;">⚪ Un chiffre (0-9)</div>
                        <div id="req-special" style="margin:3px 0;">⚪ Un caractère spécial (@#!$%...)</div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input id="confirm_password" type="password" class="form-control" name="confirm_password" autocomplete="new-password" required>
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
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
