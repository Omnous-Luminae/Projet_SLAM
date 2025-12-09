<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';

// Check if user is logged in or admin
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'animateur';
$currentUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$canReserve = $isAdmin || $currentUserId;
if (!$canReserve) {
    $_SESSION['redirect_message'] = "Vous devez être connecté pour effectuer une réservation.";
    header('Location: ../auth/connexion.php?redirect_from=reservation');
    exit;
}

$filterBien = trim($_GET['filter_bien'] ?? '');
$filterStartDate = trim($_GET['filter_start_date'] ?? '');
$filterEndDate = trim($_GET['filter_end_date'] ?? '');
$filterMinReservations = intval($_GET['filter_min_reservations'] ?? 0);

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

            if ($date_debut && $date_fin && $id_locataire && $id_biens) {
                // Calculate total cost using the API
                $costUrl = "http://localhost/Projet_HAP-House_After_Party--dev/api/calculate_reservation_cost.php?id_bien=$id_biens&date_debut=$date_debut&date_fin=$date_fin";
                $costData = json_decode(file_get_contents($costUrl), true);
                $total_cost = $costData['total'] ?? 0;

                $stmt = $pdo->prepare('INSERT INTO Reservation (date_debut_reservation, date_fin_reservation, id_locataire, id_biens, total_cost) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_biens, $total_cost]);
                $message = "Réservation ajoutée avec succès. Coût total: " . number_format($total_cost, 2) . " €";
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
                if ($date_debut && $date_fin && $id_locataire && $id_biens) {
                    // Recalculate total cost
                    $costUrl = "http://localhost/Projet_HAP-House_After_Party--dev/api/calculate_reservation_cost.php?id_bien=$id_biens&date_debut=$date_debut&date_fin=$date_fin";
                    $costData = json_decode(file_get_contents($costUrl), true);
                    $total_cost = $costData['total'] ?? 0;

                    $stmt = $pdo->prepare('UPDATE Reservation SET date_debut_reservation = ?, date_fin_reservation = ?, id_locataire = ?, id_biens = ?, total_cost = ? WHERE id_reservation = ?');
                    $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_biens, $total_cost, $id]);
                    $message = "Réservation modifiée avec succès. Nouveau coût total: " . number_format($total_cost, 2) . " €";
                } else {
                    $message = "Tous les champs sont requis.";
                }
            } else {
                $message = "Action non autorisée.";
            }
        }

        // Récupération des réservations
        $reservations = [];
        $where = [];
        $params = [];
        if ($filterBien) {
            $where[] = 'b.nom_biens LIKE ?';
            $params[] = '%' . $filterBien . '%';
        }
        if ($filterMinReservations > 0) {
            $dateCondition = '';
            $dateParams = [];
            if ($filterStartDate && $filterEndDate) {
                $dateCondition = 'AND r2.date_debut_reservation >= ? AND r2.date_fin_reservation <= ?';
                $dateParams = [$filterStartDate, $filterEndDate];
            } elseif ($filterStartDate) {
                $dateCondition = 'AND r2.date_debut_reservation >= ?';
                $dateParams = [$filterStartDate];
            } elseif ($filterEndDate) {
                $dateCondition = 'AND r2.date_fin_reservation <= ?';
                $dateParams = [$filterEndDate];
            }
            $where[] = "b.id_biens IN (SELECT r2.id_biens FROM Reservation r2 WHERE 1=1 $dateCondition GROUP BY r2.id_biens HAVING COUNT(*) >= ?)";
            $params = array_merge($params, $dateParams, [$filterMinReservations]);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        if ($isAdmin) {
            $stmt = $pdo->prepare("SELECT r.*, l.nom_locataire, l.prenom_locataire, b.nom_biens FROM Reservation r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire LEFT JOIN Biens b ON r.id_biens = b.id_biens $whereSql ORDER BY r.id_reservation DESC");
            $stmt->execute($params);
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else if ($currentUserId) {
            $where[] = 'r.id_locataire = ?';
            $params[] = $currentUserId;
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $stmt = $pdo->prepare("SELECT r.*, l.nom_locataire, l.prenom_locataire, b.nom_biens FROM Reservation r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire LEFT JOIN Biens b ON r.id_biens = b.id_biens $whereSql ORDER BY r.id_reservation DESC");
            $stmt->execute($params);
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="../Css/forms.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script src="../js/autocomplete.js"></script>
    <style>
        .reservation-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            align-items: end;
        }
        .reservation-form input,
        .reservation-form select {
            min-width: 200px;
        }
        .reservation-list {
            margin-top: 40px;
        }
        .reservation-list table {
            border-collapse: collapse;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .reservation-list th,
        .reservation-list td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        .reservation-list th {
            background: linear-gradient(135deg, #a100b8, #d100e8);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        .reservation-list tr:nth-child(even) {
            background: #f8f9fa;
        }
        .reservation-list tr:hover {
            background: rgba(161, 0, 184, 0.05);
            transition: background 0.3s ease;
        }
        .reservation-list .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 30px;
            border: none;
            width: 90%;
            max-width: 600px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .close:hover,
        .close:focus {
            color: #a100b8;
        }
        .edit-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 10px 0;
            border: 1px solid #e1e1e1;
        }
        .edit-form .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .edit-form .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        #calendar {
            margin-top: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            align-items: end;
            justify-content: center;
        }
        .filter-form input,
        .filter-form select,
        .filter-form button {
            min-width: 150px;
        }
    </style>
    <script>
        function openEditModal(id, dateDebut, dateFin, idLocataire, idBiens) {
            document.getElementById('edit_id_reservation').value = id;
            document.getElementById('edit_date_debut').value = dateDebut;
            document.getElementById('edit_date_fin').value = dateFin;
            document.getElementById('edit_id_locataire').value = idLocataire;
            document.getElementById('edit_id_biens').value = idBiens;
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
        <?php if (!$canReserve): ?>
            <div style="color: red; text-align: center; margin-bottom: 18px;">Vous devez être connecté pour effectuer une réservation.</div>
        <?php endif; ?>
        <?php if ($canReserve): ?>
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
            <input type="submit" name="add_reservation" value="Ajouter">
        </form>
        <?php endif; ?>
        <form method="get" style="margin-bottom:18px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <input type="text" name="filter_bien" placeholder="Filtrer par nom de bien..." value="<?= htmlspecialchars($filterBien) ?>" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;">
            <input type="date" name="filter_start_date" placeholder="Date début" value="<?= htmlspecialchars($filterStartDate) ?>" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;">
            <input type="date" name="filter_end_date" placeholder="Date fin" value="<?= htmlspecialchars($filterEndDate) ?>" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;">
            <input type="number" name="filter_min_reservations" placeholder="Min réservations" min="0" value="<?= htmlspecialchars($filterMinReservations) ?>" style="padding:8px 12px;border-radius:6px;border:1px solid #ccc;width:120px;">
            <button type="submit" style="padding:8px 18px;border-radius:6px;background:#a100b8;color:#fff;border:none;">Filtrer</button>
        </form>
        <div class="reservation-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Locataire</th>
                    <th>Bien</th>
                    <th>Coût Total</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['id_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['date_debut_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['date_fin_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['nom_locataire'] . ' ' . $r['prenom_locataire']) ?></td>
                        <td><?= htmlspecialchars($r['nom_biens']) ?></td>
                        <td><?= htmlspecialchars(number_format($r['total_cost'], 2)) ?> €</td>
                        <td>
                            <button type="button" onclick="openEditModal(<?= $r['id_reservation'] ?>, '<?= htmlspecialchars($r['date_debut_reservation']) ?>', '<?= htmlspecialchars($r['date_fin_reservation']) ?>', <?= $r['id_locataire'] ?>, <?= $r['id_biens'] ?>)">Modifier</button>
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
                    <input type="submit" name="edit_reservation" value="Modifier">
                </form>
            </div>
        </div>
    </div>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
<script src="../js/confirm_delete.js"></script>
