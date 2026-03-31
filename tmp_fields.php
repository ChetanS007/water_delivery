<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESC daily_deliveries");
    $fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $fields);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
