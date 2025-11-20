<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un point d'intérêt
        if (isset($_POST['add_pts_interet'])) {
            $lib = trim($_POST['lib_pts_interet'] ?? '');
            $desc = trim($_POST['description_pts_interet'] ?? '');
            $id_type = intval($_POST['id_type_points_interet'] ?? 0);

            if ($lib && $desc && $id_type) {
                $stmt = $pdo->prepare('INSERT INTO Pts_Interet (lib_pts_interet, description_pts_interet, id_type_points_interet) VALUES (?, ?, ?)');
                $stmt->execute([$lib, $desc, $id_type]);
                $message = "Point d'intérêt ajouté avec succès.";
            } else {
                $message = "Veuillez remplir tous les champs.";
            }
        }

        // Suppression d'un point d'intérêt
        if (isset($_POST['delete_pts_interet']) && isset($_POST['id_pts_interet'])) {
            $id = intval($_POST['id_pts_interet']);
            $stmt = $pdo->prepare('DELETE FROM Pts_Interet WHERE id_pts_interet = ?');
            $stmt->execute([$id]);
            $message = "Point d'intérêt supprimé avec succès.";
        }

        // Modification d'un point d'intérêt
        if (isset($_POST['edit_pts_interet']) && isset($_POST['id_pts_interet'])) {
            $id = intval($_POST['id_pts_interet']);
            $lib = trim($_POST['lib_pts_interet_edit'] ?? '');
            $desc = trim($_POST['description_pts_interet_edit'] ?? '');
            $id_type = intval($_POST['id_type_points_interet_edit'] ?? 0);

            if ($lib && $desc && $id_type) {
                $stmt = $pdo->prepare('UPDATE Pts_Interet SET lib_pts_interet = ?, description_pts_interet = ?, id_type_points_interet = ? WHERE id_pts_interet = ?');
                $stmt->execute([$lib, $desc, $id_type, $id]);
                $message = "Point d'intérêt modifié avec succès.";
            } else {
                $message = "Veuillez remplir tous les champs obligatoires.";
            }
        }

        // Récupération des points d'intérêt
        $pts_interets = $pdo->query('SELECT p.*, t.lib_type_points_interet FROM Pts_Interet p LEFT JOIN Type_Pts_Interet t ON p.id_type_points_interet = t.id_type_points_interet ORDER BY p.id_pts_interet DESC')->fetchAll(PDO::FETCH_ASSOC);

        // Types
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Points d'Intérêt</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background: #f7f7f9; margin: 0; }
        .container { max-width: 1000px; margin: 40px auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 16px rgba(80,0,80,0.06); padding: 40px 30px; }
        h2 { text-align: center; margin-bottom: 28px; }
        .add-form { display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; max-width: 600px; margin-left: auto; margin-right: auto; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 5px; color: #333; }
        input, select, textarea { padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; }
        input[type="text"], textarea { width: 100%; }
        textarea { min-height: 80px; resize: vertical; }
        select { width: 100%; }
        input[type="submit"], button { background: #a100b8; color: #fff; border: none; border-radius: 6px; padding: 8px 18px; font-weight: 600; cursor: pointer; }
        input[type="submit"]:hover, button:hover { background: #4b006e; }
        .pts-list { margin-top: 20px; }
        .pts-list table { border-collapse: collapse; width: 100%; }
        .pts-list th, .pts-list td { border: 1px solid #ccc; padding: 8px 12px; text-align: center; }
        .pts-list th { background: #f3e6fa; }
        .success { color: green; text-align: center; margin-bottom: 18px; }
        .error { color: red; text-align: center; margin-bottom: 18px; }
        .back-link { display: block; margin-bottom: 18px; color: #a100b8; text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">&larr; Retour à l'accueil</a>
        <h2>Gestion des Points d'Intérêt</h2>
        <?php if ($message): ?>
            <div class="<?= strpos($message, 'Erreur') === 0 ? 'error' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" class="add-form">
            <div class="form-group">
                <label for="lib_pts_interet">Nom du point d'intérêt</label>
                <input type="text" id="lib_pts_interet" name="lib_pts_interet" placeholder="Nom du point d'intérêt" required>
            </div>
            <div class="form-group">
                <label for="description_pts_interet">Description</label>
                <textarea id="description_pts_interet" name="description_pts_interet" placeholder="Description" required></textarea>
            </div>
            <div class="form-group">
                <label for="id_type_points_interet">Type</label>
                <select id="id_type_points_interet" name="id_type_points_interet" required>
                    <option value="">-- Sélectionner un type --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= $t['id_type_points_interet'] ?>"><?= htmlspecialchars($t['lib_type_points_interet']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="submit" name="add_pts_interet" value="Ajouter">
        </form>
        <div class="pts-list">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <div>
                    <input id="pts-search" type="text" placeholder="Rechercher un point d'intérêt..." style="padding:8px;border-radius:6px;border:1px solid #ccc;min-width:260px;">
                </div>
                <div style="font-size:0.9em;color:#666;">Total : <?= count($pts_interets) ?></div>
            </div>

            <div class="pts-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
                <?php foreach ($pts_interets as $p): ?>
                    <div class="pts-card" data-title="<?= htmlspecialchars($p['lib_pts_interet']) ?>" data-type="<?= htmlspecialchars($p['lib_type_points_interet']) ?>" style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 6px 18px rgba(0,0,0,0.04);position:relative;">
                        <h3 style="margin:0 0 8px 0;font-size:1.05em;"><?= htmlspecialchars($p['lib_pts_interet']) ?></h3>
                        <div style="font-size:0.85em;color:#777;margin-bottom:8px;"><strong>Type:</strong> <?= htmlspecialchars($p['lib_type_points_interet']) ?></div>
                        <div class="desc" style="max-height:52px;overflow:hidden;color:#444;"><?= nl2br(htmlspecialchars($p['description_pts_interet'])) ?></div>
                        <button type="button" class="toggle-desc" style="margin-top:8px;background:none;border:none;color:#a100b8;cursor:pointer;padding:0;">Voir plus</button>

                        <div style="display:flex;gap:8px;margin-top:12px;align-items:center;">
                            <form method="post" style="display:inline;margin:0;">
                                <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($p['id_pts_interet']) ?>">
                                <button type="submit" name="edit_mode" value="<?= htmlspecialchars($p['id_pts_interet']) ?>" style="background:#fff;border:1px solid #a100b8;color:#a100b8;padding:6px 10px;border-radius:6px;">Modifier</button>
                            </form>
                            <form method="post" style="display:inline;margin:0;" onsubmit="return confirm('Supprimer ce point d\'intérêt ?');">
                                <input type="hidden" name="id_pts_interet" value="<?= htmlspecialchars($p['id_pts_interet']) ?>">
                                <button type="submit" name="delete_pts_interet" style="background:#a100b8;border:none;color:#fff;padding:6px 10px;border-radius:6px;">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
<script src="../js/confirm_delete.js"></script>
<script>
    (function(){
        const search = document.getElementById('pts-search');
        if (!search) return;
        const cards = Array.from(document.querySelectorAll('.pts-card'));
        search.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            cards.forEach(c => {
                const title = (c.getAttribute('data-title')||'').toLowerCase();
                const type = (c.getAttribute('data-type')||'').toLowerCase();
                const match = title.includes(q) || type.includes(q);
                c.style.display = match ? '' : 'none';
            });
        });

        // Toggle description
        document.querySelectorAll('.toggle-desc').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.pts-card');
                const desc = card.querySelector('.desc');
                if (desc.style.maxHeight && desc.style.maxHeight !== '52px') {
                    desc.style.maxHeight = '52px';
                    this.textContent = 'Voir plus';
                } else {
                    desc.style.maxHeight = 'none';
                    this.textContent = 'Voir moins';
                }
            });
        });
    })();
</script>
