<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';
require_once __DIR__ . '/../classes/Locataire/Personne_Physique/Personne_Physique.php';
require_once __DIR__ . '/../classes/Locataire/Personne_Morale/Personne_Morale.php';

$message = '';
$locataires = [];

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        $locataireObj = new Locataire(null, null, null, null, null, null, null, null, null, $pdo);

        // Ajout d'un locataire
        if (isset($_POST['add_locataire'])) {
            $type = $_POST['type_locataire'] ?? 'physique';
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $tel = trim($_POST['tel'] ?? '');
            $date_naissance = $_POST['date_naissance'] ?? null;
            $mdp = $_POST['mdp'] ?? '';
            $rue = trim($_POST['rue'] ?? '');
            $complement = trim($_POST['complement'] ?? '');
            $siret = trim($_POST['siret'] ?? '');
            $raison_sociale = trim($_POST['raison_sociale'] ?? '');
            $id_commune = intval($_POST['id_commune'] ?? 1);

            if ($type === 'physique') {
                if ($nom && $prenom && $email && $tel && $date_naissance && $mdp && $rue) {
                    $pp = new PersonnePhysique(null, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement);
                    if ($locataireObj->createLocataire($nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, null, null, $id_commune)) {
                        $message = "Locataire (personne physique) ajouté avec succès.";
                    } else {
                        $message = "Erreur lors de l'ajout.";
                    }
                }
            } else {
                if ($nom && $prenom && $email && $tel && $date_naissance && $mdp && $rue && $siret && $raison_sociale) {
                    $pm = new PersonneMorale(null, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, $siret, $raison_sociale);
                    if ($locataireObj->createLocataire($nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, $siret, $raison_sociale, $id_commune)) {
                        $message = "Locataire (personne morale) ajouté avec succès.";
                    } else {
                        $message = "Erreur lors de l'ajout.";
                    }
                }
            }
        }

        // Suppression d'un locataire
        if (isset($_POST['delete_locataire']) && isset($_POST['id_locataire'])) {
            $id = intval($_POST['id_locataire']);
            if ($locataireObj->deleteLocataire($id)) {
                $message = "Locataire supprimé avec succès.";
            } else {
                $message = "Erreur lors de la suppression.";
            }
        }

        // Modification d'un locataire
        if (isset($_POST['edit_locataire']) && isset($_POST['id_locataire'])) {
            $id = intval($_POST['id_locataire']);
            $type = $_POST['type_locataire_edit'] ?? 'physique';
            $nom = trim($_POST['nom_edit'] ?? '');
            $prenom = trim($_POST['prenom_edit'] ?? '');
            $email = trim($_POST['email_edit'] ?? '');
            $tel = trim($_POST['tel_edit'] ?? '');
            $date_naissance = $_POST['date_naissance_edit'] ?? null;
            $mdp = $_POST['mdp_edit'] ?? '';
            $rue = trim($_POST['rue_edit'] ?? '');
            $complement = trim($_POST['complement_edit'] ?? '');
            $siret = trim($_POST['siret_edit'] ?? '');
            $raison_sociale = trim($_POST['raison_sociale_edit'] ?? '');

            if ($type === 'physique') {
                if ($nom && $prenom && $email && $tel && $date_naissance && $mdp && $rue) {
                    if ($locataireObj->updateLocataire($id, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, null, null)) {
                        $message = "Locataire (personne physique) modifié avec succès.";
                    } else {
                        $message = "Erreur lors de la modification.";
                    }
                }
            } else {
                if ($nom && $prenom && $email && $tel && $date_naissance && $mdp && $rue && $siret && $raison_sociale) {
                    if ($locataireObj->updateLocataire($id, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, $siret, $raison_sociale)) {
                        $message = "Locataire (personne morale) modifié avec succès.";
                    } else {
                        $message = "Erreur lors de la modification.";
                    }
                }
            }
        }

        // Récupération des locataires avec nom de commune
    $stmt = $pdo->query("SELECT l.*, l.date_naissance AS date_naissance_locataire, c.nom_commune FROM Locataire l JOIN Commune c ON l.id_commune = c.id_commune");
        $locataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Locataires</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <style>
    body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
    .container { max-width: 1100px; width: calc(100% - 40px); margin: 24px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 24px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
    .locataire-list { margin-top: 20px; overflow-x: auto; }
    .locataire-list table { border-collapse: collapse; width: 100%; min-width: 960px; }
    .locataire-list th, .locataire-list td { border: 1px solid #ccc; padding: 10px 14px; text-align: center; }
        .locataire-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        .morale-fields { display: none; }
        @media (max-width: 720px) {
            .container { padding: 16px; }
            form { gap: 8px; }
            .locataire-list table { min-width: 780px; }
            input, select { font-size: 14px; }
        }
    </style>
    <script>
        function toggleLocataireFields() {
            const type = document.getElementById('type_locataire').value;
            document.querySelectorAll('.morale-fields').forEach(e => e.style.display = (type === 'morale') ? 'block' : 'none');
        }
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('type_locataire').addEventListener('change', toggleLocataireFields);
            toggleLocataireFields();

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

            $("#add_form").on('submit', function(e) {
                if (!$("#id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }
            });

        });
    </script>
</head>
<body>
    <div class="container">
        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Locataires</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" id="add_form">
            <select name="type_locataire" id="type_locataire" required>
                <option value="physique">Personne physique</option>
                <option value="morale">Personne morale</option>
            </select>
            <input type="text" name="nom" placeholder="Nom" required>
            <input type="text" name="prenom" placeholder="Prénom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="tel" placeholder="Téléphone" required>
            <input type="date" name="date_naissance" placeholder="Date de naissance" required>
            <input type="password" name="mdp" placeholder="Mot de passe" required>
            <input type="text" name="rue" placeholder="Rue" required>
            <input type="text" name="complement" placeholder="Complément d'adresse">
            <input type="text" id="commune" placeholder="Commune" required>
            <input type="hidden" id="id_commune" name="id_commune">
            <input type="text" class="morale-fields" name="siret" placeholder="SIRET">
            <input type="text" class="morale-fields" name="raison_sociale" placeholder="Raison sociale">
            <input type="submit" name="add_locataire" value="Ajouter">
        </form>
        <div class="locataire-list">
            <table id="locataires_table">
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Date naissance</th>
                    <th>Rue</th>
                    <th>Complément</th>
                    <th>SIRET</th>
                    <th>Raison sociale</th>
                    <th>Commune</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($locataires as $loc): ?>
                    <tr>
                        <td><?= htmlspecialchars($loc['id_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['nom_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['prenom_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['email_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['telephone_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['date_naissance_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['rue_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['complement_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['siret']) ?></td>
                        <td><?= htmlspecialchars($loc['raison_sociale']) ?></td>
                        <td><?= htmlspecialchars($loc['nom_commune']) ?></td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $loc['id_locataire']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_locataire" value="<?= htmlspecialchars($loc['id_locataire']) ?>">
                                    <select name="type_locataire_edit" required>
                                        <option value="physique" <?= (isset($loc) && $loc['siret'] == '' ? 'selected' : '') ?>>Physique</option>
                                        <option value="morale" <?= (isset($loc) && $loc['siret'] != '' ? 'selected' : '') ?>>Morale</option>
                                    </select>
                                    <input type="text" name="nom_edit" value="<?= htmlspecialchars($loc['nom_locataire']) ?>" required>
                                    <input type="text" name="prenom_edit" value="<?= htmlspecialchars($loc['prenom_locataire']) ?>" required>
                                    <input type="email" name="email_edit" value="<?= htmlspecialchars($loc['email_locataire']) ?>" required>
                                    <input type="text" name="tel_edit" value="<?= htmlspecialchars($loc['telephone_locataire']) ?>" required>
                                    <input type="date" name="date_naissance_edit" value="<?= htmlspecialchars($loc['date_naissance_locataire']) ?>" required>
                                    <input type="password" name="mdp_edit" placeholder="Nouveau mot de passe" required>
                                    <input type="text" name="rue_edit" value="<?= htmlspecialchars($loc['rue_locataire']) ?>" required>
                                    <input type="text" name="complement_edit" value="<?= htmlspecialchars($loc['complement_locataire']) ?>">
                                    <input type="text" class="morale-fields" name="siret_edit" value="<?= htmlspecialchars($loc['siret']) ?>" placeholder="SIRET">
                                    <input type="text" class="morale-fields" name="raison_sociale_edit" value="<?= htmlspecialchars($loc['raison_sociale']) ?>" placeholder="Raison sociale">
                                    <button type="submit" name="edit_locataire">Enregistrer</button>
                                </form>
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($loc['id_locataire']) ?>">
                                    <button type="submit">Modifier</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce locataire ?');">
                                    <input type="hidden" name="id_locataire" value="<?= htmlspecialchars($loc['id_locataire']) ?>">
                                    <button type="submit" name="delete_locataire">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>