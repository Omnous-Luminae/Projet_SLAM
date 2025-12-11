-- ============================================
-- MIGRATIONS COMPLÈTES - PROJET HAP
-- Regroupe TOUTES les migrations SQL
-- À exécuter dans l'ordre sur la base Project_HAP
-- ============================================

-- ============================================
-- PARTIE 1: STRUCTURE DE BASE
-- ============================================

-- Si vous partez de zéro, décommentez la ligne suivante :
-- SOURCE sql/base.sql;


-- ============================================
-- PARTIE 2: AJOUT DES COLONNES CREATED_BY
-- ============================================

-- Ajouter les colonnes de traçabilité des créateurs
ALTER TABLE Biens
ADD COLUMN IF NOT EXISTS created_by_id INT NULL,
ADD COLUMN IF NOT EXISTS created_by_name VARCHAR(255) NULL;

-- Ajouter la contrainte de clé étrangère
ALTER TABLE Biens 
ADD CONSTRAINT IF NOT EXISTS fk_biens_created_by 
FOREIGN KEY (created_by_id) REFERENCES Locataire(id_locataire);

SELECT '✅ Colonnes created_by ajoutées aux Biens' as status;


-- ============================================
-- PARTIE 3: SYSTÈME DE VALIDATION DES BIENS
-- ============================================

-- Ajouter les colonnes de validation
ALTER TABLE Biens 
ADD COLUMN IF NOT EXISTS validated BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS validated_by INT NULL,
ADD COLUMN IF NOT EXISTS validated_at TIMESTAMP NULL;

-- Ajouter la contrainte de clé étrangère pour validated_by
ALTER TABLE Biens 
ADD CONSTRAINT IF NOT EXISTS fk_biens_validated_by 
FOREIGN KEY (validated_by) REFERENCES Animateur(id_animateur);

-- Mettre tous les biens existants comme validés (pour éviter de devoir tout re-valider)
UPDATE Biens 
SET validated = TRUE 
WHERE validated IS NULL OR validated = FALSE;

SELECT '✅ Colonnes de validation ajoutées aux Biens' as status;


-- ============================================
-- PARTIE 4: MISE À JOUR DES PRESTATIONS
-- ============================================

-- Sauvegarder les compositions existantes
CREATE TABLE IF NOT EXISTS Compose_backup AS SELECT * FROM Compose;

-- Supprimer les compositions (nécessaire à cause des foreign keys)
DELETE FROM Compose;

-- Supprimer les anciennes prestations
DELETE FROM Prestation;

-- Réinitialiser l'auto-increment
ALTER TABLE Prestation AUTO_INCREMENT = 1;

-- Insérer les nouvelles prestations sportives/loisirs
INSERT INTO Prestation (id_prestation, lib_prestation) VALUES
(1, 'Terrain de football'),
(2, 'Terrain de tennis'),
(3, 'Terrain de basketball'),
(4, 'Piscine privée'),
(5, 'Jacuzzi'),
(6, 'Sauna'),
(7, 'Salle de sport'),
(8, 'Terrain de pétanque'),
(9, 'Table de ping-pong'),
(10, 'Baby-foot'),
(11, 'Billard'),
(12, 'Salle de jeux'),
(13, 'Home cinéma'),
(14, 'Studio de musique'),
(15, 'Espace barbecue'),
(16, 'Terrain de volley'),
(17, 'Court de badminton'),
(18, 'Piste de danse'),
(19, 'Bar privé'),
(20, 'Cave à vin');

SELECT '✅ 20 prestations sportives/loisirs ajoutées' as status;


-- ============================================
-- PARTIE 5: ADRESSES DES POINTS D'INTÉRÊT
-- ============================================

-- Ajouter les colonnes d'adresse
ALTER TABLE Pts_Interet 
ADD COLUMN IF NOT EXISTS rue_pts_interet VARCHAR(255) NULL,
ADD COLUMN IF NOT EXISTS id_commune INT NULL;

-- Ajouter la contrainte de clé étrangère
ALTER TABLE Pts_Interet 
ADD CONSTRAINT IF NOT EXISTS fk_pts_interet_commune 
FOREIGN KEY (id_commune) REFERENCES Commune(id_commune);

SELECT '✅ Colonnes d\'adresse ajoutées aux Points d\'Intérêt' as status;


-- ============================================
-- PARTIE 6: TABLE REVIEWS AVEC VALIDATION
-- ============================================

-- Créer la table Reviews si elle n'existe pas
CREATE TABLE IF NOT EXISTS Reviews (
  id_review INT AUTO_INCREMENT PRIMARY KEY,
  id_biens INT NOT NULL,
  id_locataire INT NULL,
  rating TINYINT NULL,
  content TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  validated BOOLEAN DEFAULT FALSE,
  validated_by INT NULL,
  validated_at TIMESTAMP NULL,
  FOREIGN KEY (id_biens) REFERENCES Biens(id_biens) ON DELETE CASCADE,
  FOREIGN KEY (validated_by) REFERENCES Animateur(id_animateur)
);

-- Ajouter un index pour optimiser les recherches par bien
ALTER TABLE Reviews ADD INDEX IF NOT EXISTS idx_bien (id_biens);

-- Si la table existe déjà, ajouter les colonnes de validation
ALTER TABLE Reviews ADD COLUMN IF NOT EXISTS validated BOOLEAN DEFAULT FALSE;
ALTER TABLE Reviews ADD COLUMN IF NOT EXISTS validated_by INT NULL;
ALTER TABLE Reviews ADD COLUMN IF NOT EXISTS validated_at TIMESTAMP NULL;

-- Ajouter la contrainte de clé étrangère pour validated_by
ALTER TABLE Reviews ADD CONSTRAINT IF NOT EXISTS fk_reviews_validated_by 
FOREIGN KEY (validated_by) REFERENCES Animateur(id_animateur);

SELECT '✅ Table Reviews créée avec système de validation' as status;


-- ============================================
-- PARTIE 7: COLONNES SUPPLÉMENTAIRES
-- ============================================

-- Ajouter la colonne hidden aux biens (si nécessaire)
ALTER TABLE Biens ADD COLUMN IF NOT EXISTS hidden BOOLEAN DEFAULT FALSE;

-- Ajouter des champs supplémentaires aux animateurs (si nécessaire)
ALTER TABLE Animateur ADD COLUMN IF NOT EXISTS phone_animateur VARCHAR(20) NULL;
ALTER TABLE Animateur ADD COLUMN IF NOT EXISTS address_animateur VARCHAR(255) NULL;

-- Ajouter le coût total aux réservations
ALTER TABLE Reservation ADD COLUMN IF NOT EXISTS total_cost DECIMAL(10,2) NULL;

SELECT '✅ Colonnes supplémentaires ajoutées' as status;


-- ============================================
-- PARTIE 8: TABLE SEMAINES INDISPONIBLES
-- ============================================

CREATE TABLE IF NOT EXISTS Semaine_Indisponible (
    id_semaine_indisponible INT AUTO_INCREMENT PRIMARY KEY,
    id_biens INT NOT NULL,
    semaine INT NOT NULL,
    annee INT NOT NULL,
    raison VARCHAR(255) NULL,
    FOREIGN KEY (id_biens) REFERENCES Biens(id_biens) ON DELETE CASCADE,
    UNIQUE KEY unique_semaine_bien (id_biens, semaine, annee)
);

SELECT '✅ Table Semaine_Indisponible créée' as status;


-- ============================================
-- PARTIE 9: MISE À JOUR DES BIENS EXISTANTS
-- ============================================

-- Réinitialiser la validation des biens sans validated_by
UPDATE Biens 
SET validated = FALSE, 
    validated_by = NULL, 
    validated_at = NULL
WHERE validated_by IS NULL;

SELECT '✅ Biens sans validateur réinitialisés' as status;


-- ============================================
-- PARTIE 10: ASSIGNATION DES PROPRIÉTAIRES
-- ============================================

-- Lister les locataires disponibles pour référence
SELECT 
    id_locataire,
    CONCAT(prenom_locataire, ' ', nom_locataire) as nom_complet,
    email_locataire
FROM Locataire
ORDER BY id_locataire;

-- ⚠️ IMPORTANT : Décommentez et modifiez l'ID du locataire si vous voulez assigner automatiquement
-- tous les biens sans propriétaire à un locataire spécifique

-- UPDATE Biens 
-- SET created_by_id = 1,
--     created_by_name = (SELECT CONCAT(prenom_locataire, ' ', nom_locataire) FROM Locataire WHERE id_locataire = 1)
-- WHERE created_by_id IS NULL;

SELECT '⚠️ Assignation des propriétaires : À faire manuellement' as status;


-- ============================================
-- PARTIE 11: TARIFS PAR DÉFAUT
-- ============================================

-- Créer une saison par défaut si elle n'existe pas
INSERT IGNORE INTO Saison (id_saison, lib_saison) VALUES (1, 'Basse saison');
INSERT IGNORE INTO Saison (id_saison, lib_saison) VALUES (2, 'Haute saison');

-- Note : Les tarifs doivent être ajoutés manuellement pour chaque bien

SELECT '✅ Saisons par défaut créées' as status;


-- ============================================
-- VÉRIFICATIONS FINALES
-- ============================================

-- Vérifier la structure des tables principales
DESCRIBE Biens;
DESCRIBE Pts_Interet;
DESCRIBE Reviews;

-- Statistiques globales
SELECT 
    'BIENS' as table_name,
    (SELECT COUNT(*) FROM Biens) as total,
    (SELECT COUNT(*) FROM Biens WHERE validated = TRUE) as valides,
    (SELECT COUNT(*) FROM Biens WHERE validated = FALSE) as en_attente,
    (SELECT COUNT(*) FROM Biens WHERE created_by_id IS NULL) as sans_proprietaire
UNION ALL
SELECT 
    'POINTS D\'INTÉRÊT' as table_name,
    (SELECT COUNT(*) FROM Pts_Interet) as total,
    (SELECT COUNT(*) FROM Pts_Interet WHERE rue_pts_interet IS NOT NULL) as avec_rue,
    (SELECT COUNT(*) FROM Pts_Interet WHERE id_commune IS NOT NULL) as avec_commune,
    NULL
UNION ALL
SELECT 
    'AVIS (REVIEWS)' as table_name,
    (SELECT COUNT(*) FROM Reviews) as total,
    (SELECT COUNT(*) FROM Reviews WHERE validated = TRUE) as valides,
    (SELECT COUNT(*) FROM Reviews WHERE validated = FALSE) as en_attente,
    NULL
UNION ALL
SELECT 
    'PRESTATIONS' as table_name,
    (SELECT COUNT(*) FROM Prestation) as total,
    NULL,
    NULL,
    NULL
UNION ALL
SELECT 
    'COMPOSITIONS' as table_name,
    (SELECT COUNT(*) FROM Compose) as total,
    NULL,
    NULL,
    NULL;

-- Lister les biens sans propriétaire
SELECT 
    b.id_biens,
    b.nom_biens,
    c.nom_commune,
    b.created_by_id,
    b.created_by_name
FROM Biens b
LEFT JOIN Commune c ON b.id_commune = c.id_commune
WHERE b.created_by_id IS NULL;


-- ============================================
-- RÉSUMÉ DES ACTIONS
-- ============================================

SELECT '
╔═══════════════════════════════════════════════════════════════╗
║                  MIGRATIONS TERMINÉES !                       ║
╠═══════════════════════════════════════════════════════════════╣
║                                                               ║
║  ✅ Colonnes created_by ajoutées aux Biens                    ║
║  ✅ Système de validation des biens activé                    ║
║  ✅ 20 prestations sportives/loisirs créées                   ║
║  ✅ Adresses ajoutées aux points d\'intérêt                    ║
║  ✅ Table Reviews avec validation créée                       ║
║  ✅ Colonnes supplémentaires ajoutées                         ║
║  ✅ Table Semaine_Indisponible créée                          ║
║                                                               ║
╠═══════════════════════════════════════════════════════════════╣
║                   ACTIONS À EFFECTUER                         ║
╠═══════════════════════════════════════════════════════════════╣
║                                                               ║
║  1️⃣  Assigner les propriétaires aux biens                     ║
║     → Décommentez la requête UPDATE en PARTIE 10             ║
║                                                               ║
║  2️⃣  Re-valider les biens via forms/validate_biens.php       ║
║                                                               ║
║  3️⃣  Reconfigurer les compositions pour chaque bien          ║
║     → Utiliser forms/Compose.form.php                        ║
║                                                               ║
║  4️⃣  Tester l\'autocomplétion dans PtsInteret.form.php        ║
║                                                               ║
║  5️⃣  Vider le cache du navigateur (Ctrl + Shift + R)         ║
║                                                               ║
╚═══════════════════════════════════════════════════════════════╝
' as RESUME;

SELECT '🎉 MIGRATIONS COMPLÈTES - PROJET HAP PRÊT !' as final_status;
