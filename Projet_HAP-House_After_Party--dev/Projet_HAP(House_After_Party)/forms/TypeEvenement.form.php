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
<style>
    .form-section { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
    .form-section h3 { text-align: center; margin-bottom: 28px; }
    .form-section form { display: flex; gap: 10px; margin-bottom: 20px; justify-content: center; }
    .form-section input[type="text"] { flex: 1; padding: 8px; border-radius: 6px; border: 1px solid #ccc; }
    .form-section input[type="submit"], .form-section button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
    .form-section input[type="submit"]:hover, .form-section button:hover { background: #4b006e; }
    .form-section .type-list { margin-top: 20px; }
    .form-section .type-list table { border-collapse: collapse; width: 100%; }
    .form-section .type-list th, .form-section .type-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
    .form-section .type-list th { background: #f3e6fa; }
    .form-section .success { color: green; text-align: center; margin-bottom: 18px; }
</style>
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
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $t['id_type_evenement']): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_type_evenement" value="<?= htmlspecialchars($t['id_type_evenement']) ?>">
                                    <input type="text" name="lib_type_evenement_edit" value="<?= htmlspecialchars($t['lib_type_evenement']) ?>" required>
                                    <button type="submit" name="edit_type_evenement">Enregistrer</button>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($t['lib_type_evenement']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $t['id_type_evenement']): ?>
                            <?php else: ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($t['id_type_evenement']) ?>">
                                    <button type="submit">Modifier</button>
                                </form>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce type ?');">
                                    <input type="hidden" name="id_type_evenement" value="<?= htmlspecialchars($t['id_type_evenement']) ?>">
                                    <button type="submit" name="delete_type_evenement">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
</div>

<script src="../js/confirm_delete.js"></script>
