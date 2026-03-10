<?php

// Docker-compatible values can be injected through environment variables.
$host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'Project_HAP';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

$dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Avoid MySQL unbuffered cursor conflicts when multiple queries run sequentially.
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

    // Ensure password column can store full password_hash() values.
    $maxLen = 0;
    $colStmt = $pdo->query("SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'locataire' AND COLUMN_NAME = 'password_locataire' LIMIT 1");
    if ($colStmt) {
        $maxLen = (int) $colStmt->fetchColumn();
    }
    if ($maxLen <= 0) {
        $showColStmt = $pdo->query("SHOW COLUMNS FROM Locataire LIKE 'password_locataire'");
        $showCol = $showColStmt ? $showColStmt->fetch(PDO::FETCH_ASSOC) : null;
        if (!empty($showCol['Type']) && preg_match('/varchar\((\d+)\)/i', $showCol['Type'], $m)) {
            $maxLen = (int) $m[1];
        }
    }
    if ($maxLen > 0 && $maxLen < 255) {
        $pdo->exec("ALTER TABLE Locataire MODIFY COLUMN password_locataire VARCHAR(255) NOT NULL");
    }
} catch (PDOException $e) {
    echo 'Connexion echouee: ' . $e->getMessage();
    exit();
}

?>