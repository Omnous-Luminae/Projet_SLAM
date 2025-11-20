<?php 

require_once __DIR__. "/../../config/db.php";

Class Reservation{
private $id_reservation = "";
private $date_debut_reservation = "";
private $date_fin_reservation = "";
private $pdo;

public function __construct($id_reservation,$date_debut_reservation,$date_fin_reservation, $pdo){
    $this->id_reservation = $id_reservation;
    $this->date_debut_reservation = $date_debut_reservation;
    $this->date_fin_reservation = $date_fin_reservation;
    $this->pdo = $pdo;
}

public function getIdReservation() {return $this->id_reservation;}
public function getDateDebutReservation() {return $this->date_debut_reservation;}
public function getDateFinReservation() {return $this->date_fin_reservation;}

public function setDateDebutReservation($date_debut_reservation) {$this->date_debut_reservation = $date_debut_reservation;}
public function setDateFinReservation($date_fin_reservation) {$this->date_fin_reservation = $date_fin_reservation;}

public function addReservation($date_debut_reservation,$date_fin_reservation){
    $stmt=$this->pdo->prepare("INSERT INTO Reservation(date_debut_reservation,date_fin_reservation) VALUES (:date_debut_reservation,:date_fin_reservation)");
    return $stmt->execute([
        'date_debut_reservation' => $date_debut_reservation,
        'date_fin_reservation' => $date_fin_reservation
    ]);
}

public function readAllReservation(){
    $stmt=$this->pdo->query("SELECT * FROM Reservation");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function readIdReservation($id_reservation){
        $stmt = $this->pdo->prepare("SELECT * FROM Reservation WHERE idreservation = :idreservation");
        $stmt->execute(['idreservation' => $id_reservation]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updateReservation($id_reservation,$date_debut_reservation,$date_fin_reservation){
        $stmt = $this->pdo->prepare("UPDATE Reservation SET date_debut_reservation = :date_debut_reservation,date_fin_reservation = :date_fin_eservation WHERE id_reservation = :idreservation");
        return $stmt->execute(['date_debut_reservation' => $date_debut_reservation,'date_fin_reservation'=>$date_fin_reservation, 'idreservation' => $id_reservation]);
    }

public function deleteReservation($id_reservation){
        $stmt = $this->pdo->prepare("DELETE FROM Reservation WHERE idreservation = :idreservation");
        return $stmt->execute(['idreservation' => $id_reservation]);
    }



}

?>