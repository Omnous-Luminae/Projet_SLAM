<?php
require_once __DIR__ . '/../config/db.php';

$sql = "SELECT id_biens, nom_biens FROM Biens";
$stmt = $pdo->query($sql);
$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Upload de photo</title>
</head>
<body>
    <h2>Uploader une photo</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label for="photo">Choisir une image :</label><br>
        <input type="file" name="photo" id="photo" accept="image/*" required><br><br>
        <label for="id_biens">Sélectionner le bien :</label><br>
        <select name="id_biens" id="id_biens" required>
            <option value="">-- Choisir un bien --</option>
            <?php foreach ($biens as $bien): ?>
                <option value="<?= $bien['id_biens'] ?>"><?= htmlspecialchars($bien['nom_biens']) ?></option>
            <?php endforeach; ?>
        </select><br><br>
        <input type="submit" value="Uploader">
    </form>
</body>
</html>
