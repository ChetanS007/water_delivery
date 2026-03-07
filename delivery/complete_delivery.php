<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_id = $_POST['assignment_id'] ?? 0;
    $can_received = $_POST['can_received'] ?? 0;

    if (!$assignment_id) {
        echo json_encode(['success' => false, 'message' => 'Missing assignment ID']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Get Order ID
        $stmt = $pdo->prepare("SELECT order_id FROM delivery_assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
            $pdo->rollBack();
            exit();
        }
        
        $order_id = $assignment['order_id'];
        $boy_id = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        // Check if already delivered today
        $check = $pdo->prepare("SELECT id FROM daily_deliveries WHERE subscription_id = ? AND delivery_date = ?");
        $check->execute([$order_id, $today]);
        if($check->rowCount() > 0) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Delivery already logged for today!', 'already_done' => true]);
            exit();
        }

        // Insert Record
        $stmt = $pdo->prepare("INSERT INTO daily_deliveries (subscription_id, delivery_boy_id, delivery_date, status, can_received, delivered_at) VALUES (?, ?, ?, 'Delivered', ?, NOW())");
        $stmt->execute([$order_id, $boy_id, $today, $can_received]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Delivery Completed for Today!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}
?>
