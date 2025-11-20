-- Migration: create Reviews table to store user reviews and ratings for Biens
CREATE TABLE IF NOT EXISTS Reviews (
  id_review INT AUTO_INCREMENT PRIMARY KEY,
  id_biens INT NOT NULL,
  id_locataire INT NULL,
  rating TINYINT NULL,
  content TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_biens) REFERENCES Biens(id_biens) ON DELETE CASCADE
);

-- Optionally link to locataire if the column exists in your users table
ALTER TABLE Reviews ADD INDEX idx_bien (id_biens);
