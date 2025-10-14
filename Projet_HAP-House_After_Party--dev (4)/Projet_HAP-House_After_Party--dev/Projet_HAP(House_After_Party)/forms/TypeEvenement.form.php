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

        // Récupération des types
        $types = $pdo->query('SELECT * FROM Type_Evenement ORDER BY id_type_evenement DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<div class="form-section">
    <h3>Gestion des Types d'Événements</h3>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" id="lib_type_evenement" name="lib_type_evenement" placeholder="Nom du type d'événement" required>
            <input type="submit" name="add_type_evenement" value="Ajouter">
        </form>
        <div class="type-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($types as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['id_type_evenement']) ?></td>
                        <td><?= htmlspecialchars($t['lib_type_evenement']) ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce type ?');">
                                <input type="hidden" name="id_type_evenement" value="<?= htmlspecialchars($t['id_type_evenement']) ?>">
                                <button type="submit" name="delete_type_evenement">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
</div>
