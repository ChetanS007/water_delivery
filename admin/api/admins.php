<?php
require_once '../../includes/db.php';
header('Content-Type: application/json');

// Only Superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'fetch_all':
        fetchAdmins($pdo);
        break;
    case 'fetch_one':
        fetchAdmin($pdo);
        break;
    case 'create':
        createAdmin($pdo);
        break;
    case 'update':
        updateAdmin($pdo);
        break;
    case 'delete':
        deleteAdmin($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchAdmins($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE status = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $admins]);
}

function fetchAdmin($pdo) {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$id]);
    $admin = $stmt->fetch();
    if ($admin) {
        $admin['password'] = '';
        echo json_encode(['success' => true, 'data' => $admin]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
    }
}

function createAdmin($pdo) {
    $name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'] ?? null;
    $role = $_POST['role'];
    $address = $_POST['address'] ?? '';
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $check = $pdo->prepare("SELECT id FROM admins WHERE mobile = ? OR (email = ? AND email IS NOT NULL)");
    $check->execute([$mobile, $email]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Mobile or Email exists']);
        return;
    }

    $sql = "INSERT INTO admins (full_name, mobile, email, role, address, password, status) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$name, $mobile, $email, $role, $address, $password]);
        echo json_encode(['success' => true, 'message' => 'Admin created']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateAdmin($pdo) {
    $id = $_POST['id'];
    $name = $_POST['full_name'];
    $mobile = $_POST['mobile'];
    $email = $_POST['email'] ?? null;
    $role = $_POST['role'];
    $address = $_POST['address'] ?? '';

    $check = $pdo->prepare("SELECT id FROM admins WHERE (mobile = ? OR email = ?) AND id != ?");
    $check->execute([$mobile, $email, $id]);
    if ($check->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Mobile or Email exists']);
        return;
    }

    $sql = "UPDATE admins SET full_name=?, mobile=?, email=?, role=?, address=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$name, $mobile, $email, $role, $address, $id])) {
        echo json_encode(['success' => true, 'message' => 'Admin updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
}

function deleteAdmin($pdo) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("UPDATE admins SET status = 0 WHERE id = ?"); // Soft delete to 0
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Admin deleted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete']);
    }
}
?>
