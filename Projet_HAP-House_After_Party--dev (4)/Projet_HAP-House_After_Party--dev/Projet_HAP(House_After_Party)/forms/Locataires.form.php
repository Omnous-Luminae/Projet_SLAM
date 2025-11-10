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

            // Validation du numéro de téléphone français
            $tel_pattern = '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/';
            if (!preg_match($tel_pattern, $tel)) {
                $message = "Numéro de téléphone invalide. Utilisez un format français valide (ex: 06 12 34 56 78).";
            }

            // Validation du SIRET pour personne morale
            if ($type === 'morale') {
                if (strlen($siret) !== 14 || !ctype_digit($siret)) {
                    $message = "Le numéro SIRET doit contenir exactement 14 chiffres.";
                }
            }

                if ($type === 'physique') {
                if ($nom && $prenom && $email && $tel && $date_naissance && $mdp && $rue && $id_commune > 0 && empty($message)) {
                    $pp = new PersonnePhysique(null, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement);
                    if ($locataireObj->createLocataire($nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, null, null, $id_commune)) {
                        $message = "Locataire (personne physique) ajouté avec succès.";
                    } else {
                        $message = "Erreur lors de l'ajout.";
                    }
                } elseif (empty($message)) {
                    $message = "Veuillez remplir tous les champs obligatoires, y compris la commune.";
                }
            } else {
                if ($nom && $prenom && $email && $tel && $date_naissance && $mdp && $rue && $siret && $raison_sociale && $id_commune > 0 && empty($message)) {
                    $pm = new PersonneMorale(null, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, $siret, $raison_sociale);
                    if ($locataireObj->createLocataire($nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, $siret, $raison_sociale, $id_commune)) {
                        $message = "Locataire (personne morale) ajouté avec succès.";
                    } else {
                        $message = "Erreur lors de l'ajout.";
                    }
                } elseif (empty($message)) {
                    $message = "Veuillez remplir tous les champs obligatoires, y compris la commune.";
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
            $id_commune = intval($_POST['id_commune_edit'] ?? 0);

            // Validation du numéro de téléphone français
            $tel_pattern = '/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/';
            if (!preg_match($tel_pattern, $tel)) {
                $message = "Numéro de téléphone invalide. Utilisez un format français valide (ex: 06 12 34 56 78).";
            }

            // Validation du SIRET pour personne morale
            if ($type === 'morale') {
                if (strlen($siret) !== 14 || !ctype_digit($siret)) {
                    $message = "Le numéro SIRET doit contenir exactement 14 chiffres.";
                }
            }

            if ($type === 'physique') {
                if ($nom && $prenom && $email && $tel && $date_naissance && $rue && $id_commune > 0 && empty($message)) {
                    if ($locataireObj->updateLocataire($id, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, null, null, $id_commune)) {
                        $message = "Locataire (personne physique) modifié avec succès.";
                    } else {
                        $err = $pdo->errorInfo();
                        $message = "Erreur lors de la modification. SQL: " . htmlspecialchars($err[2] ?? 'unknown');
                    }
                } elseif (!isset($message)) {
                    $message = "Veuillez remplir tous les champs obligatoires, y compris la commune.";
                }
            } else {
                if ($nom && $prenom && $email && $tel && $date_naissance && $rue && $siret && $raison_sociale && $id_commune > 0 && empty($message)) {
                    if ($locataireObj->updateLocataire($id, $nom, $prenom, $email, $tel, $date_naissance, $mdp, $rue, $complement, $siret, $raison_sociale, $id_commune)) {
                        $message = "Locataire (personne morale) modifié avec succès.";
                    } else {
                        $err = $pdo->errorInfo();
                        $message = "Erreur lors de la modification. SQL: " . htmlspecialchars($err[2] ?? 'unknown');
                    }
                } elseif (empty($message)) {
                    $message = "Veuillez remplir tous les champs obligatoires, y compris la commune.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Locataires - House After Party</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="../Css/locataires.css">
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="../js/autocomplete.js"></script>
    <script>
        function toggleLocataireFields() {
            const type = document.getElementById('type_locataire').value;
            document.querySelectorAll('.morale-fields').forEach(e => e.style.display = (type === 'morale') ? 'block' : 'none');
        }
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('type_locataire').addEventListener('change', toggleLocataireFields);
            toggleLocataireFields();

            // Initialize autocomplete for add and edit forms
            initAddCommuneAutocomplete();
            initEditCommuneAutocomplete();

            $("#add_form").on('submit', function(e) {
                if (!$("#id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }

                // Validation du téléphone
                const tel = $("#tel").val().trim();
                const telPattern = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
                if (!telPattern.test(tel)) {
                    alert("Numéro de téléphone invalide. Utilisez un format français valide (ex: 06 12 34 56 78).");
                    e.preventDefault();
                    return false;
                }

                // Validation du SIRET si personne morale
                const type = $("#type_locataire").val();
                if (type === 'morale') {
                    const siret = $("#siret").val().trim();
                    if (siret.length !== 14 || !/^\d+$/.test(siret)) {
                        alert("Le numéro SIRET doit contenir exactement 14 chiffres.");
                        e.preventDefault();
                        return false;
                    }
                }
            });

            $("#editForm").on('submit', function(e) {
                if (!$("#edit_id_commune").val()) {
                    alert("Veuillez sélectionner une commune valide dans la liste d'autocomplétion.");
                    e.preventDefault();
                    return false;
                }

                // Validation du téléphone
                const tel = $("#edit_tel").val().trim();
                const telPattern = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/;
                if (!telPattern.test(tel)) {
                    alert("Numéro de téléphone invalide. Utilisez un format français valide (ex: 06 12 34 56 78).");
                    e.preventDefault();
                    return false;
                }

                // Validation du SIRET si personne morale
                const type = $("#edit_type_locataire").val();
                if (type === 'morale') {
                    const siret = $("#edit_siret").val().trim();
                    if (siret.length !== 14 || !/^\d+$/.test(siret)) {
                        alert("Le numéro SIRET doit contenir exactement 14 chiffres.");
                        e.preventDefault();
                        return false;
                    }
                }
            });

        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>👥 Gestion des Locataires</h2>
            <p>Gérez les informations des personnes physiques et morales inscrites sur la plateforme</p>
        </div>

        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h3>➕ Ajouter un nouveau locataire</h3>
            <form method="post" id="add_form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="type_locataire">Type de locataire</label>
                        <select name="type_locataire" id="type_locataire" required>
                            <option value="physique">Personne physique</option>
                            <option value="morale">Personne morale</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" name="nom" id="nom" placeholder="Nom" required>
                    </div>
                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" name="prenom" id="prenom" placeholder="Prénom" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" placeholder="email@exemple.com" required>
                    </div>
                    <div class="form-group">
                        <label for="tel">Téléphone</label>
                        <input type="text" name="tel" id="tel" placeholder="06 12 34 56 78" required>
                    </div>
                    <div class="form-group">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" name="date_naissance" id="date_naissance" required>
                    </div>
                    <div class="form-group">
                        <label for="mdp">Mot de passe</label>
                        <input type="password" name="mdp" id="mdp" placeholder="Mot de passe sécurisé" required>
                    </div>
                    <div class="form-group">
                        <label for="rue">Rue</label>
                        <input type="text" name="rue" id="rue" placeholder="123 rue de la Paix" required>
                    </div>
                    <div class="form-group">
                        <label for="complement">Complément d'adresse</label>
                        <input type="text" name="complement" id="complement" placeholder="Appartement 4B">
                    </div>
                    <div class="form-group">
                        <label for="commune">Commune</label>
                        <input type="text" id="commune" name="commune_input" placeholder="Commencez à taper..." required>
                        <input type="hidden" id="id_commune" name="id_commune">
                    </div>
                    <div class="form-group morale-fields">
                        <label for="siret">SIRET</label>
                        <input type="text" name="siret" id="siret" placeholder="123 456 789 01234">
                    </div>
                    <div class="form-group morale-fields">
                        <label for="raison_sociale">Raison sociale</label>
                        <input type="text" name="raison_sociale" id="raison_sociale" placeholder="Nom de l'entreprise">
                    </div>
                </div>
                <button type="submit" name="add_locataire" class="btn">Ajouter le locataire</button>
            </form>
        </div>
        <div class="table-section">
            <h3>📋 Liste des locataires</h3>
            <div class="locataire-list">
                <table id="locataires_table">
                    <thead>
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
                    </thead>
                    <tbody>
                <?php foreach ($locataires as $loc): ?>
                    <tr>
                        <td><?= htmlspecialchars($loc['id_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['nom_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['prenom_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['email_locataire']) ?></td>
                        <td><?= htmlspecialchars(preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $loc['telephone_locataire'])) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($loc['date_naissance_locataire']))) ?></td>
                        <td><?= htmlspecialchars($loc['rue_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['complement_locataire']) ?></td>
                        <td><?= htmlspecialchars($loc['siret'] ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{5})/', '$1 $2 $3 $4', $loc['siret']) : '') ?></td>
                        <td><?= htmlspecialchars($loc['raison_sociale']) ?></td>
                        <td><?= htmlspecialchars($loc['nom_commune']) ?></td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn-edit" onclick="openEditModal(<?= $loc['id_locataire'] ?>, '<?= htmlspecialchars($loc['nom_locataire']) ?>', '<?= htmlspecialchars($loc['prenom_locataire']) ?>', '<?= htmlspecialchars($loc['email_locataire']) ?>', '<?= htmlspecialchars($loc['telephone_locataire']) ?>', '<?= htmlspecialchars($loc['date_naissance_locataire']) ?>', '<?= htmlspecialchars($loc['rue_locataire']) ?>', '<?= htmlspecialchars($loc['complement_locataire']) ?>', '<?= htmlspecialchars($loc['siret']) ?>', '<?= htmlspecialchars($loc['raison_sociale']) ?>', '<?= htmlspecialchars($loc['nom_commune']) ?>', '<?= htmlspecialchars($loc['id_commune']) ?>')">✏️ Modifier</button>
                                <button type="button" class="btn-delete" onclick="deleteLocataire(<?= $loc['id_locataire'] ?>, '<?= htmlspecialchars($loc['nom_locataire']) ?>')">🗑️ Supprimer</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Modifier le locataire</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="post" id="editForm" class="edit-form-modal">
                    <input type="hidden" name="id_locataire" id="edit_id_locataire">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_type_locataire">Type de locataire</label>
                            <select name="type_locataire_edit" id="edit_type_locataire" required>
                                <option value="physique">Personne physique</option>
                                <option value="morale">Personne morale</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_nom">Nom</label>
                            <input type="text" name="nom_edit" id="edit_nom" placeholder="Nom" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_prenom">Prénom</label>
                            <input type="text" name="prenom_edit" id="edit_prenom" placeholder="Prénom" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" name="email_edit" id="edit_email" placeholder="email@exemple.com" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_tel">Téléphone</label>
                            <input type="text" name="tel_edit" id="edit_tel" placeholder="06 12 34 56 78" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_date_naissance">Date de naissance</label>
                            <input type="date" name="date_naissance_edit" id="edit_date_naissance" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_mdp">Mot de passe</label>
                            <input type="password" name="mdp_edit" id="edit_mdp" placeholder="Laisser vide pour ne pas changer">
                        </div>
            <div class="form-group">
                <label for="edit_rue">Rue</label>
                <input type="text" name="rue_edit" id="edit_rue" placeholder="123 rue de la Paix" required>
            </div>
            <div class="form-group">
                <label for="edit_complement">Complément d'adresse</label>
                <input type="text" name="complement_edit" id="edit_complement" placeholder="Appartement 4B">
            </div>
            <div class="form-group">
                <label for="edit_commune">Commune</label>
                <input type="text" id="edit_commune" class="form-control" placeholder="Tapez le nom de votre commune" required>
                <input type="hidden" id="edit_id_commune" name="id_commune_edit">
            </div>
                        <div class="form-group morale-fields">
                            <label for="edit_siret">SIRET</label>
                            <input type="text" name="siret_edit" id="edit_siret" placeholder="123 456 789 01234">
                        </div>
                        <div class="form-group morale-fields">
                            <label for="edit_raison_sociale">Raison sociale</label>
                            <input type="text" name="raison_sociale_edit" id="edit_raison_sociale" placeholder="Nom de l'entreprise">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
                        <button type="submit" name="edit_locataire" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(id, nom, prenom, email, tel, date_naissance, rue, complement, siret, raison_sociale, commune, id_commune) {
            document.getElementById('edit_id_locataire').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_prenom').value = prenom;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_tel').value = tel;
            document.getElementById('edit_date_naissance').value = date_naissance;
            document.getElementById('edit_rue').value = rue;
            document.getElementById('edit_complement').value = complement;
            document.getElementById('edit_siret').value = siret;
            document.getElementById('edit_raison_sociale').value = raison_sociale;
            document.getElementById('edit_commune').value = commune;
            document.getElementById('edit_id_commune').value = id_commune;

            // Set type based on siret
            const typeSelect = document.getElementById('edit_type_locataire');
            if (siret && siret.trim() !== '') {
                typeSelect.value = 'morale';
                toggleMoraleFields(true);
            } else {
                typeSelect.value = 'physique';
                toggleMoraleFields(false);
            }

            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function deleteLocataire(id, nom) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer le locataire "${nom}" ? Cette action est irréversible.`)) {
                const form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';

                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_locataire';
                inputId.value = id;

                const inputDelete = document.createElement('input');
                inputDelete.type = 'hidden';
                inputDelete.name = 'delete_locataire';
                inputDelete.value = '1';

                form.appendChild(inputId);
                form.appendChild(inputDelete);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleMoraleFields(show) {
            const moraleFields = document.querySelectorAll('#editModal .morale-fields');
            moraleFields.forEach(field => {
                field.style.display = show ? 'block' : 'none';
            });
        }

        // Event listener for type change in edit modal
        document.getElementById('edit_type_locataire').addEventListener('change', function() {
            toggleMoraleFields(this.value === 'morale');
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
