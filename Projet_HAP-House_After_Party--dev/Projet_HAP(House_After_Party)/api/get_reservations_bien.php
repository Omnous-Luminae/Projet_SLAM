<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$id_bien = intval($_GET['id_bien'] ?? 0);

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

    $stmt = $pdo->prepare('SELECT r.date_debut_reservation, r.date_fin_reservation, l.nom_locataire, l.prenom_locataire, s.lib_saison FROM Reservation r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire LEFT JOIN Tarif t ON r.id_Tarif = t.id_Tarif LEFT JOIN Saison s ON t.id_saison = s.id_saison WHERE r.id_biens = ?');
    $stmt->execute([$id_bien]);

    $seasonColors = [
        'Été' => '#FFD700', // Gold for summer
        'Hiver' => '#87CEEB', // Sky blue for winter
        'Printemps' => '#98FB98', // Pale green for spring
        'Automne' => '#FFA500', // Orange for autumn
        'Basse' => '#D3D3D3', // Light gray for low season
        'Haute' => '#FF6347', // Tomato red for high season
        'default' => '#add8e6' // Light blue default
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $start = $row['date_debut_reservation'];
        $end = $row['date_fin_reservation'];
        // FullCalendar end is exclusive, so to include the end date, add 1 day
        $endDate = new DateTime($end);
        $endDate->modify('+1 day');
        $end = $endDate->format('Y-m-d');
        $title = 'Réservé par ' . htmlspecialchars($row['nom_locataire'] . ' ' . $row['prenom_locataire']);
        $saison = $row['lib_saison'] ?? 'default';
        if (array_key_exists($saison, $seasonColors)) {
            $color = $seasonColors[$saison];
        } else {
            $baseSaison = explode(' ', $saison)[0]; // Extract base season name (e.g., 'Été' from 'Été 2024')
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

    echo json_encode($events);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
