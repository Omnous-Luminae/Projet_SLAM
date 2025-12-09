<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un type d'événement
        if (isset($_POST['add_type_evenement'])) {
            $lib = trim($_POST['lib_type_evenement'] ?? '');
            if ($lib !== '') {
                $stmt = $pdo->prepare('INSERT INTO Type_Evenement (lib_type_evenement) VALUES (?)');
                $stmt->execute([$lib]);
                $message = "Type d'événement ajouté avec succès.";
            }
        }

        // Suppression d'un type d'événement
        if (isset($_POST['delete_type_evenement']) && isset($_POST['id_type_evenement'])) {
            $id = intval($_POST['id_type_evenement']);
            $stmt = $pdo->prepare('DELETE FROM Type_Evenement WHERE id_type_evenement = ?');
            $stmt->execute([$id]);
            $message = "Type d'événement supprimé avec succès.";
        }

        // Modification d'un type d'événement
        if (isset($_POST['edit_type_evenement']) && isset($_POST['id_type_evenement']) && isset($_POST['lib_type_evenement_edit'])) {
            $id = intval($_POST['id_type_evenement']);
            $lib = trim($_POST['lib_type_evenement_edit']);
            if ($lib !== '') {
                $stmt = $pdo->prepare('UPDATE Type_Evenement SET lib_type_evenement = ? WHERE id_type_evenement = ?');
                $stmt->execute([$lib, $id]);
                $message = "Type d'événement modifié avec succès.";
            }
        }

        // Récupération des types
        $types = $pdo->query('SELECT * FROM Type_Evenement ORDER BY id_type_evenement DESC')->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Gestion des Types d'Événements - House After Party</title>
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
            <h2>🎪 Gestion des Types d'Événements</h2>
            <p>Gérez les différents types d'événements disponibles</p>
        </div>

        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="form-section">
            <h3>Ajouter un nouveau type d'événement</h3>
            <form method="post" class="form-grid">
                <div class="form-group">
                    <label for="lib_type_evenement">Nom du type d'événement</label>
                    <input type="text" id="lib_type_evenement" name="lib_type_evenement" placeholder="Ex: Mariage, Anniversaire..." required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_type_evenement" class="btn btn-primary">Ajouter le type</button>
                </div>
            </form>
        </section>

        <section class="data-section">
            <h3>Types d'événements existants</h3>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($types as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['id_type_evenement']) ?></td>
                                <td>
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $t['id_type_evenement']): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id_type_evenement" value="<?= htmlspecialchars($t['id_type_evenement']) ?>">
                                            <input type="text" name="lib_type_evenement_edit" value="<?= htmlspecialchars($t['lib_type_evenement']) ?>" required>
                                            <button type="submit" name="edit_type_evenement" class="btn btn-primary">Enregistrer</button>
                                        </form>
                                    <?php else: ?>
                                        <?= htmlspecialchars($t['lib_type_evenement']) ?>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $t['id_type_evenement']): ?>
                                        <!-- Rien, on est en mode édition -->
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($t['id_type_evenement']) ?>">
                                            <button type="submit" class="btn btn-secondary">Modifier</button>
                                        </form>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce type ?');">
                                            <input type="hidden" name="id_type_evenement" value="<?= htmlspecialchars($t['id_type_evenement']) ?>">
                                            <button type="submit" name="delete_type_evenement" class="btn btn-danger">Supprimer</button>
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

    <script src="../js/confirm_delete.js"></script>
</body>
</html>
