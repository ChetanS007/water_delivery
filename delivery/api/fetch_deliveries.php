<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$boy_id = $_SESSION['user_id'];

// Consolidate the Logic from dashboard.php
try {
    // Get ALL active assignments for this delivery boy
    $sql = "SELECT da.id as assignment_id, o.id as order_id, o.order_type, o.custom_days, o.created_at, 
                   u.full_name, u.address, u.latitude, u.longitude, u.qr_code, o.total_amount,
                   p.product_name, oi.quantity
            FROM delivery_assignments da
            JOIN orders o ON da.order_id = o.id
            JOIN users u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE da.delivery_boy_id = ? AND o.status IN ('Approved', 'Assigned')
            ORDER BY da.assigned_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$boy_id]);
    $all_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    date_default_timezone_set('Asia/Kolkata'); // Ensure correct timezone

    // --- 1. VAN STATISTICS (From Van Logs to match Admin) ---
    $stats = [
        'total_cans' => 0,
        'delivered_cans' => 0,
        'remaining_cans' => 0
    ];

    // Get Latest Active Van Log for this Delivery Boy
    $vanQuery = $pdo->prepare("SELECT id, quantity, out_time, status FROM van_logs WHERE delivery_boy_id = ? AND status IN ('Pending', 'Out') ORDER BY created_at DESC LIMIT 1");
    $vanQuery->execute([$boy_id]);
    $activeVan = $vanQuery->fetch(PDO::FETCH_ASSOC);

    if ($activeVan) {
        $stats['total_cans'] = intval($activeVan['quantity']);
        
        // Calculate Delivered Count based on Van Out Time
        // Logic must match Admin Panel (van_management.php)
        if ($activeVan['status'] === 'Out' && $activeVan['out_time']) {
            $delQuery = $pdo->prepare("
                SELECT COALESCE(SUM(oi.quantity), 0) 
                FROM daily_deliveries dd
                JOIN orders o ON dd.subscription_id = o.id
                JOIN order_items oi ON o.id = oi.order_id
                WHERE dd.delivery_boy_id = ? 
                AND dd.status = 'Delivered' 
                AND dd.delivered_at >= ?
            ");
            $delQuery->execute([$boy_id, $activeVan['out_time']]);
            $stats['delivered_cans'] = intval($delQuery->fetchColumn());
        }
        
        $stats['remaining_cans'] = $stats['total_cans'] - $stats['delivered_cans'];
    }

    // --- 2. ASSIGNED ORDERS LIST (For UI Display) ---
    $deliveries = [];
    $currentDate = new DateTime();
    $todayStr = $currentDate->format('Y-m-d');

    foreach ($all_assignments as $item) {
        $isDue = true; // FORCE SHOW ALL for now
        
        /* 
        // --- DUE DATE LOGIC (DISABLED) ---
        // ...
        */

        if ($isDue) {
            // Check if already delivered TODAY
            $check = $pdo->prepare("SELECT id, status FROM daily_deliveries WHERE subscription_id = ? AND delivery_date = ?");
            $check->execute([$item['order_id'], $todayStr]);
            $result = $check->fetch(PDO::FETCH_ASSOC);
            
            // Only add pending deliveries to the list
            if (!$result || $result['status'] !== 'Delivered') {
                $deliveries[] = $item;
            }
        }
    }

    echo json_encode(['success' => true, 'data' => $deliveries, 'stats' => $stats]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
