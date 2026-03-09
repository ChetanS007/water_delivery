<?php
require 'includes/db.php';
$stmt = $pdo->prepare('SELECT p.id, p.amount, (SELECT COALESCE(SUM(amount), 0) FROM payment_history WHERE payment_id = p.id) as history_sum FROM customer_payments p LIMIT 5');
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
