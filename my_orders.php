<?php
require_once 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<?php include 'includes/header.php'; ?>

<div class="container py-5" style="margin-top: 80px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">My Subscriptions</h2>
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fa-solid fa-arrow-left"></i> Back to Home
        </a>
    </div>

    <div class="glass-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Type</th>
                        <th>Custom Days</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['order_type']; ?></td>
                                <td>
                                    <?php 
                                    if($order['order_type'] == 'Custom' && $order['custom_days']) {
                                        $days = json_decode($order['custom_days']);
                                        echo implode(", ", $days);
                                    } else {
                                        echo "-";
                                    }
                                    ?>
                                </td>
                                <td>₹<?php echo $order['total_amount']; ?></td>
                                <td>
                                    <?php 
                                    $statusClass = match($order['status']) {
                                        'Delivered' => 'success',
                                        'Cancelled' => 'danger',
                                        'Assigned' => 'info',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $order['status']; ?></span>
                                </td>
                                <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <p class="text-muted mb-0">No orders found.</p>
                                <a href="index.php" class="btn btn-sm btn-primary mt-2">Place Your First Order</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
