<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un tarif
        if (isset($_POST['add_tarif'])) {
            $semaine = intval($_POST['semaine_tarif'] ?? 0);
            $annee = intval($_POST['annee_tarif'] ?? 0);
            $tarif = floatval($_POST['tarif'] ?? 0);
            $id_saison = intval($_POST['id_saison'] ?? 0);
            $id_biens = intval($_POST['id_biens'] ?? 0);

            if ($semaine && $annee && $tarif && $id_saison && $id_biens) {
                $stmt = $pdo->prepare('INSERT INTO Tarif (semaine_Tarif, année_Tarif, tarif, id_saison, id_biens) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$semaine, $annee, $tarif, $id_saison, $id_biens]);
                $message = "Tarif ajouté avec succès.";
            } else {
                $message = "Tous les champs sont requis.";
            }
        }

        // Suppression d'un tarif
        if (isset($_POST['delete_tarif']) && isset($_POST['id_tarif'])) {
            $id = intval($_POST['id_tarif']);
            $stmt = $pdo->prepare('DELETE FROM Tarif WHERE id_Tarif = ?');
            $stmt->execute([$id]);
            $message = "Tarif supprimé avec succès.";
        }

        // Récupération des tarifs
        $tarifs = [];
        $stmt = $pdo->query('SELECT t.*, s.lib_saison, b.nom_biens FROM Tarif t LEFT JOIN Saison s ON t.id_saison = s.id_saison LEFT JOIN Biens b ON t.id_biens = b.id_biens ORDER BY t.id_Tarif DESC');
        $tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des saisons et biens pour les selects
        $saisons = $pdo->query('SELECT id_saison, lib_saison FROM Saison')->fetchAll(PDO::FETCH_ASSOC);
        $biens = $pdo->query('SELECT id_biens, nom_biens FROM Biens')->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Gestion des Tarifs - House After Party</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="../Css/forms.css">
    <style>
    body {
        transition: background 0.3s, color 0.3s;
    }
    .theme-toggle {
        position: absolute;
        top: 24px;
        right: 32px;
        background: var(--form-btn-primary-bg);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        font-size: 1.5em;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: background 0.3s;
    }
    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        position: relative;
    }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #fff;
        color: #a100b8;
        font-weight: 600;
        text-decoration: none;
        padding: 8px 18px;
        border-radius: 22px;
        box-shadow: 0 2px 8px rgba(161,0,184,0.07);
        border: 1.5px solid #a100b8;
        transition: background 0.2s, color 0.2s;
        font-size: 1.01em;
        margin-right: 10px;
    }
    .back-link:hover {
        background: #fbe9ff;
        color: #d100e8;
        border-color: #d100e8;
    }
    @media (max-width: 600px) {
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
        .back-link {
            margin-bottom: 6px;
            width: 100%;
            justify-content: center;
        }
    }
    .form-card {
        background: var(--form-bg);
        border-radius: 18px;
        box-shadow: 0 4px 24px rgba(161,0,184,0.08);
        padding: 32px 24px 24px 24px;
        max-width: 600px;
        margin: 0 auto 32px auto;
        position: relative;
    }
    .form-card h3 {
        margin-top: 0;
        margin-bottom: 18px;
        text-align: center;
    }
    .edit-row {
        background: #f3e6fa !important;
        box-shadow: 0 0 0 2px #a100b8 inset;
        transition: background 0.2s;
    }
    .edit-row input, .edit-row select {
        padding: 6px 8px;
        border-radius: 7px;
        border: 1.5px solid #a100b8;
        font-size: 0.97em;
        margin-right: 2px;
        outline: none;
    }
    .edit-row input:focus, .edit-row select:focus {
        border-color: #d100e8;
        background: #fff7fd;
    }
    .edit-row .btn {
        min-width: 36px;
        padding: 7px 8px;
        font-size: 1em;
    }
    .edit-row .btn-cancel {
        background: #eee;
        color: #a100b8;
        border: 1px solid #a100b8;
        margin-left: 4px;
        font-weight: 600;
        border-radius: 5px;
        transition: background 0.2s, color 0.2s;
    }
    .edit-row .btn-cancel:hover {
        background: #fbe9ff;
        color: #d100e8;
    }
    /* Tableau ultra user friendly */
    .tarif-table-section {
        background: #fff;
        padding: 18px 6px 12px 6px;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(161,0,184,0.06);
        margin-bottom: 24px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }
    .tarif-table-section h3 {
        margin: 0 0 18px 0;
        font-size: 1.08em;
        color: #a100b8;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .tarif-table-container {
        overflow-x: auto;
        border-radius: 10px;
        box-shadow: 0 1px 6px rgba(161,0,184,0.04);
        background: #fff;
    }
    .tarif-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        font-size: 0.97em;
        min-width: 420px;
        table-layout: auto;
    }
    .tarif-table th {
        background: linear-gradient(135deg, #a100b8, #d100e8);
        color: #fff;
        padding: 10px 4px;
        text-align: center;
        font-weight: 700;
        font-size: 0.93em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
    }
    .tarif-table td {
        padding: 8px 4px;
        text-align: center;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.93em;
        max-width: 120px;
        word-break: break-word;
    }
    .tarif-table tr:last-child td {
        border-bottom: none;
    }
    .tarif-table tbody tr {
        transition: background 0.18s;
    }
    .tarif-table tbody tr:hover {
        background: rgba(161,0,184,0.04);
    }
    .tarif-table .actions {
        display: flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
    }
    .tarif-table .btn {
        padding: 6px 10px;
        box-shadow: 0 1px 4px rgba(161,0,184,0.08);
    }
    .tarif-table .btn-secondary {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }
    .tarif-table .btn-danger {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }
    .tarif-table .btn:hover {
        transform: translateY(-1px) scale(1.04);
        box-shadow: 0 2px 8px rgba(161,0,184,0.13);
    }
    @media (max-width: 900px) {
        .tarif-table-section {
            padding: 10px 2px 8px 2px;
        }
        .tarif-table {
            min-width: 350px;
        }
    }
    @media (max-width: 700px) {
        .tarif-table th, .tarif-table td {
            font-size: 0.89em;
            padding: 7px 2px;
        }
        .tarif-table-section {
            padding: 4px 0 4px 0;
        }
    }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" title="Changer de thème">🌙</button>
    <div class="container">
        <div class="header">
            <a href="../../apropos.php" class="back-link" title="Retour au dashboard">
                <span style="font-size:1.3em;">🏠</span>
                <span>Dashboard</span>
            </a>
            <div>
                <h2 style="margin:0;">💰 Gestion des Tarifs</h2>
                <p style="margin:0;">Gérez les tarifs des biens par saison et semaine</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="form-section" id="form-section">
            <div class="form-card">
                <h3>Ajouter un nouveau tarif</h3>
                <form method="post" class="form-grid">
                    <div class="form-group">
                        <label for="semaine_tarif">Semaine</label>
                        <input type="number" id="semaine_tarif" name="semaine_tarif" placeholder="1-52" min="1" max="52" required>
                    </div>
                    <div class="form-group">
                        <label for="annee_tarif">Année</label>
                        <input type="number" id="annee_tarif" name="annee_tarif" placeholder="2024" min="2020" required>
                    </div>
                    <div class="form-group">
                        <label for="tarif">Tarif (€)</label>
                        <input type="number" id="tarif" name="tarif" step="0.01" placeholder="150.00" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="id_saison">Saison</label>
                        <select id="id_saison" name="id_saison" required>
                            <option value="">-- Sélectionner une saison --</option>
                            <?php foreach ($saisons as $saison): ?>
                                <option value="<?= $saison['id_saison'] ?>"><?= htmlspecialchars($saison['lib_saison']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_biens">Bien</label>
                        <select id="id_biens" name="id_biens" required>
                            <option value="">-- Sélectionner un bien --</option>
                            <?php foreach ($biens as $bien): ?>
                                <option value="<?= $bien['id_biens'] ?>"><?= htmlspecialchars($bien['nom_biens']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_tarif" class="btn btn-primary" style="font-size:1.1em;">➕ Ajouter le tarif</button>
                    </div>
                </form>
            </div>
        </section>

        <div class="tarif-table-section">
            <h3>📋 Liste des tarifs</h3>
            <form method="get" class="search-section" style="display:flex;gap:12px;margin-bottom:25px;flex-wrap:wrap;align-items:center;">
                <input type="text" id="searchTarif" name="search_tarif" placeholder="🔍 Rechercher par semaine, année, saison, bien..." style="flex:1;min-width:250px;padding:10px 16px;border:2px solid #e1e1e1;border-radius:8px;font-size:0.95em;">
            </form>
            <div class="tarif-table-container">
                <table class="tarif-table" id="tarifTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Semaine</th>
                            <th>Année</th>
                            <th>Tarif</th>
                            <th>Saison</th>
                            <th>Bien</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tarifs as $tarif): ?>
                            <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $tarif['id_Tarif']): ?>
                            <tr class="edit-row">
                                <form method="post">
                                    <td><?= htmlspecialchars($tarif['id_Tarif']) ?><input type="hidden" name="id_tarif" value="<?= htmlspecialchars($tarif['id_Tarif']) ?>"></td>
                                    <td><input type="number" name="semaine_tarif_edit" id="edit-semaine-<?= $tarif['id_Tarif'] ?>" value="<?= htmlspecialchars($tarif['semaine_Tarif']) ?>" min="1" max="52" required></td>
                                    <td><input type="number" name="annee_tarif_edit" value="<?= htmlspecialchars($tarif['année_Tarif']) ?>" min="2020" required></td>
                                    <td><input type="number" name="tarif_edit" value="<?= htmlspecialchars($tarif['tarif']) ?>" step="0.01" min="0" required></td>
                                    <td>
                                        <select name="id_saison_edit" required>
                                            <?php foreach ($saisons as $saison): ?>
                                                <option value="<?= $saison['id_saison'] ?>" <?= $tarif['id_saison'] == $saison['id_saison'] ? 'selected' : '' ?>><?= htmlspecialchars($saison['lib_saison']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="id_biens_edit" required>
                                            <?php foreach ($biens as $bien): ?>
                                                <option value="<?= $bien['id_biens'] ?>" <?= $tarif['id_biens'] == $bien['id_biens'] ? 'selected' : '' ?>><?= htmlspecialchars($bien['nom_biens']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td style="text-align:center;display:flex;gap:4px;justify-content:center;align-items:center;">
                                        <button type="submit" name="edit_tarif" class="btn btn-primary" title="Enregistrer">💾</button>
                                        <button type="button" class="btn btn-cancel" onclick="cancelEditRow(this)">Annuler</button>
                                    </td>
                                </form>
                            </tr>
                                // Focus automatique sur le premier champ lors de l'édition
                                document.addEventListener('DOMContentLoaded', function() {
                                    const editRow = document.querySelector('.edit-row input[type="number"]');
                                    if (editRow) {
                                        editRow.focus();
                                        editRow.select();
                                    }
                                });

                                // Annulation de l'édition (retour à l'affichage normal)
                                function cancelEditRow(btn) {
                                    // On simule un reload sans POST pour sortir du mode édition
                                    window.location.href = window.location.pathname;
                                }
                            <?php else: ?>
                            <tr>
                                <td><?= htmlspecialchars($tarif['id_Tarif']) ?></td>
                                <td><?= htmlspecialchars($tarif['semaine_Tarif']) ?></td>
                                <td><?= htmlspecialchars($tarif['année_Tarif']) ?></td>
                                <td><?= htmlspecialchars(number_format($tarif['tarif'], 2, ',', ' ')) ?> €</td>
                                <td><?= htmlspecialchars($tarif['lib_saison']) ?></td>
                                <td><?= htmlspecialchars($tarif['nom_biens']) ?></td>
                                <td class="actions" style="text-align:center;">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="edit_mode" value="<?= htmlspecialchars($tarif['id_Tarif']) ?>">
                                        <button type="submit" class="btn btn-secondary" title="Modifier"><span style="font-size:1.2em;">✏️</span></button>
                                    </form>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer ce tarif ?');">
                                        <input type="hidden" name="id_tarif" value="<?= htmlspecialchars($tarif['id_Tarif']) ?>">
                                        <button type="submit" name="delete_tarif" class="btn btn-danger" title="Supprimer"><span style="font-size:1.2em;">🗑️</span></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/confirm_delete.js"></script>
    <script>
    // Recherche côté client sur la table des tarifs
    document.getElementById('searchTarif').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#tarifTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    // Mode clair/sombre
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', function() {
        if (document.body.getAttribute('data-theme') === 'dark') {
            document.body.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            themeToggle.innerHTML = '🌙';
        } else {
            document.body.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            themeToggle.innerHTML = '☀️';
        }
    });
    // Initialisation du thème
    if (localStorage.getItem('theme') === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
        themeToggle.innerHTML = '☀️';
    }
    </script>
</body>
</html>
