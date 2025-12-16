<?php

/**
 * Configuration de l'archivage avec support des variables d'environnement
 * Pour la production, utiliser des variables d'environnement au lieu de hardcoder les valeurs
 */

return [
    // Paramètres de cryptage
    'encryption' => [
        'algorithm' => getenv('ARCHIVE_ENCRYPTION_ALGO') ?: 'AES-256-CBC',
        'hash_algorithm' => getenv('ARCHIVE_HASH_ALGO') ?: 'sha256',
        'use_key_file' => (bool) getenv('ARCHIVE_USE_KEY_FILE') ?: true,
        'key_file_path' => getenv('ARCHIVE_KEY_PATH') ?: __DIR__ . '/.encryption_key',
        // Pour production seulement: utiliser une clé depuis l'environnement
        // 'encryption_key' => getenv('ARCHIVE_ENCRYPTION_KEY'),
    ],
    
    // Paramètres d'archivage automatique
    'auto_archive' => [
        'enabled' => (bool) getenv('ARCHIVE_AUTO_ENABLED') ?: true,
        'days_after_end' => (int) getenv('ARCHIVE_DAYS_AFTER_END') ?: 1,
        'batch_size' => (int) getenv('ARCHIVE_BATCH_SIZE') ?: 100,
        'run_on_request' => (bool) getenv('ARCHIVE_RUN_ON_REQUEST') ?: false,
    ],
    
    // Paramètres de conservation (RGPD)
    'retention' => [
        'keep_archives_days' => (int) getenv('ARCHIVE_RETENTION_DAYS') ?: 2555, // 7 ans par défaut
        'auto_delete_old' => (bool) getenv('ARCHIVE_AUTO_DELETE') ?: false,
        'delete_method' => getenv('ARCHIVE_DELETE_METHOD') ?: 'soft', // 'soft' ou 'hard'
    ],
    
    // Paramètres de sécurité
    'security' => [
        'require_password_for_restore' => (bool) getenv('ARCHIVE_REQUIRE_PASSWORD') ?: false,
        'log_all_access' => (bool) getenv('ARCHIVE_LOG_ACCESS') ?: true,
        'restrict_to_admin' => (bool) getenv('ARCHIVE_RESTRICT_ADMIN') ?: true,
        'enable_audit_trail' => (bool) getenv('ARCHIVE_AUDIT_TRAIL') ?: true,
        'max_restoration_per_day' => (int) getenv('ARCHIVE_MAX_RESTORE') ?: 50,
        'require_2fa_for_delete' => (bool) getenv('ARCHIVE_REQUIRE_2FA') ?: false,
    ],
    
    // Paramètres de performance
    'performance' => [
        'chunk_size' => (int) getenv('ARCHIVE_CHUNK_SIZE') ?: 1000,
        'parallel_archiving' => (bool) getenv('ARCHIVE_PARALLEL') ?: false,
        'db_index_optimization' => (bool) getenv('ARCHIVE_INDEX_OPT') ?: true,
        'cache_archives' => (bool) getenv('ARCHIVE_CACHE') ?: false,
        'cache_ttl' => (int) getenv('ARCHIVE_CACHE_TTL') ?: 3600,
    ],
    
    // Paramètres de notification
    'notifications' => [
        'send_email_on_archive' => (bool) getenv('ARCHIVE_EMAIL') ?: false,
        'email_to' => getenv('ARCHIVE_EMAIL_TO') ?: 'admin@example.com',
        'send_email_on_error' => (bool) getenv('ARCHIVE_ERROR_EMAIL') ?: true,
        'send_summary_report' => (bool) getenv('ARCHIVE_SUMMARY_EMAIL') ?: true,
        'summary_frequency' => getenv('ARCHIVE_SUMMARY_FREQ') ?: 'monthly', // daily, weekly, monthly
    ],
    
    // Données à inclure dans l'archive
    'archived_fields' => [
        'include_all' => (bool) getenv('ARCHIVE_INCLUDE_ALL') ?: true,
        
        'locataire' => [
            'id_locataire',
            'nom_locataire',
            'prenom_locataire',
            'email_locataire',
            'telephone_locataire',
            'date_naissance',
            'rue_locataire',
            'complement_locataire',
            'raison_sociale',
            'siret',
        ],
        
        'bien' => [
            'id_biens',
            'nom_biens',
            'rue_biens',
            'superficie_biens',
            'description_biens',
            'nb_couchage',
            'animal_biens',
        ],
        
        'reservation' => [
            'id_reservation',
            'date_debut_reservation',
            'date_fin_reservation',
            'tarif',
        ],
        
        'commune' => [
            'nom_commune',
            'code_postal',
            'code_insee',
        ],
    ],
    
    // Chemins des fichiers
    'paths' => [
        'log_dir' => getenv('ARCHIVE_LOG_DIR') ?: __DIR__ . '/../logs',
        'backup_dir' => getenv('ARCHIVE_BACKUP_DIR') ?: __DIR__ . '/../backups',
        'temp_dir' => getenv('ARCHIVE_TEMP_DIR') ?: sys_get_temp_dir(),
    ],
    
    // Logs et monitoring
    'logging' => [
        'level' => getenv('ARCHIVE_LOG_LEVEL') ?: 'INFO', // DEBUG, INFO, WARNING, ERROR
        'log_sql' => (bool) getenv('ARCHIVE_LOG_SQL') ?: false,
        'log_encryption' => (bool) getenv('ARCHIVE_LOG_ENC') ?: false,
        'enable_syslog' => (bool) getenv('ARCHIVE_SYSLOG') ?: false,
    ],
    
    // Paramètres d'environnement
    'environment' => [
        'name' => getenv('ARCHIVE_ENV') ?: 'development',
        'debug' => (bool) getenv('ARCHIVE_DEBUG') ?: false,
        'strict_mode' => (bool) getenv('ARCHIVE_STRICT') ?: false,
    ],
];

?>
