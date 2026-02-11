<?php
require_once 'includes/db.php';

try {
    // Modify orders table status enum to include 'Accepted'
    $pdo->exec("ALTER TABLE orders MODIFY COLUMN status ENUM('Pending','Accepted','Assigned','Delivered','Cancelled') DEFAULT 'Pending'");
    echo "Orders table status enum updated successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
