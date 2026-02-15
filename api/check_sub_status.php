<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get Status of Active Subscription Request
$stmt = $pdo->prepare("SELECT status FROM orders WHERE user_id = ? AND status IN ('Pending', 'Approved', 'Assigned', 'Rejected') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order) {
    // If assigned, also get delivery boy name for UX
    $boyName = null;
    if ($order['status'] === 'Assigned') {
        $stmt2 = $pdo->prepare("SELECT db.full_name FROM delivery_assignments da 
                                JOIN delivery_boys db ON da.delivery_boy_id = db.id 
                                WHERE da.order_id = (SELECT id FROM orders WHERE user_id = ? AND status='Assigned' LIMIT 1) 
                                ORDER BY da.assigned_at DESC LIMIT 1");
        $stmt2->execute([$user_id]);
        $boyName = $stmt2->fetchColumn();
    }
    
    echo json_encode(['success' => true, 'status' => $order['status'], 'delivery_boy' => $boyName]);
} else {
    echo json_encode(['success' => true, 'status' => 'None']);
}
?>
