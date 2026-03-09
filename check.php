<?php
require 'includes/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM customer_payments");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
