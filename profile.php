<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch Active Subscription
$sub_sql = "
    SELECT o.*, oi.quantity, p.product_name, p.price, p.image_url, db.full_name as delivery_boy_name, db.mobile as delivery_boy_mobile
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN delivery_assignments da ON o.id = da.order_id
    LEFT JOIN delivery_boys db ON da.delivery_boy_id = db.id
    WHERE o.user_id = ? AND o.status IN ('Pending', 'Approved', 'Assigned')
    ORDER BY o.created_at DESC LIMIT 1
";
$sub_stmt = $pdo->prepare($sub_sql);
$sub_stmt->execute([$user_id]);
$subscription = $sub_stmt->fetch(PDO::FETCH_ASSOC);
?>
<?php include 'includes/header.php'; ?>

<div class="container py-5" style="margin-top: 80px;">
    <div class="row g-4">
        <!-- Profile Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100">
                <div class="bg-primary p-4 text-center text-white position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10"></div>
                    <div class="bg-white rounded-circle p-1 d-inline-block mb-3 position-relative z-2">
                         <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                             <i class="fa-solid fa-user fa-3x text-primary"></i>
                         </div>
                    </div>
                    <h4 class="fw-bold position-relative z-2 mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="small opacity-75 mb-0 position-relative z-2"><?php echo htmlspecialchars($user['mobile']); ?></p>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light p-2 rounded-circle me-3"><i class="fa-solid fa-location-dot text-primary"></i></div>
                        <div>
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.7rem;">Delivery Address</small>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($user['address']); ?></span>
                        </div>
                    </div>
                     <div class="d-flex align-items-center mb-4">
                        <div class="bg-light p-2 rounded-circle me-3"><i class="fa-solid fa-tag text-primary"></i></div>
                        <div>
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.7rem;">Account Type</small>
                            <span class="badge bg-info text-white rounded-pill px-3"><?php echo htmlspecialchars($user['customer_type']); ?></span>
                        </div>
                    </div>
                    
                    <div class="text-center p-3 bg-light rounded-3 mb-3 border border-dashed">
                        <div id="qrcode" class="d-flex justify-content-center"></div>
                        <p class="small text-muted mt-2 mb-0 fw-bold">Scan for Delivery</p>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="my_orders.php" class="btn btn-outline-primary rounded-pill fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> Order History</a>
                        <button class="btn btn-outline-secondary rounded-pill fw-bold" disabled><i class="fa-solid fa-pen me-2"></i> Edit Profile</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Subscription -->
        <div class="col-lg-8">
            <h4 class="fw-bold text-primary mb-4 font-heading">Current Subscription</h4>
            
            <?php if ($subscription): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                        <div>
                            <?php 
                            $badgeClass = match($subscription['status']) {
                                'Pending' => 'bg-warning text-white', 
                                'Approved' => 'bg-info text-white',
                                'Assigned' => 'bg-success text-white',
                                default => 'bg-primary text-white'
                            }; 
                            ?>
                            <span class="badge <?php echo $badgeClass; ?> border px-3 py-2 rounded-pill mb-2">
                                <i class="fa-solid fa-circle-check me-2"></i><?php echo $subscription['status']; ?>
                            </span>
                            <small class="text-muted d-block">Order #<?php echo $subscription['id']; ?></small>
                        </div>
                        <h3 class="fw-bold text-dark mb-0">₹<?php echo $subscription['total_amount']; ?></h3>
                    </div>
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center mb-3 mb-md-0">
                                <?php $imgSrc = !empty($subscription['image_url']) ? $subscription['image_url'] : 'assets/img/bottle-generic.png'; ?>
                                <img src="<?php echo htmlspecialchars($imgSrc); ?>" onerror="this.src='assets/img/shop-1.jpg'" class="img-fluid rounded-3 shadow-sm" alt="Product">
                            </div>
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($subscription['product_name']); ?></h5>
                                <p class="text-muted mb-2 small">
                                    <?php echo $subscription['quantity']; ?> Can(s) • <?php echo $subscription['order_type']; ?> Delivery
                                </p>
                                <?php if($subscription['order_type'] === 'Custom' && $subscription['custom_days']): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach(json_decode($subscription['custom_days']) as $day): ?>
                                            <span class="badge bg-light text-dark border"><?php echo $day; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 border-start ps-md-4">
                                <?php if($subscription['delivery_boy_name']): ?>
                                    <p class="small text-muted fw-bold text-uppercase mb-2">Delivery Partner</p>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-motorcycle"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($subscription['delivery_boy_name']); ?></h6>
                                            <a href="tel:<?php echo $subscription['delivery_boy_mobile']; ?>" class="small text-decoration-none text-primary fw-bold">
                                                <i class="fa-solid fa-phone me-1"></i> Call Now
                                            </a>
                                        </div>
                                    </div>
                                <?php elseif($subscription['status'] === 'Approved'): ?>
                                    <div class="p-3 bg-info bg-opacity-10 rounded-3 text-info small fw-bold">
                                        <i class="fa-solid fa-spinner fa-spin me-2"></i> Assigning Partner...
                                    </div>
                                <?php else: ?>
                                    <div class="p-3 bg-warning bg-opacity-10 rounded-3 text-warning small fw-bold">
                                        <i class="fa-solid fa-clock me-2"></i> Awaiting Approval
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 p-3 text-center">
                        <small class="text-muted">Need help with this order? <a href="#" class="fw-bold text-primary">Contact Support</a></small>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border border-dashed">
                    <img src="assets/img/bottle-generic.png" height="100" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                    <h5 class="fw-bold text-muted">No Active Subscription</h5>
                    <p class="text-muted small mb-4">You haven't subscribed to any water delivery plan yet.</p>
                    <a href="index.php#products" class="btn btn-primary rounded-pill px-4 fw-bold">Browse Plans</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    var qrData = "<?php echo $user['qr_code']; ?>";
    if(qrData) {
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 150,
            height: 150
        });
    }

    // Polling for Subscription Status Changes (Real-time updates)
    const currentStatus = "<?php echo $subscription ? $subscription['status'] : 'None'; ?>";
    const currentBoy = "<?php echo $subscription['delivery_boy_name'] ?? ''; ?>";

    setInterval(() => {
        fetch('api/check_sub_status.php')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                // Check if status changed
                if (res.status !== currentStatus) {
                    location.reload();
                }
                // Check if delivery boy assigned (Transition from Approved -> Assigned)
                if (res.delivery_boy && res.delivery_boy !== currentBoy) {
                    location.reload();
                }
            }
        });
    }, 8000);
</script>
