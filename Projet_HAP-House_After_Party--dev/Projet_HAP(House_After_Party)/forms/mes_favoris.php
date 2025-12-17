<?php
/**
 * Page des favoris de l'utilisateur
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// Redirection si non connecté
if (!isset($_SESSION['locataire_id'])) {
    header('Location: ../auth/connexion.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$locataireId = $_SESSION['locataire_id'];

// Créer la table si nécessaire
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Favoris (
            id_favori INT AUTO_INCREMENT PRIMARY KEY,
            id_locataire INT NOT NULL,
            id_biens INT NOT NULL,
            date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_favori (id_locataire, id_biens)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // OK
}

// Récupérer les favoris
try {
    $stmt = $pdo->prepare("
        SELECT b.*, f.date_ajout as date_favori,
               c.nom_commune, c.code_postal,
               tb.nom_type_biens as type_bien,
               (SELECT lien_photo FROM Photos WHERE id_biens = b.id_biens LIMIT 1) as photo,
               (SELECT AVG(note) FROM Avis WHERE id_biens = b.id_biens AND valider = 1) as note_moyenne,
               (SELECT COUNT(*) FROM Avis WHERE id_biens = b.id_biens AND valider = 1) as nb_avis
        FROM Favoris f
        JOIN Biens b ON f.id_biens = b.id_biens
        LEFT JOIN Commune c ON b.id_commune = c.id_commune
        LEFT JOIN Type_Biens tb ON b.id_type_biens = tb.id_type_biens
        WHERE f.id_locataire = ?
        ORDER BY f.date_ajout DESC
    ");
    $stmt->execute([$locataireId]);
    $favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $favoris = [];
    $error = "Erreur lors du chargement des favoris.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            --danger: #ef4444;
            --heart: #ef4444;
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
            padding: 30px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header .count {
            background: var(--accent);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.5em;
        }
        
        .back-link {
            color: var(--accent);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }
        
        .empty-state .icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h2 {
            margin-bottom: 10px;
            font-size: 1.5em;
        }
        
        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 25px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        /* Grid de favoris */
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }
        
        .favorite-card {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            position: relative;
        }
        
        .favorite-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .favorite-card .image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .favorite-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .favorite-card:hover img {
            transform: scale(1.05);
        }
        
        .favorite-card .no-image {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--accent), #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3em;
        }
        
        .favorite-card .badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .favorite-card .heart-btn {
            position: absolute;
            top: 55px;
            right: 15px;
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .favorite-card .heart-btn:hover {
            transform: scale(1.1);
        }
        
        .favorite-card .heart-btn.active {
            background: var(--heart);
            color: white;
        }
        
        .favorite-card .content {
            padding: 20px;
        }
        
        .favorite-card .title {
            font-size: 1.1em;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .favorite-card .title a {
            color: var(--text-primary);
            text-decoration: none;
        }
        
        .favorite-card .title a:hover {
            color: var(--accent);
        }
        
        .favorite-card .location {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
            font-size: 0.9em;
            margin-bottom: 12px;
        }
        
        .favorite-card .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .favorite-card .stars {
            color: #fbbf24;
        }
        
        .favorite-card .rating-count {
            color: var(--text-secondary);
            font-size: 0.85em;
        }
        
        .favorite-card .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .favorite-card .date-added {
            color: var(--text-secondary);
            font-size: 0.8em;
        }
        
        .favorite-card .view-btn {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .favorite-card .view-btn:hover {
            text-decoration: underline;
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast.error {
            border-left: 4px solid var(--danger);
        }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 20px; text-align: center; }
            .favorites-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                ❤️ Mes Favoris
                <span class="count"><?= count($favoris) ?></span>
            </h1>
            <a href="Annonce.form.php" class="back-link">← Retour aux annonces</a>
        </div>
        
        <?php if (empty($favoris)): ?>
        <div class="empty-state">
            <div class="icon">💔</div>
            <h2>Aucun favori pour le moment</h2>
            <p>Explorez nos biens et cliquez sur le cœur pour sauvegarder vos coups de cœur !</p>
            <a href="Annonce.form.php" class="btn btn-primary">
                🏠 Découvrir les biens
            </a>
        </div>
        <?php else: ?>
        <div class="favorites-grid">
            <?php foreach ($favoris as $bien): ?>
            <div class="favorite-card" data-bien-id="<?= $bien['id_biens'] ?>">
                <div class="image-container">
                    <?php if (!empty($bien['photo'])): ?>
                        <img src="<?= htmlspecialchars($bien['photo']) ?>" alt="<?= htmlspecialchars($bien['nom_biens']) ?>">
                    <?php else: ?>
                        <div class="no-image">🏠</div>
                    <?php endif; ?>
                    
                    <?php if (!empty($bien['type_bien'])): ?>
                        <span class="badge"><?= htmlspecialchars($bien['type_bien']) ?></span>
                    <?php endif; ?>
                    
                    <button class="heart-btn active" onclick="toggleFavorite(<?= $bien['id_biens'] ?>, this)">
                        ❤️
                    </button>
                </div>
                
                <div class="content">
                    <h3 class="title">
                        <a href="annonce_detail.php?id=<?= $bien['id_biens'] ?>">
                            <?= htmlspecialchars($bien['nom_biens']) ?>
                        </a>
                    </h3>
                    
                    <div class="location">
                        📍 <?= htmlspecialchars($bien['nom_commune'] ?? 'Non spécifié') ?>
                        <?php if (!empty($bien['code_postal'])): ?>
                            (<?= htmlspecialchars($bien['code_postal']) ?>)
                        <?php endif; ?>
                    </div>
                    
                    <div class="rating">
                        <span class="stars">
                            <?php 
                            $note = round($bien['note_moyenne'] ?? 0);
                            echo str_repeat('⭐', $note);
                            echo str_repeat('☆', 5 - $note);
                            ?>
                        </span>
                        <span class="rating-count">(<?= $bien['nb_avis'] ?? 0 ?> avis)</span>
                    </div>
                    
                    <div class="footer">
                        <span class="date-added">
                            Ajouté le <?= date('d/m/Y', strtotime($bien['date_favori'])) ?>
                        </span>
                        <a href="annonce_detail.php?id=<?= $bien['id_biens'] ?>" class="view-btn">
                            Voir →
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="toast" id="toast"></div>
    
    <script>
        function toggleFavorite(bienId, button) {
            fetch('../api/favoris.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle&bien_id=${bienId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'removed') {
                        // Supprimer la carte avec animation
                        const card = button.closest('.favorite-card');
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => {
                            card.remove();
                            // Mettre à jour le compteur
                            const countEl = document.querySelector('.header .count');
                            const currentCount = parseInt(countEl.textContent) - 1;
                            countEl.textContent = currentCount;
                            
                            // Si plus de favoris, afficher l'état vide
                            if (currentCount === 0) {
                                location.reload();
                            }
                        }, 300);
                        
                        showToast('❤️ Retiré des favoris');
                    }
                } else {
                    showToast('Erreur: ' + data.error, true);
                }
            })
            .catch(error => {
                showToast('Erreur de connexion', true);
            });
        }
        
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast show' + (isError ? ' error' : '');
            
            setTimeout(() => {
                toast.className = 'toast';
            }, 3000);
        }
    </script>
    
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
