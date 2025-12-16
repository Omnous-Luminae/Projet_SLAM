<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['animateur_id'])) {
    header('Location: ../auth/connexion_admin.php');
    exit;
}

$adminName = $_SESSION['animateur_prenom'] ?? 'Admin';

// Statistiques
try {
    // Réservations ce mois
    $stmt = $pdo->query("SELECT COUNT(*) FROM Reservation WHERE MONTH(date_debut) = MONTH(CURRENT_DATE()) AND YEAR(date_debut) = YEAR(CURRENT_DATE())");
    $reservationsThisMonth = $stmt->fetchColumn();

    // Réservations totales
    $stmt = $pdo->query("SELECT COUNT(*) FROM Reservation");
    $totalReservations = $stmt->fetchColumn();

    // Revenus ce mois
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_cost), 0) FROM Reservation WHERE MONTH(date_debut) = MONTH(CURRENT_DATE()) AND YEAR(date_debut) = YEAR(CURRENT_DATE())");
    $revenueThisMonth = $stmt->fetchColumn();

    // Revenus totaux
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_cost), 0) FROM Reservation");
    $totalRevenue = $stmt->fetchColumn();

    // Nombre de biens
    $stmt = $pdo->query("SELECT COUNT(*) FROM Biens");
    $totalBiens = $stmt->fetchColumn();

    // Nombre de locataires
    $stmt = $pdo->query("SELECT COUNT(*) FROM Locataire");
    $totalLocataires = $stmt->fetchColumn();

    // Avis en attente de validation
    $stmt = $pdo->query("SELECT COUNT(*) FROM Avis WHERE valider = 0");
    $pendingReviews = $stmt->fetchColumn();

    // Biens en attente de validation
    $stmt = $pdo->query("SELECT COUNT(*) FROM Biens WHERE valider = 0");
    $pendingBiens = $stmt->fetchColumn();

    // Réservations récentes (5 dernières)
    $stmt = $pdo->query("
        SELECT r.id_reservation, r.date_debut, r.date_fin, r.total_cost,
               b.nom_bien, l.nom_locataire, l.prenom_locataire
        FROM Reservation r
        JOIN Biens b ON r.id_bien = b.id_bien
        JOIN Locataire l ON r.id_locataire = l.id_locataire
        ORDER BY r.id_reservation DESC
        LIMIT 5
    ");
    $recentReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Données pour le graphique (réservations par mois sur 6 mois)
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(date_debut, '%Y-%m') as month, COUNT(*) as count
        FROM Reservation
        WHERE date_debut >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_debut, '%Y-%m')
        ORDER BY month
    ");
    $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Revenus par mois
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(date_debut, '%Y-%m') as month, COALESCE(SUM(total_cost), 0) as revenue
        FROM Reservation
        WHERE date_debut >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_debut, '%Y-%m')
        ORDER BY month
    ");
    $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 des biens les plus réservés
    $stmt = $pdo->query("
        SELECT b.nom_bien, COUNT(r.id_reservation) as nb_reservations
        FROM Biens b
        LEFT JOIN Reservation r ON b.id_bien = r.id_bien
        GROUP BY b.id_bien
        ORDER BY nb_reservations DESC
        LIMIT 5
    ");
    $topBiens = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Erreur de chargement des données.";
}

// Formater les données pour Chart.js
$chartLabels = [];
$chartValues = [];
$revenueValues = [];

$months = ['01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Juin', 
           '07' => 'Juil', '08' => 'Août', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'];

foreach ($chartData as $data) {
    $monthNum = substr($data['month'], 5, 2);
    $chartLabels[] = $months[$monthNum] ?? $monthNum;
    $chartValues[] = (int)$data['count'];
}

foreach ($revenueData as $data) {
    $revenueValues[] = (float)$data['revenue'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-primary: #f8fafc;
            --bg-card: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --accent: #667eea;
            --accent-light: #818cf8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            color: white;
            position: fixed;
            width: 260px;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 25px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        
        .sidebar-logo .icon {
            width: 45px;
            height: 45px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
        }
        
        .sidebar-logo h1 {
            font-size: 1.1em;
            font-weight: 700;
        }
        
        .sidebar-logo span {
            font-size: 0.8em;
            opacity: 0.8;
        }
        
        .nav-section {
            margin-bottom: 25px;
        }
        
        .nav-section-title {
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
            margin-bottom: 12px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 10px;
            color: white;
            text-decoration: none;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.15);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.25);
        }
        
        .nav-link .badge {
            margin-left: auto;
            background: #ef4444;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75em;
            font-weight: 600;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h2 {
            font-size: 1.8em;
            font-weight: 700;
        }
        
        .header p {
            color: var(--text-secondary);
            margin-top: 5px;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            margin-bottom: 15px;
        }
        
        .stat-card .icon.blue { background: rgba(59, 130, 246, 0.1); }
        .stat-card .icon.green { background: rgba(16, 185, 129, 0.1); }
        .stat-card .icon.purple { background: rgba(139, 92, 246, 0.1); }
        .stat-card .icon.orange { background: rgba(245, 158, 11, 0.1); }
        
        .stat-card .value {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            color: var(--text-secondary);
            font-size: 0.9em;
        }
        
        .stat-card .trend {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85em;
            margin-top: 10px;
            padding: 4px 10px;
            border-radius: 20px;
        }
        
        .stat-card .trend.up {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .stat-card .trend.down {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1200px) {
            .charts-grid { grid-template-columns: 1fr; }
        }
        
        .chart-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        
        .chart-card h3 {
            margin-bottom: 20px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Alerts */
        .alerts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .alert-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .alert-card.warning {
            border-left: 4px solid #f59e0b;
        }
        
        .alert-card.danger {
            border-left: 4px solid #ef4444;
        }
        
        .alert-card .icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3em;
            flex-shrink: 0;
        }
        
        .alert-card.warning .icon { background: rgba(245, 158, 11, 0.1); }
        .alert-card.danger .icon { background: rgba(239, 68, 68, 0.1); }
        
        .alert-card .content h4 {
            font-size: 0.95em;
            margin-bottom: 4px;
        }
        
        .alert-card .content p {
            color: var(--text-secondary);
            font-size: 0.85em;
        }
        
        .alert-card .action {
            margin-left: auto;
        }
        
        .alert-card .action a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85em;
        }
        
        /* Table */
        .table-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }
        
        .table-card h3 {
            margin-bottom: 20px;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.85em;
            text-transform: uppercase;
        }
        
        td {
            font-size: 0.9em;
        }
        
        tr:hover {
            background: var(--bg-primary);
        }
        
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .status.confirmed {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status.pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        /* Top Biens */
        .top-list {
            list-style: none;
        }
        
        .top-list li {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .top-list li:last-child {
            border-bottom: none;
        }
        
        .top-list .rank {
            width: 30px;
            height: 30px;
            background: var(--bg-primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9em;
        }
        
        .top-list .rank.gold { background: #fef3c7; color: #d97706; }
        .top-list .rank.silver { background: #f1f5f9; color: #64748b; }
        .top-list .rank.bronze { background: #fed7aa; color: #c2410c; }
        
        .top-list .info {
            flex: 1;
        }
        
        .top-list .info .name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .top-list .info .count {
            color: var(--text-secondary);
            font-size: 0.85em;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .dashboard { grid-template-columns: 1fr; }
            .sidebar { 
                position: relative;
                width: 100%;
                height: auto;
            }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <div class="icon">🏠</div>
                <div>
                    <h1>HAP Admin</h1>
                    <span>House After Party</span>
                </div>
            </div>
            
            <nav>
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="dashboard.php" class="nav-link active">
                        <span>📊</span> Dashboard
                    </a>
                    <a href="Annonce.form.php" class="nav-link">
                        <span>🏡</span> Annonces
                    </a>
                    <a href="Reservation.form.php" class="nav-link">
                        <span>📅</span> Réservations
                    </a>
                    <a href="Bien.form.php" class="nav-link">
                        <span>🏢</span> Biens
                        <?php if ($pendingBiens > 0): ?>
                            <span class="badge"><?= $pendingBiens ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Gestion</div>
                    <a href="Locataires.form.php" class="nav-link">
                        <span>👥</span> Locataires
                    </a>
                    <a href="blog.php" class="nav-link">
                        <span>💬</span> Avis
                        <?php if ($pendingReviews > 0): ?>
                            <span class="badge"><?= $pendingReviews ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="manage_tarifs.php" class="nav-link">
                        <span>💰</span> Tarifs
                    </a>
                    <a href="Saison.form.php" class="nav-link">
                        <span>🌴</span> Saisons
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Paramètres</div>
                    <a href="PtsInteret.form.php" class="nav-link">
                        <span>📍</span> Points d'intérêt
                    </a>
                    <a href="Prestation.form.php" class="nav-link">
                        <span>🛎️</span> Prestations
                    </a>
                    <a href="Evenement.form.php" class="nav-link">
                        <span>🎉</span> Événements
                    </a>
                    <a href="../auth/logout.php" class="nav-link">
                        <span>🚪</span> Déconnexion
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div>
                    <h2>Bonjour, <?= htmlspecialchars($adminName) ?> 👋</h2>
                    <p>Voici un aperçu de votre activité</p>
                </div>
                <div class="header-actions">
                    <a href="Reservation.form.php?action=add" class="btn btn-primary">
                        ➕ Nouvelle réservation
                    </a>
                </div>
            </div>
            
            <!-- Alertes -->
            <?php if ($pendingReviews > 0 || $pendingBiens > 0): ?>
            <div class="alerts-grid">
                <?php if ($pendingReviews > 0): ?>
                <div class="alert-card warning">
                    <div class="icon">⚠️</div>
                    <div class="content">
                        <h4><?= $pendingReviews ?> avis en attente</h4>
                        <p>Des avis nécessitent votre validation</p>
                    </div>
                    <div class="action">
                        <a href="validate_reviews.php">Voir →</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($pendingBiens > 0): ?>
                <div class="alert-card danger">
                    <div class="icon">🏠</div>
                    <div class="content">
                        <h4><?= $pendingBiens ?> bien(s) à valider</h4>
                        <p>Des biens attendent votre approbation</p>
                    </div>
                    <div class="action">
                        <a href="validate_biens.php">Voir →</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon blue">📅</div>
                    <div class="value"><?= number_format($reservationsThisMonth) ?></div>
                    <div class="label">Réservations ce mois</div>
                    <div class="trend up">📈 +12% vs mois dernier</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon green">💰</div>
                    <div class="value"><?= number_format($revenueThisMonth, 0, ',', ' ') ?> €</div>
                    <div class="label">Revenus ce mois</div>
                    <div class="trend up">📈 +8% vs mois dernier</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon purple">🏠</div>
                    <div class="value"><?= number_format($totalBiens) ?></div>
                    <div class="label">Biens actifs</div>
                </div>
                
                <div class="stat-card">
                    <div class="icon orange">👥</div>
                    <div class="value"><?= number_format($totalLocataires) ?></div>
                    <div class="label">Locataires inscrits</div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>📈 Réservations & Revenus (6 derniers mois)</h3>
                    <canvas id="mainChart" height="100"></canvas>
                </div>
                
                <div class="chart-card">
                    <h3>🏆 Top 5 des biens</h3>
                    <ul class="top-list">
                        <?php foreach ($topBiens as $index => $bien): ?>
                        <li>
                            <div class="rank <?= $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : '')) ?>">
                                <?= $index + 1 ?>
                            </div>
                            <div class="info">
                                <div class="name"><?= htmlspecialchars($bien['nom_bien']) ?></div>
                                <div class="count"><?= $bien['nb_reservations'] ?> réservation(s)</div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Recent Reservations -->
            <div class="table-card">
                <h3>📋 Dernières réservations</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bien</th>
                            <th>Client</th>
                            <th>Dates</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReservations as $resa): ?>
                        <tr>
                            <td>#<?= $resa['id_reservation'] ?></td>
                            <td><?= htmlspecialchars($resa['nom_bien']) ?></td>
                            <td><?= htmlspecialchars($resa['prenom_locataire'] . ' ' . $resa['nom_locataire']) ?></td>
                            <td><?= date('d/m/Y', strtotime($resa['date_debut'])) ?> - <?= date('d/m/Y', strtotime($resa['date_fin'])) ?></td>
                            <td><strong><?= number_format($resa['total_cost'], 0, ',', ' ') ?> €</strong></td>
                            <td><span class="status confirmed">Confirmée</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // Chart.js Configuration
        const ctx = document.getElementById('mainChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Réservations',
                        data: <?= json_encode($chartValues) ?>,
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderRadius: 8,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Revenus (€)',
                        data: <?= json_encode($revenueValues) ?>,
                        type: 'line',
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        position: 'left',
                        title: { display: true, text: 'Réservations' }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        title: { display: true, text: 'Revenus (€)' },
                        grid: { drawOnChartArea: false }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
