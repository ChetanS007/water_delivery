<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch Active Subscription Detail
    $sub_sql = "
        SELECT o.*, oi.quantity, p.product_name, p.price, p.image_url, db.full_name as delivery_boy_name, db.mobile as delivery_boy_mobile
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN delivery_assignments da ON o.id = da.order_id
        LEFT JOIN delivery_boys db ON da.delivery_boy_id = db.id
        WHERE o.user_id = ? AND o.status IN ('Pending', 'Approved', 'Assigned')
        ORDER BY o.created_at DESC LIMIT 1
    ";
    $sub_stmt = $pdo->prepare($sub_sql);
    $sub_stmt->execute([$user_id]);
    $subscription = $sub_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'subscription' => $subscription
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
