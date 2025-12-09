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

        // Pagination
        $perPage = 50;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $perPage;

        // Récupération du nombre total de communes
        $totalCommunes = $pdo->query('SELECT COUNT(*) FROM Commune')->fetchColumn();
        $totalPages = ceil($totalCommunes / $perPage);

        // Récupération des communes avec pagination
        $communes = $pdo->query("SELECT id_commune, code_insee, nom_commune, cp_commune FROM Commune ORDER BY nom_commune LIMIT $perPage OFFSET $offset");
        $communes = $communes->fetchAll(PDO::FETCH_ASSOC);


        if (isset($_GET['edit_id'])) {
            $editCommune = $communeObj->getCommuneById($_GET['edit_id']);
        }
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Communes - House After Party</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="../Css/forms.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🏛️ Gestion des Communes</h2>
            <p>Gérez les communes disponibles dans le système</p>
        </div>

        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="data-section">
            <h3>Liste des Communes (Page <?= $page ?> sur <?= $totalPages ?>)</h3>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code INSEE</th>
                            <th>Nom</th>
                            <th>Code Postal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($communes as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['id_commune']) ?></td>
                                <td><?= htmlspecialchars($c['code_insee']) ?></td>
                                <td><?= htmlspecialchars($c['nom_commune']) ?></td>
                                <td><?= htmlspecialchars($c['cp_commune']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="btn btn-secondary">Précédent</a>
                <?php endif; ?>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>" class="btn btn-secondary <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="btn btn-secondary">Suivant</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="form-section">
            <h3><?= $editCommune ? 'Modifier' : 'Ajouter' ?> une Commune</h3>
            <form method="post" action="" class="form-grid">
                <input type="hidden" name="id_commune" value="<?= htmlspecialchars($editCommune['id_commune'] ?? '') ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="code_insee">Code INSEE</label>
                        <input type="text" id="code_insee" name="code_insee" value="<?= htmlspecialchars($editCommune['code_insee'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nom_commune">Nom Commune</label>
                        <input type="text" id="nom_commune" name="nom_commune" value="<?= htmlspecialchars($editCommune['nom_commune'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cp_commune">Code Postal</label>
                        <input type="text" id="cp_commune" name="cp_commune" value="<?= htmlspecialchars($editCommune['cp_commune'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="lat_commune">Latitude</label>
                        <input type="text" id="lat_commune" name="lat_commune" value="<?= htmlspecialchars($editCommune['latitude_commune'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="long_commune">Longitude</label>
                        <input type="text" id="long_commune" name="long_commune" value="<?= htmlspecialchars($editCommune['longitude_commune'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville_slug">Ville Slug</label>
                        <input type="text" id="ville_slug" name="ville_slug" value="<?= htmlspecialchars($editCommune['ville_slug'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_nom_reel">Ville Nom Réel</label>
                        <input type="text" id="ville_nom_reel" name="ville_nom_reel" value="<?= htmlspecialchars($editCommune['ville_nom_reel'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville_nom_soundex">Ville Nom Soundex</label>
                        <input type="text" id="ville_nom_soundex" name="ville_nom_soundex" value="<?= htmlspecialchars($editCommune['ville_nom_soundex'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_nom_metaphone">Ville Nom Metaphone</label>
                        <input type="text" id="ville_nom_metaphone" name="ville_nom_metaphone" value="<?= htmlspecialchars($editCommune['ville_nom_metaphone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville_departement">Ville Département</label>
                        <input type="text" id="ville_departement" name="ville_departement" value="<?= htmlspecialchars($editCommune['ville_departement'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_arrondissement">Ville Arrondissement</label>
                        <input type="text" id="ville_arrondissement" name="ville_arrondissement" value="<?= htmlspecialchars($editCommune['ville_arrondissement'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville_canton">Ville Canton</label>
                        <input type="text" id="ville_canton" name="ville_canton" value="<?= htmlspecialchars($editCommune['ville_canton'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_code_commune">Ville Code Commune</label>
                        <input type="text" id="ville_code_commune" name="ville_code_commune" value="<?= htmlspecialchars($editCommune['ville_code_commune'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville_commune">Ville Commune</label>
                        <input type="text" id="ville_commune" name="ville_commune" value="<?= htmlspecialchars($editCommune['ville_commune'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_surface">Ville Surface</label>
                        <input type="text" id="ville_surface" name="ville_surface" value="<?= htmlspecialchars($editCommune['ville_surface'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="ville_zmin">Ville Zmin</label>
                        <input type="text" id="ville_zmin" name="ville_zmin" value="<?= htmlspecialchars($editCommune['ville_zmin'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ville_zmax">Ville Zmax</label>
                        <input type="text" id="ville_zmax" name="ville_zmax" value="<?= htmlspecialchars($editCommune['ville_zmax'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $editCommune ? 'Modifier' : 'Ajouter' ?> la Commune</button>
                </div>
            </form>
        </section>
    </div>

    <script src="../js/confirm_delete.js"></script>
</body>
</html>
