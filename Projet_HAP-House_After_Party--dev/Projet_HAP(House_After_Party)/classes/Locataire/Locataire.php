<?php

require_once  __DIR__.  "/../../config/db.php";

Class Locataire{

    private $id_locataire = "";
    private $nom_locataire = "";
    private $prenom_locataire = "";
    private $date_naissance_locataire = "";
    private $mdp_locataire = "";
    private $rue_locataire = "";
    private $complement_rue_locataire = "";
    private $email_locataire = "";
    private $tel_locataire = "";
    private $pdo;

    public function __construct($id_locataire, $nom_locataire, $prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $mdp_locataire, $rue_locataire, $complement_rue_locataire, $pdo = null){
        $this->id_locataire = $id_locataire;
        $this->nom_locataire = $nom_locataire;
        $this->prenom_locataire = $prenom_locataire;
        $this->email_locataire = $email_locataire;
        $this->tel_locataire = $tel_locataire;
        $this->date_naissance_locataire = $date_naissance_locataire;
        $this->mdp_locataire = $mdp_locataire;
        $this->rue_locataire = $rue_locataire;
        $this->complement_rue_locataire = $complement_rue_locataire;
        $this->pdo = $pdo;
    }

    public function getIdLocataire() { return $this->id_locataire; }
    public function getNomLocataire() { return $this->nom_locataire; }
    public function getPrenomLocataire() { return $this->prenom_locataire; }
    public function getEmailLocataire() { return $this->email_locataire; }
    public function getTelLocataire() { return $this->tel_locataire; }
    public function getDateNaissanceLocataire() { return $this->date_naissance_locataire; }
    public function getMdpLocataire() { return $this->mdp_locataire; }
    public function getRueLocataire() { return $this->rue_locataire; }
    public function getComplementRueLocataire() { return $this->complement_rue_locataire; }

    public function setNomLocataire($nom_locataire) { $this->nom_locataire = $nom_locataire; }
    public function setPrenomLocataire($prenom_locataire) { $this->prenom_locataire = $prenom_locataire; }
    public function setEmailLocataire($email_locataire) { $this->email_locataire = $email_locataire; }
    public function setTelLocataire($tel_locataire) { $this->tel_locataire = $tel_locataire; }
    public function setDateNaissanceLocataire($date_naissance_locataire) { $this->date_naissance_locataire = $date_naissance_locataire; }
    public function setMdpLocataire($mdp_locataire) { $this->mdp_locataire = $mdp_locataire; }
    public function setRueLocataire($rue_locataire) { $this->rue_locataire = $rue_locataire; }
    public function setComplementRueLocataire($complement_rue_locataire) { $this->complement_rue_locataire = $complement_rue_locataire; }

// CREATE
    public function createLocataire($nom_locataire,$prenom_locataire, $email_locataire, $tel_locataire, $date_naissance_locataire, $mdp_locataire, $rue_locataire, $complement_rue_locataire, $siret = null, $raison_sociale = null, $id_commune = 1)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO Locataire (nom_locataire, prenom_locataire, email_locataire, telephone_locataire, date_naissance, password_locataire, rue_locataire, complement_locataire, siret, raison_sociale, id_commune)
            VALUES (:nom, :prenom, :email, :tel, :date_naissance, :mdp, :rue, :complement, :siret, :raison_sociale, :id_commune)"
        );
        return $stmt->execute([
            'nom' => $nom_locataire,
            'prenom' => $prenom_locataire,
            'email' => $email_locataire,
            'tel' => $tel_locataire,
            'date_naissance' => $date_naissance_locataire,
            'mdp' => $mdp_locataire,
            'rue' => $rue_locataire,
            'complement' => $complement_rue_locataire,
            'siret' => $siret,
            'raison_sociale' => $raison_sociale,
            'id_commune' => $id_commune
        ]);
    }

    // READ (all)
    public function getAllLocataire()
    {
        $stmt = $this->pdo->query("SELECT * FROM Locataire");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // READ (one)
    public function getLocataireById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Locataire WHERE id_locataire = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function updateLocataire($id_locataire, $nom_locataire, $prenom_locataire,$email_locataire, $tel_locataire, $date_naissance_locataire, $mdp_locataire, $rue_locataire, $complement_locataire, $siret = null, $raison_sociale = null)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE Locataire SET nom_locataire = :nom, prenom_locataire = :prenom, email_locataire = :email, telephone_locataire = :tel, date_naissance = :date_naissance, password_locataire = :mdp, rue_locataire = :rue, complement_locataire = :complement, siret = :siret, raison_sociale = :raison_sociale WHERE id_locataire = :id"
        );
        return $stmt->execute([
            'id' => $id_locataire,
            'nom' => $nom_locataire,
            'prenom' => $prenom_locataire,
            'email' => $email_locataire,
            'tel' => $tel_locataire,
            'date_naissance' => $date_naissance_locataire,
            'mdp' => $mdp_locataire,
            'rue' => $rue_locataire,
            'complement' => $complement_locataire,
            'siret' => $siret,
            'raison_sociale' => $raison_sociale
        ]);
    }

    // DELETE
    public function deleteLocataire($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Locataire WHERE id_locataire = :id");
        return $stmt->execute(['id' => $id]);
    }
}