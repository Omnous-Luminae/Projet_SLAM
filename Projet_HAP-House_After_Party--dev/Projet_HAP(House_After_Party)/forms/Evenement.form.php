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
    <title>Gestion des Événements</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../Css/forms.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
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
    <div class="form-container admin-form">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <div class="form-header">
            <h2>Gestion des Événements</h2>
            <p>Ajoutez, modifiez et supprimez les événements de la plateforme</p>
        </div>
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <section class="form-section">
            <h3>Ajouter un nouvel événement</h3>
            <form method="post" id="evenement_form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom_evenement">Nom de l'événement</label>
                        <input type="text" id="nom_evenement" name="nom_evenement" placeholder="Nom de l'événement" required>
                    </div>
                    <div class="form-group">
                        <label for="date_debut_evenement">Date de début</label>
                        <input type="date" id="date_debut_evenement" name="date_debut_evenement" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin_evenement">Date de fin</label>
                        <input type="date" id="date_fin_evenement" name="date_fin_evenement" required>
                    </div>
                    <div class="form-group">
                        <label for="description_evenement">Description</label>
                        <textarea id="description_evenement" name="description_evenement" placeholder="Description de l'événement" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="commune">Commune</label>
                        <input type="text" id="commune" placeholder="Commune" required>
                        <input type="hidden" id="id_commune" name="id_commune">
                    </div>
                    <div class="form-group">
                        <label for="id_type_evenement">Type d'événement</label>
                        <select id="id_type_evenement" name="id_type_evenement" required>
                            <option value="">-- Sélectionner un type --</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['id_type_evenement'] ?>"><?= htmlspecialchars($t['lib_type_evenement']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_evenement" class="btn btn-primary">Ajouter l'événement</button>
                </div>
            </form>
        </section>
        <section class="data-section">
            <h3>Événements existants</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Date début</th>
                        <th>Date fin</th>
                        <th>Description</th>
                        <th>Commune</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evenements as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['id_evenement']) ?></td>
                            <td>
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $e['id_evenement']): ?>
                                    <input type="text" name="nom_evenement_edit" value="<?= htmlspecialchars($e['nom_evenement']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($e['nom_evenement']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $e['id_evenement']): ?>
                                    <input type="date" name="date_debut_evenement_edit" value="<?= htmlspecialchars($e['date_debut_evenement']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($e['date_debut_evenement']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $e['id_evenement']): ?>
                                    <input type="date" name="date_fin_evenement_edit" value="<?= htmlspecialchars($e['date_fin_evenement']) ?>" required>
                                <?php else: ?>
                                    <?= htmlspecialchars($e['date_fin_evenement']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $e['id_evenement']): ?>
                                    <textarea name="description_evenement_edit" required style="width: 100%; min-height: 60px;"><?= htmlspecialchars($e['description_evenement']) ?></textarea>
                                <?php else: ?>
                                    <?= htmlspecialchars($e['description_evenement']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $e['id_evenement']): ?>
                                    <input type="text" id="commune_edit_<?= $e['id_evenement'] ?>" placeholder="Commune" value="<?= htmlspecialchars($e['nom_commune']) ?>" required>
                                    <input type="hidden" id="id_commune_edit_<?= $e['id_evenement'] ?>" name="id_commune_edit" value="<?= htmlspecialchars($e['id_commune']) ?>">
                                <?php else: ?>
                                    <?= htmlspecialchars($e['nom_commune']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $e['id_evenement']): ?>
                                    <select name="id_type_evenement_edit" required>
                                        <option value="">-- Type --</option>
                                        <?php foreach ($types as $t): ?>
                                            <option value="<?= $t['id_type_evenement'] ?>" <?= $t['id_type_evenement'] == $e['id_type_evenement'] ? 'selected' : '' ?>><?= htmlspecialchars($t['lib_type_evenement']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <?= htmlspecialchars($e['lib_type_evenement']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $e['id_evenement']): ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="id_evenement" value="<?= htmlspecialchars($e['id_evenement']) ?>">
                                        <button type="submit" name="edit_evenement" class="btn btn-primary">Enregistrer</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="id_evenement" value="<?= htmlspecialchars($e['id_evenement']) ?>">
                                        <button type="submit" name="edit_mode" value="<?= htmlspecialchars($e['id_evenement']) ?>" class="btn btn-secondary">Modifier</button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cet événement ?');">
                                        <input type="hidden" name="id_evenement" value="<?= htmlspecialchars($e['id_evenement']) ?>">
                                        <button type="submit" name="delete_evenement" class="btn btn-danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
<script src="../js/confirm_delete.js"></script>
