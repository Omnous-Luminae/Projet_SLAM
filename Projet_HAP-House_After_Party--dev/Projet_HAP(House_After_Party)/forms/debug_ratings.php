<?php
require_once __DIR__ . '/../config/db.php';

echo "<h2>🔍 Diagnostic des Ratings</h2>";

try {
    // 1. Vérifier si la table Reviews existe
    echo "<h3>1. Table Reviews</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'Reviews'")->fetchAll();
    if (count($tables) > 0) {
        echo "✅ La table Reviews existe<br>";
        
        // 2. Structure de la table
        echo "<h3>2. Structure de la table</h3>";
        $structure = $pdo->query("DESCRIBE Reviews")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($structure);
        echo "</pre>";
        
        // 3. Nombre total d'avis
        $total = $pdo->query("SELECT COUNT(*) as total FROM Reviews")->fetch();
        echo "<h3>3. Nombre total d'avis : " . $total['total'] . "</h3>";
        
        // 4. Nombre d'avis validés
        $validated = $pdo->query("SELECT COUNT(*) as total FROM Reviews WHERE validated = 1")->fetch();
        echo "<h3>4. Nombre d'avis validés : " . $validated['total'] . "</h3>";
        
        // 5. Exemples d'avis
        echo "<h3>5. Exemples d'avis (10 premiers)</h3>";
        $examples = $pdo->query("SELECT * FROM Reviews ORDER BY id_review DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>ID Bien</th><th>Rating</th><th>Commentaire</th><th>Validé</th><th>Date</th></tr>";
        foreach ($examples as $ex) {
            echo "<tr>";
            echo "<td>" . $ex['id_review'] . "</td>";
            echo "<td>" . $ex['id_biens'] . "</td>";
            echo "<td>" . $ex['rating'] . " ★</td>";
            echo "<td>" . htmlspecialchars(substr($ex['comment'] ?? '', 0, 50)) . "</td>";
            echo "<td>" . ($ex['validated'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($ex['created_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 6. Ratings moyens par bien
        echo "<h3>6. Ratings moyens par bien</h3>";
        $avgRatings = $pdo->query("
            SELECT b.id_biens, b.nom_biens, 
                   AVG(r.rating) as avg_rating, 
                   COUNT(*) as count_reviews
            FROM Biens b
            LEFT JOIN Reviews r ON b.id_biens = r.id_biens AND r.validated = 1
            GROUP BY b.id_biens, b.nom_biens
            HAVING count_reviews > 0
            ORDER BY avg_rating DESC
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID Bien</th><th>Nom</th><th>Moyenne</th><th>Nb Avis</th><th>Étoiles</th></tr>";
        foreach ($avgRatings as $ar) {
            $stars = str_repeat('★', round($ar['avg_rating'])) . str_repeat('☆', 5 - round($ar['avg_rating']));
            echo "<tr>";
            echo "<td>" . $ar['id_biens'] . "</td>";
            echo "<td>" . htmlspecialchars($ar['nom_biens']) . "</td>";
            echo "<td>" . number_format($ar['avg_rating'], 2) . "</td>";
            echo "<td>" . $ar['count_reviews'] . "</td>";
            echo "<td style='color:#f39c12;font-size:1.2em;'>" . $stars . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ La table Reviews n'existe PAS !<br>";
        echo "<p>Vous devez créer la table Reviews. Voici le SQL :</p>";
        echo "<pre>";
        echo "CREATE TABLE Reviews (
    id_review INT AUTO_INCREMENT PRIMARY KEY,
    id_biens INT NOT NULL,
    id_locataire INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    validated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_biens) REFERENCES Biens(id_biens) ON DELETE CASCADE,
    FOREIGN KEY (id_locataire) REFERENCES Locataire(id_locataire) ON DELETE SET NULL
);";
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>
