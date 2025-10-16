DROP DATABASE IF EXISTS Project_HAP;
CREATE DATABASE Project_HAP;
USE Project_HAP;

-- Table Commune adaptée pour toutes les villes de France
CREATE TABLE IF NOT EXISTS Commune(
    id_commune INT PRIMARY KEY AUTO_INCREMENT,
    code_insee VARCHAR(5) NOT NULL,           -- Ajout du code INSEE
    nom_commune VARCHAR(255) NOT NULL,
    cp_commune VARCHAR(10) NOT NULL,           -- Taille augmentée pour gérer les cas particuliers
    latitude_commune FLOAT NOT NULL,
    longitude_commune FLOAT NOT NULL,
    ville_slug VARCHAR(255),
    ville_nom_reel VARCHAR(255),
    ville_nom_soundex VARCHAR(255),
    ville_nom_metaphone VARCHAR(255),
    ville_departement VARCHAR(3),
    ville_arrondissement INT,
    ville_canton VARCHAR(10),
    ville_code_commune VARCHAR(5),
    ville_commune VARCHAR(5),
    ville_surface FLOAT,
    ville_zmin INT,
    ville_zmax INT,
    UNIQUE (code_insee)                        -- Pour éviter les doublons
);

-- Creation des tables  

CREATE TABLE IF NOT EXISTS Saison(
    id_saison int primary key auto_increment not null,
    lib_saison varchar(255) not null
);

Create table if not exists Type_Pts_Interet(
    id_type_points_interet int primary key auto_increment not null,
    lib_type_points_interet varchar(50) not null
);

Create table if not exists Type_Evenement(
    id_type_evenement int primary key auto_increment not null,
    lib_type_evenement varchar(50) not null
);

CREATE TABLE IF NOT EXISTS Type_Bien(
    id_type_biens int primary key auto_increment not null,
    designation_type_bien varchar(255) NOT null
);

CREATE TABLE IF NOT EXISTS Prestation(
    id_prestation int primary key auto_increment,
    lib_prestation varchar(255)
);


Create table if not exists Locataire(
    id_locataire int primary key auto_increment not null,
    nom_locataire varchar(30) not null,
    prenom_locataire varchar(30) not null,
    date_naissance date not null,
    email_locataire varchar(50) not null,
    password_locataire varchar(255) not null,
    telephone_locataire varchar(15) not null,
    rue_locataire varchar(50) not null,
    complement_locataire varchar(50),
    raison_sociale varchar(50),
    siret varchar(14),
    id_commune int not null,
    foreign key (id_commune) references Commune(id_commune)
);

Create table if not exists Biens(
    id_biens int primary key auto_increment not null,
    nom_biens varchar(50) not null,
    rue_biens varchar(50) not null,
    superficie_biens int not null,
    description_biens text not null,
    animal_biens boolean not null,
    nb_couchage int not null,
    id_commune int not null,
    id_type_biens int not null,
    foreign key (id_commune) references Commune(id_commune),
    foreign key (id_type_biens) references Type_Bien(id_type_biens)
);

Create table if not exists Tarif(
    id_Tarif int primary key auto_increment not null,
    semaine_Tarif decimal(10,2) not null,
    année_Tarif int not null,
    tarif float not null,
    id_saison int not null,
    id_biens int not null,
    foreign key (id_biens) references Biens(id_Biens),
    foreign key (id_saison) references Saison(id_saison)
);


Create table if not exists Reservation(
    id_reservation int primary key auto_increment not null,
    date_debut_reservation date not null,
    date_fin_reservation date not null,
    id_locataire int not null,
    id_biens int not null,
    id_Tarif int not null,
    foreign key (id_biens) references Biens(id_biens),
    foreign key (id_Tarif) references Tarif(id_Tarif),
    foreign key (id_locataire) references Locataire(id_locataire)
);



Create table if not exists Pts_Interet(
    id_pts_interet int primary key auto_increment not null,
    lib_pts_interet varchar(50) not null,
    description_pts_interet text not null,
    id_type_points_interet int not null,
    foreign key (id_type_points_interet) references Type_Pts_Interet(id_type_points_interet)
);

Create table if not exists Evenement(
    id_evenement int primary key auto_increment not null,
    nom_evenement varchar(50) not null,
    date_debut_evenement date not null,
    date_fin_evenement date not null,
    description_evenement text not null,
    id_commune int not null,
    id_type_evenement int not null,
    foreign key (id_type_evenement) references Type_Evenement(id_type_evenement),
    foreign key (id_commune) references Commune(id_commune)
);


CREATE TABLE IF NOT EXISTS Photos(
    id_photo int primary key auto_increment,
    nom_photos varchar(255) not null,
    lien_photo varchar(255) not null,
    id_biens int NOT NULL,
    foreign key (id_biens) references Biens(id_biens)
);

CREATE TABLE IF NOT EXISTS Dispose(
    id_biens int not null,
    id_pts_interet int not null,
    distance varchar(255),
    primary key(id_biens, id_pts_interet),
    foreign key (id_biens) references Biens(id_biens),
    foreign key (id_pts_interet) references Pts_Interet(id_pts_interet)
);

CREATE TABLE IF NOT EXISTS Compose(
    id_biens int not null,
    id_prestation int not null,
    quantite int not null,
    primary key(id_biens, id_prestation),
    foreign key (id_biens) references Biens(id_biens),
    foreign key (id_prestation) references Prestation(id_prestation)
);


Create table if not exists Animateur(
    id_animateur int primary key auto_increment not null,
    nom_animateur varchar(30) not null,
    prenom_animateur varchar(30) not null,
    email_animateur varchar(50) not null,
    password_animateur varchar(255) not null
);

