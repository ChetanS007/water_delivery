<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch($action) {
    case 'fetch_all':
        fetchSubscriptions($pdo);
        break;
    case 'approve':
        approveSubscription($pdo);
        break;
    case 'reject':
        rejectSubscription($pdo);
        break;
    case 'assign':
        assignDeliveryBoy($pdo);
        break;
    case 'fetch_delivery_boys':
        fetchActiveDeliveryBoys($pdo); 
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function fetchSubscriptions($pdo) {
    $page = $_GET['page'] ?? 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    $status = $_GET['status'] ?? '';

    $where = "1=1";
    $params = [];
    if($status) {
        $where .= " AND o.status = ?";
        $params[] = $status;
    }

    // Join with Users and Products
    $sql = "SELECT o.*, u.full_name as user_name, u.mobile, u.latitude, u.longitude, p.product_name, db.full_name as delivery_boy_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN delivery_assignments da ON o.id = da.order_id
            LEFT JOIN delivery_boys db ON da.delivery_boy_id = db.id
            WHERE $where
            ORDER BY o.created_at DESC LIMIT $limit OFFSET $offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count
    $countSql = "SELECT COUNT(*) FROM orders o WHERE $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function approveSubscription($pdo) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Approved' WHERE id = ?");
    if($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Subscription Request Approved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve']);
    }
}

function rejectSubscription($pdo) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Rejected' WHERE id = ?");
    if($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Subscription Request Rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject']);
    }
}

function assignDeliveryBoy($pdo) {
    $order_id = $_POST['order_id'];
    $boy_id = $_POST['delivery_boy_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Update Order Status
        $stmt = $pdo->prepare("UPDATE orders SET status = 'Assigned' WHERE id = ?");
        $stmt->execute([$order_id]);
        
        // Create/Update Assignment
        // Check if exists
        $check = $pdo->prepare("SELECT id FROM delivery_assignments WHERE order_id = ?");
        $check->execute([$order_id]);
        
        if($check->rowCount() > 0) {
            $assign = $pdo->prepare("UPDATE delivery_assignments SET delivery_boy_id = ?, assigned_at = NOW() WHERE order_id = ?");
            $assign->execute([$boy_id, $order_id]);
        } else {
            $assign = $pdo->prepare("INSERT INTO delivery_assignments (order_id, delivery_boy_id, delivery_status) VALUES (?, ?, 'Pending')");
            $assign->execute([$order_id, $boy_id]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Delivery Partner Assigned Successfully']);
    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Assignment Failed: ' . $e->getMessage()]);
    }
}

function fetchActiveDeliveryBoys($pdo) {
    $stmt = $pdo->query("SELECT id, full_name, current_lat, current_lng FROM delivery_boys WHERE status = 1");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
?>
