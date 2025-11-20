<?php

require_once __DIR__.  "/../../config/db.php";

Class Commune{

    private $id_commune = "";
    private $code_insee = "";
    private $nom_commune = "";
    private $cp_commune = "";
    private $lat_commune = "";
    private $long_commune = "";
    private $ville_slug = "";
    private $ville_nom_reel = "";
    private $ville_nom_soundex = "";
    private $ville_nom_metaphone = "";
    private $ville_departement = "";
    private $ville_arrondissement = "";
    private $ville_canton = "";
    private $ville_code_commune = "";
    private $ville_commune = "";
    private $ville_surface = "";
    private $ville_zmin = "";
    private $ville_zmax = "";
    private $pdo;


    public function __construct($id_commune, $code_insee, $nom_commune, $cp_commune, $lat_commune, $long_commune, $ville_slug, $ville_nom_reel, $ville_nom_soundex, $ville_nom_metaphone, $ville_departement, $ville_arrondissement, $ville_canton, $ville_code_commune, $ville_commune, $ville_surface, $ville_zmin, $ville_zmax, $pdo){
        $this->id_commune = $id_commune;
        $this->code_insee = $code_insee;
        $this->nom_commune = $nom_commune;
        $this->cp_commune = $cp_commune;
        $this->lat_commune = $lat_commune;
        $this->long_commune = $long_commune;
        $this->ville_slug = $ville_slug;
        $this->ville_nom_reel = $ville_nom_reel;
        $this->ville_nom_soundex = $ville_nom_soundex;
        $this->ville_nom_metaphone = $ville_nom_metaphone;
        $this->ville_departement = $ville_departement;
        $this->ville_arrondissement = $ville_arrondissement;
        $this->ville_canton = $ville_canton;
        $this->ville_code_commune = $ville_code_commune;
        $this->ville_commune = $ville_commune;
        $this->ville_surface = $ville_surface;
        $this->ville_zmin = $ville_zmin;
        $this->ville_zmax = $ville_zmax;
        $this->pdo = $pdo;

    }

    public function getIdCommune() { return $this->id_commune; }
    public function getCodeInsee() { return $this->code_insee; }
    public function getNomCommune() { return $this->nom_commune; }
    public function getCpCommune() { return $this->cp_commune; }
    public function getLatCommune() { return $this->lat_commune; }
    public function getLongCommune() { return $this->long_commune; }
    public function getVilleSlug() { return $this->ville_slug; }
    public function getVilleNomReel() { return $this->ville_nom_reel; }
    public function getVilleNomSoundex() { return $this->ville_nom_soundex; }
    public function getVilleNomMetaphone() { return $this->ville_nom_metaphone; }
    public function getVilleDepartement() { return $this->ville_departement; }
    public function getVilleArrondissement() { return $this->ville_arrondissement; }
    public function getVilleCanton() { return $this->ville_canton; }
    public function getVilleCodeCommune() { return $this->ville_code_commune; }
    public function getVilleCommune() { return $this->ville_commune; }
    public function getVilleSurface() { return $this->ville_surface; }
    public function getVilleZmin() { return $this->ville_zmin; }
    public function getVilleZmax() { return $this->ville_zmax; }

    public function setNomCommune($nom_commune) { $this->nom_commune = $nom_commune; }
    public function setCpCommune($cp_commune) { $this->cp_commune = $cp_commune; }
    public function setLatCommune($lat_commune) { $this->lat_commune = $lat_commune; }
    public function setLongCommune($long_commune) { $this->long_commune = $long_commune; }
    public function setVilleSurface($ville_surface) { $this->ville_surface = $ville_surface; }
    public function setVilleZmin($ville_zmin) { $this->ville_zmin = $ville_zmin; }
    public function setVilleZmax($ville_zmax) { $this->ville_zmax = $ville_zmax; }


        public function createCommune($code_insee, $nom_commune, $cp_commune, $lat_commune, $long_commune, $ville_slug, $ville_nom_reel, $ville_nom_soundex, $ville_nom_metaphone, $ville_departement, $ville_arrondissement, $ville_canton, $ville_code_commune, $ville_commune, $ville_surface, $ville_zmin, $ville_zmax)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Commune (code_insee, nom_commune, cp_commune, latitude_commune, longitude_commune, ville_slug, ville_nom_reel, ville_nom_soundex, ville_nom_metaphone, ville_departement, ville_arrondissement, ville_canton, ville_code_commune, ville_commune, ville_surface, ville_zmin, ville_zmax) VALUES (:code_insee, :nom_commune, :cp_commune, :lat, :long, :slug, :nom_reel, :soundex, :metaphone, :departement, :arrondissement, :canton, :code_commune, :commune, :surface, :zmin, :zmax)");
        return $stmt->execute([
            'code_insee' => $code_insee,
            'nom_commune' => $nom_commune,
            'cp_commune' => $cp_commune,
            'lat' => $lat_commune,
            'long' => $long_commune,
            'slug' => $ville_slug,
            'nom_reel' => $ville_nom_reel,
            'soundex' => $ville_nom_soundex,
            'metaphone' => $ville_nom_metaphone,
            'departement' => $ville_departement,
            'arrondissement' => $ville_arrondissement,
            'canton' => $ville_canton,
            'code_commune' => $ville_code_commune,
            'commune' => $ville_commune,
            'surface' => $ville_surface,
            'zmin' => $ville_zmin,
            'zmax' => $ville_zmax
        ]);
    }

    // READ (all)
    public function getAllCommune()
    {
        $stmt = $this->pdo->query("SELECT * FROM Commune");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ (one)
    public function getCommuneById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Commune WHERE id_commune = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function updateCommune($id, $nom_commune, $cp_commune, $lat_commune, $long_commune, $ville_surface, $ville_zmin, $ville_zmax)
    {
        $stmt = $this->pdo->prepare("UPDATE Commune SET nom_commune = :nom_commune, cp_commune = :cp_commune, latitude_commune = :lat, longitude_commune = :long, ville_surface = :surface, ville_zmin = :zmin, ville_zmax = :zmax WHERE id_commune = :id");
        return $stmt->execute([
            'nom_commune' => $nom_commune,
            'cp_commune' => $cp_commune,
            'lat' => $lat_commune,
            'long' => $long_commune,
            'surface' => $ville_surface,
            'zmin' => $ville_zmin,
            'zmax' => $ville_zmax,
            'id' => $id
        ]);
    }

    // DELETE
    public function deleteCommune($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Commune WHERE id_commune = :id");
        return $stmt->execute(['id' => $id]);
    }

}