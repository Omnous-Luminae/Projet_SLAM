<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un type de point d'intérêt
        if (isset($_POST['add_type_pts_interet'])) {
            $lib = trim($_POST['lib_type_points_interet'] ?? '');
            if ($lib !== '') {
                $stmt = $pdo->prepare('INSERT INTO Type_Pts_Interet (lib_type_points_interet) VALUES (?)');
                $stmt->execute([$lib]);
                $message = "Type de point d'intérêt ajouté avec succès.";
            }
        }

        // Suppression d'un type de point d'intérêt
        if (isset($_POST['delete_type_pts_interet']) && isset($_POST['id_type_points_interet'])) {
            $id = intval($_POST['id_type_points_interet']);
            $stmt = $pdo->prepare('DELETE FROM Type_Pts_Interet WHERE id_type_points_interet = ?');
            $stmt->execute([$id]);
            $message = "Type de point d'intérêt supprimé avec succès.";
        }

        // Récupération des types
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet ORDER BY id_type_points_interet DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<div class="form-section">
    <h3>Gestion des Types de Points d'Intérêt</h3>
    <?php if ($message): ?>
        <div class="success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="text" id="lib_type_points_interet" name="lib_type_points_interet" placeholder="Nom du type" required>
        <input type="submit" name="add_type_pts_interet" value="Ajouter">
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
                    <td><?= htmlspecialchars($t['id_type_points_interet']) ?></td>
                    <td><?= htmlspecialchars($t['lib_type_points_interet']) ?></td>
                    <td>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce type ?');">
                            <input type="hidden" name="id_type_points_interet" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                            <button type="submit" name="delete_type_pts_interet">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
