<?php

require_once __DIR__. "/../../config/db.php";

Class Prestation{

    private $id_prestation = "";
    private $lib_prestation = "";
    private $pdo;

public function __construct($id_prestation, $lib_prestation, $pdo){
    $this->id_prestation = $id_prestation;
    $this->lib_prestation = $lib_prestation;
    $this->pdo = $pdo;
}

public function getidprestation () {return $this->id_prestation; }
public function getlibprestation () {return $this->lib_prestation;}

public function setlibprestation($lib_prestation) {$this->lib_prestation = $lib_prestation;}



public function addPrestation($lib_prestation){
    $stmt=$this->pdo->prepare("INSERT INTO Prestation(lib_prestation) VALUES (:lib_prestation)");
    return $stmt->execute(['lib_prestation' => $lib_prestation]);
}

public function readAllPrestation(){
    $stmt=$this->pdo->query("SELECT * FROM Prestation");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function readIdPrestation($id_prestation){
        $stmt = $this->pdo->prepare("SELECT * FROM Prestation WHERE id_prestation = :idprestation");
        $stmt->execute(['idprestation' => $id_prestation]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updatePrestation($id_prestation,$lib_prestation){
        $stmt = $this->pdo->prepare("UPDATE Prestation SET lib_prestation = :libprestation WHERE id_prestation = :idprestation");
        return $stmt->execute(['libprestation' => $lib_prestation, 'id' => $id_prestation]);
    }

public function deletePrestation($id_prestation){
        $stmt = $this->pdo->prepare("DELETE FROM Prestation WHERE id_prestation = :idprestation");
        return $stmt->execute(['idprestation' => $id_prestation]);
    }
}
?>