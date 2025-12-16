<?php
session_start();
require_once __DIR__ . '/Projet_HAP(House_After_Party)/config/db.php';

$success = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $priorite = $_POST['priorite'] ?? 'normale';
    $page_concernee = trim($_POST['page_concernee'] ?? '');
    $id_locataire = $_SESSION['user_id'] ?? null;
    
    // Validation
    if (empty($type) || empty($sujet) || empty($message)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!$id_locataire && (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        $error = "Veuillez fournir une adresse email valide.";
    } else {
        try {
            // Vérifier si la table existe, sinon la créer
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
            
            // Insérer le message
            $sql = "INSERT INTO Contact_Messages (type_message, sujet, message, email, nom, priorite, page_concernee, id_locataire) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            // Si l'utilisateur est connecté, utiliser ses infos
            if ($id_locataire) {
                $stmtUser = $pdo->prepare("SELECT email_locataire, nom_locataire, prenom_locataire FROM Locataire WHERE id_locataire = ?");
                $stmtUser->execute([$id_locataire]);
                $user = $stmtUser->fetch();
                if ($user) {
                    $email = $email ?: $user['email_locataire'];
                    $nom = $nom ?: ($user['prenom_locataire'] . ' ' . $user['nom_locataire']);
                }
            }
            
            $stmt->execute([$type, $sujet, $message, $email, $nom, $priorite, $page_concernee, $id_locataire]);
            
            $ticketId = $pdo->lastInsertId();
            $success = "Votre message a été envoyé avec succès ! Numéro de ticket : #" . str_pad($ticketId, 6, '0', STR_PAD_LEFT);
            
            // Reset form
            $_POST = [];
            
        } catch (PDOException $e) {
            $error = "Erreur lors de l'envoi du message. Veuillez réessayer.";
        }
    }
}

// Récupérer les tickets de l'utilisateur connecté
$mesTickets = [];
if (isset($_SESSION['user_id'])) {
    $stmtTickets = $pdo->prepare("SELECT * FROM Contact_Messages WHERE id_locataire = ? ORDER BY date_creation DESC LIMIT 5");
    $stmtTickets->execute([$_SESSION['user_id']]);
    $mesTickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact & Support - House After Party</title>
    <link rel="stylesheet" href="Projet_HAP(House_After_Party)/Css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .contact-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding-top: 80px;
        }
        
        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .contact-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #a100b8, #667eea);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }
        
        .contact-header p {
            color: rgba(255,255,255,0.7);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        @media (max-width: 968px) {
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .contact-form-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 35px;
            border: 1px solid rgba(161, 0, 184, 0.2);
        }
        
        .contact-form-card h2 {
            color: #fff;
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.9);
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group label .required {
            color: #ef4444;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #a100b8;
            background: rgba(161, 0, 184, 0.1);
            box-shadow: 0 0 20px rgba(161, 0, 184, 0.2);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255,255,255,0.4);
        }
        
        .form-group select {
            cursor: pointer;
        }
        
        .form-group select option {
            background: #1a1a2e;
            color: #fff;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* Type selection cards */
        .type-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .type-option {
            position: relative;
        }
        
        .type-option input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .type-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 10px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .type-option label:hover {
            border-color: rgba(161, 0, 184, 0.5);
            background: rgba(161, 0, 184, 0.1);
        }
        
        .type-option input:checked + label {
            border-color: #a100b8;
            background: rgba(161, 0, 184, 0.2);
            box-shadow: 0 0 20px rgba(161, 0, 184, 0.3);
        }
        
        .type-option .type-icon {
            font-size: 1.8rem;
            margin-bottom: 8px;
        }
        
        .type-option .type-label {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.8);
        }
        
        /* Priority badges */
        .priority-selector {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .priority-option {
            position: relative;
        }
        
        .priority-option input {
            position: absolute;
            opacity: 0;
        }
        
        .priority-option label {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            border: 2px solid rgba(255,255,255,0.1);
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .priority-option.basse label { border-color: rgba(34, 197, 94, 0.3); }
        .priority-option.normale label { border-color: rgba(59, 130, 246, 0.3); }
        .priority-option.haute label { border-color: rgba(249, 115, 22, 0.3); }
        .priority-option.urgente label { border-color: rgba(239, 68, 68, 0.3); }
        
        .priority-option input:checked + label {
            color: #fff;
        }
        
        .priority-option.basse input:checked + label { background: #22c55e; border-color: #22c55e; }
        .priority-option.normale input:checked + label { background: #3b82f6; border-color: #3b82f6; }
        .priority-option.haute input:checked + label { background: #f97316; border-color: #f97316; }
        .priority-option.urgente input:checked + label { background: #ef4444; border-color: #ef4444; }
        
        .btn-submit {
            width: 100%;
            padding: 16px 30px;
            background: linear-gradient(135deg, #a100b8, #667eea);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(161, 0, 184, 0.4);
        }
        
        /* Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
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
        
        /* Info cards */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .info-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(161, 0, 184, 0.2);
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            border-color: #a100b8;
            transform: translateY(-3px);
        }
        
        .info-card h3 {
            color: #fff;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }
        
        .info-card p {
            color: rgba(255,255,255,0.7);
            line-height: 1.6;
            margin: 0;
        }
        
        .info-card ul {
            color: rgba(255,255,255,0.7);
            padding-left: 20px;
            margin: 10px 0 0 0;
        }
        
        .info-card li {
            margin-bottom: 8px;
        }
        
        /* FAQ Section */
        .faq-card {
            background: linear-gradient(135deg, rgba(161, 0, 184, 0.1), rgba(102, 126, 234, 0.1));
        }
        
        .faq-item {
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .faq-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .faq-question {
            color: #fff;
            font-weight: 600;
            margin-bottom: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-answer {
            color: rgba(255,255,255,0.7);
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        /* Tickets section */
        .tickets-card {
            margin-top: 30px;
        }
        
        .ticket-item {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #a100b8;
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .ticket-id {
            color: #a100b8;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .ticket-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .ticket-status.nouveau { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .ticket-status.en_cours { background: rgba(249, 115, 22, 0.2); color: #f97316; }
        .ticket-status.resolu { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .ticket-status.ferme { background: rgba(107, 114, 128, 0.2); color: #6b7280; }
        
        .ticket-sujet {
            color: #fff;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .ticket-date {
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
        }
        
        .ticket-reponse {
            margin-top: 10px;
            padding: 10px;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 8px;
            border-left: 3px solid #22c55e;
        }
        
        .ticket-reponse-label {
            color: #22c55e;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .ticket-reponse-text {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        
        /* Contact methods */
        .contact-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .contact-method {
            text-align: center;
            padding: 20px 15px;
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .contact-method:hover {
            background: rgba(161, 0, 184, 0.1);
        }
        
        .contact-method-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .contact-method-label {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
            display: block;
            margin-bottom: 5px;
        }
        
        .contact-method-value {
            color: #fff;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* Light theme */
        [data-theme="light"] .contact-page {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 50%, #dee2e6 100%);
        }
        
        [data-theme="light"] .contact-form-card,
        [data-theme="light"] .info-card {
            background: rgba(255,255,255,0.95);
            border-color: rgba(161, 0, 184, 0.15);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        [data-theme="light"] .contact-header h1 {
            -webkit-text-fill-color: #a100b8;
        }
        
        [data-theme="light"] .contact-header p,
        [data-theme="light"] .form-group label,
        [data-theme="light"] .info-card p,
        [data-theme="light"] .info-card li,
        [data-theme="light"] .faq-answer {
            color: #475569;
        }
        
        [data-theme="light"] .contact-form-card h2,
        [data-theme="light"] .info-card h3,
        [data-theme="light"] .faq-question,
        [data-theme="light"] .ticket-sujet {
            color: #1e293b;
        }
        
        [data-theme="light"] .form-group input,
        [data-theme="light"] .form-group select,
        [data-theme="light"] .form-group textarea {
            background: #f8f9fa;
            border-color: #e2e8f0;
            color: #1e293b;
        }
        
        [data-theme="light"] .form-group select option {
            background: #fff;
            color: #1e293b;
        }
        
        [data-theme="light"] .type-option label,
        [data-theme="light"] .priority-option label {
            border-color: #e2e8f0;
            color: #475569;
        }
        
        [data-theme="light"] .type-option .type-label {
            color: #1e293b;
        }
        
        [data-theme="light"] .ticket-item {
            background: #f8f9fa;
        }
        
        [data-theme="light"] .ticket-date {
            color: #64748b;
        }
        
        [data-theme="light"] .ticket-reponse {
            background: rgba(34, 197, 94, 0.15);
        }
        
        [data-theme="light"] .ticket-reponse-text {
            color: #1e293b;
        }
        
        [data-theme="light"] .contact-method {
            background: #f8f9fa;
        }
        
        [data-theme="light"] .contact-method-value {
            color: #1e293b;
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo">
            <span class="logo-icon">🎵</span> HAP
        </a>
        <nav>
            <a href="index.php">🏠 Accueil</a>
            <a href="Projet_HAP(House_After_Party)/forms/Annonce.form.php">📅 Annonces</a>
            <a href="Projet_HAP(House_After_Party)/map.php">🗺️ Carte</a>
            <a href="Projet_HAP(House_After_Party)/forms/PtsInteret.form.php">🎵 Point d'Intérêt</a>
            <a href="Projet_HAP(House_After_Party)/forms/blog.php">📝 Blog</a>
            <a href="contact.php" class="active">📞 Contact</a>
        </nav>
        <?php
        if (isset($_SESSION['user_name'])) {
            echo '<span class="welcome-msg">Bienvenue, ' . htmlspecialchars($_SESSION['user_name']) . ' !</span>';
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'animateur') {
                echo '<a href="apropos.php" class="btn-admin">🛠️ Dashboard</a>';
            }
            echo '<a href="Projet_HAP(House_After_Party)/auth/logout.php" class="btn-logout">Se déconnecter</a>';
        } else {
            echo '<a href="Projet_HAP(House_After_Party)/auth/connexion.php" class="btn-login">Se connecter</a>';
        }
        ?>
    </header>
    
    <div class="contact-page">
        <div class="contact-container">
            <div class="contact-header">
                <h1>📞 Contactez-nous</h1>
                <p>Une question, un problème ou une suggestion ? Notre équipe est là pour vous aider 24h/24, 7j/7.</p>
            </div>
            
            <div class="contact-grid">
                <!-- Formulaire de contact -->
                <div class="contact-form-card">
                    <h2>✉️ Envoyer un message</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <span>✅</span> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <span>❌</span> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <!-- Type de message -->
                        <div class="form-group">
                            <label>Type de demande <span class="required">*</span></label>
                            <div class="type-selector">
                                <div class="type-option">
                                    <input type="radio" name="type" id="type-question" value="question" <?= ($_POST['type'] ?? '') === 'question' ? 'checked' : '' ?>>
                                    <label for="type-question">
                                        <span class="type-icon">❓</span>
                                        <span class="type-label">Question</span>
                                    </label>
                                </div>
                                <div class="type-option">
                                    <input type="radio" name="type" id="type-signalement" value="signalement" <?= ($_POST['type'] ?? '') === 'signalement' ? 'checked' : '' ?>>
                                    <label for="type-signalement">
                                        <span class="type-icon">🚨</span>
                                        <span class="type-label">Signalement</span>
                                    </label>
                                </div>
                                <div class="type-option">
                                    <input type="radio" name="type" id="type-erreur" value="erreur" <?= ($_POST['type'] ?? '') === 'erreur' ? 'checked' : '' ?>>
                                    <label for="type-erreur">
                                        <span class="type-icon">🐛</span>
                                        <span class="type-label">Bug/Erreur</span>
                                    </label>
                                </div>
                                <div class="type-option">
                                    <input type="radio" name="type" id="type-suggestion" value="suggestion" <?= ($_POST['type'] ?? '') === 'suggestion' ? 'checked' : '' ?>>
                                    <label for="type-suggestion">
                                        <span class="type-icon">💡</span>
                                        <span class="type-label">Suggestion</span>
                                    </label>
                                </div>
                                <div class="type-option">
                                    <input type="radio" name="type" id="type-autre" value="autre" <?= ($_POST['type'] ?? '') === 'autre' ? 'checked' : '' ?>>
                                    <label for="type-autre">
                                        <span class="type-icon">📝</span>
                                        <span class="type-label">Autre</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informations personnelles (si non connecté) -->
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nom">Votre nom</label>
                                <input type="text" id="nom" name="nom" placeholder="Jean Dupont" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" placeholder="jean@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="email" value="">
                        <input type="hidden" name="nom" value="">
                        <?php endif; ?>
                        
                        <!-- Sujet -->
                        <div class="form-group">
                            <label for="sujet">Sujet <span class="required">*</span></label>
                            <input type="text" id="sujet" name="sujet" placeholder="Résumez votre demande en quelques mots" value="<?= htmlspecialchars($_POST['sujet'] ?? '') ?>" required>
                        </div>
                        
                        <!-- Page concernée (pour les bugs) -->
                        <div class="form-group" id="page-group" style="display: none;">
                            <label for="page_concernee">Page concernée</label>
                            <input type="text" id="page_concernee" name="page_concernee" placeholder="URL ou nom de la page où vous avez rencontré le problème" value="<?= htmlspecialchars($_POST['page_concernee'] ?? '') ?>">
                        </div>
                        
                        <!-- Message -->
                        <div class="form-group">
                            <label for="message">Message <span class="required">*</span></label>
                            <textarea id="message" name="message" placeholder="Décrivez votre demande en détail. Pour un bug, précisez les étapes pour le reproduire." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Priorité -->
                        <div class="form-group">
                            <label>Priorité</label>
                            <div class="priority-selector">
                                <div class="priority-option basse">
                                    <input type="radio" name="priorite" id="prio-basse" value="basse" <?= ($_POST['priorite'] ?? 'normale') === 'basse' ? 'checked' : '' ?>>
                                    <label for="prio-basse">🟢 Basse</label>
                                </div>
                                <div class="priority-option normale">
                                    <input type="radio" name="priorite" id="prio-normale" value="normale" <?= ($_POST['priorite'] ?? 'normale') === 'normale' ? 'checked' : '' ?>>
                                    <label for="prio-normale">🔵 Normale</label>
                                </div>
                                <div class="priority-option haute">
                                    <input type="radio" name="priorite" id="prio-haute" value="haute" <?= ($_POST['priorite'] ?? 'normale') === 'haute' ? 'checked' : '' ?>>
                                    <label for="prio-haute">🟠 Haute</label>
                                </div>
                                <div class="priority-option urgente">
                                    <input type="radio" name="priorite" id="prio-urgente" value="urgente" <?= ($_POST['priorite'] ?? 'normale') === 'urgente' ? 'checked' : '' ?>>
                                    <label for="prio-urgente">🔴 Urgente</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <span>📤</span> Envoyer le message
                        </button>
                    </form>
                </div>
                
                <!-- Informations et FAQ -->
                <div class="contact-info">
                    <!-- Coordonnées -->
                    <div class="info-card">
                        <h3>📍 Nos coordonnées</h3>
                        <p>Notre équipe support est disponible pour répondre à toutes vos questions.</p>
                        <div class="contact-methods">
                            <div class="contact-method">
                                <div class="contact-method-icon">📧</div>
                                <span class="contact-method-label">Email</span>
                                <span class="contact-method-value">contact@hap.fr</span>
                            </div>
                            <div class="contact-method">
                                <div class="contact-method-icon">📱</div>
                                <span class="contact-method-label">Téléphone</span>
                                <span class="contact-method-value">01 23 45 67 89</span>
                            </div>
                            <div class="contact-method">
                                <div class="contact-method-icon">💬</div>
                                <span class="contact-method-label">Chat</span>
                                <span class="contact-method-value">24h/24</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Délais de réponse -->
                    <div class="info-card">
                        <h3>⏱️ Délais de réponse</h3>
                        <ul>
                            <li><strong>🔴 Urgente :</strong> Sous 2 heures</li>
                            <li><strong>🟠 Haute :</strong> Sous 12 heures</li>
                            <li><strong>🔵 Normale :</strong> Sous 24-48 heures</li>
                            <li><strong>🟢 Basse :</strong> Sous 72 heures</li>
                        </ul>
                    </div>
                    
                    <!-- FAQ -->
                    <div class="info-card faq-card">
                        <h3>❓ Questions fréquentes</h3>
                        
                        <div class="faq-item">
                            <div class="faq-question">Comment annuler une réservation ?</div>
                            <div class="faq-answer">Rendez-vous dans votre profil, section "Mes réservations", puis cliquez sur "Annuler". Les conditions d'annulation varient selon le logement.</div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">Comment signaler un problème avec un logement ?</div>
                            <div class="faq-answer">Utilisez le formulaire ci-contre en sélectionnant "Signalement" et décrivez le problème en détail. Joignez des photos si possible.</div>
                        </div>
                        
                        <div class="faq-item">
                            <div class="faq-question">Puis-je modifier ma réservation ?</div>
                            <div class="faq-answer">Oui, contactez-nous via ce formulaire avec votre numéro de réservation et les modifications souhaitées.</div>
                        </div>
                    </div>
                    
                    <!-- Mes tickets (si connecté) -->
                    <?php if (!empty($mesTickets)): ?>
                    <div class="info-card tickets-card">
                        <h3>🎫 Mes derniers tickets</h3>
                        <?php foreach ($mesTickets as $ticket): ?>
                        <div class="ticket-item">
                            <div class="ticket-header">
                                <span class="ticket-id">#<?= str_pad($ticket['id_message'], 6, '0', STR_PAD_LEFT) ?></span>
                                <span class="ticket-status <?= $ticket['statut'] ?>"><?= ucfirst(str_replace('_', ' ', $ticket['statut'])) ?></span>
                            </div>
                            <div class="ticket-sujet"><?= htmlspecialchars($ticket['sujet']) ?></div>
                            <div class="ticket-date"><?= date('d/m/Y à H:i', strtotime($ticket['date_creation'])) ?></div>
                            <?php if ($ticket['reponse_admin']): ?>
                            <div class="ticket-reponse">
                                <div class="ticket-reponse-label">Réponse de l'équipe :</div>
                                <div class="ticket-reponse-text"><?= nl2br(htmlspecialchars($ticket['reponse_admin'])) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <div style="display: flex; justify-content: center; gap: 40px; margin-bottom: 20px; flex-wrap: wrap;">
            <a href="about.php" style="color: #666; text-decoration: none;">À propos</a>
            <a href="Projet_HAP(House_After_Party)/forms/Annonce.form.php" style="color: #666; text-decoration: none;">Annonces</a>
            <a href="contact.php" style="color: #666; text-decoration: none;">Contact</a>
        </div>
        &copy; <?= date('Y') ?> House After Party &mdash; Tous droits réservés.<br>
        <small style="color: #999;">Fait avec ❤️ pour les amoureux des nuits blanches</small>
    </footer>
    
    <?php include 'theme_toggle.php'; ?>
    
    <script>
    // Afficher/masquer le champ "page concernée" selon le type
    document.querySelectorAll('input[name="type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const pageGroup = document.getElementById('page-group');
            if (this.value === 'erreur' || this.value === 'signalement') {
                pageGroup.style.display = 'block';
            } else {
                pageGroup.style.display = 'none';
            }
        });
    });
    
    // Vérifier au chargement
    const checkedType = document.querySelector('input[name="type"]:checked');
    if (checkedType && (checkedType.value === 'erreur' || checkedType.value === 'signalement')) {
        document.getElementById('page-group').style.display = 'block';
    }
    </script>
</body>
</html>
