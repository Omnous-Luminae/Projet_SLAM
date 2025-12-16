<?php
/**
 * Configuration de sécurité pour les utilisateurs (locataires)
 * Protection contre les attaques brute force et spam
 */

// Protection brute force - Connexion
define('USER_MAX_LOGIN_ATTEMPTS', 5);           // Nombre max de tentatives
define('USER_LOGIN_LOCKOUT_TIME', 900);         // Durée de blocage (15 min)

// Protection brute force - Inscription
define('USER_MAX_REGISTER_ATTEMPTS', 3);        // Nombre max d'inscriptions par IP/heure
define('USER_REGISTER_COOLDOWN', 3600);         // Cooldown inscription (1 heure)

// Protection CSRF
define('USER_CSRF_ENABLED', true);

// Rate limiting général
define('USER_RATE_LIMIT_ENABLED', true);

// Logging des tentatives suspectes
define('USER_LOG_SUSPICIOUS', true);
define('USER_LOG_FILE', __DIR__ . '/../logs/security.log');

/**
 * Récupère le nombre de tentatives de connexion pour une IP
 */
function getUserLoginAttempts($pdo, $ip) {
    // Créer la table si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) DEFAULT 0,
        INDEX idx_ip_time (ip_address, attempt_time)
    )");
    
    $cutoff = date('Y-m-d H:i:s', time() - USER_LOGIN_LOCKOUT_TIME);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_login_attempts WHERE ip_address = :ip AND attempt_time > :cutoff AND success = 0");
    $stmt->execute(['ip' => $ip, 'cutoff' => $cutoff]);
    return (int) $stmt->fetchColumn();
}

/**
 * Enregistre une tentative de connexion
 */
function recordUserLoginAttempt($pdo, $ip, $email = null, $success = false) {
    $stmt = $pdo->prepare("INSERT INTO user_login_attempts (ip_address, email, success) VALUES (:ip, :email, :success)");
    $stmt->execute(['ip' => $ip, 'email' => $email, 'success' => $success ? 1 : 0]);
    
    // Log si tentative suspecte
    if (USER_LOG_SUSPICIOUS && !$success) {
        logSecurityEvent("Failed login attempt from $ip for email: $email");
    }
}

/**
 * Efface les tentatives après connexion réussie
 */
function clearUserLoginAttempts($pdo, $ip) {
    $stmt = $pdo->prepare("DELETE FROM user_login_attempts WHERE ip_address = :ip");
    $stmt->execute(['ip' => $ip]);
}

/**
 * Récupère le nombre d'inscriptions récentes pour une IP
 */
function getRegistrationAttempts($pdo, $ip) {
    // Créer la table si elle n'existe pas
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_registration_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) DEFAULT 0,
        INDEX idx_ip_time (ip_address, attempt_time)
    )");
    
    $cutoff = date('Y-m-d H:i:s', time() - USER_REGISTER_COOLDOWN);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_registration_attempts WHERE ip_address = :ip AND attempt_time > :cutoff");
    $stmt->execute(['ip' => $ip, 'cutoff' => $cutoff]);
    return (int) $stmt->fetchColumn();
}

/**
 * Enregistre une tentative d'inscription
 */
function recordRegistrationAttempt($pdo, $ip, $email = null, $success = false) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_registration_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        success TINYINT(1) DEFAULT 0,
        INDEX idx_ip_time (ip_address, attempt_time)
    )");
    
    $stmt = $pdo->prepare("INSERT INTO user_registration_attempts (ip_address, email, success) VALUES (:ip, :email, :success)");
    $stmt->execute(['ip' => $ip, 'email' => $email, 'success' => $success ? 1 : 0]);
    
    if (USER_LOG_SUSPICIOUS && !$success) {
        logSecurityEvent("Failed registration attempt from $ip for email: $email");
    }
}

/**
 * Vérifie si une IP est bloquée pour la connexion
 */
function isUserLoginBlocked($pdo, $ip) {
    $attempts = getUserLoginAttempts($pdo, $ip);
    return $attempts >= USER_MAX_LOGIN_ATTEMPTS;
}

/**
 * Vérifie si une IP est bloquée pour l'inscription
 */
function isRegistrationBlocked($pdo, $ip) {
    $attempts = getRegistrationAttempts($pdo, $ip);
    return $attempts >= USER_MAX_REGISTER_ATTEMPTS;
}

/**
 * Calcule le temps restant avant déblocage (connexion)
 */
function getUserLockoutRemaining($pdo, $ip) {
    $stmt = $pdo->prepare("SELECT MAX(attempt_time) FROM user_login_attempts WHERE ip_address = :ip AND success = 0");
    $stmt->execute(['ip' => $ip]);
    $lastAttempt = $stmt->fetchColumn();
    
    if ($lastAttempt) {
        $unlockTime = strtotime($lastAttempt) + USER_LOGIN_LOCKOUT_TIME;
        return max(0, $unlockTime - time());
    }
    return 0;
}

/**
 * Calcule le temps restant avant déblocage (inscription)
 */
function getRegistrationCooldownRemaining($pdo, $ip) {
    $stmt = $pdo->prepare("SELECT MIN(attempt_time) FROM user_registration_attempts WHERE ip_address = :ip AND attempt_time > :cutoff");
    $cutoff = date('Y-m-d H:i:s', time() - USER_REGISTER_COOLDOWN);
    $stmt->execute(['ip' => $ip, 'cutoff' => $cutoff]);
    $firstAttempt = $stmt->fetchColumn();
    
    if ($firstAttempt) {
        $unlockTime = strtotime($firstAttempt) + USER_REGISTER_COOLDOWN;
        return max(0, $unlockTime - time());
    }
    return 0;
}

/**
 * Log des événements de sécurité
 */
function logSecurityEvent($message) {
    if (!USER_LOG_SUSPICIOUS) return;
    
    $logDir = dirname(USER_LOG_FILE);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    @file_put_contents(USER_LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Génère un token CSRF
 */
function generateUserCsrfToken() {
    if (!isset($_SESSION['user_csrf_token'])) {
        $_SESSION['user_csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
    return $_SESSION['user_csrf_token'];
}

/**
 * Vérifie un token CSRF
 */
function verifyUserCsrfToken($token) {
    return isset($_SESSION['user_csrf_token']) && hash_equals($_SESSION['user_csrf_token'], $token);
}

/**
 * Régénère le token CSRF
 */
function regenerateUserCsrfToken() {
    $_SESSION['user_csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    return $_SESSION['user_csrf_token'];
}
