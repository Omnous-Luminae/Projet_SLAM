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
            background: linear-gradient(135deg, rgba(161, 0, 184, 0.05), rgba(209, 0, 232, 0.05));
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .form-group label .required {
            color: #e74c3c;
            font-size: 1.2em;
        }
        .form-group input,
        .form-group select {
            padding: 12px 16px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: white;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #a100b8;
            box-shadow: 0 0 0 3px rgba(161, 0, 184, 0.1);
        }
        .form-group input[readonly] {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        .submit-btn {
            grid-column: 1 / -1;
            padding: 14px 32px;
            background: linear-gradient(135deg, #a100b8, #d100e8);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(161, 0, 184, 0.3);
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 0, 184, 0.4);
        }
        .form-title {
            margin: 0 0 20px 0;
            font-size: 1.3em;
            font-weight: 700;
            color: #a100b8;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-text {
            background: #e8f4f8;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #2c3e50;
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
        .reservation-list .actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85em;
            transition: all 0.3s ease;
            font-family: 'Montserrat', sans-serif;
        }
        .reservation-list .actions button[type="button"] {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        .reservation-list .actions button[type="button"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        .reservation-list .actions button[type="submit"] {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        .reservation-list .actions button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
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
            padding: 20px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 35px;
            border: none;
            width: 90%;
            max-width: 700px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
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
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e1e1e1;
        }
        .filter-section h3 {
            margin: 0 0 20px 0;
            font-size: 1.1em;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        .filter-grid input,
        .filter-grid select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95em;
        }
        .filter-grid button {
            padding: 10px 24px;
            background: #a100b8;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .filter-grid button:hover {
            background: #8a0099;
            transform: translateY(-1px);
        }
    </style>
    <script>
        function openEditModal(id, dateDebut, dateFin, idLocataire, idBiens, nomBien) {
            document.getElementById('edit_id_reservation').value = id;
            document.getElementById('edit_date_debut').value = dateDebut;
            document.getElementById('edit_date_fin').value = dateFin;
            if (document.getElementById('edit_id_locataire')) {
                document.getElementById('edit_id_locataire').value = idLocataire;
            }
            document.getElementById('edit_biens_id').value = idBiens;
            document.getElementById('edit_biens_input').value = nomBien || '';
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
        <div class="reservation-form">
            <h3 class="form-title">📅 Nouvelle Réservation</h3>
            <?php if (!$isAdmin && $currentUserId): ?>
            <div class="info-text">
                💡 Vous réservez en tant que <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'utilisateur') ?></strong>
            </div>
            <?php endif; ?>
            <form method="post" id="add_reservation_form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="date_debut">Date de début <span class="required">*</span></label>
                        <input type="date" id="date_debut" name="date_debut" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Date de fin <span class="required">*</span></label>
                        <input type="date" id="date_fin" name="date_fin" required min="<?= date('Y-m-d') ?>">
                    </div>
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
                    <?php if ($isAdmin): ?>
                    <div class="form-group">
                        <label for="locataire_input">Locataire <span class="required">*</span></label>
                        <input type="text" id="locataire_input" name="locataire_name" placeholder="Tapez pour rechercher..." required>
                        <input type="hidden" id="locataire_id" name="id_locataire">
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label for="locataire_input">Locataire</label>
                        <input type="text" id="locataire_input" name="locataire_name" value="<?= $locataireValue ?>" <?= $locataireReadonly ?>>
                        <input type="hidden" id="locataire_id" name="id_locataire" value="<?= $locataireHiddenVal ?>">
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="biens_input">Bien à réserver <span class="required">*</span></label>
                        <input type="text" id="biens_input" name="biens_name" placeholder="Tapez pour rechercher..." required>
                        <input type="hidden" id="biens_id" name="id_biens">
                    </div>
                </div>
                <button type="submit" name="add_reservation" class="submit-btn">✓ Créer la réservation</button>
            </form>
        </div>
        <?php endif; ?>
        <div class="filter-section">
            <h3>🔍 Filtrer les réservations</h3>
            <form method="get">
                <div class="filter-grid">
                    <input type="text" name="filter_bien" placeholder="Nom du bien..." value="<?= htmlspecialchars($filterBien) ?>">
                    <input type="date" name="filter_start_date" placeholder="Date début" value="<?= htmlspecialchars($filterStartDate) ?>">
                    <input type="date" name="filter_end_date" placeholder="Date fin" value="<?= htmlspecialchars($filterEndDate) ?>">
                    <input type="number" name="filter_min_reservations" placeholder="Min réservations" min="0" value="<?= htmlspecialchars($filterMinReservations) ?>">
                    <button type="submit">🔍 Filtrer</button>
                </div>
            </form>
        </div>
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
                            <button type="button" onclick="openEditModal(<?= $r['id_reservation'] ?>, '<?= htmlspecialchars($r['date_debut_reservation']) ?>', '<?= htmlspecialchars($r['date_fin_reservation']) ?>', <?= $r['id_locataire'] ?>, <?= $r['id_biens'] ?>, '<?= htmlspecialchars($r['nom_biens'], ENT_QUOTES) ?>')">✏️ Modifier</button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer cette réservation ?');">
                                <input type="hidden" name="id_reservation" value="<?= htmlspecialchars($r['id_reservation']) ?>">
                                <button type="submit" name="delete_reservation">🗑️ Supprimer</button>
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
                <h3 class="form-title">✏️ Modifier la Réservation</h3>
                <form method="post">
                    <input type="hidden" id="edit_id_reservation" name="id_reservation">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="edit_date_debut">Date de début <span class="required">*</span></label>
                            <input type="date" id="edit_date_debut" name="date_debut_edit" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_date_fin">Date de fin <span class="required">*</span></label>
                            <input type="date" id="edit_date_fin" name="date_fin_edit" required>
                        </div>
                        <?php if ($isAdmin): ?>
                        <div class="form-group">
                            <label for="edit_id_locataire">Locataire <span class="required">*</span></label>
                            <select id="edit_id_locataire" name="id_locataire_edit" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($locataires as $l): ?>
                                    <option value="<?= $l['id_locataire'] ?>"><?= htmlspecialchars($l['nom_locataire'] . ' ' . $l['prenom_locataire']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="edit_biens_input">Bien <span class="required">*</span></label>
                            <input type="text" id="edit_biens_input" name="edit_biens_name" placeholder="Tapez pour rechercher..." required>
                            <input type="hidden" id="edit_biens_id" name="id_biens_edit">
                        </div>
                    </div>
                    <button type="submit" name="edit_reservation" class="submit-btn">✓ Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
<script src="../js/confirm_delete.js"></script>
