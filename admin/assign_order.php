<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = $_POST['order_id'];
    $delivery_boy_id = $_POST['delivery_boy_id'];

    try {
        $pdo->beginTransaction();

        // 1. Create Assignment
        $stmt = $pdo->prepare("INSERT INTO delivery_assignments (order_id, delivery_boy_id, delivery_status) VALUES (?, ?, 'Pending')");
        $stmt->execute([$order_id, $delivery_boy_id]);

        // 2. Update Order Status
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Assigned' WHERE id = ?");
        $stmt->execute([$order_id]);

        $pdo->commit();
        header("Location: dashboard.php");
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
