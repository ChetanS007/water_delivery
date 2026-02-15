<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ''; 
}

switch($action) {
    case 'fetch_todays_deliveries':
        fetchTodaysDeliveries($pdo);
        break;
    case 'fetch_history':
        fetchHistory($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action ' . $action]);
}

function fetchTodaysDeliveries($pdo) {
    try {
        // 1. Fetch all active subscriptions (Approved or Assigned)
        // Join with products and users
        $sql = "SELECT o.id as sub_id, o.user_id, o.order_type, o.custom_days, o.created_at,
                       u.full_name as customer_name, u.mobile, u.address,
                       da.delivery_boy_id, db.full_name as delivery_boy_name
                FROM orders o
                JOIN users u ON u.id = o.user_id
                LEFT JOIN delivery_assignments da ON o.id = da.order_id
                LEFT JOIN delivery_boys db ON da.delivery_boy_id = db.id
                WHERE o.status IN ('Approved', 'Assigned')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $todaysDeliveries = [];
        $currentDate = new DateTime();
        $todayStr = $currentDate->format('Y-m-d');
        $todayDay = $currentDate->format('D'); // Mon, Tue, etc.

        foreach($subscriptions as $sub) {
            $isDue = false;
            
            // Logic for Due Date
            if ($sub['order_type'] === 'Daily') {
                $isDue = true;
            } elseif ($sub['order_type'] === 'Alternate') {
                // Calculate days since start
                $start = new DateTime($sub['created_at']);
                $diff = $start->diff($currentDate)->days;
                // Basic Alternate Logic: Every 2 days
                if ($diff % 2 == 0) {
                    $isDue = true;
                }
            } elseif ($sub['order_type'] === 'Custom') {
                $days = json_decode($sub['custom_days'] ?? '[]', true);
                if (is_array($days) && in_array($todayDay, $days)) {
                    $isDue = true;
                }
            } elseif ($sub['order_type'] === 'Weekly') {
                $start = new DateTime($sub['created_at']);
                if ($start->format('D') === $todayDay) {
                    $isDue = true;
                }
            }

            if ($isDue) {
                // Check if already logged in daily_deliveries
                $check = $pdo->prepare("SELECT status, delivered_at FROM daily_deliveries WHERE subscription_id = ? AND delivery_date = ?");
                $check->execute([$sub['sub_id'], $todayStr]);
                $result = $check->fetch(PDO::FETCH_ASSOC); 

                $sub['today_status'] = $result ? $result['status'] : 'Pending';
                $sub['delivered_at'] = $result ? $result['delivered_at'] : null;
                $todaysDeliveries[] = $sub;
            }
        }

        echo json_encode(['success' => true, 'data' => $todaysDeliveries]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function fetchHistory($pdo) {
    try {
        $sql = "SELECT dd.*, u.full_name as customer_name, db.full_name as delivery_boy_name
                FROM daily_deliveries dd
                JOIN orders o ON dd.subscription_id = o.id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN delivery_boys db ON dd.delivery_boy_id = db.id
                ORDER BY dd.delivery_date DESC, dd.created_at DESC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
