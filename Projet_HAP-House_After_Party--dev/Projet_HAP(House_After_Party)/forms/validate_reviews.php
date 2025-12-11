<?php
session_start();
require_once '../config/db.php';

// Vérifier que l'utilisateur est un animateur (admin)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'animateur') {
    header('Location: ../auth/connexion_admin.php');
    exit;
}

// Récupérer l'ID de l'animateur
$animateur_id = $_SESSION['user_id'];

// Gérer la validation d'un avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_review'])) {
    $review_id = (int)$_POST['review_id'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE Reviews 
            SET validated = TRUE, 
                validated_by = ?, 
                validated_at = NOW() 
            WHERE id_review = ?
        ");
        $stmt->execute([$animateur_id, $review_id]);
        
        $success = "Avis validé avec succès !";
    } catch (PDOException $e) {
        $error = "Erreur lors de la validation : " . $e->getMessage();
    }
}

// Gérer le rejet d'un avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_review'])) {
    $review_id = (int)$_POST['review_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM Reviews WHERE id_review = ?");
        $stmt->execute([$review_id]);
        
        $success = "Avis supprimé avec succès !";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Récupérer tous les avis en attente de validation
try {
    $stmt = $pdo->query("
        SELECT 
            r.*,
            b.nom_biens,
            b.ville_biens,
            CONCAT(l.prenom_locataire, ' ', l.nom_locataire) as nom_locataire
        FROM Reviews r
        JOIN Biens b ON r.id_biens = b.id_biens
        LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire
        WHERE r.validated = FALSE
        ORDER BY r.created_at DESC
    ");
    $pending_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les avis validés
    $stmt = $pdo->query("
        SELECT 
            r.*,
            b.nom_biens,
            b.ville_biens,
            CONCAT(l.prenom_locataire, ' ', l.nom_locataire) as nom_locataire,
            CONCAT(a.prenom_animateur, ' ', a.nom_animateur) as validateur
        FROM Reviews r
        JOIN Biens b ON r.id_biens = b.id_biens
        LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire
        LEFT JOIN Animateur a ON r.validated_by = a.id_animateur
        WHERE r.validated = TRUE
        ORDER BY r.validated_at DESC
        LIMIT 50
    ");
    $validated_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des avis : " . $e->getMessage();
    $pending_reviews = [];
    $validated_reviews = [];
}

// Statistiques
try {
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN validated = FALSE THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN validated = TRUE THEN 1 ELSE 0 END) as valides,
            AVG(rating) as note_moyenne
        FROM Reviews
    ")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total' => 0, 'en_attente' => 0, 'valides' => 0, 'note_moyenne' => 0];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation des Avis - HAP Admin</title>
    <link rel="stylesheet" href="../Css/dashboard.css">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
        }

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-color: #f1f5f9;
            --border-color: #334155;
        }

        body {
            background: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            transition: background 0.3s, color 0.3s;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: 0.9em;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .tab {
            padding: 15px 30px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.1em;
            color: var(--text-color);
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .tab.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .review-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .review-bien {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--primary-color);
        }

        .review-meta {
            display: flex;
            gap: 20px;
            font-size: 0.9em;
            opacity: 0.7;
            margin-bottom: 15px;
        }

        .rating {
            display: flex;
            gap: 5px;
            font-size: 1.5em;
        }

        .star {
            color: #fbbf24;
        }

        .star.empty {
            opacity: 0.3;
        }

        .review-content {
            margin: 20px 0;
            padding: 20px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 10px;
            line-height: 1.6;
        }

        .review-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger-color);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.6;
        }

        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <?php include '../../theme_toggle.php'; ?>

    <div class="container">
        <div class="header">
            <h1>🎭 Validation des Avis</h1>
            <p>Gérez et modérez les avis des locataires sur les biens</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Avis</div>
                <div class="stat-number"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">En Attente</div>
                <div class="stat-number"><?= $stats['en_attente'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Validés</div>
                <div class="stat-number"><?= $stats['valides'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Note Moyenne</div>
                <div class="stat-number"><?= number_format($stats['note_moyenne'], 1) ?>/5</div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('pending')">
                En attente (<?= count($pending_reviews) ?>)
            </button>
            <button class="tab" onclick="switchTab('validated')">
                Validés (<?= count($validated_reviews) ?>)
            </button>
        </div>

        <div id="pending-tab" class="tab-content active">
            <?php if (empty($pending_reviews)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✨</div>
                    <h3>Aucun avis en attente</h3>
                    <p>Tous les avis ont été traités !</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <div class="review-bien"><?= htmlspecialchars($review['nom_biens']) ?></div>
                                <div style="opacity: 0.7;"><?= htmlspecialchars($review['ville_biens']) ?></div>
                            </div>
                            <span class="badge badge-warning">En attente</span>
                        </div>

                        <div class="review-meta">
                            <span>👤 <?= htmlspecialchars($review['nom_locataire'] ?: 'Anonyme') ?></span>
                            <span>📅 <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></span>
                        </div>

                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $review['rating'] ? '' : 'empty' ?>">★</span>
                            <?php endfor; ?>
                            <span style="margin-left: 10px; font-size: 0.8em; opacity: 0.7;">
                                (<?= $review['rating'] ?>/5)
                            </span>
                        </div>

                        <?php if (!empty($review['content'])): ?>
                            <div class="review-content">
                                "<?= nl2br(htmlspecialchars($review['content'])) ?>"
                            </div>
                        <?php endif; ?>

                        <div class="review-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="review_id" value="<?= $review['id_review'] ?>">
                                <button type="submit" name="validate_review" class="btn btn-success" 
                                        onclick="return confirm('Valider cet avis ?')">
                                    ✓ Valider
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="review_id" value="<?= $review['id_review'] ?>">
                                <button type="submit" name="reject_review" class="btn btn-danger" 
                                        onclick="return confirm('Supprimer cet avis définitivement ?')">
                                    ✗ Rejeter
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="validated-tab" class="tab-content">
            <?php if (empty($validated_reviews)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h3>Aucun avis validé</h3>
                    <p>Les avis validés apparaîtront ici</p>
                </div>
            <?php else: ?>
                <?php foreach ($validated_reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <div class="review-bien"><?= htmlspecialchars($review['nom_biens']) ?></div>
                                <div style="opacity: 0.7;"><?= htmlspecialchars($review['ville_biens']) ?></div>
                            </div>
                            <span class="badge badge-success">✓ Validé</span>
                        </div>

                        <div class="review-meta">
                            <span>👤 <?= htmlspecialchars($review['nom_locataire'] ?: 'Anonyme') ?></span>
                            <span>📅 <?= date('d/m/Y', strtotime($review['created_at'])) ?></span>
                            <span>✅ Par <?= htmlspecialchars($review['validateur']) ?></span>
                        </div>

                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?= $i <= $review['rating'] ? '' : 'empty' ?>">★</span>
                            <?php endfor; ?>
                            <span style="margin-left: 10px; font-size: 0.8em; opacity: 0.7;">
                                (<?= $review['rating'] ?>/5)
                            </span>
                        </div>

                        <?php if (!empty($review['content'])): ?>
                            <div class="review-content">
                                "<?= nl2br(htmlspecialchars($review['content'])) ?>"
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Masquer tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activer l'onglet et le contenu sélectionnés
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
