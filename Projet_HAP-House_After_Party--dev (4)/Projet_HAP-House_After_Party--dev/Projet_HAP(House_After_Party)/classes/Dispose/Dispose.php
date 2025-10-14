<?php

require_once  __DIR__. "/../../config/db.php";

Class Dispose{

    private $distance = "";
    private $pdo;

    public function __construct($distance, $pdo){
        $this->distance = $distance;
        $this->pdo = $pdo;
    }
    public function getDistance() { return $this->distance; }
    public function setDistance($distance) { $this->distance = $distance; }
     // Ajouter une liaison
    public function addDispose($id_biens, $id_pts_interet, $distance)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Dispose (id_biens, id_pts_interet, distance) VALUES (:id_biens, :id_pts_interet, :distance)");
        return $stmt->execute([
            'id_biens' => $id_biens,
            'id_pts_interet' => $id_pts_interet,
            'distance' => $distance
        ]);
    }

    // Supprimer une liaison
    public function deleteDispose($id_biens, $id_pts_interet)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Dispose WHERE id_biens = :id_biens AND id_pts_interet = :id_pts_interet");
        return $stmt->execute([
            'id_biens' => $id_biens,
            'id_pts_interet' => $id_pts_interet
        ]);
    }

    // Lire toutes les liaisons pour un bien
    public function getByBien($id_biens)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Dispose WHERE id_biens = :id_biens");
        $stmt->execute(['id_biens' => $id_biens]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lire toutes les liaisons pour un point d'intérêt
    public function getByPointInteret($id_pts_interet)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Dispose WHERE id_pts_interet = :id_pts_interet");
        $stmt->execute(['id_pts_interet' => $id_pts_interet]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}