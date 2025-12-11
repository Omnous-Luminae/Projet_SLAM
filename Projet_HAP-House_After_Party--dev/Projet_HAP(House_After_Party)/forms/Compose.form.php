<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'animateur') {
    // Sauvegarder la page courante pour y revenir après la connexion
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Gestion des Compositions</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f7; margin: 0; }
            .container { max-width: 600px; margin: 80px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; text-align: center; }
            h2 { margin-bottom: 28px; }
            .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
            .back-link:hover { text-decoration: underline; }
            .info { color: #a100b8; font-size: 1.2em; margin-top: 40px; }
            .login-link { display: inline-block; margin-top: 20px; background: #a100b8; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 6px; }
            .login-link:hover { background: #4b006e; }
        </style>
    </head>
    <body>
        <div class="container">
            <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
            <h2>Gestion des Compositions</h2>
            <div class="info">Cette section est réservée aux administrateurs.<br>Veuillez vous connecter avec un compte administrateur pour y accéder.</div>
            <a href="../auth/connexion_admin.php?key=admin_access_2023" class="login-link">Se connecter en tant qu'administrateur</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'une composition
        if (isset($_POST['add_compose'])) {
            $id_biens = intval($_POST['id_biens'] ?? 0);
            $id_prestation = intval($_POST['id_prestation'] ?? 0);
            $quantite = intval($_POST['quantite'] ?? 0);

            if ($id_biens && $id_prestation && $quantite > 0) {
                $stmt = $pdo->prepare('INSERT INTO Compose (id_biens, id_prestation, quantite) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite)');
                $stmt->execute([$id_biens, $id_prestation, $quantite]);
                $message = "Composition ajoutée avec succès.";
            }
        }

        // Suppression d'une composition
        if (isset($_POST['delete_compose']) && isset($_POST['id_biens_del']) && isset($_POST['id_prestation_del'])) {
            $id_biens = intval($_POST['id_biens_del']);
            $id_prestation = intval($_POST['id_prestation_del']);
            $stmt = $pdo->prepare('DELETE FROM Compose WHERE id_biens = ? AND id_prestation = ?');
            $stmt->execute([$id_biens, $id_prestation]);
            $message = "Composition supprimée avec succès.";
        }

        // Modification d'une composition
        if (isset($_POST['edit_compose']) && isset($_POST['id_biens_edit']) && isset($_POST['id_prestation_edit']) && isset($_POST['quantite_edit'])) {
            $id_biens = intval($_POST['id_biens_edit']);
            $id_prestation = intval($_POST['id_prestation_edit']);
            $quantite = intval($_POST['quantite_edit']);
            if ($quantite > 0) {
                $stmt = $pdo->prepare('UPDATE Compose SET quantite = ? WHERE id_biens = ? AND id_prestation = ?');
                $stmt->execute([$quantite, $id_biens, $id_prestation]);
                $message = "Composition modifiée avec succès.";
            }
        }

        // Récupération du filtre par bien si spécifié
        $filter_bien = isset($_GET['id_bien']) ? intval($_GET['id_bien']) : 0;
        
        // Récupération des compositions avec filtre optionnel
        if ($filter_bien > 0) {
            $stmt = $pdo->prepare('SELECT c.*, b.nom_biens, p.lib_prestation FROM Compose c LEFT JOIN Biens b ON c.id_biens = b.id_biens LEFT JOIN Prestation p ON c.id_prestation = p.id_prestation WHERE c.id_biens = ? ORDER BY c.id_prestation');
            $stmt->execute([$filter_bien]);
            $composes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $composes = $pdo->query('SELECT c.*, b.nom_biens, p.lib_prestation FROM Compose c LEFT JOIN Biens b ON c.id_biens = b.id_biens LEFT JOIN Prestation p ON c.id_prestation = p.id_prestation ORDER BY c.id_biens, c.id_prestation')->fetchAll(PDO::FETCH_ASSOC);
        }

    // Biens et prestations
    $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
    $selectedBien = isset($_GET['bien_id']) ? intval($_GET['bien_id']) : 0;
        $prestations = $pdo->query('SELECT id_prestation, lib_prestation FROM Prestation')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Compositions</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1200px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; justify-content: center; }
        input, select { padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .compose-list { margin-top: 20px; }
        .compose-list table { border-collapse: collapse; width: 100%; }
        .compose-list th, .compose-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .compose-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Compositions (Biens - Prestations)</h2>
        
        <!-- Filtre par bien -->
        <div style="margin-bottom: 20px; text-align: center;">
            <form method="get" style="display: inline-flex; gap: 10px;">
                <select name="id_bien" onchange="this.form.submit()" style="padding: 10px;">
                    <option value="0">-- Tous les biens --</option>
                    <?php foreach ($biens as $b): ?>
                        <option value="<?= $b['id_biens'] ?>" <?= $filter_bien == $b['id_biens'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['nom_biens']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filter_bien > 0): ?>
                    <a href="Compose.form.php" style="padding: 10px; background: #f44336; color: white; text-decoration: none; border-radius: 6px;">Réinitialiser le filtre</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <select name="id_biens" required>
                <option value="">-- Bien --</option>
                <?php foreach ($biens as $b): ?>
                    <option value="<?= $b['id_biens'] ?>" <?= $filter_bien == $b['id_biens'] ? 'selected' : '' ?>><?= htmlspecialchars($b['nom_biens']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="id_prestation" required>
                <option value="">-- Prestation --</option>
                <?php foreach ($prestations as $p): ?>
                    <option value="<?= $p['id_prestation'] ?>"><?= htmlspecialchars($p['lib_prestation']) ?></option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="quantite" placeholder="Quantité" min="1" required>
            <input type="submit" name="add_compose" value="Ajouter">
        </form>

        <div class="compose-list">
            <table>
                <thead>
                    <tr>
                        <th>Bien</th>
                        <th>Prestation</th>
                        <th>Quantité</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($composes)): foreach ($composes as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nom_biens']) ?></td>
                            <td><?= htmlspecialchars($c['lib_prestation']) ?></td>
                            <td>
                                <form method="post" style="display:inline;margin:0;">
                                    <input type="hidden" name="id_biens_edit" value="<?= $c['id_biens'] ?>">
                                    <input type="hidden" name="id_prestation_edit" value="<?= $c['id_prestation'] ?>">
                                    <input type="number" name="quantite_edit" value="<?= $c['quantite'] ?>" min="1" style="width:60px;">
                                    <input type="submit" name="edit_compose" value="✓" style="padding:4px 8px;">
                                </form>
                            </td>
                            <td>
                                <form method="post" style="display:inline;margin:0;" onsubmit="return confirm('Voulez-vous vraiment supprimer cette composition ?');">
                                    <input type="hidden" name="id_biens_del" value="<?= $c['id_biens'] ?>">
                                    <input type="hidden" name="id_prestation_del" value="<?= $c['id_prestation'] ?>">
                                    <input type="submit" name="delete_compose" value="×" style="background:#e74c3c;">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="4">Aucune composition trouvée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
