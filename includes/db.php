<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP password
$db   = 'water_delivery';

// $host = 'localhost';
// $user = 'u479035143_water';
// $pass = 'U479035143_water';  
// $db   = 'u479035143_water';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
