<?php

/**
 * Configuration de l'archivage et du cryptage des réservations
 */

return [
    // Paramètres de cryptage
    'encryption' => [
        'algorithm' => 'AES-256-CBC',        // Algorithme de cryptage
        'hash_algorithm' => 'sha256',        // Algorithme de hachage
        'use_key_file' => true,              // Utiliser un fichier de clé
        'key_file_path' => __DIR__ . '/.encryption_key',
    ],
    
    // Paramètres d'archivage automatique
    'auto_archive' => [
        'enabled' => true,                   // Activer l'archivage automatique
        'days_after_end' => 1,               // Archiver N jours après la fin de la réservation
        'run_on_request' => false,           // Exécuter le cronjob lors d'une requête
    ],
    
    // Paramètres de conservation
    'retention' => [
        'keep_archives_days' => 2555,        // Conserver les archives pendant 7 ans
        'auto_delete_old' => false,          // Supprimer automatiquement les anciennes archives
    ],
    
    // Paramètres de sécurité
    'security' => [
        'require_password_for_restore' => false,    // Exiger un mot de passe pour restaurer
        'log_all_access' => true,                   // Enregistrer tous les accès aux archives
        'restrict_to_admin' => true,                // Restreindre la gestion aux administrateurs
        'enable_audit_trail' => true,               // Activer la traçabilité complète
    ],
    
    // Données à inclure dans l'archive
    'archived_fields' => [
        'locataire' => [
            'id_locataire',
            'nom_locataire',
            'prenom_locataire',
            'email_locataire',
            'telephone_locataire',
            'date_naissance',
            'rue_locataire',
            'complement_locataire',
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
    ],
];

?>
