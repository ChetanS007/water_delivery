<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $mobile = $_POST['mobile'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $order_details = $_POST['order_details'] ?? '';

    if (empty($name) || empty($mobile) || empty($event_date)) {
        echo json_encode(['success' => false, 'message' => 'सर्व फील्ड भरणे आवश्यक आहे.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO event_bookings (name, mobile, event_date, order_details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $mobile, $event_date, $order_details]);

        echo json_encode(['success' => true, 'message' => 'तुमची विनंती यशस्वीरित्या नोंदवली गेली आहे.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'काहीतरी चूक झाली: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
