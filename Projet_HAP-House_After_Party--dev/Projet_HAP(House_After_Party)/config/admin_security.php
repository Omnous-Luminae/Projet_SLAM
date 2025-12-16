<?php
/**
 * Configuration de sécurité pour l'accès admin
 * NE PAS COMMITTER CE FICHIER EN PRODUCTION
 */

// Clé secrète pour accéder aux pages admin
// À changer régulièrement et garder secrète
define('ADMIN_SECRET_KEY', 'HAP_Admin_2025_SecureKey!');

// Nombre maximum de tentatives de connexion avant blocage
define('MAX_LOGIN_ATTEMPTS', 5);

// Durée du blocage en secondes (15 minutes)
define('LOGIN_LOCKOUT_TIME', 900);

// Activer la vérification par email pour les nouvelles inscriptions admin
define('ADMIN_EMAIL_VERIFICATION', false); // Mettre true en production

// Liste des IPs autorisées (vide = toutes autorisées)
// En production, vous pouvez restreindre aux IPs de confiance
define('ADMIN_ALLOWED_IPS', []);

// Activer les logs de connexion admin
define('ADMIN_LOG_ENABLED', true);
