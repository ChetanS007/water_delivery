<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // 1. Totals
    $total_sales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE status != 2")->fetchColumn();
    $total_delivery_boys = $pdo->query("SELECT COUNT(*) FROM delivery_boys")->fetchColumn();

    // 2. Sales Analytics (Daily Delivered Cans for Current Month)
    $current_month_days = date('t');
    $daily_data = array_fill(1, $current_month_days, 0);
    $stmt = $pdo->prepare("
        SELECT DAY(dd.delivery_date) as d, SUM(oi.quantity) as c 
        FROM daily_deliveries dd 
        JOIN order_items oi ON dd.subscription_id = oi.order_id 
        WHERE dd.status = 'Delivered' 
        AND MONTH(dd.delivery_date) = MONTH(CURDATE()) 
        AND YEAR(dd.delivery_date) = YEAR(CURDATE()) 
        GROUP BY d
    ");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $daily_data[$row['d']] = (int)$row['c'];
    }

    // 3. Recent Orders (Last 7 Days)
    $recent_counts_api = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $recent_counts_api[$date] = 0;
    }
    $stmt = $pdo->prepare("SELECT DATE(created_at) as d, COUNT(*) as c FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY d");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (isset($recent_counts_api[$row['d']])) {
            $recent_counts_api[$row['d']] = (int)$row['c'];
        }
    }

    // 4. Status Counts
    $delivered_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn();
    $cancelled_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn();

    // 5. Bill / Payment Stats
    $total_received = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE status = 'Approved'")->fetchColumn();
    $total_bill = $pdo->query("
        SELECT COALESCE(SUM(oi.quantity * p.price), 0)
        FROM daily_deliveries dd
        JOIN orders o ON dd.subscription_id = o.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE dd.status = 'Delivered'
    ")->fetchColumn();
    $pending_bill = max(0, $total_bill - $total_received);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_sales' => number_format($total_sales),
            'total_orders' => number_format($total_orders),
            'total_customers' => number_format($total_customers),
            'total_delivery_boys' => number_format($total_delivery_boys),
            'total_received' => number_format($total_received),
            'pending_bill' => number_format($pending_bill),
            'delivered_count' => $delivered_count,
            'cancelled_count' => $cancelled_count
        ],
        'charts' => [
            'sales_analytics' => array_values($daily_data),
            'recent_orders' => array_values($recent_counts_api)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
