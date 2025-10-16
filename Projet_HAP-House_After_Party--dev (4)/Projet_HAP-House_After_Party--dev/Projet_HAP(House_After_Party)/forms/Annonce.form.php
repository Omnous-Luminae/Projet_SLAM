<?php
require_once __DIR__ . '/../config/db.php';


$message = '';

try {
    if (!isset($pdo)) {
        throw new Exception('Connexion PDO introuvable. Vérifiez config/db.php');
    }

    // traitement du formulaire (création / mise à jour / suppression / chargement édition)
    $editing = false;
    $editing_data = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // suppression d'une annonce
        if (isset($_POST['delete_annonce']) && isset($_POST['id_annonce'])) {
            $id = intval($_POST['id_annonce']);

            // supprimer les fichiers photos du disque
            $stmtp = $pdo->prepare('SELECT lien_photo FROM Photos WHERE id_biens = :id_biens');
            $stmtp->execute([':id_biens' => $id]);
            $photos = $stmtp->fetchAll(PDO::FETCH_COLUMN);
            foreach ($photos as $lien) {
                $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($lien, '/');
                if (file_exists($path)) @unlink($path);
            }

            // supprimer en base
            $stmt = $pdo->prepare('DELETE FROM Photos WHERE id_biens = :id_biens');
            $stmt->execute([':id_biens' => $id]);
            $stmt = $pdo->prepare('DELETE FROM Biens WHERE id_biens = :id_biens');
            $res = $stmt->execute([':id_biens' => $id]);
            $message = $res ? 'Annonce supprimée.' : 'Erreur lors de la suppression.';

        // passer en mode édition (charge les données pour pré-remplir le formulaire)
        } elseif (isset($_POST['edit_mode'])) {
            $id = intval($_POST['edit_mode']);
            $stmt = $pdo->prepare('SELECT b.*, c.nom_commune, c.cp_commune FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune WHERE b.id_biens = :id_biens');
            $stmt->execute([':id_biens' => $id]);
            $editing_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($editing_data) $editing = true;

        // mise à jour d'une annonce existante
        } elseif (isset($_POST['update_annonce']) && isset($_POST['id_biens'])) {
            $id_biens = intval($_POST['id_biens']);

            // Champs principaux
            $nom = trim($_POST['nom_bien'] ?? '');
            $rue = trim($_POST['rue_bien'] ?? '');
            $superficie = intval($_POST['superficie_bien'] ?? 0);
            $description = trim($_POST['description_bien'] ?? '');
            $animal = isset($_POST['animal_bien']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 1);
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type_biens = intval($_POST['id_type_biens'] ?? 0);

            // Validation minimale
            $errors = [];
            if ($nom === '') $errors[] = 'Le nom du bien est requis.';
            if ($rue === '') $errors[] = 'L\'adresse (rue) est requise.';
            if ($superficie <= 0) $errors[] = 'La superficie doit être un entier positif.';
            if ($description === '') $errors[] = 'La description est requise.';
            if ($id_commune <= 0) $errors[] = 'La commune est requise.';
            if ($id_type_biens <= 0) $errors[] = 'Le type de bien est requis.';

            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE Biens SET nom_biens = :nom, rue_biens = :rue, superficie_biens = :superficie, description_biens = :description, animal_biens = :animal, nb_couchage = :nb_couchage, id_commune = :id_commune, id_type_biens = :id_type_biens WHERE id_biens = :id_biens');
                $stmt->execute([
                    ':nom' => $nom,
                    ':rue' => $rue,
                    ':superficie' => $superficie,
                    ':description' => $description,
                    ':animal' => $animal,
                    ':nb_couchage' => $nb_couchage,
                    ':id_commune' => $id_commune,
                    ':id_type_biens' => $id_type_biens,
                    ':id_biens' => $id_biens
                ]);

                // supprimer les photos demandées
                if (!empty($_POST['delete_photo_ids']) && is_array($_POST['delete_photo_ids'])) {
                    $toDelete = array_map('intval', $_POST['delete_photo_ids']);
                    $in = implode(',', array_fill(0, count($toDelete), '?'));
                    $stmtDel = $pdo->prepare('SELECT lien_photo FROM Photos WHERE id_photo IN (' . $in . ')');
                    $stmtDel->execute($toDelete);
                    $delLinks = $stmtDel->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($delLinks as $lien) {
                        $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($lien, '/');
                        if (file_exists($path)) @unlink($path);
                    }
                    $stmtDel2 = $pdo->prepare('DELETE FROM Photos WHERE id_photo IN (' . $in . ')');
                    $stmtDel2->execute($toDelete);
                }

                $message = 'Annonce mise à jour avec succès.';
            } else {
                $message = implode('<br>', $errors);
            }

        // création d'une nouvelle annonce
        } elseif (isset($_POST['submit_annonce'])) {
            // Champs principaux
            $nom = trim($_POST['nom_bien'] ?? '');
            $rue = trim($_POST['rue_bien'] ?? '');
            $superficie = intval($_POST['superficie_bien'] ?? 0);
            $description = trim($_POST['description_bien'] ?? '');
            $animal = isset($_POST['animal_bien']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 1);
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type_biens = intval($_POST['id_type_biens'] ?? 0);

            // Validation minimale
            $errors = [];
            if ($nom === '') $errors[] = 'Le nom du bien est requis.';
            if ($rue === '') $errors[] = 'L\'adresse (rue) est requise.';
            if ($superficie <= 0) $errors[] = 'La superficie doit être un entier positif.';
            if ($description === '') $errors[] = 'La description est requise.';
            if ($id_commune <= 0) $errors[] = 'La commune est requise.';
            if ($id_type_biens <= 0) $errors[] = 'Le type de bien est requis.';

            if (empty($errors)) {
                // insertion dans Biens
                $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens) VALUES (:nom, :rue, :superficie, :description, :animal, :nb_couchage, :id_commune, :id_type_biens)');
                $stmt->execute([
                    ':nom' => $nom,
                    ':rue' => $rue,
                    ':superficie' => $superficie,
                    ':description' => $description,
                    ':animal' => $animal,
                    ':nb_couchage' => $nb_couchage,
                    ':id_commune' => $id_commune,
                    ':id_type_biens' => $id_type_biens
                ]);

                $id_bien = $pdo->lastInsertId();

                // Gestion des photos
                if (!empty($_FILES['photos']['name'][0])) {
                    $uploadDir = __DIR__ . '/../images/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                            $fileName = basename($_FILES['photos']['name'][$key]);
                            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

                            if (in_array($fileExt, $allowedExts)) {
                                $newFileName = uniqid('photo_', true) . '.' . $fileExt;
                                $filePath = $uploadDir . $newFileName;

                                if (move_uploaded_file($tmp_name, $filePath)) {
                                    $lienPhoto = 'images/uploads/' . $newFileName;
                                    $stmtPhoto = $pdo->prepare('INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (:nom, :lien, :id_bien)');
                                    $stmtPhoto->execute([
                                        ':nom' => $fileName,
                                        ':lien' => $lienPhoto,
                                        ':id_bien' => $id_bien
                                    ]);
                                }
                            }
                        }
                    }
                }

                $message = 'Annonce ajoutée avec succès.';
            } else {
                $message = implode('<br>', $errors);
            }
        }
    }

    // Récupérer communes et types de biens pour les selects
    $communes = [];
    $stmt = $pdo->query('SELECT id_commune, nom_commune, cp_commune FROM Commune ORDER BY nom_commune LIMIT 100');
    $communes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $types = [];
    $stmt = $pdo->query('SELECT id_type_biens, designation_type_bien FROM Type_Bien');
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer annonces existantes
    $annonces = [];
    $stmt = $pdo->query('SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens ORDER BY b.id_biens DESC LIMIT 50');
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = 'Erreur : ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Créer une annonce</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 40px; text-align: center; }
        .header h1 { margin: 0; font-size: 2.5em; font-weight: 700; }
        .header p { margin: 10px 0 0; font-size: 1.2em; opacity: 0.9; }
        .form-section { padding: 40px; }
        .section { margin-bottom: 40px; background: #f9f9f9; border-radius: 15px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .section h3 { margin-top: 0; color: #333; font-size: 1.5em; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 8px; color: #555; display: flex; align-items: center; }
        .form-group label i { margin-right: 8px; color: #667eea; }
        input, textarea, select { padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; transition: border-color 0.3s; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        textarea { min-height: 120px; resize: vertical; }
        .full-width { grid-column: 1 / -1; }
        .checkbox-group { display: flex; align-items: center; }
        .checkbox-group input { width: auto; margin-right: 10px; }
        .photo-upload { border: 2px dashed #667eea; border-radius: 15px; padding: 40px; text-align: center; background: #f0f4ff; transition: background 0.3s; position: relative; }
        .photo-upload:hover, .photo-upload.dragover { background: #e8f0ff; border-color: #4a5fd5; }
        .photo-upload i { font-size: 3em; color: #667eea; margin-bottom: 10px; }
        .photo-preview { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .preview-item { position: relative; display: inline-block; }
        .preview-image { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; border: 2px solid #667eea; }
        .remove-preview { position: absolute; top: -5px; right: -5px; background: #ff6b6b; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; }
        .remove-preview:hover { background: #ee5a52; }
        .existing-photos { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .photo-item { position: relative; }
        .photo-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; }
        .photo-item label { position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.8); border-radius: 50%; padding: 5px; cursor: pointer; }
        .actions { text-align: center; margin-top: 30px; }
        .btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 15px 30px; border: none; border-radius: 25px; font-size: 18px; font-weight: 600; cursor: pointer; transition: transform 0.2s; margin: 0 10px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); }
        .btn-secondary { background: #6c757d; }
        .message { text-align: center; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .listings { margin-top: 40px; }
        .listings h3 { text-align: center; color: #333; margin-bottom: 20px; }
        .listing-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .listing-card { background: #fff; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; transition: transform 0.3s; }
        .listing-card:hover { transform: translateY(-5px); }
        .listing-card img { width: 100%; height: 200px; object-fit: cover; }
        .listing-card .content { padding: 20px; }
        .listing-card h4 { margin: 0 0 10px; color: #333; }
        .listing-card p { margin: 5px 0; color: #666; }
        .listing-card .actions { margin-top: 15px; text-align: center; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #667eea; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .header h1 { font-size: 2em; } }
    </style>
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

            $("#annonce_form").on('submit', function(e) {
                if (!$("#id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }
            });

            // Photo preview functionality
            $("#photos").on('change', function(e) {
                const files = e.target.files;
                const previewContainer = $("#photo-preview");

                // Clear existing previews
                previewContainer.empty();

                if (files.length > 0) {
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = $('<img>').attr('src', e.target.result).addClass('preview-image');
                                const removeBtn = $('<button>').addClass('remove-preview').html('<i class="fas fa-times"></i>').attr('type', 'button');
                                const previewItem = $('<div>').addClass('preview-item').append(img).append(removeBtn);
                                previewContainer.append(previewItem);

                                // Remove preview functionality
                                removeBtn.on('click', function() {
                                    previewItem.remove();
                                    // Remove from input files
                                    const dt = new DataTransfer();
                                    const input = $("#photos")[0];
                                    const { files } = input;

                                    for (let j = 0; j < files.length; j++) {
                                        if (j !== i) {
                                            dt.items.add(files[j]);
                                        }
                                    }
                                    input.files = dt.files;
                                });
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                }
            });

            // Drag and drop functionality
            const photoUpload = $(".photo-upload");
            photoUpload.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            photoUpload.on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            photoUpload.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');

                const files = e.originalEvent.dataTransfer.files;
                const input = $("#photos")[0];
                input.files = files;
                $(input).trigger('change');
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-home"></i> Créer une annonce</h1>
            <p>Partagez votre propriété avec des voyageurs du monde entier</p>
        </div>
        <div class="form-section">
            <a href="/../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
            <?php if ($message): ?>
                <div class="message <?= strpos($message, 'Erreur') === 0 ? 'error' : 'success' ?>"><?= $message ?></div>
            <?php endif; ?>
            <div class="section">
                <h3><i class="fas fa-info-circle"></i> Informations générales</h3>
                <form method="post" enctype="multipart/form-data" id="annonce_form">
                    <?php if (!empty($editing_data)): ?>
                        <input type="hidden" name="id_biens" value="<?= htmlspecialchars($editing_data['id_biens']) ?>">
                    <?php endif; ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom_bien"><i class="fas fa-tag"></i> Titre / Nom du bien</label>
                            <input type="text" id="nom_bien" name="nom_bien" required value="<?= htmlspecialchars($editing_data['nom_biens'] ?? '') ?>" placeholder="Ex: Charming cottage in the countryside">
                        </div>
                        <div class="form-group">
                            <label for="id_type_biens"><i class="fas fa-building"></i> Type de bien</label>
                            <select id="id_type_biens" name="id_type_biens" required>
                                <option value="">-- Choisir un type --</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= htmlspecialchars($t['id_type_biens']) ?>" <?= (isset($editing_data) && $editing_data['id_type_biens'] == $t['id_type_biens']) ? 'selected' : '' ?>><?= htmlspecialchars($t['designation_type_bien']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rue_bien"><i class="fas fa-map-marker-alt"></i> Rue / Adresse</label>
                            <input type="text" id="rue_bien" name="rue_bien" required value="<?= htmlspecialchars($editing_data['rue_biens'] ?? '') ?>" placeholder="123 Main Street">
                        </div>
                        <div class="form-group">
                            <label for="commune"><i class="fas fa-city"></i> Commune</label>
                            <input type="text" id="commune" name="commune" placeholder="Rechercher une commune..." required value="<?= isset($editing_data) ? htmlspecialchars($editing_data['nom_commune'] . ' (' . $editing_data['cp_commune'] . ')') : '' ?>">
                            <input type="hidden" id="id_commune" name="id_commune" value="<?= isset($editing_data) ? htmlspecialchars($editing_data['id_commune']) : '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="superficie_bien"><i class="fas fa-ruler-combined"></i> Superficie (m²)</label>
                            <input type="number" id="superficie_bien" name="superficie_bien" min="1" required value="<?= htmlspecialchars($editing_data['superficie_biens'] ?? '') ?>" placeholder="50">
                        </div>
                        <div class="form-group">
                            <label for="nb_couchage"><i class="fas fa-bed"></i> Nombre de couchages</label>
                            <input type="number" id="nb_couchage" name="nb_couchage" min="1" value="<?= htmlspecialchars($editing_data['nb_couchage'] ?? 1) ?>" required placeholder="2">
                        </div>
                        <div class="form-group full-width">
                            <label for="description_bien"><i class="fas fa-align-left"></i> Description</label>
                            <textarea id="description_bien" name="description_bien" required placeholder="Décrivez votre propriété de manière attrayante..."><?= htmlspecialchars($editing_data['description_biens'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group checkbox-group">
                            <label for="animal_bien"><i class="fas fa-paw"></i> Animaux acceptés</label>
                            <input type="checkbox" id="animal_bien" name="animal_bien" value="1" <?= (!empty($editing_data) && $editing_data['animal_biens']) ? 'checked' : '' ?>>
                        </div>
                    </div>
                </form>
            </div>

            <div class="section">
                <h3><i class="fas fa-camera"></i> Photos de votre propriété</h3>
                <div class="photo-upload">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Glissez-déposez vos photos ici ou cliquez pour sélectionner</p>
                    <input type="file" id="photos" name="photos[]" accept="image/*" multiple style="display: none;">
                    <button type="button" class="btn" onclick="document.getElementById('photos').click()">Choisir des fichiers</button>
                </div>
                <div id="photo-preview" class="photo-preview"></div>
                <?php if (!empty($editing_data)): ?>
                    <div class="existing-photos">
                        <?php
                        $stmtp = $pdo->prepare('SELECT id_photo, lien_photo FROM Photos WHERE id_biens = :id_biens');
                        $stmtp->execute([':id_biens' => $editing_data['id_biens']]);
                        $existing_photos = $stmtp->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($existing_photos as $p) {
                            echo '<div class="photo-item">';
                            echo '<img src="/' . htmlspecialchars($p['lien_photo']) . '" alt="Photo existante">';
                            echo '<label><input type="checkbox" name="delete_photo_ids[]" value="' . (int)$p['id_photo'] . '"><i class="fas fa-trash"></i></label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="actions">
                <?php if (!empty($editing_data)): ?>
                    <button class="btn" type="submit" form="annonce_form" name="update_annonce">Mettre à jour l'annonce</button>
                    <button class="btn btn-danger" type="submit" form="annonce_form" name="delete_annonce" value="<?= htmlspecialchars($editing_data['id_biens']) ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');">Supprimer</button>
                    <a href="/Projet_HAP(House_After_Party)/forms/Annonce.form.php" class="btn btn-secondary">Annuler</a>
                <?php else: ?>
                    <button class="btn" type="submit" form="annonce_form" name="submit_annonce">Publier l'annonce</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="listings">
            <h3><i class="fas fa-list"></i> Vos annonces récentes</h3>
            <div class="listing-grid">
                <?php foreach ($annonces as $a): ?>
                    <div class="listing-card">
                        <?php
                        $stmtp = $pdo->prepare('SELECT lien_photo FROM Photos WHERE id_biens = :id_biens LIMIT 1');
                        $stmtp->execute([':id_biens' => $a['id_biens']]);
                        $photo = $stmtp->fetch(PDO::FETCH_COLUMN);
                        ?>
                        <img src="<?= $photo ? '/Projet_HAP(House_After_Party)/' . htmlspecialchars($photo) : 'https://via.placeholder.com/300x200?text=No+Image' ?>" alt="Photo de l'annonce">
                        <div class="content">
                            <h4><?= htmlspecialchars($a['nom_biens']) ?></h4>
                            <p><i class="fas fa-building"></i> <?= htmlspecialchars($a['designation_type_bien'] ?? 'Type non spécifié') ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($a['nom_commune'] ?? 'Commune non spécifiée') ?></p>
                            <p><i class="fas fa-ruler-combined"></i> <?= htmlspecialchars($a['superficie_biens']) ?> m²</p>
                            <div class="actions">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($a['id_biens']) ?>">
                                    <button type="submit" class="btn">Modifier</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>

