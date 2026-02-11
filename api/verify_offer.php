<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
$amount = (float)($_POST['amount'] ?? 0);

if (!$code) {
    echo json_encode(['success' => false, 'message' => 'Please enter a code']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM offer_codes WHERE code = ? AND status = 1");
$stmt->execute([$code]);
$offer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$offer) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired offer code']);
    exit;
}

$discount = 0;
if ($offer['discount_type'] === 'Percentage') {
    $discount = ($amount * $offer['discount_value']) / 100;
} else {
    $discount = $offer['discount_value'];
}

// Ensure discount doesn't exceed total amount
if ($discount > $amount) {
    $discount = $amount;
}

$final_amount = $amount - $discount;

echo json_encode([
    'success' => true,
    'message' => 'Offer applied successfully!',
    'discount_amount' => round($discount, 2),
    'final_amount' => round($final_amount, 2),
    'code' => $code
]);
?>
