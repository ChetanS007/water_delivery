<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$boy_id = $_SESSION['user_id'];
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if ($lat && $lng) {
    try {
        $stmt = $pdo->prepare("UPDATE delivery_boys SET current_lat = ?, current_lng = ? WHERE id = ?");
        $stmt->execute([$lat, $lng, $boy_id]);
        echo json_encode(['success' => true, 'message' => 'Location updated']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
}
?>
