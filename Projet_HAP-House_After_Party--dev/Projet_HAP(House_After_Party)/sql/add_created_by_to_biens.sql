-- Migration: add created_by columns to Biens
ALTER TABLE Biens
ADD COLUMN created_by_id INT NULL AFTER id_type_biens,
ADD COLUMN created_by_name VARCHAR(255) NULL AFTER created_by_id,
ADD CONSTRAINT fk_biens_created_by FOREIGN KEY (created_by_id) REFERENCES Locataire(id_locataire);

-- Run this SQL on your database (e.g., using phpMyAdmin or mysql CLI):
-- mysql -u user -p Project_HAP < add_created_by_to_biens.sql
