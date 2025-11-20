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
    public function updateLocataire($id_locataire, $nom_locataire, $prenom_locataire,$email_locataire, $tel_locataire, $date_naissance_locataire, $mdp_locataire, $rue_locataire, $complement_locataire, $siret = null, $raison_sociale = null, $id_commune = null)
    {
        $setParts = [];
        $params = ['id' => $id_locataire];

        if ($nom_locataire !== null) {
            $setParts[] = "nom_locataire = :nom";
            $params['nom'] = $nom_locataire;
        }
        if ($prenom_locataire !== null) {
            $setParts[] = "prenom_locataire = :prenom";
            $params['prenom'] = $prenom_locataire;
        }
        if ($email_locataire !== null) {
            $setParts[] = "email_locataire = :email";
            $params['email'] = $email_locataire;
        }
        if ($tel_locataire !== null) {
            $setParts[] = "telephone_locataire = :tel";
            $params['tel'] = $tel_locataire;
        }
        if ($date_naissance_locataire !== null) {
            $setParts[] = "date_naissance = :date_naissance";
            $params['date_naissance'] = $date_naissance_locataire;
        }
        if (!empty($mdp_locataire)) {
            $setParts[] = "password_locataire = :mdp";
            $params['mdp'] = password_hash($mdp_locataire, PASSWORD_DEFAULT);
        }
        if ($rue_locataire !== null) {
            $setParts[] = "rue_locataire = :rue";
            $params['rue'] = $rue_locataire;
        }
        if ($complement_locataire !== null) {
            $setParts[] = "complement_locataire = :complement";
            $params['complement'] = $complement_locataire;
        }
        if ($siret !== null) {
            $setParts[] = "siret = :siret";
            $params['siret'] = $siret;
        }
        if ($raison_sociale !== null) {
            $setParts[] = "raison_sociale = :raison_sociale";
            $params['raison_sociale'] = $raison_sociale;
        }
        if ($id_commune !== null) {
            $setParts[] = "id_commune = :id_commune";
            $params['id_commune'] = $id_commune;
        }

        if (empty($setParts)) {
            return true; // nothing to update
        }

        $setString = implode(', ', $setParts);
        $stmt = $this->pdo->prepare("UPDATE Locataire SET $setString WHERE id_locataire = :id");
        return $stmt->execute($params);
    }

    // DELETE
    public function deleteLocataire($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM Locataire WHERE id_locataire = :id");
        return $stmt->execute(['id' => $id]);
    }

    // AUTHENTICATE
    public function authenticateLocataire($email, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Locataire WHERE LOWER(email_locataire) = LOWER(:email)");
        $stmt->execute(['email' => $email]);
        $locataire = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($locataire) {
            $stored_hash = $locataire['password_locataire'];
            $authenticated = false;

            if (password_verify($password, $stored_hash)) {
                $authenticated = true;
            } elseif (substr($stored_hash, 0, 4) === '$2y$' && strlen($stored_hash) < 60) {
                // Truncated bcrypt hash, treat as plain text and re-hash
                $authenticated = true;
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $this->pdo->prepare("UPDATE Locataire SET password_locataire = :hash WHERE id_locataire = :id");
                $updateStmt->execute(['hash' => $hashed, 'id' => $locataire['id_locataire']]);
            } elseif (strlen($stored_hash) < 60 && $password === $stored_hash) {
                // Plain text
                $authenticated = true;
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $this->pdo->prepare("UPDATE Locataire SET password_locataire = :hash WHERE id_locataire = :id");
                $updateStmt->execute(['hash' => $hashed, 'id' => $locataire['id_locataire']]);
            } elseif (md5($password) === $stored_hash) {
                // MD5 hash
                $authenticated = true;
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $this->pdo->prepare("UPDATE Locataire SET password_locataire = :hash WHERE id_locataire = :id");
                $updateStmt->execute(['hash' => $hashed, 'id' => $locataire['id_locataire']]);
            }

            if ($authenticated) {
                return $locataire;
            }
        }
        return false;
    }
}
