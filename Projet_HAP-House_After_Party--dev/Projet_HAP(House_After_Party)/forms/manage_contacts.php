<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'animateur') {
    header('Location: /Projet_HAP(House_After_Party)/auth/connexion.php');
    exit;
}
require_once '../config/db.php';

$message = '';
$messageType = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_message = $_POST['id_message'] ?? 0;
    
    if ($action === 'update_status' && $id_message) {
        $new_status = $_POST['new_status'] ?? '';
        if (in_array($new_status, ['nouveau', 'en_cours', 'resolu', 'ferme'])) {
            $stmt = $pdo->prepare("UPDATE Contact_Messages SET statut = ? WHERE id_message = ?");
            $stmt->execute([$new_status, $id_message]);
            $message = "Statut mis à jour avec succès !";
            $messageType = 'success';
        }
    }
    
    if ($action === 'reply' && $id_message) {
        $reponse = trim($_POST['reponse'] ?? '');
        if ($reponse) {
            $stmt = $pdo->prepare("UPDATE Contact_Messages SET reponse_admin = ?, statut = 'resolu' WHERE id_message = ?");
            $stmt->execute([$reponse, $id_message]);
            $message = "Réponse envoyée avec succès !";
            $messageType = 'success';
        }
    }
    
    if ($action === 'delete' && $id_message) {
        $stmt = $pdo->prepare("DELETE FROM Contact_Messages WHERE id_message = ?");
        $stmt->execute([$id_message]);
        $message = "Message supprimé avec succès !";
        $messageType = 'success';
    }
}

// Créer la table si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS Contact_Messages (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    type_message ENUM('question', 'signalement', 'erreur', 'suggestion', 'autre') NOT NULL,
    sujet VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    email VARCHAR(255),
    nom VARCHAR(100),
    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
    page_concernee VARCHAR(255),
    id_locataire INT,
    statut ENUM('nouveau', 'en_cours', 'resolu', 'ferme') DEFAULT 'nouveau',
    reponse_admin TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_locataire) REFERENCES Locataire(id_locataire) ON DELETE SET NULL
)");

// Filtres
$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$search = $_GET['search'] ?? '';

// Construction de la requête
$sql = "SELECT cm.*, 
        CONCAT(l.prenom_locataire, ' ', l.nom_locataire) as nom_complet,
        l.email_locataire
        FROM Contact_Messages cm
        LEFT JOIN Locataire l ON cm.id_locataire = l.id_locataire
        WHERE 1=1";
$params = [];

if ($filterStatus) {
    $sql .= " AND cm.statut = ?";
    $params[] = $filterStatus;
}

if ($filterType) {
    $sql .= " AND cm.type_message = ?";
    $params[] = $filterType;
}

if ($filterPriority) {
    $sql .= " AND cm.priorite = ?";
    $params[] = $filterPriority;
}

if ($search) {
    $sql .= " AND (cm.sujet LIKE ? OR cm.message LIKE ? OR cm.email LIKE ? OR cm.nom LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$sql .= " ORDER BY 
    CASE cm.priorite 
        WHEN 'urgente' THEN 1 
        WHEN 'haute' THEN 2 
        WHEN 'normale' THEN 3 
        WHEN 'basse' THEN 4 
    END,
    cm.date_creation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats = [
    'total' => 0,
    'nouveau' => 0,
    'en_cours' => 0,
    'resolu' => 0,
    'urgente' => 0
];

try {
    $stmtStats = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as nouveau,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'resolu' THEN 1 ELSE 0 END) as resolu,
        SUM(CASE WHEN priorite = 'urgente' AND statut NOT IN ('resolu', 'ferme') THEN 1 ELSE 0 END) as urgente
        FROM Contact_Messages");
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Messages - Admin HAP</title>
    <link rel="stylesheet" href="../Css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .tickets-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Stats cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-mini {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid var(--card-border);
        }
        
        .stat-mini-value {
            font-size: 2rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-mini-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        .stat-mini.urgent .stat-mini-value {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            -webkit-background-clip: text;
        }
        
        /* Filters */
        .filters-bar {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
            padding: 20px;
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--card-border);
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid var(--card-border);
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
            min-width: 150px;
        }
        
        .filter-group select option {
            background: #1a1a2e;
        }
        
        .btn-filter {
            align-self: flex-end;
            padding: 10px 20px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-reset {
            align-self: flex-end;
            padding: 10px 20px;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--card-border);
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }
        
        /* Tickets list */
        .tickets-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .ticket-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--card-border);
            transition: all 0.3s ease;
        }
        
        .ticket-card:hover {
            border-color: var(--hap-primary);
            box-shadow: 0 10px 30px rgba(161, 0, 184, 0.2);
        }
        
        .ticket-card.priority-urgente {
            border-left: 4px solid #ef4444;
        }
        
        .ticket-card.priority-haute {
            border-left: 4px solid #f97316;
        }
        
        .ticket-card.priority-normale {
            border-left: 4px solid #3b82f6;
        }
        
        .ticket-card.priority-basse {
            border-left: 4px solid #22c55e;
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .ticket-id {
            color: var(--hap-primary);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .ticket-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-type {
            background: rgba(161, 0, 184, 0.2);
            color: #c94ddb;
        }
        
        .badge-status {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .badge-status.nouveau { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-status.en_cours { background: rgba(249, 115, 22, 0.2); color: #f97316; }
        .badge-status.resolu { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-status.ferme { background: rgba(107, 114, 128, 0.2); color: #6b7280; }
        
        .badge-priority {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .badge-priority.basse { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .badge-priority.normale { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-priority.haute { background: rgba(249, 115, 22, 0.2); color: #f97316; }
        .badge-priority.urgente { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        
        .ticket-sujet {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .ticket-message {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
        }
        
        .ticket-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 15px;
        }
        
        .ticket-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .ticket-page {
            background: rgba(161, 0, 184, 0.1);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            color: var(--hap-primary);
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .ticket-reponse-existante {
            background: rgba(34, 197, 94, 0.1);
            border-left: 3px solid #22c55e;
            padding: 15px;
            border-radius: 0 10px 10px 0;
            margin-bottom: 15px;
        }
        
        .ticket-reponse-existante h4 {
            color: #22c55e;
            margin: 0 0 8px 0;
            font-size: 0.9rem;
        }
        
        .ticket-reponse-existante p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        /* Actions */
        .ticket-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            padding-top: 15px;
            border-top: 1px solid var(--card-border);
        }
        
        .action-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .action-form select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--card-border);
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
            font-size: 0.85rem;
        }
        
        .btn-action {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-action.primary {
            background: var(--gradient-primary);
            color: white;
        }
        
        .btn-action.secondary {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
        }
        
        .btn-action.danger {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Reply modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: #1a1a2e;
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            border: 1px solid var(--card-border);
        }
        
        .modal-content h3 {
            color: var(--text-primary);
            margin-bottom: 20px;
        }
        
        .modal-content textarea {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid var(--card-border);
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            margin-bottom: 20px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Alert messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #22c55e;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        /* Back link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--hap-primary);
        }
        
        /* ===== LIGHT THEME ===== */
        [data-theme="light"] body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #dee2e6 100%);
        }
        
        [data-theme="light"] .filters-bar,
        [data-theme="light"] .ticket-card,
        [data-theme="light"] .stat-mini {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(161, 0, 184, 0.15);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        
        [data-theme="light"] .filter-group label {
            color: #475569;
        }
        
        [data-theme="light"] .filter-group select,
        [data-theme="light"] .filter-group input {
            background: #f8f9fa;
            border-color: #e2e8f0;
            color: #1e293b;
        }
        
        [data-theme="light"] .filter-group select option {
            background: #fff;
            color: #1e293b;
        }
        
        [data-theme="light"] .btn-reset {
            color: #475569;
            border-color: #e2e8f0;
        }
        
        [data-theme="light"] .btn-reset:hover {
            background: #f1f5f9;
        }
        
        [data-theme="light"] .stat-mini-label {
            color: #64748b;
        }
        
        [data-theme="light"] .ticket-sujet {
            color: #1e293b;
        }
        
        [data-theme="light"] .ticket-message {
            background: #f8f9fa;
            color: #475569;
        }
        
        [data-theme="light"] .ticket-meta {
            color: #64748b;
        }
        
        [data-theme="light"] .ticket-actions {
            border-color: #e2e8f0;
        }
        
        [data-theme="light"] .action-form select {
            background: #f8f9fa;
            border-color: #e2e8f0;
            color: #1e293b;
        }
        
        [data-theme="light"] .ticket-reponse-existante {
            background: rgba(34, 197, 94, 0.08);
        }
        
        [data-theme="light"] .ticket-reponse-existante p {
            color: #475569;
        }
        
        /* Modal light theme */
        [data-theme="light"] .modal-content {
            background: #ffffff;
            border-color: rgba(161, 0, 184, 0.2);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        [data-theme="light"] .modal-content h3 {
            color: #1e293b;
        }
        
        [data-theme="light"] .modal-content p {
            color: #64748b;
        }
        
        [data-theme="light"] .modal-content textarea {
            background: #f8f9fa;
            border-color: #e2e8f0;
            color: #1e293b;
        }
        
        [data-theme="light"] .modal-content textarea::placeholder {
            color: #94a3b8;
        }
        
        [data-theme="light"] .empty-state {
            color: #64748b;
        }
        
        [data-theme="light"] .empty-state h3 {
            color: #1e293b;
        }
        
        [data-theme="light"] .back-link {
            color: #64748b;
        }
    </style>
</head>
<body>
    <header>
        <h1>📬 Gestion des Messages</h1>
        <nav>
            <a href="/index.php">🏠 Accueil</a>
            <a href="/apropos.php">📊 Dashboard</a>
            <a href="../auth/logout.php">🚪 Déconnexion</a>
        </nav>
    </header>
    
    <?php include '../../theme_toggle.php'; ?>
    
    <main>
        <div class="tickets-container">
            <a href="/apropos.php" class="back-link">← Retour au Dashboard</a>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <span><?= $messageType === 'success' ? '✅' : '❌' ?></span>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistiques -->
            <div class="stats-row">
                <div class="stat-mini">
                    <div class="stat-mini-value"><?= $stats['total'] ?? 0 ?></div>
                    <div class="stat-mini-label">Total messages</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?= $stats['nouveau'] ?? 0 ?></div>
                    <div class="stat-mini-label">Nouveaux</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?= $stats['en_cours'] ?? 0 ?></div>
                    <div class="stat-mini-label">En cours</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-value"><?= $stats['resolu'] ?? 0 ?></div>
                    <div class="stat-mini-label">Résolus</div>
                </div>
                <div class="stat-mini urgent">
                    <div class="stat-mini-value"><?= $stats['urgente'] ?? 0 ?></div>
                    <div class="stat-mini-label">🔴 Urgents</div>
                </div>
            </div>
            
            <!-- Filtres -->
            <form class="filters-bar" method="GET">
                <div class="filter-group">
                    <label>Statut</label>
                    <select name="status">
                        <option value="">Tous</option>
                        <option value="nouveau" <?= $filterStatus === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                        <option value="en_cours" <?= $filterStatus === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="resolu" <?= $filterStatus === 'resolu' ? 'selected' : '' ?>>Résolu</option>
                        <option value="ferme" <?= $filterStatus === 'ferme' ? 'selected' : '' ?>>Fermé</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="">Tous</option>
                        <option value="question" <?= $filterType === 'question' ? 'selected' : '' ?>>Question</option>
                        <option value="signalement" <?= $filterType === 'signalement' ? 'selected' : '' ?>>Signalement</option>
                        <option value="erreur" <?= $filterType === 'erreur' ? 'selected' : '' ?>>Bug/Erreur</option>
                        <option value="suggestion" <?= $filterType === 'suggestion' ? 'selected' : '' ?>>Suggestion</option>
                        <option value="autre" <?= $filterType === 'autre' ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Priorité</label>
                    <select name="priority">
                        <option value="">Toutes</option>
                        <option value="urgente" <?= $filterPriority === 'urgente' ? 'selected' : '' ?>>🔴 Urgente</option>
                        <option value="haute" <?= $filterPriority === 'haute' ? 'selected' : '' ?>>🟠 Haute</option>
                        <option value="normale" <?= $filterPriority === 'normale' ? 'selected' : '' ?>>🔵 Normale</option>
                        <option value="basse" <?= $filterPriority === 'basse' ? 'selected' : '' ?>>🟢 Basse</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Recherche</label>
                    <input type="text" name="search" placeholder="Sujet, message, email..." value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <button type="submit" class="btn-filter">🔍 Filtrer</button>
                <a href="manage_contacts.php" class="btn-reset">Réinitialiser</a>
            </form>
            
            <!-- Liste des tickets -->
            <div class="tickets-list">
                <?php if (empty($tickets)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3>Aucun message trouvé</h3>
                        <p>Il n'y a pas de messages correspondant à vos critères.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="ticket-card priority-<?= $ticket['priorite'] ?>">
                            <div class="ticket-header">
                                <span class="ticket-id">#<?= str_pad($ticket['id_message'], 6, '0', STR_PAD_LEFT) ?></span>
                                <div class="ticket-badges">
                                    <span class="badge badge-type">
                                        <?php
                                        $typeIcons = ['question' => '❓', 'signalement' => '🚨', 'erreur' => '🐛', 'suggestion' => '💡', 'autre' => '📝'];
                                        echo ($typeIcons[$ticket['type_message']] ?? '📝') . ' ' . ucfirst($ticket['type_message']);
                                        ?>
                                    </span>
                                    <span class="badge badge-status <?= $ticket['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $ticket['statut'])) ?></span>
                                    <span class="badge badge-priority <?= $ticket['priorite'] ?>">
                                        <?php
                                        $prioIcons = ['basse' => '🟢', 'normale' => '🔵', 'haute' => '🟠', 'urgente' => '🔴'];
                                        echo ($prioIcons[$ticket['priorite']] ?? '') . ' ' . ucfirst($ticket['priorite']);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="ticket-sujet"><?= htmlspecialchars($ticket['sujet']) ?></div>
                            
                            <div class="ticket-message"><?= nl2br(htmlspecialchars($ticket['message'])) ?></div>
                            
                            <?php if ($ticket['page_concernee']): ?>
                                <div class="ticket-page">📄 Page: <?= htmlspecialchars($ticket['page_concernee']) ?></div>
                            <?php endif; ?>
                            
                            <div class="ticket-meta">
                                <span>👤 <?= htmlspecialchars($ticket['nom'] ?: $ticket['nom_complet'] ?: 'Anonyme') ?></span>
                                <span>📧 <?= htmlspecialchars($ticket['email'] ?: $ticket['email_locataire'] ?: 'Non renseigné') ?></span>
                                <span>📅 <?= date('d/m/Y à H:i', strtotime($ticket['date_creation'])) ?></span>
                            </div>
                            
                            <?php if ($ticket['reponse_admin']): ?>
                                <div class="ticket-reponse-existante">
                                    <h4>✅ Réponse de l'équipe</h4>
                                    <p><?= nl2br(htmlspecialchars($ticket['reponse_admin'])) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="ticket-actions">
                                <!-- Changer le statut -->
                                <form class="action-form" method="POST">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id_message" value="<?= $ticket['id_message'] ?>">
                                    <select name="new_status">
                                        <option value="nouveau" <?= $ticket['statut'] === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                                        <option value="en_cours" <?= $ticket['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                                        <option value="resolu" <?= $ticket['statut'] === 'resolu' ? 'selected' : '' ?>>Résolu</option>
                                        <option value="ferme" <?= $ticket['statut'] === 'ferme' ? 'selected' : '' ?>>Fermé</option>
                                    </select>
                                    <button type="submit" class="btn-action secondary">Mettre à jour</button>
                                </form>
                                
                                <!-- Répondre -->
                                <button type="button" class="btn-action primary" onclick="openReplyModal(<?= $ticket['id_message'] ?>, '<?= htmlspecialchars(addslashes($ticket['sujet'])) ?>')">
                                    💬 Répondre
                                </button>
                                
                                <!-- Supprimer -->
                                <form class="action-form" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_message" value="<?= $ticket['id_message'] ?>">
                                    <button type="submit" class="btn-action danger">🗑️ Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Modal de réponse -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-content">
            <h3>💬 Répondre au ticket <span id="modalTicketId"></span></h3>
            <p style="color: var(--text-secondary); margin-bottom: 15px;">Sujet: <strong id="modalTicketSujet"></strong></p>
            <form method="POST" id="replyForm">
                <input type="hidden" name="action" value="reply">
                <input type="hidden" name="id_message" id="replyTicketId">
                <textarea name="reponse" placeholder="Tapez votre réponse ici..." required></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-action secondary" onclick="closeReplyModal()">Annuler</button>
                    <button type="submit" class="btn-action primary">📤 Envoyer la réponse</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openReplyModal(ticketId, sujet) {
        document.getElementById('replyModal').classList.add('show');
        document.getElementById('modalTicketId').textContent = '#' + String(ticketId).padStart(6, '0');
        document.getElementById('modalTicketSujet').textContent = sujet;
        document.getElementById('replyTicketId').value = ticketId;
    }
    
    function closeReplyModal() {
        document.getElementById('replyModal').classList.remove('show');
        document.getElementById('replyForm').reset();
    }
    
    // Fermer la modal en cliquant à l'extérieur
    document.getElementById('replyModal').addEventListener('click', function(e) {
        if (e.target === this) closeReplyModal();
    });
    
    // Fermer avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeReplyModal();
    });
    </script>
</body>
</html>
