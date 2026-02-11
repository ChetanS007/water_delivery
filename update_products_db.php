<?php
require_once 'includes/db.php';

try {
    $col_check = $pdo->query("SHOW COLUMNS FROM products LIKE 'stock_quantity'");
    if ($col_check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0 AFTER price");
        echo "Column 'stock_quantity' added successfully.";
    } else {
        echo "Column 'stock_quantity' already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
