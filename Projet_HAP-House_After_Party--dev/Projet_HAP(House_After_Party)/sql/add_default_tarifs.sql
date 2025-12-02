-- Migration: Add default tarifs table and default_tarif column to Biens
-- This allows setting default prices per season for weeks without specific tarifs

ALTER TABLE Biens ADD COLUMN IF NOT EXISTS default_tarif_set BOOLEAN DEFAULT FALSE;

-- Create table for default tarifs per season per bien
CREATE TABLE IF NOT EXISTS Tarif_Defaut (
    id_tarif_defaut INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    id_biens INT NOT NULL,
    id_saison INT NOT NULL,
    tarif_defaut FLOAT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bien_saison (id_biens, id_saison),
    FOREIGN KEY (id_biens) REFERENCES Biens(id_biens) ON DELETE CASCADE,
    FOREIGN KEY (id_saison) REFERENCES Saison(id_saison) ON DELETE CASCADE
);

-- Add column to track available/unavailable weeks
ALTER TABLE Biens ADD COLUMN IF NOT EXISTS unavailable_weeks JSON COMMENT 'JSON array of week numbers (1-52) that are not available for booking';
