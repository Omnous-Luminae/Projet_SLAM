<?php

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../classes/ReservationArchive.php";

// Démarrer la session pour récupérer l'utilisateur connecté
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$archive_manager = new ReservationArchive($pdo);
// ID utilisateur courant pour l'audit (admin/animateur/locataire)
$current_user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

// Traiter les actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'archive_single':
            $id_reservation = intval($_POST['id_reservation'] ?? 0);
            if ($id_reservation > 0) {
                if ($archive_manager->archiveReservation($id_reservation, $current_user_id)) {
                    $message = "Réservation $id_reservation archivée avec succès";
                    $message_type = 'success';
                } else {
                    $message = "Erreur lors de l'archivage de la réservation";
                    $message_type = 'error';
                }
            }
            break;
            
        case 'archive_all':
            $jours = intval($_POST['jours'] ?? 1);
            $count = $archive_manager->archiveAllPastReservations($jours, $current_user_id);
            $message = "$count réservation(s) archivée(s)";
            $message_type = 'success';
            break;
            
        case 'restore':
            $id_archive = intval($_POST['id_archive'] ?? 0);
            if ($id_archive > 0) {
                $donnees = $archive_manager->restoreArchive($id_archive, $current_user_id);
                if ($donnees) {
                    $_SESSION['archive_restauree'] = $donnees;
                    $message = "Archive restaurée avec succès";
                    $message_type = 'success';
                } else {
                    $message = "Erreur lors de la restauration de l'archive";
                    $message_type = 'error';
                }
            }
            break;
            
        case 'delete':
            $id_archive = intval($_POST['id_archive'] ?? 0);
            if ($id_archive > 0) {
                if ($archive_manager->deleteArchive($id_archive, $current_user_id)) {
                    $message = "Archive supprimée";
                    $message_type = 'success';
                } else {
                    $message = "Erreur lors de la suppression";
                    $message_type = 'error';
                }
            }
            break;
    }
}

// Récupérer les réservations passées non archivées
$stmt = $pdo->query("
    SELECT r.*, l.nom_locataire, l.prenom_locataire, b.nom_biens, c.nom_commune
    FROM Reservation r
    JOIN Locataire l ON r.id_locataire = l.id_locataire
    JOIN Biens b ON r.id_biens = b.id_biens
    JOIN Commune c ON b.id_commune = c.id_commune
    WHERE r.date_fin_reservation < CURDATE()
    AND r.id_reservation NOT IN (
        SELECT id_reservation_original FROM Reservation_Archive 
        WHERE statut_archivage != 'supprimé'
    )
    ORDER BY r.date_fin_reservation DESC
");
$reservations_passees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les archives existantes
$archives = $archive_manager->getArchives();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Archives de Réservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            color: #fff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .archive-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .archive-container > h1 {
            text-align: center;
            color: #ff6b6b;
            margin-bottom: 30px;
            text-shadow: 0 0 10px rgba(255, 107, 107, 0.5);
        }
        
        .back-link {
            display: inline-block;
            color: #4ecdc4;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card h2 {
            color: #ff6b6b;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 107, 107, 0.5);
        }
        
        .card h3 {
            color: #4ecdc4;
            margin-top: 20px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #90EE90;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        button, .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #333;
        }
        
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }
        
        table th, table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        table th {
            background: rgba(102, 126, 234, 0.3);
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        table td {
            color: #e0e0e0;
        }
        
        table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }
        
        table td .btn {
            margin: 2px;
            padding: 8px 12px;
            font-size: 12px;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-success {
            background: rgba(40, 167, 69, 0.3);
            color: #90EE90;
        }
        
        .badge-warning {
            background: rgba(255, 193, 7, 0.3);
            color: #ffc107;
        }
        
        .badge-danger {
            background: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 36px;
            color: #fff;
        }
        
        .stat-box p {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 30px;
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #ff6b6b;
            border-bottom: 2px solid rgba(255, 107, 107, 0.5);
            padding-bottom: 10px;
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            color: #4ecdc4;
            border: 1px solid rgba(78, 205, 196, 0.2);
        }
        
        .empty-message {
            color: #888;
            text-align: center;
            padding: 30px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="archive-container">
        <a href="../../apropos.php" class="back-link">← Retour au Dashboard</a>
        <h1>🔐 Gestion des Archives de Réservations</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-box">
                <h3><?php echo count($reservations_passees); ?></h3>
                <p>Réservations à archiver</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($archives); ?></h3>
                <p>Archives existantes</p>
            </div>
        </div>
        
        <!-- Section Archivage -->
        <div class="card">
            <h2>📦 Archivage des Réservations Passées</h2>
            
            <div class="action-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="archive_all">
                    <input type="hidden" name="jours" value="1">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Archiver toutes les réservations passées depuis 1 jour ?')">
                        Archiver Tous les Anciens
                    </button>
                </form>
            </div>
            
            <?php if (!empty($reservations_passees)): ?>
                <h3>Réservations Passées à Archiver</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Locataire</th>
                                <th>Bien</th>
                                <th>Commune</th>
                                <th>Fin de réservation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations_passees as $res): ?>
                                <tr>
                                    <td><?php echo $res['id_reservation']; ?></td>
                                    <td><?php echo htmlspecialchars($res['prenom_locataire'] . ' ' . $res['nom_locataire']); ?></td>
                                    <td><?php echo htmlspecialchars($res['nom_biens']); ?></td>
                                    <td><?php echo htmlspecialchars($res['nom_commune']); ?></td>
                                    <td><?php echo $res['date_fin_reservation']; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="archive_single">
                                            <input type="hidden" name="id_reservation" value="<?php echo $res['id_reservation']; ?>">
                                            <button type="submit" class="btn btn-primary" onclick="return confirm('Archiver cette réservation ?')">
                                                Archiver
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucune réservation à archiver pour le moment.</p>
            <?php endif; ?>
        </div>
        
        <!-- Section Archives -->
        <div class="card">
            <h2>📁 Archives Existantes</h2>
            
            <?php if (!empty($archives)): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Archive</th>
                                <th>ID Réservation</th>
                                <th>Dates</th>
                                <th>Statut</th>
                                <th>Archivée le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archives as $archive): ?>
                                <tr>
                                    <td><?php echo $archive['id_archive']; ?></td>
                                    <td><?php echo $archive['id_reservation_original']; ?></td>
                                    <td>
                                        Du <?php echo date('d/m/Y', strtotime($archive['date_debut_reservation'])); ?>
                                        au <?php echo date('d/m/Y', strtotime($archive['date_fin_reservation'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $archive['statut_archivage'] === 'supprimé' ? 'danger' : 'success'; ?>">
                                            <?php echo ucfirst($archive['statut_archivage']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($archive['date_archivage'])); ?></td>
                                    <td>
                                        <button class="btn btn-primary" onclick="viewArchive(<?php echo $archive['id_archive']; ?>)">
                                            Consulter
                                        </button>
                                        <?php if ($archive['statut_archivage'] === 'archivé'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="id_archive" value="<?php echo $archive['id_archive']; ?>">
                                                <button type="submit" class="btn btn-warning" onclick="return confirm('Restaurer cette archive ?')">
                                                    Restaurer
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id_archive" value="<?php echo $archive['id_archive']; ?>">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer cette archive (définitif) ?')">
                                                Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-message">Aucune archive pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal pour afficher les données archivées -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Détails de l'Archive</div>
            <div class="modal-body" id="archiveContent">
                <p>Chargement...</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeModal()">Fermer</button>
            </div>
        </div>
    </div>
    
    <script>
        function viewArchive(archiveId) {
            // Utilise l'endpoint API pour consulter les détails d'archive
            fetch('../api/get_archive_details.php?id=' + archiveId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const content = document.getElementById('archiveContent');
                        content.innerHTML = '<pre>' + JSON.stringify(data.data, null, 2) + '</pre>';
                        document.getElementById('archiveModal').classList.add('active');
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erreur de chargement: ' + error);
                });
        }
        
        function closeModal() {
            document.getElementById('archiveModal').classList.remove('active');
        }
    </script>
</body>
</html>
