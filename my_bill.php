<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// AJAX: Fetch Bill Status (Summary & Table)
if (isset($_GET['action']) && $_GET['action'] === 'fetch_bill_status') {
    header('Content-Type: application/json');
    try {
        // Total Amount
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(oi.quantity * p.price), 0)
            FROM daily_deliveries dd
            JOIN orders o ON dd.subscription_id = o.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ? AND dd.status = 'Delivered'
        ");
        $stmt->execute([$user_id]);
        $totalAmt = $stmt->fetchColumn();

        // Paid Amount (Approved or Remaining)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE user_id = ? AND status IN ('Approved', 'Remaining')");
        $stmt->execute([$user_id]);
        $paidAmt = $stmt->fetchColumn();

        // Monthly Breakdown
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(dd.delivery_date, '%Y-%m') as month_key,
                SUM(oi.quantity * p.price) as monthly_total,
                (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE user_id = ? AND payment_month = DATE_FORMAT(dd.delivery_date, '%Y-%m') AND status IN ('Approved', 'Remaining')) as monthly_paid,
                (SELECT status FROM customer_payments WHERE user_id = ? AND payment_month = DATE_FORMAT(dd.delivery_date, '%Y-%m') ORDER BY id DESC LIMIT 1) as last_payment_status
            FROM daily_deliveries dd
            JOIN orders o ON dd.subscription_id = o.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ? AND dd.status = 'Delivered'
            GROUP BY month_key
            ORDER BY month_key DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'summary' => [
                'total' => $totalAmt,
                'paid' => $paidAmt,
                'pending' => $totalAmt - $paidAmt
            ],
            'breakdown' => $breakdown
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// AJAX: Fetch Month Details
if (isset($_GET['action']) && $_GET['action'] === 'fetch_month_details') {
    header('Content-Type: application/json');
    $month = $_GET['month'] ?? '';
    try {
        $stmt = $pdo->prepare("
            SELECT 
                dd.delivery_date,
                dd.status,
                dd.can_received
            FROM daily_deliveries dd
            JOIN orders o ON dd.subscription_id = o.id
            WHERE o.user_id = ? AND DATE_FORMAT(dd.delivery_date, '%Y-%m') = ?
            ORDER BY dd.delivery_date ASC
        ");
        $stmt->execute([$user_id, $month]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $details]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Handle Payment Upload (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['screenshot'])) {
    header('Content-Type: application/json');
    $amount = $_POST['amount'] ?? 0;
    $month = $_POST['payment_month'] ?? '';
    
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
                echo json_encode(['success' => true, 'message' => 'Sent to Admin.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'पेमेंट तपशील जतन करण्यात अक्षम.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'स्क्रीनशॉट अपलोड करताना त्रुटी आली.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'अवैध फाइल प्रकार. फक्त JPG, PNG, WEBP ला परवानगी आहे.']);
    }
    exit();
}

// Fetch Payment QR
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'payment_qr'");
$stmt->execute();
$paymentQR = $stmt->fetchColumn() ?: '';

// Calculate Total Amount (from delivered daily deliveries)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(oi.quantity * p.price), 0)
    FROM daily_deliveries dd
    JOIN orders o ON dd.subscription_id = o.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND dd.status = 'Delivered'
");
$stmt->execute([$user_id]);
$totalAmount = $stmt->fetchColumn();

// Calculate Paid Amount (Approved or Remaining)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE user_id = ? AND status IN ('Approved', 'Remaining')");
$stmt->execute([$user_id]);
$paidAmount = $stmt->fetchColumn();

$pendingAmount = $totalAmount - $paidAmount;

// Monthly Breakdown with aggregated payment status
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(dd.delivery_date, '%Y-%m') as month_key,
        SUM(oi.quantity * p.price) as monthly_total,
        (SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE user_id = ? AND payment_month = DATE_FORMAT(dd.delivery_date, '%Y-%m') AND status IN ('Approved', 'Remaining')) as monthly_paid,
        (SELECT status FROM customer_payments WHERE user_id = ? AND payment_month = DATE_FORMAT(dd.delivery_date, '%Y-%m') ORDER BY id DESC LIMIT 1) as last_payment_status
    FROM daily_deliveries dd
    JOIN orders o ON dd.subscription_id = o.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND dd.status = 'Delivered'
    GROUP BY month_key
    ORDER BY month_key DESC
");
$stmt->execute([$user_id, $user_id, $user_id]);
$monthlyBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <i class="fa-solid fa-wallet me-2"></i> Check Payment Upload
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
                    <h2 class="fw-bold mb-0" id="statTotal" style="color: white;">₹<?php echo number_format($totalAmount, 2); ?></h2>
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
                    <h2 class="fw-bold mb-0" id="statPaid" style="color: white;">₹<?php echo number_format($paidAmount, 2); ?></h2>
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
                    <h2 class="fw-bold mb-0" id="statPending">₹<?php echo number_format($pendingAmount, 2); ?></h2>
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
                            <th>एकूण रक्कम (Total Bill)</th>
                            <th>बाकी बिल (Pending Bill)</th>
                            <th>स्थिती (Status)</th>
                        </tr>
                    </thead>
                    <tbody id="monthlyBillBody">
                        <?php if (empty($monthlyBreakdown)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">कोणतेही बिल रेकॉर्ड सापडले नाहीत.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($monthlyBreakdown as $row): ?>
                                <?php 
                                    $monthlyPaid = (float)($row['monthly_paid'] ?? 0);
                                    $monthlyTotal = (float)$row['monthly_total'];
                                    $pendingBill = max(0, $monthlyTotal - $monthlyPaid);
                                    
                                    $statusText = 'प्रलंबित (Pending)';
                                    $statusClass = 'danger';
                                    
                                    if ($pendingBill <= 0) {
                                        $statusText = 'यशस्वी (Paid)';
                                        $statusClass = 'success';
                                    } elseif ($row['last_payment_status'] === 'Pending') {
                                        $statusText = 'तपासणी सुरू (Checking)';
                                        $statusClass = 'warning';
                                    } elseif ($row['last_payment_status'] === 'Remaining') {
                                        $statusText = 'प्रलंबित (Pending)';
                                        $statusClass = 'danger';
                                    }
                                ?>
                                <tr>
                                    <td class="ps-4 fw-medium">
                                        <a href="javascript:void(0)" class="text-decoration-none view-month-details" data-month="<?php echo $row['month_key']; ?>" data-month-name="<?php echo date('F Y', strtotime($row['month_key'])); ?>">
                                            <?php echo date('M Y', strtotime($row['month_key'])); ?>
                                        </a>
                                    </td>
                                    <td class="fw-bold text-primary">₹<?php echo number_format($row['monthly_total'], 2); ?></td>
                                    <td class="fw-bold text-danger">₹<?php echo number_format($pendingBill, 2); ?></td>
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
                <div class="alert alert-warning border-0 rounded-4 mb-4 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation fs-3 me-3 text-warning"></i>
                        <div>
                            <div class="small fw-bold text-uppercase opacity-75">एकूण प्रलंबित रक्कम (Total Pending)</div>
                            <h4 class="fw-bold mb-0 text-dark" id="modalPendingAmount">₹<?php echo number_format($pendingAmount, 2); ?></h4>
                        </div>
                    </div>
                </div>

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

                <form id="paymentUploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase opacity-75">महिना निवडा (Select Month)</label>
                        <select name="payment_month" class="form-select" required>
                            <option value="">प्रलंबित महिना निवडा...</option>
                            <tbody id="modalMonthSelect">
                            <?php foreach ($monthlyBreakdown as $row): ?>
                                <?php if ($row['monthly_total'] > (float)$row['monthly_paid']): ?>
                                    <option value="<?php echo $row['month_key']; ?>" data-due="<?php echo ($row['monthly_total'] - $row['monthly_paid']); ?>">
                                        <?php echo date('M Y', strtotime($row['month_key'])); ?> (₹<?php echo number_format($row['monthly_total'] - $row['monthly_paid'], 2); ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </select>
                        <div class="form-text small">केवळ प्रलंबित महिने येथे दिसतील.</div>
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
                        <input type="file" name="screenshot" id="screenshotInput" class="form-control" accept="image/*" required>
                        <div class="form-text small">कृपया तुमच्या व्यवहार पुष्टीकरणाची स्पष्ट प्रतिमा अपलोड करा.</div>
                    </div>

                    <!-- Progress Bar (Initially Hidden) -->
                    <div id="uploadProgressContainer" class="d-none mb-3">
                        <div class="progress rounded-pill" style="height: 10px;">
                            <div id="uploadBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="text-center mt-2">
                             <span class="small text-muted" id="uploadStatus">अपलोड होत आहे... (Uploading...)</span>
                        </div>
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> पेमेंट सबमिट करा
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Month Details Modal -->
<div class="modal fade" id="monthDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 bg-light rounded-top-4">
                <h5 class="modal-title fw-bold" id="monthModalTitle">महिना तपशील</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light shadow-sm">
                            <tr>
                                <th class="ps-4">तारीख (Date)</th>
                                <th class="text-center">Water Can Delivered</th>
                                <th class="text-center">Can Received</th>
                            </tr>
                        </thead>
                        <tbody id="monthDetailsBody">
                            <!-- Data will be loaded via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="monthDetailsLoader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">लोड होत आहे...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthModal = new bootstrap.Modal(document.getElementById('monthDetailsModal'));
    const detailsBody = document.getElementById('monthDetailsBody');
    const modalTitle = document.getElementById('monthModalTitle');
    const loader = document.getElementById('monthDetailsLoader');

    bindTableEvents();

    // Auto-refresh summary and table every 10 seconds for real-time updates
    setInterval(loadBillStatus, 10000);

    const paymentForm = document.getElementById('paymentUploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const progressContainer = document.getElementById('uploadProgressContainer');
    const uploadBar = document.getElementById('uploadBar');
    const uploadStatus = document.getElementById('uploadStatus');

    // Auto-fill amount based on selected month
    document.querySelector('select[name="payment_month"]').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const due = option.getAttribute('data-due');
        if(due) {
            document.querySelector('input[name="amount"]').value = parseFloat(due).toFixed(2);
        } else {
            document.querySelector('input[name="amount"]').value = '';
        }
    });

    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // 1. Disable UI
        submitBtn.disabled = true;
        progressContainer.classList.remove('d-none');
        
        const formData = new FormData(this);
        const xhr = new XMLHttpRequest();

        // Track Progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                uploadBar.style.width = percent + '%';
                if(percent === 100) {
                    uploadStatus.innerHTML = '<i class="fa-solid fa-sync fa-spin me-2"></i> सर्व्हरवर प्रक्रिया करत आहे...';
                }
            }
        });

        // Handle Response
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if(res.success) {
                            uploadStatus.innerHTML = `<span class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i> ${res.message}</span>`;
                            uploadBar.classList.remove('progress-bar-animated');
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'यशस्वी!',
                                text: 'Payment submitted successfully and sent to Admin.',
                                confirmButtonText: 'ठीक आहे'
                            }).then(() => {
                                loadBillStatus(); // Dynamically update the page
                                resetForm();     // Clear form
                                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'त्रुटी!',
                                text: res.message,
                                confirmButtonText: 'ठीक आहे'
                            });
                            resetForm();
                        }
                    } catch (e) {
                        Swal.fire({
                            icon: 'error',
                            title: 'त्रुटी!',
                            text: 'सर्व्हर प्रतिसाद वाचताना त्रुटी आली.',
                            confirmButtonText: 'ठीक आहे'
                        });
                        resetForm();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'नेटवर्क त्रुटी!',
                        text: 'नेटवर्क एरर. कृपया पुन्हा प्रयत्न करा.',
                        confirmButtonText: 'ठीक आहे'
                    });
                    resetForm();
                }
            }
        };

        xhr.open('POST', 'my_bill.php', true);
        xhr.send(formData);

        function resetForm() {
            paymentForm.reset();
            submitBtn.disabled = false;
            progressContainer.classList.add('d-none');
            uploadBar.style.width = '0%';
            uploadStatus.innerText = 'अपलोड होत आहे...';
        }
    });

    function loadBillStatus() {
        fetch('my_bill.php?action=fetch_bill_status')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                // Update Summary Cards
                document.getElementById('statTotal').innerText = '₹' + parseFloat(res.summary.total).toLocaleString('en-IN', {minimumFractionDigits: 2});
                document.getElementById('statPaid').innerText = '₹' + parseFloat(res.summary.paid).toLocaleString('en-IN', {minimumFractionDigits: 2});
                document.getElementById('statPending').innerText = '₹' + parseFloat(res.summary.pending).toLocaleString('en-IN', {minimumFractionDigits: 2});
                document.getElementById('modalPendingAmount').innerText = '₹' + parseFloat(res.summary.pending).toLocaleString('en-IN', {minimumFractionDigits: 2});

                // Update Table
                const tbody = document.getElementById('monthlyBillBody');
                tbody.innerHTML = '';
                if(res.breakdown.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted">कोणतेही बिल रेकॉर्ड सापडले नाहीत.</td></tr>';
                } else {
                    res.breakdown.forEach(row => {
                        const monthlyPaid = parseFloat(row.monthly_paid || 0);
                        const monthlyTotal = parseFloat(row.monthly_total);
                        const pendingBill = Math.max(0, monthlyTotal - monthlyPaid);

                        let statusText = 'प्रलंबित (Pending)';
                        let statusClass = 'danger';
                        if (pendingBill <= 0) { statusText = 'यशस्वी (Paid)'; statusClass = 'success'; }
                        else if (row.last_payment_status === 'Pending') { statusText = 'तपासणी सुरू (Checking)'; statusClass = 'warning'; }
                        else if (row.last_payment_status === 'Remaining') { statusText = 'प्रलंबित (Pending)'; statusClass = 'danger'; }

                        const monthDate = new Date(row.month_key + '-01');
                        const monthName = monthDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                        const fullMonthName = monthDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

                        tbody.innerHTML += `
                            <tr>
                                <td class="ps-4 fw-medium">
                                    <a href="javascript:void(0)" class="text-decoration-none view-month-details" 
                                       data-month="${row.month_key}" data-month-name="${fullMonthName}">
                                        ${monthName}
                                    </a>
                                </td>
                                <td class="fw-bold text-primary">₹${monthlyTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                <td class="fw-bold text-danger">₹${pendingBill.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                <td>
                                    <span class="badge bg-${statusClass} rounded-pill px-3">${statusText}</span>
                                </td>
                            </tr>
                        `;
                    });
                    
                    // Re-bind click events for newly created links
                    bindTableEvents();
                }

                // Update Modal Dropdown
                const select = document.querySelector('select[name="payment_month"]');
                select.innerHTML = '<option value="">प्रलंबित महिना निवडा...</option>';
                res.breakdown.forEach(row => {
                    const monthlyPaid = parseFloat(row.monthly_paid || 0);
                    const monthlyTotal = parseFloat(row.monthly_total);
                    if(monthlyTotal > monthlyPaid) {
                        const monthDate = new Date(row.month_key + '-01');
                        const monthName = monthDate.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                        const due = monthlyTotal - monthlyPaid;
                        select.innerHTML += `<option value="${row.month_key}" data-due="${due}">${monthName} (₹${due.toLocaleString('en-IN', {minimumFractionDigits: 2})})</option>`;
                    }
                });
            }
        });
    }

    function bindTableEvents() {
        document.querySelectorAll('.view-month-details').forEach(link => {
            link.onclick = function() {
                const month = this.getAttribute('data-month');
                const monthName = this.getAttribute('data-month-name');
                openDetails(month, monthName);
            };
        });
    }

    function openDetails(month, monthName) {
        modalTitle.innerText = monthName + " - तपशील";
        detailsBody.innerHTML = '';
        loader.classList.remove('d-none');
        monthModal.show();

        fetch(`my_bill.php?action=fetch_month_details&month=${month}`)
            .then(r => r.json())
            .then(res => {
                loader.classList.add('d-none');
                if(res.success) {
                    if(res.data.length === 0) {
                        detailsBody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">तपशील सापडले नाहीत.</td></tr>';
                    } else {
                        res.data.forEach(row => {
                            const isDelivered = row.status === 'Delivered';
                            const isReceived = parseInt(row.can_received) === 1;
                            const deliveredIcon = isDelivered ? '<i class="fa-solid fa-circle-check text-success fs-5"></i>' : '<i class="fa-solid fa-circle-xmark text-danger fs-5"></i>';
                            const receivedIcon = isReceived ? '<i class="fa-solid fa-circle-check text-success fs-5"></i>' : '<i class="fa-solid fa-circle-xmark text-danger fs-5"></i>';
                            detailsBody.innerHTML += `
                                <tr>
                                    <td class="ps-4 fw-medium">${new Date(row.delivery_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                                    <td class="text-center">${deliveredIcon}</td>
                                    <td class="text-center">${receivedIcon}</td>
                                </tr>
                            `;
                        });
                    }
                }
            });
    }

    // Prevent page refresh during upload
    window.onbeforeunload = function() {
        if(submitBtn.disabled && !uploadStatus.innerHTML.includes('fa-check-circle')) {
            return "अपलोड सुरू आहे. कृपया प्रक्रिया पूर्ण होईपर्यंत प्रतीक्षा करा.";
        }
    };
});
</script>

<?php include 'includes/footer.php'; ?>
