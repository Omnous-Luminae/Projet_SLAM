<?php

require_once __DIR__. "/../../../config/db.php";
require_once __DIR__. "/../Locataire.php";

class PersonneMorale extends Locataire
{
    private $siret = "";
    private $raison_sociale = "";

    public function __construct(
        $id_locataire, $nom_locataire, $prenom_locataire, $email_locataire,
        $tel_locataire, $date_naissance_locataire, $mdp_locataire,
        $rue_locataire, $complement_rue_locataire,
        $siret, $raison_sociale
    ) {
        parent::__construct(
            $id_locataire, $nom_locataire, $prenom_locataire, $email_locataire,
            $tel_locataire, $date_naissance_locataire, $mdp_locataire,
            $rue_locataire, $complement_rue_locataire
        );
        $this->siret = $siret;
        $this->raison_sociale = $raison_sociale;
    }

    public function getSiret() { return $this->siret; }
    public function setSiret($siret) { $this->siret = $siret; }

    public function getRaisonSociale() { return $this->raison_sociale; }
    public function setRaisonSociale($raison_sociale) { $this->raison_sociale = $raison_sociale; }
}