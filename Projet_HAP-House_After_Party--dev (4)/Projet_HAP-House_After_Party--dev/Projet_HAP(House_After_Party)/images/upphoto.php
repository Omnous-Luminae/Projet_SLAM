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
        <input type="submit" value="Uploader">
    </form>
</body>
</html>