<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
}

switch($action) {
    case 'fetch_orders':
        fetchOrders($pdo);
        break;
    case 'fetch_delivery_boys':
        fetchDeliveryBoys($pdo);
        break;
    case 'accept_order':
        acceptOrder($pdo);
        break;
    case 'assign_order':
        assignOrder($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchOrders($pdo) {
    $type = $_GET['type'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $where = "1=1";
    $params = [];

    if ($type === 'today') {
        $where .= " AND DATE(o.created_at) = CURDATE()";
    } elseif ($type === 'delivered') {
        $where .= " AND o.status = 'Delivered'";
        
        $start_date = $_GET['start_date'] ?? '';
        $end_date = $_GET['end_date'] ?? '';

        $deliveredSubquery = "(SELECT delivered_at FROM delivery_assignments da 
                               WHERE da.order_id = o.id AND da.delivery_status = 'Delivered' 
                               ORDER BY da.delivered_at DESC LIMIT 1)";

        if ($start_date) {
            $where .= " AND DATE($deliveredSubquery) >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $where .= " AND DATE($deliveredSubquery) <= ?";
            $params[] = $end_date;
        }
    }

    if ($search) {
        $where .= " AND (u.full_name LIKE ? OR u.mobile LIKE ? OR o.id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql = "SELECT o.*, u.full_name, u.mobile, u.address,
            (SELECT full_name FROM delivery_boys db 
             JOIN delivery_assignments da ON da.delivery_boy_id = db.id 
             WHERE da.order_id = o.id ORDER BY da.assigned_at DESC LIMIT 1) as delivery_boy_name,
            (SELECT delivered_at FROM delivery_assignments da 
             WHERE da.order_id = o.id AND da.delivery_status = 'Delivered' 
             ORDER BY da.delivered_at DESC LIMIT 1) as delivered_at
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE $where 
            ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $orders]);
}

function fetchDeliveryBoys($pdo) {
    $stmt = $pdo->query("SELECT id, full_name FROM delivery_boys WHERE status = 1");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function acceptOrder($pdo) {
    $order_id = $_POST['order_id'];
    
    // Authorization check could be added here
    
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Accepted' WHERE id = ? AND status = 'Pending'");
    if ($stmt->execute([$order_id])) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order accepted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Order not in pending status or already updated']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}

function assignOrder($pdo) {
    $order_id = $_POST['order_id'];
    $boy_id = $_POST['delivery_boy_id'];

    try {
        $pdo->beginTransaction();

        // 1. Update Order Status
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Assigned' WHERE id = ?");
        $stmt->execute([$order_id]);

        // 2. Create Assignment
        $stmt = $pdo->prepare("INSERT INTO delivery_assignments (order_id, delivery_boy_id, delivery_status) VALUES (?, ?, 'Pending')");
        $stmt->execute([$order_id, $boy_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Order assigned to delivery boy']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
