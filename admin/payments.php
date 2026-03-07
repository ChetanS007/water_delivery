<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Customer Payments</h3>
            <p class="text-muted small mb-0">Review and approve payment submissions from customers.</p>
        </div>
        <!-- <button class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="loadPayments()">
            <i class="fa-solid fa-sync me-1"></i> Refresh
        </button> -->
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Customer Name</th>
                            <th>Bill Month</th>
                            <th>Submitted Amount</th>
                            <th>Total Bill</th>
                            <th>Paid Amount</th>
                            <th>Pending</th>
                            <th>Screenshot</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2 text-muted small">Loading payments...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Screenshot View Modal -->
<div class="modal fade" id="screenshotModal" tabindex="-1" aria-labelledby="screenshotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content " style="background-color: #00000082;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex align-items-center justify-content-center">
                <img id="modalScreenshotImg" src="" alt="Payment Screenshot" class="img-fluid rounded" style="max-height: 90vh; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

<script>
let lastPaymentData = null;

document.addEventListener('DOMContentLoaded', () => {
    loadPayments();
    
    // Auto-refresh every 10 seconds to detect new payments in real-time
    setInterval(() => {
        loadPayments(true);
    }, 10000);
});

function loadPayments(isPoll = false) {
    const tbody = document.getElementById('paymentsTableBody');
    
    fetch('api/payments.php?action=fetch_all')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            // Check if data actually changed to avoid unnecessary re-renders
            const currentDataStr = JSON.stringify(res.data);
            if (lastPaymentData === currentDataStr) return;
            lastPaymentData = currentDataStr;

            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-muted">No payments found.</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach(pay => {
                const totalBill = parseFloat(pay.total_bill);
                const totalPaid = parseFloat(pay.total_paid);
                const pending = totalBill - totalPaid;
                const statusClass = pay.status === 'Approved' ? 'success' : (pay.status === 'Remaining' ? 'warning' : (pay.status === 'Pending' ? 'warning' : 'danger'));
                const monthName = pay.payment_month ? new Date(pay.payment_month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'General';
                
                // Show the exact status in the Admin panel
                const displayStatus = pay.status;
                
                let actionBtn = `<button class="btn btn-sm btn-light rounded-pill px-3 text-muted" disabled>Processed</button>`;
                
                if (pay.status === 'Pending') {
                    const pendingAfter = totalBill - (totalPaid + parseFloat(pay.submitted_amount));
                    if (pendingAfter > 0.01) {
                         actionBtn = `<button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold text-white" onclick="approvePayment(${pay.id}, 'Remaining')">Remaining</button>`;
                    } else {
                         actionBtn = `<button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" onclick="approvePayment(${pay.id}, 'Approved')">Approve</button>`;
                    }
                }

                html += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#${pay.id}</td>
                        <td>
                            <div class="fw-bold text-dark">${pay.user_name}</div>
                            <small class="text-muted">${new Date(pay.created_at).toLocaleString()}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">${monthName}</span></td>
                        <td class="fw-bold text-primary">₹${parseFloat(pay.submitted_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        <td>
                            <div class="small fw-bold">₹${totalBill.toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                            <small class="text-muted">Total for Month</small>
                        </td>
                        <td>
                            <div class="small text-success fw-bold">₹${totalPaid.toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                            <small class="text-muted">Paid for Month</small>
                        </td>
                        <td class="text-danger fw-bold">₹${pending.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-info rounded-pill" onclick="viewScreenshot('../${pay.screenshot_url}')">
                                <i class="fa-solid fa-eye me-1"></i> View
                            </button>
                        </td>
                        <td>
                            <span class="badge bg-${statusClass} rounded-pill px-3">
                                ${displayStatus}
                            </span>
                        </td>
                        <td class="text-end pe-4">${actionBtn}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    })
    .catch(err => {
        console.error('Error loading payments:', err);
        if(!isPoll) tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-danger">Failed to load payments.</td></tr>';
    });
}

function viewScreenshot(imageUrl) {
    document.getElementById('modalScreenshotImg').src = imageUrl;
    const modal = new bootstrap.Modal(document.getElementById('screenshotModal'));
    modal.show();
}

function approvePayment(id, status = 'Approved') {
    const title = status === 'Remaining' ? 'Confirm Partial Payment?' : 'Approve Full Payment?';
    const text = status === 'Remaining' ? 'This will mark the payment as partial (Remaining).' : 'This will mark the bill as fully Paid (Approved).';
    
    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: status === 'Remaining' ? '#0dcaf0' : '#198754',
        confirmButtonText: 'Yes, Process'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'approve');
            fd.append('payment_id', id);
            fd.append('status', status);

            fetch('api/payments.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'Approved!', text: res.message, timer: 2000, showConfirmButton: false });
                    loadPayments(); // Instantly refresh data
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            });
        }
    });
}
</script>
<?php include 'includes/footer.php'; ?>