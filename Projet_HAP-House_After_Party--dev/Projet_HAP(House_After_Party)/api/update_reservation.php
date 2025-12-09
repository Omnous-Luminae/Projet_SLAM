<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'animateur';

$idReservation = intval($_POST['id_reservation'] ?? 0);
$newStart = trim($_POST['date_debut'] ?? '');
$newEnd = trim($_POST['date_fin'] ?? '');
$idBien = intval($_POST['id_bien'] ?? 0);

if (!$idReservation || !$newStart || !$newEnd || !$idBien) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $newStart) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newEnd)) {
    echo json_encode(['success' => false, 'message' => 'Format de date invalide']);
    exit;
}

// Validate dates
$dtStart = new DateTime($newStart);
$dtEnd = new DateTime($newEnd);
$today = new DateTime();
$today->setTime(0, 0, 0);

if ($dtStart < $today) {
    echo json_encode(['success' => false, 'message' => 'La date de début doit être aujourd\'hui ou ultérieure']);
    exit;
}

if ($dtEnd <= $dtStart) {
    echo json_encode(['success' => false, 'message' => 'La date de fin doit être postérieure à la date de début']);
    exit;
}

try {
    $pdo = $pdo ?? null;
    if (!$pdo) {
        throw new Exception('Connexion à la base de données échouée');
    }

    // Check if user owns the reservation or is admin
    $stmt = $pdo->prepare('SELECT id_locataire FROM Reservation WHERE id_reservation = ? AND id_biens = ?');
    $stmt->execute([$idReservation, $idBien]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => 'Réservation introuvable']);
        exit;
    }

    if (!$isAdmin && intval($reservation['id_locataire']) !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    // Check for overlaps with other reservations
    $overlapStmt = $pdo->prepare('SELECT 1 FROM Reservation WHERE id_biens = ? AND id_reservation != ? AND NOT (date_fin_reservation <= ? OR date_debut_reservation >= ?) LIMIT 1');
    $overlapStmt->execute([$idBien, $idReservation, $newStart, $newEnd]);
    if ($overlapStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Les nouvelles dates se chevauchent avec une autre réservation']);
        exit;
    }

    // Check unavailable weeks
    $pairs = [];
    $d = clone $dtStart;
    while ($d < $dtEnd) {
        $w = intval($d->format('W'));
        $y = intval($d->format('Y'));
        $key = $y . '-' . $w;
        if (!isset($pairs[$key])) {
            $pairs[$key] = ['annee' => $y, 'semaine' => $w];
        }
        $d->modify('+1 day');
    }

    if (!empty($pairs)) {
        $conds = [];
        $params = [$idBien];
        foreach ($pairs as $p) {
            $conds[] = '(annee = ? AND semaine = ?)';
            $params[] = $p['annee'];
            $params[] = $p['semaine'];
        }
        $sql = 'SELECT 1 FROM semaine_indisponible WHERE id_biens = ? AND (' . implode(' OR ', $conds) . ') LIMIT 1';
        $uStmt = $pdo->prepare($sql);
        $uStmt->execute($params);
        if ($uStmt->fetchColumn()) {
            echo json_encode(['success' => false, 'message' => 'Les nouvelles dates incluent des semaines marquées comme indisponibles']);
            exit;
        }
    }

    // Update the reservation
    $updateStmt = $pdo->prepare('UPDATE Reservation SET date_debut_reservation = ?, date_fin_reservation = ? WHERE id_reservation = ?');
    $updateStmt->execute([$newStart, $newEnd, $idReservation]);

    echo json_encode(['success' => true, 'message' => 'Réservation mise à jour avec succès']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
