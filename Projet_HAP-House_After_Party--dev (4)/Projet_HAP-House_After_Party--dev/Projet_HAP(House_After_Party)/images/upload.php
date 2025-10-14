<?php
require_once __DIR__ . '/../config/db.inc.php'; // adapte le chemin si besoin

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    
    $fileTmpPath = $_FILES['photo']['tmp_name'];
    $fileName = basename($_FILES['photo']['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExtension, $allowedExtensions)) {
        // Création du nom unique
        $newFileName = uniqid('img_', true) . '.' . $fileExtension;
        $uploadDir = 'uploads/';
        $destPath = $uploadDir . $newFileName;

        // Déplace le fichier
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Enregistre le lien dans la base
            $sql = "INSERT INTO photos (chemin) VALUES (:chemin)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['chemin' => $destPath]);

            echo "<p>✅ Fichier uploadé avec succès !</p>";
            echo "<p><img src='$destPath' width='200'></p>";
        } else {
            echo "<p>Erreur lors du déplacement du fichier.</p>";
        }
    } else {
        echo "<p>extension non autorisée. Formats acceptés : jpg, png, gif.</p>";
    }
} else {
    echo "<p>Aucune image sélectionnée ou erreur d’upload.</p>";
}
?>