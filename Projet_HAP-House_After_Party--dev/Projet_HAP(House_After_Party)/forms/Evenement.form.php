<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un événement
        if (isset($_POST['add_evenement'])) {
            $nom = trim($_POST['nom_evenement'] ?? '');
            $date_debut = trim($_POST['date_debut_evenement'] ?? '');
            $date_fin = trim($_POST['date_fin_evenement'] ?? '');
            $desc = trim($_POST['description_evenement'] ?? '');
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type = intval($_POST['id_type_evenement'] ?? 0);

            // Validation des dates
            if (strtotime($date_debut) > strtotime($date_fin)) {
                $message = "La date de début ne peut pas être après la date de fin.";
            } elseif ($nom && $date_debut && $date_fin && $desc && $id_commune && $id_type) {
                $stmt = $pdo->prepare('INSERT INTO Evenement (nom_evenement, date_debut_evenement, date_fin_evenement, description_evenement, id_commune, id_type_evenement) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$nom, $date_debut, $date_fin, $desc, $id_commune, $id_type]);
                $message = "Événement ajouté avec succès.";
            } else {
                $message = "Veuillez remplir tous les champs obligatoires.";
            }
        }

        // Modification d'un événement
        if (isset($_POST['edit_evenement']) && isset($_POST['id_evenement'])) {
            $id = intval($_POST['id_evenement']);
            $nom = trim($_POST['nom_evenement_edit'] ?? '');
            $date_debut = trim($_POST['date_debut_evenement_edit'] ?? '');
            $date_fin = trim($_POST['date_fin_evenement_edit'] ?? '');
            $desc = trim($_POST['description_evenement_edit'] ?? '');
            $id_commune = intval($_POST['id_commune_edit'] ?? 0);
            $id_type = intval($_POST['id_type_evenement_edit'] ?? 0);

            // Validation des dates
            if (strtotime($date_debut) > strtotime($date_fin)) {
                $message = "La date de début ne peut pas être après la date de fin.";
            } elseif ($nom && $date_debut && $date_fin && $desc && $id_commune && $id_type) {
                $stmt = $pdo->prepare('UPDATE Evenement SET nom_evenement = ?, date_debut_evenement = ?, date_fin_evenement = ?, description_evenement = ?, id_commune = ?, id_type_evenement = ? WHERE id_evenement = ?');
                $stmt->execute([$nom, $date_debut, $date_fin, $desc, $id_commune, $id_type, $id]);
                $message = "Événement modifié avec succès.";
            } else {
                $message = "Veuillez remplir tous les champs obligatoires.";
            }
        }

        // Suppression d'un événement
        if (isset($_POST['delete_evenement']) && isset($_POST['id_evenement'])) {
            $id = intval($_POST['id_evenement']);
            $stmt = $pdo->prepare('DELETE FROM Evenement WHERE id_evenement = ?');
            $stmt->execute([$id]);
            $message = "Événement supprimé avec succès.";
        }

        // Récupération des événements
        $evenements = $pdo->query('SELECT e.*, c.nom_commune, t.lib_type_evenement FROM Evenement e LEFT JOIN Commune c ON e.id_commune = c.id_commune LEFT JOIN Type_Evenement t ON e.id_type_evenement = t.id_type_evenement ORDER BY e.id_evenement DESC')->fetchAll(PDO::FETCH_ASSOC);

        // Communes et types
        $communes = $pdo->query('SELECT id_commune, nom_commune FROM Commune LIMIT 100')->fetchAll(PDO::FETCH_ASSOC); // Limit for performance
        $types = $pdo->query('SELECT * FROM Type_Evenement')->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Gestion des Événements</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../Css/forms.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
        :root {
            --event-primary: #6366f1;
            --event-secondary: #8b5cf6;
            --event-success: #10b981;
            --event-danger: #ef4444;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .events-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .events-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .events-header h1 {
            font-size: 3em;
            margin: 0 0 10px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .events-header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-box {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-box:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 3em;
            font-weight: bold;
            background: linear-gradient(135deg, var(--event-primary), var(--event-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .form-card h2 {
            margin-top: 0;
            color: var(--event-primary);
            font-size: 1.8em;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--event-primary);
        }

        .events-grid {
            display: grid;
            gap: 20px;
        }

        .event-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 5px solid var(--event-primary);
            position: relative;
            overflow: hidden;
        }

        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--event-primary), var(--event-secondary));
            opacity: 0.1;
            border-radius: 0 15px 0 50%;
        }

        .event-card:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .event-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #1e293b;
            margin: 0;
        }

        .event-type {
            background: linear-gradient(135deg, var(--event-primary), var(--event-secondary));
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .event-dates {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .event-date {
            flex: 1;
        }

        .event-date-label {
            font-size: 0.75em;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .event-date-value {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--event-primary);
        }

        .event-description {
            color: #475569;
            line-height: 1.6;
            margin: 15px 0;
        }

        .event-location {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            margin-top: 15px;
            font-size: 0.95em;
        }

        .event-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9em;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--event-primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--event-primary), var(--event-secondary));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99,102,241,0.3);
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Date inputs enhancement */
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            filter: invert(0.5);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .events-header h1 {
                font-size: 2em;
            }

            .event-dates {
                flex-direction: column;
                gap: 10px;
            }

            .event-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
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

            $("#evenement_form").on('submit', function(e) {
                if (!$("#id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }
            });

            // Autocomplete for edit commune fields
            <?php foreach ($evenements as $e): ?>
                $("#commune_edit_<?= $e['id_evenement'] ?>").autocomplete({
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
                        $("#commune_edit_<?= $e['id_evenement'] ?>").val(ui.item.label);
                        $("#id_commune_edit_<?= $e['id_evenement'] ?>").val(ui.item.id);
                        return false;
                    }
                });

                $("#commune_edit_<?= $e['id_evenement'] ?>").on('input', function() {
                    $("#id_commune_edit_<?= $e['id_evenement'] ?>").val('');
                });
            <?php endforeach; ?>
        });
    </script>
</head>
<body>
    <div class="events-container">
        <div class="events-header">
            <h1>🎉 Gestion des Événements</h1>
            <p>Organisez et gérez les événements de votre région</p>
            <a href="../../index.php" style="color: white; text-decoration: none; display: inline-block; margin-top: 15px; opacity: 0.9;">
                ← Retour à l'accueil
            </a>
        </div>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'succès') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-number"><?= count($evenements) ?></div>
                <div class="stat-label">Événements totaux</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php
                    $upcoming = array_filter($evenements, function($e) {
                        return strtotime($e['date_debut_evenement']) > time();
                    });
                    echo count($upcoming);
                    ?>
                </div>
                <div class="stat-label">À venir</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <?php
                    $ongoing = array_filter($evenements, function($e) {
                        $now = time();
                        return strtotime($e['date_debut_evenement']) <= $now && strtotime($e['date_fin_evenement']) >= $now;
                    });
                    echo count($ongoing);
                    ?>
                </div>
                <div class="stat-label">En cours</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?= count($types) ?></div>
                <div class="stat-label">Types d'événements</div>
            </div>
        </div>

        <div class="content-grid">
            <!-- Form Card -->
            <div class="form-card">
                <h2>➕ Nouvel Événement</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="nom_evenement">Nom de l'événement *</label>
                        <input type="text" name="nom_evenement" id="nom_evenement" required 
                               placeholder="Ex: Festival de musique 2025">
                    </div>

                    <div class="form-group">
                        <label for="id_type_evenement">Type d'événement *</label>
                        <select name="id_type_evenement" id="id_type_evenement" required>
                            <option value="">Sélectionner un type</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['id_type_evenement'] ?>">
                                    <?= htmlspecialchars($t['lib_type_evenement']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="commune">Commune *</label>
                        <input type="text" name="commune" id="commune" 
                               placeholder="Rechercher une commune..." required>
                        <input type="hidden" name="id_commune" id="id_commune" required>
                    </div>

                    <div class="form-group">
                        <label for="date_debut_evenement">Date de début *</label>
                        <input type="date" name="date_debut_evenement" id="date_debut_evenement" required>
                    </div>

                    <div class="form-group">
                        <label for="date_fin_evenement">Date de fin *</label>
                        <input type="date" name="date_fin_evenement" id="date_fin_evenement" required>
                    </div>

                    <div class="form-group">
                        <label for="description_evenement">Description *</label>
                        <textarea name="description_evenement" id="description_evenement" 
                                  rows="4" required placeholder="Décrivez l'événement..."></textarea>
                    </div>

                    <button type="submit" name="add_evenement" class="btn-submit">
                        ✨ Créer l'événement
                    </button>
                </form>
            </div>

            <!-- Events List -->
            <div>
                <div class="form-card" style="margin-bottom: 20px;">
                    <h2>📅 Liste des Événements</h2>
                </div>
                
                <div class="events-grid">
                    <?php if (empty($evenements)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <h3>Aucun événement</h3>
                            <p>Créez votre premier événement pour commencer !</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($evenements as $e): ?>
                            <div class="event-card">
                                <div class="event-header">
                                    <h3 class="event-title"><?= htmlspecialchars($e['nom_evenement']) ?></h3>
                                    <span class="event-type"><?= htmlspecialchars($e['lib_type_evenement']) ?></span>
                                </div>

                                <div class="event-dates">
                                    <div class="event-date">
                                        <div class="event-date-label">Début</div>
                                        <div class="event-date-value">
                                            <?= date('d/m/Y', strtotime($e['date_debut_evenement'])) ?>
                                        </div>
                                    </div>
                                    <div class="event-date">
                                        <div class="event-date-label">Fin</div>
                                        <div class="event-date-value">
                                            <?= date('d/m/Y', strtotime($e['date_fin_evenement'])) ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="event-description">
                                    <?= nl2br(htmlspecialchars($e['description_evenement'])) ?>
                                </div>

                                <div class="event-location">
                                    📍 <?= htmlspecialchars($e['nom_commune']) ?>
                                </div>

                                <div class="event-actions">
                                    <button class="btn btn-edit" onclick="editEvent(<?= $e['id_evenement'] ?>)">
                                        ✏️ Modifier
                                    </button>
                                    <form method="POST" style="display: inline; flex: 1;">
                                        <input type="hidden" name="id_evenement" value="<?= $e['id_evenement'] ?>">
                                        <button type="submit" name="delete_evenement" class="btn btn-delete" 
                                                onclick="return confirm('Supprimer cet événement ?')" style="width: 100%;">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                </div>

                                <!-- Edit Form (hidden) -->
                                <div id="edit-form-<?= $e['id_evenement'] ?>" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                                    <form method="POST">
                                        <input type="hidden" name="id_evenement" value="<?= $e['id_evenement'] ?>">
                                        
                                        <div class="form-group">
                                            <label>Nom de l'événement *</label>
                                            <input type="text" name="nom_evenement_edit" 
                                                   value="<?= htmlspecialchars($e['nom_evenement']) ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Type *</label>
                                            <select name="id_type_evenement_edit" required>
                                                <?php foreach ($types as $t): ?>
                                                    <option value="<?= $t['id_type_evenement'] ?>" 
                                                            <?= $t['id_type_evenement'] == $e['id_type_evenement'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($t['lib_type_evenement']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Commune *</label>
                                            <input type="text" name="commune_edit" id="commune_edit_<?= $e['id_evenement'] ?>" 
                                                   value="<?= htmlspecialchars($e['nom_commune']) ?>" required>
                                            <input type="hidden" name="id_commune_edit" 
                                                   id="id_commune_edit_<?= $e['id_evenement'] ?>" 
                                                   value="<?= $e['id_commune'] ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Date de début *</label>
                                            <input type="date" name="date_debut_evenement_edit" 
                                                   value="<?= $e['date_debut_evenement'] ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Date de fin *</label>
                                            <input type="date" name="date_fin_evenement_edit" 
                                                   value="<?= $e['date_fin_evenement'] ?>" required>
                                        </div>

                                        <div class="form-group">
                                            <label>Description *</label>
                                            <textarea name="description_evenement_edit" rows="4" required><?= htmlspecialchars($e['description_evenement']) ?></textarea>
                                        </div>

                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" name="edit_evenement" class="btn-submit" style="flex: 1;">
                                                💾 Enregistrer
                                            </button>
                                            <button type="button" class="btn btn-delete" onclick="cancelEdit(<?= $e['id_evenement'] ?>)">
                                                ✖️ Annuler
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editEvent(id) {
            document.getElementById('edit-form-' + id).style.display = 'block';
        }

        function cancelEdit(id) {
            document.getElementById('edit-form-' + id).style.display = 'none';
        }
    </script>
</body>
</html>
