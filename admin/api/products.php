<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
}

switch($action) {
    case 'fetch_all':
        fetchProducts($pdo);
        break;
    case 'fetch_one':
        fetchOneProduct($pdo);
        break;
    case 'create':
        createProduct($pdo);
        break;
    case 'update':
        updateProduct($pdo);
        break;
    case 'delete':
        deleteProduct($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchProducts($pdo) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $whereSQL = "1=1"; 
    $params = [];

    if ($search) {
        $whereSQL .= " AND (product_name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE $whereSQL");
    $totalStmt->execute($params);
    $total = $totalStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM products WHERE $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true, 
        'data' => $products,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function fetchOneProduct($pdo) {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID missing']);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) echo json_encode(['success' => true, 'data' => $product]);
    else echo json_encode(['success' => false, 'message' => 'Not found']);
}

function createProduct($pdo) {
    $name = $_POST['product_name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock_quantity'] ?? 0;
    $status = $_POST['status'] ?? 1;

    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image_url = 'uploads/products/' . $filename;
        }
    }

    $sql = "INSERT INTO products (product_name, description, price, stock_quantity, image_url, status) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$name, $desc, $price, $stock, $image_url, $status]);
        echo json_encode(['success' => true, 'message' => 'Product created successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateProduct($pdo) {
    $id = $_POST['id'];
    $name = $_POST['product_name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock_quantity'];
    $status = $_POST['status'];

    $sql = "UPDATE products SET product_name=?, description=?, price=?, stock_quantity=?, status=?";
    $params = [$name, $desc, $price, $stock, $status];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image_url = 'uploads/products/' . $filename;
            $sql .= ", image_url=?";
            $params[] = $image_url;
        }
    }

    $sql .= " WHERE id=?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
}

function deleteProduct($pdo) {
    $id = $_POST['id'];
    
    // Optionally delete image file
    $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();
    if($prod && $prod['image_url']) {
        $filePath = '../../' . $prod['image_url'];
        if(file_exists($filePath)) unlink($filePath);
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete']);
    }
}
?>
