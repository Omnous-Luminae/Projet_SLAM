<?php 

require_once __DIR__. "/../../config/db.php";

Class Biens{
private $id_biens = "";
private $nom_biens = "";
private $rue_biens = "";
private $superficie_biens = "";
private $description_biens = "";
private $animal_biens = "";
private $nb_couchage = "";
private $pdo;

public function __construct($pdo, $biensData = []){
    $this->pdo = $pdo;
    $this->id_biens = $biensData['id_biens'] ?? "";
    $this->nom_biens = $biensData['nom_biens'] ?? "";
    $this->rue_biens = $biensData['rue_biens'] ?? "";
    $this->superficie_biens = $biensData['superficie_biens'] ?? "";
    $this->description_biens = $biensData['description_biens'] ?? "";
    $this->animal_biens = $biensData['animal_biens'] ?? "";
    $this->nb_couchage = $biensData['nb_couchage'] ?? "";
}

public function getIdBiens() {return $this->id_biens;}
public function getNomBiens() {return $this->nom_biens;}
public function getRueBiens() {return $this->rue_biens;}
public function getSuperficieBiens() {return $this->superficie_biens;}
public function getDescriptionBiens() {return $this->description_biens;}
public function getAnimalBiens() {return $this->animal_biens;}
public function getNbCouchage() {return $this->nb_couchage;}

public function setNomBiens($nom_biens) {$this->nom_biens = $nom_biens;}
public function setRueBiens($rue_biens) {$this->rue_biens = $rue_biens;}
public function setSuperficieBiens($superficie_biens) {$this->superficie_biens = $superficie_biens;}
public function setDescriptionBiens($description_biens) {$this->description_biens = $description_biens;}
public function setAnimalBiens($animal_biens) {$this->animal_biens = $animal_biens;}
public function setNbCouchage($nb_couchage) {$this->nb_couchage = $nb_couchage;}

  // CREATE
    public function createBiens($nom_biens, $rue_biens, $superficie_biens, $description_biens, $animal_biens, $nb_couchage)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage) VALUES (:nom, :rue, :superficie, :description, :animal, :couchage)");
        return $stmt->execute([
            'nom' => $nom_biens,
            'rue' => $rue_biens,
            'superficie' => $superficie_biens,
            'description' => $description_biens,
            'animal' => $animal_biens,
            'couchage' => $nb_couchage
        ]);
    }

    // READ (all)
    public function getAllBiens()
    {
        $stmt = $this->pdo->query("SELECT * FROM Biens");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ (one)
    public function getBiensById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Biens WHERE id_biens = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function updateBiens($id, $nom_biens, $rue_biens, $superficie_biens, $description_biens, $animal_biens, $nb_couchage)
    {
        $stmt = $this->pdo->prepare("UPDATE Biens SET nom_biens = :nom, rue_biens = :rue, superficie_biens = :superficie, description_biens = :description, animal_biens = :animal, nb_couchage = :couchage WHERE id_biens = :id");
        return $stmt->execute([
            'nom' => $nom_biens,
            'rue' => $rue_biens,
            'superficie' => $superficie_biens,
            'description' => $description_biens,
            'animal' => $animal_biens,
            'couchage' => $nb_couchage,
            'id' => $id
        ]);
    }

    // DELETE
    public function deleteBiens($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Biens WHERE id_biens = :id");
        return $stmt->execute(['id' => $id]);
    }


}
?>