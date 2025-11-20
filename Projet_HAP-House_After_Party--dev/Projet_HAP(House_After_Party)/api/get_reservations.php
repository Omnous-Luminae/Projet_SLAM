<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        echo json_encode([]);
        exit;
    }

    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'animateur';
    $currentUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

    $events = [];

    if ($isAdmin) {
        $stmt = $pdo->query('SELECT r.date_debut_reservation, r.date_fin_reservation, b.nom_biens, l.nom_locataire, l.prenom_locataire FROM Reservation r LEFT JOIN Biens b ON r.id_biens = b.id_biens LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire');
    } else {
        if ($currentUserId) {
            $stmt = $pdo->prepare('SELECT r.date_debut_reservation, r.date_fin_reservation, b.nom_biens, l.nom_locataire, l.prenom_locataire FROM Reservation r LEFT JOIN Biens b ON r.id_biens = b.id_biens LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire WHERE r.id_locataire = ?');
            $stmt->execute([$currentUserId]);
        } else {
            echo json_encode([]);
            exit;
        }
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $start = $row['date_debut_reservation'];
        $end = $row['date_fin_reservation'];
        // FullCalendar end is exclusive, so to include the end date, add 1 day
        $endDate = new DateTime($end);
        $endDate->modify('+1 day');
        $end = $endDate->format('Y-m-d');
        $title = htmlspecialchars($row['nom_biens'] . ' - ' . $row['nom_locataire'] . ' ' . $row['prenom_locataire']);
        $events[] = [
            'title' => $title,
            'start' => $start,
            'end' => $end,
            'allDay' => true
        ];
    }

    echo json_encode($events);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
