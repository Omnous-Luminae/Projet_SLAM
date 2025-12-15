-- Migration : Ajout de la table Photos pour les Points d'Intérêt
-- Date : 11/12/2025

USE Project_HAP;

-- Créer la table pour les photos des points d'intérêt
CREATE TABLE IF NOT EXISTS Photos_PtsInteret (
    id_photo_pts INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    lien_photo_pts VARCHAR(255) NOT NULL,
    id_pts_interet INT NOT NULL,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pts_interet) REFERENCES Pts_Interet(id_pts_interet) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vérification
SELECT 'Table Photos_PtsInteret créée avec succès' AS message;

-- Afficher la structure
DESCRIBE Photos_PtsInteret;
