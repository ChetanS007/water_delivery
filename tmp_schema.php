<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESC daily_deliveries");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('schema_output.txt', print_r($res, true));
