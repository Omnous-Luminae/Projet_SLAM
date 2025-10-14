<?php

require_once __DIR__. "/../../config/db.php";


Class Tarif{

    private $id_tarif = "";
    private $semaine_tarif = "";
    private $annee_tarif = "";
    private $tarif = "";
    private $pdo;

    public function __construct($id_tarif, $semaine_tarif, $annee_tarif, $tarif, $pdo){
        $this->id_tarif = $id_tarif;
        $this->semaine_tarif = $semaine_tarif;
        $this->annee_tarif = $annee_tarif;
        $this->tarif = $tarif;
        $this->pdo = $pdo;
    }

    public function getIdTarif() { return $this->id_tarif; }
    public function getSemaineTarif() { return $this->semaine_tarif; }
    public function getAnneeTarif() { return $this->annee_tarif; }
    public function getTarif() { return $this->tarif; }

    public function setSemaineTarif($semaine_tarif) { $this->semaine_tarif = $semaine_tarif; }
    public function setAnneeTarif($annee_tarif) { $this->annee_tarif = $annee_tarif; }
    public function setTarif($tarif) { $this->tarif = $tarif; }

    public function createTarif($semaine_tarif, $annee_tarif, $tarif)
    {
        $stmt = $this->pdo->prepare("INSERT INTO Tarif (semaine_tarif, annee_tarif, tarif) VALUES (:semaine, :annee, :tarif)");
        return $stmt->execute([
            'semaine' => $semaine_tarif,
            'annee' => $annee_tarif,
            'tarif' => $tarif
        ]);
    }

    // READ (all)
    public function getAllTarif()
    {
        $stmt = $this->pdo->query("SELECT * FROM Tarif");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ (one)
    public function getTarifById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Tarif WHERE id_tarif = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function updateTarif($id, $semaine_tarif, $annee_tarif, $tarif)
    {
        $stmt = $this->pdo->prepare("UPDATE Tarif SET semaine_tarif = :semaine, annee_tarif = :annee, tarif = :tarif WHERE id_tarif = :id");
        return $stmt->execute([
            'semaine' => $semaine_tarif,
            'annee' => $annee_tarif,
            'tarif' => $tarif,
            'id' => $id
        ]);
    }

    // DELETE
    public function deleteTarif($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Tarif WHERE id_tarif = :id");
        return $stmt->execute(['id' => $id]);
    }
}

?>