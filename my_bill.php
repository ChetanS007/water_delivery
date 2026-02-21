<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle Payment Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['screenshot'])) {
    $amount = $_POST['amount'] ?? 0;
    
    $targetDir = "uploads/payments/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = "pay_" . $user_id . "_" . time() . "_" . basename($_FILES["screenshot"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $allowTypes = array('jpg', 'png', 'jpeg', 'webp');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $targetFilePath)) {
            $stmt = $pdo->prepare("INSERT INTO customer_payments (user_id, amount, screenshot_url, status) VALUES (?, ?, ?, 'Pending')");
            if ($stmt->execute([$user_id, $amount, $targetFilePath])) {
                $message = "Payment screenshot uploaded successfully. Awaiting admin approval.";
                $messageType = "success";
            } else {
                $message = "Failed to save payment details.";
                $messageType = "danger";
            }
        } else {
            $message = "Error uploading screenshot.";
            $messageType = "danger";
        }
    } else {
        $message = "Invalid file type. Only JPG, PNG, WEBP allowed.";
        $messageType = "danger";
    }
}

// 1. Calculate Total Amount (Deliveries)
$sqlTotal = "
    SELECT COALESCE(SUM(oi.quantity * p.price), 0) as total
    FROM daily_deliveries dd
    JOIN orders o ON dd.subscription_id = o.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND dd.status = 'Delivered'
";
$stmt = $pdo->prepare($sqlTotal);
$stmt->execute([$user_id]);
$totalAmount = $stmt->fetchColumn();

// 2. Calculate Paid Amount (Approved Payments)
$sqlPaid = "SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE user_id = ? AND status = 'Approved'";
$stmt = $pdo->prepare($sqlPaid);
$stmt->execute([$user_id]);
$paidAmount = $stmt->fetchColumn();

// 3. Pending Amount
$pendingAmount = $totalAmount - $paidAmount;

// 4. Month-wise breakdown
$sqlMonthly = "
    SELECT 
        DATE_FORMAT(dd.delivery_date, '%M %Y') as month_name,
        DATE_FORMAT(dd.delivery_date, '%Y-%m') as month_key,
        SUM(oi.quantity * p.price) as monthly_total
    FROM daily_deliveries dd
    JOIN orders o ON dd.subscription_id = o.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND dd.status = 'Delivered'
    GROUP BY month_key
    ORDER BY month_key DESC
";
$stmt = $pdo->prepare($sqlMonthly);
$stmt->execute([$user_id]);
$monthlyBreakdown = $stmt->fetchAll();

// Fetch Payment QR
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'payment_qr'");
$stmt->execute();
$paymentQR = $stmt->fetchColumn();

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-dark mb-1">My Billing Summary</h2>
            <p class="text-muted">Track your consumption and payments</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="fa-solid fa-upload me-2"></i> Upload Payment
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase opacity-75 small fw-bold mb-0">Total Amount</h6>
                        <i class="fa-solid fa-receipt fs-4"></i>
                    </div>
                    <h2 class="fw-bold mb-0">₹<?php echo number_format($totalAmount, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-success text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase opacity-75 small fw-bold mb-0">Paid Amount</h6>
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                    <h2 class="fw-bold mb-0">₹<?php echo number_format($paidAmount, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-warning text-dark h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase opacity-75 small fw-bold mb-0 text-dark">Pending Amount</h6>
                        <i class="fa-solid fa-clock fs-4 text-dark"></i>
                    </div>
                    <h2 class="fw-bold mb-0">₹<?php echo number_format($pendingAmount, 2); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0">Month-wise Billing</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Month</th>
                            <th class="text-end pe-4">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlyBreakdown)): ?>
                            <tr>
                                <td colspan="2" class="text-center py-5 text-muted">No billing records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monthlyBreakdown as $row): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?php echo $row['month_name']; ?></td>
                                    <td class="text-end pe-4 fw-bold text-primary">₹<?php echo number_format($row['monthly_total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Submit Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <p class="text-muted small mb-3">Scan the QR code below and pay your pending amount.</p>
                    <div class="bg-light p-3 rounded-4 d-inline-block border">
                        <?php if ($paymentQR): ?>
                            <img src="<?php echo htmlspecialchars($paymentQR); ?>" alt="Payment QR" style="max-height: 200px; width: auto;">
                        <?php else: ?>
                            <div class="text-muted py-4">Admin hasn't uploaded a QR Code yet.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <form action="my_bill.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase opacity-75">Payment Amount (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">₹</span>
                            <input type="number" step="0.01" name="amount" class="form-control border-start-0 ps-0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase opacity-75">Upload Screenshot</label>
                        <input type="file" name="screenshot" class="form-control" accept="image/*" required>
                        <div class="form-text small">Please upload a clear image of your transaction confirmation.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                        Submit Payment Details
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
