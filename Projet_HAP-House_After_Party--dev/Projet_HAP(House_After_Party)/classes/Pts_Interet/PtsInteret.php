<?php

require_once __DIR__ . "/../../config/db.php";

Class PtsInteret{

    private $id_pts_interet = "";
    private $lib_pts_interet = "";
    private $description_pts_interet = "";
    private $pdo;

public function __construct($id_pts_interet,$lib_pts_interet,$description_pts_interet,$pdo){
    $this->id_pts_interet = $id_pts_interet;
    $this->lib_pts_interet = $lib_pts_interet;
    $this->description_pts_interet = $description_pts_interet;
    $this->pdo = $pdo;
}

public function getIdPtsInteret() {return $this->id_pts_interet;}
public function getLibPtsInteret() {return $this->lib_pts_interet;}
public function getDescriptionPtsInteret() {return $this->description_pts_interet;}


public function setLibPtsInteret($lib_pts_interet) {$this->lib_pts_interet = $lib_pts_interet;}
public function setDescription($description_pts_interet) {$this->description_pts_interet = $description_pts_interet;}


 // CREATE
    public function createPtsInteret($lib_pts_interet, $description_pts_interet)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Pts_Interet (lib_pts_interet, description_pts_interet) VALUES (:lib, :desc)");
        return $stmt->execute([
            'lib' => $lib_pts_interet,
            'desc' => $description_pts_interet
        ]);
    }

    // READ (all)
    public function getAllPtsInteret()
    {
        $stmt = $this->pdo->query("SELECT * FROM Pts_Interet");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ (one)
    public function getPtsInteretById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Pts_Interet WHERE id_pts_interet = :id");
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