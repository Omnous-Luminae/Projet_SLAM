<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'une réservation
        if (isset($_POST['add_reservation'])) {
            $date_debut = trim($_POST['date_debut'] ?? '');
            $date_fin = trim($_POST['date_fin'] ?? '');
            $id_locataire = intval($_POST['id_locataire'] ?? 0);
            $id_biens = intval($_POST['id_biens'] ?? 0);
            $id_tarif = intval($_POST['id_tarif'] ?? 0);

            if ($date_debut && $date_fin && $id_locataire && $id_biens && $id_tarif) {
                $stmt = $pdo->prepare('INSERT INTO Reservation (date_debut_reservation, date_fin_reservation, id_locataire, id_biens, id_Tarif) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$date_debut, $date_fin, $id_locataire, $id_biens, $id_tarif]);
                $message = "Réservation ajoutée avec succès.";
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Suppression d'une réservation
        if (isset($_POST['delete_reservation']) && isset($_POST['id_reservation'])) {
            $id = intval($_POST['id_reservation']);
            $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_reservation = ?');
            $stmt->execute([$id]);
            $message = "Réservation supprimée avec succès.";
        }

        // Récupération des réservations
        $reservations = [];
        $stmt = $pdo->query('SELECT r.*, l.nom_locataire, l.prenom_locataire, b.nom_biens FROM Reservation r LEFT JOIN Locataire l ON r.id_locataire = l.id_locataire LEFT JOIN Biens b ON r.id_biens = b.id_biens ORDER BY r.id_reservation DESC');
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des locataires, biens, tarifs pour les selects
        $locataires = $pdo->query('SELECT id_locataire, nom_locataire, prenom_locataire FROM Locataire')->fetchAll(PDO::FETCH_ASSOC);
        $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
        $tarifs = $pdo->query('SELECT id_Tarif, tarif FROM Tarif')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Réservations</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .reservation-list { margin-top: 20px; }
        .reservation-list table { border-collapse: collapse; width: 100%; }
        .reservation-list th, .reservation-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .reservation-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Réservations</h2>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="date" name="date_debut" placeholder="Date début" required>
            <input type="date" name="date_fin" placeholder="Date fin" required>
            <select name="id_locataire" required>
                <option value="">-- Locataire --</option>
                <?php foreach ($locataires as $l): ?>
                    <option value="<?= $l['id_locataire'] ?>"><?= htmlspecialchars($l['nom_locataire'] . ' ' . $l['prenom_locataire']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_biens" required>
                <option value="">-- Bien --</option>
                <?php foreach ($biens as $b): ?>
                    <option value="<?= $b['id_biens'] ?>"><?= htmlspecialchars($b['nom_biens']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_tarif" required>
                <option value="">-- Tarif --</option>
                <?php foreach ($tarifs as $t): ?>
                    <option value="<?= $t['id_Tarif'] ?>"><?= htmlspecialchars($t['tarif']) ?> €</option>
                <?php endforeach; ?>
            </select>
            <input type="submit" name="add_reservation" value="Ajouter">
        </form>
        <div class="reservation-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Date début</th>
                    <th>Date fin</th>
                    <th>Locataire</th>
                    <th>Bien</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['id_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['date_debut_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['date_fin_reservation']) ?></td>
                        <td><?= htmlspecialchars($r['nom_locataire'] . ' ' . $r['prenom_locataire']) ?></td>
                        <td><?= htmlspecialchars($r['nom_biens']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette réservation ?');">
                                <input type="hidden" name="id_reservation" value="<?= htmlspecialchars($r['id_reservation']) ?>">
                                <button type="submit" name="delete_reservation">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
