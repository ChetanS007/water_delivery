<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
}

switch($action) {
    case 'fetch_all':
        fetchBoys($pdo);
        break;
    case 'fetch_one':
        fetchOneBoy($pdo);
        break;
    case 'create':
        createBoy($pdo);
        break;
    case 'update':
        updateBoy($pdo);
        break;
    case 'delete':
        deleteBoy($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchBoys($pdo) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $whereSQL = "1=1"; 
    $params = [];

    if ($search) {
        $whereSQL .= " AND (full_name LIKE ? OR mobile LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM delivery_boys WHERE $whereSQL");
    $totalStmt->execute($params);
    $total = $totalStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM delivery_boys WHERE $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $boys = $stmt->fetchAll();

    echo json_encode([
        'success' => true, 
        'data' => $boys,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function fetchOneBoy($pdo) {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID missing']);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM delivery_boys WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $boy = $stmt->fetch();
    
    if ($boy) echo json_encode(['success' => true, 'data' => $boy]);
    else echo json_encode(['success' => false, 'message' => 'Not found']);
}

function createBoy($pdo) {
    $name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $status = $_POST['status'] ?? 1;

    // Check Duplicate
    $stmt = $pdo->prepare("SELECT id FROM delivery_boys WHERE mobile = ?");
    $stmt->execute([$mobile]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Mobile number already exists']);
        return;
    }

    $sql = "INSERT INTO delivery_boys (full_name, mobile, password, status) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$name, $mobile, $password, $status])) {
        echo json_encode(['success' => true, 'message' => 'Delivery Partner created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create partner']);
    }
}

function updateBoy($pdo) {
    $id = $_POST['id'];
    $name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $status = $_POST['status'];

    // Optional Password Update
    $passwordSQL = "";
    $params = [$name, $mobile, $status];

    if (!empty($_POST['password'])) {
        $passwordSQL = ", password = ?";
        $params[] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }
    
    $params[] = $id;

    $sql = "UPDATE delivery_boys SET full_name=?, mobile=?, status=? $passwordSQL WHERE id=?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
}

function deleteBoy($pdo) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM delivery_boys WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete']);
    }
}
?>
