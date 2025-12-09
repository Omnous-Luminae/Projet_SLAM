<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/connexion.php');
    exit;
}

$id_bien = intval($_GET['id_bien'] ?? 0);
$selectedYear = intval($_GET['year'] ?? date('Y'));
$userId = intval($_SESSION['user_id']);
$userRole = $_SESSION['user_role'] ?? 'user';
$isAdmin = $userRole === 'admin' || (isset($_SESSION['role']) && $_SESSION['role'] === 'animateur');
$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Verify ownership or admin access
        $bienStmt = $pdo->prepare('SELECT created_by_id, nom_biens FROM Biens WHERE id_biens = ?');
        $bienStmt->execute([$id_bien]);
        $bien = $bienStmt->fetch(PDO::FETCH_ASSOC);

        if (!$bien || (!$isAdmin && $bien['created_by_id'] != $userId)) {
            header('Location: Annonce.form.php');
            exit;
        }

        // Handle unavailable weeks update (use semaine_indisponible table when available)
        if (isset($_POST['update_unavailable_weeks'])) {
            $year = intval($_POST['year'] ?? date('Y'));
            $weeks = $_POST['unavailable_weeks'] ?? [];
            $weeksArray = array_values(array_filter(array_map('intval', $weeks), fn($w) => $w >= 1 && $w <= 53));

            // Check if semaine_indisponible table exists
            $tableExists = false;
            try {
                $check = $pdo->query("SHOW TABLES LIKE 'semaine_indisponible'")->fetch();
                $tableExists = (bool)$check;
            } catch (Exception $e) {
                $tableExists = false;
            }

            try {
                if ($tableExists) {
                    $pdo->beginTransaction();
                    $del = $pdo->prepare('DELETE FROM semaine_indisponible WHERE id_biens = ? AND annee = ?');
                    $del->execute([$id_bien, $year]);

                    if (!empty($weeksArray)) {
                        $ins = $pdo->prepare('INSERT INTO semaine_indisponible (id_biens, annee, semaine, created_by) VALUES (?, ?, ?, ?)');
                        foreach ($weeksArray as $w) {
                            $ins->execute([$id_bien, $year, $w, $userId]);
                        }
                    }
                    $pdo->commit();
                    $message = 'Semaines indisponibles mises à jour.';
                } else {
                    // Fallback to legacy JSON field on Biens
                    $weekJson = json_encode(array_values($weeksArray));
                    $stmt = $pdo->prepare('UPDATE Biens SET unavailable_weeks = ? WHERE id_biens = ?');
                    $stmt->execute([$weekJson, $id_bien]);
                    $message = 'Semaines indisponibles mises à jour (mode legacy).';
                }
            } catch (Exception $e) {
                if (!empty($pdo) && $pdo->inTransaction()) $pdo->rollBack();
                $message = 'Erreur lors de la mise à jour des semaines : ' . $e->getMessage();
            }
        }

        // Handle default tarifs
        if (isset($_POST['update_default_tarifs'])) {
            $saisons = $pdo->query('SELECT id_saison, lib_saison FROM Saison')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($saisons as $s) {
                $key = 'tarif_saison_' . $s['id_saison'];
                $tarif = floatval($_POST[$key] ?? 0);
                if ($tarif > 0) {
                    // Check if exists
                    $checkStmt = $pdo->prepare('SELECT id_tarif_defaut FROM Tarif_Defaut WHERE id_biens = ? AND id_saison = ?');
                    $checkStmt->execute([$id_bien, $s['id_saison']]);
                    $exists = $checkStmt->fetch();

                    if ($exists) {
                        $updateStmt = $pdo->prepare('UPDATE Tarif_Defaut SET tarif_defaut = ? WHERE id_biens = ? AND id_saison = ?');
                        $updateStmt->execute([$tarif, $id_bien, $s['id_saison']]);
                    } else {
                        $insertStmt = $pdo->prepare('INSERT INTO Tarif_Defaut (id_biens, id_saison, tarif_defaut) VALUES (?, ?, ?)');
                        $insertStmt->execute([$id_bien, $s['id_saison'], $tarif]);
                    }
                }
            }
            $message = 'Tarifs par défaut mis à jour.';
        }

        // Load current data
        $saisons = $pdo->query('SELECT id_saison, lib_saison FROM Saison')->fetchAll(PDO::FETCH_ASSOC);
        $defaultTarifs = $pdo->query("SELECT id_saison, tarif_defaut FROM Tarif_Defaut WHERE id_biens = $id_bien")->fetchAll(PDO::FETCH_ASSOC);
        $defaultTarifsMap = [];
        foreach ($defaultTarifs as $dt) {
            $defaultTarifsMap[$dt['id_saison']] = $dt['tarif_defaut'];
        }

        // Get unavailable weeks for selected year (prefer table, fallback to JSON)
        $unavailableWeeks = [];
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'semaine_indisponible'")->fetch();
            if ($check) {
                $uStmt = $pdo->prepare('SELECT semaine FROM semaine_indisponible WHERE id_biens = ? AND annee = ?');
                $uStmt->execute([$id_bien, $selectedYear]);
                $rows = $uStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) $unavailableWeeks[] = (int)$r['semaine'];
            } else {
                $bienStmt2 = $pdo->prepare('SELECT unavailable_weeks FROM Biens WHERE id_biens = ?');
                $bienStmt2->execute([$id_bien]);
                $bien2 = $bienStmt2->fetch(PDO::FETCH_ASSOC);
                $unavailableWeeks = $bien2['unavailable_weeks'] ? json_decode($bien2['unavailable_weeks'], true) : [];
            }
        } catch (Exception $e) {
            $unavailableWeeks = [];
        }
    }
} catch (Exception $e) {
    $message = 'Erreur : ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Gérer les tarifs et disponibilités</title>
    <link rel="stylesheet" href="../Css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .tarif-management { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(80,0,80,0.06); }
        .tarif-section { margin-bottom: 30px; }
        .tarif-section h3 { color: #a100b8; margin-bottom: 15px; }
        .tarif-grid { display: grid; gap: 15px; }
        .tarif-row { display: flex; gap: 10px; align-items: center; padding: 10px; background: #f9f9f9; border-radius: 6px; }
        .tarif-row label { flex: 1; font-weight: 500; }
        .tarif-row input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 120px; }
        .week-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(50px, 1fr)); gap: 8px; }
        .week-checkbox { display: flex; align-items: center; gap: 5px; }
        .week-checkbox input { width: 20px; height: 20px; cursor: pointer; }
        .btn { background: #a100b8; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn:hover { background: #4b006e; }
    </style>
</head>
<body>
    <div class="tarif-management">
        <a href="annonce_detail.php?id=<?= $id_bien ?>" class="back-link">&larr; Retour</a>
        <h2>Gérer les tarifs et disponibilités</h2>
        <p style="color:#666;">Bien: <strong><?= htmlspecialchars($bien['nom_biens']) ?></strong></p>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Default Tarifs Section -->
        <div class="tarif-section">
            <h3>Tarifs par défaut (par saison)</h3>
            <p style="color:#666;font-size:0.95em;">Ces tarifs s'appliquent aux semaines sans tarif spécifique.</p>
            <form method="post">
                <div class="tarif-grid">
                    <?php foreach ($saisons as $s): ?>
                        <div class="tarif-row">
                            <label for="tarif_<?= $s['id_saison'] ?>"><?= htmlspecialchars($s['lib_saison']) ?>:</label>
                            <input type="number" id="tarif_<?= $s['id_saison'] ?>" name="tarif_saison_<?= $s['id_saison'] ?>" step="0.01" value="<?= isset($defaultTarifsMap[$s['id_saison']]) ? number_format($defaultTarifsMap[$s['id_saison']], 2) : '' ?>" placeholder="€/nuit">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" name="update_default_tarifs" class="btn" style="margin-top: 15px;">Enregistrer tarifs par défaut</button>
            </form>
        </div>

        <!-- Unavailable Weeks Section -->
        <div class="tarif-section">
            <h3>Semaines indisponibles (non réservables)</h3>
            <p style="color:#666;font-size:0.95em;">Sélectionnez les semaines où le bien n'est pas disponible à la réservation.</p>
            <form method="post">
                <label for="year">Année :</label>
                <input type="number" name="year" id="year" value="<?= htmlspecialchars($selectedYear) ?>" min="1900" max="2100" style="margin-bottom:12px;padding:8px;border:1px solid #ddd;border-radius:6px;width:140px;" />
                <div class="week-grid">
                    <?php for ($w = 1; $w <= 53; $w++): ?>
                        <div class="week-checkbox">
                            <input type="checkbox" id="week_<?= $w ?>" name="unavailable_weeks[]" value="<?= $w ?>" <?= in_array($w, $unavailableWeeks) ? 'checked' : '' ?>>
                            <label for="week_<?= $w ?>" style="cursor:pointer;margin:0;">S<?= str_pad($w, 2, '0', STR_PAD_LEFT) ?></label>
                        </div>
                    <?php endfor; ?>
                </div>
                <button type="submit" name="update_unavailable_weeks" class="btn" style="margin-top: 15px;">Enregistrer semaines indisponibles</button>
            </form>
        </div>
    </div>
    <?php include '../../theme_toggle.php'; ?>
</body>
</html>
