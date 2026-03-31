<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESC daily_deliveries");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
