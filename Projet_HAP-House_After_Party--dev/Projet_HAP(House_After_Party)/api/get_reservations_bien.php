<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id_bien = intval($_GET['id_bien'] ?? 0);
$id_user = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if (!$id_bien) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        echo json_encode([]);
        exit;
    }

    $events = [];
    $unavailableWeeks = [];

    // Get the bien to check if current user is the owner
    $bienStmt = $pdo->prepare('SELECT created_by_id, unavailable_weeks FROM Biens WHERE id_biens = ?');
    $bienStmt->execute([$id_bien]);
    $bien = $bienStmt->fetch(PDO::FETCH_ASSOC);

    $isOwner = ($bien && $id_user && $bien['created_by_id'] == $id_user);

    // Read unavailable weeks from the new table `semaine_indisponible` (if it exists)
    try {
        $uStmt = $pdo->prepare('SELECT annee, semaine FROM semaine_indisponible WHERE id_biens = ?');
        $uStmt->execute([$id_bien]);
        $rows = $uStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $unavailableWeeks[] = ['annee' => intval($r['annee']), 'semaine' => intval($r['semaine'])];
        }
    } catch (Exception $e) {
        // table may not exist yet; fall back to JSON stored on Biens
        if ($bien && $bien['unavailable_weeks']) {
            $unavailableWeeks = json_decode($bien['unavailable_weeks'], true) ?: [];
        }
    }

    // Get all reservations
    $stmt = $pdo->prepare('SELECT r.id_reservation, r.date_debut_reservation, r.date_fin_reservation, r.id_locataire, l.nom_locataire, l.prenom_locataire, s.lib_saison FROM Reservation r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire LEFT JOIN Tarif t ON r.id_Tarif = t.id_Tarif LEFT JOIN Saison s ON t.id_saison = s.id_saison WHERE r.id_biens = ?');
    $stmt->execute([$id_bien]);

    $seasonColors = [
        'Été' => '#FFD700',
        'Hiver' => '#87CEEB',
        'Printemps' => '#98FB98',
        'Automne' => '#FFA500',
        'Saison basse' => '#D3D3D3',
        'Saison haute' => '#FF6347',
        'default' => '#add8e6'
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $start = $row['date_debut_reservation'];
        $end = $row['date_fin_reservation'];
        $endDate = new DateTime($end);
        $endDate->modify('+1 day');
        $end = $endDate->format('Y-m-d');
        $title = 'Réservé par ' . htmlspecialchars($row['nom_locataire'] . ' ' . $row['prenom_locataire']);

        // Determine saison based on the month of the start date
        $dt = new DateTime($start);
        $month = intval($dt->format('m'));
        if ($month >= 3 && $month <= 5) {
            $saison = 'Printemps';
        } elseif ($month >= 6 && $month <= 8) {
            $saison = 'Été';
        } elseif ($month >= 9 && $month <= 11) {
            $saison = 'Automne';
        } else {
            $saison = 'Hiver';
        }

        if (array_key_exists($saison, $seasonColors)) {
            $color = $seasonColors[$saison];
        } else {
            $baseSaison = explode(' ', $saison)[0];
            $color = $seasonColors[$baseSaison] ?? $seasonColors['default'];
        }
        $events[] = [
            'title' => $title,
            'start' => $start,
            'end' => $end,
            'allDay' => true,
            'backgroundColor' => $color,
            'borderColor' => '#000000',
            'display' => 'block',
            'className' => 'reserved-event'
        ];
    }

    // Add events for unavailable weeks (visible to all users)
    if (!empty($unavailableWeeks)) {
        foreach ($unavailableWeeks as $uw) {
            // uw may be either an integer (legacy JSON weeks) or array with annee/semaine
            if (is_array($uw) && isset($uw['annee']) && isset($uw['semaine'])) {
                $year = intval($uw['annee']);
                $weekNum = intval($uw['semaine']);
            } else {
                $year = intval(date('Y'));
                $weekNum = intval($uw);
            }

            try {
                $date = new DateTime();
                $date->setISODate($year, $weekNum, 1); // Monday of that week
                $start = $date->format('Y-m-d');
                $endDate = clone $date;
                $endDate->modify('+7 days');
                $end = $endDate->format('Y-m-d');

                $events[] = [
                    'title' => '⚠️ INDISPONIBLE ⚠️',
                    'start' => $start,
                    'end' => $end,
                    'allDay' => true,
                    'backgroundColor' => '#ff6666',
                    'borderColor' => '#cc0000',
                    'textColor' => '#ffffff',
                    'display' => 'block',
                    'className' => 'unavailable-week',
                    'selectable' => false
                ];
            } catch (Exception $e) {
                // ignore invalid week/year
            }
        }
    }

    echo json_encode($events);
} catch (Exception $e) {
    error_log('Error in get_reservations_bien.php: ' . $e->getMessage());
    echo json_encode([]);
}
?>
