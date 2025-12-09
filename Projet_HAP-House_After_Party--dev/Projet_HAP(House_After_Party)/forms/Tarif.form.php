<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un tarif
        if (isset($_POST['add_tarif'])) {
            $semaine = intval($_POST['semaine_tarif'] ?? 0);
            $annee = intval($_POST['annee_tarif'] ?? 0);
            $tarif = floatval($_POST['tarif'] ?? 0);
            $id_saison = intval($_POST['id_saison'] ?? 0);
            $id_biens = intval($_POST['id_biens'] ?? 0);

            if ($semaine && $annee && $tarif && $id_saison && $id_biens) {
                $stmt = $pdo->prepare('INSERT INTO Tarif (semaine_Tarif, année_Tarif, tarif, id_saison, id_biens) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$semaine, $annee, $tarif, $id_saison, $id_biens]);
                $message = "Tarif ajouté avec succès.";
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Suppression d'un tarif
        if (isset($_POST['delete_tarif']) && isset($_POST['id_tarif'])) {
            $id = intval($_POST['id_tarif']);
            $stmt = $pdo->prepare('DELETE FROM Tarif WHERE id_Tarif = ?');
            $stmt->execute([$id]);
            $message = "Tarif supprimé avec succès.";
        }

        // Récupération des tarifs
        $tarifs = [];
        $stmt = $pdo->query('SELECT t.*, s.lib_saison, b.nom_biens FROM Tarif t LEFT JOIN Saison s ON t.id_saison = s.id_saison LEFT JOIN Biens b ON t.id_biens = b.id_biens ORDER BY t.id_Tarif DESC');
        $tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des saisons et biens pour les selects
        $saisons = $pdo->query('SELECT id_saison, lib_saison FROM Saison')->fetchAll(PDO::FETCH_ASSOC);
        $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tarifs - House After Party</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="../Css/forms.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>💰 Gestion des Tarifs</h2>
            <p>Gérez les tarifs des biens par saison et semaine</p>
        </div>

        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="form-section">
            <h3>Ajouter un nouveau tarif</h3>
            <form method="post" class="form-grid">
                <div class="form-group">
                    <label for="semaine_tarif">Semaine</label>
                    <input type="number" id="semaine_tarif" name="semaine_tarif" placeholder="1-52" min="1" max="52" required>
                </div>
                <div class="form-group">
                    <label for="annee_tarif">Année</label>
                    <input type="number" id="annee_tarif" name="annee_tarif" placeholder="2024" min="2020" required>
                </div>
                <div class="form-group">
                    <label for="tarif">Tarif (€)</label>
                    <input type="number" id="tarif" name="tarif" step="0.01" placeholder="150.00" min="0" required>
                </div>
                <div class="form-group">
                    <label for="id_saison">Saison</label>
                    <select id="id_saison" name="id_saison" required>
                        <option value="">-- Sélectionner une saison --</option>
                        <?php foreach ($saisons as $saison): ?>
                            <option value="<?= $saison['id_saison'] ?>"><?= htmlspecialchars($saison['lib_saison']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_biens">Bien</label>
                    <select id="id_biens" name="id_biens" required>
                        <option value="">-- Sélectionner un bien --</option>
                        <?php foreach ($biens as $bien): ?>
                            <option value="<?= $bien['id_biens'] ?>"><?= htmlspecialchars($bien['nom_biens']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_tarif" class="btn btn-primary">Ajouter le tarif</button>
                </div>
            </form>
        </section>

        <section class="data-section">
            <h3>Tarifs existants</h3>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Semaine</th>
                            <th>Année</th>
                            <th>Tarif</th>
                            <th>Saison</th>
                            <th>Bien</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tarifs as $tarif): ?>
                            <tr>
                                <td><?= htmlspecialchars($tarif['id_Tarif']) ?></td>
                                <td><?= htmlspecialchars($tarif['semaine_Tarif']) ?></td>
                                <td><?= htmlspecialchars($tarif['année_Tarif']) ?></td>
                                <td><?= htmlspecialchars(number_format($tarif['tarif'], 2, ',', ' ')) ?> €</td>
                                <td><?= htmlspecialchars($tarif['lib_saison']) ?></td>
                                <td><?= htmlspecialchars($tarif['nom_biens']) ?></td>
                                <td class="actions">
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce tarif ?');">
                                        <input type="hidden" name="id_tarif" value="<?= htmlspecialchars($tarif['id_Tarif']) ?>">
                                        <button type="submit" name="delete_tarif" class="btn btn-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="../js/confirm_delete.js"></script>
</body>
</html>
