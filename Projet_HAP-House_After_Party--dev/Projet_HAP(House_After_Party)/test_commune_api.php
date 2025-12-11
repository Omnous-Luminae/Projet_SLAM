<?php
// Test de l'API search_communes.php
require_once __DIR__ . '/config/db.php';

echo "<h2>Test de la table Commune</h2>";

try {
    // Test 1: Compter les communes
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM Commune');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Nombre total de communes:</strong> " . $result['count'] . "</p>";
    
    // Test 2: Vérifier si code_insee existe
    $stmt = $pdo->query("SHOW COLUMNS FROM Commune");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Colonnes de la table Commune:</strong> " . implode(', ', $columns) . "</p>";
    $hasCodeInsee = in_array('code_insee', $columns);
    echo "<p><strong>Colonne code_insee existe:</strong> " . ($hasCodeInsee ? 'OUI' : 'NON') . "</p>";
    
    // Test 3: Afficher quelques exemples
    echo "<h3>Exemples de communes:</h3><ul>";
    $stmt = $pdo->query('SELECT * FROM Commune LIMIT 10');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>";
        echo "ID: " . $row['id_commune'] . " - ";
        echo $row['nom_commune'] . " (" . $row['cp_commune'] . ")";
        if ($hasCodeInsee && isset($row['code_insee'])) {
            echo " - INSEE: " . $row['code_insee'];
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Test 4: Simuler une recherche
    echo "<h3>Test de recherche pour 'paris':</h3>";
    $stmt = $pdo->prepare("SELECT id_commune, nom_commune, cp_commune FROM Commune WHERE LOWER(nom_commune) LIKE LOWER(?) LIMIT 10");
    $stmt->execute(['%paris%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Résultats trouvés: " . count($results) . "</p>";
    if (count($results) > 0) {
        echo "<ul>";
        foreach ($results as $row) {
            echo "<li>" . $row['nom_commune'] . " (" . $row['cp_commune'] . ")</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Erreur:</strong> " . $e->getMessage() . "</p>";
}
?>
