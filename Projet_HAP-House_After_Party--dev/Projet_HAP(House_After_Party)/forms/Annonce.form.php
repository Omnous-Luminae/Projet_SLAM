<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/spam_limits.php';
require_once __DIR__ . '/../classes/Tarif/Tarif.php';
require_once __DIR__ . '/../classes/Saison/Saison.php';
require_once __DIR__ . '/../classes/Compose/Compose.php';

$message = '';
$spamWarning = '';

// Pagination parameters
$perPage = 9; // Maximum 9 announcements per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Search parameters
$searchCommune = trim($_GET['search_commune'] ?? '');
$searchCommuneId = intval($_GET['search_commune_id'] ?? 0);
$searchNomBien = trim($_GET['search_nom_bien'] ?? '');
$searchTypeBien = intval($_GET['search_type_bien'] ?? 0);
$searchPrixMin = floatval($_GET['search_prix_min'] ?? 0);
$searchPrixMax = floatval($_GET['search_prix_max'] ?? 0);
$searchCouchageMin = intval($_GET['search_couchage_min'] ?? 0);
$searchCouchageMax = intval($_GET['search_couchage_max'] ?? 0);
$searchSuperficieMin = intval($_GET['search_superficie_min'] ?? 0);
$searchSuperficieMax = intval($_GET['search_superficie_max'] ?? 0);
$searchAnimaux = isset($_GET['search_animaux']) ? intval($_GET['search_animaux']) : -1;
$searchNote = intval($_GET['search_note'] ?? 0);

// Initialize variables
$biens = [];
$photos = [];
$communes = [];
$types = [];

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un bien (annonce)
        if (isset($_SESSION['user_id']) && isset($_POST['add_bien'])) {
            $nom = trim($_POST['nom_biens'] ?? '');
            $rue = trim($_POST['rue_biens'] ?? '');
            $rue_validated = trim($_POST['rue_biens_validated'] ?? '0');
            $superficie = intval($_POST['superficie_biens'] ?? 0);
            $desc = trim($_POST['description_biens'] ?? '');
            $animal = isset($_POST['animal_biens']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 0);
            $tarifs = $_POST['tarifs'] ?? [];
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $commune_text = trim($_POST['commune'] ?? '');
            $id_type = intval($_POST['id_type_biens'] ?? 0);
            // Name of creator (try several session keys)
            $createdBy = null;
            if (isset($_SESSION['user_name'])) {
                $createdBy = $_SESSION['user_name'];
            } elseif (isset($_SESSION['username'])) {
                $createdBy = $_SESSION['username'];
            } elseif (isset($_SESSION['email'])) {
                $createdBy = $_SESSION['email'];
            } elseif (isset($_SESSION['user_id'])) {
                $createdBy = 'user_' . intval($_SESSION['user_id']);
            }
            // composition will be an array of items: [ ['prestation_id'=>..., 'quantite'=>...], ... ]
            $composition = $_POST['composition'] ?? [];

            // Vérification anti-spam AVANT la validation des champs
            $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : $createdBy;
            $userRole = $_SESSION['role'] ?? null;
            $spamCheck = checkSpamLimit($pdo, $userId, $userRole);
            
            if (!$spamCheck['allowed']) {
                $message = "🚫 " . $spamCheck['message'];
            } else {
                // Validation des champs de base
                if ($nom && $rue && $superficie > 0 && $desc && $nb_couchage > 0 && ($id_commune || $commune_text) && $id_type && !empty($tarifs)) {
                    // Server-side verification that the address was validated via autocomplete
                    if ($rue_validated !== '1') {
                        $message = "Veuillez sélectionner une adresse valide via l'autocomplétion (rue).";
                    }

                    // verify commune exists in DB to avoid foreign key errors
                    if (empty($message)) {
                        $communeExists = false;
                        $final_id_commune = 0;
                        
                        if ($id_commune > 0) {
                            // Direct id_commune match (should already be the correct ID from the API)
                            try {
                                $stmtComm = $pdo->prepare('SELECT id_commune FROM Commune WHERE id_commune = ? LIMIT 1');
                                $stmtComm->execute([$id_commune]);
                                $row = $stmtComm->fetch(PDO::FETCH_ASSOC);
                                if ($row) {
                                    $communeExists = true;
                                    $final_id_commune = $row['id_commune'];
                                }
                            } catch (Exception $e) {
                                // Silent fail, try text search below
                            }
                        }
                        
                        // Fallback: try by commune text name if id not found
                        if (!$communeExists && !empty($commune_text)) {
                            $parsed_commune = preg_replace('/\s*\([^)]*\)\s*$/', '', $commune_text);
                            try {
                                $stmtComm = $pdo->prepare('SELECT id_commune FROM Commune WHERE LOWER(nom_commune) = LOWER(?) LIMIT 1');
                                $stmtComm->execute([$parsed_commune]);
                                $row = $stmtComm->fetch(PDO::FETCH_ASSOC);
                                if ($row) {
                                    $communeExists = true;
                                    $final_id_commune = $row['id_commune'];
                                }
                            } catch (Exception $e) {
                                // Silent fail
                            }
                        }
                        
                        if (!$communeExists) {
                            $message = "Commune invalide. Veuillez sélectionner une commune valide dans l'autocomplétion.";
                        }
                    }

                    // If the client sent a validation flag, we still accept it but server-side checks above protect against bypass.
                    if (!empty($message)) {
                        // skip insertion due to invalid address or commune
                    } else {
                        // proceed to insert
                        try {
                            // Try to insert with both created_by_name and created_by_id (if the column exists)
                            // New announcements are not validated by default (validated = FALSE)
                            $createdById = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
                            $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens, created_by_name, created_by_id, validated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)');
                            $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $final_id_commune, $id_type, $createdBy, $createdById]);
                        } catch (PDOException $e) {
                            try {
                                // Fallback: only created_by_name
                                $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens, created_by_name, validated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)');
                                $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $final_id_commune, $id_type, $createdBy]);
                            } catch (PDOException $e2) {
                                // Column might not exist; fallback to original INSERT without created_by info
                                $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens, validated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE)');
                                $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $final_id_commune, $id_type]);
                            }
                        }
                        $id_biens = $pdo->lastInsertId();

                        // Insérer les tarifs depuis le tableau tarifs
                        if (isset($tarifs) && is_array($tarifs)) {
                            $tarifClass = new Tarif(null, null, null, null, null, $pdo);
                            foreach ($tarifs as $tarifData) {
                                $semaine_tarif = intval($tarifData['semaine_tarif'] ?? 0);
                                $annee_tarif = intval($tarifData['annee_tarif'] ?? 0);
                                $tarif_value = floatval($tarifData['tarif'] ?? 0);
                                $id_saison = intval($tarifData['id_saison'] ?? 0);
                                if ($semaine_tarif > 0 && $annee_tarif > 0 && $tarif_value > 0 && $id_saison > 0) {
                                    $tarifClass->createTarif($id_biens, $semaine_tarif, $annee_tarif, $tarif_value, $id_saison);
                                }
                            }
                        }

                        // Upload photos if present
                        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                            $uploadDir = __DIR__ . '/../images/uploads/';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }
                            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            
                            foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                                    $fileName = basename($_FILES['photos']['name'][$key]);
                                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                    
                                    if (in_array($fileExtension, $allowedExtensions)) {
                                        $newFileName = uniqid('img_', true) . '.' . $fileExtension;
                                        $destPath = $uploadDir . $newFileName;
                                        
                                        if (move_uploaded_file($tmpName, $destPath)) {
                                            // Save photo reference to database (just filename, display code uses basename)
                                            $stmtPhoto = $pdo->prepare('INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (?, ?, ?)');
                                            $stmtPhoto->execute([$fileName, $newFileName, $id_biens]);
                                        }
                                    }
                                }
                            }
                        }

                        $message = "Annonce créée avec succès. Elle sera visible après validation par un administrateur.";
                    }
                } else {
                    $message = "Veuillez remplir tous les champs correctement.";
                }
            } // Fin du bloc anti-spam check
        }

        // Masquage d'un bien (au lieu de suppression)
        if (isset($_POST['delete_bien']) && isset($_POST['id_biens'])) {
            $id = intval($_POST['id_biens']);
            $stmt = $pdo->prepare('UPDATE Biens SET is_hidden = TRUE WHERE id_biens = ?');
            $stmt->execute([$id]);
            $message = "Bien masqué avec succès.";
        }

        // Build query for biens with search and pagination
        // Only show validated announcements to users
        $whereClause = 'WHERE (b.is_hidden IS NULL OR b.is_hidden = FALSE) AND b.validated = TRUE';
        $params = [];

        if ($searchCommuneId > 0) {
            $whereClause .= ' AND b.id_commune = ?';
            $params[] = $searchCommuneId;
        }

        if ($searchNomBien) {
            $whereClause .= ' AND b.nom_biens LIKE ?';
            $params[] = '%' . $searchNomBien . '%';
        }

        if ($searchTypeBien > 0) {
            $whereClause .= ' AND b.id_type_biens = ?';
            $params[] = $searchTypeBien;
        }

        if ($searchCouchageMin > 0) {
            $whereClause .= ' AND b.nb_couchage >= ?';
            $params[] = $searchCouchageMin;
        }

        if ($searchCouchageMax > 0) {
            $whereClause .= ' AND b.nb_couchage <= ?';
            $params[] = $searchCouchageMax;
        }

        if ($searchSuperficieMin > 0) {
            $whereClause .= ' AND b.superficie_biens >= ?';
            $params[] = $searchSuperficieMin;
        }

        if ($searchSuperficieMax > 0) {
            $whereClause .= ' AND b.superficie_biens <= ?';
            $params[] = $searchSuperficieMax;
        }

        if ($searchAnimaux >= 0) {
            $whereClause .= ' AND b.animal_biens = ?';
            $params[] = $searchAnimaux;
        }

        // ÉTAPE 1: Récupérer TOUS les biens (sans pagination pour permettre le filtrage)
        $query = "SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens $whereClause ORDER BY b.id_biens DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $allBiens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ÉTAPE 2: Récupérer les ratings pour TOUS les biens
        $ratings = [];
        if ($allBiens) {
            $ids = array_column($allBiens, 'id_biens');
            if (count($ids) > 0) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $pdo->prepare("SELECT id_biens, AVG(rating) as avg_rating, COUNT(*) as count_reviews FROM Reviews WHERE id_biens IN ($placeholders) AND validated = 1 GROUP BY id_biens");
                $stmt->execute($ids);
                $allRatings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($allRatings as $r) {
                    $ratings[$r['id_biens']] = $r;
                }
            }
        }
        
        // DEBUG: Afficher les ratings pour vérification (à retirer plus tard)
        // echo "<!-- DEBUG RATINGS: " . json_encode($ratings) . " -->";

        // ÉTAPE 3: Filtrage par prix (car les prix sont dans la table Tarif)
        if (($searchPrixMin > 0 || $searchPrixMax > 0) && $allBiens) {
            $tarifClass = new Tarif(null, null, null, null, null, $pdo);
            $allBiens = array_filter($allBiens, function($b) use ($tarifClass, $searchPrixMin, $searchPrixMax) {
                $price = $tarifClass->getLatestTarifByBien($b['id_biens']);
                if ($searchPrixMin > 0 && $price < $searchPrixMin) return false;
                if ($searchPrixMax > 0 && $price > $searchPrixMax) return false;
                return true;
            });
            $allBiens = array_values($allBiens);
        }

        // ÉTAPE 4: Filtrage par note minimale
        if ($searchNote > 0 && $allBiens) {
            $allBiens = array_filter($allBiens, function($b) use ($ratings, $searchNote) {
                if (!isset($ratings[$b['id_biens']])) return false;
                return round($ratings[$b['id_biens']]['avg_rating']) >= $searchNote;
            });
            $allBiens = array_values($allBiens);
        }

        // ÉTAPE 5: Calculer la pagination APRÈS les filtres
        $totalRecords = count($allBiens);
        $totalPages = ceil($totalRecords / $perPage);

        // ÉTAPE 6: Appliquer la pagination
        $biens = array_slice($allBiens, $offset, $perPage);

        // ÉTAPE 7: Récupérer les photos et compositions pour les biens paginés
        $photos = [];
        if ($biens) {
            $ids = array_column($biens, 'id_biens');
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM Photos WHERE id_biens IN ($placeholders)");
            $stmt->execute($ids);
            $allPhotos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allPhotos as $photo) {
                $photos[$photo['id_biens']][] = $photo;
            }
        }

        // Composition (Compose) par bien
        $compositions = [];
        if ($biens) {
            $ids = array_column($biens, 'id_biens');
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT c.*, p.lib_prestation FROM Compose c LEFT JOIN Prestation p ON c.id_prestation = p.id_prestation WHERE c.id_biens IN ($placeholders) ORDER BY c.id_biens, c.id_prestation");
            $stmt->execute($ids);
            $allComps = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allComps as $comp) {
                $compositions[$comp['id_biens']][] = $comp;
            }
        }

        // Communes, types et prestations
        $communes = $pdo->query('SELECT id_commune, nom_commune FROM Commune LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
        $types = $pdo->query('SELECT * FROM Type_Bien')->fetchAll(PDO::FETCH_ASSOC);
        $prestations = $pdo->query('SELECT id_prestation, lib_prestation FROM Prestation')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}

// expose saisons to JS for client-side auto-selection logic
$allSaisons = [];
if (isset($pdo) && $pdo) {
    try { $allSaisons = $pdo->query('SELECT id_saison, lib_saison FROM Saison')->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { $allSaisons = []; }
}
// expose valid communes to JS
$validCommunes = [];
if (isset($pdo) && $pdo) {
    try { $validCommunes = $pdo->query('SELECT id_commune FROM Commune LIMIT 100')->fetchAll(PDO::FETCH_COLUMN); } catch (Exception $e) { $validCommunes = []; }
}
?>
<script>window.saisons = <?= json_encode($allSaisons, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<script>window.validCommunes = <?= json_encode($validCommunes, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;</script>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Annonces</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../Css/annonce.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .back-to-dashboard { display: inline-block; margin: 20px; padding: 10px 20px; background: #a100b8; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .back-to-dashboard:hover { background: #4b006e; }
        /* Spacing and layout for tarif items */
        .tarif-item { margin-bottom: 12px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .tarif-item input, .tarif-item select { min-width: 120px; }
        
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
        
        /* Cartes d'annonces */
        .annonce-card-wrapper {
            position: relative;
        }
        .annonce-card {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .annonce-rating {
            transition: all 0.3s ease;
        }
        .annonce-rating:hover {
            transform: scale(1.02);
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
        }
        
        /* Bouton Favori */
        .favorite-btn {
            position: absolute;
            top: 55px;
            right: 15px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.95);
            font-size: 1.3rem;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .favorite-btn:hover {
            transform: scale(1.15);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .favorite-btn.active {
            animation: heartPulse 0.3s ease;
        }
        @keyframes heartPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <script src="../js/autocomplete.js"></script>
    <script src="../js/search_filters.js"></script>
    <script>
            function titleCase(str) {
                return str.toUpperCase();
            }

            $(document).ready(function() {
            // Diagnostic checks
            console.log('=== DIAGNOSTIC START ===');
            console.log('jQuery version:', $.fn.jquery);
            console.log('jQuery UI loaded:', typeof $.fn.autocomplete !== 'undefined');
            console.log('#commune_input exists:', $('#commune_input').length);
            console.log('#commune_id exists:', $('#commune_id').length);
            console.log('initAddCommuneAutocomplete function exists:', typeof initAddCommuneAutocomplete !== 'undefined');
            console.log('=== DIAGNOSTIC END ===');
            
            // Test API directly
            $.ajax({
                url: '../api/search_communes.php?q=pa',
                dataType: 'json',
                success: function(data) {
                    console.log('API test result for "pa":', data);
                },
                error: function(xhr, status, error) {
                    console.error('API test failed:', status, error, xhr.responseText);
                }
            });
            
            // Initialize autocomplete for search and add forms
            initSearchCommuneAutocomplete();
            initAddCommuneAutocomplete();
            initAddCompositionAutocomplete();
            
            // Visual indicator for commune selection
            function updateCommuneStatus() {
                const communeId = $('#commune_id').val();
                const $status = $('#commune_status');
                if (communeId && communeId > 0) {
                    $status.html('<span style="color: green;">✓ Sélectionnée</span>');
                } else {
                    $status.html('<span style="color: orange;">⚠ À sélectionner</span>');
                }
            }
            
            // Watch commune_id changes
            $(document).on('change', '#commune_id', function() {
                updateCommuneStatus();
            });
            
            // Listen for commune selection to load streets
            $(document).on('commune-selected', '#commune_input', function(event, code_insee) {
                console.log('Commune selected with code_insee:', code_insee);
                if (code_insee) {
                    // Clear rue field when commune changes
                    $('#rue_biens').val('');
                    $('#rue_biens_validated').val('0');
                    
                    // Get commune name and postal code for better street search
                    const communeText = $('#commune_input').val();
                    const communeId = $('#commune_id').val();
                    
                    // Load streets for this commune using both code_insee and commune info
                    fetchStreetsForCommune(code_insee, communeText, communeId);
                }
            });
            
            // Watch commune_input changes (when user types, reset commune_id)
            $(document).on('input', '#commune_input', function() {
                if (!$('#commune_id').val()) {
                    updateCommuneStatus();
                }
                // Clear streets when user types (commune not selected yet)
                streetsForCommune = [];
                streetsForCommuneFeatures = [];
                $('#rue_biens').val('');
                $('#rue_biens_validated').val('0');
            });
            
            // Initial status
            updateCommuneStatus();
            
            // Commune-specific street features and map handling
            let streetsForCommuneFeatures = [];
            let streetsForCommune = [];
            
            // Initialize rue field - disabled until commune is selected
            if (document.querySelector('#rue_biens')) {
                $('#rue_biens').attr('placeholder', 'Sélectionnez d\'abord une commune...');
                $('#rue_biens').prop('disabled', true);
            }

                let annonceMap = null;
                let annonceLayerGroup = null;

                function initAnnonceMap() {
                    if (annonceMap) return;
                    // ensure the map container is visible
                    const $mapEl = $('#annonce-map');
                    if ($mapEl.length) $mapEl.show();
                    annonceMap = L.map('annonce-map', { zoomControl: true }).setView([46.8, 2.3], 6);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(annonceMap);
                    annonceLayerGroup = L.layerGroup().addTo(annonceMap);
                }

                // Try to obtain a precise point for a feature: prefer Point from API, otherwise compute midpoint of LineString
                function getPrecisePointForFeature(feature, callback) {
                    if (!feature) return callback(null);
                    // if feature already has a Point geometry, use it
                    if (feature.geometry && feature.geometry.type === 'Point') {
                        const c = feature.geometry.coordinates;
                        return callback([c[1], c[0]]);
                    }

                    // try to query adresse.data.gouv.fr with the street name + city/postcode for a more precise point
                    let q = '';
                    let citycode = null;
                    if (feature.properties) {
                        const name = feature.properties.name || feature.properties.label || '';
                        const city = feature.properties.city || '';
                        const postcode = feature.properties.postcode || '';
                        q = (name + ' ' + city + ' ' + postcode).trim();
                        citycode = feature.properties.citycode || null;
                    }

                    if (q) {
                        $.ajax({
                            url: 'https://api-adresse.data.gouv.fr/search/',
                            dataType: 'json',
                            data: { q: q, citycode: citycode || undefined, limit: 5 },
                            success: function(data) {
                                const feats = data && data.features ? data.features : [];
                                // prefer a Point geometry
                                let p = feats.find(f => f.geometry && f.geometry.type === 'Point');
                                if (p && p.geometry && p.geometry.coordinates) {
                                    const cc = p.geometry.coordinates;
                                    return callback([cc[1], cc[0]]);
                                }
                                // else fall back to midpoint from original feature
                                return callback(midpointFromFeature(feature));
                            },
                            error: function() {
                                return callback(midpointFromFeature(feature));
                            }
                        });
                    } else {
                        return callback(midpointFromFeature(feature));
                    }
                }

                function midpointFromFeature(feature) {
                    if (!feature || !feature.geometry) return null;
                    const geom = feature.geometry;
                    if (geom.type === 'LineString') {
                        const coords = geom.coordinates;
                        const mid = Math.floor(coords.length / 2);
                        const c = coords[mid];
                        return [c[1], c[0]];
                    }
                    if (geom.type === 'MultiLineString') {
                        // choose longest part
                        let longest = [];
                        geom.coordinates.forEach(part => { if (part.length > longest.length) longest = part; });
                        if (longest.length) {
                            const mid = Math.floor(longest.length / 2);
                            const c = longest[mid];
                            return [c[1], c[0]];
                        }
                    }
                    if (geom.type === 'Polygon') {
                        const ring = geom.coordinates[0] || [];
                        const mid = Math.floor(ring.length / 2);
                        const c = ring[mid];
                        return [c[1], c[0]];
                    }
                    return null;
                }

                function showStreetOnMap(feature) {
                    if (!feature) return;
                    initAnnonceMap();
                    annonceLayerGroup.clearLayers();
                    const geom = feature.geometry;
                    // draw geometry if available (line/poly)
                    if (geom) {
                        if (geom.type === 'LineString') {
                            const latlngs = geom.coordinates.map(c => [c[1], c[0]]);
                            L.polyline(latlngs, { color: '#3388ff', weight: 6, opacity: 0.6 }).addTo(annonceLayerGroup);
                        } else if (geom.type === 'MultiLineString') {
                            geom.coordinates.forEach(part => {
                                const latlngs = part.map(c => [c[1], c[0]]);
                                L.polyline(latlngs, { color: '#3388ff', weight: 6, opacity: 0.6 }).addTo(annonceLayerGroup);
                            });
                        } else if (geom.type === 'Polygon' || geom.type === 'MultiPolygon') {
                            const coords = geom.type === 'Polygon' ? geom.coordinates[0] : geom.coordinates[0][0];
                            const latlngs = coords.map(c => [c[1], c[0]]);
                            L.polygon(latlngs, { color: '#3388ff', weight: 2, opacity: 0.8, fillOpacity: 0.15 }).addTo(annonceLayerGroup);
                        }
                    }

                    // get a precise point (address point if available, else midpoint) and draw a small zone and marker
                    getPrecisePointForFeature(feature, function(point) {
                        if (!point) {
                            // if no point, just fit to geometry bounds if any
                            try { const bounds = annonceLayerGroup.getBounds(); if (bounds.isValid()) annonceMap.fitBounds(bounds.pad(0.2)); } catch (e) {}
                            return;
                        }
                        const marker = L.circleMarker(point, { radius: 6, color: '#ff3333', fillColor: '#ff6666', fillOpacity: 1 }).addTo(annonceLayerGroup);
                        // small zone (buffer) to represent the announcement's street zone
                        const zone = L.circle(point, { radius: 30, color: '#ff3333', weight: 1, opacity: 0.6, fillOpacity: 0.12 }).addTo(annonceLayerGroup);
                        // fit map to show both geometry and point
                        const groupBounds = annonceLayerGroup.getBounds();
                        if (groupBounds.isValid()) annonceMap.fitBounds(groupBounds.pad(0.2)); else annonceMap.setView(point, 17);
                    });
                }

                function fetchStreetsForCommune(code_insee, communeText, communeId) {
                    if (!code_insee && !communeText) {
                        console.warn('fetchStreetsForCommune called without citycode or commune name');
                        return;
                    }
                    
                    console.log('🔍 Récupération de TOUTES les rues pour:', { code_insee, communeText, communeId });
                    
                    // Show loading state
                    $('#rue_biens').prop('disabled', true);
                    $('#rue_biens').attr('placeholder', 'Chargement de toutes les rues...');
                    
                    // Extract postal code from commune text (format: "COMMUNE (75001)")
                    let postalCode = null;
                    let communeName = communeText;
                    if (communeText) {
                        const match = communeText.match(/\((\d{5})\)/);
                        if (match) {
                            postalCode = match[1];
                            communeName = communeText.replace(/\s*\([^)]*\)\s*$/, '').trim();
                        }
                    }
                    
                    console.log('Extracted:', { postalCode, communeName, code_insee });
                    
                    // Use reverse geocoding API endpoint which returns ALL streets at once
                    // Try multiple approaches
                    const apiUrl = 'https://api-adresse.data.gouv.fr/search/';
                    
                    // Strategy: Request with very high limit or use multiple character searches
                    function fetchWithWildcard() {
                        console.log('🎯 Stratégie: Recherche avec caractères communs pour obtenir toutes les rues');
                        
                        const commonPrefixes = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 
                                                'rue', 'avenue', 'boulevard', 'place', 'chemin', 'impasse', 'allée'];
                        let allFeatures = [];
                        let completed = 0;
                        
                        console.log('📝 Lancement de', commonPrefixes.length, 'recherches parallèles...');
                        
                        commonPrefixes.forEach(function(prefix, index) {
                            const params = {
                                q: prefix,
                                type: 'street',
                                limit: 50
                            };
                            
                            if (code_insee && code_insee.length === 5) {
                                params.citycode = code_insee;
                            } else if (postalCode) {
                                params.postcode = postalCode;
                            }
                            
                            $.ajax({
                                url: apiUrl,
                                dataType: 'json',
                                data: params,
                                success: function(data) {
                                    const features = data && data.features ? data.features : [];
                                    if (features.length > 0) {
                                        console.log('✅ "' + prefix + '":', features.length, 'rues');
                                        allFeatures = allFeatures.concat(features);
                                    }
                                },
                                error: function() {
                                    console.warn('⚠️ Erreur pour préfixe:', prefix);
                                },
                                complete: function() {
                                    completed++;
                                    if (completed === commonPrefixes.length) {
                                        // All requests completed
                                        console.log('🎯 Total brut récupéré:', allFeatures.length, 'features');
                                        processStreetResults(allFeatures, postalCode);
                                    }
                                }
                            });
                        });
                    }
                    
                    // Start the wildcard search
                    fetchWithWildcard();
                    
                    function processStreetResults(features, postalCode) {
                        console.log('📋 Traitement de', features.length, 'features reçues');
                        
                        // Filter to keep only streets (type=street or housenumber)
                        let filteredFeatures = features.filter(f => {
                            if (!f.properties) return false;
                            const type = f.properties.type;
                            // Keep street, housenumber, but not city, municipality, etc.
                            return type === 'street' || type === 'housenumber' || type === 'locality';
                        });
                        console.log('🛣️ Filtrage par type (street/housenumber):', features.length, '→', filteredFeatures.length);
                        
                        // Further filter by postal code if available
                        if (postalCode) {
                            const before = filteredFeatures.length;
                            filteredFeatures = filteredFeatures.filter(f => 
                                f.properties && f.properties.postcode === postalCode
                            );
                            console.log('🔍 Filtrage par code postal', postalCode + ':', before, '→', filteredFeatures.length, 'rues');
                        }
                        
                        streetsForCommuneFeatures = filteredFeatures;
                        // keep only the street name for the local autocomplete
                        streetsForCommune = filteredFeatures.map(function(f) {
                            return (f && f.properties && (f.properties.name || f.properties.label)) ? (f.properties.name || f.properties.label) : null;
                        }).filter(Boolean);
                        
                        // Remove duplicates
                        const beforeDedup = streetsForCommune.length;
                        streetsForCommune = [...new Set(streetsForCommune)];
                        console.log('🗑️ Dédoublonnage:', beforeDedup, '→', streetsForCommune.length, 'rues uniques');
                        
                        if (streetsForCommune.length > 0) {
                            console.log('✅ Autocomplétion activée avec', streetsForCommune.length, 'rues');
                            console.log('   Exemples:', streetsForCommune.slice(0, 5));
                            setRueAutocompleteFromStreets();
                            // Enable rue field
                            $('#rue_biens').prop('disabled', false);
                            $('#rue_biens').attr('placeholder', 'Tapez le nom d\'une rue... (' + streetsForCommune.length + ' rues disponibles)');
                            $('#rue_biens').focus();
                        } else {
                            console.warn('⚠️ Aucune rue après filtrage');
                            enableManualEntry('aucune suggestion disponible');
                        }
                    }
                    
                    function enableManualEntry(reason) {
                        console.log('✍️ Mode manuel activé:', reason);
                        $('#rue_biens').prop('disabled', false);
                        $('#rue_biens').attr('placeholder', 'Tapez le nom de la rue (' + reason + ')');
                        $('#rue_biens_validated').val('1');
                    }
                }

                function setRueAutocompleteFromStreets() {
                    try { $('#rue_biens').autocomplete('destroy'); } catch (e) {}
                    $('#rue_biens').autocomplete({
                        source: function(request, response) {
                            const term = (request.term || '').toLowerCase();
                            const results = streetsForCommune.filter(s => s.toLowerCase().indexOf(term) !== -1).slice(0, 50).map(s => ({ label: s, value: s }));
                            response(results);
                        },
                        minLength: 1,
                        select: function(event, ui) {
                            $('#rue_biens').val(ui.item.value);
                            if ($('#rue_biens_validated').length) $('#rue_biens_validated').val('1');
                            
                            // Find the feature to show on map
                            const f = streetsForCommuneFeatures.find(function(feat) {
                                const lab = feat && feat.properties && (feat.properties.label || feat.properties.name) ? (feat.properties.label || feat.properties.name) : null;
                                return lab === ui.item.value;
                            });
                            
                            if (f) {
                                // Don't update commune - it's already selected
                                // Just show the street on the map
                                showStreetOnMap(f);
                            }
                            return false;
                        }
                    });
                }

                // Note: We don't use the generic onChange handler for commune_id anymore
                // because the commune-selected event already handles loading streets

                // Reset validation flag when typing in rue field
                $(document).on('input', '#rue_biens', function() {
                    if ($('#rue_biens_validated').length) $('#rue_biens_validated').val('0');
                });

                // Prevent submit if address not validated
                $('#addBienForm').on('submit', function(e) {
                    const communeId = $('#commune_id').val();
                    console.log('Form submit - commune_id value:', communeId);
                    
                    // Check if commune_id is set and is a valid numeric ID
                    if (!communeId || isNaN(communeId) || parseInt(communeId) <= 0) {
                        alert('⚠️ Veuillez sélectionner une commune valide.\n\nÉtapes :\n1. Tapez le nom de la commune dans le champ "Commune"\n2. Sélectionnez une commune dans la liste qui apparaît\n3. Vérifiez que le statut affiche "✓ Sélectionnée"');
                        console.error('Invalid commune_id:', communeId);
                        // Scroll to commune field
                        $('#commune_input').focus();
                        e.preventDefault();
                        return false;
                    }
                    
                    // Check rue field - allow manual entry if validated is 1 OR if there's a value
                    const rueValue = $('#rue_biens').val();
                    const rueValidated = $('#rue_biens_validated').val();
                    
                    if (!rueValue || rueValue.trim() === '') {
                        alert('⚠️ Veuillez saisir une adresse de rue.');
                        $('#rue_biens').focus();
                        e.preventDefault();
                        return false;
                    }
                    
                    // Only check validation if streets were loaded (validated = 0 means user is typing)
                    if (streetsForCommune.length > 0 && rueValidated !== '1') {
                        alert('Veuillez sélectionner une rue valide dans la liste d\'autocomplétion.\n\nÉtapes :\n1. Assurez-vous d\'avoir sélectionné une commune\n2. Tapez le nom de la rue\n3. Sélectionnez une rue dans la liste qui apparaît');
                        $('#rue_biens').focus();
                        e.preventDefault();
                        return false;
                    }
                    
                    console.log('Form validation passed, submitting...');
                });

            // Gestion des tarifs dynamiques
            let tarifIndex = 1;
            $('#add-tarif').on('click', function() {
                const newTarif = `
                    <div class="tarif-item">
                        <input type="number" name="tarifs[${tarifIndex}][semaine_tarif]" placeholder="Semaine" min="1" max="52" value="<?= date('W') ?>" required>
                        <input type="number" name="tarifs[${tarifIndex}][annee_tarif]" placeholder="Année" min="2020" max="2030" value="<?= date('Y') ?>" required>
                        <input type="number" step="0.01" name="tarifs[${tarifIndex}][tarif]" placeholder="Tarif (€) par semaine" required>
                        <select name="tarifs[${tarifIndex}][saison_mode]" class="saison-mode">
                            <option value="auto">Auto</option>
                            <option value="haute">Forcer : Haute</option>
                            <option value="basse">Forcer : Basse</option>
                        </select>
                        <select name="tarifs[${tarifIndex}][id_saison]" required>
                            <option value="">-- Sélectionnez une saison --</option>
                            <?php
                            $saisons_for_template = isset($pdo) ? $pdo->query('SELECT id_saison, lib_saison FROM Saison')->fetchAll(PDO::FETCH_ASSOC) : [];
                            foreach ($saisons_for_template as $saison): ?>
                                <option value="<?= $saison['id_saison'] ?>"><?= htmlspecialchars($saison['lib_saison']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="remove-tarif">Supprimer</button>
                        <small style="display:block;color:#666;margin-top:6px;">Vous pouvez laisser la saison se remplir automatiquement en fonction de la semaine (modifiable manuellement).</small>
                    </div>
                `;
                $('#tarifs-container').append(newTarif);
                // After append, wire up auto-fill behavior for the newly added tarif-item
                const $newItem = $('#tarifs-container .tarif-item').last();
                const $weekInput = $newItem.find('input[name$="[semaine_tarif]"]');
                const $seasonSelect = $newItem.find('select[name$="[id_saison]"]');
                const $modeSelect = $newItem.find('select.saison-mode');
                // mark that user hasn't manually changed the season yet
                $seasonSelect.data('userSet', false);
                $seasonSelect.on('change', function(e){ if (e && e.originalEvent) $(this).data('userSet', true); });
                // mode select handling for newly added item
                $modeSelect.on('change', function() {
                    const mode = $(this).val();
                    if (mode === 'haute' || mode === 'basse') {
                        const sid = findSeasonIdByMode(mode);
                        if (sid) {
                            $seasonSelect.val(sid);
                            $seasonSelect.data('userSet', true);
                        }
                    } else {
                        // auto mode
                        $seasonSelect.data('userSet', false);
                        const val = parseInt($weekInput.val(), 10);
                        if (!isNaN(val)) {
                            const sid = guessSeasonIdFromWeek(val);
                            if (sid) $seasonSelect.val(sid);
                        }
                    }
                });
                $weekInput.on('input change', function(){
                    const val = parseInt($(this).val(), 10);
                    if (isNaN(val) || val < 1 || val > 52) return;
                    const mode = $modeSelect.val();
                    if (mode === 'haute' || mode === 'basse') return; // forced mode
                    if ($seasonSelect.data('userSet')) return; // user chose season manually
                    const sid = guessSeasonIdFromWeek(val);
                    if (sid) $seasonSelect.val(sid);
                });
                tarifIndex++;
            });

            $(document).on('click', '.remove-tarif', function() {
                $(this).closest('.tarif-item').remove();
            });

            // Helper: find saison id by mode ('haute' or 'basse')
            function findSeasonIdByMode(mode) {
                if (!window.saisons || !window.saisons.length) return null;
                const key = (mode || '').toLowerCase();
                const list = window.saisons.map(s => ({ id: s.id_saison, name: (s.lib_saison || '').toLowerCase() }));
                if (key === 'haute') {
                    const hc = list.find(s => s.name.includes('haute') || s.name.includes('été') || s.name.includes('summer'));
                    return hc ? hc.id : null;
                }
                if (key === 'basse') {
                    const lc = list.find(s => s.name.includes('basse') || s.name.includes('hiver') || s.name.includes('winter'));
                    return lc ? lc.id : null;
                }
                return null;
            }

            // Helper: guess a season id from week number using window.saisons
            // Supports printemps, été (haute), automne, hiver
            function guessSeasonIdFromWeek(week) {
                if (!window.saisons || !window.saisons.length) return null;
                const list = window.saisons.map(s => ({ id: s.id_saison, name: (s.lib_saison || '').toLowerCase() }));
                const printemps = list.find(s => s.name.includes('printemps') || s.name.includes('spring'));
                const ete = list.find(s => s.name.includes('ete') || s.name.includes('été') || s.name.includes('haute') || s.name.includes('summer'));
                const automne = list.find(s => s.name.includes('automne') || s.name.includes('autumn'));
                const hiver = list.find(s => s.name.includes('hiver') || s.name.includes('winter'));

                // week ranges (ISO weeks):
                // printemps: weeks 12-25
                // été (haute): weeks 26-35
                // automne: weeks 36-48
                // hiver: weeks 49-53 and 1-11
                let chosen = null;
                if (week >= 12 && week <= 25) {
                    chosen = printemps ? printemps.id : null;
                } else if (week >= 26 && week <= 35) {
                    chosen = ete ? ete.id : null;
                } else if (week >= 36 && week <= 48) {
                    chosen = automne ? automne.id : null;
                } else {
                    // weeks 49-53 and 1-11 -> hiver
                    chosen = hiver ? hiver.id : null;
                }
                console.debug('guessSeasonIdFromWeek:', { week: week, printemps: printemps ? printemps.id : null, ete: ete ? ete.id : null, automne: automne ? automne.id : null, hiver: hiver ? hiver.id : null, chosen: chosen });
                return chosen;
            }

            // Initialize existing tarif items so auto-fill and mode select work
            $('#tarifs-container .tarif-item').each(function() {
                const $item = $(this);
                const $sel = $item.find('select[name$="[id_saison]"]');
                const $weekInput = $item.find('input[name$="[semaine_tarif]"]');
                const $modeSelect = $item.find('select.saison-mode');
                if ($sel.length) $sel.data('userSet', false);
                if ($modeSelect.length) {
                    $modeSelect.off('change').on('change', function() {
                        const mode = $(this).val();
                        if (mode === 'haute' || mode === 'basse') {
                            const sid = findSeasonIdByMode(mode);
                            if (sid) {
                                $sel.val(sid);
                                $sel.data('userSet', true);
                            }
                        } else {
                            $sel.data('userSet', false);
                            const val = parseInt($weekInput.val(), 10);
                            if (!isNaN(val)) {
                                const sid = guessSeasonIdFromWeek(val);
                                if (sid) $sel.val(sid);
                            }
                        }
                    });
                }
                if ($weekInput.length) {
                    $weekInput.off('input change').on('input change', function() {
                        const val = parseInt($(this).val(), 10);
                        if (isNaN(val) || val < 1 || val > 52) return;
                        const mode = $modeSelect.length ? $modeSelect.val() : 'auto';
                        if (mode === 'haute' || mode === 'basse') return;
                        if ($sel.data('userSet')) return;
                        const sid = guessSeasonIdFromWeek(val);
                        if (sid) $sel.val(sid);
                    });
                }
            });

            // Delegate: whenever a week input changes, attempt to auto-fill the corresponding season
            $(document).on('input change', '#tarifs-container input[name$="[semaine_tarif]"]', function() {
                const $week = $(this);
                const $tarifItem = $week.closest('.tarif-item');
                const $seasonSelect = $tarifItem.find('select[name$="[id_saison]"]');
                if (!$seasonSelect.length) return;
                if ($seasonSelect.data('userSet')) return; // user manually set season
                const val = parseInt($week.val(), 10);
                if (isNaN(val) || val < 1 || val > 52) return;
                const sid = guessSeasonIdFromWeek(val);
                if (sid) $seasonSelect.val(sid);
            });

            // Delegate: when user explicitly changes a season select, mark it to prevent auto-overwrite
            $(document).on('change', '#tarifs-container select[name$="[id_saison]"]', function(e) {
                if (e && e.originalEvent) $(this).data('userSet', true);
            });



            $(document).on('click', '.remove-prestation', function() {
                $(this).closest('.prestation-item').remove();
            });
            // Composition subform dynamic behavior
            let compIndex = 0;
            $('#add-composition').on('click', function() {
                const newComp = `
                    <div class="composition-item" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                        <input type="text" name="composition[${compIndex}][label]" placeholder="Ex: 2 chambres" required style="flex:1;min-width:140px;padding:6px;border-radius:6px;border:1px solid #ccc;">
                        <input type="number" name="composition[${compIndex}][quantite]" min="1" value="1" required style="width:80px;">
                        <button type="button" class="remove-composition">Supprimer</button>
                    </div>
                `;
                $('#composition-container').append(newComp);
                compIndex++;
            });

            $(document).on('click', '.remove-composition', function() {
                $(this).closest('.composition-item').remove();
            });

            // Photos : drag & drop + multiple selection handling
            const photoDT = new DataTransfer();
            const $photosInput = $('<input id="photos_input" type="file" name="photos[]" accept="image/*" multiple style="display:none;">');
            $('#photos-container').empty().append($photosInput);

            function refreshInputFiles() {
                const input = document.getElementById('photos_input');
                try { input.files = photoDT.files; } catch (e) { /* some browsers restrict setting files directly */ }
            }

            function renderPreviews() {
                $('#photo-previews').empty();
                for (let i = 0; i < photoDT.files.length; i++) {
                    const file = photoDT.files[i];
                    const idx = i;
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const $preview = $('<div class="photo-preview" data-preview-index="' + idx + '" style="position:relative;width:100px;height:70px;border:1px solid #ddd;padding:4px;display:flex;align-items:center;justify-content:center;background:#fff;border-radius:6px;overflow:hidden;margin-right:8px;margin-bottom:8px;"></div>');
                        const $img = $('<img src="' + evt.target.result + '" style="max-width:100%;max-height:100%;display:block;">');
                        const $remove = $('<button type="button" class="remove-preview" data-remove-index="' + idx + '" style="position:absolute;top:2px;right:2px;background:#fff;border:0;padding:2px 6px;border-radius:4px;cursor:pointer;">✕</button>');
                        $remove.on('click', function(){
                            const removeIndex = parseInt($(this).attr('data-remove-index'));
                            const newDT = new DataTransfer();
                            for (let j = 0; j < photoDT.files.length; j++) {
                                if (j === removeIndex) continue;
                                newDT.items.add(photoDT.files[j]);
                            }
                            // replace contents of photoDT by copying
                            while (photoDT.items.length > 0) photoDT.items.remove(0);
                            for (let k = 0; k < newDT.files.length; k++) photoDT.items.add(newDT.files[k]);
                            refreshInputFiles();
                            renderPreviews();
                        });
                        $preview.append($img).append($remove);
                        $('#photo-previews').append($preview);
                    };
                    reader.readAsDataURL(file);
                }
            }

            // Add files to DataTransfer and render previews
            function addFilesToDT(files) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
                let rejectedFiles = [];
                
                for (let f of files) {
                    // Validate file type
                    const fileName = f.name.toLowerCase();
                    const fileType = f.type.toLowerCase();
                    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                    const hasValidMimeType = allowedTypes.includes(fileType);
                    
                    if (!hasValidExtension || !hasValidMimeType) {
                        rejectedFiles.push(f.name);
                        continue;
                    }
                    
                    // skip duplicates (name+size)
                    let exists = false;
                    for (let i = 0; i < photoDT.files.length; i++) { if (photoDT.files[i].name === f.name && photoDT.files[i].size === f.size) { exists = true; break; } }
                    if (!exists) photoDT.items.add(f);
                }
                
                if (rejectedFiles.length > 0) {
                    alert('❌ Fichiers rejetés (format non autorisé) :\n' + rejectedFiles.join('\n') + '\n\nFormats acceptés : JPG, PNG, GIF, WEBP');
                }
                
                refreshInputFiles();
                renderPreviews();
            }

            // open file selector
            $('#add-photo-input').off('click').on('click', function() { $('#photos_input').trigger('click'); });

            // when user selects files via picker
            $(document).on('change', '#photos_input', function(e) {
                const files = e.target.files;
                addFilesToDT(files);
            });

            // drag & drop support on photos container area
            const $dropZone = $('<div id="photo-dropzone" style="border:2px dashed #ccc;border-radius:6px;padding:16px;text-align:center;cursor:pointer;background:#fafafa;">Glissez-déposez vos photos ici ou cliquez pour sélectionner</div>');
            $('#photos-container').prepend($dropZone);
            $dropZone.on('click', function(){ $('#photos_input').trigger('click'); });

            $dropZone.on('dragover', function(e){ e.preventDefault(); e.originalEvent.dataTransfer.dropEffect = 'copy'; $(this).css('border-color','#6aa6ff'); });
            $dropZone.on('dragleave', function(e){ e.preventDefault(); $(this).css('border-color','#ccc'); });
            $dropZone.on('drop', function(e){ e.preventDefault(); $(this).css('border-color','#ccc'); const dt = e.originalEvent.dataTransfer; if (dt && dt.files && dt.files.length) { addFilesToDT(dt.files); } });
        });

        // Fonction pour masquer/afficher le formulaire d'annonces
        function toggleAnnonceForm() {
            const formContainer = document.getElementById('annonce-form-container');
            const toggleIcon = document.getElementById('toggle-form-icon');
            const toggleText = document.getElementById('toggle-form-text');
            
            if (formContainer.style.display === 'none') {
                formContainer.style.display = 'block';
                toggleIcon.textContent = '▼';
                toggleText.textContent = 'Masquer le formulaire';
            } else {
                formContainer.style.display = 'none';
                toggleIcon.textContent = '▶';
                toggleText.textContent = 'Créer une nouvelle annonce';
            }
        }

        // Fonction pour masquer/afficher les filtres
        function toggleFilters() {
            const filterRows = document.querySelectorAll('.filter-row');
            const toggleIcon = document.getElementById('toggle-icon');
            const toggleText = document.getElementById('toggle-text');
            
            filterRows.forEach(row => {
                if (row.style.display === 'none') {
                    row.style.display = 'grid';
                    toggleIcon.textContent = '▼';
                    toggleText.textContent = 'Masquer les filtres';
                } else {
                    row.style.display = 'none';
                    toggleIcon.textContent = '▶';
                    toggleText.textContent = 'Afficher les filtres';
                }
            });
        }

        // Au chargement de la page, vérifier s'il y a des filtres actifs
        document.addEventListener('DOMContentLoaded', function() {
            const hasActiveFilters = <?= 
                $searchNomBien || $searchCommuneId || $searchTypeBien || 
                $searchPrixMin || $searchPrixMax || $searchCouchageMin || 
                $searchCouchageMax || $searchSuperficieMin || $searchSuperficieMax || 
                $searchAnimaux >= 0 || $searchNote 
                ? 'true' : 'false' 
            ?>;
            
            // Si aucun filtre actif, masquer les filtres par défaut
            if (!hasActiveFilters) {
                const filterRows = document.querySelectorAll('.filter-row');
                filterRows.forEach(row => {
                    row.style.display = 'none';
                });
                document.getElementById('toggle-icon').textContent = '▶';
                document.getElementById('toggle-text').textContent = 'Afficher les filtres';
            }

            // Auto-complétion pour la recherche de commune dans les filtres
            if (typeof initSearchCommuneAutocomplete === 'function') {
                initSearchCommuneAutocomplete();
            }
        });
    </script>
</head>
<body>
    <?php include '../../theme_toggle.php'; ?>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Annonces</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout d'une annonce -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Bouton toggle pour le formulaire -->
        <div style="text-align: center; margin-bottom: 20px;">
            <button type="button" onclick="toggleAnnonceForm()" style="background: linear-gradient(135deg, #a100b8, #d100e8); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(161, 0, 184, 0.3); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(161, 0, 184, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(161, 0, 184, 0.3)'">
                <span id="toggle-form-icon">▶</span>
                <span id="toggle-form-text">Créer une nouvelle annonce</span>
            </button>
        </div>
        
        <div id="annonce-form-container" style="display: none;">
        <?php 
        // Afficher les statistiques de publication
        $currentUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
        $currentUserRole = $_SESSION['role'] ?? null;
        if ($currentUserId) {
            $userStats = getUserAnnouncementStats($pdo, $currentUserId);
            $limitCheck = checkSpamLimit($pdo, $currentUserId, $currentUserRole);
            
            // Afficher un panneau d'information si pas admin
            if (!in_array($currentUserRole, EXEMPT_ROLES)):
        ?>
        <div style="background: linear-gradient(135deg, rgba(161, 0, 184, 0.08), rgba(209, 0, 232, 0.08)); border-left: 4px solid #a100b8; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
            <h4 style="margin: 0 0 10px 0; color: #a100b8; font-size: 1rem;">📊 Vos statistiques de publication</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; font-size: 0.9rem;">
                <div>
                    <strong>Aujourd'hui:</strong> 
                    <span style="color: <?= $userStats['today'] >= MAX_ANNOUNCEMENTS_PER_DAY ? '#dc3545' : '#28a745' ?>">
                        <?= $userStats['today'] ?>/<?= MAX_ANNOUNCEMENTS_PER_DAY ?>
                    </span>
                </div>
                <div>
                    <strong>Cette semaine:</strong> 
                    <span style="color: <?= $userStats['week'] >= MAX_ANNOUNCEMENTS_PER_WEEK ? '#dc3545' : '#28a745' ?>">
                        <?= $userStats['week'] ?>/<?= MAX_ANNOUNCEMENTS_PER_WEEK ?>
                    </span>
                </div>
                <div>
                    <strong>Ce mois:</strong> 
                    <span style="color: <?= $userStats['month'] >= MAX_ANNOUNCEMENTS_PER_MONTH ? '#dc3545' : '#28a745' ?>">
                        <?= $userStats['month'] ?>/<?= MAX_ANNOUNCEMENTS_PER_MONTH ?>
                    </span>
                </div>
                <div>
                    <strong>Total:</strong> <?= $userStats['total'] ?>
                </div>
            </div>
            <?php if ($userStats['last_post']): 
                $lastPostDate = new DateTime($userStats['last_post']);
                $now = new DateTime();
                $interval = $now->diff($lastPostDate);
                $minutesAgo = ($now->getTimestamp() - $lastPostDate->getTimestamp()) / 60;
            ?>
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(161, 0, 184, 0.2); font-size: 0.85rem; color: #666;">
                Dernière publication: il y a 
                <?php 
                if ($minutesAgo < 60) {
                    echo ceil($minutesAgo) . ' minute(s)';
                } elseif ($minutesAgo < 1440) {
                    echo ceil($minutesAgo / 60) . ' heure(s)';
                } else {
                    echo ceil($minutesAgo / 1440) . ' jour(s)';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
        <?php 
            endif;
        }
        ?>
        <div class="form-section">
            <h3>Ajouter une nouvelle annonce</h3>
            <form id="addBienForm" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nom_biens">Nom du bien:</label>
                    <input type="text" id="nom_biens" name="nom_biens" required>
                </div>
                 <div class="form-group">
                    <label for="commune_input">Commune: <span id="commune_status" style="font-size: 0.85em;"></span></label>
                    <input type="text" id="commune_input" name="commune" placeholder="Tapez ou sélectionnez une commune..." required>
                    <input type="hidden" id="commune_id" name="id_commune">
                    <small style="color: #666; display: block; margin-top: 4px;">
                        💡 Conseil : Sélectionnez d'abord la commune manuellement, puis l'adresse de la rue
                    </small>
                </div>
                <div class="form-group">
                    <label for="rue_biens">Rue: <span style="font-size: 0.85em; color: #666;">(sélectionnez d'abord une commune)</span></label>
                    <input type="text" id="rue_biens" name="rue_biens" required>
                    <input type="hidden" id="rue_biens_validated" name="rue_biens_validated" value="0">
                </div>
                <div id="annonce-map" style="height:240px;margin-bottom:12px;border:1px solid #ddd;border-radius:6px;display:none;"></div>
                <div class="form-group">
                    <label for="superficie_biens">Superficie (m²):</label>
                    <input type="number" id="superficie_biens" name="superficie_biens" required>
                </div>
                <div class="form-group">
                    <label for="description_biens">Description:</label>
                    <textarea id="description_biens" name="description_biens" required></textarea>
                </div>
                <div class="form-group">
                    <label for="nb_couchage">Nombre de couchages:</label>
                    <input type="number" id="nb_couchage" name="nb_couchage" required>
                </div>
                <details class="subform">
                    <summary>Tarifs (cliquez pour ouvrir)</summary>
                    <div id="tarifs-container">
                        <!-- Tarifs will be added here dynamically -->
                    </div>
                    <button type="button" id="add-tarif">Ajouter un tarif</button>
                </details>
                <details class="subform">
                    <summary>Composition (cliquez pour ouvrir)</summary>
                    <div id="composition-container">
                        <!-- Composition items will be added here -->
                    </div>
                    <button type="button" id="add-composition">Ajouter un élément de composition</button>
                    <p style="font-size:0.9em;color:#666;margin-top:8px;">Ex : 2 chambres, 1 salle de bain, 1 salon. Choisissez un type et saisissez la quantité.</p>
                </details>
                <div class="form-group">
                    <label for="id_type_biens">Type de bien:</label>
                    <select id="id_type_biens" name="id_type_biens" required>
                        <option value="">-- Sélectionnez un type --</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= $type['id_type_biens'] ?>"><?= htmlspecialchars($type['designation_type_bien']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="animal_biens">Animaux acceptés:</label>
                    <input type="checkbox" id="animal_biens" name="animal_biens">
                </div>
                <div class="form-group">
                    <label for="photos_input">Photos:</label>
                    <div id="photos-container"></div>
                    <div id="photo-previews" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;"></div>
                    <div style="margin-top:8px;">
                        <button type="button" id="add-photo-input">Sélectionner des photos</button>
                        <small style="display:block;color:#666;margin-top:6px;">Glissez-déposez vos photos ou sélectionnez plusieurs fichiers à la fois.</small>
                    </div>
                </div>

                <input type="submit" name="add_bien" value="Ajouter l'annonce">
            </form>
        </div>
        </div> <!-- Fin du conteneur formulaire -->
        <?php else: ?>
            <p>Vous devez être connecté pour ajouter une annonce. <a href="../auth/connexion.php">Se connecter</a></p>
        <?php endif; ?>

        <div class="bien-list">
            <h3>Annonces publiées</h3>

            <!-- Search and Filter Section -->
            <div class="search-section-advanced">
                <h3 style="text-align:center;color:#a100b8;margin-bottom:24px;font-size:1.5em;">🔍 Recherche Avancée</h3>
                <form method="get" class="advanced-search-form">
                    <!-- Ligne 1: Nom et Commune -->
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search_nom_bien">Nom du bien</label>
                            <input type="text" id="search_nom_bien" name="search_nom_bien" value="<?= htmlspecialchars($searchNomBien) ?>" placeholder="Ex: Villa Paradis...">
                        </div>
                        <div class="filter-group">
                            <label for="search_commune_input">Commune</label>
                            <input type="text" id="search_commune_input" name="search_commune" value="<?= htmlspecialchars($searchCommune) ?>" placeholder="Ex: Paris, Lyon...">
                            <input type="hidden" id="search_commune_id" name="search_commune_id" value="<?= $searchCommuneId ?>">
                        </div>
                        <div class="filter-group">
                            <label for="search_type_bien">Type de bien</label>
                            <select id="search_type_bien" name="search_type_bien">
                                <option value="">Tous les types</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= $type['id_type_biens'] ?>" <?= ($searchTypeBien == $type['id_type_biens']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['designation_type_bien']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Ligne 2: Prix et Couchages -->
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search_prix_min">Prix min (€/sem)</label>
                            <input type="number" id="search_prix_min" name="search_prix_min" value="<?= $searchPrixMin > 0 ? $searchPrixMin : '' ?>" placeholder="Ex: 100" min="0" step="10">
                        </div>
                        <div class="filter-group">
                            <label for="search_prix_max">Prix max (€/sem)</label>
                            <input type="number" id="search_prix_max" name="search_prix_max" value="<?= $searchPrixMax > 0 ? $searchPrixMax : '' ?>" placeholder="Ex: 500" min="0" step="10">
                        </div>
                        <div class="filter-group">
                            <label for="search_couchage_min">Couchages min</label>
                            <input type="number" id="search_couchage_min" name="search_couchage_min" value="<?= $searchCouchageMin > 0 ? $searchCouchageMin : '' ?>" placeholder="Ex: 2" min="0">
                        </div>
                        <div class="filter-group">
                            <label for="search_couchage_max">Couchages max</label>
                            <input type="number" id="search_couchage_max" name="search_couchage_max" value="<?= $searchCouchageMax > 0 ? $searchCouchageMax : '' ?>" placeholder="Ex: 6" min="0">
                        </div>
                    </div>

                    <!-- Ligne 3: Superficie, Animaux et Note -->
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="search_superficie_min">Superficie min (m²)</label>
                            <input type="number" id="search_superficie_min" name="search_superficie_min" value="<?= $searchSuperficieMin > 0 ? $searchSuperficieMin : '' ?>" placeholder="Ex: 50" min="0">
                        </div>
                        <div class="filter-group">
                            <label for="search_superficie_max">Superficie max (m²)</label>
                            <input type="number" id="search_superficie_max" name="search_superficie_max" value="<?= $searchSuperficieMax > 0 ? $searchSuperficieMax : '' ?>" placeholder="Ex: 200" min="0">
                        </div>
                        <div class="filter-group">
                            <label for="search_animaux">Animaux acceptés</label>
                            <select id="search_animaux" name="search_animaux">
                                <option value="-1" <?= $searchAnimaux == -1 ? 'selected' : '' ?>>Peu importe</option>
                                <option value="1" <?= $searchAnimaux == 1 ? 'selected' : '' ?>>Oui</option>
                                <option value="0" <?= $searchAnimaux == 0 ? 'selected' : '' ?>>Non</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="search_note">Note minimum</label>
                            <select id="search_note" name="search_note">
                                <option value="0">Toutes les notes</option>
                                <?php for($i=5; $i>=1; $i--): ?>
                                    <option value="<?= $i ?>" <?= ($searchNote == $i) ? 'selected' : '' ?>><?= str_repeat('★', $i) ?> et plus</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div class="filter-actions">
                        <button type="submit" class="search-btn-advanced">
                            <span>🔍</span> Rechercher
                        </button>
                        <a href="Annonce.form.php" class="clear-search-btn">
                            <span>🔄</span> Réinitialiser
                        </a>
                        <button type="button" class="toggle-filters-btn" onclick="toggleFilters()">
                            <span id="toggle-icon">▼</span> <span id="toggle-text">Masquer les filtres</span>
                        </button>
                    </div>
                </form>
                
                <!-- Résumé des filtres actifs -->
                <?php 
                $activeFiltersCount = 0;
                $activeFiltersSummary = [];
                
                if ($searchNomBien) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Nom: " . htmlspecialchars($searchNomBien);
                }
                if ($searchCommuneId > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Commune: " . htmlspecialchars($searchCommune);
                }
                if ($searchTypeBien > 0) {
                    $activeFiltersCount++;
                    foreach ($types as $type) {
                        if ($type['id_type_biens'] == $searchTypeBien) {
                            $activeFiltersSummary[] = "Type: " . htmlspecialchars($type['designation_type_bien']);
                            break;
                        }
                    }
                }
                if ($searchPrixMin > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Prix min: " . number_format($searchPrixMin, 0) . "€";
                }
                if ($searchPrixMax > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Prix max: " . number_format($searchPrixMax, 0) . "€";
                }
                if ($searchCouchageMin > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Couchages min: " . $searchCouchageMin;
                }
                if ($searchCouchageMax > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Couchages max: " . $searchCouchageMax;
                }
                if ($searchSuperficieMin > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Superficie min: " . $searchSuperficieMin . "m²";
                }
                if ($searchSuperficieMax > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Superficie max: " . $searchSuperficieMax . "m²";
                }
                if ($searchAnimaux == 1) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Animaux: Acceptés";
                } elseif ($searchAnimaux == 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Animaux: Non acceptés";
                }
                if ($searchNote > 0) {
                    $activeFiltersCount++;
                    $activeFiltersSummary[] = "Note: " . str_repeat('★', $searchNote) . "+";
                }
                ?>
                
                <?php if ($activeFiltersCount > 0): ?>
                    <div class="active-filters-summary">
                        <div class="filters-summary-header">
                            <span class="filters-count-badge"><?= $activeFiltersCount ?></span>
                            <strong>Filtres actifs:</strong>
                        </div>
                        <div class="filters-tags">
                            <?php foreach ($activeFiltersSummary as $filter): ?>
                                <span class="filter-tag"><?= $filter ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Informations sur les résultats -->
            <div class="results-info">
                <?php 
                $totalBiens = count($biens);
                if ($activeFiltersCount > 0): 
                ?>
                    <p class="results-count">
                        <span class="results-number"><?= $totalBiens ?></span> 
                        <?= $totalBiens > 1 ? 'résultats trouvés' : 'résultat trouvé' ?>
                        <?php if ($totalRecords > $perPage): ?>
                            sur <strong><?= $totalRecords ?></strong> au total
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="results-count">
                        <span class="results-number"><?= $totalRecords ?></span> 
                        <?= $totalRecords > 1 ? 'annonces disponibles' : 'annonce disponible' ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($biens): ?>
                <div class="annonces-grid">
                    <?php foreach ($biens as $b): ?>
                        <div class="annonce-card-wrapper">
                            <a href="annonce_detail.php?id=<?= htmlspecialchars($b['id_biens']) ?>" class="annonce-card">
                                <?php
                                // Gérer les différents formats de chemin de photos
                                if (isset($photos[$b['id_biens']]) && !empty($photos[$b['id_biens']])) {
                                    $lienPhoto = $photos[$b['id_biens']][0]['lien_photo'];
                                    // Nettoyer le chemin et utiliser le bon chemin relatif
                                    $filename = basename($lienPhoto);
                                    $imageSrc = '../images/uploads/' . htmlspecialchars($filename);
                                } else {
                                    // Image placeholder SVG en data URI
                                    $imageSrc = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect fill="%23667eea" width="400" height="300"/%3E%3Ctext fill="%23ffffff" font-family="Arial" font-size="24" x="50%25" y="50%25" text-anchor="middle" dy=".3em"%3EAucune image%3C/text%3E%3C/svg%3E';
                                }
                                ?>
                                <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($b['nom_biens']) ?>" class="annonce-image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23667eea%22 width=%22400%22 height=%22300%22/%3E%3Ctext fill=%22%23ffffff%22 font-family=%22Arial%22 font-size=%2224%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EImage non disponible%3C/text%3E%3C/svg%3E'">
                                <div class="annonce-price">
                                    <?php
                                    // Utilisation de la classe Tarif
                                    $tarifClass = new Tarif(null, null, null, null, null, $pdo);
                                    $price = $tarifClass->getLatestTarifByBien($b['id_biens']);
                                    ?>
                                    €<?= number_format($price, 2) ?>/semaine
                                </div>
                                
                                <?php 
                                // DEBUG: Afficher ce qui se passe avec les ratings
                                $debugRating = isset($ratings[$b['id_biens']]) ? 'OUI - ' . json_encode($ratings[$b['id_biens']]) : 'NON';
                                ?>
                                <!-- DEBUG: Rating pour bien #<?= $b['id_biens'] ?>: <?= $debugRating ?> -->
                                
                                <?php if (!empty($ratings[$b['id_biens']])): $r = $ratings[$b['id_biens']]; ?>
                                    <div class="annonce-rating" style="text-align: center; padding: 8px 12px; background: linear-gradient(135deg, #fff9e6, #ffffff); border-radius: 8px; margin: 8px 12px; font-size: 0.95em; color: #f39c12; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.08); border: 1px solid #ffe5b4;">
                                        <?= str_repeat('★', round($r['avg_rating'])) . str_repeat('☆', 5 - round($r['avg_rating'])) ?>
                                        <span style="color:#666;font-size:0.85em;margin-left:6px;">(<?= intval($r['count_reviews']) ?> avis)</span>
                                    </div>
                                <?php endif; ?>
                                <div class="annonce-content">
                                    <h4 class="annonce-title"><?= htmlspecialchars($b['nom_biens']) ?></h4>
                                    <p class="annonce-location"><?= htmlspecialchars($b['nom_commune']) ?>, <?= htmlspecialchars($b['rue_biens']) ?></p>
                                    <p class="annonce-details">
                                        <?= htmlspecialchars($b['superficie_biens']) ?> m² • <?= htmlspecialchars($b['nb_couchage']) ?> couchages • <?= htmlspecialchars($b['designation_type_bien']) ?>
                                    </p>
                                    <?php if (!empty($compositions[$b['id_biens']])): ?>
                                        <p class="annonce-composition" style="font-size:0.9em;color:#555;margin-top:6px;">
                                            <?php
                                            $parts = [];
                                            foreach ($compositions[$b['id_biens']] as $citem) {
                                                $parts[] = intval($citem['quantite']) . '× ' . htmlspecialchars($citem['lib_prestation']);
                                            }
                                            echo implode(' • ', $parts);
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'animateur'): ?>
                                        <p style="margin-top:8px;"><a href="Compose.form.php?bien_id=<?= htmlspecialchars($b['id_biens']) ?>" style="color:#a100b8;">Gérer la composition</a></p>
                                    <?php endif; ?>
                                    <?php
                                        $poster = 'HAP';
                                        if (isset($b['created_by_name']) && $b['created_by_name']) {
                                            $poster = $b['created_by_name'];
                                        }
                                    ?>
                                    <div class="annonce-footer" style="margin-top:10px; font-size:0.85em; color:#666; border-top:1px solid #eee; padding-top:8px;">Posté par : <?= htmlspecialchars($poster) ?></div>
                                </div>
                            </a>
                            <!-- Bouton Favori -->
                            <button class="favorite-btn" data-bien-id="<?= $b['id_biens'] ?>" onclick="FavorisManager.toggle(<?= $b['id_biens'] ?>, this)" title="Ajouter aux favoris">🤍</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        // Build pagination URL parameters
                        $paginationParams = '';
                        if ($searchCommuneId > 0) {
                            $paginationParams = "&search_commune_id=$searchCommuneId&search_commune=" . urlencode($searchCommune);
                        }

                        // Previous button
                        if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $paginationParams ?>" class="pagination-btn prev-btn">&laquo; Précédent</a>
                        <?php endif; ?>

                        <div class="pagination-numbers">
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            // First page
                            if ($startPage > 1): ?>
                                <a href="?page=1<?= $paginationParams ?>" class="pagination-btn" aria-label="Page 1">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Page numbers -->
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?= $i ?><?= $paginationParams ?>" class="pagination-btn <?= $i == $page ? 'active' : '' ?>" aria-label="Page <?= $i ?>"><?= $i ?></a>
                            <?php endfor; ?>

                            <!-- Last page -->
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                                <a href="?page=<?= $totalPages ?><?= $paginationParams ?>" class="pagination-btn" aria-label="Page <?= $totalPages ?>"><?= $totalPages ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $paginationParams ?>" class="pagination-btn next-btn">Suivant &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="results-info">
                    <p>Affichage de <?= count($biens) ?> annonce(s) sur <?= $totalRecords ?> au total</p>
                </div>
            <?php else: ?>
                <p class="no-annonces">
                    <?php if ($searchCommuneId > 0): ?>
                        Aucune annonce trouvée pour la commune sélectionnée.
                    <?php else: ?>
                        Aucune annonce publiée pour le moment.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script src="../js/confirm_delete.js"></script>
    <script src="../js/favoris.js"></script>
    <script>
        // Initialiser les favoris au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof FavorisManager !== 'undefined') {
                FavorisManager.init();
            }
        });
    </script>
</body>
</html>
