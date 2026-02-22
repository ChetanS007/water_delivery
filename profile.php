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
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.7rem;">डिलिव्हरीचा पत्ता</small>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($user['address']); ?></span>
                        </div>
                    </div>
                     <div class="d-flex align-items-center mb-4">
                        <div class="bg-light p-2 rounded-circle me-3"><i class="fa-solid fa-tag text-primary"></i></div>
                        <div>
                            <small class="text-muted d-block uppercase fw-bold" style="font-size: 0.7rem;">खात्याचा प्रकार</small>
                            <span class="badge bg-info text-white rounded-pill px-3">
                                <?php echo ($user['customer_type'] === 'Home') ? 'घर (Home)' : 'दुकान (Shop)'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-center p-3 bg-light rounded-3 mb-3 border border-dashed">
                        <div id="qrcode" class="d-flex justify-content-center"></div>
                        <p class="small text-muted mt-2 mb-0 fw-bold">डिलिव्हरीसाठी स्कॅन करा</p>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="my_bill.php" class="btn btn-outline-primary rounded-pill fw-bold"><i class="fa-solid fa-clock-rotate-left me-2"></i> ऑर्डर इतिहास</a>
                        <button class="btn btn-outline-secondary rounded-pill fw-bold" disabled><i class="fa-solid fa-pen me-2"></i> प्रोफाइल संपादित करा</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Subscription -->
        <div class="col-lg-8" id="subscriptionSection">
            <h4 class="fw-bold text-primary mb-4 font-heading">चालू सबस्क्रिप्शन</h4>
            
            <div id="subCardContainer">
                <?php if ($subscription): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                        <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                            <div>
                                <?php 
                                $statusLabel = match($subscription['status']) {
                                    'Pending' => 'प्रलंबित', 
                                    'Approved' => 'मंजूर',
                                    'Assigned' => 'नियुक्त',
                                    default => $subscription['status']
                                };
                                $badgeClass = match($subscription['status']) {
                                    'Pending' => 'bg-warning text-white', 
                                    'Approved' => 'bg-info text-white',
                                    'Assigned' => 'bg-success text-white',
                                    default => 'bg-primary text-white'
                                }; 
                                ?>
                                <span class="badge <?php echo $badgeClass; ?> border px-3 py-2 rounded-pill mb-2">
                                    <i class="fa-solid fa-circle-check me-2"></i><?php echo $statusLabel; ?>
                                </span>
                                <small class="text-muted d-block">ऑर्डर #<?php echo $subscription['id']; ?></small>
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
                                        <?php echo $subscription['quantity']; ?> कॅन • 
                                        <?php 
                                        echo match($subscription['order_type']) {
                                            'Daily' => 'दररोज',
                                            'Alternate' => 'एक दिवसाआड',
                                            'Custom' => 'निवडक दिवस',
                                            default => $subscription['order_type']
                                        };
                                        ?> डिलिव्हरी
                                    </p>
                                    <?php if($subscription['order_type'] === 'Custom' && $subscription['custom_days']): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php 
                                            $days_map = ['Mon'=>'सोम', 'Tue'=>'मंगळ', 'Wed'=>'बुध', 'Thu'=>'गुरु', 'Fri'=>'शुक्र', 'Sat'=>'शनी', 'Sun'=>'रवी'];
                                            foreach(json_decode($subscription['custom_days']) as $day): ?>
                                                <span class="badge bg-light text-dark border"><?php echo $days_map[$day] ?? $day; ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 border-start ps-md-4">
                                    <?php if($subscription['delivery_boy_name']): ?>
                                        <p class="small text-muted fw-bold text-uppercase mb-2">डिलिव्हरी पार्टनर</p>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                <i class="fa-solid fa-motorcycle"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($subscription['delivery_boy_name']); ?></h6>
                                                <a href="tel:<?php echo $subscription['delivery_boy_mobile']; ?>" class="small text-decoration-none text-primary fw-bold">
                                                    <i class="fa-solid fa-phone me-1"></i> आत्ता कॉल करा
                                                </a>
                                            </div>
                                        </div>
                                    <?php elseif($subscription['status'] === 'Approved'): ?>
                                        <div class="p-3 bg-info bg-opacity-10 rounded-3 text-info small fw-bold">
                                            <i class="fa-solid fa-spinner fa-spin me-2"></i> जोडीदार नियुक्त करत आहे...
                                        </div>
                                    <?php else: ?>
                                        <div class="p-3 bg-warning bg-opacity-10 rounded-3 text-warning small fw-bold">
                                            <i class="fa-solid fa-clock me-2"></i> मंजुरीची प्रतीक्षा आहे
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light border-0 p-3 text-center">
                            <small class="text-muted">या ऑर्डरबद्दल मदतीची गरज आहे? <a href="#" class="fw-bold text-primary">सपोर्टशी संपर्क साधा</a></small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm border border-dashed">
                        <img src="assets/img/bottle-generic.png" height="100" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                        <h5 class="fw-bold text-muted">कोणतेही सक्रिय सबस्क्रिप्शन नाही</h5>
                        <p class="text-muted small mb-4">तुम्ही अद्याप कोणत्याही पाणी वितरण योजनेचे सबस्क्रिप्शन घेतलेले नाही.</p>
                        <a href="index.php#products" class="btn btn-primary rounded-pill px-4 fw-bold">योजना पहा</a>
                    </div>
                <?php endif; ?>
            </div>
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

    // Polling for Subscription Status Changes
    let lastSubData = null;

    setInterval(() => {
        fetch('api/customer_dashboard.php')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const currentDataStr = JSON.stringify(res.subscription);
                if (lastSubData === currentDataStr) return;
                lastSubData = currentDataStr;

                renderSubscription(res.subscription);
            }
        });
    }, 10000);

    function renderSubscription(sub) {
        const container = document.getElementById('subCardContainer');
        if (!sub) {
            container.innerHTML = `
                <div class="text-center py-5 bg-white rounded-4 shadow-sm border border-dashed">
                    <img src="assets/img/bottle-generic.png" height="100" class="mb-3 opacity-50" style="filter: grayscale(100%);">
                    <h5 class="fw-bold text-muted">कोणतेही सक्रिय सबस्क्रिप्शन नाही</h5>
                    <p class="text-muted small mb-4">तुम्ही अद्याप कोणत्याही पाणी वितरण योजनेचे सबस्क्रिप्शन घेतलेले नाही.</p>
                    <a href="index.php#products" class="btn btn-primary rounded-pill px-4 fw-bold">योजना पहा</a>
                </div>
            `;
            return;
        }

        const statusLabel = sub.status === 'Pending' ? 'प्रलंबित' : (sub.status === 'Approved' ? 'मंजूर' : (sub.status === 'Assigned' ? 'नियुक्त' : sub.status));
        const badgeClass = sub.status === 'Pending' ? 'bg-warning' : (sub.status === 'Approved' ? 'bg-info' : (sub.status === 'Assigned' ? 'bg-success' : 'bg-primary'));
        const imgSrc = sub.image_url || 'assets/img/bottle-generic.png';
        
        const typeLabel = sub.order_type === 'Daily' ? 'दररोज' : (sub.order_type === 'Alternate' ? 'एक दिवसाआड' : (sub.order_type === 'Custom' ? 'निवडक दिवस' : sub.order_type));

        const partnerHtml = sub.delivery_boy_name ? `
            <p class="small text-muted fw-bold text-uppercase mb-2">डिलिव्हरी पार्टनर</p>
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">${sub.delivery_boy_name}</h6>
                    <a href="tel:${sub.delivery_boy_mobile}" class="small text-decoration-none text-primary fw-bold">
                        <i class="fa-solid fa-phone me-1"></i> आत्ता कॉल करा
                    </a>
                </div>
            </div>
        ` : (sub.status === 'Approved' ? `
            <div class="p-3 bg-info bg-opacity-10 rounded-3 text-info small fw-bold">
                <i class="fa-solid fa-spinner fa-spin me-2"></i> जोडीदार नियुक्त करत आहे...
            </div>
        ` : `
            <div class="p-3 bg-warning bg-opacity-10 rounded-3 text-warning small fw-bold">
                <i class="fa-solid fa-clock me-2"></i> मंजुरीची प्रतीक्षा आहे
            </div>
        `);

        let customDaysHtml = '';
        if (sub.order_type === 'Custom' && sub.custom_days) {
            const days_map = {'Mon':'सोम', 'Tue':'मंगळ', 'Wed':'बुध', 'Thu':'गुरु', 'Fri':'शुक्र', 'Sat':'शनी', 'Sun':'रवी'};
            const days = JSON.parse(sub.custom_days);
            customDaysHtml = '<div class="d-flex flex-wrap gap-1">' + days.map(d => `<span class="badge bg-light text-dark border">${days_map[d] || d}</span>`).join('') + '</div>';
        }

        container.innerHTML = `
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 animate__animated animate__fadeIn">
                <div class="card-header bg-white border-0 p-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge ${badgeClass} text-white border px-3 py-2 rounded-pill mb-2">
                            <i class="fa-solid fa-circle-check me-2"></i>${statusLabel}
                        </span>
                        <small class="text-muted d-block">ऑर्डर #${sub.id}</small>
                    </div>
                    <h3 class="fw-bold text-dark mb-0">₹${sub.total_amount}</h3>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <img src="${imgSrc}" onerror="this.src='assets/img/shop-1.jpg'" class="img-fluid rounded-3 shadow-sm" alt="Product">
                        </div>
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h5 class="fw-bold text-dark mb-1">${sub.product_name}</h5>
                            <p class="text-muted mb-2 small">
                                ${sub.quantity} कॅन • ${typeLabel} डिलिव्हरी
                            </p>
                            ${customDaysHtml}
                        </div>
                        <div class="col-md-4 border-start ps-md-4">
                            ${partnerHtml}
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 p-3 text-center">
                    <small class="text-muted">या ऑर्डरबद्दल मदतीची गरज आहे? <a href="#" class="fw-bold text-primary">सपोर्टशी संपर्क साधा</a></small>
                </div>
            </div>
        `;
    }
</script>
