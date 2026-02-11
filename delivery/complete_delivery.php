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

        // Update Assignment
        $stmt = $pdo->prepare("UPDATE delivery_assignments SET delivery_status = 'Delivered', delivered_at = NOW() WHERE id = ?");
        $stmt->execute([$assignment_id]);

        // Update Order
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Delivered' WHERE id = ?");
        $stmt->execute([$order_id]);

        $pdo->commit();
        echo "<script>alert('Delivery Completed!'); window.location.href='dashboard.php';</script>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>
