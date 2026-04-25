<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$boy_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Consolidate the Logic from dashboard.php
try {
    // Get assignments/orders
    // If searching, we look at ALL approved orders across the system
    // If not searching, we only look at orders assigned to this boy
    $sql = "SELECT da.id as assignment_id, o.id as order_id, o.order_type, o.custom_days, o.created_at, 
                   u.full_name, u.address, u.latitude, u.longitude, u.qr_code, o.total_amount,
                   p.product_name, oi.quantity, da.delivery_boy_id as assigned_boy_id
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN delivery_assignments da ON o.id = da.order_id
            WHERE o.status IN ('Approved', 'Assigned')";

    if ($search) {
        $sql .= " AND (u.full_name LIKE :search1 OR u.mobile LIKE :search2)";
        $params = [':search1' => "%$search%", ':search2' => "%$search%"];
    } else {
        $sql .= " AND da.delivery_boy_id = :boy_id";
        $params = [':boy_id' => $boy_id];
    }

    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    date_default_timezone_set('Asia/Kolkata'); // Ensure correct timezone

    // --- 1. VAN STATISTICS (From Van Logs to match Admin) ---
    $stats = [
        'total_cans' => 0,
        'delivered_cans' => 0,
        'remaining_cans' => 0
    ];

    // Get Latest Active Van Log for this Delivery Boy
    $vanQuery = $pdo->prepare("SELECT id, quantity, out_time, status, created_at FROM van_logs WHERE delivery_boy_id = ? AND status IN ('Pending', 'Out') ORDER BY created_at DESC LIMIT 1");
    $vanQuery->execute([$boy_id]);
    $activeVan = $vanQuery->fetch(PDO::FETCH_ASSOC);

    if ($activeVan) {
        $stats['total_cans'] = intval($activeVan['quantity']);
        
        // Count all deliveries done AFTER this van log was initialized
        // This covers both 'Pending' and 'Out' states.
        $delQuery = $pdo->prepare("
            SELECT COALESCE(SUM(dd.quantity), 0) 
            FROM daily_deliveries dd
            WHERE dd.delivery_boy_id = ? 
            AND dd.status = 'Delivered' 
            AND dd.delivered_at >= ?
        ");
        $delQuery->execute([$boy_id, $activeVan['created_at']]); // Use created_at as baseline
        $stats['delivered_cans'] = intval($delQuery->fetchColumn());
        
        $stats['remaining_cans'] = $stats['total_cans'] - $stats['delivered_cans'];
    }

    // --- 2. ASSIGNED ORDERS LIST (For UI Display) ---
    $deliveries = [];
    $currentDate = new DateTime();
    $todayStr = $currentDate->format('Y-m-d');

    foreach ($all_assignments as $item) {
        $isDue = false;
        $currentDayName = date('D'); // Mon, Tue, etc.

        if ($item['order_type'] === 'Daily') {
            $isDue = true;
        } elseif ($item['order_type'] === 'Custom') {
            $days = json_decode($item['custom_days'], true);
            if (is_array($days) && in_array($currentDayName, $days)) {
                $isDue = true;
            }
        } elseif ($item['order_type'] === 'Alternate') {
            // Logic for Alternate Day: (Today - Created) % 2 == 0
            $start = new DateTime(date('Y-m-d', strtotime($item['created_at'])));
            $today = new DateTime($todayStr);
            $diff = $start->diff($today)->days;
            if ($diff % 2 === 0) {
                $isDue = true;
            }
        } else {
            // Default for other types for now
            $isDue = true; 
        }

        if ($isDue || $search !== '') {
            // Check if already delivered TODAY
            $check = $pdo->prepare("SELECT id, status FROM daily_deliveries WHERE subscription_id = ? AND delivery_date = ?");
            $check->execute([$item['order_id'], $todayStr]);
            $result = $check->fetch(PDO::FETCH_ASSOC);
            
            // If searching, show all matches but mark status
            // If not searching, only show pending deliveries
            if ($search !== '') {
                $item['today_status'] = $result ? $result['status'] : 'Pending';
                $deliveries[] = $item;
            } else {
                if (!$result || $result['status'] !== 'Delivered') {
                    $item['today_status'] = 'Pending';
                    $deliveries[] = $item;
                }
            }
        }
    }

    echo json_encode(['success' => true, 'data' => $deliveries, 'stats' => $stats]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
