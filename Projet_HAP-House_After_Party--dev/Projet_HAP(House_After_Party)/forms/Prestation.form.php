<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'une prestation
        if (isset($_POST['add_prestation'])) {
            $lib = trim($_POST['lib_prestation'] ?? '');
            if ($lib !== '') {
                $stmt = $pdo->prepare('INSERT INTO Prestation (lib_prestation) VALUES (?)');
                $stmt->execute([$lib]);
                $message = "Prestation ajoutée avec succès.";
            }
        }

        // Suppression d'une prestation
        if (isset($_POST['delete_prestation']) && isset($_POST['id_prestation'])) {
            $id = intval($_POST['id_prestation']);
            $stmt = $pdo->prepare('DELETE FROM Prestation WHERE id_prestation = ?');
            $stmt->execute([$id]);
            $message = "Prestation supprimée avec succès.";
        }

        // Modification d'une prestation
        if (isset($_POST['edit_prestation']) && isset($_POST['id_prestation']) && isset($_POST['lib_prestation_edit'])) {
            $id = intval($_POST['id_prestation']);
            $lib = trim($_POST['lib_prestation_edit']);
            if ($lib !== '') {
                $stmt = $pdo->prepare('UPDATE Prestation SET lib_prestation = ? WHERE id_prestation = ?');
                $stmt->execute([$lib, $id]);
                $message = "Prestation modifiée avec succès.";
            }
        }

        // Récupération des prestations
        $prestations = $pdo->query('SELECT * FROM Prestation ORDER BY id_prestation DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Prestations</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/forms.css">
    <style>
        .prestation-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
            align-items: end;
        }
        .prestation-form input[type="text"] {
            min-width: 250px;
        }
        .prestation-list {
            margin-top: 40px;
        }
        .prestation-list table {
            border-collapse: collapse;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        .prestation-list th,
        .prestation-list td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        .prestation-list th {
            background: linear-gradient(135deg, #a100b8, #d100e8);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        .prestation-list tr:nth-child(even) {
            background: #f8f9fa;
        }
        .prestation-list tr:hover {
            background: rgba(161, 0, 184, 0.05);
            transition: background 0.3s ease;
        }
        .prestation-list .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚽ Gestion des Prestations</h2>
            <p>Gérez les équipements sportifs et de loisirs disponibles pour les biens</p>
        </div>
        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="form-section">
            <h3>Ajouter une nouvelle prestation</h3>
            <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                Exemples : Terrain de football, Piscine privée, Jacuzzi, Salle de sport, Baby-foot, etc.
            </p>
            <form method="post" class="prestation-form">
                <input type="text" id="lib_prestation" name="lib_prestation" placeholder="Ex: Terrain de tennis, Bar privé..." required>
                <button type="submit" name="add_prestation" class="btn btn-primary">Ajouter</button>
            </form>
        </section>

        <section class="data-section">
            <h3>Équipements et prestations existants</h3>
            <div class="prestation-list">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Équipement / Prestation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prestations as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['id_prestation']) ?></td>
                                <td>
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $p['id_prestation']): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id_prestation" value="<?= htmlspecialchars($p['id_prestation']) ?>">
                                            <input type="text" name="lib_prestation_edit" value="<?= htmlspecialchars($p['lib_prestation']) ?>" required>
                                            <button type="submit" name="edit_prestation" class="btn btn-primary">Enregistrer</button>
                                        </form>
                                    <?php else: ?>
                                        <?= htmlspecialchars($p['lib_prestation']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $p['id_prestation']): ?>
                                        <!-- Rien, on est en mode édition -->
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($p['id_prestation']) ?>">
                                            <button type="submit" class="btn btn-secondary">Modifier</button>
                                        </form>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette prestation ?');">
                                            <input type="hidden" name="id_prestation" value="<?= htmlspecialchars($p['id_prestation']) ?>">
                                            <button type="submit" name="delete_prestation" class="btn btn-danger">Supprimer</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
<script src="../js/confirm_delete.js"></script>
