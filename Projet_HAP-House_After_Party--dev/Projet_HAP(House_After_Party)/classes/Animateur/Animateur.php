<?php

require_once __DIR__. "/../../config/db.php";

Class Animateur {
private $id_animateur = "";
private $nom_animateur= "";
private $prenom_animateur = "";
private $email_animateur = "";
private $telephone_animateur = "";
private $date_naissance_animateur = "";
private $password_animateur = "";
private $pdo;

public function __construct($pdo, $animateurData = []){
    $this->pdo = $pdo;
    $this->id_animateur = $animateurData['id_animateur'] ?? "";
    $this->nom_animateur = $animateurData['nom_animateur'] ?? "";
    $this->prenom_animateur = $animateurData['prenom_animateur'] ?? "";
    $this->email_animateur = $animateurData['email_animateur'] ?? "";
    $this->telephone_animateur = $animateurData['telephone_animateur'] ?? "";
    $this->date_naissance_animateur = $animateurData['date_naissance_animateur'] ?? "";
    $this->password_animateur = $animateurData['password_animateur'] ?? "";
}

public function getIdAnimateur() {return $this->id_animateur;}
public function getNomAnimateur() {return $this->nom_animateur;}
public function getPrenomAnimateur() {return $this->prenom_animateur;}
public function getEmailAnimateur() {return $this->email_animateur;}
public function getTelephoneAnimateur() {return $this->telephone_animateur;}
public function getDateNaissanceAnimateur() {return $this->date_naissance_animateur;}
public function getPasswordAnimateur() {return $this->password_animateur;}

public function setNomAnimateur($nom_animateur) {$this->nom_animateur = $nom_animateur;}
public function setPrenomAnimateur($prenom_animateur) {$this->prenom_animateur = $prenom_animateur;}
public function setEmailAnimateur($email_animateur) {$this->email_animateur = $email_animateur;}
public function setTelephoneAnimateur($telephone_animateur) {$this->telephone_animateur = $telephone_animateur;}
public function setDateNaissanceAnimateur($date_naissance_animateur) {$this->date_naissance_animateur = $date_naissance_animateur;}
public function setPasswordAnimateur($password_animateur) {$this->password_animateur = $password_animateur;}

// CREATE
public function createAnimateur($nom_animateur, $prenom_animateur, $email_animateur, $telephone_animateur, $date_naissance_animateur, $password_animateur)
{
    $stmt = $this->pdo->prepare(
        "INSERT INTO Animateur (nom_animateur, prenom_animateur, email_animateur, telephone_animateur, date_naissance_animateur, password_animateur)
        VALUES (:nom, :prenom, :email, :telephone, :date_naissance, :password)"
    );
    return $stmt->execute([
        'nom' => $nom_animateur,
        'prenom' => $prenom_animateur,
        'email' => $email_animateur,
        'telephone' => $telephone_animateur,
        'date_naissance' => $date_naissance_animateur,
        'password' => $password_animateur
    ]);
}

// AUTHENTICATE
public function authenticateAnimateur($email, $password)
{
    $stmt = $this->pdo->prepare("SELECT * FROM Animateur WHERE email_animateur = :email");
    $stmt->execute(['email' => $email]);
    $animateur = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($animateur && password_verify($password, $animateur['password_animateur'])) {
        return $animateur;
    }
    return false;
}

}
?>
