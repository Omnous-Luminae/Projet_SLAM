<?php

require_once __DIR__. "/../../config/db.php";

class Evenement{

    private $id_evenement = "";
    private $nom_evenement = "";
    private $description_evenement = "";
    private $date_debut_evenement = "";
    private $date_fin_evenement = "";
    private $pdo;

    public function __construct($id_evenement, $nom_evenement, $description_evenement,$date_debut_evenement, $date_fin_evenement, $pdo){
        $this->id_evenement = $id_evenement;
        $this->nom_evenement = $nom_evenement;
        $this->description_evenement = $description_evenement;
        $this->date_debut_evenement = $date_debut_evenement;
        $this->date_fin_evenement = $date_fin_evenement;
        $this->pdo = $pdo;
    }

    public function getIdEvenement() { return $this->id_evenement; }
    public function getNomEvenement() { return $this->nom_evenement; }
    public function getDescriptionEvenement() { return $this->description_evenement; }
    public function getDateDebutEvenement() { return $this->date_debut_evenement; }
    public function getDateFinEvenement() { return $this->date_fin_evenement; }

    public function setNomEvenement($nom_evenement) { $this->nom_evenement = $nom_evenement; }
    public function setDescriptionEvenement($description_evenement) { $this->description_evenement = $description_evenement; }
    public function setDateDebutEvenement($date_debut_evenement) { $this->date_debut_evenement = $date_debut_evenement; }
    public function setDateFinEvenement($date_fin_evenement) { $this->date_fin_evenement = $date_fin_evenement; }

    public function addEvenement($nom_evenement,$date_debut_evenement,$date_fin_evenement,$description_evenement){
    $stmt=$this->pdo->prepare("INSERT INTO Evenement (nom_evenement,date_debut_evenement,date_fin_evenement,description_evenement) VALUES (:nomevenement,:datedebutevenement,:datefinevenement,:descriptionevenement)");
    return $stmt->execute([
        'nom_evenement' => $nom_evenement,
        'date_debut_evenement' => $date_debut_evenement,
        'date_fin_evenement' => $date_fin_evenement,
        'description_evenement' => $description_evenement
    ]);
}

public function readAllEvenement(){
    $stmt=$this->pdo->query("SELECT * FROM Evenement");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);

}

public function readIdEvenement($id_evenement){
    $stmt=$this->pdo->prepare("SELECT * FROM Evenement WHERE id_evenement=:idevenement");
    $stmt->execute(['idevenement'=>$id_evenement]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updateEvenement($id_evenement,$nom_evenement,$date_debut_evenement,$date_fin_evenement,$description_evenement){
    $stmt=$this->pdo->prepare("UPDATE Evenement SET nom_evenement=:nomevenement,date_debut_evenement=:datedebutevenement,date_fin_evenement=:datefinevenement,description_evenement=:descriptionevenement WHERE id_evenement=:idevenement");
    return $stmt->execute([
    'idevenement'=>$id_evenement,
    'nom_evenement'=>$nom_evenement,
    'date_debut_evenement'=>$date_debut_evenement,
    'date_fin_evenement' => $date_fin_evenement,
     'description_evement'=>$description_evenement
    ]);
}

public function deleteEvenement($id_evenement){
    $stmt=$this->pdo->prepare("DELETE FROM Evenement WHERE id_evenement=:idevenement");
    return $stmt->execute(['idevenement'=>$id_evenement]);
    }
}
?>
