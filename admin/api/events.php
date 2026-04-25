<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'fetch_all') {
    $search = $_GET['search'] ?? '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    try {
        $where = "WHERE name LIKE :search OR mobile LIKE :search";
        $params = [':search' => "%$search%"];

        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM event_bookings $where");
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM event_bookings $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'pages' => ceil($total / $limit),
                'total' => $total
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'book_now') {
    $id = $_POST['id'] ?? null;
    $can_quantity = $_POST['can_quantity'] ?? 0;
    $location_name = $_POST['location_name'] ?? '';
    $delivery_date = $_POST['delivery_date'] ?? '';

    if (!$id || !$delivery_date) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE event_bookings SET can_quantity = ?, location_name = ?, delivery_date = ?, status = 'Accepted' WHERE id = ?");
        $stmt->execute([$can_quantity, $location_name, $delivery_date, $id]);
        echo json_encode(['success' => true, 'message' => 'Booking accepted and details saved.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'mark_delivered') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE event_bookings SET status = 'Delivered' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Event marked as delivered.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'fetch_one') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM event_bookings WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
