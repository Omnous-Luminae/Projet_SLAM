<?php
require_once __DIR__ . '/../config/db.php';


$message = '';

try {
    if (!isset($pdo)) {
        throw new Exception('Connexion PDO introuvable. Vérifiez config/db.php');
    }

    // traitement du formulaire (création / mise à jour / suppression / chargement édition)
    $editing = false;
    $editing_data = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // suppression d'une annonce
        if (isset($_POST['delete_annonce']) && isset($_POST['id_annonce'])) {
            $id = intval($_POST['id_annonce']);

            // supprimer les fichiers photos du disque
            $stmtp = $pdo->prepare('SELECT lien_photo FROM Photos WHERE id_biens = :id_biens');
            $stmtp->execute([':id_biens' => $id]);
            $photos = $stmtp->fetchAll(PDO::FETCH_COLUMN);
            foreach ($photos as $lien) {
                $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($lien, '/');
                if (file_exists($path)) @unlink($path);
            }

            // supprimer en base
            $stmt = $pdo->prepare('DELETE FROM Photos WHERE id_biens = :id_biens');
            $stmt->execute([':id_biens' => $id]);
            $stmt = $pdo->prepare('DELETE FROM Biens WHERE id_biens = :id_biens');
            $res = $stmt->execute([':id_biens' => $id]);
            $message = $res ? 'Annonce supprimée.' : 'Erreur lors de la suppression.';

        // passer en mode édition (charge les données pour pré-remplir le formulaire)
        } elseif (isset($_POST['edit_mode'])) {
            $id = intval($_POST['edit_mode']);
            $stmt = $pdo->prepare('SELECT * FROM Biens WHERE id_biens = :id_biens');
            $stmt->execute([':id_biens' => $id]);
            $editing_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($editing_data) $editing = true;

        // mise à jour d'une annonce existante
        } elseif (isset($_POST['update_annonce']) && isset($_POST['id_biens'])) {
            $id_biens = intval($_POST['id_biens']);

            // Champs principaux
            $nom = trim($_POST['nom_bien'] ?? '');
            $rue = trim($_POST['rue_bien'] ?? '');
            $superficie = intval($_POST['superficie_bien'] ?? 0);
            $description = trim($_POST['description_bien'] ?? '');
            $animal = isset($_POST['animal_bien']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 1);
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type_biens = intval($_POST['id_type_biens'] ?? 0);

            // Validation minimale
            $errors = [];
            if ($nom === '') $errors[] = 'Le nom du bien est requis.';
            if ($rue === '') $errors[] = 'L\'adresse (rue) est requise.';
            if ($superficie <= 0) $errors[] = 'La superficie doit être un entier positif.';
            if ($description === '') $errors[] = 'La description est requise.';
            if ($id_commune <= 0) $errors[] = 'La commune est requise.';
            if ($id_type_biens <= 0) $errors[] = 'Le type de bien est requis.';

            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE Biens SET nom_biens = :nom, rue_biens = :rue, superficie_biens = :superficie, description_biens = :description, animal_biens = :animal, nb_couchage = :nb_couchage, id_commune = :id_commune, id_type_biens = :id_type_biens WHERE id_biens = :id_biens');
                $stmt->execute([
                    ':nom' => $nom,
                    ':rue' => $rue,
                    ':superficie' => $superficie,
                    ':description' => $description,
                    ':animal' => $animal,
                    ':nb_couchage' => $nb_couchage,
                    ':id_commune' => $id_commune,
                    ':id_type_biens' => $id_type_biens,
                    ':id_biens' => $id_biens
                ]);

                // supprimer les photos demandées
                if (!empty($_POST['delete_photo_ids']) && is_array($_POST['delete_photo_ids'])) {
                    $toDelete = array_map('intval', $_POST['delete_photo_ids']);
                    $in = implode(',', array_fill(0, count($toDelete), '?'));
                    $stmtDel = $pdo->prepare('SELECT lien_photo FROM Photos WHERE id_photo IN (' . $in . ')');
                    $stmtDel->execute($toDelete);
                    $delLinks = $stmtDel->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($delLinks as $lien) {
                        $path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($lien, '/');
                        if (file_exists($path)) @unlink($path);
                    }
                    $stmtDel2 = $pdo->prepare('DELETE FROM Photos WHERE id_photo IN (' . $in . ')');
                    $stmtDel2->execute($toDelete);
                }

                $message = 'Annonce mise à jour avec succès.';
            } else {
                $message = implode('<br>', $errors);
            }

        // création d'une nouvelle annonce
        } elseif (isset($_POST['submit_annonce'])) {
            // Champs principaux
            $nom = trim($_POST['nom_bien'] ?? '');
            $rue = trim($_POST['rue_bien'] ?? '');
            $superficie = intval($_POST['superficie_bien'] ?? 0);
            $description = trim($_POST['description_bien'] ?? '');
            $animal = isset($_POST['animal_bien']) ? 1 : 0;
            $nb_couchage = intval($_POST['nb_couchage'] ?? 1);
            $id_commune = intval($_POST['id_commune'] ?? 0);
            $id_type_biens = intval($_POST['id_type_biens'] ?? 0);

            // Validation minimale
            $errors = [];
            if ($nom === '') $errors[] = 'Le nom du bien est requis.';
            if ($rue === '') $errors[] = 'L\'adresse (rue) est requise.';
            if ($superficie <= 0) $errors[] = 'La superficie doit être un entier positif.';
            if ($description === '') $errors[] = 'La description est requise.';
            if ($id_commune <= 0) $errors[] = 'La commune est requise.';
            if ($id_type_biens <= 0) $errors[] = 'Le type de bien est requis.';

            if (empty($errors)) {
                // insertion dans Biens
                $stmt = $pdo->prepare('INSERT INTO Biens (nom_biens, rue_biens, superficie_biens, description_biens, animal_biens, nb_couchage, id_commune, id_type_biens) VALUES (:nom, :rue, :superficie, :description, :animal, :nb_couchage, :id_commune, :id_type_biens)');
                $stmt->execute([
                    ':nom' => $nom,
                    ':rue' => $rue,
                    ':superficie' => $superficie,
                    ':description' => $description,
                    ':animal' => $animal,
                    ':nb_couchage' => $nb_couchage,
                    ':id_commune' => $id_commune,
                    ':id_type_biens' => $id_type_biens
                ]);

                $message = 'Annonce ajoutée avec succès.';
            } else {
                $message = implode('<br>', $errors);
            }
        }
    }

    // Récupérer communes et types de biens pour les selects
    $communes = [];
    $stmt = $pdo->query('SELECT id_commune, nom_commune, cp_commune FROM Commune ORDER BY nom_commune LIMIT 100');
    $communes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $types = [];
    $stmt = $pdo->query('SELECT id_type_biens, designation_type_bien FROM Type_Bien');
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer annonces existantes
    $annonces = [];
    $stmt = $pdo->query('SELECT b.*, c.nom_commune, t.designation_type_bien FROM Biens b LEFT JOIN Commune c ON b.id_commune = c.id_commune LEFT JOIN Type_Bien t ON b.id_type_biens = t.id_type_biens ORDER BY b.id_biens DESC LIMIT 50');
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $message = 'Erreur : ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Créer une annonce</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; background:#f7f7f9; margin:0; }
        .container { max-width:900px; margin:40px auto; background:#fff; border-radius:18px; box-shadow:0 2px 16px rgba(80,0,80,0.06); padding:30px; }
        h2{ text-align:center }
        form { display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
        label{ display:block; font-weight:600; margin-bottom:6px }
        input, textarea, select { padding:8px; border-radius:6px; border:1px solid #ccc; width:100%; box-sizing:border-box }
        textarea { min-height:120px }
        .full { grid-column: 1 / -1 }
        .actions { text-align:center }
        .btn { background:#a100b8; color:#fff; padding:10px 18px; border:none; border-radius:8px; cursor:pointer }
        table { width:100%; border-collapse:collapse; margin-top:18px }
        th,td{ border:1px solid #eee; padding:8px; text-align:left }
        .message{ text-align:center; color:green; margin-bottom:12px }
        .error{ color:#b00020 }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php">&larr; Retour</a>
        <h2>Créer une annonce</h2>
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?php if (!empty($editing_data)): ?>
                <input type="hidden" name="id_biens" value="<?= htmlspecialchars($editing_data['id_biens']) ?>">
            <?php endif; ?>
            <div>
                <label for="nom_bien">Titre / Nom du bien</label>
                <input type="text" id="nom_bien" name="nom_bien" required value="<?= htmlspecialchars($editing_data['nom_biens'] ?? '') ?>">
            </div>
            <div>
                <label for="id_type_biens">Type de bien</label>
                <select id="id_type_biens" name="id_type_biens" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t['id_type_biens']) ?>" <?= (isset($editing_data) && $editing_data['id_type_biens'] == $t['id_type_biens']) ? 'selected' : '' ?>><?= htmlspecialchars($t['designation_type_bien']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="rue_bien">Rue / Adresse</label>
                <input type="text" id="rue_bien" name="rue_bien" required value="<?= htmlspecialchars($editing_data['rue_biens'] ?? '') ?>">
            </div>
            <div>
                <label for="id_commune">Commune</label>
                <select id="id_commune" name="id_commune" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($communes as $c): ?>
                        <option value="<?= htmlspecialchars($c['id_commune']) ?>" <?= (isset($editing_data) && $editing_data['id_commune'] == $c['id_commune']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nom_commune']) ?> (<?= htmlspecialchars($c['cp_commune']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="superficie_bien">Superficie (m²)</label>
                <input type="number" id="superficie_bien" name="superficie_bien" min="1" required value="<?= htmlspecialchars($editing_data['superficie_biens'] ?? '') ?>">
            </div>
            <div>
                <label for="nb_couchage">Nombre de couchages</label>
                <input type="number" id="nb_couchage" name="nb_couchage" min="1" value="<?= htmlspecialchars($editing_data['nb_couchage'] ?? 1) ?>" required>
            </div>
            <div class="full">
                <label for="description_bien">Description</label>
                <textarea id="description_bien" name="description_bien" required><?= htmlspecialchars($editing_data['description_biens'] ?? '') ?></textarea>
            </div>
            <div>
                <label for="animal_bien">Animaux acceptés</label>
                <input type="checkbox" id="animal_bien" name="animal_bien" value="1" <?= (!empty($editing_data) && $editing_data['animal_biens']) ? 'checked' : '' ?> >
            </div>
            <div class="full">
                <label for="photos">Photos (plusieurs possible)</label>
                <input type="file" id="photos" name="photos[]" accept="image/*" multiple>
                <?php if (!empty($editing_data)): ?>
                    <div style="margin-top:8px">
                        <?php
                        $stmtp = $pdo->prepare('SELECT id_photos, lien_photo FROM Photos WHERE id_biens = :id_biens');
                        $stmtp->execute([':id_biens' => $editing_data['id_biens']]);
                        $existing_photos = $stmtp->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($existing_photos as $p) {
                            echo '<div style="display:inline-block; text-align:center; margin-right:6px">';
                            echo '<img src="/' . htmlspecialchars($p['lien_photo']) . '" style="max-height:60px; display:block">';
                            echo '<label style="font-size:12px"><input type="checkbox" name="delete_photo_ids[]" value="' . (int)$p['id_photos'] . '"> Supprimer</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="full actions">
                <?php if (!empty($editing_data)): ?>
                    <button class="btn" type="submit" name="update_annonce">Mettre à jour</button>
                    <button class="btn" type="submit" name="delete_annonce" value="<?= htmlspecialchars($editing_data['id_biens']) ?>" style="margin-left:8px; background:#b00020" onclick="return confirm('Supprimer cette annonce ?');">Supprimer</button>
                    <a href="/Projet_HAP(House_After_Party)/forms/Annonce.form.php" style="margin-left:8px; color:#a100b8; font-weight:600; text-decoration:none">Annuler</a>
                <?php else: ?>
                    <button class="btn" type="submit" name="submit_annonce">Publier l'annonce</button>
                <?php endif; ?>
            </div>
        </form>

        <h3>Annonces récentes</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Type</th>
                <th>Commune</th>
                <th>Superficie</th>
                <th>Photos</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($annonces as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['id_biens']) ?></td>
                    <td><?= htmlspecialchars($a['nom_biens']) ?></td>
                    <td><?= htmlspecialchars($a['designation_type_bien'] ?? '') ?></td>
                    <td><?= htmlspecialchars($a['nom_commune'] ?? '') ?></td>
                    <td><?= htmlspecialchars($a['superficie_biens']) ?> m²</td>
                    <td>
                        <?php
                        $stmtp = $pdo->prepare('SELECT lien_photo FROM Photos WHERE id_biens = :id_biens');
                        $stmtp->execute([':id_biens' => $a['id_biens']]);
                        $photos = $stmtp->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($photos as $p) {
                            echo '<img src="/' . htmlspecialchars($p) . '" alt="photo" style="max-height:60px; margin-right:6px">';
                        }
                        ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($a['id_biens']) ?>">
                            <button type="submit">Modifier</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>

