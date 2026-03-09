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
            <h3 class="fw-bold text-dark mb-1">Bill & Payments</h3>
            <p class="text-muted small mb-0">Review combined billing and payment data.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="input-group" style="width: 250px;">
                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-calendar-days text-muted"></i></span>
                <select id="monthFilter" class="form-select border-start-0 ps-0" onchange="loadData()">
                    <option value="">All Months</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Sr. No.</th>
                            <th>Customer Name</th>
                            <th>Bill Month</th>
                            <th>Total Bill (Calculated)</th>
                            <th>Plan Amount (Fixed)</th>
                            <th>Submitted Amount</th>
                            <th>Paid Amount</th>
                            <th>Pending</th>
                            <th>Screenshot</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="billPaymentsTableBody">
                        <tr>
                            <td colspan="11" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <div class="mt-2 text-muted small">Loading records...</div>
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

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 text-center">
                        <thead class="bg-light">
                            <tr>
                                <th>Payment Date</th>
                                <th>Amount</th>
                                <th>Payment Type</th>
                            </tr>
                        </thead>
                        <tbody id="paymentDetailsTbody">
                            <!-- Rows will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Amount Entry Modal -->
<div class="modal fade" id="amountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="amountModalTitle">Enter Payment Amount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="amountForm">
                    <input type="hidden" id="paymentRowId" name="payment_id">
                    <input type="hidden" id="paymentMethod" name="status">
                    <div class="mb-3">
                        <label class="form-label" id="amountLabel">Amount (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="paymentAmountInput" name="amount" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitAmount()">Submit Payment</button>
            </div>
        </div>
    </div>
</div>

<script>
let lastData = null;

document.addEventListener('DOMContentLoaded', () => {
    loadMonths();
    loadData();
    setInterval(() => {
        loadData(true);
    }, 10000);
});

function loadMonths() {
    fetch('api/bill_payments.php?action=get_months')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const filter = document.getElementById('monthFilter');
            res.data.forEach(m => {
                const date = new Date(m + '-01');
                const label = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                filter.innerHTML += `<option value="${m}">${label}</option>`;
            });
        }
    });
}

function loadData(isPoll = false) {
    const tbody = document.getElementById('billPaymentsTableBody');
    const month = document.getElementById('monthFilter').value;
    
    fetch(`api/bill_payments.php?action=fetch_all&month=${month}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastData === currentDataStr) return;
            lastData = currentDataStr;

            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5 text-muted">No records found.</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach((row, index) => {
                let srNo = index + 1;
                const calculatedBillVal = parseFloat(row.calculated_bill || 0);
                const calculatedBill = calculatedBillVal.toFixed(2);
                const deliveredCount = parseInt(row.delivered_count || 0);
                const planAmount = parseFloat(row.plan_amount || 0).toFixed(2);
                
                const submittedAmount = parseFloat(row.submitted_amount || 0);
                const totalPaid = parseFloat(row.total_paid || 0);
                
                // displayPaidAmount should be what is ACTUALLY paid (status IN Approved, Remaining, etc.)
                const displayPaidAmount = totalPaid;
                
                // Calculate Pending amount as Total Bill - Total Paid
                const pending = Math.max(0, calculatedBillVal - displayPaidAmount);
                const monthName = row.payment_month ? new Date(row.payment_month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'General';
                
                const displayStatus = row.status;
                let bgBadge = 'warning';
                if (displayStatus === 'Approved') bgBadge = 'success';
                else if (displayStatus === 'Online Paid') bgBadge = 'info';
                else if (displayStatus === 'Cash Paid') bgBadge = 'secondary';
                
                const hasScreenshot = row.screenshot_url && row.screenshot_url.trim() !== '';
                let screenshotCol = '';
                if (hasScreenshot) {
                    screenshotCol = `<button class="btn btn-sm btn-outline-info rounded-pill" onclick="viewScreenshot('../${row.screenshot_url}')"><i class="fa-solid fa-eye me-1"></i> View</button>`;
                } else {
                    screenshotCol = `<span class="text-muted small">No Proof</span>`;
                }
                
                let actionBtn = '';
                let buttonsHtml = '';
                
                const isPendingUpload = hasScreenshot && (row.status === 'Pending' || row.status === 'Remaining');

                if (isPendingUpload) {
                    buttonsHtml += `<button type="button" class="btn btn-sm btn-success rounded-pill px-3 m-1 fw-bold" onclick="approvePayment(${row.id}, 'Approved')">Approval</button>`;
                }

                if (pending > 0 && !isPendingUpload) {
                    buttonsHtml += `
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 m-1 fw-bold" onclick="openAmountModal(${row.id}, 'Online Paid')">Online</button>
                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3 m-1 fw-bold" onclick="openAmountModal(${row.id}, 'Cash Paid')">Cash</button>
                    `;
                }

                if (buttonsHtml !== '') {
                    actionBtn = `
                        <div class="d-flex justify-content-end align-items-center flex-wrap">
                            ${buttonsHtml}
                        </div>
                    `;
                } else {
                    actionBtn = `<button class="btn btn-sm btn-light rounded-pill px-3 text-muted" disabled>Processed</button>`;
                }

                html += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">${srNo}</td>
                        <td>
                            <div class="fw-bold text-dark">${row.user_name}</div>
                            <small class="text-muted">${new Date(row.created_at).toLocaleString()}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">${monthName}</span></td>
                        <td><span class="badge bg-success fs-6">₹${calculatedBill}</span> <small class="text-muted d-block">${deliveredCount} deliveries</small></td>
                        <td>₹${planAmount}</td>
                        <td>
                            <a href="#" onclick="viewPaymentDetails(${row.id}, '${row.created_at}', ${submittedAmount}, '${displayStatus}'); return false;" class="text-primary fw-bold text-decoration-underline" title="View details">
                                ₹${submittedAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}
                            </a>
                        </td>
                        <td>
                            <div class="small text-success fw-bold">₹${displayPaidAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                            <small class="text-muted">Paid for Month</small>
                        </td>
                        <td class="text-danger fw-bold">₹${pending.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        <td>${screenshotCol}</td>
                        <td><span class="badge bg-${bgBadge} px-2 py-1 rounded-pill">${displayStatus}</span></td>
                        <td class="text-end pe-4">${actionBtn}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    })
    .catch(err => {
        console.error('Error loading data:', err);
        if(!isPoll) tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5 text-danger">Failed to load data.</td></tr>';
    });
}

function viewScreenshot(imageUrl) {
    document.getElementById('modalScreenshotImg').src = imageUrl;
    const modal = new bootstrap.Modal(document.getElementById('screenshotModal'));
    modal.show();
}

function viewPaymentDetails(paymentId, dateStr, amount, status) {
    const tbody = document.getElementById('paymentDetailsTbody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div></td></tr>';
    
    // Create new modal instance or get existing
    const modalEl = document.getElementById('paymentDetailsModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }
    modal.show();
    
    fetch(`api/bill_payments.php?action=fetch_history&payment_id=${paymentId}`)
    .then(r => r.json())
    .then(res => {
        if(res.success && res.data && res.data.length > 0) {
            let html = '';
            let totalAmount = 0;
            res.data.forEach(r => {
                let typeClass = 'badge bg-primary';
                if(r.payment_type === 'Online') typeClass = 'badge bg-info text-dark';
                else if(r.payment_type === 'Cash') typeClass = 'badge bg-dark';
                
                let amt = parseFloat(r.amount);
                totalAmount += amt;
                
                html += `
                    <tr>
                        <td>${r.formatted_date}</td>
                        <td class="fw-bold text-primary">₹${amt.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        <td><span class="${typeClass}">${r.payment_type}</span></td>
                    </tr>
                `;
            });
            
            // Add a total row
            html += `
                <tr class="table-light">
                    <td class="text-end fw-bold">Total:</td>
                    <td class="fw-bold text-success fs-6">₹${totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                    <td></td>
                </tr>
            `;
            tbody.innerHTML = html;
        } else {
            // Fallback for old records without history
            const d = new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            let pType = 'User Paid';
            if (status === 'Online Paid') pType = 'Online';
            else if (status === 'Cash Paid') pType = 'Cash';
            
            let typeClass = 'badge bg-secondary';
            if (pType === 'Online') typeClass = 'badge bg-info text-dark';
            if (pType === 'Cash') typeClass = 'badge bg-dark';
            if (pType === 'User Paid') typeClass = 'badge bg-primary';
            
            tbody.innerHTML = `
                <tr>
                    <td>${d}</td>
                    <td class="fw-bold text-primary">₹${parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                    <td><span class="${typeClass}">${pType}</span></td>
                </tr>
            `;
        }
    })
    .catch(err => {
        tbody.innerHTML = '<tr><td colspan="3" class="text-danger text-center">Failed to load details.</td></tr>';
    });
}

function openAmountModal(id, method) {
    document.getElementById('paymentRowId').value = id;
    document.getElementById('paymentMethod').value = method;
    document.getElementById('paymentAmountInput').value = '';
    
    document.getElementById('amountModalTitle').innerText = `Record ${method === 'Cash Paid' ? 'Cash' : 'Online'} Payment`;
    
    const modal = new bootstrap.Modal(document.getElementById('amountModal'));
    modal.show();
}

function submitAmount() {
    const id = document.getElementById('paymentRowId').value;
    const status = document.getElementById('paymentMethod').value;
    const amount = document.getElementById('paymentAmountInput').value;
    
    if (!amount || parseFloat(amount) <= 0) {
        Swal.fire({ icon: 'error', title: 'Invalid Amount', text: 'Please enter a valid amount' });
        return;
    }

    const fd = new FormData();
    fd.append('action', 'mark_paid');
    fd.append('payment_id', id);
    fd.append('status', status);
    fd.append('amount', amount);

    fetch('api/bill_payments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Saved!', text: res.message, timer: 2000, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('amountModal')).hide();
            loadData();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    });
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

            fetch('api/bill_payments.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'Approved!', text: res.message, timer: 2000, showConfirmButton: false });
                    loadData();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            });
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
