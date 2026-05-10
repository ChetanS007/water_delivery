<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) NULL DEFAULT NULL");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
