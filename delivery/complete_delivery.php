<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_id = $_POST['assignment_id'];

    try {
        $pdo->beginTransaction();

        // Get Order ID
        $stmt = $pdo->prepare("SELECT order_id FROM delivery_assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch();
        $order_id = $assignment['order_id'];

        // Create Daily Delivery Log
        $boy_id = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        // Check if already delivered today
        $check = $pdo->prepare("SELECT id FROM daily_deliveries WHERE subscription_id = ? AND delivery_date = ?");
        $check->execute([$order_id, $today]);
        if($check->rowCount() > 0) {
            $pdo->commit();
            echo "<script>alert('Delivery already logged for today!'); window.location.href='dashboard.php';</script>";
            exit();
        }

        // Insert Record
        $stmt = $pdo->prepare("INSERT INTO daily_deliveries (subscription_id, delivery_boy_id, delivery_date, status, delivered_at) VALUES (?, ?, ?, 'Delivered', NOW())");
        $stmt->execute([$order_id, $boy_id, $today]);

        $pdo->commit();
        echo "<script>alert('Delivery Completed for Today!'); window.location.href='dashboard.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
