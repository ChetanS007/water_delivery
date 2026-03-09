<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_all':
        fetchBillPayments($pdo);
        break;
    case 'approve':
        approvePayment($pdo);
        break;
    case 'mark_paid':
        markPaid($pdo);
        break;
    case 'fetch_history':
        fetchPaymentHistory($pdo);
        break;
    case 'get_months':
        fetchMonths($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchBillPayments($pdo) {
    $sql = "
        SELECT 
            cp.id,
            cp.user_id,
            u.full_name as user_name,
            cp.payment_month,
            cp.created_at,
            cp.status,
            cp.screenshot_url,
            cp.amount as submitted_amount,
            
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
                AND status IN ('Approved', 'Remaining', 'Online Paid', 'Cash Paid')
                AND (cp.payment_month IS NULL OR payment_month = cp.payment_month)
            ) as total_paid,

            /* Plan Amount (Fixed) - latest subscription plan amount */
            (
                SELECT total_amount 
                FROM orders 
                WHERE user_id = cp.user_id 
                AND status IN ('Approved', 'Assigned', 'Delivered', 'Completed')
                ORDER BY created_at DESC LIMIT 1
            ) as plan_amount,

            /* Calculate Total Bill based on Delivered Cans for the specific month */
            (
                SELECT COALESCE(SUM(oi2.quantity * p2.price), 0)
                FROM daily_deliveries dd2
                JOIN orders o2 ON dd2.subscription_id = o2.id
                JOIN order_items oi2 ON o2.id = oi2.order_id
                JOIN products p2 ON oi2.product_id = p2.id
                WHERE dd2.subscription_id = (
                    SELECT id FROM orders WHERE user_id = cp.user_id 
                    AND status IN ('Approved', 'Assigned', 'Delivered', 'Completed')
                    ORDER BY created_at DESC LIMIT 1
                ) AND dd2.status = 'Delivered'
                AND (cp.payment_month IS NULL OR DATE_FORMAT(dd2.delivery_date, '%Y-%m') = cp.payment_month)
            ) as calculated_bill,

            /* Count Delivered Trips for the specific month */
             (
                SELECT COUNT(*)
                FROM daily_deliveries dd3
                WHERE dd3.subscription_id = (
                    SELECT id FROM orders WHERE user_id = cp.user_id 
                    AND status IN ('Approved', 'Assigned', 'Delivered', 'Completed')
                    ORDER BY created_at DESC LIMIT 1
                ) AND dd3.status = 'Delivered'
                AND (cp.payment_month IS NULL OR DATE_FORMAT(dd3.delivery_date, '%Y-%m') = cp.payment_month)
            ) as delivered_count

        FROM customer_payments cp
        JOIN users u ON cp.user_id = u.id
    ";

    $month = $_GET['month'] ?? '';
    if ($month) {
        $sql .= " WHERE cp.payment_month = :month ";
    }
    
    $sql .= " ORDER BY cp.created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        if ($month) {
            $stmt->bindParam(':month', $month);
        }
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $records]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function approvePayment($pdo) {
    $paymentId = $_POST['payment_id'] ?? 0;
    $status = $_POST['status'] ?? 'Approved';
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

function markPaid($pdo) {
    $paymentId = $_POST['payment_id'] ?? 0;
    $status = $_POST['status'] ?? ''; 
    $amount = $_POST['amount'] ?? 0;
    
    if (!$paymentId || !$status || !$amount) {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
        return;
    }

    $stmt = $pdo->prepare("UPDATE customer_payments SET amount = amount + ?, status = ? WHERE id = ?");
    if ($stmt->execute([$amount, $status, $paymentId])) {
        // Record payment history
        $paymentType = ($status === 'Online Paid') ? 'Online' : (($status === 'Cash Paid') ? 'Cash' : 'User Paid');
        $stmtHistory = $pdo->prepare("INSERT INTO payment_history (payment_id, amount, payment_type) VALUES (?, ?, ?)");
        $stmtHistory->execute([$paymentId, $amount, $paymentType]);
        
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
    }
}

function fetchPaymentHistory($pdo) {
    if (!isset($_GET['payment_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing payment ID']);
        return;
    }
    
    $paymentId = intval($_GET['payment_id']);
    
    $stmt = $pdo->prepare("SELECT amount, created_at FROM customer_payments WHERE id = ?");
    $stmt->execute([$paymentId]);
    $cp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cp) {
        echo json_encode(['success' => false, 'message' => 'Not found']);
        return;
    }
    
    $sql = "SELECT amount, payment_type, DATE_FORMAT(created_at, '%d %b %Y') as formatted_date FROM payment_history WHERE payment_id = ? ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$paymentId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $historySum = 0;
    foreach ($records as $r) {
        $historySum += (float)$r['amount'];
    }
    
    $difference = (float)$cp['amount'] - $historySum;
    
    if ($difference > 0.001) { // Floating point safety
        $formattedDate = date('d M Y', strtotime($cp['created_at']));
        array_unshift($records, [
            'amount' => number_format($difference, 2, '.', ''),
            'payment_type' => 'User Paid',
            'formatted_date' => $formattedDate
        ]);
    }
    
    echo json_encode(['success' => true, 'data' => $records]);
}

function fetchMonths($pdo) {
    try {
        $stmt = $pdo->query("SELECT DISTINCT payment_month FROM customer_payments WHERE payment_month IS NOT NULL ORDER BY payment_month DESC");
        $months = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'data' => $months]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
