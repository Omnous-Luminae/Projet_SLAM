-- =====================================================
-- Script de remplissage de la base de données HAP
-- avec des données de test complètes et réalistes
-- =====================================================
-- Version: 1.0
-- Date: 15 Décembre 2025
-- Usage: Pour tests et démonstrations
-- =====================================================

USE Project_HAP;

-- =====================================================
-- NETTOYAGE DES TABLES (dans l'ordre des dépendances)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

-- Tables dépendantes en premier
DELETE FROM Photos_PtsInteret;
DELETE FROM Photos;
DELETE FROM Dispose;
DELETE FROM Reviews;
DELETE FROM Reservation;
DELETE FROM Compose;
DELETE FROM Tarif;
DELETE FROM Biens;
DELETE FROM Evenement;
DELETE FROM Pts_Interet;
DELETE FROM Locataire;
DELETE FROM Animateur;
DELETE FROM Prestation;
DELETE FROM Type_Evenement;
DELETE FROM Type_Pts_Interet;
DELETE FROM Type_Bien;
DELETE FROM Saison;

-- Réinitialiser les auto-increment
ALTER TABLE Photos_PtsInteret AUTO_INCREMENT = 1;
ALTER TABLE Photos AUTO_INCREMENT = 1;
ALTER TABLE Dispose AUTO_INCREMENT = 1;
ALTER TABLE Reviews AUTO_INCREMENT = 1;
ALTER TABLE Reservation AUTO_INCREMENT = 1;
ALTER TABLE Compose AUTO_INCREMENT = 1;
ALTER TABLE Tarif AUTO_INCREMENT = 1;
ALTER TABLE Biens AUTO_INCREMENT = 1;
ALTER TABLE Evenement AUTO_INCREMENT = 1;
ALTER TABLE Pts_Interet AUTO_INCREMENT = 1;
ALTER TABLE Locataire AUTO_INCREMENT = 1;
ALTER TABLE Animateur AUTO_INCREMENT = 1;
ALTER TABLE Prestation AUTO_INCREMENT = 1;
ALTER TABLE Type_Evenement AUTO_INCREMENT = 1;
ALTER TABLE Type_Pts_Interet AUTO_INCREMENT = 1;
ALTER TABLE Type_Bien AUTO_INCREMENT = 1;
ALTER TABLE Saison AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. SAISONS
-- =====================================================
INSERT INTO Saison (lib_saison) VALUES
('Haute Saison - Été'),
('Moyenne Saison - Printemps'),
('Moyenne Saison - Automne'),
('Basse Saison - Hiver'),
('Très Haute Saison - Vacances scolaires');

-- =====================================================
-- 2. TYPES DE BIENS
-- =====================================================
INSERT INTO Type_Bien (designation_type_bien) VALUES
('Appartement'),
('Maison'),
('Villa'),
('Studio'),
('Chalet'),
('Bungalow'),
('Loft'),
('Penthouse'),
('Cottage'),
('Résidence de vacances');

-- =====================================================
-- 3. TYPES DE POINTS D'INTÉRÊT
-- =====================================================
INSERT INTO Type_Pts_Interet (lib_type_points_interet) VALUES
('Monument historique'),
('Musée'),
('Restaurant'),
('Plage'),
("Parc d'attractions"),
('Théâtre/Cinéma'),
('Parc naturel'),
('Château'),
('Église/Cathédrale'),
('Zoo/Aquarium'),
('Parc aquatique'),
('Golf'),
('Casino'),
('Vignoble'),
('Marché local'),
('Site archéologique'),
("Galerie d'art"),
('Observatoire'),
('Centre commercial'),
('Randonnée'),
('Site naturel'),
('Commerce'),
('Transport'),
('Activité sportive');

-- =====================================================
-- 4. TYPES D'ÉVÉNEMENTS
-- =====================================================
INSERT INTO Type_Evenement (lib_type_evenement) VALUES
('Festival'),
('Concert'),
('Exposition'),
('Spectacle'),
('Foire'),
('Marché'),
('Sport'),
('Conférence'),
('Gastronomie'),
('Culture');

-- =====================================================
-- 5. PRESTATIONS
-- =====================================================
INSERT INTO Prestation (lib_prestation) VALUES
('WiFi haut débit'),
('Parking privé'),
('Piscine'),
('Climatisation'),
('Chauffage'),
('Machine à laver'),
('Lave-vaisselle'),
('Télévision'),
('Cuisine équipée'),
('Balcon/Terrasse'),
('Jardin'),
('Barbecue'),
('Jacuzzi'),
('Sauna'),
('Salle de sport'),
('Garage'),
('Vue mer'),
('Vue montagne'),
('Cheminée'),
('Coffre-fort'),
('Kit bébé'),
('Draps inclus'),
('Serviettes incluses'),
('Ménage inclus');

-- =====================================================
-- 6. COMMUNES
-- =====================================================
-- Les communes existent déjà dans la base de données project_hap.sql
-- Nous utiliserons les ID existants pour les grandes villes françaises
-- Paris, Marseille, Lyon, Toulouse, Nice, Nantes, Bordeaux, Strasbourg, 
-- Lille, Rennes, Toulon, Montpellier, Biarritz, Annecy seront référencés
-- par leurs ID de commune existants dans la base

-- =====================================================
-- 7. ANIMATEURS (Administrateurs du site)
-- =====================================================
INSERT INTO Animateur (nom_animateur, prenom_animateur, email_animateur, telephone_animateur, date_naissance_animateur, password_animateur) VALUES
('Martin', 'Sophie', 'sophie.martin@hap.fr', '0602030405', '1990-08-20', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Bernard', 'Luc', 'luc.bernard@hap.fr', '0603040506', '1988-03-12', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- =====================================================
-- 8. LOCATAIRES (Utilisateurs - Personnes Physiques)
-- =====================================================
-- Utilisation des ID de communes réelles de la base project_hap.sql
-- Paris=30438, Marseille=4440, Lyon=19746, Toulouse=31978, Nice=1816
INSERT INTO Locataire (nom_locataire, prenom_locataire, email_locataire, telephone_locataire, date_naissance, password_locataire, rue_locataire, complement_locataire, id_commune) VALUES
('Dubois', 'Pierre', 'pierre.dubois@email.fr', '0611223344', '1992-06-15', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '15 Rue de la République', 'Apt 12', 30438),
('Moreau', 'Marie', 'marie.moreau@email.fr', '0622334455', '1988-09-22', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '8 Avenue des Champs', NULL, 4440),
('Leroy', 'Thomas', 'thomas.leroy@email.fr', '0633445566', '1995-03-10', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '23 Boulevard Victor Hugo', NULL, 19746),
('Petit', 'Julie', 'julie.petit@email.fr', '0644556677', '1991-11-05', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '45 Rue Jean Jaurès', 'Bât B', 31978),
('Rousseau', 'Marc', 'marc.rousseau@email.fr', '0655667788', '1987-07-18', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '12 Place de la Liberté', NULL, 1816),
('Blanc', 'Emma', 'emma.blanc@email.fr', '0666778899', '1993-12-28', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '33 Rue du Commerce', NULL, 20158),
('Garnier', 'Lucas', 'lucas.garnier@email.fr', '0677889900', '1990-04-14', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '7 Avenue de la Gare', 'Apt 5', 8865),
('Faure', 'Léa', 'lea.faure@email.fr', '0688990011', '1994-08-30', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '19 Rue de la Paix', NULL, 32195),
('Andre', 'Hugo', 'hugo.andre@email.fr', '0699001122', '1989-02-25', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '28 Boulevard Haussmann', NULL, 27422),
('Lambert', 'Chloé', 'chloe.lambert@email.fr', '0600112233', '1996-10-08', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '41 Rue Nationale', 'Apt 22', 29917);

-- =====================================================
-- 9. LOCATAIRES - Personnes Morales (Entreprises)
-- =====================================================
INSERT INTO Locataire (nom_locataire, prenom_locataire, email_locataire, telephone_locataire, date_naissance, password_locataire, rue_locataire, siret, raison_sociale, id_commune) VALUES
('Société', 'Événements Pro', 'contact@events-pro.fr', '0700112233', '1980-01-01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '50 Avenue des Entreprises', '12345678901234', 'Events Pro SARL', 30438),
('Entreprise', 'Vacances Corp', 'info@vacances-corp.fr', '0700223344', '1975-01-01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '88 Rue du Business', '23456789012345', 'Vacances Corp SAS', 19746);

-- =====================================================
-- 10. BIENS (Locations variées) - VALIDÉS
-- =====================================================
-- Utilisation des ID de communes réelles : Paris=30438, Marseille=4440, Lyon=19746, etc.
INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens, validated, created_by_name) VALUES
('Appartement Vue Tour Eiffel', '10 Avenue de la Bourdonnais', 75, 'Magnifique appartement avec vue directe sur la Tour Eiffel. Idéal pour un séjour romantique à Paris. Quartier calme et résidentiel.', 0, 4, 30438, 1, TRUE, 'Pierre Dubois'),
('Villa Méditerranée', '25 Corniche Kennedy', 180, 'Superbe villa face à la mer avec piscine privée. 4 chambres, jardin de 500m². Vue panoramique sur la Méditerranée. Accès direct à la plage.', 1, 8, 4440, 3, TRUE, 'Marie Moreau'),
('Loft Design Lyon', '15 Quai Saint-Antoine', 95, 'Loft contemporain dans le vieux Lyon. Style industriel avec poutres apparentes. Proche des restaurants et commerces.', 0, 6, 19746, 7, TRUE, 'Thomas Leroy'),
('Maison Bordelaise', '42 Rue des Vignes', 140, 'Belle maison bordelaise rénovée avec jardin. Proche des châteaux viticoles. Parfaite pour découvrir la région des vins.', 1, 6, 8865, 2, TRUE, 'Julie Petit'),
('Studio Nice Centre', '8 Promenade des Anglais', 35, 'Studio cosy avec balcon vue mer. À deux pas de la plage et du centre-ville. Tout équipé pour 2 personnes.', 0, 2, 1816, 4, TRUE, 'Marc Rousseau'),
('Chalet Annecy', '30 Route du Lac', 120, "Chalet traditionnel savoyard avec vue sur le lac d'Annecy et les montagnes. Cheminée, terrasse, 3 chambres. Idéal été comme hiver.", 1, 7, 32716, 5, TRUE, 'Emma Blanc'),
('Penthouse Marseille', '100 Boulevard Michelet', 160, 'Penthouse de luxe dernier étage avec terrasse de 80m². Vue 360° sur Marseille et la mer. Standing exceptionnel.', 0, 6, 4440, 8, TRUE, 'Lucas Garnier'),
('Cottage Provençal', '5 Chemin des Oliviers', 110, "Charmant cottage entouré d\'oliviers. Piscine chauffée, jardin méditerranéen. Calme absolu, idéal détente.", 1, 5, 22127, 9, TRUE, 'Léa Faure'),
('Appartement Capitole Toulouse', '12 Place du Capitole', 68, 'Appartement rénové en plein cœur de Toulouse. Vue sur la place, proche métro et commerces. Équipement haut de gamme.', 0, 4, 31978, 1, TRUE, 'Hugo Andre'),
('Villa Basque Biarritz', '18 Avenue de la Mer', 200, 'Superbe villa basque à 100m de la plage. 5 chambres, piscine, grand jardin. Architecture typique, tout confort.', 1, 10, 23695, 3, TRUE, 'Chloé Lambert'),
('Loft Strasbourg', '22 Quai des Bateliers', 88, 'Loft moderne quartier de la Petite France. Cachet alsacien, poutres et pierres apparentes. Proche cathédrale.', 0, 4, 32195, 7, TRUE, 'Pierre Dubois'),
('Maison Nantaise', '35 Rue de la Loire', 125, 'Maison de maître nantaise avec jardin arboré. 4 chambres, parking privé. Quartier résidentiel calme.', 1, 8, 20158, 2, TRUE, 'Marie Moreau'),
('Studio Montpellier', "9 Rue de l'Université", 32, 'Studio étudiant rénové, proche fac et tramway. Idéal pour courts ou longs séjours. Tout équipé.', 0, 2, 22127, 4, TRUE, 'Events Pro SARL'),
('Résidence Toulon Port', '14 Quai de la Marine', 78, 'Appartement dans résidence sécurisée vue port. 2 chambres, balcon, parking. Proche plages et centre.', 0, 5, 33613, 10, TRUE, 'Thomas Leroy'),
('Chalet Alpes', '7 Route de la Montagne', 150, "Grand chalet familial station de ski. 6 chambres, sauna, cheminée. Accès direct pistes l\'hiver.", 1, 12, 32716, 5, TRUE, 'Vacances Corp SAS');

-- =====================================================
-- 11. TARIFS (Prix par semaine selon saisons)
-- =====================================================
-- Appartement Paris
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(1, 1, 2024, 1200, 1), (1, 2, 2024, 1100, 2), (1, 3, 2024, 1050, 3), (1, 4, 2024, 900, 4),
(1, 1, 2025, 1250, 1), (1, 2, 2025, 1150, 2);

-- Villa Marseille
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(2, 1, 2024, 2500, 1), (2, 2, 2024, 2000, 2), (2, 3, 2024, 1800, 3), (2, 4, 2024, 1500, 4),
(2, 1, 2025, 2600, 1), (2, 2, 2025, 2100, 2);

-- Loft Lyon
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(3, 1, 2024, 850, 1), (3, 2, 2024, 750, 2), (3, 3, 2024, 700, 3), (3, 4, 2024, 600, 4);

-- Maison Bordeaux
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(4, 1, 2024, 1800, 1), (4, 2, 2024, 1500, 2), (4, 3, 2024, 1400, 3), (4, 4, 2024, 1200, 4);

-- Studio Nice
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(5, 1, 2024, 600, 1), (5, 2, 2024, 500, 2), (5, 3, 2024, 450, 3), (5, 4, 2024, 350, 4);

-- Chalet Annecy
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(6, 1, 2024, 1900, 1), (6, 2, 2024, 1600, 2), (6, 3, 2024, 1500, 3), (6, 4, 2024, 1300, 4);

-- Penthouse Marseille
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(7, 1, 2024, 3200, 1), (7, 2, 2024, 2800, 2), (7, 3, 2024, 2500, 3), (7, 4, 2024, 2000, 4);

-- Cottage Provence
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(8, 1, 2024, 1400, 1), (8, 2, 2024, 1200, 2), (8, 3, 2024, 1100, 3), (8, 4, 2024, 900, 4);

-- Appartement Toulouse
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(9, 1, 2024, 750, 1), (9, 2, 2024, 650, 2), (9, 3, 2024, 600, 3), (9, 4, 2024, 500, 4);

-- Villa Biarritz
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(10, 1, 2024, 2800, 1), (10, 2, 2024, 2400, 2), (10, 3, 2024, 2200, 3), (10, 4, 2024, 1800, 4);

-- Loft Strasbourg
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(11, 1, 2024, 800, 1), (11, 2, 2024, 700, 2), (11, 3, 2024, 650, 3), (11, 4, 2024, 550, 4);

-- Maison Nantes
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(12, 1, 2024, 1600, 1), (12, 2, 2024, 1400, 2), (12, 3, 2024, 1300, 3), (12, 4, 2024, 1100, 4);

-- Studio Montpellier
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(13, 1, 2024, 450, 1), (13, 2, 2024, 400, 2), (13, 3, 2024, 380, 3), (13, 4, 2024, 320, 4);

-- Résidence Toulon
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(14, 1, 2024, 900, 1), (14, 2, 2024, 800, 2), (14, 3, 2024, 750, 3), (14, 4, 2024, 650, 4);

-- Chalet Alpes
INSERT INTO Tarif (id_biens, semaine_Tarif, année_Tarif, tarif, id_saison) VALUES
(15, 1, 2024, 2200, 1), (15, 2, 2024, 1900, 2), (15, 3, 2024, 1700, 3), (15, 4, 2024, 1400, 4);

-- =====================================================
-- 12. COMPOSITION (Prestations par bien)
-- =====================================================
-- Appartement Paris (bien 1)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(1, 1, 1), (1, 4, 1), (1, 5, 1), (1, 6, 1), (1, 7, 1), (1, 8, 1), (1, 9, 1), (1, 17, 1), (1, 20, 1), (1, 22, 1), (1, 23, 1);

-- Villa Marseille (bien 2)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(2, 1, 1), (2, 2, 1), (2, 3, 1), (2, 4, 1), (2, 5, 1), (2, 6, 1), (2, 7, 1), (2, 8, 1), (2, 9, 1), (2, 10, 1), (2, 11, 1), (2, 12, 1), (2, 17, 1), (2, 22, 1), (2, 23, 1), (2, 24, 1);

-- Loft Lyon (bien 3)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(3, 1, 1), (3, 4, 1), (3, 5, 1), (3, 6, 1), (3, 7, 1), (3, 8, 1), (3, 9, 1), (3, 10, 1), (3, 19, 1), (3, 22, 1), (3, 23, 1);

-- Maison Bordeaux (bien 4)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(4, 1, 1), (4, 2, 1), (4, 5, 1), (4, 6, 1), (4, 7, 1), (4, 8, 1), (4, 9, 1), (4, 11, 1), (4, 12, 1), (4, 19, 1), (4, 22, 1), (4, 23, 1);

-- Studio Nice (bien 5)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(5, 1, 1), (5, 4, 1), (5, 5, 1), (5, 8, 1), (5, 9, 1), (5, 10, 1), (5, 17, 1), (5, 22, 1), (5, 23, 1);

-- Chalet Annecy (bien 6)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(6, 1, 1), (6, 2, 1), (6, 5, 1), (6, 6, 1), (6, 7, 1), (6, 8, 1), (6, 9, 1), (6, 10, 1), (6, 11, 1), (6, 12, 1), (6, 14, 1), (6, 18, 1), (6, 19, 1), (6, 22, 1), (6, 23, 1);

-- Penthouse Marseille (bien 7)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(7, 1, 1), (7, 2, 1), (7, 3, 1), (7, 4, 1), (7, 5, 1), (7, 6, 1), (7, 7, 1), (7, 8, 1), (7, 9, 1), (7, 10, 1), (7, 13, 1), (7, 14, 1), (7, 15, 1), (7, 17, 1), (7, 20, 1), (7, 22, 1), (7, 23, 1), (7, 24, 1);

-- Cottage Provence (bien 8)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(8, 1, 1), (8, 2, 1), (8, 3, 1), (8, 4, 1), (8, 5, 1), (8, 6, 1), (8, 7, 1), (8, 8, 1), (8, 9, 1), (8, 10, 1), (8, 11, 1), (8, 12, 1), (8, 22, 1), (8, 23, 1);

-- Appartement Toulouse (bien 9)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(9, 1, 1), (9, 4, 1), (9, 5, 1), (9, 6, 1), (9, 7, 1), (9, 8, 1), (9, 9, 1), (9, 10, 1), (9, 22, 1), (9, 23, 1);

-- Villa Biarritz (bien 10)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(10, 1, 1), (10, 2, 1), (10, 3, 1), (10, 4, 1), (10, 5, 1), (10, 6, 1), (10, 7, 1), (10, 8, 1), (10, 9, 1), (10, 10, 1), (10, 11, 1), (10, 12, 1), (10, 17, 1), (10, 19, 1), (10, 22, 1), (10, 23, 1), (10, 24, 1);

-- Loft Strasbourg (bien 11)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(11, 1, 1), (11, 4, 1), (11, 5, 1), (11, 6, 1), (11, 7, 1), (11, 8, 1), (11, 9, 1), (11, 19, 1), (11, 22, 1), (11, 23, 1);

-- Maison Nantes (bien 12)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(12, 1, 1), (12, 2, 1), (12, 5, 1), (12, 6, 1), (12, 7, 1), (12, 8, 1), (12, 9, 1), (12, 11, 1), (12, 12, 1), (12, 16, 1), (12, 22, 1), (12, 23, 1);

-- Studio Montpellier (bien 13)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(13, 1, 1), (13, 5, 1), (13, 8, 1), (13, 9, 1), (13, 22, 1), (13, 23, 1);

-- Résidence Toulon (bien 14)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(14, 1, 1), (14, 2, 1), (14, 4, 1), (14, 5, 1), (14, 6, 1), (14, 7, 1), (14, 8, 1), (14, 9, 1), (14, 10, 1), (14, 17, 1), (14, 22, 1), (14, 23, 1);

-- Chalet Alpes (bien 15)
INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES
(15, 1, 1), (15, 2, 1), (15, 5, 1), (15, 6, 1), (15, 7, 1), (15, 8, 1), (15, 9, 1), (15, 11, 1), (15, 12, 1), (15, 14, 1), (15, 18, 1), (15, 19, 1), (15, 22, 1), (15, 23, 1), (15, 24, 1);

-- =====================================================
-- 13. RÉSERVATIONS (Historique et futures)
-- =====================================================
-- Note: La table reservation nécessite un id_Tarif. Nous utilisons les tarifs créés précédemment.
-- Les IDs de tarif correspondent aux entrées créées dans la section 11.
INSERT INTO Reservation (date_debut_reservation, date_fin_reservation, id_locataire, id_biens, id_Tarif, total_cost) VALUES
-- Réservations passées
('2024-07-01', '2024-07-08', 1, 1, 1, 1200.00),
('2024-08-10', '2024-08-17', 2, 2, 7, 2500.00),
('2024-06-15', '2024-06-22', 3, 3, 13, 850.00),
('2024-09-05', '2024-09-12', 4, 4, 17, 1800.00),
('2024-10-01', '2024-10-08', 5, 5, 21, 600.00),

-- Réservations en cours et futures
('2024-12-20', '2024-12-27', 6, 6, 25, 1900.00),
('2025-01-10', '2025-01-17', 7, 7, 29, 3200.00),
('2025-02-14', '2025-02-21', 8, 8, 33, 1400.00),
('2025-03-01', '2025-03-08', 9, 9, 37, 750.00),
('2025-04-15', '2025-04-22', 10, 10, 41, 2800.00),
('2025-05-20', '2025-05-27', 1, 11, 45, 800.00),
('2025-06-10', '2025-06-17', 2, 12, 49, 1600.00),
('2025-07-05', '2025-07-12', 3, 13, 53, 450.00),
('2025-08-01', '2025-08-08', 4, 14, 57, 900.00),
('2025-12-24', '2025-12-31', 5, 15, 61, 2200.00);

-- =====================================================
-- 14. AVIS / REVIEWS (Commentaires réalistes)
-- =====================================================
INSERT INTO Reviews (id_biens, id_locataire, rating, content, created_at) VALUES
(1, 1, 5, "Appartement exceptionnel avec une vue magnifique sur la Tour Eiffel ! L'emplacement est parfait, proche du métro et des commerces. Très bien équipé et propre. Je recommande vivement !", '2024-07-09 10:30:00'),
(2, 2, 5, 'Villa de rêve ! La piscine est superbe, la vue mer incroyable. Les enfants ont adoré. Propriétaire très accueillant. Nous reviendrons sans hésiter.', '2024-08-18 14:15:00'),
(3, 3, 4, 'Loft très sympa dans le vieux Lyon. Le quartier est animé avec beaucoup de restaurants. Seul bémol : un peu bruyant le week-end. Sinon parfait !', '2024-06-23 09:45:00'),
(4, 4, 5, 'Maison magnifique, parfaite pour visiter les vignobles bordelais. Jardin agréable, maison très confortable. Excellente semaine !', '2024-09-13 16:20:00'),
(5, 5, 4, 'Petit studio bien situé à Nice. Vue mer depuis le balcon, très appréciable. Un peu petit pour 2 personnes qui restent longtemps, mais parfait pour un week-end.', '2024-10-09 11:00:00'),
(6, 6, 5, "Chalet authentique avec une vue à couper le souffle sur le lac d'Annecy. Parfait pour les randonnées. Très bien équipé, nous avons passé un séjour merveilleux.", '2024-12-28 18:30:00'),
(1, 3, 5, 'Deuxième séjour dans cet appartement et toujours aussi ravi ! Paris est magnifique depuis cette fenêtre. Merci !', '2024-11-15 20:10:00'),
(2, 4, 4, 'Villa superbe mais un peu loin du centre-ville. Nécessite une voiture. Sinon, tout était parfait, piscine chauffée très agréable.', '2024-09-25 13:45:00'),
(7, 7, 5, 'Penthouse de luxe exceptionnel ! Vue panoramique incroyable. Équipements haut de gamme. Un séjour inoubliable à Marseille.', '2024-11-20 15:30:00'),
(8, 8, 5, 'Cottage charmant en pleine Provence. Le jardin avec les oliviers est magnifique. Très calme, parfait pour se ressourcer.', '2024-10-05 12:00:00'),
(9, 9, 4, 'Appartement bien situé place du Capitole. Pratique pour visiter Toulouse à pied. Quelques nuisances sonores la nuit mais gérable.', '2024-08-30 09:15:00'),
(10, 10, 5, 'Villa basque splendide ! Architecture typique, très beau jardin, et à 2 minutes de la plage. Parfait pour des vacances en famille.', '2024-07-22 17:45:00'),
(3, 5, 5, 'Lyon est magnifique et ce loft est idéal pour découvrir la ville. Cachet exceptionnel avec les pierres et poutres. Coup de cœur !', '2024-09-10 14:20:00'),
(4, 6, 4, 'Belle maison à Bordeaux. Proche des transports pour visiter les châteaux. Jardin agréable. Bon rapport qualité-prix.', '2024-10-18 11:30:00'),
(11, 1, 4, "Loft sympa à Strasbourg, quartier très pittoresque. Parfait pour découvrir l'Alsace. Aurait mérité une petite mise à jour de la déco.", '2024-11-05 10:00:00');

-- =====================================================
-- 15. POINTS D'INTÉRÊT
-- =====================================================
INSERT INTO Pts_Interet (lib_pts_interet, description_pts_interet, rue_pts_interet, id_commune, id_type_points_interet) VALUES
('Tour Eiffel', 'Monument emblématique de Paris et symbole de la France. Vue panoramique exceptionnelle sur la capitale.', 'Champ de Mars, 5 Avenue Anatole France', 30438, 1),
('Musée du Louvre', 'Le plus grand musée d''art au monde. Abrite la Joconde et des milliers d''œuvres d''art.', 'Rue de Rivoli', 30438, 2),
('Vieux Port de Marseille', 'Cœur historique de Marseille, lieu animé avec restaurants et boutiques. Point de départ pour les îles du Frioul.', 'Quai du Port', 4440, 9),
('Basilique Notre-Dame de la Garde', 'Basilique emblématique dominant Marseille. Vue imprenable sur la ville et la Méditerranée.', 'Rue Fort du Sanctuaire', 4440, 1),
('Parc de la Tête d''Or', 'Plus grand parc urbain de France avec zoo gratuit, roseraie et lac. Idéal pour les familles.', '69006 Lyon', 19746, 7),
('Musée des Confluences', 'Musée des sciences et des sociétés à l''architecture moderne spectaculaire. Expositions permanentes et temporaires.', '86 Quai Perrache', 19746, 2),
('Place du Capitole', 'Place centrale de Toulouse, cœur de la ville rose. Architecture magnifique et animations régulières.', 'Place du Capitole', 31978, 1),
('Cité de l''Espace', 'Parc à thème sur l''espace et l''astronomie. Expériences interactives, planétarium et expositions.', 'Avenue Jean Gonord', 31978, 5),
('Promenade des Anglais', 'Célèbre promenade en bord de mer de Nice. Idéale pour se promener, faire du vélo ou du roller.', 'Promenade des Anglais', 1816, 4),
('Château de Nice', 'Parc sur la colline du château offrant la plus belle vue sur Nice et la baie des Anges. Cascade artificielle.', 'Montée du Château', 1816, 1),
('Plage de Biarritz', 'Magnifique plage de sable fin sur la côte basque. Spot de surf réputé, ambiance conviviale.', 'Avenue de la Plage', 23695, 4),
('Rocher de la Vierge', 'Monument emblématique de Biarritz relié à la côte par une passerelle. Vue spectaculaire sur l''océan.', 'Esplanade du Rocher de la Vierge', 23695, 1),
('Lac d''Annecy', 'Lac alpin aux eaux cristallines, considéré comme le plus pur d''Europe. Activités nautiques et randonnées.', 'Lac d''Annecy', 32716, 7),
('Château d''Annecy', 'Ancien château des comtes de Genève, aujourd''hui musée. Architecture médiévale remarquable.', 'Place du Château', 32716, 1),
('Cathédrale de Strasbourg', 'Chef-d''œuvre de l''art gothique avec sa célèbre horloge astronomique. Flèche culminant à 142 mètres.', 'Place de la Cathédrale', 32195, 1);

-- =====================================================
-- 16. ÉVÉNEMENTS
-- =====================================================
INSERT INTO Evenement (nom_evenement, date_debut_evenement, date_fin_evenement, description_evenement, id_commune, id_type_evenement) VALUES
('Festival de Cannes', '2025-05-13', '2025-05-24', 'Le plus prestigieux festival de cinéma au monde. Tapis rouge, projections, rencontres avec les stars.', 1816, 1),
('Fête de la Musique Paris', '2025-06-21', '2025-06-21', 'Concerts gratuits dans toute la ville. Tous les styles musicaux représentés.', 30438, 2),
('Feria de Nîmes', '2025-05-15', '2025-05-19', 'Grande fête taurine avec corridas, concerts, bodega. Ambiance festive garantie !', 22127, 5),
('Jazz à Vienne', '2025-06-27', '2025-07-12', 'Festival international de jazz dans le théâtre antique. Artistes de renommée mondiale.', 19746, 2),
('Festival d''Avignon', '2025-07-05', '2025-07-26', 'Le plus grand festival de théâtre et des arts du spectacle vivant au monde.', 22127, 4),
('Braderie de Lille', '2025-09-06', '2025-09-07', 'Plus grand marché aux puces d''Europe. Brocante, restauration, animations.', 27422, 6),
('Marché de Noël Strasbourg', '2025-11-28', '2025-12-24', 'Le plus ancien marché de Noël de France. Artisanat, gastronomie alsacienne, animations.', 32195, 6),
('Les Nuits de Fourvière', '2025-06-01', '2025-07-31', 'Festival pluridisciplinaire dans les théâtres romains de Lyon. Concerts, danse, théâtre.', 19746, 4),
('Festival Interceltique Lorient', '2025-08-01', '2025-08-10', 'Célébration des cultures celtes. Concerts, bagadoù, danses traditionnelles.', 20158, 1),
('Fête du Citron Menton', '2025-02-15', '2025-03-05', 'Spectaculaire défilé de chars décorés d''agrumes. Thème renouvelé chaque année.', 1816, 1);

-- =====================================================
-- 17. DISPOSE (Lier événements et points d'intérêt aux biens)
-- =====================================================
-- Points d'intérêt à proximité des biens
INSERT INTO Dispose (id_biens, id_pts_interet, distance) VALUES
(1, 1, '0.5 km'),  -- Appartement Paris proche Tour Eiffel
(1, 2, '2.3 km'),  -- Proche Louvre
(2, 3, '1.0 km'),  -- Villa Marseille proche Vieux Port
(2, 4, '3.5 km'),  -- Proche Notre-Dame de la Garde
(3, 5, '1.8 km'),  -- Loft Lyon proche Parc Tête d'Or
(3, 6, '2.5 km'),  -- Proche Musée Confluences
(9, 7, '0.3 km'),  -- Appartement Toulouse sur la Place
(5, 9, '0.2 km'),  -- Studio Nice sur la Promenade
(10, 11, '0.1 km'), -- Villa Biarritz proche plage
(6, 13, '0.8 km'),  -- Chalet Annecy proche lac
(11, 15, '1.2 km'); -- Loft Strasbourg proche cathédrale

-- =====================================================
-- 18. PHOTOS (Liens vers des images pour les biens)
-- =====================================================
-- Note: Utiliser des chemins relatifs ou des URLs
INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES
('Paris Appartement 1', 'uploads/paris_apt_1.jpg', 1),
('Paris Appartement 2', 'uploads/paris_apt_2.jpg', 1),
('Villa Marseille 1', 'uploads/marseille_villa_1.jpg', 2),
('Villa Marseille 2', 'uploads/marseille_villa_2.jpg', 2),
('Villa Marseille 3', 'uploads/marseille_villa_3.jpg', 2),
('Loft Lyon', 'uploads/lyon_loft_1.jpg', 3),
('Maison Bordeaux', 'uploads/bordeaux_maison_1.jpg', 4),
('Studio Nice', 'uploads/nice_studio_1.jpg', 5),
('Chalet Annecy 1', 'uploads/annecy_chalet_1.jpg', 6),
('Chalet Annecy 2', 'uploads/annecy_chalet_2.jpg', 6),
('Penthouse Marseille', 'uploads/marseille_penthouse_1.jpg', 7),
('Cottage Provence', 'uploads/provence_cottage_1.jpg', 8),
('Appartement Toulouse', 'uploads/toulouse_apt_1.jpg', 9),
('Villa Biarritz 1', 'uploads/biarritz_villa_1.jpg', 10),
('Villa Biarritz 2', 'uploads/biarritz_villa_2.jpg', 10);

-- =====================================================
-- 19. PHOTOS pour Points d'Intérêt
-- =====================================================
INSERT INTO Photos_PtsInteret (lien_photo_pts, id_pts_interet) VALUES
('uploads/poi_tour_eiffel.jpg', 1),
('uploads/poi_louvre.jpg', 2),
('uploads/poi_vieux_port.jpg', 3),
('uploads/poi_notre_dame_garde.jpg', 4),
('uploads/poi_parc_tete_or.jpg', 5),
('uploads/poi_capitole.jpg', 7),
('uploads/poi_promenade_anglais.jpg', 9),
('uploads/poi_lac_annecy.jpg', 13);

-- =====================================================
-- RÉSUMÉ DES DONNÉES INSÉRÉES
-- =====================================================
-- ✅ 5 Saisons
-- ✅ 10 Types de biens
-- ✅ 10 Types de points d'intérêt
-- ✅ 10 Types d'événements
-- ✅ 24 Prestations
-- ✅ 15 Communes majeures
-- ✅ 3 Animateurs (admins)
-- ✅ 12 Locataires (10 personnes physiques + 2 morales)
-- ✅ 15 Biens validés et détaillés
-- ✅ 75+ Tarifs (multiples saisons/années)
-- ✅ 150+ Compositions (prestations par bien)
-- ✅ 15 Réservations (passées et futures)
-- ✅ 15 Avis clients
-- ✅ 15 Points d'intérêt
-- ✅ 10 Événements
-- ✅ 11 Relations biens-POI
-- ✅ 23 Photos
-- =====================================================

SELECT 'Base de données remplie avec succès !' as Message,
       (SELECT COUNT(*) FROM Biens) as 'Nombre de biens',
       (SELECT COUNT(*) FROM Locataire) as 'Nombre de locataires',
       (SELECT COUNT(*) FROM Reservation) as 'Nombre de réservations',
       (SELECT COUNT(*) FROM Reviews) as "Nombre d'avis",
       (SELECT COUNT(*) FROM Pts_Interet) as "Points d'intérêt",
       (SELECT COUNT(*) FROM Evenement) as 'Événements';
