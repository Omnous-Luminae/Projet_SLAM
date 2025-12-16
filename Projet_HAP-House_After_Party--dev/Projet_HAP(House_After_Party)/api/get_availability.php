<?php
/**
 * API pour récupérer les disponibilités d'un bien
 * Retourne les dates réservées pour affichage dans le calendrier
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$bienId = isset($_GET['bien_id']) ? intval($_GET['bien_id']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

if ($bienId <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID bien invalide']);
    exit;
}

try {
    // Récupérer les réservations pour ce bien
    $stmt = $pdo->prepare("
        SELECT date_debut, date_fin, statut
        FROM Reservation
        WHERE id_bien = ?
        AND (
            (YEAR(date_debut) = ? AND MONTH(date_debut) = ?) OR
            (YEAR(date_fin) = ? AND MONTH(date_fin) = ?) OR
            (date_debut <= ? AND date_fin >= ?)
        )
        ORDER BY date_debut
    ");
    
    $startOfMonth = sprintf('%04d-%02d-01', $year, $month);
    $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
    
    $stmt->execute([
        $bienId, 
        $year, $month, 
        $year, $month,
        $endOfMonth, $startOfMonth
    ]);
    
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construire la liste des dates indisponibles
    $unavailableDates = [];
    $pendingDates = [];
    
    foreach ($reservations as $reservation) {
        $start = new DateTime($reservation['date_debut']);
        $end = new DateTime($reservation['date_fin']);
        $end->modify('+1 day'); // Pour inclure la date de fin
        
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            if ($reservation['statut'] === 'pending' || $reservation['statut'] === 'en_attente') {
                $pendingDates[] = $dateStr;
            } else {
                $unavailableDates[] = $dateStr;
            }
        }
    }
    
    // Récupérer les tarifs par saison
    $stmt = $pdo->prepare("
        SELECT s.date_debut, s.date_fin, t.prix_jour
        FROM Tarif t
        JOIN Saison s ON t.id_saison = s.id_saison
        WHERE t.id_bien = ?
        ORDER BY s.date_debut
    ");
    $stmt->execute([$bienId]);
    $tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Construire le calendrier des prix
    $priceDates = [];
    foreach ($tarifs as $tarif) {
        $start = new DateTime($tarif['date_debut']);
        $end = new DateTime($tarif['date_fin']);
        $end->modify('+1 day');
        
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $priceDates[$dateStr] = $tarif['prix_jour'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'bien_id' => $bienId,
        'month' => $month,
        'year' => $year,
        'unavailable' => array_unique($unavailableDates),
        'pending' => array_unique($pendingDates),
        'prices' => $priceDates
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
}
