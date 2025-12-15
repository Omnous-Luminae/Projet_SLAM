<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Add user_type column if not exists
        $pdo->exec("ALTER TABLE Reviews ADD COLUMN IF NOT EXISTS user_type VARCHAR(10) DEFAULT 'locataire'");
        // Handle new blog/review submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_review'])) {
            // Vérifier si l'utilisateur est connecté
            if (!isset($_SESSION['user_id'])) {
                $message = 'Vous devez être connecté pour publier un avis. <a href="../auth/connexion.php" style="color: inherit;">Se connecter</a>';
            } else {
                $bien_id = intval($_POST['bien_id'] ?? 0);
                $content = trim($_POST['content'] ?? '');
                $rating = intval($_POST['rating'] ?? 0);
                $userId = intval($_SESSION['user_id']);

                if ($bien_id > 0 && ($rating > 0 || $content !== '')) {
                $ins = $pdo->prepare('INSERT INTO Reviews (id_biens, id_locataire, rating, content, created_at) VALUES (?, ?, ?, ?, NOW())');
                    $ins->execute([$bien_id, $userId, $rating > 0 ? $rating : null, $content]);
                    $message = 'Merci pour votre retour.';
                } else {
                    $message = 'Sélectionnez un bien et laissez une note ou un commentaire.';
                }
            }
        }

        // Pagination for reviews (10 per page)
        $perPage = 10;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $perPage;

        // Total reviews count
        $countStmt = $pdo->query('SELECT COUNT(*) as total FROM Reviews');
        $totalReviews = intval($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        $totalPages = $totalReviews > 0 ? ceil($totalReviews / $perPage) : 1;

        // Filtres pour la recherche d'avis
        $filterBien = trim($_GET['filter_bien'] ?? '');
        $filterNote = trim($_GET['filter_note'] ?? '');

        // Load reviews page with bien info and user details
        $where = [];
        $params = [];
        if ($filterBien) {
            $where[] = 'b.nom_biens LIKE ?';
            $params[] = '%' . $filterBien . '%';
        }
        if ($filterNote && in_array($filterNote, ['1','2','3','4','5'])) {
            $where[] = 'r.rating = ?';
            $params[] = intval($filterNote);
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "SELECT r.id_review, r.rating, r.content, r.created_at, b.nom_biens,
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
    }
} catch (Exception $e) {
    $message = 'Erreur : ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Blog & Avis</title>
    <link rel="stylesheet" href="../Css/style.css">
    <link rel="stylesheet" href="../Css/blog.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="../js/autocomplete.js"></script>
</head>
<body>
    <div class="blog-container">
        <a href="/../index.php" class="back-link">&larr; Retour</a>
        <div class="blog-header">
            <h2>Partager votre expérience</h2>
        </div>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="form-section">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="alert alert-info">
                    <p>Vous devez être connecté pour publier un avis.</p>
                    <a href="../auth/connexion.php" class="login-link">Se connecter</a>
                    <span class="or-signup">ou</span>
                    <a href="../auth/inscription.php" class="signup-link">S'inscrire</a>
                </div>
            <?php else: ?>
            <form method="post">
                <div class="user-info">
                    Connecté en tant que <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                </div>
                <div class="form-group">
                    <label for="bien_input">Bien</label>
                    <input type="text" id="bien_input" name="bien_label" placeholder="Tapez le nom du bien..." required>
                    <input type="hidden" id="bien_id" name="bien_id">
                </div>
                <div class="form-group">
                    <label for="rating">Note</label>
                    <div class="rating-group">
                        <?php for ($r = 5; $r >= 1; $r--): ?>
                            <input type="radio" name="rating" id="rating<?= $r ?>" value="<?= $r ?>" style="display:none;">
                            <label for="rating<?= $r ?>" class="rating-label"><?= $r ?> ★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="content">Votre commentaire</label>
                    <textarea id="content" name="content" rows="5" style="width:100%;"></textarea>
                </div>
                <button type="submit" name="post_review" class="submit-button">Publier</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- FILTRE AVIS AMÉLIORÉ -->
        <div class="filter-section" style="margin:32px auto 24px auto;max-width:700px;">
            <form method="get" style="display:flex;gap:14px;align-items:center;justify-content:center;background:#f3e6fa;padding:20px 24px;border-radius:10px;box-shadow:0 2px 12px rgba(161,0,184,0.1);border:1px solid #e0aaff;">
                <input type="text" name="filter_bien" placeholder="Filtrer par nom de bien..." value="<?= htmlspecialchars($filterBien) ?>" style="padding:11px 16px;border-radius:6px;border:1.5px solid #a100b8;font-size:1.08em;min-width:200px;background:#fff;transition:border 0.2s;">
                <label style="font-weight:600;color:#a100b8;margin:0;">Note :</label>
                <select name="filter_note" onchange="this.form.submit()" style="padding:11px 16px;border-radius:6px;border:1.5px solid #a100b8;font-size:1.08em;background:#fff;transition:border 0.2s;">
                    <option value="">Toutes les notes</option>
                    <?php for($i=5;$i>=1;$i--): ?>
                        <option value="<?= $i ?>" <?= ($filterNote==$i)?'selected':''; ?>><?= $i ?> ★</option>
                    <?php endfor; ?>
                </select>
                <button type="submit" style="padding:11px 32px;border-radius:6px;background:linear-gradient(90deg, #a100b8 60%, #e0aaff 100%);color:#fff;border:none;font-weight:600;font-size:1.08em;box-shadow:0 2px 8px rgba(161,0,184,0.15);cursor:pointer;transition:background 0.2s;">Filtrer</button>
            </form>
        </div>

        <div class="reviews-section">
            <h3>Derniers avis</h3>
        <?php if (!empty($reviews)): foreach ($reviews as $rev): ?>
            <div class="review-card">
                <div class="review-header">
                    <div>
                        <span class="review-author"><?= htmlspecialchars($rev['nom_complet'] ?? 'Anonyme') ?></span>
                        <span> — </span>
                        <span class="review-property"><?= htmlspecialchars($rev['nom_biens'] ?? 'Bien supprimé') ?></span>
                    </div>
                </div>
                <div class="rating-stars"><?= str_repeat('★', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])) ?></div>
                <div class="review-content"><?= nl2br(htmlspecialchars($rev['content'])) ?></div>
                <div class="review-date">Posté le <?= htmlspecialchars(date('d-m-Y à H:i', strtotime($rev['created_at']))) ?></div>
            </div>
        <?php endforeach; else: ?>
            <p>Aucun avis pour le moment.</p>
        <?php endif; ?>

        <!-- Pagination for reviews -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="pagination-btn">&laquo; Précédent</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?= $p ?>" class="pagination-btn <?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="pagination-btn">Suivant &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(function(){
            initBiensAutocomplete('#bien_input', '#bien_id');
        });
    </script>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
