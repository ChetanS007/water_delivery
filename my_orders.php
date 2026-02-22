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
        <h2 class="fw-bold font-heading">माझे सबस्क्रिप्शन</h2>
        <a href="index.php" class="btn btn-outline-primary rounded-pill px-4">
            <i class="fa-solid fa-arrow-left me-2"></i> मुख्यपृष्ठावर जा
        </a>
    </div>

    <div class="glass-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ऑर्डर आयडी</th>
                        <th>प्रकार</th>
                        <th>निवडक दिवस</th>
                        <th>रक्कम</th>
                        <th>स्थिती</th>
                        <th>तारीख</th>
                        <th>कृती</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
let lastOrderData = null;

const days_map = {'Mon':'सोम', 'Tue':'मंगळ', 'Wed':'बुध', 'Thu':'गुरु', 'Fri':'शुक्र', 'Sat':'शनी', 'Sun':'रवी'};
const status_map = {
    'Pending': 'प्रलंबित',
    'Approved': 'मंजूर',
    'Assigned': 'नियुक्त',
    'Delivered': 'पोहोचवले',
    'Cancelled': 'रद्द',
    'Success': 'यशस्वी'
};
const type_map = {
    'Daily': 'दररोज',
    'Alternate': 'एक दिवसाआड',
    'Custom': 'निवडक दिवस'
};

document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
    setInterval(() => loadOrders(true), 15000);
});

function loadOrders(isPoll = false) {
    const tbody = document.getElementById('ordersTableBody');
    
    fetch('api/my_orders.php')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if(lastOrderData === currentDataStr) return;
            lastOrderData = currentDataStr;

            if(res.data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <p class="text-muted mb-0">कोणतीही ऑर्डर सापडली नाही.</p>
                            <a href="index.php" class="btn btn-sm btn-primary mt-2">तुमची पहिली ऑर्डर द्या</a>
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            res.data.forEach(order => {
                const statusClass = order.status === 'Delivered' ? 'success' : (order.status === 'Cancelled' ? 'danger' : (order.status === 'Assigned' ? 'info' : 'warning'));
                
                let daysHtml = '-';
                if(order.order_type === 'Custom' && order.custom_days) {
                    try {
                        const days = JSON.parse(order.custom_days);
                        daysHtml = days.map(d => days_map[d] || d).join(", ");
                    } catch(e) { daysHtml = '-'; }
                }

                const date = new Date(order.created_at);
                const dateStr = date.toLocaleDateString('mr-IN', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + 
                                date.toLocaleTimeString('mr-IN', { hour: '2-digit', minute: '2-digit' });

                html += `
                    <tr class="animate__animated animate__fadeIn">
                        <td>#${order.id}</td>
                        <td>${type_map[order.order_type] || order.order_type}</td>
                        <td>${daysHtml}</td>
                        <td>₹${order.total_amount}</td>
                        <td><span class="badge bg-${statusClass}">${status_map[order.status] || order.status}</span></td>
                        <td>${dateStr}</td>
                        <td>
                            <a href="my_bill.php" class="btn btn-sm btn-success rounded-pill px-3">
                                <i class="fa-solid fa-upload me-1"></i> अपलोड
                            </a>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    });
}
</script>
