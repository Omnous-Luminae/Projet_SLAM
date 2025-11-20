<?php 

require_once __DIR__. "/../../config/db.php";

Class TypePtsInteret {

    private $id_type_points_interet = "";
    private $lib_type_points_interet = "";
    private $pdo;

    public function __construct($id_type_points_interet,$lib_type_points_interet, $pdo){
        $this->id_type_points_interet = $id_type_points_interet;
        $this->lib_type_points_interet = $lib_type_points_interet;
        $this->pdo = $pdo;
    }

    public function getIdTypePointsInteret() { return $this->id_type_points_interet; }
    public function getLibTypePointsInteret() { return $this->lib_type_points_interet;}
    public function setLibTypePointsInteret($lib_type_points_interet) { $this->lib_type_points_interet = $lib_type_points_interet; }

    public function createTypePtsInteret($lib_type_points_interet)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Type_Pts_Interet (lib_type_points_interet) VALUES (:lib)");
        return $stmt->execute(['lib' => $lib_type_points_interet]);
    }

    public function readAllTypePtsInteret()
    {
        $stmt = $this->pdo->query("SELECT * FROM Type_Pts_Interet");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readTypePtsInteretById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Type_Pts_Interet WHERE id_type_points_interet = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $lib_type_points_interet)
    {
        $stmt = $this->pdo->prepare("UPDATE Type_Pts_Interet SET lib_type_points_interet = :lib WHERE id_type_points_interet = :id");
        return $stmt->execute(['lib' => $lib_type_points_interet, 'id' => $id]);
    }

    public function deleteTypePtsInteret($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Type_Pts_Interet WHERE id_type_points_interet = :id");
        return $stmt->execute(['id' => $id]);
    }
}

?>