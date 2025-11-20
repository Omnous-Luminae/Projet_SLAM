<?php
require_once __DIR__ . '/../config/db.php'; // adapte le chemin si besoin

header('Content-Type: application/json');

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && isset($_POST['id_biens'])) {

    $fileTmpPath = $_FILES['photo']['tmp_name'];
    $fileName = basename($_FILES['photo']['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExtension, $allowedExtensions)) {
        // Création du nom unique
        $newFileName = uniqid('img_', true) . '.' . $fileExtension;
        $uploadDir = 'uploads/';
        $destPath = $uploadDir . $newFileName;
        $lienPhoto = 'Projet_HAP(House_After_Party)/images/uploads/' . $newFileName;

        // Déplace le fichier
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Enregistre dans la base Photos
            $sql = "INSERT INTO Photos (nom_photos, lien_photo, id_biens) VALUES (:nom_photos, :lien_photo, :id_biens)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nom_photos' => $fileName,
                'lien_photo' => $lienPhoto,
                'id_biens' => $_POST['id_biens']
            ]);

            echo json_encode(['success' => true, 'message' => 'Fichier uploadé avec succès !', 'path' => $lienPhoto]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Extension non autorisée. Formats acceptés : jpg, png, gif.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aucune image sélectionnée, bien non choisi ou erreur d’upload.']);
}
?>
