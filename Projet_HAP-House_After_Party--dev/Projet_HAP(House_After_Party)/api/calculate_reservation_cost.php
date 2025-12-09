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

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        echo json_encode(['error' => 'Erreur BDD', 'total' => 0, 'details' => []]);
        exit;
    }

    $start = new DateTime($date_debut);
    $end = new DateTime($date_fin);
    $end->modify('+1 day'); // Include end date

    $details = [];
    $total = 0;
    $current = clone $start;

    while ($current < $end) {
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

        // Group by week for cleaner output
        $weekKey = $year . '-W' . str_pad($week, 2, '0', STR_PAD_LEFT);
        
        if (!isset($details[$weekKey])) {
            $details[$weekKey] = [
                'week' => $week,
                'year' => $year,
                'saison' => $saisonLabel,
                'price_per_night' => $price,
                'nights' => 0,
                'subtotal' => 0,
                'is_special' => $isSpecial,
                'start_date' => $dateStr,
                'end_date' => $dateStr
            ];
        }

        $details[$weekKey]['nights']++;
        $details[$weekKey]['subtotal'] += $price;
        $details[$weekKey]['end_date'] = $dateStr;
        $total += $price;

        $current->modify('+1 day');
    }

    echo json_encode([
        'total' => $total,
        'currency' => '€',
        'details' => array_values($details),
        'night_count' => count($details) > 0 ? array_sum(array_column($details, 'nights')) : 0
    ]);
} catch (Exception $e) {
    error_log('Error in calculate_reservation_cost.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Erreur: ' . $e->getMessage(), 'total' => 0, 'details' => []]);
}
?>
