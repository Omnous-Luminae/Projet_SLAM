<?php

require_once __DIR__ . "/../classes/EncryptionManager.php";

/**
 * Classe ReservationArchive
 * Gère l'archivage et le cryptage des réservations passées
 */
class ReservationArchive {
    
    private $pdo;
    private $encryption_manager;
    
    public function __construct($pdo, $encryption_key = null) {
        $this->pdo = $pdo;
        $this->encryption_manager = new EncryptionManager($pdo, $encryption_key);
    }
    
    /**
     * Archive une réservation en cryptant ses données
     * @param int $id_reservation ID de la réservation à archiver
     * @param int|null $utilisateur_id ID de l'utilisateur qui effectue l'archivage
     * @return int|false ID de l'archive créée ou false
     */
    public function archiveReservation($id_reservation, $utilisateur_id = null) {
        try {
            // Récupérer les données complètes de la réservation
            $reservation = $this->getFullReservationData($id_reservation);
            
            if (!$reservation) {
                return false;
            }
            
            // Vérifier que la réservation est bien passée
            $date_fin = new DateTime($reservation['date_fin_reservation']);
            $today = new DateTime('today');
            
            if ($date_fin > $today) {
                throw new Exception("La réservation n'est pas encore terminée");
            }
            
            // Préparer les données à archiver
            $donnees_archive = [
                'reservation_id' => $reservation['id_reservation'],
                'locataire' => [
                    'id' => $reservation['id_locataire'],
                    'nom' => $reservation['nom_locataire'],
                    'prenom' => $reservation['prenom_locataire'],
                    'email' => $reservation['email_locataire'],
                    'telephone' => $reservation['telephone_locataire'],
                    'date_naissance' => $reservation['date_naissance'],
                    'adresse' => [
                        'rue' => $reservation['rue_locataire'],
                        'complement' => $reservation['complement_locataire'],
                        'commune' => $reservation['nom_commune'],
                        'code_postal' => $reservation['cp_commune']
                    ]
                ],
                'bien' => [
                    'id' => $reservation['id_biens'],
                    'nom' => $reservation['nom_biens'],
                    'rue' => $reservation['rue_biens'],
                    'superficie' => $reservation['superficie_biens'],
                    'description' => $reservation['description_biens'],
                    'nb_couchage' => $reservation['nb_couchage'],
                    'type' => $reservation['designation_type_bien']
                ],
                'reservation' => [
                    'date_debut' => $reservation['date_debut_reservation'],
                    'date_fin' => $reservation['date_fin_reservation'],
                    'tarif' => $reservation['tarif'],
                    'saison' => $reservation['lib_saison']
                ],
                'date_archivage' => date('Y-m-d H:i:s'),
                'version' => '1.0'
            ];
            
            // Crypter les données
            $encrypted_result = $this->encryption_manager->encrypt($donnees_archive);
            
            // Générer une clé dérivée unique pour cette réservation
            $derived_key = $this->encryption_manager->generateDerivedKey($id_reservation);
            
            // Insérer dans la table d'archive
            $stmt = $this->pdo->prepare("
                INSERT INTO Reservation_Archive (
                    id_reservation_original,
                    donnees_cryptees,
                    cle_derivee,
                    vecteur_initialisation,
                    date_debut_reservation,
                    date_fin_reservation,
                    id_locataire,
                    id_biens
                ) VALUES (
                    :id_reservation,
                    :donnees_cryptees,
                    :cle_derivee,
                    :iv,
                    :date_debut,
                    :date_fin,
                    :id_locataire,
                    :id_biens
                )
            ");
            
            $success = $stmt->execute([
                ':id_reservation' => $id_reservation,
                ':donnees_cryptees' => $encrypted_result['encrypted'],
                ':cle_derivee' => $derived_key,
                ':iv' => $encrypted_result['iv'],
                ':date_debut' => $reservation['date_debut_reservation'],
                ':date_fin' => $reservation['date_fin_reservation'],
                ':id_locataire' => $reservation['id_locataire'],
                ':id_biens' => $reservation['id_biens']
            ]);
            
            if ($success) {
                $archive_id = $this->pdo->lastInsertId();
                
                // Enregistrer dans le log
                $this->logAction('archivage', $id_reservation, $archive_id, $utilisateur_id, 
                    'Réservation archivée et cryptée avec succès');
                
                return $archive_id;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Erreur archivage réservation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les données complètes d'une réservation
     * @param int $id_reservation
     * @return array|false
     */
    private function getFullReservationData($id_reservation) {
        $stmt = $this->pdo->prepare("
            SELECT 
                r.*,
                l.nom_locataire, l.prenom_locataire, l.email_locataire, l.telephone_locataire, 
                l.date_naissance, l.rue_locataire, l.complement_locataire,
                b.nom_biens, b.rue_biens, b.superficie_biens, b.description_biens, b.nb_couchage,
                c.nom_commune, c.cp_commune,
                tb.designation_type_bien,
                t.tarif, t.semaine_Tarif,
                s.lib_saison
            FROM Reservation r
            JOIN Locataire l ON r.id_locataire = l.id_locataire
            JOIN Biens b ON r.id_biens = b.id_biens
            JOIN Commune c ON b.id_commune = c.id_commune
            JOIN Type_Bien tb ON b.id_type_biens = tb.id_type_biens
            JOIN Tarif t ON r.id_Tarif = t.id_Tarif
            JOIN Saison s ON t.id_saison = s.id_saison
            WHERE r.id_reservation = :id_reservation
        ");
        
        $stmt->execute([':id_reservation' => $id_reservation]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Restaure les données d'une réservation archivée
     * @param int $id_archive ID de l'archive
     * @return array|false Données décryptées ou false
     */
    public function restoreArchive($id_archive, $utilisateur_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT donnees_cryptees FROM Reservation_Archive 
                WHERE id_archive = :id_archive AND statut_archivage = 'archivé'
            ");
            
            $stmt->execute([':id_archive' => $id_archive]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false;
            }
            
            // Décrypter les données
            $donnees = $this->encryption_manager->decrypt($result['donnees_cryptees']);
            
            if ($donnees) {
                // Marquer l'archive comme restaurée
                $update_stmt = $this->pdo->prepare("
                    UPDATE Reservation_Archive 
                    SET statut_archivage = 'restauré'
                    WHERE id_archive = :id_archive
                ");
                $update_stmt->execute([':id_archive' => $id_archive]);
                
                $this->logAction('restauration', null, $id_archive, $utilisateur_id, 
                    'Archive restaurée et décryptée');
            }
            
            return $donnees;
            
        } catch (Exception $e) {
            error_log("Erreur restauration archive: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Archive automatiquement toutes les réservations passées
     * @param int|null $jours Nombre de jours après la fin de la réservation (défaut: 1)
     * @return int Nombre de réservations archivées
     */
    public function archiveAllPastReservations($jours = 1, $utilisateur_id = null) {
        try {
            $date_limite = date('Y-m-d', strtotime("-$jours days"));
            
            // Récupérer toutes les réservations passées non archivées
            $stmt = $this->pdo->prepare("
                SELECT id_reservation 
                FROM Reservation r
                WHERE date_fin_reservation <= :date_limite
                AND id_reservation NOT IN (
                    SELECT id_reservation_original FROM Reservation_Archive 
                    WHERE statut_archivage != 'supprimé'
                )
            ");
            
            $stmt->execute([':date_limite' => $date_limite]);
            $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $count = 0;
            foreach ($reservations as $res) {
                if ($this->archiveReservation($res['id_reservation'], $utilisateur_id)) {
                    $count++;
                }
            }
            
            return $count;
            
        } catch (Exception $e) {
            error_log("Erreur archivage batch: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Récupère les archives avec filtres optionnels
     * @param array $filtres Filtres de recherche
     * @return array
     */
    public function getArchives($filtres = []) {
        $query = "
            SELECT id_archive, id_reservation_original, date_debut_reservation, 
                   date_fin_reservation, id_locataire, id_biens, date_archivage, 
                   statut_archivage FROM Reservation_Archive WHERE 1=1
        ";
        
        $params = [];
        
        if (isset($filtres['id_locataire'])) {
            $query .= " AND id_locataire = :id_locataire";
            $params[':id_locataire'] = $filtres['id_locataire'];
        }
        
        if (isset($filtres['id_biens'])) {
            $query .= " AND id_biens = :id_biens";
            $params[':id_biens'] = $filtres['id_biens'];
        }
        
        if (isset($filtres['statut'])) {
            $query .= " AND statut_archivage = :statut";
            $params[':statut'] = $filtres['statut'];
        }
        
        if (isset($filtres['date_debut'])) {
            $query .= " AND date_archivage >= :date_debut";
            $params[':date_debut'] = $filtres['date_debut'];
        }
        
        if (isset($filtres['date_fin'])) {
            $query .= " AND date_archivage <= :date_fin";
            $params[':date_fin'] = $filtres['date_fin'];
        }
        
        $query .= " ORDER BY date_archivage DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Supprime définitivement une archive (avec audit trail)
     * @param int $id_archive
     * @return bool
     */
    public function deleteArchive($id_archive, $utilisateur_id = null) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE Reservation_Archive 
                SET statut_archivage = 'supprimé'
                WHERE id_archive = :id_archive
            ");
            
            $success = $stmt->execute([':id_archive' => $id_archive]);
            
            if ($success) {
                $this->logAction('suppression', null, $id_archive, $utilisateur_id, 
                    'Archive marquée comme supprimée');
            }
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Erreur suppression archive: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistre une action dans le log
     * @param string $action
     * @param int|null $id_reservation
     * @param int|null $id_archive
     * @param int|null $utilisateur_id
     * @param string $description
     */
    private function logAction($action, $id_reservation = null, $id_archive = null, 
                              $utilisateur_id = null, $description = '') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO Archive_Log (action, id_reservation, id_archive, utilisateur_id, description)
                VALUES (:action, :id_reservation, :id_archive, :utilisateur_id, :description)
            ");
            
            $stmt->execute([
                ':action' => $action,
                ':id_reservation' => $id_reservation,
                ':id_archive' => $id_archive,
                ':utilisateur_id' => $utilisateur_id,
                ':description' => $description
            ]);
        } catch (Exception $e) {
            error_log("Erreur enregistrement log: " . $e->getMessage());
        }
    }
    
    /**
     * Récupère les logs d'archivage
     * @param array $filtres
     * @return array
     */
    public function getLogs($filtres = []) {
        $query = "
            SELECT * FROM Archive_Log WHERE 1=1
        ";
        
        $params = [];
        
        if (isset($filtres['action'])) {
            $query .= " AND action = :action";
            $params[':action'] = $filtres['action'];
        }
        
        if (isset($filtres['id_archive'])) {
            $query .= " AND id_archive = :id_archive";
            $params[':id_archive'] = $filtres['id_archive'];
        }
        
        if (isset($filtres['date_debut'])) {
            $query .= " AND date_action >= :date_debut";
            $params[':date_debut'] = $filtres['date_debut'];
        }
        
        $query .= " ORDER BY date_action DESC";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
