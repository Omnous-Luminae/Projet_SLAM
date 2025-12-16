<?php

/**
 * Script de tâche programmée pour archiver automatiquement les réservations passées
 * À configurer comme une tâche cron (par exemple: chaque jour à 2h du matin)
 * 
 * Cron: 0 2 * * * /usr/bin/php /chemin/vers/cron_archive_reservations.php
 */

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/classes/ReservationArchive.php";

// Définir le fuseau horaire
date_default_timezone_set('Europe/Paris');

try {
    $archive_manager = new ReservationArchive($pdo);
    
    // Archiver les réservations passées depuis plus d'1 jour
    $count = $archive_manager->archiveAllPastReservations(1);
    
    $message = "[" . date('Y-m-d H:i:s') . "] $count réservation(s) archivée(s)";
    
    // Logger l'action
    file_put_contents(
        __DIR__ . '/logs/archive.log',
        $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    
    echo $message;
    
} catch (Exception $e) {
    $error = "[" . date('Y-m-d H:i:s') . "] ERREUR: " . $e->getMessage();
    
    file_put_contents(
        __DIR__ . '/logs/archive_errors.log',
        $error . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
    
    echo $error;
    exit(1);
}

exit(0);

?>
