<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Pts_Interet/PtsInteret.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        $ptsInteretObj = new PtsInteret(null, null, null, null, null, $pdo);

        // Ajout d'un point d'intérêt
        if (isset($_POST['add_pts_interet'])) {
            $lib = trim($_POST['lib_pts_interet'] ?? '');
            $desc = trim($_POST['description_pts_interet'] ?? '');
            $rue = trim($_POST['rue_pts_interet'] ?? '');
            $type = intval($_POST['id_type_points_interet'] ?? 0);
            $commune = intval($_POST['id_commune'] ?? 0);
            $commune_validated = intval($_POST['commune_validated'] ?? 0);
            
            if ($lib !== '' && $type > 0 && $commune > 0 && $commune_validated === 1) {
                try {
                    // Vérifier si la colonne rue existe, sinon utiliser l'ancienne méthode
                    $stmt = $pdo->prepare('SHOW COLUMNS FROM Pts_Interet LIKE "rue_pts_interet"');
                    $stmt->execute();
                    $hasRueColumn = $stmt->fetch();
                    
                    if ($hasRueColumn) {
                        // Nouvelle version avec rue
                        $stmt = $pdo->prepare('INSERT INTO Pts_Interet (lib_pts_interet, description_pts_interet, rue_pts_interet, id_type_points_interet, id_commune) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$lib, $desc, $rue, $type, $commune]);
                    } else {
                        // Ancienne version sans rue (fallback)
                        if ($ptsInteretObj->createPtsInteret($lib, $desc, $type, $commune)) {
                            $message = "Point d'intérêt ajouté avec succès.";
                        } else {
                            $message = "Erreur lors de l'ajout.";
                        }
                    }
                    $message = "Point d'intérêt ajouté avec succès.";
                } catch (Exception $e) {
                    $message = "Erreur lors de l'ajout : " . $e->getMessage();
                }
            } else {
                if ($commune_validated !== 1) {
                    $message = "Veuillez sélectionner une commune valide dans la liste d'autocomplétion.";
                } else {
                    $message = "Tous les champs requis doivent être remplis.";
                }
            }
        }

        // Modification d'un point d'intérêt
        if (isset($_POST['edit_pts_interet']) && isset($_POST['id_pts_interet'])) {
            $id = intval($_POST['id_pts_interet']);
            $lib = trim($_POST['lib_pts_interet_edit'] ?? '');
            $desc = trim($_POST['description_pts_interet_edit'] ?? '');
            if ($lib !== '') {
                if ($ptsInteretObj->updatePtsInteret($id, $lib, $desc)) {
                    $message = "Point d'intérêt modifié avec succès.";
                } else {
                    $message = "Erreur lors de la modification.";
                }
            } else {
                $message = "Le nom ne peut pas être vide.";
            }
        }

        // Suppression d'un point d'intérêt
        if (isset($_POST['delete_pts_interet']) && isset($_POST['id_pts_interet'])) {
            $id = intval($_POST['id_pts_interet']);
            if ($ptsInteretObj->deletePtsInteret($id)) {
                $message = "Point d'intérêt supprimé avec succès.";
            } else {
                $message = "Erreur lors de la suppression.";
            }
        }

        // Récupération des points d'intérêt avec gestion de la colonne rue
        try {
            $stmt = $pdo->prepare('SHOW COLUMNS FROM Pts_Interet LIKE "rue_pts_interet"');
            $stmt->execute();
            $hasRueColumn = $stmt->fetch();
            
            if ($hasRueColumn) {
                $ptsInterets = $pdo->query("SELECT pi.*, c.nom_commune, c.latitude_commune, c.longitude_commune, t.lib_type_points_interet FROM Pts_Interet pi LEFT JOIN Commune c ON pi.id_commune = c.id_commune LEFT JOIN Type_Pts_Interet t ON pi.id_type_points_interet = t.id_type_points_interet")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $ptsInterets = $ptsInteretObj->getAllPtsInteret();
            }
        } catch (Exception $e) {
            $ptsInterets = $ptsInteretObj->getAllPtsInteret();
        }

        // Récupération des types pour le dropdown
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet ORDER BY lib_type_points_interet')->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des communes pour le dropdown
        $communes = $pdo->query('SELECT * FROM Commune ORDER BY nom_commune')->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Points d'Intérêt - HAP</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../Css/forms.css">
    <style>
        :root {
            --pts-bg: #f7f7f9;
            --pts-card-bg: #fff;
            --pts-text: #333;
            --pts-heading: #a100b8;
            --pts-accent: #a100b8;
            --pts-gradient: linear-gradient(135deg, #a100b8 0%, #4b006e 100%);
            --pts-border: #e0e0e0;
            --pts-hover: #f3e6fa;
            --pts-shadow: 0 4px 15px rgba(161, 0, 184, 0.1);
        }

        [data-theme="dark"] {
            --pts-bg: #0d0d0d;
            --pts-card-bg: #1e1e1e;
            --pts-text: #bb86fc;
            --pts-heading: #bb86fc;
            --pts-accent: #bb86fc;
            --pts-gradient: linear-gradient(135deg, #bb86fc 0%, #7c4dff 100%);
            --pts-border: #333;
            --pts-hover: #2a2a2a;
            --pts-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background: var(--pts-bg);
            color: var(--pts-text);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--pts-gradient);
            color: #fff;
            padding: 40px 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: var(--pts-shadow);
        }

        .header h2 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            font-size: 1.1em;
            opacity: 0.9;
        }

        .back-link {
            display: inline-block;
            color: var(--pts-accent);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 20px;
            border-radius: 25px;
            transition: background 0.3s;
        }

        .back-link:hover {
            background: var(--pts-hover);
        }

        .success, .error {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-section {
            background: var(--pts-card-bg);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: var(--pts-shadow);
        }

        .form-section h3 {
            color: var(--pts-heading);
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.5em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: var(--pts-text);
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9em;
        }

        input[type="text"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--pts-border);
            background: var(--pts-card-bg);
            color: var(--pts-text);
            border-radius: 8px;
            font-size: 1em;
            font-family: 'Montserrat', Arial, sans-serif;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--pts-accent);
            outline: none;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Autocomplete styles */
        .ui-autocomplete {
            z-index: 9999 !important;
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--pts-card-bg) !important;
            border: 2px solid var(--pts-accent) !important;
            border-radius: 8px;
        }

        .ui-menu-item {
            padding: 5px 10px;
            color: var(--pts-text);
        }

        .ui-menu-item:hover,
        .ui-menu-item.ui-state-active {
            background: var(--pts-hover) !important;
            color: var(--pts-accent) !important;
            border: none !important;
        }

        .validation-icon {
            display: inline-block;
            margin-left: 10px;
            font-size: 1.2em;
        }

        .btn {
            background: var(--pts-gradient);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(161, 0, 184, 0.2);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 0, 184, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .pts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .pts-card {
            background: var(--pts-card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--pts-shadow);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .pts-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(161, 0, 184, 0.2);
            border-color: var(--pts-accent);
        }

        .pts-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .pts-card-title {
            color: var(--pts-heading);
            font-size: 1.3em;
            font-weight: 700;
            margin: 0;
            flex: 1;
        }

        .pts-card-id {
            background: var(--pts-gradient);
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .pts-card-description {
            color: var(--pts-text);
            margin: 15px 0;
            line-height: 1.6;
            min-height: 50px;
        }

        .pts-card-meta {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .pts-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9em;
            background: var(--pts-hover);
            padding: 5px 12px;
            border-radius: 15px;
        }

        .pts-card-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid var(--pts-border);
        }

        .pts-card-actions button {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9em;
        }

        .edit-mode-card {
            border-color: #ffc107;
            background: var(--pts-hover);
        }

        .edit-form-inline input,
        .edit-form-inline textarea {
            margin-bottom: 10px;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--pts-card-bg);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--pts-shadow);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: var(--pts-accent);
            margin: 10px 0;
        }

        .stat-label {
            color: var(--pts-text);
            font-size: 0.9em;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .pts-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .header h2 {
                font-size: 1.8em;
            }
        }
    </style>
</head>
<body>
    <?php include '../../theme_toggle.php'; ?>
    <div class="container">
        <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="header">
            <h1>🎵 Points d'Intérêt</h1>
            <p>Gérez les boîtes de nuit et lieux festifs autour de vos biens</p>
        </div>
        
        <?php if ($message): ?>
            <div class="<?= strpos($message, 'Erreur') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-label">Total Points</div>
                <div class="stat-number"><?= count($ptsInterets) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Types</div>
                <div class="stat-number"><?= count($types) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Communes</div>
                <div class="stat-number"><?= count($communes) ?></div>
            </div>
        </div>

        <!-- Filtres et Recherche -->
        <div class="form-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 30px;">
            <h3 style="color: white;">🔍 Rechercher et filtrer</h3>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr auto;">
                <div class="form-group">
                    <label for="search-name" style="color: white;">Nom du lieu</label>
                    <input type="text" id="search-name" placeholder="Rechercher..." onkeyup="filterPoints()">
                </div>
                <div class="form-group">
                    <label for="filter-type" style="color: white;">Type</label>
                    <select id="filter-type" onchange="filterPoints()">
                        <option value="">Tous les types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= htmlspecialchars($t['lib_type_points_interet']) ?>">
                                <?= htmlspecialchars($t['lib_type_points_interet']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter-commune" style="color: white;">Commune</label>
                    <select id="filter-commune" onchange="filterPoints()">
                        <option value="">Toutes les communes</option>
                        <?php 
                        $uniqueCommunes = array_unique(array_column($ptsInterets, 'nom_commune'));
                        sort($uniqueCommunes);
                        foreach ($uniqueCommunes as $c): 
                            if ($c):
                        ?>
                            <option value="<?= htmlspecialchars($c) ?>">
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="color: white; opacity: 0;">Action</label>
                    <button type="button" onclick="resetFilters()" style="background: white; color: #667eea; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                        🔄 Réinitialiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Formulaire d'ajout -->
        <div class="form-section">
            <h3>➕ Ajouter un nouveau point d'intérêt</h3>
            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="lib_pts_interet">🏷️ Nom du lieu *</label>
                        <input type="text" id="lib_pts_interet" name="lib_pts_interet" placeholder="Ex: Le Paradise Club" required>
                    </div>
                    <div class="form-group">
                        <label for="id_type_points_interet">🎭 Type de lieu *</label>
                        <select name="id_type_points_interet" id="id_type_points_interet" required>
                            <option value="">-- Sélectionner un type --</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                                    <?= htmlspecialchars($t['lib_type_points_interet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_commune">📍 Commune *</label>
                        <input type="text" id="commune" name="commune" placeholder="Tapez pour rechercher une commune..." required autocomplete="off">
                        <input type="hidden" id="id_commune" name="id_commune" value="">
                        <input type="hidden" id="commune_validated" name="commune_validated" value="0">
                        <span id="commune_validation_icon" class="validation-icon"></span>
                    </div>
                    <div class="form-group">
                        <label for="rue_pts_interet">🏠 Adresse</label>
                        <input type="text" id="rue_pts_interet" name="rue_pts_interet" placeholder="Tapez pour rechercher une rue..." autocomplete="off">
                        <input type="hidden" id="rue_validated" name="rue_validated" value="0">
                        <span id="rue_validation_icon" class="validation-icon"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="description_pts_interet">📝 Description</label>
                    <textarea id="description_pts_interet" name="description_pts_interet" placeholder="Décrivez ce lieu festif..."></textarea>
                </div>
                <button type="submit" name="add_pts_interet" class="btn">✨ Ajouter le point d'intérêt</button>
            </form>
        </div>

        <!-- Liste des points d'intérêt -->
        <div class="form-section">
            <h3>📍 Liste des points d'intérêt (<?= count($ptsInterets) ?>)</h3>
            
            <?php if (empty($ptsInterets)): ?>
                <p style="text-align: center; padding: 40px; color: var(--pts-text); opacity: 0.6;">
                    Aucun point d'intérêt pour le moment. Ajoutez-en un ci-dessus !
                </p>
            <?php else: ?>
                <div class="pts-grid cards-grid">
                    <?php foreach ($ptsInterets as $pi): ?>
                        <div class="pts-card <?= (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $pi['id_pts_interet']) ? 'edit-mode-card' : '' ?>"
                             data-name="<?= htmlspecialchars($pi['lib_pts_interet']) ?>"
                             data-type="<?= htmlspecialchars($pi['lib_type_points_interet']) ?>"
                             data-commune="<?= htmlspecialchars($pi['nom_commune'] ?? '') ?>">
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $pi['id_pts_interet']): ?>
                                <!-- Mode édition -->
                                <form method="post" class="edit-form-inline">
                                    <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($pi['id_pts_interet']) ?>">
                                    
                                    <div class="pts-card-header">
                                        <div class="pts-card-id">ID: <?= htmlspecialchars($pi['id_pts_interet']) ?></div>
                                    </div>
                                    
                                    <input type="text" name="lib_pts_interet_edit" value="<?= htmlspecialchars($pi['lib_pts_interet']) ?>" required placeholder="Nom du lieu">
                                    <textarea name="description_pts_interet_edit" placeholder="Description"><?= htmlspecialchars($pi['description_pts_interet']) ?></textarea>
                                    
                                    <div class="pts-card-meta">
                                        <div class="pts-meta-item">
                                            <span>🎭</span>
                                            <span><?= htmlspecialchars($pi['lib_type_points_interet']) ?></span>
                                        </div>
                                        <div class="pts-meta-item">
                                            <span>📍</span>
                                            <span><?= htmlspecialchars($pi['nom_commune']) ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="pts-card-actions">
                                        <button type="submit" name="edit_pts_interet" class="btn">💾 Enregistrer</button>
                                        <button type="submit" name="cancel_edit" class="btn btn-secondary">❌ Annuler</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <!-- Mode affichage -->
                                <div class="pts-card-header">
                                    <h4 class="pts-card-title"><?= htmlspecialchars($pi['lib_pts_interet']) ?></h4>
                                    <div class="pts-card-id">ID: <?= htmlspecialchars($pi['id_pts_interet']) ?></div>
                                </div>
                                
                                <p class="pts-card-description">
                                    <?= htmlspecialchars($pi['description_pts_interet']) ?: 'Aucune description' ?>
                                </p>
                                
                                <div class="pts-card-meta">
                                    <?php if (!empty($pi['rue_pts_interet'])): ?>
                                    <div class="pts-meta-item">
                                        <span>🏠</span>
                                        <span><?= htmlspecialchars($pi['rue_pts_interet']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="pts-meta-item">
                                        <span>📍</span>
                                        <span><?= htmlspecialchars($pi['nom_commune']) ?></span>
                                    </div>
                                    <div class="pts-meta-item">
                                        <span>🎭</span>
                                        <span><?= htmlspecialchars($pi['lib_type_points_interet']) ?></span>
                                    </div>
                                </div>
                                
                                <div class="pts-card-actions">
                                    <a href="pts_interet_detail.php?id=<?= $pi['id_pts_interet'] ?>" class="btn" style="text-decoration: none; width: 100%; text-align: center; display: block;">
                                        👁️ Voir détails & modifier
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="../js/autocomplete.js"></script>
    <script src="../js/confirm_delete.js"></script>
    <script>
        $(document).ready(function() {
            let selectedCodeInsee = '';
            
            // Autocomplete pour les communes
            $('#commune').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '../api/search_communes.php',
                        dataType: 'json',
                        data: { term: request.term },
                        success: function(data) {
                            response(data.map(function(item) {
                                return {
                                    label: item.nom_commune + ' (' + item.cp_commune + ')',
                                    value: item.nom_commune,
                                    id_commune: item.id_commune,
                                    code_insee: item.code_insee,
                                    cp_commune: item.cp_commune
                                };
                            }));
                        },
                        error: function() {
                            response([]);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#id_commune').val(ui.item.id_commune);
                    $('#commune_validated').val('1');
                    $('#commune_validation_icon').html('✅').css('color', 'green');
                    selectedCodeInsee = ui.item.code_insee;
                    
                    // Réinitialiser le champ rue
                    $('#rue_pts_interet').val('').prop('disabled', false);
                    $('#rue_validated').val('0');
                    $('#rue_validation_icon').html('');
                    
                    return true;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $('#id_commune').val('');
                        $('#commune_validated').val('0');
                        $('#commune_validation_icon').html('❌').css('color', 'red');
                        $('#rue_pts_interet').val('').prop('disabled', true);
                        selectedCodeInsee = '';
                    }
                }
            });

            // Désactiver le champ rue au départ
            $('#rue_pts_interet').prop('disabled', true);

            // Autocomplete pour les rues avec recherche parallèle par préfixes
            $('#rue_pts_interet').autocomplete({
                source: function(request, response) {
                    if (!selectedCodeInsee) {
                        response([]);
                        return;
                    }

                    const prefixes = [
                        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
                        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
                        'rue', 'avenue', 'boulevard', 'place', 'chemin', 'impasse', 'allée'
                    ];

                    let allStreets = [];
                    let completed = 0;

                    prefixes.forEach(function(prefix) {
                        $.ajax({
                            url: 'https://api-adresse.data.gouv.fr/search/',
                            dataType: 'json',
                            data: {
                                q: prefix,
                                citycode: selectedCodeInsee,
                                type: 'street',
                                limit: 50
                            },
                            success: function(data) {
                                if (data.features) {
                                    data.features.forEach(function(feature) {
                                        if (feature.properties.type === 'street') {
                                            allStreets.push({
                                                label: feature.properties.name,
                                                value: feature.properties.name
                                            });
                                        }
                                    });
                                }
                            },
                            complete: function() {
                                completed++;
                                if (completed === prefixes.length) {
                                    // Dédupliquer les rues
                                    const uniqueStreets = [];
                                    const seen = new Set();
                                    allStreets.forEach(function(street) {
                                        const key = street.label.toLowerCase();
                                        if (!seen.has(key)) {
                                            seen.add(key);
                                            uniqueStreets.push(street);
                                        }
                                    });

                                    // Filtrer selon la recherche de l'utilisateur
                                    const term = request.term.toLowerCase();
                                    const filtered = uniqueStreets.filter(function(street) {
                                        return street.label.toLowerCase().includes(term);
                                    });

                                    response(filtered.slice(0, 100));
                                }
                            }
                        });
                    });
                },
                minLength: 1,
                select: function(event, ui) {
                    $('#rue_validated').val('1');
                    $('#rue_validation_icon').html('✅').css('color', 'green');
                    return true;
                },
                change: function(event, ui) {
                    if (!ui.item) {
                        $('#rue_validated').val('0');
                        $('#rue_validation_icon').html('❌').css('color', 'red');
                    }
                }
            });

            // Réinitialiser les validations quand l'utilisateur tape
            $('#commune').on('input', function() {
                if ($(this).val() === '') {
                    $('#id_commune').val('');
                    $('#commune_validated').val('0');
                    $('#commune_validation_icon').html('');
                    $('#rue_pts_interet').val('').prop('disabled', true);
                    selectedCodeInsee = '';
                }
            });

            $('#rue_pts_interet').on('input', function() {
                if ($(this).val() !== '' && $('#rue_validated').val() === '0') {
                    $('#rue_validation_icon').html('⏳').css('color', 'orange');
                }
            });
        });

        // Filtrage des points d'intérêt
        function filterPoints() {
            const searchName = document.getElementById('search-name').value.toLowerCase();
            const filterType = document.getElementById('filter-type').value.toLowerCase();
            const filterCommune = document.getElementById('filter-commune').value.toLowerCase();
            
            const cards = document.querySelectorAll('.pts-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const name = card.getAttribute('data-name').toLowerCase();
                const type = card.getAttribute('data-type').toLowerCase();
                const commune = card.getAttribute('data-commune').toLowerCase();
                
                const matchName = name.includes(searchName);
                const matchType = filterType === '' || type === filterType;
                const matchCommune = filterCommune === '' || commune === filterCommune;
                
                if (matchName && matchType && matchCommune) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Afficher message si aucun résultat
            const noResults = document.getElementById('no-results');
            if (visibleCount === 0) {
                if (!noResults) {
                    const msg = document.createElement('div');
                    msg.id = 'no-results';
                    msg.style.cssText = 'text-align: center; padding: 40px; color: #999; font-size: 1.2em;';
                    msg.innerHTML = '📭 Aucun résultat trouvé';
                    document.querySelector('.cards-grid').appendChild(msg);
                }
            } else if (noResults) {
                noResults.remove();
            }
        }
        
        function resetFilters() {
            document.getElementById('search-name').value = '';
            document.getElementById('filter-type').value = '';
            document.getElementById('filter-commune').value = '';
            filterPoints();
        }
    </script>
</body>
</html>
