<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';
$messageType = '';
$reviews = [];
$biens = [];
$avgRating = 0;
$totalAllReviews = 0;
$totalReviews = 0;
$totalPages = 1;
$page = 1;
$filterBien = '';
$filterNote = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Add user_type column if it does not exist (portable for MySQL versions without ADD COLUMN IF NOT EXISTS).
        $colCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'user_type'");
        $colCheck->execute();
        $hasUserType = (int) $colCheck->fetchColumn() > 0;
        if (!$hasUserType) {
            $pdo->exec("ALTER TABLE Reviews ADD COLUMN user_type VARCHAR(10) DEFAULT 'locataire'");
        }
        
        // Handle new blog/review submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_review'])) {
            if (!isset($_SESSION['user_id'])) {
                $message = 'Vous devez être connecté pour publier un avis.';
                $messageType = 'error';
            } else {
                $bien_id = intval($_POST['bien_id'] ?? 0);
                $content = trim($_POST['content'] ?? '');
                $rating = intval($_POST['rating'] ?? 0);
                $userId = intval($_SESSION['user_id']);

                if ($bien_id > 0 && ($rating > 0 || $content !== '')) {
                    $ins = $pdo->prepare('INSERT INTO Reviews (id_biens, id_locataire, rating, content, created_at) VALUES (?, ?, ?, ?, NOW())');
                    $ins->execute([$bien_id, $userId, $rating > 0 ? $rating : null, $content]);
                    $message = '✨ Merci pour votre avis ! Il sera visible après validation.';
                    $messageType = 'success';
                } else {
                    $message = 'Veuillez sélectionner un bien et laisser une note ou un commentaire.';
                    $messageType = 'error';
                }
            }
        }

        // Pagination
        $perPage = 6;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $perPage;

        // Filtres
        $filterBien = trim($_GET['filter_bien'] ?? '');
        $filterNote = trim($_GET['filter_note'] ?? '');

        // Count with filters
        $whereCount = [];
        $paramsCount = [];
        if ($filterBien) {
            $whereCount[] = 'b.nom_biens LIKE ?';
            $paramsCount[] = '%' . $filterBien . '%';
        }
        if ($filterNote && in_array($filterNote, ['1','2','3','4','5'])) {
            $whereCount[] = 'r.rating = ?';
            $paramsCount[] = intval($filterNote);
        }
        $whereCountSql = $whereCount ? ('WHERE ' . implode(' AND ', $whereCount)) : '';
        
        $countSql = "SELECT COUNT(*) as total FROM Reviews r LEFT JOIN Biens b ON r.id_biens = b.id_biens $whereCountSql";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($paramsCount);
        $totalReviews = intval($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $totalPages = $totalReviews > 0 ? ceil($totalReviews / $perPage) : 1;

        // Load reviews
        $where = $whereCount;
        $params = $paramsCount;
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        
        $sql = "SELECT r.id_review, r.rating, r.content, r.created_at, b.nom_biens, b.id_biens,
                    CASE
                        WHEN l.id_locataire IS NOT NULL THEN CONCAT(l.prenom_locataire, ' ', l.nom_locataire)
                        ELSE CONCAT(a.prenom_animateur, ' ', a.nom_animateur)
                    END as nom_complet
                FROM Reviews r
                LEFT JOIN Biens b ON r.id_biens = b.id_biens
                LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire
                LEFT JOIN Animateur a ON r.id_locataire = a.id_animateur
                $whereSql
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $paramIndex = 1;
        foreach ($params as $v) {
            $stmt->bindValue($paramIndex, $v);
            $paramIndex++;
        }
        $stmt->bindValue($paramIndex, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramIndex + 1, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats
        $avgRating = $pdo->query("SELECT AVG(rating) as avg FROM Reviews WHERE rating IS NOT NULL")->fetch()['avg'] ?? 0;
        $totalAllReviews = $pdo->query("SELECT COUNT(*) as cnt FROM Reviews")->fetch()['cnt'] ?? 0;

        // Biens pour autocomplete (pour le formulaire)
        $biens = $pdo->query("SELECT id_biens, nom_biens FROM Biens ORDER BY nom_biens")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = 'Erreur : ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog & Avis - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-primary: rgba(255, 255, 255, 0.95);
            --bg-secondary: #fafafa;
            --text-primary: #333;
            --text-secondary: #666;
            --text-muted: #888;
            --border-color: #e0e0e0;
            --border-top: #f0f0f0;
        }

        [data-theme="dark"] {
            --bg-primary: rgba(30, 30, 40, 0.95);
            --bg-secondary: #2a2a3a;
            --text-primary: #f0f0f0;
            --text-secondary: #c0c0c0;
            --text-muted: #999;
            --border-color: #444;
            --border-top: #444;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .blog-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header */
        .blog-hero {
            background: var(--bg-primary);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #764ba2;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        [data-theme="dark"] .back-link {
            color: #a78bfa;
        }

        .back-link:hover {
            color: #667eea;
            transform: translateX(-5px);
        }

        .blog-hero h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .blog-hero p {
            color: var(--text-secondary);
            font-size: 1.1em;
        }

        /* Stats */
        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid var(--border-top);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: 700;
            color: #764ba2;
        }

        [data-theme="dark"] .stat-number {
            color: #a78bfa;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9em;
        }

        /* Messages */
        .message {
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .message.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Main Grid */
        .blog-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .blog-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Form Section */
        .form-card {
            background: var(--bg-primary);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 20px;
            height: fit-content;
        }

        .form-card h2 {
            color: var(--text-primary);
            font-size: 1.4em;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-prompt {
            text-align: center;
            padding: 30px 20px;
        }

        .login-prompt p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .login-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-login {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-signup {
            display: inline-block;
            padding: 12px 25px;
            background: transparent;
            color: #764ba2;
            text-decoration: none;
            border: 2px solid #764ba2;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-signup:hover {
            background: #764ba2;
            color: white;
        }

        .user-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95em;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1em;
            font-family: inherit;
            transition: all 0.3s;
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            border-color: #764ba2;
            outline: none;
            background: var(--bg-primary);
            box-shadow: 0 0 0 4px rgba(118, 75, 162, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Star Rating */
        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            cursor: pointer;
            font-size: 2em;
            color: #ddd;
            transition: all 0.2s;
        }

        .rating-input label:hover,
        .rating-input label:hover ~ label,
        .rating-input input:checked ~ label {
            color: #ffc107;
            transform: scale(1.1);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        /* Reviews Section */
        .reviews-section {
            background: var(--bg-primary);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .reviews-header h2 {
            color: var(--text-primary);
            font-size: 1.4em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Filter */
        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95em;
            font-family: inherit;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: #764ba2;
            outline: none;
        }

        .filter-bar input {
            min-width: 200px;
        }

        .btn-filter {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
        }

        .btn-reset {
            padding: 10px 16px;
            background: #f0f0f0;
            color: #666;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: #e0e0e0;
        }

        /* Review Cards */
        .reviews-grid {
            display: grid;
            gap: 20px;
        }

        .review-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 25px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .review-card:hover {
            border-color: #764ba2;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(118, 75, 162, 0.1);
        }

        .review-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .review-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1em;
        }

        .author-info h4 {
            color: var(--text-primary);
            margin-bottom: 3px;
        }

        .author-info span {
            color: var(--text-muted);
            font-size: 0.85em;
        }

        .review-rating {
            display: flex;
            gap: 3px;
        }

        .review-rating .star {
            color: #ffc107;
            font-size: 1.2em;
        }

        .review-rating .star.empty {
            color: #ddd;
        }

        .review-property {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .review-content {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 15px;
        }

        .review-date {
            color: var(--text-muted);
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state .icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--text-primary);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 10px 16px;
            background: var(--bg-secondary);
            color: #764ba2;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
        }

        [data-theme="dark"] .pagination a {
            color: #a78bfa;
        }

        .pagination a:hover,
        .pagination a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: transparent;
        }

        /* Autocomplete Styling */
        .ui-autocomplete {
            max-height: 250px;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--bg-primary);
            border: 2px solid #764ba2;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 9999 !important;
        }

        .ui-menu-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: background 0.2s;
            color: var(--text-primary);
        }

        .ui-menu-item:hover,
        .ui-state-active {
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            color: white !important;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .blog-hero h1 {
                font-size: 1.8em;
            }

            .stats-bar {
                flex-direction: column;
                gap: 20px;
            }

            .filter-bar {
                flex-direction: column;
            }

            .filter-bar input {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="blog-wrapper">
        <!-- Hero Section -->
        <div class="blog-hero">
            <a href="../../index.php" class="back-link">← Retour à l'accueil</a>
            <h1>📝 Blog & Avis</h1>
            <p>Partagez votre expérience et découvrez les avis de notre communauté</p>
            
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($totalAllReviews ?? 0) ?></div>
                    <div class="stat-label">Avis publiés</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= number_format($avgRating ?? 0, 1) ?> ★</div>
                    <div class="stat-label">Note moyenne</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= count($biens ?? []) ?></div>
                    <div class="stat-label">Biens disponibles</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="blog-grid">
            <!-- Form Card -->
            <div class="form-card">
                <h2>✍️ Laisser un avis</h2>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="login-prompt">
                        <p>Connectez-vous pour partager votre expérience</p>
                        <div class="login-buttons">
                            <a href="../auth/connexion.php" class="btn-login">Se connecter</a>
                            <a href="../auth/inscription.php" class="btn-signup">Créer un compte</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="user-badge">
                        👤 Connecté en tant que <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Utilisateur') ?></strong>
                    </div>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="bien_input">🏠 Sélectionnez un bien</label>
                            <input type="text" id="bien_input" name="bien_label" placeholder="Tapez pour rechercher..." autocomplete="off" required>
                            <input type="hidden" id="bien_id" name="bien_id">
                        </div>
                        
                        <div class="form-group">
                            <label>⭐ Votre note</label>
                            <div class="rating-input">
                                <?php for ($r = 5; $r >= 1; $r--): ?>
                                    <input type="radio" name="rating" id="star<?= $r ?>" value="<?= $r ?>">
                                    <label for="star<?= $r ?>">★</label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">💬 Votre commentaire</label>
                            <textarea id="content" name="content" placeholder="Partagez votre expérience..."></textarea>
                        </div>
                        
                        <button type="submit" name="post_review" class="btn-submit">
                            Publier mon avis
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Reviews Section -->
            <div class="reviews-section">
                <div class="reviews-header">
                    <h2>💬 Derniers avis (<?= $totalReviews ?>)</h2>
                    
                    <form method="get" class="filter-bar">
                        <input type="text" 
                               id="filter_bien_input" 
                               name="filter_bien" 
                               placeholder="🔍 Filtrer par bien..." 
                               value="<?= htmlspecialchars($filterBien ?? '') ?>"
                               autocomplete="off">
                        <select name="filter_note">
                            <option value="">Toutes les notes</option>
                            <?php for($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= ($filterNote == $i) ? 'selected' : '' ?>><?= $i ?> ★</option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn-filter">Filtrer</button>
                        <?php if ($filterBien || $filterNote): ?>
                            <a href="blog.php" class="btn-reset">✕ Réinitialiser</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="reviews-grid">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $rev): ?>
                            <div class="review-card">
                                <div class="review-top">
                                    <div class="review-author">
                                        <div class="author-avatar">
                                            <?= strtoupper(substr($rev['nom_complet'] ?? 'A', 0, 1)) ?>
                                        </div>
                                        <div class="author-info">
                                            <h4><?= htmlspecialchars($rev['nom_complet'] ?? 'Anonyme') ?></h4>
                                            <span><?= date('d/m/Y', strtotime($rev['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= intval($rev['rating']) ? '' : 'empty' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="review-property">
                                    🏠 <?= htmlspecialchars($rev['nom_biens'] ?? 'Bien supprimé') ?>
                                </div>
                                
                                <?php if (!empty($rev['content'])): ?>
                                    <p class="review-content"><?= nl2br(htmlspecialchars($rev['content'])) ?></p>
                                <?php endif; ?>
                                
                                <div class="review-date">
                                    🕐 Posté le <?= date('d/m/Y à H:i', strtotime($rev['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="icon">📭</div>
                            <h3>Aucun avis pour le moment</h3>
                            <p>Soyez le premier à partager votre expérience !</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>&filter_bien=<?= urlencode($filterBien) ?>&filter_note=<?= urlencode($filterNote) ?>">« Précédent</a>
                        <?php endif; ?>
                        
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($p = $start; $p <= $end; $p++): 
                        ?>
                            <a href="?page=<?= $p ?>&filter_bien=<?= urlencode($filterBien) ?>&filter_note=<?= urlencode($filterNote) ?>" 
                               class="<?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>&filter_bien=<?= urlencode($filterBien) ?>&filter_note=<?= urlencode($filterNote) ?>">Suivant »</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(function() {
            // Autocomplete pour le formulaire d'ajout d'avis
            $('#bien_input').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '../api/search_biens.php',
                        dataType: 'json',
                        data: { q: request.term },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#bien_input').val(ui.item.label);
                    $('#bien_id').val(ui.item.id);
                    return false;
                }
            });

            // Autocomplete pour le filtre (avec ID: filter_bien_input)
            $('#filter_bien_input').autocomplete({
                source: function(request, response) {
                    $.ajax({
                        url: '../api/search_biens.php',
                        dataType: 'json',
                        data: { q: request.term },
                        success: function(data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: function(event, ui) {
                    $('#filter_bien_input').val(ui.item.label);
                    return false;
                }
            });

            // Clear hidden field when typing in bien input
            $('#bien_input').on('input', function() {
                $('#bien_id').val('');
            });
        });
    </script>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
