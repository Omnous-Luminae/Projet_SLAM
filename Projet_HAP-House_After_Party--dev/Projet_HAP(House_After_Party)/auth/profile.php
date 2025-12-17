<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$pdo = $pdo ?? null;
$message = '';
$messageType = 'success';
$userId = intval($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';

try {
    // Mise à jour du profil utilisateur
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        $email = trim($_POST['email'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $tel = trim($_POST['tel'] ?? '');
        $date_naissance = trim($_POST['date_naissance'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $complement = trim($_POST['complement'] ?? '');
        $commune = trim($_POST['commune'] ?? '');
        // RGPD must be checked
        if (!isset($_POST['rgpd'])) {
            $message = 'Vous devez accepter la politique de confidentialité.';
            $messageType = 'error';
        } else if (empty($email) || empty($nom) || empty($prenom) || empty($tel) || empty($date_naissance) || empty($adresse) || empty($commune)) {
            $message = 'Merci de remplir tous les champs obligatoires.';
            $messageType = 'error';
        } else {
            $locClass = new Locataire(null, null, null, null, null, null, null, null, null, $pdo);
            // Met à jour tous les champs du profil, sauf pseudo
            $locClass->updateLocataire($userId, $nom, $prenom, null, $email, $tel, $date_naissance, null, $adresse, $complement, null, null, null);
            // Met à jour la commune si besoin (à adapter selon ta structure)
            $stmt = $pdo->prepare('UPDATE Locataire SET commune = ? WHERE id_locataire = ?');
            $stmt->execute([$commune, $userId]);
            $message = 'Profil mis à jour avec succès.';
            // Rafraîchir les données utilisateur
            $userData = $locClass->getLocataireById($userId);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
        $id_reservation = intval($_POST['id_reservation'] ?? 0);
        if ($id_reservation > 0) {
            $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_reservation = ? AND id_locataire = ?');
            $stmt->execute([$id_reservation, $userId]);
            $message = 'Réservation annulée avec succès.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current = trim($_POST['current_password'] ?? '');
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        if ($new !== $confirm) {
            $message = 'Le nouveau mot de passe et la confirmation ne correspondent pas.';
            $messageType = 'error';
        } else {
            // Verify current password using same logic as authenticateLocataire
            $locClass = new Locataire(null, null, null, null, null, null, null, null, null, $pdo);
            $user = $locClass->getLocataireById($userId);
            $authenticated = false;
            if ($user) {
                $stored_hash = $user['password_locataire'];
                if (password_verify($current, $stored_hash)) {
                    $authenticated = true;
                } elseif (substr($stored_hash, 0, 4) === '$2y$' && strlen($stored_hash) < 60) {
                    // Truncated bcrypt hash, treat as plain text
                    $authenticated = true;
                } elseif (strlen($stored_hash) < 60 && $current === $stored_hash) {
                    // Plain text
                    $authenticated = true;
                } elseif (md5($current) === $stored_hash) {
                    // MD5 hash
                    $authenticated = true;
                }
            }
            if ($authenticated) {
                $locClass->updateLocataire($userId, null, null, null, null, null, $new, null, null, null);
                $message = 'Mot de passe mis à jour.';
            } else {
                $message = 'Mot de passe actuel incorrect.';
                $messageType = 'error';
            }
        }
    }

    // Fetch user's annonces (by created_by_id or created_by_name fallback)
    $stmt = $pdo->prepare('SELECT * FROM Biens WHERE (created_by_id = ? OR created_by_name = ?) ORDER BY id_biens DESC');
    $stmt->execute([$userId, $userName]);
    $userBiens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's reservations (only those made by this user)
    $reservationsStmt = $pdo->prepare('SELECT r.*, b.nom_biens, t.tarif, t.id_Tarif FROM Reservation r LEFT JOIN Biens b ON r.id_biens = b.id_biens LEFT JOIN Tarif t ON r.id_Tarif = t.id_Tarif WHERE r.id_locataire = ? ORDER BY r.date_debut_reservation DESC');
    $reservationsStmt->execute([$userId]);
    $userReservations = $reservationsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's reviews (comments)
    $reviewsStmt = $pdo->prepare('SELECT r.*, b.nom_biens FROM Reviews r LEFT JOIN Biens b ON r.id_biens = b.id_biens WHERE r.id_locataire = ? ORDER BY r.created_at DESC');
    $reviewsStmt->execute([$userId]);
    $userReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's favorites
    $userFavoris = [];
    $debugFavorisRows = [];
    $debugBienRow = [];
    $favorisQuery = "
         SELECT b.*, f.date_ajout as date_favori,
             c.nom_commune,
             tb.designation_type_bien as type_bien,
               (SELECT lien_photo FROM Photos WHERE id_biens = b.id_biens LIMIT 1) as photo,
               (SELECT AVG(rating) FROM reviews WHERE id_biens = b.id_biens) as note_moyenne,
               (SELECT COUNT(*) FROM reviews WHERE id_biens = b.id_biens) as nb_avis
        FROM Favoris f
        JOIN Biens b ON f.id_biens = b.id_biens
        LEFT JOIN Commune c ON b.id_commune = c.id_commune
        LEFT JOIN Type_Bien tb ON b.id_type_biens = tb.id_type_biens
        WHERE f.id_locataire = ? AND b.validated = 1
        ORDER BY f.date_ajout DESC
        LIMIT 6
    ";
    $favorisStmt = $pdo->prepare($favorisQuery);
    $ok = $favorisStmt->execute([$userId]);
    $userFavoris = $favorisStmt->fetchAll(PDO::FETCH_ASSOC);
    $favorisError = $ok ? null : $favorisStmt->errorInfo();
    // Debug Favoris bruts et Bien favori
    try {
        $debugFavoris = $pdo->prepare("SELECT * FROM Favoris WHERE id_locataire = ? ORDER BY date_ajout DESC LIMIT 6");
        $debugFavoris->execute([$userId]);
        $debugFavorisRows = $debugFavoris->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($debugFavorisRows[0]['id_biens'])) {
            $debugBien = $pdo->prepare("SELECT * FROM Biens WHERE id_biens = ?");
            $debugBien->execute([$debugFavorisRows[0]['id_biens']]);
            $debugBienRow = $debugBien->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {}

    // Fetch user data for profile form
    $locClass = new Locataire(null, null, null, null, null, null, null, null, null, null, $pdo);
    if (!$pdo) {
        require_once __DIR__ . '/../config/db.php';
    }
    $userData = $locClass->getLocataireById($userId);

} catch (Exception $e) {
    $message = 'Erreur: ' . $e->getMessage();
    $messageType = 'error';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - House After Party</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/style.css">
    <link rel="stylesheet" href="../Css/profile.css">
    <style>
    * { box-sizing: border-box; }
    
    :root {
        --primary: #667eea;
        --primary-dark: #5a67d8;
        --secondary: #764ba2;
        --accent: #a100b8;
        --bg-primary: #f8fafc;
        --bg-card: #ffffff;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --success: #10b981;
        --error: #ef4444;
        --warning: #f59e0b;
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
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }
    
    .profile-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }
    
    .profile-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        color: white;
        display: flex;
        align-items: center;
        gap: 30px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3em;
        border: 4px solid rgba(255,255,255,0.3);
        flex-shrink: 0;
    }
    
    .profile-info h1 {
        margin: 0 0 10px 0;
        font-size: 2em;
        font-weight: 700;
    }
    
    .profile-info p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1em;
    }
    
    .profile-stats {
        display: flex;
        gap: 30px;
        margin-top: 20px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-number {
        font-size: 1.8em;
        font-weight: 700;
        display: block;
    }
    
    .stat-label {
        font-size: 0.85em;
        opacity: 0.8;
    }
    
    .back-link {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        text-decoration: none;
        background: rgba(255,255,255,0.2);
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s;
        backdrop-filter: blur(5px);
    }
    
    .back-link:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
    }
    
    .profile-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        flex-wrap: wrap;
    }
    
    .tab-btn {
        padding: 12px 24px;
        border: none;
        background: var(--bg-card);
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        color: var(--text-secondary);
        border: 1px solid var(--border-color);
        font-family: inherit;
    }
    
    .tab-btn:hover, .tab-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-color: transparent;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
        animation: fadeIn 0.4s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .profile-section {
        background: var(--bg-card);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 25px;
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
    }
    
    .profile-section h3 {
        color: var(--text-primary);
        margin: 0 0 20px 0;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.3em;
    }
    
    .message {
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .message.success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .message.error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
        color: var(--error);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    /* Favoris Grid */
    .favoris-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .favori-card {
        background: var(--bg-card);
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        transition: all 0.3s;
        position: relative;
    }
    
    .favori-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    }
    
    .favori-card .image-container {
        height: 160px;
        position: relative;
        overflow: hidden;
    }
    
    .favori-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .favori-card:hover img {
        transform: scale(1.05);
    }
    
    .favori-card .no-image {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5em;
    }
    
    .favori-card .badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.75em;
        font-weight: 600;
    }
    
    .favori-card .heart-icon {
        position: absolute;
        top: 12px;
        right: 12px;
        color: #ef4444;
        font-size: 1.5em;
    }
    
    .favori-card .content {
        padding: 15px;
    }
    
    .favori-card .title {
        font-weight: 700;
        margin-bottom: 5px;
        font-size: 1em;
    }
    
    .favori-card .title a {
        color: var(--text-primary);
        text-decoration: none;
    }
    
    .favori-card .title a:hover {
        color: var(--primary);
    }
    
    .favori-card .location {
        color: var(--text-secondary);
        font-size: 0.85em;
        margin-bottom: 8px;
    }
    
    .favori-card .rating {
        color: #fbbf24;
        font-size: 0.9em;
    }
    
    .view-all-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        margin-top: 20px;
        padding: 10px 20px;
        border: 2px solid var(--primary);
        border-radius: 25px;
        transition: all 0.3s;
    }
    
    .view-all-link:hover {
        background: var(--primary);
        color: white;
    }
    
    /* Tables modernes */
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 15px;
    }
    
    .modern-table thead th {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9em;
    }
    
    .modern-table thead th:first-child {
        border-radius: 10px 0 0 0;
    }
    
    .modern-table thead th:last-child {
        border-radius: 0 10px 0 0;
    }
    
    .modern-table tbody tr {
        transition: all 0.3s;
    }
    
    .modern-table tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
    }
    
    .modern-table tbody td {
        padding: 15px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .modern-table tbody td a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }
    
    .modern-table tbody td a:hover {
        text-decoration: underline;
    }
    
    /* Annonces cards */
    .annonces-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .annonce-card {
        background: var(--bg-card);
        border-radius: 14px;
        padding: 20px;
        border: 1px solid var(--border-color);
        transition: all 0.3s;
    }
    
    .annonce-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border-color: var(--primary);
    }
    
    .annonce-card .annonce-title {
        font-weight: 700;
        font-size: 1.1em;
        margin-bottom: 10px;
    }
    
    .annonce-card .annonce-title a {
        color: var(--text-primary);
        text-decoration: none;
    }
    
    .annonce-card .annonce-title a:hover {
        color: var(--primary);
    }
    
    .annonce-card .annonce-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .annonce-card .annonce-actions a,
    .annonce-card .annonce-actions button {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 0.9em;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
        font-family: inherit;
    }
    
    .annonce-card .annonce-actions a {
        background: var(--primary);
        color: white;
        border: none;
    }
    
    .annonce-card .annonce-actions a:hover {
        background: var(--primary-dark);
    }
    
    .annonce-card .annonce-actions button {
        background: transparent;
        color: var(--error);
        border: 1px solid var(--error);
    }
    
    .annonce-card .annonce-actions button:hover {
        background: var(--error);
        color: white;
    }
    
    /* Reviews */
    .reviews-list {
        display: grid;
        gap: 15px;
    }
    
    .review-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s;
    }
    
    .review-card:hover {
        border-color: var(--primary);
    }
    
    .review-card .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .review-card .bien-name a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    
    .review-card .rating {
        color: #fbbf24;
    }
    
    .review-card .review-content {
        color: var(--text-secondary);
        line-height: 1.6;
        margin-bottom: 10px;
    }
    
    .review-card .review-date {
        font-size: 0.85em;
        color: var(--text-secondary);
    }
    
    /* Forms */
    .profile-edit-form {
        max-width: 600px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
        font-size: 0.95em;
    }
    
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group input[type="date"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        font-size: 1em;
        background: var(--bg-card);
        color: var(--text-primary);
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .form-group input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }
    
    .form-group small {
        color: var(--text-secondary);
        font-size: 0.85em;
        margin-top: 5px;
        display: block;
    }
    
    .form-group input[type="checkbox"] {
        margin-right: 10px;
        accent-color: var(--primary);
        width: 18px;
        height: 18px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border: none;
        padding: 14px 30px;
        border-radius: 10px;
        font-size: 1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .btn-cancel {
        background: transparent;
        color: var(--error);
        border: 2px solid var(--error);
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.9em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-family: inherit;
    }
    
    .btn-cancel:hover {
        background: var(--error);
        color: white;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-secondary);
    }
    
    .empty-state .icon {
        font-size: 3em;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
        }
        
        .profile-stats {
            justify-content: center;
        }
        
        .back-link {
            position: static;
            margin-top: 20px;
        }
        
        .profile-tabs {
            justify-content: center;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .modern-table {
            font-size: 0.85em;
        }
        
        .modern-table thead th,
        .modern-table tbody td {
            padding: 10px;
        }
    }
    </style>
</head>
<body>
    <div class="profile-wrapper">
        <!-- En-tête du profil -->
        <div class="profile-header">
            <div class="profile-avatar">👤</div>
            <div class="profile-info">
                <h1><?php
                    if (empty($userData['nom_locataire']) || empty($userData['prenom_locataire'])) {
                        echo '<pre style="color:red;">DEBUG $userData: ' . htmlspecialchars(print_r($userData, true)) . '</pre>';
                    }
                    echo htmlspecialchars(($userData['nom_locataire'] ?? '') . ' ' . ($userData['prenom_locataire'] ?? ''));
                ?></h1>
                <p><?php
                    echo htmlspecialchars(($userData['nom_locataire'] ?? '') . '.' . ($userData['prenom_locataire'] ?? ''));
                ?></p>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?= count($userReservations) ?></span>
                        <span class="stat-label">Réservations</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= count($userBiens) ?></span>
                        <span class="stat-label">Annonces</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= count($userFavoris) ?></span>
                        <span class="stat-label">Favoris</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?= count($userReviews) ?></span>
                        <span class="stat-label">Avis</span>
                    </div>
                </div>
            </div>
            <a href="/../index.php" class="back-link">← Accueil</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= $messageType === 'success' ? '✓' : '✕' ?> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Onglets de navigation -->
        <div class="profile-tabs">
            <button class="tab-btn active" onclick="showTab('favoris')">❤️ Favoris</button>
            <button class="tab-btn" onclick="showTab('reservations')">📅 Réservations</button>
            <button class="tab-btn" onclick="showTab('annonces')">🏠 Mes annonces</button>
            <button class="tab-btn" onclick="showTab('avis')">⭐ Mes avis</button>
            <button class="tab-btn" onclick="showTab('settings')">⚙️ Paramètres</button>
        </div>

        <!-- Onglet Favoris -->
        <div id="tab-favoris" class="tab-content active">
            <section class="profile-section">
                <h3>❤️ Mes Favoris</h3>
                <?php if (empty($userFavoris)) : ?>
                    <div class="empty-state">
                        <div class="icon">💔</div>
                        <p>Vous n'avez pas encore de favoris.<br>Explorez les annonces et ajoutez vos coups de cœur !</p>
                        <a href="../forms/Annonce.form.php" class="view-all-link">🏠 Découvrir les biens</a>
                    </div>
                <?php else : ?>
                    <div class="favoris-grid">
                        <?php foreach ($userFavoris as $bien) : ?>
                        <div class="favori-card">
                            <div class="image-container">
                                <?php if (!empty($bien['photo'])): ?>
                                    <?php
                                    $photoPath = $bien['photo'];
                                    if (strpos($photoPath, 'http') === 0) {
                                        // Lien absolu, on ne touche pas
                                    } else {
                                        // Supprime le préfixe Projet_HAP(House_After_Party)/ s'il existe
                                        $photoPath = preg_replace('#^Projet_HAP\(House_After_Party\)/#', '', $photoPath);
                                        if (strpos($photoPath, 'images/uploads/') === 0) {
                                            $photoPath = '../' . ltrim($photoPath, '/');
                                        } else {
                                            $photoPath = '../images/uploads/' . ltrim($photoPath, '/');
                                        }
                                    }
                                    ?>
                                <img src="<?= htmlspecialchars($photoPath) ?>" alt="<?= htmlspecialchars($bien['nom_biens']) ?>">
                                <?php else: ?>
                                    <div class="no-image">🏠</div>
                                <?php endif; ?>
                                <?php if (!empty($bien['type_bien'])): ?>
                                    <span class="badge"><?= htmlspecialchars($bien['type_bien']) ?></span>
                                <?php endif; ?>
                                <span class="heart-icon">❤️</span>
                            </div>
                            <div class="content">
                                <h4 class="title">
                                    <a href="../forms/annonce_detail.php?id=<?= $bien['id_biens'] ?>">
                                        <?= htmlspecialchars($bien['nom_biens']) ?>
                                    </a>
                                </h4>
                                <div class="location">
                                    📍 <?= htmlspecialchars($bien['nom_commune'] ?? 'Non spécifié') ?>
                                    <?php if (!empty($bien['code_postal'])): ?>
                                        (<?= htmlspecialchars($bien['code_postal']) ?>)
                                    <?php endif; ?>
                                </div>
                                <div class="rating">
                                    <?php 
                                    $note = round($bien['note_moyenne'] ?? 0);
                                    echo str_repeat('⭐', $note);
                                    echo str_repeat('☆', 5 - $note);
                                    ?>
                                    <span style="color: var(--text-secondary); font-size: 0.85em;">(<?= $bien['nb_avis'] ?? 0 ?> avis)</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="../forms/mes_favoris.php" class="view-all-link">Voir tous mes favoris →</a>
                <?php endif; ?>
            </section>
        </div>

        <!-- Onglet Réservations -->
        <div id="tab-reservations" class="tab-content">
            <section class="profile-section">
                <h3>📅 Mes réservations</h3>
                <?php if (!empty($userReservations)): ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bien</th>
                                <th>Date début</th>
                                <th>Date fin</th>
                                <th>Tarif</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($userReservations as $res): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($res['id_reservation']) ?></td>
                                <td><a href="../forms/annonce_detail.php?id=<?= $res['id_biens'] ?>"><?= htmlspecialchars($res['nom_biens'] ?? '—') ?></a></td>
                                <td><?= date('d/m/Y', strtotime($res['date_debut_reservation'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($res['date_fin_reservation'])) ?></td>
                                <td><?= isset($res['tarif']) ? number_format($res['tarif'],2) . ' €' : '—' ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Voulez-vous annuler cette réservation ?');" style="display:inline;">
                                        <input type="hidden" name="id_reservation" value="<?= htmlspecialchars($res['id_reservation']) ?>">
                                        <button type="submit" name="delete_reservation" class="btn-cancel">Annuler</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">📅</div>
                        <p>Vous n'avez effectué aucune réservation pour le moment.</p>
                        <a href="../forms/Annonce.form.php" class="view-all-link">🏠 Voir les annonces</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Onglet Annonces -->
        <div id="tab-annonces" class="tab-content">
            <section class="profile-section">
                <h3>🏠 Mes annonces</h3>
                <?php if (!empty($userBiens)): ?>
                    <div class="annonces-grid">
                        <?php foreach ($userBiens as $b): ?>
                        <div class="annonce-card">
                            <div class="annonce-title">
                                <a href="../forms/annonce_detail.php?id=<?= $b['id_biens'] ?>"><?= htmlspecialchars($b['nom_biens']) ?></a>
                            </div>
                            <div class="annonce-actions">
                                <a href="../forms/annonce_detail.php?id=<?= $b['id_biens'] ?>">Voir / Éditer</a>
                                <form method="post" action="../forms/Annonce.form.php" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette annonce ?');">
                                    <input type="hidden" name="id_biens" value="<?= $b['id_biens'] ?>">
                                    <button type="submit" name="delete_bien">Supprimer</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">🏠</div>
                        <p>Vous n'avez posté aucune annonce pour le moment.</p>
                        <a href="../forms/Annonce.form.php" class="view-all-link">➕ Créer une annonce</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Onglet Avis -->
        <div id="tab-avis" class="tab-content">
            <section class="profile-section">
                <h3>⭐ Mes avis</h3>
                <?php if (!empty($userReviews)): ?>
                    <div class="reviews-list">
                        <?php foreach ($userReviews as $rev): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="bien-name">
                                    <a href="../forms/annonce_detail.php?id=<?= $rev['id_biens'] ?>">
                                        <?= htmlspecialchars($rev['nom_biens']) ?>
                                    </a>
                                </span>
                                <span class="rating">
                                    <?= str_repeat('⭐', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])) ?>
                                </span>
                            </div>
                            <div class="review-content"><?= nl2br(htmlspecialchars($rev['content'])) ?></div>
                            <div class="review-date">Posté le <?= date('d/m/Y à H:i', strtotime($rev['created_at'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon">⭐</div>
                        <p>Vous n'avez posté aucun avis pour le moment.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Onglet Paramètres -->
        <div id="tab-settings" class="tab-content">
            <section class="profile-section">
                <h3>👤 Modifier mes informations</h3>
                <form method="post" class="profile-edit-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" maxlength="100" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nom">Nom</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($userData['nom'] ?? '') ?>" maxlength="50" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($userData['prenom'] ?? '') ?>" maxlength="50" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tel">Téléphone</label>
                            <input type="tel" id="tel" name="tel" value="<?= htmlspecialchars($userData['tel'] ?? '') ?>" maxlength="20" pattern="[0-9+\s.-]{8,20}" required>
                        </div>
                        <div class="form-group">
                            <label for="date_naissance">Date de naissance</label>
                            <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($userData['date_naissance'] ?? '') ?>" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($userData['adresse'] ?? '') ?>" maxlength="100" required autocomplete="street-address">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="complement">Complément d'adresse</label>
                            <input type="text" id="complement" name="complement" value="<?= htmlspecialchars($userData['complement'] ?? '') ?>" maxlength="100" autocomplete="address-line2">
                        </div>
                        <div class="form-group">
                            <label for="commune">Commune</label>
                            <input type="text" id="commune" name="commune" value="<?= htmlspecialchars($userData['commune'] ?? '') ?>" maxlength="100" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="checkbox" id="rgpd" name="rgpd" required>
                        <label for="rgpd" style="display: inline;">J'accepte la politique de confidentialité et le traitement de mes données personnelles conformément au RGPD.</label>
                    </div>
                    <button type="submit" name="update_profile" class="btn-primary">💾 Enregistrer les modifications</button>
                </form>
            </section>

            <section class="profile-section">
                <h3>🔒 Changer le mot de passe</h3>
                <form method="post" class="profile-edit-form">
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn-primary">🔐 Mettre à jour le mot de passe</button>
                </form>
            </section>
        </div>
    </div>
    
    <script>
    function showTab(tabName) {
        // Cacher tous les contenus
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Désactiver tous les boutons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Afficher le contenu sélectionné
        document.getElementById('tab-' + tabName).classList.add('active');
        
        // Activer le bouton correspondant
        event.target.classList.add('active');
    }
    </script>
    
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
