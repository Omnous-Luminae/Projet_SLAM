<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Commune/Commune.php';

$message = '';
$editCommune = null;

try {
    $pdo = $pdo ?? null;
        if ($pdo) {
            $communeObj = new Commune(null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['delete_id'])) {
                $success = $communeObj->deleteCommune($_POST['delete_id']);
                $message = $success ? "Commune supprimée avec succès." : "Erreur lors de la suppression de la commune.";
            } else {
                $id_commune = $_POST['id_commune'] ?? null;
                $code_insee = $_POST['code_insee'] ?? '';
                $nom_commune = $_POST['nom_commune'] ?? '';
                $cp_commune = $_POST['cp_commune'] ?? '';
                $lat_commune = $_POST['lat_commune'] ?? '';
                $long_commune = $_POST['long_commune'] ?? '';
                $ville_slug = $_POST['ville_slug'] ?? '';
                $ville_nom_reel = $_POST['ville_nom_reel'] ?? '';
                $ville_nom_soundex = $_POST['ville_nom_soundex'] ?? '';
                $ville_nom_metaphone = $_POST['ville_nom_metaphone'] ?? '';
                $ville_departement = $_POST['ville_departement'] ?? '';
                $ville_arrondissement = $_POST['ville_arrondissement'] ?? '';
                $ville_canton = $_POST['ville_canton'] ?? '';
                $ville_code_commune = $_POST['ville_code_commune'] ?? '';
                $ville_commune = $_POST['ville_commune'] ?? '';
                $ville_surface = $_POST['ville_surface'] ?? '';
                $ville_zmin = $_POST['ville_zmin'] ?? '';
                $ville_zmax = $_POST['ville_zmax'] ?? '';

                if ($id_commune) {
                    $success = $communeObj->updateCommune($id_commune, $nom_commune, $cp_commune, $lat_commune, $long_commune, $ville_surface, $ville_zmin, $ville_zmax);
                    $message = $success ? "Commune mise à jour avec succès." : "Erreur lors de la mise à jour de la commune.";
                } else {
                    $success = $communeObj->createCommune($code_insee, $nom_commune, $cp_commune, $lat_commune, $long_commune, $ville_slug, $ville_nom_reel, $ville_nom_soundex, $ville_nom_metaphone, $ville_departement, $ville_arrondissement, $ville_canton, $ville_code_commune, $ville_commune, $ville_surface, $ville_zmin, $ville_zmax);
                    $message = $success ? "Commune créée avec succès." : "Erreur lors de la création de la commune.";
                }
            }
        }

        // Récupération des communes 
        $communes = $pdo->query('SELECT id_commune, code_insee, nom_commune, cp_commune FROM Commune ORDER BY nom_commune LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);


        if (isset($_GET['edit_id'])) {
            $editCommune = $communeObj->getCommuneById($_GET['edit_id']);
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<div class="form-section">
    <h3>Liste des Communes (premières 100)</h3>
        <?php if ($message): ?>
            <div class="success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <div class="commune-list">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Code INSEE</th>
                    <th>Nom</th>
                    <th>Code Postal</th>
                </tr>
                <?php foreach ($communes as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['id_commune']) ?></td>
                        <td><?= htmlspecialchars($c['code_insee']) ?></td>
                        <td><?= htmlspecialchars($c['nom_commune']) ?></td>
                        <td><?= htmlspecialchars($c['cp_commune']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <h2>Ajouter / Modifier une Commune</h2>
        <form method="post" action="">
            <input type="hidden" name="id_commune" value="<?= htmlspecialchars($editCommune['id_commune'] ?? '') ?>">

            <label>Code INSEE:</label><br>
            <input type="text" name="code_insee" value="<?= htmlspecialchars($editCommune['code_insee'] ?? '') ?>" required><br><br>

            <label>Nom Commune:</label><br>
            <input type="text" name="nom_commune" value="<?= htmlspecialchars($editCommune['nom_commune'] ?? '') ?>" required><br><br>

            <label>Code Postal:</label><br>
            <input type="text" name="cp_commune" value="<?= htmlspecialchars($editCommune['cp_commune'] ?? '') ?>" required><br><br>

            <label>Latitude:</label><br>
            <input type="text" name="lat_commune" value="<?= htmlspecialchars($editCommune['latitude_commune'] ?? '') ?>"><br><br>

            <label>Longitude:</label><br>
            <input type="text" name="long_commune" value="<?= htmlspecialchars($editCommune['longitude_commune'] ?? '') ?>"><br><br>

            <label>Ville Slug:</label><br>
            <input type="text" name="ville_slug" value="<?= htmlspecialchars($editCommune['ville_slug'] ?? '') ?>"><br><br>

            <label>Ville Nom Reel:</label><br>
            <input type="text" name="ville_nom_reel" value="<?= htmlspecialchars($editCommune['ville_nom_reel'] ?? '') ?>"><br><br>

            <label>Ville Nom Soundex:</label><br>
            <input type="text" name="ville_nom_soundex" value="<?= htmlspecialchars($editCommune['ville_nom_soundex'] ?? '') ?>"><br><br>

            <label>Ville Nom Metaphone:</label><br>
            <input type="text" name="ville_nom_metaphone" value="<?= htmlspecialchars($editCommune['ville_nom_metaphone'] ?? '') ?>"><br><br>

            <label>Ville Departement:</label><br>
            <input type="text" name="ville_departement" value="<?= htmlspecialchars($editCommune['ville_departement'] ?? '') ?>"><br><br>

            <label>Ville Arrondissement:</label><br>
            <input type="text" name="ville_arrondissement" value="<?= htmlspecialchars($editCommune['ville_arrondissement'] ?? '') ?>"><br><br>

            <label>Ville Canton:</label><br>
            <input type="text" name="ville_canton" value="<?= htmlspecialchars($editCommune['ville_canton'] ?? '') ?>"><br><br>

            <label>Ville Code Commune:</label><br>
            <input type="text" name="ville_code_commune" value="<?= htmlspecialchars($editCommune['ville_code_commune'] ?? '') ?>"><br><br>

            <label>Ville Commune:</label><br>
            <input type="text" name="ville_commune" value="<?= htmlspecialchars($editCommune['ville_commune'] ?? '') ?>"><br><br>

            <label>Ville Surface:</label><br>
            <input type="text" name="ville_surface" value="<?= htmlspecialchars($editCommune['ville_surface'] ?? '') ?>"><br><br>

            <label>Ville Zmin:</label><br>
            <input type="text" name="ville_zmin" value="<?= htmlspecialchars($editCommune['ville_zmin'] ?? '') ?>"><br><br>

            <label>Ville Zmax:</label><br>
            <input type="text" name="ville_zmax" value="<?= htmlspecialchars($editCommune['ville_zmax'] ?? '') ?>"><br><br>

            <button type="submit"><?= $editCommune ? 'Modifier' : 'Ajouter' ?> Commune</button>
        </form>
</div>
