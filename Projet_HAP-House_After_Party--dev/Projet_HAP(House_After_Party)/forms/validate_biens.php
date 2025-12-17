<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'animateur') {
    header('Location: ../auth/connexion.php');
    exit;
}
require_once '../config/db.php';
require_once '../includes/breadcrumbs.php';

// Traitement de la validation/refus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['id_biens'])) {
        $id_biens = intval($_POST['id_biens']);
        $action = $_POST['action'];
        $id_animateur = $_SESSION['user_id'];
        
        if ($action === 'validate') {
                        // Correction : forcer validated=1 (pas TRUE qui peut être mal interprété en SQL), validated_by et validated_at
                        $stmt = $pdo->prepare("UPDATE Biens SET validated = 1, validated_by = ?, validated_at = NOW() WHERE id_biens = ?");
                        $stmt->execute([$id_animateur, $id_biens]);
                        $message = "Bien validé avec succès !";
                        $messageClass = "success";
        } elseif ($action === 'reject' || $action === 'delete') {
            try {
                // Récupérer et supprimer les fichiers photos
                $stmtPhotos = $pdo->prepare("SELECT lien_photo FROM Photos WHERE id_biens = ?");
                $stmtPhotos->execute([$id_biens]);
                $photos = $stmtPhotos->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($photos as $photo) {
                    // Essayer plusieurs chemins possibles
                    $possiblePaths = [
                        __DIR__ . '/../images/uploads/' . basename($photo),
                        __DIR__ . '/../' . $photo
                    ];
                    
                    foreach ($possiblePaths as $filePath) {
                        if (file_exists($filePath)) {
                            unlink($filePath);
                            break;
                        }
                    }
                }
                
                // Supprimer d'abord les dépendances
                $pdo->prepare("DELETE FROM Compose WHERE id_biens = ?")->execute([$id_biens]);
                $pdo->prepare("DELETE FROM Tarif WHERE id_biens = ?")->execute([$id_biens]);
                $pdo->prepare("DELETE FROM Tarif_Defaut WHERE id_biens = ?")->execute([$id_biens]);
                $pdo->prepare("DELETE FROM Dispose WHERE id_biens = ?")->execute([$id_biens]);
                $pdo->prepare("DELETE FROM Reservation WHERE id_biens = ?")->execute([$id_biens]);
                $pdo->prepare("DELETE FROM Photos WHERE id_biens = ?")->execute([$id_biens]);
                $pdo->prepare("DELETE FROM Reviews WHERE id_biens = ?")->execute([$id_biens]);
                
                // Maintenant supprimer le bien
                $stmt = $pdo->prepare("DELETE FROM Biens WHERE id_biens = ?");
                $stmt->execute([$id_biens]);
                
                $message = $action === 'delete' ? "Bien supprimé avec succès." : "Bien refusé et supprimé avec toutes ses dépendances.";
                $messageClass = "success";
            } catch (PDOException $e) {
                $message = "Erreur lors de la suppression : " . $e->getMessage();
                $messageClass = "error";
            }
        }
    }
}

// Récupérer tous les biens en attente de validation
$stmt = $pdo->query("
    SELECT b.*, c.nom_commune, t.designation_type_bien, 
           b.created_by_name,
           COALESCE(l.email_locataire, 'N/A') as email_locataire,
           COALESCE(CONCAT(l.prenom_locataire, ' ', l.nom_locataire), b.created_by_name, 'N/A') as owner_name
    FROM Biens b
    LEFT JOIN Commune c ON b.id_commune = c.id_commune
    LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens
    LEFT JOIN Locataire l ON b.created_by_id = l.id_locataire
    WHERE b.validated = FALSE
    ORDER BY b.id_biens DESC
");
$biens_pending = $stmt->fetchAll();

// Récupérer les biens déjà validés (pour historique)
$stmt = $pdo->query("
    SELECT b.*, c.nom_commune, t.designation_type_bien, b.validated_at, b.validated_by, b.created_by_id,
           CONCAT(COALESCE(l.prenom_locataire, ''), ' ', COALESCE(l.nom_locataire, '')) as owner_name,
           CONCAT(COALESCE(a.prenom_animateur, ''), ' ', COALESCE(a.nom_animateur, '')) as validateur
    FROM Biens b
    LEFT JOIN Commune c ON b.id_commune = c.id_commune
    LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens
    LEFT JOIN Animateur a ON b.validated_by = a.id_animateur
    LEFT JOIN Locataire l ON b.created_by_id = l.id_locataire
    WHERE b.validated = TRUE
    ORDER BY b.validated_at DESC
    LIMIT 20
");
$biens_validated = $stmt->fetchAll();

// Debug: afficher les données pour voir ce qui manque
if (!empty($biens_validated)) {
    error_log("DEBUG - Premier bien validé: " . print_r($biens_validated[0], true));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation des Biens - HAP Admin</title>
    <link rel="stylesheet" href="../Css/dashboard.css">
    <link rel="stylesheet" href="../Css/style.css">
    <link rel="stylesheet" href="../Css/toast.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <?= getBreadcrumbStyles() ?>
    <style>
        .validation-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .bien-card {
            background: var(--dashboard-card-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        
        .bien-card.pending {
            border-color: #ffd700;
        }
        
        .bien-card.validated {
            border-color: #4caf50;
            opacity: 0.7;
        }
        
        .bien-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .bien-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .info-item {
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }
        
        .info-item strong {
            display: block;
            color: var(--dashboard-accent);
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        
        .bien-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-validate, .btn-reject {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-validate {
            background: #4caf50;
            color: white;
        }
        
        .btn-validate:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        
        .btn-reject {
            background: #f44336;
            color: white;
        }
        
        .btn-reject:hover {
            background: #da190b;
            transform: translateY(-2px);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .status-pending {
            background: #ffd700;
            color: #000;
        }
        
        .status-validated {
            background: #4caf50;
            color: white;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .tab {
            padding: 12px 24px;
            background: var(--dashboard-card-bg);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: var(--dashboard-accent);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include '../../theme_toggle.php'; ?>
    
    <div class="validation-container">
        <?php 
        renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => '../../index.php'],
            ['label' => 'Dashboard', 'url' => '../../apropos.php'],
            ['label' => 'Validation des Biens']
        ]);
        ?>
        
        <header style="margin-bottom: 30px;">
            <h1>🔍 Validation des Biens</h1>
        </header>
        
        <?php if (isset($message)): ?>
            <div class="message <?php echo $messageClass; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('pending')">
                En attente (<?php echo count($biens_pending); ?>)
            </button>
            <button class="tab" onclick="switchTab('validated')">
                Validés (<?php echo count($biens_validated); ?>)
            </button>
        </div>
        
        <!-- Biens en attente -->
        <div id="pending" class="tab-content active">
            <h2>Biens en attente de validation</h2>
            <?php if (empty($biens_pending)): ?>
                <p style="text-align: center; padding: 40px; color: var(--dashboard-text);">
                    ✅ Aucun bien en attente de validation
                </p>
            <?php else: ?>
                <?php foreach ($biens_pending as $bien): ?>
                    <div class="bien-card pending">
                        <div class="bien-header">
                            <h3><?php echo htmlspecialchars($bien['nom_biens']); ?></h3>
                            <span class="status-badge status-pending">⏳ En attente</span>
                        </div>
                        
                        <div class="bien-info">
                            <div class="info-item">
                                <strong>Type</strong>
                                <?php echo htmlspecialchars($bien['designation_type_bien'] ?? 'N/A'); ?>
                            </div>
                            <div class="info-item">
                                <strong>Propriétaire</strong>
                                <?php echo htmlspecialchars($bien['owner_name'] ?? 'N/A'); ?>
                            </div>
                            <div class="info-item">
                                <strong>Email</strong>
                                <?php echo htmlspecialchars($bien['email_locataire'] ?? 'N/A'); ?>
                            </div>
                            <div class="info-item">
                                <strong>Commune</strong>
                                <?php echo htmlspecialchars($bien['nom_commune'] ?? 'N/A'); ?>
                            </div>
                            <div class="info-item">
                                <strong>Adresse</strong>
                                <?php echo htmlspecialchars($bien['rue_biens']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Superficie</strong>
                                <?php echo htmlspecialchars($bien['superficie_biens']); ?> m²
                            </div>
                            <div class="info-item">
                                <strong>Couchages</strong>
                                <?php echo htmlspecialchars($bien['nb_couchage']); ?>
                            </div>
                            <div class="info-item">
                                <strong>Animaux</strong>
                                <?php echo $bien['animal_biens'] ? '✅ Autorisés' : '❌ Non autorisés'; ?>
                            </div>
                        </div>
                        
                        <div class="info-item" style="grid-column: 1 / -1;">
                            <strong>Description</strong>
                            <?php echo nl2br(htmlspecialchars($bien['description_biens'])); ?>
                        </div>
                        
                        <div class="bien-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id_biens" value="<?php echo $bien['id_biens']; ?>">
                                <input type="hidden" name="action" value="validate">
                                <button type="submit" class="btn-validate" onclick="return confirm('Valider ce bien ?');">
                                    ✅ Valider
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id_biens" value="<?php echo $bien['id_biens']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn-reject" onclick="return confirm('Refuser et supprimer ce bien définitivement ?');">
                                    ❌ Refuser
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Biens validés -->
        <div id="validated" class="tab-content">
            <h2>Biens récemment validés (20 derniers)</h2>
            <?php if (empty($biens_validated)): ?>
                <p style="text-align: center; padding: 40px;">Aucun bien validé pour le moment</p>
            <?php else: ?>
                <?php foreach ($biens_validated as $bien): ?>
                    <div class="bien-card validated">
                        <div class="bien-header">
                            <h3><?php echo htmlspecialchars($bien['nom_biens']); ?></h3>
                            <span class="status-badge status-validated">✅ Validé</span>
                        </div>
                        
                        <div class="bien-info">
                            <div class="info-item">
                                <strong>Type</strong>
                                <?php echo htmlspecialchars($bien['designation_type_bien'] ?? 'N/A'); ?>
                            </div>
                            <div class="info-item">
                                <strong>Propriétaire</strong>
                                <?php 
                                    $owner = trim($bien['owner_name'] ?? '');
                                    echo htmlspecialchars(!empty($owner) ? $owner : ($bien['created_by_name'] ?? 'N/A')); 
                                ?>
                            </div>
                            <div class="info-item">
                                <strong>Commune</strong>
                                <?php echo htmlspecialchars($bien['nom_commune'] ?? 'N/A'); ?>
                            </div>
                            <div class="info-item">
                                <strong>Validé par</strong>
                                <?php 
                                    $validateur = trim($bien['validateur'] ?? '');
                                    echo htmlspecialchars(!empty($validateur) ? $validateur : 'N/A'); 
                                ?>
                            </div>
                            <div class="info-item">
                                <strong>Date de validation</strong>
                                <?php echo $bien['validated_at'] ? date('d/m/Y H:i', strtotime($bien['validated_at'])) : 'N/A'; ?>
                            </div>
                            <!-- DEBUG -->
                            <div class="info-item" style="font-size: 0.8em; opacity: 0.6;">
                                <strong>Debug</strong>
                                validated_by: <?= $bien['validated_by'] ?? 'NULL' ?> | 
                                created_by_id: <?= $bien['created_by_id'] ?? 'NULL' ?>
                            </div>
                        </div>
                        
                        <div class="bien-actions" style="margin-top: 15px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id_biens" value="<?php echo $bien['id_biens']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn-reject" onclick="return confirm('Supprimer définitivement ce bien ?');"
                                    style="background: #d32f2f; padding: 8px 16px; font-size: 0.9em;">
                                    🗑️ Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Cacher tous les contenus
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activer l'onglet et le contenu sélectionné
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
    <script src="../js/toast.js"></script>
</body>
</html>
