<?php
require_once '../../includes/db.php';

header('Content-Type: application/json');

// Only Admin/Superadmin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_all':
        fetchUsers($pdo);
        break;
    case 'fetch_one':
        fetchUser($pdo);
        break;
    case 'create':
        createUser($pdo);
        break;
    case 'update':
        updateUser($pdo);
        break;
    case 'delete':
        deleteUser($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchUsers($pdo) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $whereSQL = "status != 2"; // Assuming 2 is deleted
    $params = [];

    if ($status !== '') {
        $whereSQL .= " AND status = ?";
        $params[] = $status;
    }

    if ($search) {
        $whereSQL .= " AND (full_name LIKE ? OR mobile LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $whereSQL");
    $totalStmt->execute($params);
    $total = $totalStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true, 
        'data' => $users, 
        'pagination' => [
            'total' => $total, 
            'page' => $page, 
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function fetchUser($pdo) {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if ($user) {
        $user['password'] = ''; // Don't send hash
        echo json_encode(['success' => true, 'data' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

function createUser($pdo) {
    $name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'] ?? null;
    $address = $_POST['address'];
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $type = $_POST['customer_type'] ?? 'Home';
    $status = $_POST['status'] ?? 1;
    // Default password
    $password = password_hash('123456', PASSWORD_BCRYPT); 

    // Check mobile
    $check = $pdo->prepare("SELECT id FROM users WHERE mobile = ? OR (email = ? AND email IS NOT NULL)");
    $check->execute([$mobile, $email]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Mobile or Email already exists']);
        return;
    }

    $sql = "INSERT INTO users (full_name, mobile, email, address, city, state, pincode, latitude, longitude, customer_type, status, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$name, $mobile, $email, $address, $city, $state, $pincode, $latitude, $longitude, $type, $status, $password]);
        echo json_encode(['success' => true, 'message' => 'User created successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateUser($pdo) {
    $id = $_POST['id'];
    $name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'] ?? null;
    $address = $_POST['address'];
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $type = $_POST['customer_type'];
    $status = $_POST['status'];

    // Check conflict
    $check = $pdo->prepare("SELECT id FROM users WHERE (mobile = ? OR email = ?) AND id != ?");
    $check->execute([$mobile, $email, $id]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Mobile or Email already exists']);
        return;
    }

    $sql = "UPDATE users SET full_name=?, mobile=?, email=?, address=?, city=?, state=?, pincode=?, latitude=?, longitude=?, customer_type=?, status=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$name, $mobile, $email, $address, $city, $state, $pincode, $latitude, $longitude, $type, $status, $id])) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user']);
    }
}

function deleteUser($pdo) {
    $id = $_POST['id'];
    // Soft delete: status = 0 or specific deleted status. Let's use 2 as deleted based on fetch logic, or just 0 as inactive? 
    // Request asked for Soft Delete. 
    // Status 0 usually means Inactive (can login? no). 
    // Let's assume Status 2 is "Deleted" hidden from list unless filtered.
    $stmt = $pdo->prepare("UPDATE users SET status = 2 WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
    }
}
?>
