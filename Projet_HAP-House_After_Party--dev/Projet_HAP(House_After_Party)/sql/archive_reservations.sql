-- Table pour archiver les réservations passées avec données cryptées
CREATE TABLE IF NOT EXISTS Reservation_Archive(
    id_archive INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    id_reservation_original INT NOT NULL,
    donnees_cryptees LONGTEXT NOT NULL,
    cle_derivee VARCHAR(64) NOT NULL,
    vecteur_initialisation VARCHAR(64) NOT NULL,
    date_archivage DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_debut_reservation DATE NOT NULL,
    date_fin_reservation DATE NOT NULL,
    id_locataire INT NOT NULL,
    id_biens INT NOT NULL,
    statut_archivage ENUM('archivé', 'restauré', 'supprimé') DEFAULT 'archivé',
    date_derniere_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date_archivage (date_archivage),
    INDEX idx_id_reservation_original (id_reservation_original),
    INDEX idx_statut (statut_archivage)
);

-- Table de journalisation des archivages
CREATE TABLE IF NOT EXISTS Archive_Log(
    id_log INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    action VARCHAR(50) NOT NULL,
    id_reservation INT,
    id_archive INT,
    date_action DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    utilisateur_id INT,
    description TEXT,
    INDEX idx_date_action (date_action),
    INDEX idx_action (action)
);
