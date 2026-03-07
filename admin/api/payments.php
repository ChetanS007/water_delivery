<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Only Admin/Superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_all':
        fetchPayments($pdo);
        break;
    case 'approve':
        approvePayment($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchPayments($pdo) {
    $sql = "
        SELECT 
            cp.id,
            cp.user_id,
            u.full_name as user_name,
            cp.amount as submitted_amount,
            cp.payment_month,
            cp.screenshot_url,
            cp.status,
            cp.created_at,
            
            /* Total Bill for the specific month (or all if month null) */
            (
                SELECT COALESCE(SUM(oi2.quantity * p2.price), 0)
                FROM daily_deliveries dd2
                JOIN orders o2 ON dd2.subscription_id = o2.id
                JOIN order_items oi2 ON o2.id = oi2.order_id
                JOIN products p2 ON oi2.product_id = p2.id
                WHERE o2.user_id = cp.user_id 
                AND dd2.status = 'Delivered'
                AND (cp.payment_month IS NULL OR DATE_FORMAT(dd2.delivery_date, '%Y-%m') = cp.payment_month)
            ) as total_bill,

            /* Total Paid for the specific month (including Approved and Remaining) */
            (
                SELECT COALESCE(SUM(amount), 0)
                FROM customer_payments
                WHERE user_id = cp.user_id 
                AND status IN ('Approved', 'Remaining')
                AND (cp.payment_month IS NULL OR payment_month = cp.payment_month)
            ) as total_paid

        FROM customer_payments cp
        JOIN users u ON cp.user_id = u.id
        ORDER BY cp.created_at DESC
    ";

    try {
        $stmt = $pdo->query($sql);
        $payments = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $payments]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function approvePayment($pdo) {
    $paymentId = $_POST['payment_id'] ?? 0;
    $status = $_POST['status'] ?? 'Approved'; // Default to Approved
    if (!$paymentId) {
        echo json_encode(['success' => false, 'message' => 'Missing payment ID']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE customer_payments SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $paymentId])) {
        echo json_encode(['success' => true, 'message' => 'Payment processed as ' . $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve payment']);
    }
}
?>
