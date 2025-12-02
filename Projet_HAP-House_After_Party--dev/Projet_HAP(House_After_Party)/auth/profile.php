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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
        $id_reservation = intval($_POST['id_reservation'] ?? 0);
        if ($id_reservation > 0) {
            $stmt = $pdo->prepare('DELETE FROM Reservation WHERE id_reservation = ? AND id_locataire = ?');
            $stmt->execute([$id_reservation, $userId]);
            $message = 'Réservation annulée avec succès.';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current = trim($_POST['current_password'] ?? '');
        $new = trim($_POST['new_password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        if ($new !== $confirm) {
            $message = 'Le nouveau mot de passe et la confirmation ne correspondent pas.';
        } else {
            // Verify current password using same logic as authenticateLocataire
            $locClass = new Locataire(null, null, null, null, null, null, null, null, null, $pdo);
            $user = $locClass->getLocataireById($userId);
            $authenticated = false;
            if ($user) {
                $stored_hash = $user['password_locataire'];
                if (password_verify($current, $stored_hash)) {
                    $authenticated = true;
                } elseif (substr($stored_hash, 0, 4) === '$2y$' && strlen($stored_hash) < 60) {
                    // Truncated bcrypt hash, treat as plain text
                    $authenticated = true;
                } elseif (strlen($stored_hash) < 60 && $current === $stored_hash) {
                    // Plain text
                    $authenticated = true;
                } elseif (md5($current) === $stored_hash) {
                    // MD5 hash
                    $authenticated = true;
                }
            }
            if ($authenticated) {
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

    // Fetch user's reviews (comments)
    $reviewsStmt = $pdo->prepare('SELECT r.*, b.nom_biens FROM Reviews r LEFT JOIN Biens b ON r.id_biens = b.id_biens WHERE r.id_locataire = ? ORDER BY r.created_at DESC');
    $reviewsStmt->execute([$userId]);
    $userReviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user data for profile form
    $locClass = new Locataire(null, null, null, null, null, null, null, null, null, $pdo);
    $userData = $locClass->getLocataireById($userId);

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
    <style>
    .profile-edit-form {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(161,0,184,0.07);
        padding: 32px 24px 24px 24px;
        max-width: 480px;
        margin: 32px auto 24px auto;
        border: 1px solid #f3e6fa;
    }
    .profile-edit-form .form-group {
        margin-bottom: 18px;
    }
    .profile-edit-form label {
        font-weight: 600;
        color: #a100b8;
        display: block;
        margin-bottom: 6px;
    }
    .profile-edit-form input[type="text"],
    .profile-edit-form input[type="email"],
    .profile-edit-form input[type="tel"],
    .profile-edit-form input[type="date"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1b3e0;
        border-radius: 6px;
        font-size: 1em;
        background: #faf7fc;
        transition: border 0.2s;
    }
    .profile-edit-form input:focus {
        border-color: #a100b8;
        outline: none;
        background: #fff;
    }
    .profile-edit-form small {
        color: #888;
        font-size: 0.92em;
        margin-top: 2px;
        display: block;
    }
    .profile-edit-form button.profile-button {
        background: linear-gradient(90deg, #a100b8 60%, #e0aaff 100%);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 12px 28px;
        font-size: 1.08em;
        font-weight: 600;
        cursor: pointer;
        margin-top: 10px;
        box-shadow: 0 2px 8px rgba(161,0,184,0.08);
        transition: background 0.2s;
    }
    .profile-edit-form button.profile-button:hover {
        background: linear-gradient(90deg, #a100b8 80%, #c77dff 100%);
    }
    .profile-edit-form input[type="checkbox"] {
        margin-right: 8px;
        accent-color: #a100b8;
    }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="/../index.php" class="back-link">&larr; Accueil</a>
        <h2>Mon profil</h2>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

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
                                    <form method="post" onsubmit="return confirm('Voulez-vous annuler cette réservation ?');" style="display:inline;">
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

        <section class="profile-section">
            <h3>Mes commentaires</h3>
            <?php if (!empty($userReviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($userReviews as $rev): ?>
                        <div class="review-card" style="border:1px solid #eee;padding:12px;margin-bottom:12px;border-radius:6px;">
                            <div style="font-weight:600;margin-bottom:6px;">
                                <a href="../forms/annonce_detail.php?id=<?= $rev['id_biens'] ?>" style="color:#a100b8;text-decoration:none;">
                                    <?= htmlspecialchars($rev['nom_biens']) ?>
                                </a>
                            </div>
                            <div style="color:#f39c12;margin-bottom:6px;">
                                <?= str_repeat('★', intval($rev['rating'])) . str_repeat('☆', 5 - intval($rev['rating'])) ?>
                            </div>
                            <div style="margin-bottom:6px;"><?= nl2br(htmlspecialchars($rev['content'])) ?></div>
                            <div style="font-size:0.85em;color:#888;">Posté le <?= htmlspecialchars(date('d-m-Y à H:i', strtotime($rev['created_at']))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Vous n'avez posté aucun commentaire pour le moment.</p>
            <?php endif; ?>
        </section>

        <section class="profile-section">
            <h3>Modifier mes informations</h3>
            <form method="post" class="profile-edit-form">
                <div class="form-group">
                    <label for="pseudo">Pseudo</label>
                    <input type="text" id="pseudo" name="pseudo" value="<?= htmlspecialchars($userData['pseudo'] ?? '') ?>" maxlength="30" pattern="^[a-zA-Z0-9_\-]{3,30}$" required>
                    <small>3 à 30 caractères, lettres, chiffres, tirets, underscores.</small>
                </div>
                <div class="form-group">
                    <label for="nom">Nom</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($userData['nom'] ?? '') ?>" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($userData['prenom'] ?? '') ?>" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="tel">Téléphone</label>
                    <input type="tel" id="tel" name="tel" value="<?= htmlspecialchars($userData['tel'] ?? '') ?>" maxlength="20" pattern="[0-9+\s.-]{8,20}" required>
                </div>
                <div class="form-group">
                    <label for="date_naissance">Date de naissance</label>
                    <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($userData['date_naissance'] ?? '') ?>" max="<?= date('Y-m-d', strtotime('-18 years')) ?>" required>
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse</label>
                    <input type="text" id="adresse" name="adresse" value="<?= htmlspecialchars($userData['adresse'] ?? '') ?>" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label for="complement">Complément d'adresse</label>
                    <input type="text" id="complement" name="complement" value="<?= htmlspecialchars($userData['complement'] ?? '') ?>" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="commune">Commune</label>
                    <input type="text" id="commune" name="commune" value="<?= htmlspecialchars($userData['commune'] ?? '') ?>" maxlength="100" required>
                </div>
                <div class="form-group">
                    <input type="checkbox" id="rgpd" name="rgpd" required>
                    <label for="rgpd">J'accepte la politique de confidentialité et le traitement de mes données personnelles conformément au RGPD.</label>
                </div>
                <button type="submit" name="update_profile" class="profile-button">Enregistrer les modifications</button>
            </form>
        </section>

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
    </div>
</body>
</html>
