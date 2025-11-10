<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Tarif/Tarif.php';
require_once __DIR__ . '/../classes/Saison/Saison.php';
require_once __DIR__ . '/../classes/Compose/Compose.php';

$message = '';

// Pagination parameters
$perPage = 9; // Maximum 9 announcements per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Search parameters
$searchCommune = trim($_GET['search_commune'] ?? '');
$searchCommuneId = intval($_GET['search_commune_id'] ?? 0);

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
            $superficie = intval($_POST['superficie_biens'] ?? 0);
            $desc = trim($_POST['description_biens'] ?? '');
            $animal = isset($_POST['animal_biens']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 0);
            $tarifs = $_POST['tarifs'] ?? [];
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type = intval($_POST['id_type_biens'] ?? 0);
            // composition will be an array of items: [ ['prestation_id'=>..., 'quantite'=>...], ... ]
            $composition = $_POST['composition'] ?? [];

            // Validation des champs de base
                if ($nom && $rue && $superficie > 0 && $desc && $nb_couchage > 0 && $id_commune && $id_type && !empty($tarifs)) {
                // Include created_by_name if available (store poster username). The DB may need migration to add created_by_name column.
                $createdBy = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
                // Try to insert with created_by_name column - if the column doesn't exist, fall back to the shorter INSERT.
                try {
                    // Try to insert with both created_by_name and created_by_id (if the column exists)
                    $createdById = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
                    $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens, created_by_name, created_by_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $id_commune, $id_type, $createdBy, $createdById]);
                } catch (PDOException $e) {
                    try {
                        // Fallback: only created_by_name
                        $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens, created_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $id_commune, $id_type, $createdBy]);
                    } catch (PDOException $e2) {
                        // Column might not exist; fallback to original INSERT without created_by info
                        $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $id_commune, $id_type]);
                    }
                }
                $id_biens = $pdo->lastInsertId();

                // Créer les tarifs pour le bien
                $tarifClass = new Tarif(null, null, null, null, null, $pdo);
                foreach ($tarifs as $tarifData) {
                    $semaine_tarif = intval($tarifData['semaine_tarif'] ?? 0);
                    $annee_tarif = intval($tarifData['annee_tarif'] ?? 0);
                    $tarif = floatval($tarifData['tarif'] ?? 0);
                    $id_saison = intval($tarifData['id_saison'] ?? 0);
                    if ($semaine_tarif > 0 && $annee_tarif > 0 && $tarif > 0 && $id_saison > 0) {
                        $tarifClass->createTarif($id_biens, $semaine_tarif, $annee_tarif, $tarif, $id_saison);
                    }
                }

                // Créer la composition (sous-formulaire) si fournie
                if (!empty($composition) && is_array($composition)) {
                    $composeClass = new Compose(null, $pdo);
                    // We'll need to resolve labels to prestation IDs (find existing or create)
                    $findPrestation = $pdo->prepare('SELECT id_prestation FROM Prestation WHERE LOWER(lib_prestation) = LOWER(?) LIMIT 1');
                    $insertPrestation = $pdo->prepare('INSERT INTO Prestation (lib_prestation) VALUES (?)');
                    foreach ($composition as $comp) {
                        $label = trim($comp['label'] ?? '');
                        $quantite = intval($comp['quantite'] ?? 0);
                        if ($label === '' || $quantite <= 0) { continue; }

                        // find existing prestation
                        $findPrestation->execute([$label]);
                        $row = $findPrestation->fetch(PDO::FETCH_ASSOC);
                        if ($row && isset($row['id_prestation']) && $row['id_prestation'] > 0) {
                            $id_prestation = $row['id_prestation'];
                        } else {
                            // create new prestation label
                            $insertPrestation->execute([$label]);
                            $id_prestation = $pdo->lastInsertId();
                        }

                        if ($id_prestation > 0) {
                            $composeClass->addCompose($id_biens, $id_prestation, $quantite);
                        }
                    }
                }

                // Upload des images (support single file or multiple files reliably)
                if (isset($_FILES['photos'])) {
                    $files = $_FILES['photos'];
                    $uploadDir = __DIR__ . '/../images/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                    // Normalize to a set of files
                    $fileCount = 0;
                    if (is_array($files['name'])) {
                        $fileCount = count($files['name']);
                    } elseif (!empty($files['name'])) {
                        $fileCount = 1;
                    }

                    for ($i = 0; $i < $fileCount; $i++) {
                        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                        if ($error !== UPLOAD_ERR_OK) {
                            continue; // skip if any error for this index
                        }
                        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                        $fileName = is_array($files['name']) ? basename($files['name'][$i]) : basename($files['name']);
                        if (empty($tmpName) || !is_uploaded_file($tmpName)) { continue; }

                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        if (!in_array($fileExtension, $allowedExtensions)) { continue; }

                        $newFileName = uniqid('img_', true) . '.' . $fileExtension;
                        $destPath = $uploadDir . $newFileName;
                        $lienPhoto = 'Projet_HAP(House_After_Party)/images/uploads/' . $newFileName;
                        if (move_uploaded_file($tmpName, $destPath)) {
                            $stmtPhoto = $pdo->prepare('INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (?, ?, ?)');
                            $stmtPhoto->execute([$fileName, $lienPhoto, $id_biens]);
                        }
                    }
                }



                $message = "Bien ajouté avec succès.";
            } else {
                $message = "Veuillez remplir tous les champs correctement.";
            }
        }

        // Suppression d'un bien
        if (isset($_POST['delete_bien']) && isset($_POST['id_biens'])) {
            $id = intval($_POST['id_biens']);
            // Supprimer les photos associées
            $stmt = $pdo->prepare('DELETE FROM Photos WHERE id_biens = ?');
            $stmt->execute([$id]);
            // Supprimer les tarifs associés
            $stmt = $pdo->prepare('DELETE FROM Tarif WHERE id_biens = ?');
            $stmt->execute([$id]);
            // Supprimer la composition associée
            $stmt = $pdo->prepare('DELETE FROM Compose WHERE id_biens = ?');
            $stmt->execute([$id]);
            // Supprimer le bien
            $stmt = $pdo->prepare('DELETE FROM Biens WHERE id_biens = ?');
            $stmt->execute([$id]);
            $message = "Bien supprimé avec succès.";
        }

        // Build query for biens with search and pagination
        $whereClause = '';
        $params = [];

        if ($searchCommuneId > 0) {
            $whereClause = 'WHERE b.id_commune = ?';
            $params[] = $searchCommuneId;
        }

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens $whereClause";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalRecords / $perPage);

        // Récupération des biens avec photos (paginated and filtered)
        $query = "SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens $whereClause ORDER BY b.id_biens DESC LIMIT $perPage OFFSET $offset";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $biens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Photos par bien
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

        // Ratings (Reviews) par bien
        $ratings = [];
        if ($biens) {
            $ids = array_column($biens, 'id_biens');
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id_biens, AVG(rating) as avg_rating, COUNT(*) as count_reviews FROM Reviews WHERE id_biens IN ($placeholders) GROUP BY id_biens");
            $stmt->execute($ids);
            $allRatings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allRatings as $r) {
                $ratings[$r['id_biens']] = $r;
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Annonces</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../Css/annonce.css">
    <style>
        .back-to-dashboard { display: inline-block; margin: 20px; padding: 10px 20px; background: #a100b8; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .back-to-dashboard:hover { background: #4b006e; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <script src="../js/autocomplete.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize autocomplete for search and add forms
            initSearchCommuneAutocomplete();
            initAddCommuneAutocomplete();

            // Gestion des tarifs dynamiques
            let tarifIndex = 1;
            $('#add-tarif').on('click', function() {
                const newTarif = `
                    <div class="tarif-item">
                        <input type="number" name="tarifs[${tarifIndex}][semaine_tarif]" placeholder="Semaine" min="1" max="52" value="<?= date('W') ?>" required>
                        <input type="number" name="tarifs[${tarifIndex}][annee_tarif]" placeholder="Année" min="2020" max="2030" value="<?= date('Y') ?>" required>
                        <input type="number" step="0.01" name="tarifs[${tarifIndex}][tarif]" placeholder="Tarif (€)" required>
                        <select name="tarifs[${tarifIndex}][id_saison]" required>
                            <option value="">-- Sélectionnez une saison --</option>
                            <?php
                            $saisons = $pdo->query('SELECT * FROM Saison')->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($saisons as $saison): ?>
                                <option value="<?= $saison['id_saison'] ?>"><?= htmlspecialchars($saison['lib_saison']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="remove-tarif">Supprimer</button>
                    </div>
                `;
                $('#tarifs-container').append(newTarif);
                tarifIndex++;
            });

            $(document).on('click', '.remove-tarif', function() {
                $(this).closest('.tarif-item').remove();
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

            // Photo inputs: allow multiple separate inputs so selecting a file doesn't replace others
            let photoIndex = 1;
            function createPhotoRow(index) {
                return $(
                    '<div class="photo-input-row" data-index="' + index + '" style="display:flex;gap:8px;align-items:center;">' +
                    '<input type="file" name="photos[]" class="photo-input">' +
                    '<button type="button" class="remove-photo-input">Supprimer</button>' +
                    '</div>'
                );
            }

            $('#add-photo-input').on('click', function() {
                const row = createPhotoRow(photoIndex);
                $('#photos-container').append(row);
                photoIndex++;
            });

            // Handle change to show preview
            $(document).on('change', '.photo-input', function(e) {
                const input = this;
                const file = input.files && input.files[0];
                const $row = $(input).closest('.photo-input-row');
                const idx = $row.attr('data-index') || Date.now();

                // remove existing preview for this row if any
                $('#photo-previews').find('[data-preview-index="' + idx + '"]').remove();

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const $preview = $('<div class="photo-preview" data-preview-index="' + idx + '" style="position:relative;width:100px;height:70px;border:1px solid #ddd;padding:4px;display:flex;align-items:center;justify-content:center;background:#fff;border-radius:6px;overflow:hidden;"></div>');
                        const $img = $('<img src="' + evt.target.result + '" style="max-width:100%;max-height:100%;display:block;">');
                        const $remove = $('<button type="button" class="remove-preview" style="position:absolute;top:2px;right:2px;background:#fff;border:0;padding:2px 6px;border-radius:4px;cursor:pointer;">✕</button>');
                        $remove.on('click', function(){
                            $preview.remove();
                            $row.remove();
                        });
                        $preview.append($img).append($remove);
                        $('#photo-previews').append($preview);
                        // show remove button on row
                        $row.find('.remove-photo-input').show();
                    };
                    reader.readAsDataURL(file);
                } else {
                    $row.find('.remove-photo-input').hide();
                }
            });

            // Remove row and corresponding preview
            $(document).on('click', '.remove-photo-input', function() {
                const $row = $(this).closest('.photo-input-row');
                const idx = $row.attr('data-index');
                $('#photo-previews').find('[data-preview-index="' + idx + '"]').remove();
                $row.remove();
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Annonces</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout d'une annonce -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="form-section">
            <h3>Ajouter une nouvelle annonce</h3>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nom_biens">Nom du bien:</label>
                    <input type="text" id="nom_biens" name="nom_biens" required>
                </div>
                <div class="form-group">
                    <label for="rue_biens">Rue:</label>
                    <input type="text" id="rue_biens" name="rue_biens" required>
                </div>
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
                    <label for="commune_input">Commune:</label>
                    <input type="text" id="commune_input" name="commune" placeholder="Tapez le nom d'une commune..." required>
                    <input type="hidden" id="commune_id" name="id_commune">
                </div>
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
                    <label for="photos-0">Photos:</label>
                    <div id="photos-container">
                        <div class="photo-input-row" data-index="0" style="display:flex;gap:8px;align-items:center;">
                            <input id="photos-0" type="file" name="photos[]" class="photo-input" accept="image/*">
                            <button type="button" class="remove-photo-input" style="display:none;">Supprimer</button>
                        </div>
                    </div>
                    <div id="photo-previews" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;"></div>
                    <div style="margin-top:8px;">
                        <button type="button" id="add-photo-input">Ajouter une photo</button>
                        <small style="display:block;color:#666;margin-top:6px;">Vous pouvez sélectionner plusieurs fichiers en une fois ou ajouter plusieurs champs. Les champs séparés conservent chaque sélection.</small>
                    </div>
                </div>

                <input type="submit" name="add_bien" value="Ajouter l'annonce">
            </form>
        </div>
        <?php else: ?>
            <p>Vous devez être connecté pour ajouter une annonce. <a href="../auth/connexion.php">Se connecter</a></p>
        <?php endif; ?>

        <div class="bien-list">
            <h3>Annonces publiées</h3>

            <!-- Search and Filter Section -->
            <div class="search-section">
                <form method="get" class="search-form">
                    <div class="search-group">
                        <label for="search_commune_input">Rechercher par commune</label>
                        <input type="text" id="search_commune_input" name="search_commune" value="<?= htmlspecialchars($searchCommune) ?>" placeholder="Tapez le nom d'une commune...">
                        <input type="hidden" id="search_commune_id" name="search_commune_id" value="<?= $searchCommuneId ?>">
                        <button type="submit" class="search-btn">Rechercher</button>
                        <?php if ($searchCommuneId > 0): ?>
                            <a href="Annonce.form.php" class="clear-search">Effacer la recherche</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($biens): ?>
                <div class="annonces-grid">
                    <?php foreach ($biens as $b): ?>
                        <div class="annonce-card-wrapper">
                            <a href="annonce_detail.php?id=<?= htmlspecialchars($b['id_biens']) ?>" class="annonce-card">
                                <?php
                                $imageSrc = isset($photos[$b['id_biens']]) && !empty($photos[$b['id_biens']])
                                    ? '/' . htmlspecialchars($photos[$b['id_biens']][0]['lien_photo'])
                                    : '/Projet_HAP(House_After_Party)/images/placeholder.jpg';
                                ?>
                                <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($b['nom_biens']) ?>" class="annonce-image">
                                <div class="annonce-price">
                                    <?php
                                    // Utilisation de la classe Tarif
                                    $tarifClass = new Tarif(null, null, null, null, null, $pdo);
                                    $price = $tarifClass->getLatestTarifByBien($b['id_biens']);
                                    ?>
                                    €<?= number_format($price, 2) ?>/nuit
                                    <?php if (!empty($ratings[$b['id_biens']])): $r = $ratings[$b['id_biens']]; ?>
                                        <div style="font-size:0.9em;color:#f39c12;margin-top:6px;">
                                            <?= str_repeat('★', round($r['avg_rating'])) . str_repeat('☆', 5 - round($r['avg_rating'])) ?>
                                            <span style="color:#666;font-size:0.85em;">(<?= intval($r['count_reviews']) ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
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
</body>
</html>
