<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';

// Handle Approval
if (isset($_POST['action']) && $_POST['action'] === 'approve' && isset($_POST['payment_id'])) {
    $paymentId = $_POST['payment_id'];
    $stmt = $pdo->prepare("UPDATE customer_payments SET status = 'Approved' WHERE id = ?");
    if ($stmt->execute([$paymentId])) {
        $message = "Payment approved successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to approve payment.";
        $messageType = "danger";
    }
}

// Fetch Payments with User and Billing Info
$sql = "
    SELECT 
        cp.id,
        cp.user_id,
        u.full_name as user_name,
        cp.amount as submitted_amount,
        cp.screenshot_url,
        cp.status,
        cp.created_at,
        
        /* Total Bill for the User */
        (
            SELECT COALESCE(SUM(oi2.quantity * p2.price), 0)
            FROM daily_deliveries dd2
            JOIN orders o2 ON dd2.subscription_id = o2.id
            JOIN order_items oi2 ON o2.id = oi2.order_id
            JOIN products p2 ON oi2.product_id = p2.id
            WHERE o2.user_id = cp.user_id AND dd2.status = 'Delivered'
        ) as total_bill,

        /* Total Paid by the User (Approved only) */
        (
            SELECT COALESCE(SUM(amount), 0)
            FROM customer_payments
            WHERE user_id = cp.user_id AND status = 'Approved'
        ) as total_paid

    FROM customer_payments cp
    JOIN users u ON cp.user_id = u.id
    ORDER BY cp.created_at DESC
";

$stmt = $pdo->query($sql);
$payments = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Customer Payments</h3>
            <p class="text-muted small mb-0">Review and approve payment submissions from customers.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show shadow-sm" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Customer Name</th>
                            <th>Submitted Amount</th>
                            <th>Total Bill</th>
                            <th>Paid Amount</th>
                            <th>Pending</th>
                            <th>Screenshot</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">No payments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $pay): ?>
                                <?php 
                                    $pending = $pay['total_bill'] - $pay['total_paid'];
                                    $statusClass = $pay['status'] === 'Approved' ? 'success' : ($pay['status'] === 'Pending' ? 'warning' : 'danger');
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">#<?php echo $pay['id']; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($pay['user_name']); ?></div>
                                        <small class="text-muted"><?php echo date('d M, Y h:i A', strtotime($pay['created_at'])); ?></small>
                                    </td>
                                    <td class="fw-bold text-primary">₹<?php echo number_format($pay['submitted_amount'], 2); ?></td>
                                    <td>₹<?php echo number_format($pay['total_bill'], 2); ?></td>
                                    <td class="text-success">₹<?php echo number_format($pay['total_paid'], 2); ?></td>
                                    <td class="text-danger fw-bold">₹<?php echo number_format($pending, 2); ?></td>
                                    <td>
                                        <a href="../<?php echo htmlspecialchars($pay['screenshot_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill">
                                            <i class="fa-solid fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $statusClass; ?> rounded-pill px-3">
                                            <?php echo $pay['status'] === 'Approved' ? 'Payment Successful' : $pay['status']; ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php if ($pay['status'] === 'Pending'): ?>
                                            <form action="payments.php" method="POST" class="d-inline">
                                                <input type="hidden" name="payment_id" value="<?php echo $pay['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" onclick="return confirm('Approve this payment?')">
                                                    Approve
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light rounded-pill px-3 text-muted" disabled>Processed</button>
                                        <?php endif; ?>
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

<?php include 'includes/footer.php'; ?>