<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$notifications = [];

try {
    // 1. Pending Subscriptions
    $stmt = $pdo->query("SELECT o.id, u.full_name, o.created_at FROM orders o JOIN users u ON o.user_id = u.id WHERE o.status = 'Pending' ORDER BY o.created_at DESC");
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($subs as $sub) {
        $notifications[] = [
            'id' => 'sub_' . $sub['id'],
            'type' => 'subscription',
            'title' => 'New Subscription Request',
            'message' => $sub['full_name'] . ' has requested a new subscription.',
            'timestamp' => $sub['created_at'],
            'url' => 'subscriptions.php'
        ];
    }

    // 2. Pending Payments
    $stmt = $pdo->query("SELECT cp.id, u.full_name, cp.created_at FROM customer_payments cp JOIN users u ON cp.user_id = u.id WHERE cp.status = 'Pending' ORDER BY cp.created_at DESC");
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($payments as $pay) {
        $notifications[] = [
            'id' => 'pay_' . $pay['id'],
            'type' => 'payment',
            'title' => 'New Bill Payment',
            'message' => $pay['full_name'] . ' uploaded a payment screenshot.',
            'timestamp' => $pay['created_at'],
            'url' => 'payments.php'
        ];
    }

    // Sort notifications by timestamp descending
    usort($notifications, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });

    echo json_encode(['success' => true, 'data' => $notifications, 'unread_count' => count($notifications)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
