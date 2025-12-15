<?php
require_once __DIR__ . '/../config/db.php';

$message = '';

try {
    $pdo = $pdo ?? null;
    if ($pdo) {
        // Ajout d'un type de point d'intérêt
        if (isset($_POST['add_type_pts_interet'])) {
            $lib = trim($_POST['lib_type_points_interet'] ?? '');
            if ($lib !== '') {
                // Vérifier si le type existe déjà
                $existing = $pdo->prepare('SELECT id_type_points_interet FROM Type_Pts_Interet WHERE lib_type_points_interet = ?');
                $existing->execute([$lib]);
                if ($existing->fetch()) {
                    $message = "Ce type de point d'intérêt existe déjà.";
                } else {
                    $stmt = $pdo->prepare('INSERT INTO Type_Pts_Interet (lib_type_points_interet) VALUES (?)');
                    $stmt->execute([$lib]);
                    $message = "Type de point d'intérêt ajouté avec succès.";
                }
            } else {
                $message = "Le nom du type ne peut pas être vide.";
            }
        }

        // Modification d'un type de point d'intérêt
        if (isset($_POST['edit_type_pts_interet']) && isset($_POST['id_type_points_interet']) && isset($_POST['lib_type_points_interet_edit'])) {
            $id = intval($_POST['id_type_points_interet']);
            $lib_edit = trim($_POST['lib_type_points_interet_edit']);
            if ($lib_edit !== '') {
                // Vérifier si le type existe déjà (sauf pour lui-même)
                $existing = $pdo->prepare('SELECT id_type_points_interet FROM Type_Pts_Interet WHERE lib_type_points_interet = ? AND id_type_points_interet != ?');
                $existing->execute([$lib_edit, $id]);
                if ($existing->fetch()) {
                    $message = "Ce type de point d'intérêt existe déjà.";
                } else {
                    $stmt = $pdo->prepare('UPDATE Type_Pts_Interet SET lib_type_points_interet = ? WHERE id_type_points_interet = ?');
                    $stmt->execute([$lib_edit, $id]);
                    $message = "Type de point d'intérêt modifié avec succès.";
                }
            } else {
                $message = "Le nom du type ne peut pas être vide.";
            }
        }

        // Suppression d'un type de point d'intérêt
        if (isset($_POST['delete_type_pts_interet']) && isset($_POST['id_type_points_interet'])) {
            $id = intval($_POST['id_type_points_interet']);
            $stmt = $pdo->prepare('DELETE FROM Type_Pts_Interet WHERE id_type_points_interet = ?');
            $stmt->execute([$id]);
            $message = "Type de point d'intérêt supprimé avec succès.";
        }

        // Récupération des types
        $types = $pdo->query('SELECT * FROM Type_Pts_Interet ORDER BY id_type_points_interet DESC')->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Gestion des Types de Points d'Intérêt</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Css/dashboard.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --hover-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            font-size: 16px;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        .page-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .page-header h2 {
            margin: 0 0 10px 0;
            color: #667eea;
            font-size: 32px;
            font-weight: 700;
        }

        .page-header p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }

        .success, .error {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .add-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .add-form h3 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            font-weight: 600;
        }

        .form-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .form-group input[type="text"] {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Montserrat', sans-serif;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .type-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            border-left: 5px solid #667eea;
        }

        .type-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .type-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .type-id {
            background: var(--primary-gradient);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .type-name {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin: 15px 0;
        }

        .type-card-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-edit, .btn-delete, .btn-save {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            flex: 1;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            flex: 1;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
        }

        .btn-save {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
            width: 100%;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 233, 123, 0.4);
        }

        .edit-input {
            width: 100%;
            padding: 12px 20px;
            border: 2px solid #667eea;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            margin-bottom: 15px;
        }

        .edit-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .empty-state {
            background: white;
            border-radius: 15px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: var(--card-shadow);
        }

        .empty-state p {
            font-size: 18px;
            color: #999;
            margin: 0;
        }

        @media (max-width: 768px) {
            .types-grid {
                grid-template-columns: 1fr;
            }

            .form-group {
                flex-direction: column;
            }

            .form-group input[type="text"] {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="/../index.php" class="back-link">← Retour à l'accueil</a>
        
        <div class="page-header">
            <h2>🏷️ Gestion des Types de Points d'Intérêt</h2>
            <p>Gérez les catégories de lieux à découvrir (bars, restaurants, monuments...)</p>
        </div>

        <?php if ($message): ?>
            <div class="<?= strpos($message, 'Erreur') !== false || strpos($message, 'existe déjà') !== false || strpos($message, 'vide') !== false ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="add-form">
            <h3>➕ Ajouter un nouveau type</h3>
            <form method="post">
                <div class="form-group">
                    <input type="text" id="lib_type_points_interet" name="lib_type_points_interet" placeholder="Nom du type (ex: Bar, Restaurant, Monument...)" required>
                    <button type="submit" name="add_type_pts_interet" class="btn-primary">Ajouter</button>
                </div>
            </form>
        </div>

        <?php if (!empty($types)): ?>
            <div class="types-grid">
                <?php foreach ($types as $t): ?>
                    <div class="type-card">
                        <div class="type-card-header">
                            <span class="type-id">#<?= htmlspecialchars($t['id_type_points_interet']) ?></span>
                        </div>
                        
                        <?php if (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $t['id_type_points_interet']): ?>
                            <form method="post">
                                <input type="hidden" name="id_type_points_interet" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                                <input type="text" name="lib_type_points_interet_edit" value="<?= htmlspecialchars($t['lib_type_points_interet']) ?>" class="edit-input" required autofocus>
                                <button type="submit" name="edit_type_pts_interet" class="btn-save">
                                    ✓ Enregistrer
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="type-name"><?= htmlspecialchars($t['lib_type_points_interet']) ?></div>
                            <div class="type-card-actions">
                                <form method="post" style="flex: 1;">
                                    <input type="hidden" name="id_type_points_interet" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                                    <button type="submit" name="edit_mode" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>" class="btn-edit">
                                        ✏️ Modifier
                                    </button>
                                </form>
                                <form method="post" style="flex: 1;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type ?');">
                                    <input type="hidden" name="id_type_points_interet" value="<?= htmlspecialchars($t['id_type_points_interet']) ?>">
                                    <button type="submit" name="delete_type_pts_interet" class="btn-delete">
                                        🗑️ Supprimer
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>Aucun type de point d'intérêt pour le moment. Commencez par en ajouter un !</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="../js/confirm_delete.js"></script>
</body>
</html>
