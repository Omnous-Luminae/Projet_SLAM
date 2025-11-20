<?php

require_once __DIR__ . "/../../config/db.php";

Class PtsInteret{

    private $id_pts_interet = "";
    private $lib_pts_interet = "";
    private $description_pts_interet = "";
    private $id_type_points_interet = "";
    private $id_commune = "";
    private $pdo;

public function __construct($id_pts_interet,$lib_pts_interet,$description_pts_interet,$id_type_points_interet,$id_commune,$pdo){
    $this->id_pts_interet = $id_pts_interet;
    $this->lib_pts_interet = $lib_pts_interet;
    $this->description_pts_interet = $description_pts_interet;
    $this->id_type_points_interet = $id_type_points_interet;
    $this->id_commune = $id_commune;
    $this->pdo = $pdo;
}

public function getIdPtsInteret() {return $this->id_pts_interet;}
public function getLibPtsInteret() {return $this->lib_pts_interet;}
public function getDescriptionPtsInteret() {return $this->description_pts_interet;}
public function getIdTypePointsInteret() {return $this->id_type_points_interet;}
public function getIdCommune() {return $this->id_commune;}


public function setLibPtsInteret($lib_pts_interet) {$this->lib_pts_interet = $lib_pts_interet;}
public function setDescription($description_pts_interet) {$this->description_pts_interet = $description_pts_interet;}


 // CREATE
    public function createPtsInteret($lib_pts_interet, $description_pts_interet, $id_type_points_interet, $id_commune)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Pts_Interet (lib_pts_interet, description_pts_interet, id_type_points_interet, id_commune) VALUES (:lib, :desc, :type, :commune)");
        return $stmt->execute([
            'lib' => $lib_pts_interet,
            'desc' => $description_pts_interet,
            'type' => $id_type_points_interet,
            'commune' => $id_commune
        ]);
    }

    // READ (all)
    public function getAllPtsInteret()
    {
        $stmt = $this->pdo->query("SELECT pi.*, c.nom_commune, c.latitude_commune, c.longitude_commune, t.lib_type_points_interet FROM Pts_Interet pi LEFT JOIN Commune c ON pi.id_commune = c.id_commune LEFT JOIN Type_Pts_Interet t ON pi.id_type_points_interet = t.id_type_points_interet");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ (one)
    public function getPtsInteretById($id)
    {
        $stmt = $this->pdo->prepare("SELECT pi.*, c.nom_commune, c.latitude_commune, c.longitude_commune, t.lib_type_points_interet FROM Pts_Interet pi LEFT JOIN Commune c ON pi.id_commune = c.id_commune LEFT JOIN Type_Pts_Interet t ON pi.id_type_points_interet = t.id_type_points_interet WHERE pi.id_pts_interet = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function updatePtsInteret($id, $lib_pts_interet, $description_pts_interet)
    {
        $stmt = $this->pdo->prepare("UPDATE Pts_Interet SET lib_pts_interet = :lib, description_pts_interet = :desc WHERE id_pts_interet = :id");
        return $stmt->execute([
            'lib' => $lib_pts_interet,
            'desc' => $description_pts_interet,
            'id' => $id
        ]);
    }

    // DELETE
    public function deletePtsInteret($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Pts_Interet WHERE id_pts_interet = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>