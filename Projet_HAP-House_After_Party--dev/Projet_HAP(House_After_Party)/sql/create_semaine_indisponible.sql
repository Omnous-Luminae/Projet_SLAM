-- Migration: create table semaine_indisponible
-- Run this on your MySQL server (test on a copy first)

CREATE TABLE IF NOT EXISTS `semaine_indisponible` (
  `id_semaine_indisponible` INT AUTO_INCREMENT PRIMARY KEY,
  `id_biens` INT NOT NULL,
  `annee` SMALLINT NOT NULL,
  `semaine` TINYINT NOT NULL,
  `raison` VARCHAR(255) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_bien_week_year` (`id_biens`, `annee`, `semaine`),
  KEY `idx_bien_year_week` (`id_biens`, `annee`, `semaine`),
  CONSTRAINT `fk_semaine_biens` FOREIGN KEY (`id_biens`) REFERENCES `Biens`(`id_biens`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: migrate existing JSON from Biens.unavailable_weeks (assumes JSON array of week numbers for current year)
-- This uses JSON_TABLE available in MySQL 8+. If your MySQL does not support JSON_TABLE, consider a PHP migration script.

-- Example migration (MySQL 8+):
-- START TRANSACTION;
-- INSERT IGNORE INTO semaine_indisponible (id_biens, annee, semaine)
-- SELECT b.id_biens, YEAR(CURDATE()), CAST(j.semaine AS UNSIGNED)
-- FROM Biens b
-- JOIN JSON_TABLE(b.unavailable_weeks, '$[*]' COLUMNS(semaine VARCHAR(10) PATH '$')) AS j
-- WHERE b.unavailable_weeks IS NOT NULL AND b.unavailable_weeks <> '[]';
-- COMMIT;

-- If you prefer a safe PHP migration (recommended for MySQL < 8), run a PHP script that reads Biens.unavailable_weeks, parses JSON, and inserts rows.
