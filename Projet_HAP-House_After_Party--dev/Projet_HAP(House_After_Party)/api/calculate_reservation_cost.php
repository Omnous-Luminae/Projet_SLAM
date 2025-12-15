<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id_bien = intval($_GET['id_bien'] ?? 0);
$date_debut = trim($_GET['date_debut'] ?? '');
$date_fin = trim($_GET['date_fin'] ?? '');

if (!$id_bien || !$date_debut || !$date_fin) {
    echo json_encode(['error' => 'Paramètres manquants', 'total' => 0, 'details' => []]);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_debut) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_fin)) {
    echo json_encode(['error' => 'Format de date invalide. Utilisez YYYY-MM-DD', 'total' => 0, 'details' => []]);
    exit;
}

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        echo json_encode(['error' => 'Erreur BDD', 'total' => 0, 'details' => []]);
        exit;
    }

    $start = new DateTime($date_debut);
    $end = new DateTime($date_fin);
    
    // Calculate duration in weeks (tarifs are per week, not per night)
    $interval = $start->diff($end);
    $totalDays = $interval->days;
    $numberOfWeeks = ceil($totalDays / 7); // Round up to full weeks
    
    if ($numberOfWeeks < 1) {
        echo json_encode(['error' => 'La durée minimale de réservation est d\'une semaine', 'total' => 0, 'details' => []]);
        exit;
    }

    $details = [];
    $total = 0;
    $current = clone $start;
    
    // Process week by week
    for ($weekIndex = 0; $weekIndex < $numberOfWeeks; $weekIndex++) {
        $dateStr = $current->format('Y-m-d');
        $week = intval($current->format('W'));
        $year = intval($current->format('Y'));
        $month = intval($current->format('m'));

        // Determine season based on month
        if ($month >= 3 && $month <= 5) {
            $season = 'Printemps';
        } elseif ($month >= 6 && $month <= 8) {
            $season = 'Été';
        } elseif ($month >= 9 && $month <= 11) {
            $season = 'Automne';
        } else {
            $season = 'Hiver';
        }

        // Try to get specific tarif for this week
        $stmt = $pdo->prepare('
            SELECT t.tarif, s.lib_saison
            FROM Tarif t
            LEFT JOIN Saison s ON t.id_saison = s.id_saison
            WHERE t.id_biens = ? AND t.semaine_Tarif = ? AND t.année_Tarif = ?
            LIMIT 1
        ');
        $stmt->execute([$id_bien, $week, $year]);
        $tarif = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tarif) {
            $price = floatval($tarif['tarif']);
            $saisonLabel = $tarif['lib_saison'];
            $isSpecial = true;
        } else {
            // Get default tarif for season
            if ($season) {
                $defaultStmt = $pdo->prepare('
                    SELECT td.tarif_defaut, s.lib_saison
                    FROM Tarif_Defaut td
                    LEFT JOIN Saison s ON td.id_saison = s.id_saison
                    WHERE td.id_biens = ? AND s.lib_saison LIKE ?
                    LIMIT 1
                ');
                $defaultStmt->execute([$id_bien, '%' . $season . '%']);
                $default = $defaultStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($default) {
                    $price = floatval($default['tarif_defaut']);
                    $saisonLabel = $default['lib_saison'];
                    $isSpecial = false;
                } else {
                    $price = 0;
                    $saisonLabel = $season;
                    $isSpecial = false;
                }
            } else {
                $price = 0;
                $saisonLabel = 'Inconnue';
                $isSpecial = false;
            }
        }

        // Store details for this week
        $weekKey = $year . '-W' . str_pad($week, 2, '0', STR_PAD_LEFT);
        
        $details[$weekKey] = [
            'week' => $week,
            'year' => $year,
            'saison' => $saisonLabel,
            'price_per_week' => $price,
            'weeks' => 1,
            'subtotal' => $price,
            'is_special' => $isSpecial,
            'start_date' => $dateStr
        ];
        
        $total += $price;
        $current->modify('+7 days'); // Move to next week
    }

    echo json_encode([
        'total' => $total,
        'currency' => '€',
        'details' => array_values($details),
        'week_count' => $numberOfWeeks,
        'duration_days' => $totalDays
    ]);
} catch (Exception $e) {
    error_log('Error in calculate_reservation_cost.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage(), 'total' => 0, 'details' => []]);
}
?>
