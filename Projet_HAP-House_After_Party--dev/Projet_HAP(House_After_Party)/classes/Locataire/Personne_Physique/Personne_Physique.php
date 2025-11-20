<?php

require_once __DIR__. "/../../../config/db.php";
require_once __DIR__. "/../Locataire.php";

class PersonnePhysique extends Locataire
{
    // Pas d'attributs supplémentaires, tout est dans Locataire

    public function __construct(
        $id_locataire, $nom_locataire, $prenom_locataire, $email_locataire,
        $tel_locataire, $date_naissance_locataire, $mdp_locataire,
        $rue_locataire, $complement_rue_locataire
    ) {
        parent::__construct(
            $id_locataire, $nom_locataire, $prenom_locataire, $email_locataire,
            $tel_locataire, $date_naissance_locataire, $mdp_locataire,
            $rue_locataire, $complement_rue_locataire
        );
    }
}
?>