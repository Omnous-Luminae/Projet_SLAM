<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'une prestation
        if (isset($_POST['add_prestation'])) {
            $lib = trim($_POST['lib_prestation'] ?? '');
            if ($lib !== '') {
                $stmt = $pdo->prepare('INSERT INTO Prestation (lib_prestation) VALUES (?)');
                $stmt->execute([$lib]);
                $message = "Prestation ajoutée avec succès.";
            }
        }

        // Suppression d'une prestation
        if (isset($_POST['delete_prestation']) && isset($_POST['id_prestation'])) {
            $id = intval($_POST['id_prestation']);
            $stmt = $pdo->prepare('DELETE FROM Prestation WHERE id_prestation = ?');
            $stmt->execute([$id]);
            $message = "Prestation supprimée avec succès.";
        }

        // Modification d'une prestation
        if (isset($_POST['edit_prestation']) && isset($_POST['id_prestation']) && isset($_POST['lib_prestation_edit'])) {
            $id = intval($_POST['id_prestation']);
            $lib = trim($_POST['lib_prestation_edit']);
            if ($lib !== '') {
                $stmt = $pdo->prepare('UPDATE Prestation SET lib_prestation = ? WHERE id_prestation = ?');
                $stmt->execute([$lib, $id]);
                $message = "Prestation modifiée avec succès.";
            }
        }

        // Récupération des prestations
        $prestations = $pdo->query('SELECT * FROM Prestation ORDER BY id_prestation DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Prestations</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/forms.css">
    <style>
        body {
            background: linear-gradient(120deg, #f3e7fa 0%, #e3f0ff 100%);
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 40px auto 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(161,0,184,0.10);
            padding: 36px 32px 32px 32px;
        }
        .header h2 {
            font-size: 2.1em;
            color: #a100b8;
            margin-bottom: 0.2em;
        }
        .header p {
            color: #6c6c6c;
            margin-bottom: 1.5em;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 18px;
            color: #a100b8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #d100e8;
        }
        .prestation-form {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 30px;
            justify-content: center;
            align-items: end;
            background: #faf6ff;
            border-radius: 12px;
            padding: 18px 20px 12px 20px;
            box-shadow: 0 2px 8px rgba(161,0,184,0.04);
        }
        .prestation-form input[type="text"] {
            min-width: 270px;
            border: 1.5px solid #d1b3e0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 1em;
            outline: none;
            transition: border 0.2s;
        }
        .prestation-form input[type="text"]:focus {
            border: 1.5px solid #a100b8;
            background: #f7eaff;
        }
        .prestation-form .btn-primary {
            background: linear-gradient(90deg, #a100b8 60%, #d100e8 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 22px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(161,0,184,0.08);
            transition: background 0.2s, transform 0.1s;
        }
        .prestation-form .btn-primary:hover {
            background: linear-gradient(90deg, #d100e8 60%, #a100b8 100%);
            transform: translateY(-2px) scale(1.04);
        }
        .form-section h3 {
            color: #a100b8;
            margin-bottom: 0.5em;
        }
        .form-section p {
            color: #7a7a7a;
        }
        .message.success {
            background: linear-gradient(90deg, #e0ffe8 60%, #f3fff7 100%);
            color: #1a7a3c;
            border-left: 5px solid #1a7a3c;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 18px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(26,122,60,0.04);
        }
        .message.error {
            background: linear-gradient(90deg, #ffe0e0 60%, #fff3f3 100%);
            color: #d1003c;
            border-left: 5px solid #d1003c;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 18px;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(209,0,60,0.04);
        }
        .prestation-list {
            margin-top: 40px;
            border-radius: 16px;
            overflow-x: auto;
            background: #f8f9fa;
            box-shadow: 0 4px 20px rgba(161,0,184,0.07);
            padding: 12px 0 0 0;
        }
        .prestation-list table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            min-width: 420px;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
        }
        .prestation-list th,
        .prestation-list td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        .prestation-list th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: linear-gradient(135deg, #a100b8, #d100e8);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 1em;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(161,0,184,0.04);
        }
        .prestation-list tr:nth-child(even) {
            background: #f6f2fa;
        }
        .prestation-list tr:nth-child(odd) {
            background: #fff;
        }
        .prestation-list tr {
            transition: background 0.3s, box-shadow 0.2s;
        }
        .prestation-list tr:hover {
            background: #e3f0ff;
            box-shadow: 0 2px 12px rgba(161,0,184,0.10);
        }
        .prestation-list tr.editing {
            background: #e3f0ff !important;
            box-shadow: 0 2px 16px rgba(0, 123, 255, 0.10);
        }
        .prestation-list .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .prestation-list .empty-row td {
            text-align: center;
            color: #a100b8;
            font-style: italic;
            background: #f8f9fa;
        }
        .prestation-list .count {
            font-size: 0.98em;
            color: #a100b8;
            font-weight: 600;
            margin-bottom: 8px;
            margin-left: 10px;
        }
        @media (max-width: 600px) {
            .prestation-list table, .prestation-list th, .prestation-list td {
                font-size: 0.93em;
                padding: 10px 8px;
            }
            .prestation-list {
                padding: 0;
            }
        }
        .btn-secondary {
            background: #f3e7fa;
            color: #a100b8;
            border: 1.5px solid #a100b8;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(161,0,184,0.08);
            transition: background 0.2s, color 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-secondary:hover {
            background: #a100b8;
            color: #fff;
            transform: translateY(-2px) scale(1.04);
        }
        .btn-danger {
            background: #ffeaea;
            color: #d1003c;
            border: 1.5px solid #d1003c;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(209,0,60,0.08);
            transition: background 0.2s, color 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-danger:hover {
            background: #d1003c;
            color: #fff;
            transform: translateY(-2px) scale(1.04);
        }
        @media (max-width: 600px) {
            .container {
                padding: 10px 2vw;
            }
            .prestation-form {
                flex-direction: column;
                align-items: stretch;
            }
            .prestation-list table, .prestation-list th, .prestation-list td {
                font-size: 0.95em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>⚽ Gestion des Prestations</h2>
            <p>Gérez les équipements sportifs et de loisirs disponibles pour les biens</p>
        </div>
        <a href="../../index.php" class="back-link">&larr; Retour à l'accueil</a>

        <?php if ($message): ?>
            <?php if (strpos($message, 'Erreur') === 0): ?>
                <div class="message error"><?= htmlspecialchars($message) ?></div>
            <?php else: ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <section class="form-section">
            <h3>Ajouter une nouvelle prestation</h3>
            <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                Exemples : Terrain de football, Piscine privée, Jacuzzi, Salle de sport, Baby-foot, etc.
            </p>
            <form method="post" class="prestation-form">
                <input type="text" id="lib_prestation" name="lib_prestation" placeholder="Ex: Terrain de tennis, Bar privé..." required>
                <button type="submit" name="add_prestation" class="btn btn-primary">Ajouter</button>
            </form>
        </section>

        <section class="data-section">
            <h3>Équipements et prestations existants</h3>
            <div class="prestation-list">
                <div class="count">
                    <?php $nbPrest = count($prestations); ?>
                    <?= $nbPrest === 0 ? 'Aucune prestation enregistrée.' : $nbPrest . ' prestation' . ($nbPrest > 1 ? 's' : '') . ' trouvée' . ($nbPrest > 1 ? 's' : '') ?>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Équipement / Prestation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prestations)): ?>
                            <tr class="empty-row"><td colspan="3">Aucune prestation à afficher.</td></tr>
                        <?php else: ?>
                            <?php foreach ($prestations as $p):
                                $isEditing = isset($_POST['edit_mode']) && $_POST['edit_mode'] == $p['id_prestation'];
                            ?>
                                <tr<?= $isEditing ? ' class="editing"' : '' ?>>
                                    <td><?= htmlspecialchars($p['id_prestation']) ?></td>
                                    <td>
                                        <?php if ($isEditing): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="id_prestation" value="<?= htmlspecialchars($p['id_prestation']) ?>">
                                                <input type="text" name="lib_prestation_edit" value="<?= htmlspecialchars($p['lib_prestation']) ?>" required style="background:#f7eaff; border:1.5px solid #a100b8; border-radius:8px; padding:8px 12px;">
                                                <button type="submit" name="edit_prestation" class="btn btn-primary">Enregistrer</button>
                                            </form>
                                        <?php else: ?>
                                            <?= htmlspecialchars($p['lib_prestation']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <?php if ($isEditing): ?>
                                            <!-- Rien, on est en mode édition -->
                                        <?php else: ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($p['id_prestation']) ?>">
                                                <button type="submit" class="btn btn-secondary">
                                                    <span style="font-size:1.1em;">✏️</span> Modifier
                                                </button>
                                            </form>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette prestation ?');">
                                                <input type="hidden" name="id_prestation" value="<?= htmlspecialchars($p['id_prestation']) ?>">
                                                <button type="submit" name="delete_prestation" class="btn btn-danger">
                                                    <span style="font-size:1.1em;">🗑️</span> Supprimer
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
<script src="../js/confirm_delete.js"></script>
