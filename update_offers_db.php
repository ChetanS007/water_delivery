<?php
require_once 'includes/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS offer_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        discount_type ENUM('Percentage', 'Fixed') NOT NULL,
        discount_value DECIMAL(10,2) NOT NULL,
        description TEXT,
        status TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Table 'offer_codes' created successfully.";

    // Add 'discount_amount' and 'offer_code_applied' columns to orders table if they don't exist
    $col_check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'discount_amount'");
    if ($col_check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER total_amount");
        $pdo->exec("ALTER TABLE orders ADD COLUMN offer_code_applied VARCHAR(50) NULL AFTER discount_amount");
        echo "Columns 'discount_amount' and 'offer_code_applied' added to orders table.";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
