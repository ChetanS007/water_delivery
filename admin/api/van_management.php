<?php
// admin/api/van_management.php
ob_start(); // Start output buffering
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

// Disable error display to prevent HTML error output in JSON api
ini_set('display_errors', 0);
error_reporting(E_ALL);

$action = $_GET['action'] ?? '';

// Ensure table exists and has correct columns
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS van_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        van_id VARCHAR(50) NOT NULL,
        delivery_boy_id INT NULL,
        quantity INT DEFAULT 0,
        out_time DATETIME NULL,
        in_time DATETIME NULL,
        status ENUM('Pending', 'Out', 'In') DEFAULT 'Pending', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Attempt to update ENUM if it was created with old schema
    $pdo->exec("ALTER TABLE van_logs MODIFY COLUMN status ENUM('Pending', 'Out', 'In') DEFAULT 'Pending'");
} catch (Exception $e) {
    // Silent fail on table/column modification
}

// Clear any previous output (warnings, notices)
ob_clean();

// Fetch Van Logs
if ($action === 'fetch_logs') {
    try {
        $stmt = $pdo->prepare("
            SELECT vl.*, db.full_name as boy_name,
            (
                SELECT COALESCE(SUM(oi.quantity), 0) 
                FROM daily_deliveries dd
                JOIN orders o ON dd.subscription_id = o.id
                JOIN order_items oi ON o.id = oi.order_id
                WHERE dd.delivery_boy_id = vl.delivery_boy_id 
                AND dd.status = 'Delivered' 
                AND dd.delivered_at >= vl.created_at
                AND (vl.in_time IS NULL OR dd.delivered_at <= vl.in_time)
            ) as delivered_count
            FROM van_logs vl 
            LEFT JOIN delivery_boys db ON vl.delivery_boy_id = db.id 
            ORDER BY vl.created_at DESC
        ");
        $stmt->execute();
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle Add Van Entry (Initial 'Pending')
if ($action === 'add_van') {
    ob_clean();
    try {
        if (!isset($_POST['van_id'], $_POST['boy_id'], $_POST['quantity'])) {
            throw new Exception("Missing required fields");
        }

        $van_id = $_POST['van_id'];
        $boy_id = $_POST['boy_id'];
        $qty = $_POST['quantity'];
        
        $stmt = $pdo->prepare("INSERT INTO van_logs (van_id, delivery_boy_id, quantity, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$van_id, $boy_id, $qty]);
        
        echo json_encode(['success' => true, 'message' => 'Van Added Successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle 'Out' Action
if ($action === 'mark_out') {
    ob_clean();
    try {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE van_logs SET out_time = NOW(), status = 'Out' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Van Dispatched (Out)']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle 'In' Action
if ($action === 'mark_in') {
    ob_clean();
    try {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE van_logs SET in_time = NOW(), status = 'In' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Van Returned (In)']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}
?>
