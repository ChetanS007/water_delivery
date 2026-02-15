<?php
ob_start();
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

$action = $_GET['action'] ?? '';

if ($action === 'fetch_customers') {
    try {
        $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Customer' ORDER BY full_name");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'fetch_bills') {
    try {
        $customerId = $_GET['customer_id'] ?? '';
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $whereClauses = ["o.status IN ('Approved', 'Assigned', 'Delivered', 'Completed')"];
        $params = [];

        if (!empty($customerId)) {
            $whereClauses[] = "o.user_id = ?";
            $params[] = $customerId;
        }

        if (!empty($startDate) && !empty($endDate)) {
            $whereClauses[] = "DATE(o.created_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $whereSql = implode(' AND ', $whereClauses);

        // Fetch subscriptions (Active/Completed)
        $sql = "
            SELECT 
                o.id as sub_id,
                u.full_name as customer_name,
                o.created_at as start_date,
                o.total_amount as plan_amount,
                p.price as unit_price,
                'Pending' as payment_status, /* Placeholder until Payments module linked */
                
                /* Calculate Total Bill based on Delivered Cans */
                (
                    SELECT COALESCE(SUM(oi2.quantity * p2.price), 0)
                    FROM daily_deliveries dd2
                    JOIN orders o2 ON dd2.subscription_id = o2.id
                    JOIN order_items oi2 ON o2.id = oi2.order_id
                    JOIN products p2 ON oi2.product_id = p2.id
                    WHERE dd2.subscription_id = o.id AND dd2.status = 'Delivered'
                ) as calculated_bill,

                /* Count Delivered Trips */
                 (
                    SELECT COUNT(*)
                    FROM daily_deliveries dd3
                    WHERE dd3.subscription_id = o.id AND dd3.status = 'Delivered'
                ) as delivered_count

            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE $whereSql
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_clean();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

if ($action === 'fetch_details') {
    $id = $_GET['id'] ?? 0;
    try {
        $sql = "
            SELECT 
                dd.delivery_date,
                dd.status,
                dd.delivered_at,
                oi.quantity,
                p.product_name,
                p.price,
                (oi.quantity * p.price) as cost
            FROM daily_deliveries dd
            JOIN orders o ON dd.subscription_id = o.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ? AND dd.status = 'Delivered'
            ORDER BY dd.delivery_date DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_clean();
        echo json_encode(['success' => true, 'data' => $details]);
    } catch (Exception $e) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
?>
