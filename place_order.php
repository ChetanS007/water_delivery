<?php
require_once 'includes/db.php';

// Ensure this page is only accessible via POST from a logged-in User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: index.php");
    exit();
}

// Default response
$status = "error";
$message = "Invalid request.";
$redirect = "index.php";

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
            if ($discount_amount > $subtotal) $discount_amount = $subtotal;
            $offer_code_applied = $offer_code;
        }
    }

    // Check for existing pending orders
    $check_stmt = $pdo->prepare("SELECT id FROM orders WHERE user_id = ? AND status IN ('Pending', 'Approved', 'Assigned')");
    $check_stmt->execute([$user_id]);
    
    if ($check_stmt->rowCount() > 0) {
        $status = "info";
        $message = "तुमची आधीच एक विनंती प्रलंबित आहे. कृपया ती पूर्ण होईपर्यंत प्रतीक्षा करा.";
        $redirect = "profile.php";
    } else {
        $final_amount = $subtotal - $discount_amount;
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_type, custom_days, total_amount, discount_amount, offer_code_applied, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$user_id, $order_type, $custom_days, $final_amount, $discount_amount, $offer_code_applied]);
            $order_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $quantity, $price]);

            $pdo->commit();
            
            $_SESSION['is_subscribed'] = true; // User is now subscribed
            
            $status = "success";
            $message = "Your subscription request has been sent to the admin.";
            $redirect = "profile.php";
        } catch (Exception $e) {
            $pdo->rollBack();
            $status = "error";
            $message = "विनंती पाठवण्यात त्रुटी आली: " . $e->getMessage();
            $redirect = "index.php";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Order...</title>
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f8f9fa; margin: 0; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 2s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="loader"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $status; ?>',
                title: '<?php echo ($status === "success" ? "Success!" : ($status === "info" ? "सूचना" : "त्रुटी!")); ?>',
                text: '<?php echo addslashes($message); ?>',
                confirmButtonText: 'OK',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = '<?php echo $redirect; ?>';
            });
        });
    </script>
</body>
</html>
