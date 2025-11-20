<?php

$host = "localhost";
$db_name = "Project_HAP";
$username = "root";
$password = "";
$dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connexion échouée: " . $e->getMessage();
    exit();
}








?>