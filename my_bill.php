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
    $month = $_POST['payment_month'] ?? ''; // New field
    
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
            $stmt = $pdo->prepare("INSERT INTO customer_payments (user_id, amount, payment_month, screenshot_url, status) VALUES (?, ?, ?, ?, 'Pending')");
            if ($stmt->execute([$user_id, $amount, $month, $targetFilePath])) {
                $message = "पेमेंट स्क्रीनशॉट यशस्वीरित्या अपलोड झाला. ॲडमिनच्या मंजुरीची प्रतीक्षा आहे.";
                $messageType = "success";
            } else {
                $message = "पेमेंट तपशील जतन करण्यात अक्षम.";
                $messageType = "danger";
            }
        } else {
            $message = "स्क्रीनशॉट अपलोड करताना त्रुटी आली.";
            $messageType = "danger";
        }
    } else {
        $message = "अवैध फाइल प्रकार. फक्त JPG, PNG, WEBP ला परवानगी आहे.";
        $messageType = "danger";
    }
}

// ... existing logic ...

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold text-dark mb-1">माझ्या बिलाचा सारांश</h2>
            <p class="text-muted">तुमचा वापर आणि पेमेंटचा मागोवा घ्या</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="fa-solid fa-upload me-2"></i> पेमेंट अपलोड करा
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
                        <h6 class="text-uppercase opacity-75 small fw-bold mb-0" style="color: white;">एकूण रक्कम</h6>
                        <i class="fa-solid fa-receipt fs-4"></i>
                    </div>
                    <h2 class="fw-bold mb-0" style="color: white;">₹<?php echo number_format($totalAmount, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-success text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase opacity-75 small fw-bold mb-0" style="color: white;">भरलेली रक्कम</h6>
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                    <h2 class="fw-bold mb-0" style="color: white;">₹<?php echo number_format($paidAmount, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-warning text-dark h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-uppercase opacity-75 small fw-bold mb-0 text-dark">प्रलंबित रक्कम</h6>
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
            <h5 class="fw-bold mb-0">महिनानिहाय बिल</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">महिना</th>
                            <th>एकूण रक्कम</th>
                            <th>स्थिती (Status)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlyBreakdown)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">कोणतेही बिल रेकॉर्ड सापडले नाहीत.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monthlyBreakdown as $row): ?>
                                <?php 
                                    $statusText = 'प्रलंबित';
                                    $statusClass = 'secondary';
                                    
                                    if ($row['payment_status'] === 'Approved') {
                                        $statusText = 'पेमेंट यशस्वी';
                                        $statusClass = 'success';
                                    } elseif ($row['payment_status'] === 'Pending') {
                                        $statusText = 'तपासणी सुरू';
                                        $statusClass = 'warning';
                                    } elseif ($row['payment_status'] === 'Rejected') {
                                        $statusText = 'पेमेंट अयशस्वी';
                                        $statusClass = 'danger';
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?php echo date('M Y', strtotime($row['month_key'])); ?></td>
                                    <td class="fw-bold text-primary">₹<?php echo number_format($row['monthly_total'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $statusClass; ?> rounded-pill px-3"><?php echo $statusText; ?></span>
                                    </td>
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
                <h5 class="modal-title fw-bold">पेमेंट सबमिट करा</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <p class="text-muted small mb-3">खालील QR कोड स्कॅन करा आणि तुमची प्रलंबित रक्कम भरा.</p>
                    <div class="bg-light p-3 rounded-4 d-inline-block border">
                        <?php if ($paymentQR): ?>
                            <img src="<?php echo htmlspecialchars($paymentQR); ?>" alt="Payment QR" style="max-height: 200px; width: auto;">
                        <?php else: ?>
                            <div class="text-muted py-4">ऍडमिनने अद्याप QR कोड अपलोड केलेला नाही.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <form action="my_bill.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase opacity-75">महिना निवडा</label>
                        <select name="payment_month" class="form-select" required>
                            <option value="">महिना निवडा...</option>
                            <?php foreach ($monthlyBreakdown as $row): ?>
                                <option value="<?php echo $row['month_key']; ?>"><?php echo date('M Y', strtotime($row['month_key'])); ?> (₹<?php echo number_format($row['monthly_total'], 2); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase opacity-75">पेमेंट रक्कम (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">₹</span>
                            <input type="number" step="0.01" name="amount" class="form-control border-start-0 ps-0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase opacity-75">स्क्रीनशॉट अपलोड करा</label>
                        <input type="file" name="screenshot" class="form-control" accept="image/*" required>
                        <div class="form-text small">कृपया तुमच्या व्यवहार पुष्टीकरणाची स्पष्ट प्रतिमा अपलोड करा.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                        पेमेंट तपशील सबमिट करा
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
