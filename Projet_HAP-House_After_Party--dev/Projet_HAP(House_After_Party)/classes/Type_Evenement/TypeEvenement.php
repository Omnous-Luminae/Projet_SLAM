<?php

require_once __DIR__. "/../../config/db.php";

Class TypeEvenement {

    private $id_type_evenement = "";
    private $lib_type_evenement = "";
    private $pdo;

    public function __construct($id_type_evenement,$lib_type_evenement, $pdo){
        $this->id_type_evenement = $id_type_evenement;
        $this->lib_type_evenement = $lib_type_evenement;
        $this->pdo = $pdo;
    }

    public function getIdTypeEvenement() { return $this->id_type_evenement; }
    public function getLibTypeEvenement() { return $this->lib_type_evenement;}
    public function setLibTypeEvenement($lib_type_evenement) { $this->lib_type_evenement = $lib_type_evenement; }
public function createTypeEvenement($lib_type_evenement)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Type_Evenement (lib_type_evenement) VALUES (:lib)");
        return $stmt->execute(['lib' => $lib_type_evenement]);
    }

    public function readAllTypeEvenement()
    {
        $stmt = $this->pdo->query("SELECT * FROM Type_Evenement");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readTypeEvenementById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Type_Evenement WHERE id_type_evenement = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateTypeEvenement($id, $lib_type_evenement)
    {
        $stmt = $this->pdo->prepare("UPDATE Type_Evenement SET lib_type_evenement = :lib WHERE id_type_evenement = :id");
        return $stmt->execute(['lib' => $lib_type_evenement, 'id' => $id]);
    }

    public function deleteEvenement($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Type_Evenement WHERE id_type_evenement = :id");
        return $stmt->execute(['id' => $id]);
    }
    
}

?>