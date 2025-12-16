<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
$id_pts_interet = intval($_GET['id'] ?? 0);
$editMode = false;

// Vérifier si l'utilisateur est admin/animateur
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'animateur';

if (!$id_pts_interet) {
    header('Location: PtsInteret.form.php');
    exit;
}

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Récupération des types et communes pour les dropdowns
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet ORDER BY lib_type_points_interet')->fetchAll(PDO::FETCH_ASSOC);
        $communes = $pdo->query('SELECT * FROM Commune ORDER BY nom_commune LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);

        // Modification du point d'intérêt (admin seulement)
        if (isset($_POST['update_pts_interet']) && $isAdmin) {
            $lib = $_POST['lib_pts_interet'] ?? '';
            $desc = $_POST['description_pts_interet'] ?? '';
            $rue = $_POST['rue_pts_interet'] ?? '';
            $id_type = intval($_POST['id_type_points_interet'] ?? 0);
            $id_commune = intval($_POST['id_commune'] ?? 0);

            $stmt = $pdo->prepare('UPDATE Pts_Interet SET lib_pts_interet = ?, description_pts_interet = ?, rue_pts_interet = ?, id_type_points_interet = ?, id_commune = ? WHERE id_pts_interet = ?');
            $stmt->execute([$lib, $desc, $rue, $id_type, $id_commune, $id_pts_interet]);
            $message = "Point d'intérêt modifié avec succès !";
        }

        // Suppression du point d'intérêt (admin seulement)
        if (isset($_POST['delete_pts_interet']) && $isAdmin) {
            $stmt = $pdo->prepare('DELETE FROM Pts_Interet WHERE id_pts_interet = ?');
            $stmt->execute([$id_pts_interet]);
            header('Location: PtsInteret.form.php?message=Point d\'intérêt supprimé');
            exit;
        }

        // Mode édition (admin seulement)
        if (isset($_POST['edit_mode']) && $isAdmin) {
            $editMode = true;
        }

        if (isset($_POST['cancel_edit'])) {
            $editMode = false;
        }

        // Récupération du point d'intérêt avec détails
        $stmt = $pdo->prepare('
            SELECT p.*, c.nom_commune, c.cp_commune, t.lib_type_points_interet 
            FROM Pts_Interet p 
            LEFT JOIN Commune c ON p.id_commune = c.id_commune 
            LEFT JOIN Type_Pts_Interet t ON p.id_type_points_interet = t.id_type_points_interet 
            WHERE p.id_pts_interet = ?
        ');
        $stmt->execute([$id_pts_interet]);
        $ptsInteret = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ptsInteret) {
            header('Location: PtsInteret.form.php');
            exit;
        }

        // Récupération des photos
        $stmt = $pdo->prepare('SELECT * FROM Photos_PtsInteret WHERE id_pts_interet = ? ORDER BY date_ajout DESC');
        $stmt->execute([$id_pts_interet]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Upload de photo (admin seulement)
        if (isset($_POST['upload_photo']) && isset($_FILES['photo']) && $isAdmin) {
            $file = $_FILES['photo'];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                
                if (in_array($file['type'], $allowedTypes)) {
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFileName = 'pts_' . $id_pts_interet . '_' . uniqid() . '.' . $extension;
                    $uploadDir = __DIR__ . '/../images/uploads/';
                    $uploadPath = $uploadDir . $newFileName;
                    
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        $stmt = $pdo->prepare('INSERT INTO Photos_PtsInteret (lien_photo_pts, id_pts_interet) VALUES (?, ?)');
                        $stmt->execute([$newFileName, $id_pts_interet]);
                        $message = "Photo ajoutée avec succès !";
                        
                        // Recharger les photos
                        $stmt = $pdo->prepare('SELECT * FROM Photos_PtsInteret WHERE id_pts_interet = ? ORDER BY date_ajout DESC');
                        $stmt->execute([$id_pts_interet]);
                        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $message = "Erreur lors de l'upload de la photo.";
                    }
                } else {
                    $message = "Format de fichier non autorisé. Utilisez JPG, PNG ou GIF.";
                }
            } else {
                $message = "Erreur lors de l'upload : " . $file['error'];
            }
        }

        // Suppression de photo (admin seulement)
        if (isset($_POST['delete_photo']) && isset($_POST['id_photo']) && $isAdmin) {
            $id_photo = intval($_POST['id_photo']);
            
            $stmt = $pdo->prepare('SELECT lien_photo_pts FROM Photos_PtsInteret WHERE id_photo_pts = ? AND id_pts_interet = ?');
            $stmt->execute([$id_photo, $id_pts_interet]);
            $photo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($photo) {
                $filePath = __DIR__ . '/../images/uploads/' . $photo['lien_photo_pts'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                $stmt = $pdo->prepare('DELETE FROM Photos_PtsInteret WHERE id_photo_pts = ?');
                $stmt->execute([$id_photo]);
                $message = "Photo supprimée avec succès !";
                
                // Recharger les photos
                $stmt = $pdo->prepare('SELECT * FROM Photos_PtsInteret WHERE id_pts_interet = ? ORDER BY date_ajout DESC');
                $stmt->execute([$id_pts_interet]);
                $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ptsInteret['lib_pts_interet']) ?> - Détails</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
    <style>
        :root {
            --pts-primary: #a100b8;
            --pts-secondary: #4b006e;
            --pts-gradient: linear-gradient(135deg, #a100b8 0%, #4b006e 100%);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .detail-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .detail-header {
            background: var(--pts-gradient);
            color: white;
            padding: 40px;
            position: relative;
        }

        .detail-header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            text-align: center;
        }

        .detail-header .type-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-top: 10px;
        }

        .header-actions {
            position: absolute;
            top: 40px;
            right: 40px;
            display: flex;
            gap: 10px;
        }

        .header-actions button,
        .header-actions .btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .header-actions button:hover,
        .header-actions .btn:hover {
            background: white;
            color: var(--pts-primary);
            transform: translateY(-2px);
        }

        .header-actions .btn-danger {
            background: rgba(239, 68, 68, 0.2);
            border-color: #fee2e2;
        }

        .header-actions .btn-danger:hover {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 20px;
            opacity: 0.9;
            transition: opacity 0.3s;
        }

        .back-link:hover {
            opacity: 1;
        }

        .detail-content {
            padding: 40px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .info-card {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--pts-primary);
        }

        .info-label {
            font-size: 0.85em;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .info-value {
            font-size: 1.2em;
            font-weight: 600;
            color: #1e293b;
        }

        .description-section {
            background: #f8fafc;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
        }

        .description-section h2 {
            color: var(--pts-primary);
            margin-top: 0;
        }

        .photos-section {
            margin-top: 40px;
        }

        .photos-section h2 {
            color: var(--pts-primary);
            margin-bottom: 20px;
        }

        .upload-form {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            color: white;
        }

        .upload-form h3 {
            margin-top: 0;
            color: white;
        }

        .upload-zone {
            background: rgba(255,255,255,0.1);
            border: 2px dashed rgba(255,255,255,0.3);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-zone:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.5);
        }

        .upload-zone input[type="file"] {
            display: none;
        }

        .upload-label {
            display: block;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }

        .btn-upload {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s;
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .photo-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .photo-card:hover {
            transform: translateY(-5px);
        }

        .photo-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .photo-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 15px;
            color: white;
        }

        .btn-delete-photo {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
            transition: all 0.3s;
        }

        .btn-delete-photo:hover {
            background: #dc2626;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .empty-photos {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-photos-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Formulaire d'édition */
        .edit-form {
            background: #f8fafc;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .edit-form h3 {
            color: var(--pts-primary);
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--pts-primary);
            box-shadow: 0 0 0 3px rgba(161, 0, 184, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .form-actions button {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-save {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-cancel {
            background: #94a3b8;
            color: white;
        }

        .btn-cancel:hover {
            background: #64748b;
        }
    </style>
</head>
<body>
    <?php include '../../theme_toggle.php'; ?>
    
    <div class="detail-container">
        <div class="detail-header">
            <a href="PtsInteret.form.php" class="back-link">← Retour à la liste</a>
            
            <div class="header-actions">
                <?php if (!$editMode && $isAdmin): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="edit_mode" class="btn">✏️ Modifier</button>
                    </form>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce point d\'intérêt et toutes ses photos ?');">
                        <button type="submit" name="delete_pts_interet" class="btn btn-danger">🗑️ Supprimer</button>
                    </form>
                <?php endif; ?>
            </div>

            <h1><?= htmlspecialchars($ptsInteret['lib_pts_interet']) ?></h1>
            <div style="text-align: center;">
                <span class="type-badge">🎭 <?= htmlspecialchars($ptsInteret['lib_type_points_interet']) ?></span>
            </div>
        </div>

        <div class="detail-content">
            <?php if ($message): ?>
                <div class="message <?= strpos($message, 'Erreur') !== false ? 'error' : 'success' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($editMode): ?>
                <!-- Formulaire d'édition -->
                <div class="edit-form">
                    <h3>✏️ Modifier le point d'intérêt</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>📝 Nom du lieu</label>
                            <input type="text" name="lib_pts_interet" value="<?= htmlspecialchars($ptsInteret['lib_pts_interet']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>📄 Description</label>
                            <textarea name="description_pts_interet" placeholder="Décrivez ce point d'intérêt..."><?= htmlspecialchars($ptsInteret['description_pts_interet']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>🏠 Adresse (rue)</label>
                            <input type="text" name="rue_pts_interet" value="<?= htmlspecialchars($ptsInteret['rue_pts_interet']) ?>" placeholder="Ex: 15 rue de la Fête">
                        </div>

                        <div class="form-group">
                            <label>🎭 Type de lieu</label>
                            <select name="id_type_points_interet" required>
                                <option value="">-- Sélectionnez un type --</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= $type['id_type_points_interet'] ?>" <?= $type['id_type_points_interet'] == $ptsInteret['id_type_points_interet'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['lib_type_points_interet']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>📍 Commune</label>
                            <select name="id_commune" required>
                                <option value="">-- Sélectionnez une commune --</option>
                                <?php foreach ($communes as $commune): ?>
                                    <option value="<?= $commune['id_commune'] ?>" <?= $commune['id_commune'] == $ptsInteret['id_commune'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($commune['nom_commune']) ?> (<?= htmlspecialchars($commune['cp_commune']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_pts_interet" class="btn-save">💾 Enregistrer</button>
                            <button type="submit" name="cancel_edit" class="btn-cancel">❌ Annuler</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Informations principales -->
                <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">📍 Commune</div>
                    <div class="info-value"><?= htmlspecialchars($ptsInteret['nom_commune']) ?></div>
                </div>
                <div class="info-card">
                    <div class="info-label">📮 Code Postal</div>
                    <div class="info-value"><?= htmlspecialchars($ptsInteret['cp_commune']) ?></div>
                </div>
                <?php if (!empty($ptsInteret['rue_pts_interet'])): ?>
                <div class="info-card">
                    <div class="info-label">🛣️ Adresse</div>
                    <div class="info-value"><?= htmlspecialchars($ptsInteret['rue_pts_interet']) ?></div>
                </div>
                <?php endif; ?>
                <div class="info-card">
                    <div class="info-label">🎭 Type</div>
                    <div class="info-value"><?= htmlspecialchars($ptsInteret['lib_type_points_interet']) ?></div>
                </div>
            </div>

            <!-- Description -->
            <?php if (!empty($ptsInteret['description_pts_interet'])): ?>
            <div class="description-section">
                <h2>📝 Description</h2>
                <p><?= nl2br(htmlspecialchars($ptsInteret['description_pts_interet'])) ?></p>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Section Photos -->
            <div class="photos-section">
                <h2>📸 Galerie Photos (<?= count($photos) ?>)</h2>

                <!-- Formulaire d'upload (admin seulement) -->
                <?php if ($isAdmin): ?>
                <div class="upload-form">
                    <h3>Ajouter une photo</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="upload-zone" onclick="document.getElementById('photo-input').click()">
                            <label for="photo-input" class="upload-label">
                                <div class="upload-icon">📷</div>
                                <p>Cliquez pour sélectionner une image</p>
                                <p style="font-size: 0.85em; opacity: 0.8;">JPG, PNG ou GIF - Max 5MB</p>
                            </label>
                            <input type="file" id="photo-input" name="photo" accept="image/*" required onchange="this.form.querySelector('.btn-upload').style.display = 'inline-block'">
                        </div>
                        <button type="submit" name="upload_photo" class="btn-upload" style="display: none;">
                            ⬆️ Uploader la photo
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Grille de photos -->
                <?php if (empty($photos)): ?>
                    <div class="empty-photos">
                        <div class="empty-photos-icon">🖼️</div>
                        <h3>Aucune photo</h3>
                        <p>Ajoutez des photos pour illustrer ce point d'intérêt !</p>
                    </div>
                <?php else: ?>
                    <div class="photos-grid">
                        <?php foreach ($photos as $photo): 
                            // Gérer les différents formats de chemin
                            $lienPhoto = $photo['lien_photo_pts'];
                            if (strpos($lienPhoto, 'Projet_HAP') !== false || strpos($lienPhoto, 'images/uploads/') !== false) {
                                // Chemin complet depuis la racine
                                $photoPath = '/' . $lienPhoto;
                            } else {
                                // Juste le nom du fichier - chercher dans poi/ ou uploads/
                                if (file_exists(__DIR__ . '/../images/uploads/poi/' . $lienPhoto)) {
                                    $photoPath = '../images/uploads/poi/' . $lienPhoto;
                                } else {
                                    $photoPath = '../images/uploads/' . $lienPhoto;
                                }
                            }
                        ?>
                            <div class="photo-card">
                                <a href="<?= htmlspecialchars($photoPath) ?>" data-lightbox="pts-gallery">
                                    <img src="<?= htmlspecialchars($photoPath) ?>" 
                                         alt="Photo de <?= htmlspecialchars($ptsInteret['lib_pts_interet']) ?>">
                                </a>
                                <div class="photo-overlay">
                                    <small><?= date('d/m/Y', strtotime($photo['date_ajout'])) ?></small>
                                    <?php if ($isAdmin): ?>
                                    <form method="POST" style="display: inline; float: right;" onsubmit="return confirm('Supprimer cette photo ?')">
                                        <input type="hidden" name="id_photo" value="<?= $photo['id_photo_pts'] ?>">
                                        <button type="submit" name="delete_photo" class="btn-delete-photo">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</body>
</html>
