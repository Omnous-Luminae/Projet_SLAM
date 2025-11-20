<?php 

require_once __DIR__. "/../../config/db.php";

Class Compose{

    private $quantite = "";
    private $pdo;

    public function __construct($quantite, $pdo){
        $this->quantite = $quantite;
        $this->pdo = $pdo;

    }

    public function getQuantite() {return $this->quantite;}

    public function setQuantite($quantite) {$this->quantite = $quantite;}

        // Ajouter une liaison
    public function addCompose($id_biens, $id_prestation, $quantite)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES (:id_biens, :id_prestation, :quantite)");
        return $stmt->execute([
            'id_biens' => $id_biens,
            'id_prestation' => $id_prestation,
            'quantite' => $quantite
        ]);
    }

    // Supprimer une liaison
    public function deleteCompose($id_biens, $id_prestation)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Compose WHERE id_biens = :id_biens AND id_prestation = :id_prestation");
        return $stmt->execute([
            'id_biens' => $id_biens,
            'id_prestation' => $id_prestation
        ]);
    }

    // Lire toutes les liaisons pour un bien
    public function getByBien($id_biens)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Compose WHERE id_biens = :id_biens");
        $stmt->execute(['id_biens' => $id_biens]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lire toutes les liaisons pour une prestation
    public function getByPrestation($id_prestation)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Compose WHERE id_prestation = :id_prestation");
        $stmt->execute(['id_prestation' => $id_prestation]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>