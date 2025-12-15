<?php
/**
 * EXEMPLE D'INTÉGRATION COMPLÈTE
 * Démontre l'utilisation de tous les systèmes UX
 */
session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumbs.php';

$message = '';
$messageClass = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation côté serveur (toujours nécessaire !)
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email invalide";
        $messageClass = "error";
    } else {
        $message = "Inscription réussie !";
        $messageClass = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exemple d'intégration UX - HAP</title>
    
    <!-- CSS de base -->
    <link rel="stylesheet" href="../Css/forms.css">
    
    <!-- Nouveaux systèmes UX -->
    <link rel="stylesheet" href="../Css/toast.css">
    <link rel="stylesheet" href="../Css/validation.css">
    
    <!-- Breadcrumbs styles -->
    <?= getBreadcrumbStyles() ?>
    
    <style>
        body {
            background: var(--bg-color, #f5f5f5);
            padding: 20px;
        }
        
        .example-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .info-box {
            background: #e0e7ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .demo-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        
        .demo-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .demo-btn:hover {
            transform: translateY(-2px);
        }
        
        .demo-btn.success { background: #10b981; color: white; }
        .demo-btn.error { background: #ef4444; color: white; }
        .demo-btn.warning { background: #f59e0b; color: white; }
        .demo-btn.info { background: #3b82f6; color: white; }
    </style>
</head>
<body>
    <?php include '../../theme_toggle.php'; ?>
    
    <div class="example-container">
        <!-- 1. BREADCRUMBS -->
        <?php 
        renderBreadcrumbs([
            ['label' => 'Accueil', 'url' => '../../index.php'],
            ['label' => 'Dashboard', 'url' => '../../apropos.php'],
            ['label' => 'Exemple d\'intégration']
        ]);
        ?>
        
        <h1>🎨 Démo des fonctionnalités UX</h1>
        
        <!-- 2. NOTIFICATIONS TOAST -->
        <div class="info-box">
            <h3>📢 Notifications Toast</h3>
            <p>Cliquez sur les boutons pour tester :</p>
            <div class="demo-buttons">
                <button class="demo-btn success" onclick="showToast('Opération réussie !', 'success')">
                    ✓ Succès
                </button>
                <button class="demo-btn error" onclick="showToast('Une erreur est survenue', 'error')">
                    ✗ Erreur
                </button>
                <button class="demo-btn warning" onclick="showToast('Attention à cette action', 'warning')">
                    ⚠ Avertissement
                </button>
                <button class="demo-btn info" onclick="showToast('Information importante', 'info')">
                    ℹ Information
                </button>
            </div>
        </div>
        
        <!-- Message PHP (sera automatiquement converti en toast) -->
        <?php if ($message): ?>
            <div class="message <?= $messageClass ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- 3. VALIDATION EN TEMPS RÉEL -->
        <div class="info-box">
            <h3>✓ Validation en temps réel</h3>
            <p>Remplissez les champs pour voir la validation instantanée :</p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    data-validate="required,email"
                    placeholder="exemple@email.com"
                >
            </div>
            
            <div class="form-group">
                <label for="phone">Téléphone</label>
                <input 
                    type="tel" 
                    id="phone" 
                    name="phone" 
                    data-validate="phone"
                    placeholder="06 12 34 56 78"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    data-validate="required,password"
                    placeholder="Min 8 caractères"
                >
                <small>Min 8 caractères avec majuscule, minuscule, chiffre et caractère spécial</small>
            </div>
            
            <div class="form-group">
                <label for="birthdate">Date de naissance *</label>
                <input 
                    type="date" 
                    id="birthdate" 
                    name="birthdate" 
                    data-validate="required,age18"
                >
            </div>
            
            <div class="form-group">
                <label for="postal">Code postal</label>
                <input 
                    type="text" 
                    id="postal" 
                    name="postal" 
                    data-validate="postalCode"
                    placeholder="75001"
                >
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                Soumettre le formulaire
            </button>
        </form>
        
        <div class="info-box" style="margin-top: 30px;">
            <h3>💡 Conseils d'utilisation</h3>
            <ul>
                <li><strong>Breadcrumbs :</strong> Ajoutez <code>renderBreadcrumbs()</code> en haut de chaque page</li>
                <li><strong>Toasts :</strong> Incluez <code>toast.js</code> et <code>toast.css</code></li>
                <li><strong>Validation :</strong> Ajoutez <code>data-validate</code> sur les inputs</li>
                <li><strong>Combinaison :</strong> Utilisez plusieurs validateurs : <code>data-validate="required,email"</code></li>
            </ul>
        </div>
    </div>
    
    <!-- Scripts des nouveaux systèmes -->
    <script src="../js/toast.js"></script>
    <script src="../js/validation.js"></script>
</body>
</html>
