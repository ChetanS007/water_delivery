<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<?php include 'includes/header.php'; ?>

<div class="container py-5" style="margin-top: 80px;">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="glass-card mb-4">
                <h2 class="fw-bold mb-4">Profile Details</h2>
                
                <div class="row align-items-center">
                    <div class="col-md-4 text-center mb-4 mb-md-0">
                        <div class="glass-card p-3 d-inline-block bg-white">
                            <div id="qrcode"></div>
                            <p class="small text-muted mt-2 mb-0">Your Unique Delivery Code</p>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr>
                                <th class="w-25">Full Name</th>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Mobile</th>
                                <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($user['customer_type']); ?></span></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?php echo htmlspecialchars($user['address']); ?></td>
                            </tr>
                            <tr>
                                <th>Joined</th>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        </table>
                        
                        <div class="mt-4">
                            <a href="my_orders.php" class="btn btn-outline-primary">
                                <i class="fa-solid fa-list"></i> View My Orders
                            </a>
                            <button class="btn btn-outline-danger ms-2" onclick="alert('Edit Profile - Coming Soon')">
                                <i class="fa-solid fa-pen"></i> Edit Profile
                            </button>
                        </div>
                    </div>
                </div>
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
</script>
