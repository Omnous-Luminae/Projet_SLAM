<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'animateur';
    $currentUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

    if ($pdo) {
        // Ajout d'une réservation
        if (isset($_POST['add_reservation'])) {
            $date_debut = trim($_POST['date_debut'] ?? '');
            $date_fin = trim($_POST['date_fin'] ?? '');
            // If user is not admin, force id_locataire to current session user
            if ($isAdmin) {
                $id_locataire = intval($_POST['id_locataire'] ?? 0);
            } else {
                $id_locataire = $currentUserId;
            }
            // accept either hidden biens_id or posted value from autocomplete
            $id_biens = intval($_POST['id_biens'] ?? $_POST['biens_id'] ?? 0);
            $id_tarif = intval($_POST['id_tarif'] ?? 0);

            if ($date_debut && $date_fin && $id_locataire && $id_biens && $id_tarif) {
                $stmt = $pdo->prepare('INSERT INTO Reservation (date_debut_reservation, date_fin_reservation, id_locataire, id_biens, id_Tarif) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_biens, $id_tarif]);
                $message = "Réservation ajoutée avec succès.";
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Suppression d'une réservation
        if (isset($_POST['delete_reservation']) && isset($_POST['id_reservation'])) {
            $id = intval($_POST['id_reservation']);
            // only allow deletion if admin or owner
            if ($isAdmin) {
                $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_reservation = ?');
                $stmt->execute([$id]);
                $message = "Réservation supprimée avec succès.";
            } else {
                $stmtCheck = $pdo->prepare('SELECT id_locataire FROM Reservation WHERE id_reservation = ?');
                $stmtCheck->execute([$id]);
                $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if ($row && intval($row['id_locataire']) === $currentUserId) {
                    $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_reservation = ?');
                    $stmt->execute([$id]);
                    $message = "Réservation supprimée avec succès.";
                } else {
                    $message = "Action non autorisée.";
                }
            }
        }

        // Modification d'une réservation
        if (isset($_POST['edit_reservation']) && isset($_POST['id_reservation'])) {
            $id = intval($_POST['id_reservation']);
            $date_debut = trim($_POST['date_debut_edit'] ?? '');
            $date_fin = trim($_POST['date_fin_edit'] ?? '');
            $id_locataire = intval($_POST['id_locataire_edit'] ?? 0);
            $id_biens = intval($_POST['id_biens_edit'] ?? 0);
            $id_tarif = intval($_POST['id_tarif_edit'] ?? 0);

            // only admin or owner can edit
            $allowed = false;
            if ($isAdmin) { $allowed = true; }
            else {
                $stmtCheck = $pdo->prepare('SELECT id_locataire FROM Reservation WHERE id_reservation = ?');
                $stmtCheck->execute([$id]);
                $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if ($row && intval($row['id_locataire']) === $currentUserId) { $allowed = true; }
            }

            if ($allowed) {
                if ($date_debut && $date_fin && $id_locataire && $id_biens && $id_tarif) {
                    $stmt = $pdo->prepare('UPDATE Reservation SET date_debut_reservation = ?, date_fin_reservation = ?, id_locataire = ?, id_biens = ?, id_Tarif = ? WHERE id_reservation = ?');
                    $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_biens, $id_tarif, $id]);
                    $message = "Réservation modifiée avec succès.";
                } else {
                    $message = "Tous les champs sont requis.";
                }
            } else {
                $message = "Action non autorisée.";
            }
        }

        // Récupération des réservations
        $reservations = [];
        if ($isAdmin) {
            $stmt = $pdo->query('SELECT r.*, l.nom_locataire, l.prenom_locataire, b.nom_biens FROM Reservation r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire LEFT JOIN Biens b ON r.id_biens = b.id_biens ORDER BY r.id_reservation DESC');
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            if ($currentUserId) {
                $stmt = $pdo->prepare('SELECT r.*, l.nom_locataire, l.prenom_locataire, b.nom_biens FROM Reservation r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire LEFT JOIN Biens b ON r.id_biens = b.id_biens WHERE r.id_locataire = ? ORDER BY r.id_reservation DESC');
                $stmt->execute([$currentUserId]);
                $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        // Récupération des locataires, biens, tarifs pour les selects (admins)
        $locataires = [];
        $biens = [];
        if ($isAdmin) {
            $locataires = $pdo->query('SELECT id_locataire, nom_locataire, prenom_locataire FROM Locataire')->fetchAll(PDO::FETCH_ASSOC);
            $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // for non-admins, only fetch biens titles for convenience
            $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
        }
        $tarifs = $pdo->query('SELECT id_Tarif, tarif FROM Tarif')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Réservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="../js/autocomplete.js"></script>
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .reservation-list { margin-top: 20px; }
        .reservation-list table { border-collapse: collapse; width: 100%; }
        .reservation-list th, .reservation-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .reservation-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 10px; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
    </style>
    <script>
        function openEditModal(id, dateDebut, dateFin, idLocataire, idBiens, idTarif) {
            document.getElementById('edit_id_reservation').value = id;
            document.getElementById('edit_date_debut').value = dateDebut;
            document.getElementById('edit_date_fin').value = dateFin;
            document.getElementById('edit_id_locataire').value = idLocataire;
            document.getElementById('edit_id_biens').value = idBiens;
            document.getElementById('edit_id_tarif').value = idTarif;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeModal();
            }
        }

        $(document).ready(function() {
            initReservationBiensAutocomplete();
            initEditBiensAutocomplete();
            // Autocomplétion locataire
            $("#locataire_input").autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: "../api/search_locataires.php",
                        dataType: "json",
                        data: { q: request.term },
                        success: function(data) { response(data); }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $("#locataire_input").val(ui.item.label);
                    $("#locataire_id").val(ui.item.id);
                    return false;
                }
            });
            $("#locataire_input").on('input', function() { $("#locataire_id").val(''); });
        });
    </script>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Réservations</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" class="reservation-form">
            <input type="date" name="date_debut" placeholder="Date début" required>
            <input type="date" name="date_fin" placeholder="Date fin" required>
            <?php
                // Single locataire input (keeps IDs stable for JS). If user is logged and not admin, prefill and make readonly.
                $locataireValue = '';
                $locataireReadonly = '';
                $locataireHiddenVal = '';
                if (!$isAdmin && $currentUserId) {
                    $locataireValue = htmlspecialchars($_SESSION['user_name'] ?? '');
                    $locataireReadonly = 'readonly';
                    $locataireHiddenVal = $currentUserId;
                }
            ?>
            <input type="text" id="locataire_input" name="locataire_name" placeholder="Rechercher un locataire..." <?= $locataireReadonly ?> value="<?= $locataireValue ?>">
            <input type="hidden" id="locataire_id" name="id_locataire" value="<?= $locataireHiddenVal ?>">

            <input type="text" id="biens_input" name="biens_name" placeholder="Rechercher un bien..." required>
            <input type="hidden" id="biens_id" name="id_biens">
            <select name="id_tarif" required>
                <option value="">-- Tarif --</option>
                <?php foreach ($tarifs as $t): ?>
                    <option value="<?= $t['id_Tarif'] ?>"><?= htmlspecialchars($t['tarif']) ?> €</option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="add_reservation" value="Ajouter">
        </form>
        <div class="reservation-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Locataire</th>
                    <th>Bien</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['id_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['date_debut_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['date_fin_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['nom_locataire'] . ' ' . $r['prenom_locataire']) ?></td>
                        <td><?= htmlspecialchars($r['nom_biens']) ?></td>
                        <td>
                            <button type="button" onclick="openEditModal(<?= $r['id_reservation'] ?>, '<?= htmlspecialchars($r['date_debut_reservation']) ?>', '<?= htmlspecialchars($r['date_fin_reservation']) ?>', <?= $r['id_locataire'] ?>, <?= $r['id_biens'] ?>, <?= $r['id_Tarif'] ?>)">Modifier</button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette réservation ?');">
                                <input type="hidden" name="id_reservation" value="<?= htmlspecialchars($r['id_reservation']) ?>">
                                <button type="submit" name="delete_reservation">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Modal for editing reservation -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <button class="close" type="button" aria-label="Fermer" onclick="closeModal()">&times;</button>
                <h3>Modifier la Réservation</h3>
                <form method="post">
                    <input type="hidden" id="edit_id_reservation" name="id_reservation">
                    <input type="date" id="edit_date_debut" name="date_debut_edit" required><br><br>
                    <input type="date" id="edit_date_fin" name="date_fin_edit" required><br><br>
                    <select id="edit_id_locataire" name="id_locataire_edit" required>
                        <option value="">-- Locataire --</option>
                        <?php foreach ($locataires as $l): ?>
                            <option value="<?= $l['id_locataire'] ?>"><?= htmlspecialchars($l['nom_locataire'] . ' ' . $l['prenom_locataire']) ?></option>
                        <?php endforeach; ?>
                    </select><br><br>
                    <input type="text" id="edit_biens_input" name="edit_biens_name" placeholder="Rechercher un bien..." required><br><br>
                    <input type="hidden" id="edit_biens_id" name="id_biens_edit">
                    <select id="edit_id_tarif" name="id_tarif_edit" required>
                        <option value="">-- Tarif --</option>
                        <?php foreach ($tarifs as $t): ?>
                            <option value="<?= $t['id_Tarif'] ?>"><?= htmlspecialchars($t['tarif']) ?> €</option>
                        <?php endforeach; ?>
                    </select><br><br>
                    <input type="submit" name="edit_reservation" value="Modifier">
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<script src="../js/confirm_delete.js"></script>
