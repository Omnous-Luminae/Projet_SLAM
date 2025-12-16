-- Table des favoris
-- Migration pour ajouter le système de favoris

CREATE TABLE IF NOT EXISTS Favoris (
    id_favori INT AUTO_INCREMENT PRIMARY KEY,
    id_locataire INT NOT NULL,
    id_biens INT NOT NULL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_locataire) REFERENCES Locataire(id_locataire) ON DELETE CASCADE,
    FOREIGN KEY (id_biens) REFERENCES Biens(id_biens) ON DELETE CASCADE,
    UNIQUE KEY unique_favori (id_locataire, id_biens)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour optimiser les requêtes
CREATE INDEX idx_favoris_locataire ON Favoris(id_locataire);
CREATE INDEX idx_favoris_biens ON Favoris(id_biens);
