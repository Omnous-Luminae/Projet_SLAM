<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Tarif/Tarif.php';

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
        if (isset($_POST['add_bien'])) {
            $nom = trim($_POST['nom_biens'] ?? '');
            $rue = trim($_POST['rue_biens'] ?? '');
            $superficie = intval($_POST['superficie_biens'] ?? 0);
            $desc = trim($_POST['description_biens'] ?? '');
            $animal = isset($_POST['animal_biens']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 0);
            $tarif = floatval($_POST['tarif_biens'] ?? 0);
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type = intval($_POST['id_type_biens'] ?? 0);

            if ($nom && $rue && $superficie > 0 && $desc && $nb_couchage > 0 && $tarif > 0 && $id_commune && $id_type) {
                $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$nom, $rue, $superficie, $desc, $animal, $nb_couchage, $id_commune, $id_type]);
                $id_biens = $pdo->lastInsertId();

                // Créer un tarif par défaut pour le bien
                $tarifClass = new Tarif(null, null, null, null, $pdo);
                $currentWeek = date('W');
                $currentYear = date('Y');
                $tarifClass->createTarif($id_biens, $currentWeek, $currentYear, $tarif);

                // Upload des images
                if (isset($_FILES['photos'])) {
                    $uploadDir = __DIR__ . '/../images/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = basename($_FILES['photos']['name'][$key]);
                            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            if (in_array($fileExtension, $allowedExtensions)) {
                                $newFileName = uniqid('img_', true) . '.' . $fileExtension;
                                $destPath = $uploadDir . $newFileName;
                                $lienPhoto = 'Projet_HAP(House_After_Party)/images/uploads/' . $newFileName;
                                if (move_uploaded_file($tmpName, $destPath)) {
                                    $stmtPhoto = $pdo->prepare('INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (?, ?, ?)');
                                    $stmtPhoto->execute([$fileName, $lienPhoto, $id_biens]);
                                }
                            }
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

        // Communes et types
        $communes = $pdo->query('SELECT id_commune, nom_commune FROM Commune LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
        $types = $pdo->query('SELECT * FROM Type_Bien')->fetchAll(PDO::FETCH_ASSOC);
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <!-- Lightbox CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <script>
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

            $("#bien_form").on('submit', function(e) {
                if (!$("#id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }
            });

            // Preview images before upload
            $("#photos").on('change', function() {
                const files = this.files;
                const preview = $("#preview");
                preview.empty();
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = $('<img>').attr('src', e.target.result);
                            preview.append(img);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });

            // Search commune autocomplete for search form
            $("#search_commune_input").autocomplete({
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
                    $("#search_commune_input").val(ui.item.label);
                    $("#search_commune_id").val(ui.item.id);
                    return false;
                }
            });

            $("#search_commune_input").on('input', function() {
                $("#search_commune_id").val('');
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
        <div class="form-section">
            <h3>Publier une nouvelle annonce</h3>
            <form method="post" enctype="multipart/form-data" id="bien_form">
                <div class="form-group">
                    <label for="nom_biens">Nom du bien</label>
                    <input type="text" id="nom_biens" name="nom_biens" required>
                </div>
                <div class="form-group">
                    <label for="rue_biens">Rue</label>
                    <input type="text" id="rue_biens" name="rue_biens" required>
                </div>
                <div class="form-group">
                    <label for="superficie_biens">Superficie (m²)</label>
                    <input type="number" id="superficie_biens" name="superficie_biens" required min="1">
                </div>
                <div class="form-group">
                    <label for="description_biens">Description</label>
                    <textarea id="description_biens" name="description_biens" required></textarea>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="animal_biens" name="animal_biens">
                        <label for="animal_biens">Animaux acceptés</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="nb_couchage">Nombre de couchages</label>
                    <input type="number" id="nb_couchage" name="nb_couchage" required min="1">
                </div>
                <div class="form-group">
                    <label for="tarif_biens">Tarif par nuit (€)</label>
                    <input type="number" id="tarif_biens" name="tarif_biens" required min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="commune">Commune</label>
                    <input type="text" id="commune" required>
                    <input type="hidden" id="id_commune" name="id_commune">
                </div>
                <div class="form-group">
                    <label for="id_type_biens">Type de bien</label>
                    <select id="id_type_biens" name="id_type_biens" required>
                        <option value="">-- Sélectionnez un type --</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t['id_type_biens'] ?>"><?= htmlspecialchars($t['designation_type_bien']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Photos du bien</label>
                    <div class="file-input" onclick="document.getElementById('photos').click()">
                        Cliquez ici pour sélectionner une ou plusieurs images
                        <input type="file" id="photos" name="photos[]" accept="image/*" multiple required>
                    </div>
                    <div class="preview-images" id="preview"></div>
                </div>
                <input type="submit" name="add_bien" value="Publier l'annonce">
            </form>
        </div>
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
                                $tarifClass = new Tarif(null, null, null, null, $pdo);
                                $price = $tarifClass->getLatestTarifByBien($b['id_biens']);
                                ?>
                                €<?= number_format($price, 2) ?>/nuit
                            </div>
                            <div class="annonce-content">
                                <h4 class="annonce-title"><?= htmlspecialchars($b['nom_biens']) ?></h4>
                                <p class="annonce-location"><?= htmlspecialchars($b['nom_commune']) ?>, <?= htmlspecialchars($b['rue_biens']) ?></p>
                                <p class="annonce-details">
                                    <?= htmlspecialchars($b['superficie_biens']) ?> m² • <?= htmlspecialchars($b['nb_couchage']) ?> couchages • <?= htmlspecialchars($b['designation_type_bien']) ?>
                                </p>
                            </div>
                        </a>
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
                                <a href="?page=1<?= $paginationParams ?>" class="pagination-btn">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <!-- Page numbers -->
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?= $i ?><?= $paginationParams ?>" class="pagination-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>

                            <!-- Last page -->
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="pagination-dots">...</span>
                                <?php endif; ?>
                                <a href="?page=<?= $totalPages ?><?= $paginationParams ?>" class="pagination-btn"><?= $totalPages ?></a>
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
</body>
</html>
