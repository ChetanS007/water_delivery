<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'];
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $order_type = $_POST['order_type'];
    $custom_days = isset($_POST['days']) ? json_encode($_POST['days']) : null;
    $offer_code = isset($_POST['offer_code']) ? strtoupper(trim($_POST['offer_code'])) : '';
    
    $subtotal = $price * $quantity;
    $discount_amount = 0;
    $offer_code_applied = null;

    // Server-side validation of offer code
    if ($offer_code) {
        $stmt = $pdo->prepare("SELECT * FROM offer_codes WHERE code = ? AND status = 1");
        $stmt->execute([$offer_code]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($offer) {
            if ($offer['discount_type'] === 'Percentage') {
                $discount_amount = ($subtotal * $offer['discount_value']) / 100;
            } else {
                $discount_amount = $offer['discount_value'];
            }
            // Ensure discount doesn't exceed total
            if ($discount_amount > $subtotal) {
                $discount_amount = $subtotal;
            }
            $offer_code_applied = $offer_code;
        }
    }

    // Check for existing active subscription (Pending, Approved, Assigned)
    $check_stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND status IN ('Pending', 'Approved', 'Assigned')");
    $check_stmt->execute([$user_id]);
    if ($check_stmt->rowCount() > 0) {
        echo "<script>alert('You already have an active subscription request. Please wait for it to be processed.'); window.location.href='profile.php';</script>";
        exit();
    }

    $final_amount = $subtotal - $discount_amount;

    try {
        $pdo->beginTransaction();

        // 1. Create Order with Discount Info (Status: Pending)
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, custom_days, total_amount, discount_amount, offer_code_applied, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$user_id, $order_type, $custom_days, $final_amount, $discount_amount, $offer_code_applied]);
        $order_id = $pdo->lastInsertId();

        // 2. Add Order Item
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $product_id, $quantity, $price]);

        $pdo->commit();
        echo "<script>alert('Subscription Request Sent Successfully! Awaiting Admin Approval.'); window.location.href='profile.php';</script>";

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Failed to send request: " . $e->getMessage() . "'); window.location.href='index.php';</script>";
    }
}
?>
