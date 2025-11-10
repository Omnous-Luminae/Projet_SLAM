<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Locataire/Locataire.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$pdo = $pdo ?? null;
$message = '';
$userId = intval($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current = trim($_POST['current_password'] ?? '');
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        if ($new !== $confirm) {
            $message = 'Le nouveau mot de passe et la confirmation ne correspondent pas.';
        } else {
            // Verify current password
            $locClass = new Locataire(null, null, null, null, null, null, null, null, null, $pdo);
            $user = $locClass->getLocataireById($userId);
            if ($user && password_verify($current, $user['password_locataire'])) {
                $locClass->updateLocataire($userId, null, null, null, null, null, $new, null, null);
                $message = 'Mot de passe mis à jour.';
            } else {
                $message = 'Mot de passe actuel incorrect.';
            }
        }
    }

    // Fetch user's annonces (by created_by_id or created_by_name fallback)
    $stmt = $pdo->prepare('SELECT * FROM Biens WHERE (created_by_id = ? OR created_by_name = ?) ORDER BY id_biens DESC');
    $stmt->execute([$userId, $userName]);
    $userBiens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's reservations (only those made by this user)
    $reservationsStmt = $pdo->prepare('SELECT r.*, b.nom_biens, t.tarif, t.id_Tarif FROM Reservation r LEFT JOIN Biens b ON r.id_biens = b.id_biens LEFT JOIN Tarif t ON r.id_Tarif = t.id_Tarif WHERE r.id_locataire = ? ORDER BY r.date_debut_reservation DESC');
    $reservationsStmt->execute([$userId]);
    $userReservations = $reservationsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = 'Erreur: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Profil</title>
    <link rel="stylesheet" href="../Css/style.css">
    <link rel="stylesheet" href="../Css/profile.css">
</head>
<body>
    <div class="profile-container">
        <a href="/../index.php" class="back-link">&larr; Accueil</a>
        <h2>Mon profil</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="profile-section">
            <h3>Changer le mot de passe</h3>
            <form method="post" class="password-form">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="profile-button">Mettre à jour</button>
            </form>
        </section>
        <section class="profile-section">
            <h3>Mes réservations</h3>
            <?php if (!empty($userReservations)): ?>
                <div class="reservations-list">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#f3e6fa;">
                                <th style="padding:8px;border:1px solid #eee;">ID</th>
                                <th style="padding:8px;border:1px solid #eee;">Bien</th>
                                <th style="padding:8px;border:1px solid #eee;">Date début</th>
                                <th style="padding:8px;border:1px solid #eee;">Date fin</th>
                                <th style="padding:8px;border:1px solid #eee;">Tarif</th>
                                <th style="padding:8px;border:1px solid #eee;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($userReservations as $res): ?>
                            <tr>
                                <td style="padding:8px;border:1px solid #eee;"><?= htmlspecialchars($res['id_reservation']) ?></td>
                                <td style="padding:8px;border:1px solid #eee;"><a href="../forms/annonce_detail.php?id=<?= $res['id_biens'] ?>"><?= htmlspecialchars($res['nom_biens'] ?? '—') ?></a></td>
                                <td style="padding:8px;border:1px solid #eee;"><?= htmlspecialchars($res['date_debut_reservation']) ?></td>
                                <td style="padding:8px;border:1px solid #eee;"><?= htmlspecialchars($res['date_fin_reservation']) ?></td>
                                <td style="padding:8px;border:1px solid #eee;"><?= isset($res['tarif']) ? number_format($res['tarif'],2) . ' €' : '—' ?></td>
                                <td style="padding:8px;border:1px solid #eee;">
                                    <form method="post" action="../forms/Reservation.form.php" onsubmit="return confirm('Voulez-vous annuler cette réservation ?');" style="display:inline;">
                                        <input type="hidden" name="id_reservation" value="<?= htmlspecialchars($res['id_reservation']) ?>">
                                        <button type="submit" name="delete_reservation" class="profile-button">Annuler</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Vous n'avez effectué aucune réservation pour le moment.</p>
            <?php endif; ?>
        </section>

        <section class="profile-section">
            <h3>Mes annonces</h3>
            <div class="annonces-list">
                <?php if (!empty($userBiens)): foreach ($userBiens as $b): ?>
                    <div class="annonce-card">
                        <div class="annonce-title">
                            <a href="../forms/annonce_detail.php?id=<?= $b['id_biens'] ?>" aria-label="Voir l'annonce <?= htmlspecialchars($b['nom_biens']) ?>"><?= htmlspecialchars($b['nom_biens']) ?></a>
                        </div>
                        <div class="annonce-actions">
                            <a href="../forms/annonce_detail.php?id=<?= $b['id_biens'] ?>" aria-label="Voir et éditer l'annonce <?= htmlspecialchars($b['nom_biens']) ?>">Voir / Éditer</a>
                            <form method="post" action="../forms/Annonce.form.php" style="display:inline;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette annonce ?');">
                                <input type="hidden" name="id_biens" value="<?= $b['id_biens'] ?>">
                                <button type="submit" name="delete_bien">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; else: ?>
                    <p>Vous n'avez posté aucune annonce pour le moment.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
