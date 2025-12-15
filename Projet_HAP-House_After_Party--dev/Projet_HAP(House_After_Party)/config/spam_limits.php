<?php
/**
 * Configuration des limites anti-spam pour HAP
 * 
 * Ce fichier définit les règles de limitation de publication d'annonces
 * pour éviter le spam et les abus.
 */

// Limites de publication d'annonces
define('SPAM_LIMIT_ENABLED', true); // Activer/désactiver le système anti-spam

// Limite par jour (nombre maximum d'annonces créées par jour par utilisateur)
define('MAX_ANNOUNCEMENTS_PER_DAY', 3);

// Limite par semaine (nombre maximum d'annonces créées par semaine par utilisateur)
define('MAX_ANNOUNCEMENTS_PER_WEEK', 10);

// Limite par mois (nombre maximum d'annonces créées par mois par utilisateur)
define('MAX_ANNOUNCEMENTS_PER_MONTH', 20);

// Intervalle minimum entre deux publications (en minutes)
define('MIN_INTERVAL_BETWEEN_POSTS', 30); // 30 minutes

// Les administrateurs sont-ils exemptés des limites ?
define('ADMIN_EXEMPT_FROM_LIMITS', true);

// Rôles exemptés des limites (en plus des admins)
define('EXEMPT_ROLES', ['animateur', 'super_admin']);

/**
 * Vérifie si un utilisateur a atteint sa limite de publication
 * 
 * @param PDO $pdo Connexion à la base de données
 * @param int|string $userId ID de l'utilisateur (ou nom si pas connecté)
 * @param string $userRole Rôle de l'utilisateur (null si non connecté)
 * @return array ['allowed' => bool, 'message' => string, 'count' => int, 'limit' => int]
 */
function checkSpamLimit($pdo, $userId, $userRole = null) {
    // Si le système est désactivé, toujours autoriser
    if (!SPAM_LIMIT_ENABLED) {
        return ['allowed' => true, 'message' => '', 'count' => 0, 'limit' => 999999];
    }
    
    // Si l'utilisateur est admin et que les admins sont exemptés
    if (ADMIN_EXEMPT_FROM_LIMITS && $userRole && in_array($userRole, EXEMPT_ROLES)) {
        return ['allowed' => true, 'message' => '', 'count' => 0, 'limit' => 999999];
    }
    
    $now = new DateTime();
    
    // Vérification 1: Limite par jour
    $dayAgo = (clone $now)->modify('-1 day')->format('Y-m-d H:i:s');
    $countDay = countRecentAnnouncements($pdo, $userId, $dayAgo);
    
    if ($countDay >= MAX_ANNOUNCEMENTS_PER_DAY) {
        return [
            'allowed' => false,
            'message' => "Limite journalière atteinte. Vous avez publié {$countDay} annonces sur " . MAX_ANNOUNCEMENTS_PER_DAY . " autorisées aujourd'hui. Veuillez réessayer demain.",
            'count' => $countDay,
            'limit' => MAX_ANNOUNCEMENTS_PER_DAY,
            'period' => 'jour'
        ];
    }
    
    // Vérification 2: Limite par semaine
    $weekAgo = (clone $now)->modify('-7 days')->format('Y-m-d H:i:s');
    $countWeek = countRecentAnnouncements($pdo, $userId, $weekAgo);
    
    if ($countWeek >= MAX_ANNOUNCEMENTS_PER_WEEK) {
        return [
            'allowed' => false,
            'message' => "Limite hebdomadaire atteinte. Vous avez publié {$countWeek} annonces sur " . MAX_ANNOUNCEMENTS_PER_WEEK . " autorisées cette semaine. Veuillez réessayer dans quelques jours.",
            'count' => $countWeek,
            'limit' => MAX_ANNOUNCEMENTS_PER_WEEK,
            'period' => 'semaine'
        ];
    }
    
    // Vérification 3: Limite par mois
    $monthAgo = (clone $now)->modify('-30 days')->format('Y-m-d H:i:s');
    $countMonth = countRecentAnnouncements($pdo, $userId, $monthAgo);
    
    if ($countMonth >= MAX_ANNOUNCEMENTS_PER_MONTH) {
        return [
            'allowed' => false,
            'message' => "Limite mensuelle atteinte. Vous avez publié {$countMonth} annonces sur " . MAX_ANNOUNCEMENTS_PER_MONTH . " autorisées ce mois. Veuillez réessayer le mois prochain.",
            'count' => $countMonth,
            'limit' => MAX_ANNOUNCEMENTS_PER_MONTH,
            'period' => 'mois'
        ];
    }
    
    // Vérification 4: Intervalle minimum entre publications
    $lastPost = getLastAnnouncementTime($pdo, $userId);
    if ($lastPost) {
        $lastPostTime = new DateTime($lastPost);
        $minutesSinceLastPost = ($now->getTimestamp() - $lastPostTime->getTimestamp()) / 60;
        
        if ($minutesSinceLastPost < MIN_INTERVAL_BETWEEN_POSTS) {
            $remainingMinutes = ceil(MIN_INTERVAL_BETWEEN_POSTS - $minutesSinceLastPost);
            return [
                'allowed' => false,
                'message' => "Veuillez patienter {$remainingMinutes} minute(s) avant de publier une nouvelle annonce. Intervalle minimum requis : " . MIN_INTERVAL_BETWEEN_POSTS . " minutes.",
                'count' => 0,
                'limit' => MIN_INTERVAL_BETWEEN_POSTS,
                'period' => 'intervalle'
            ];
        }
    }
    
    // Toutes les vérifications passées
    return [
        'allowed' => true,
        'message' => '',
        'count' => $countDay,
        'limit' => MAX_ANNOUNCEMENTS_PER_DAY,
        'remaining_today' => MAX_ANNOUNCEMENTS_PER_DAY - $countDay,
        'remaining_week' => MAX_ANNOUNCEMENTS_PER_WEEK - $countWeek,
        'remaining_month' => MAX_ANNOUNCEMENTS_PER_MONTH - $countMonth
    ];
}

/**
 * Compte le nombre d'annonces créées par un utilisateur depuis une date donnée
 * 
 * @param PDO $pdo Connexion à la base de données
 * @param int|string $userId ID ou nom de l'utilisateur
 * @param string $since Date de début (format MySQL)
 * @return int Nombre d'annonces
 */
function countRecentAnnouncements($pdo, $userId, $since) {
    try {
        // Vérifier si c'est un ID numérique ou un nom
        if (is_numeric($userId)) {
            // Recherche par created_by_id
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM Biens WHERE created_by_id = ? AND id_biens >= (SELECT COALESCE(MIN(id_biens), 0) FROM Biens WHERE created_by_id = ? AND created_at >= ?)');
            $stmt->execute([$userId, $userId, $since]);
        } else {
            // Recherche par created_by_name
            $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM Biens WHERE created_by_name = ? AND created_at >= ?');
            $stmt->execute([$userId, $since]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($result['count'] ?? 0);
    } catch (PDOException $e) {
        // En cas d'erreur (ex: colonne created_at n'existe pas), retourner 0
        return 0;
    }
}

/**
 * Récupère la date/heure de la dernière annonce créée par un utilisateur
 * 
 * @param PDO $pdo Connexion à la base de données
 * @param int|string $userId ID ou nom de l'utilisateur
 * @return string|null Date de la dernière annonce (format MySQL) ou null
 */
function getLastAnnouncementTime($pdo, $userId) {
    try {
        if (is_numeric($userId)) {
            $stmt = $pdo->prepare('SELECT created_at FROM Biens WHERE created_by_id = ? ORDER BY id_biens DESC LIMIT 1');
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare('SELECT created_at FROM Biens WHERE created_by_name = ? ORDER BY id_biens DESC LIMIT 1');
            $stmt->execute([$userId]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['created_at'] ?? null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Obtient des statistiques sur les publications d'un utilisateur
 * 
 * @param PDO $pdo Connexion à la base de données
 * @param int|string $userId ID ou nom de l'utilisateur
 * @return array Statistiques de publication
 */
function getUserAnnouncementStats($pdo, $userId) {
    $now = new DateTime();
    
    return [
        'today' => countRecentAnnouncements($pdo, $userId, (clone $now)->modify('-1 day')->format('Y-m-d H:i:s')),
        'week' => countRecentAnnouncements($pdo, $userId, (clone $now)->modify('-7 days')->format('Y-m-d H:i:s')),
        'month' => countRecentAnnouncements($pdo, $userId, (clone $now)->modify('-30 days')->format('Y-m-d H:i:s')),
        'total' => countRecentAnnouncements($pdo, $userId, '2000-01-01 00:00:00'),
        'last_post' => getLastAnnouncementTime($pdo, $userId)
    ];
}
