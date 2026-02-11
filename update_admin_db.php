<?php
require_once 'includes/db.php';

try {
    // 1. Add username column if not exists
    $col_check = $pdo->query("SHOW COLUMNS FROM admins LIKE 'username'");
    if ($col_check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE admins ADD COLUMN username VARCHAR(50) UNIQUE AFTER full_name");
        echo "Column 'username' added.<br>";
    }

    // 2. Clear existing admins to avoid duplicates during seed
    $pdo->exec("TRUNCATE TABLE admins");

    // 3. Seed Admins
    $password_admin = password_hash('admin@123', PASSWORD_BCRYPT);
    $password_super = password_hash('superadmin@123', PASSWORD_BCRYPT);

    $sql = "INSERT INTO admins (full_name, username, mobile, password, role, status) VALUES 
            ('System Admin', 'admin', '0000000001', ?, 'Admin', 1),
            ('Super Admin', 'superadmin', '0000000000', ?, 'Superadmin', 1)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$password_admin, $password_super]);

    echo "Admin credentials updated successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
