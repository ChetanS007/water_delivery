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

<!-- Add jQuery (Required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Add Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Bill & Payments</h3>
            <p class="text-muted small mb-0" id="billTitleDesc">Review combined billing and payment data.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div id="filterIndicator" class="d-none">
                <a href="bill&payments.php" class="btn btn-sm btn-outline-secondary rounded-pill pe-3">
                    <i class="fa-solid fa-circle-xmark ms-2"></i> Clear Customer Filter
                </a>
            </div>
            <div class="input-group" style="width: 250px;">
                <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-calendar-days text-muted"></i></span>
                <select id="monthFilter" class="form-select border-start-0 ps-0" onchange="refreshCurrentTab()">
                    <option value="">All Months</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-4 shadow-sm d-inline-flex" id="paymentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 fw-bold" id="monthly-bill-tab" data-bs-toggle="pill" data-bs-target="#monthly-bill" type="button" role="tab">Monthly Bill</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 fw-bold" id="counter-payment-tab" data-bs-toggle="pill" data-bs-target="#counter-payment" type="button" role="tab">Counter Payment</button>
        </li>
    </ul>

    <div class="tab-content" id="paymentTabsContent">
        <!-- Monthly Bill Tab -->
        <div class="tab-pane fade show active" id="monthly-bill" role="tabpanel">
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

        <!-- Counter Payment Tab -->
        <div class="tab-pane fade" id="counter-payment" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="bg-primary bg-opacity-10 px-3 py-2 rounded-3">
                    <span class="text-primary fw-bold">Total Monthly Receipt: </span>
                    <span class="text-dark fw-bold fs-5" id="counterTotalAmount">₹0.00</span>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" onclick="openAddCounterModal()">
                    <i class="fa-solid fa-plus me-2"></i> Add Payment
                </button>
            </div>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-start">Sr. No.</th>
                                    <th class="text-start">Customer Name</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="counterPaymentsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-5">
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
    </div>
</div>

<!-- Screenshot View Modal -->
<div class="modal fade" id="screenshotModal" tabindex="-1" aria-hidden="true">
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

<!-- Add Counter Payment Modal -->
<div class="modal fade" id="addCounterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Add Counter Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="counterPaymentForm">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Select Customer</label>
                        <select name="user_id" id="counterUserSelect" class="form-select select2-user" required style="width: 100%;">
                            <option value="">Choose Customer...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Month</label>
                        <input type="month" name="payment_month" class="form-control" value="<?php echo date('Y-m'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Amount (₹)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">₹</span>
                            <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Payment Type</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_type" id="typeCash" value="Cash" checked>
                                <label class="form-check-label" for="typeCash">Cash</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_type" id="typeOnline" value="Online">
                                <label class="form-check-label" for="typeOnline">Online</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold">Save Payment</button>
                    </div>
                </form>
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
                        <tbody id="paymentDetailsTbody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delivery Details Modal -->
<div class="modal fade" id="deliveryDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="deliveryDetailsTitle">Delivery Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th class="text-center">Delivered Qty</th>
                                <th class="text-center">Returned Qty</th>
                                <th class="text-center">Can Status</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="deliveryDetailsTbody"></tbody>
                    </table>
                </div>
                <div id="deliveryDetailsLoader" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Amount Entry Modal (For Monthly Bills) -->
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
                        <label class="form-label">Amount (₹)</label>
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

<?php include 'includes/footer.php'; ?>
<!-- Add Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let lastMonthlyData = null;
let lastCounterData = null;

$(document).ready(function() {
    loadMonths();
    refreshCurrentTab();
    
    // Auto refresh every 10s
    setInterval(() => {
        refreshCurrentTab(true);
    }, 10000);

    // Form submission
    document.getElementById('counterPaymentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitCounterPayment();
    });

    // Load users for the dropdown
    fetchUsers();

    // Initialize Select2
    $('.select2-user').select2({
        dropdownParent: $('#addCounterModal'),
        placeholder: "Choose Customer..."
    });
});

function refreshCurrentTab(isPoll = false) {
    const activeTab = document.querySelector('#paymentTabs .nav-link.active').id;
    if (activeTab === 'monthly-bill-tab') {
        loadMonthlyBills(isPoll);
    } else {
        loadCounterPayments(isPoll);
    }
}

// Handle tab changes manually
document.querySelectorAll('#paymentTabs .nav-link').forEach(tab => {
    tab.addEventListener('shown.bs.tab', () => {
        refreshCurrentTab();
    });
});

function loadMonths() {
    fetch('api/bill_payments.php?action=get_months')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const filter = document.getElementById('monthFilter');
            const currentSelected = filter.value;
            filter.innerHTML = '<option value="">All Months</option>';
            res.data.forEach(m => {
                const date = new Date(m + '-01');
                const label = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                const selected = (m === currentSelected) ? 'selected' : '';
                filter.innerHTML += `<option value="${m}" ${selected}>${label}</option>`;
            });
        }
    });
}

function loadMonthlyBills(isPoll = false) {
    const tbody = document.getElementById('billPaymentsTableBody');
    const month = document.getElementById('monthFilter').value;
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('user_id') || '';
    
    if (userId) {
        document.getElementById('filterIndicator').classList.remove('d-none');
        document.getElementById('billTitleDesc').innerText = `Viewing payments for Customer ID: #${userId}`;
    }

    fetch(`api/bill_payments.php?action=fetch_all&month=${month}&user_id=${userId}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastMonthlyData === currentDataStr) return;
            lastMonthlyData = currentDataStr;

            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5 text-muted">No records found.</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach((row, index) => {
                let srNo = index + 1;
                const calculatedBillVal = parseFloat(row.calculated_bill || 0);
                const totalPaid = parseFloat(row.total_paid || 0);
                const pending = Math.max(0, calculatedBillVal - totalPaid);
                const monthName = row.payment_month ? new Date(row.payment_month + '-01').toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'General';
                
                let bgBadge = 'warning';
                if (row.status === 'Approved') bgBadge = 'success';
                else if (row.status === 'Online Paid') bgBadge = 'info';
                else if (row.status === 'Cash Paid') bgBadge = 'secondary';
                
                const hasScreenshot = row.screenshot_url && row.screenshot_url.trim() !== '';
                let screenshotCol = hasScreenshot 
                    ? `<button class="btn btn-sm btn-outline-info rounded-pill" onclick="viewScreenshot('../${row.screenshot_url}')"><i class="fa-solid fa-eye me-1"></i> View</button>`
                    : `<span class="text-muted small">No Proof</span>`;
                
                let buttonsHtml = '';
                const isPendingUpload = hasScreenshot && (row.status === 'Pending' || row.status === 'Remaining');
                if (isPendingUpload) {
                    buttonsHtml += `<button class="btn btn-sm btn-success rounded-pill px-3 m-1 fw-bold" onclick="approvePayment(${row.id}, 'Approved')">Approval</button>`;
                }
                if (pending > 0 && !isPendingUpload) {
                    buttonsHtml += `
                        <button class="btn btn-sm btn-primary rounded-pill px-3 m-1 fw-bold" onclick="openAmountModal(${row.id}, 'Online Paid')">Online</button>
                        <button class="btn btn-sm btn-secondary rounded-pill px-3 m-1 fw-bold" onclick="openAmountModal(${row.id}, 'Cash Paid')">Cash</button>
                    `;
                }

                html += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">${srNo}</td>
                        <td>
                            <div class="fw-bold text-dark">${row.user_name}</div>
                            <small class="text-muted">${new Date(row.created_at).toLocaleString()}</small>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-light border text-dark fw-bold rounded-pill" 
                                    onclick="showDeliveryDetails(${row.user_id}, '${row.payment_month}', '${monthName}', '${row.user_name.replace(/'/g, "\\'")}')">
                                <i class="fa-solid fa-list-check me-1"></i> ${monthName}
                            </button>
                        </td>
                        <td><span class="badge bg-success fs-6">₹${calculatedBillVal.toFixed(2)}</span></td>
                        <td>₹${parseFloat(row.plan_amount || 0).toFixed(2)}</td>
                        <td>
                            <a href="#" onclick="viewPaymentDetails(${row.id}, '${row.created_at}', ${row.submitted_amount}, '${row.status}'); return false;" class="text-primary fw-bold text-decoration-underline">
                                ₹${parseFloat(row.submitted_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}
                            </a>
                        </td>
                        <td><div class="small text-success fw-bold">₹${totalPaid.toLocaleString('en-IN', {minimumFractionDigits: 2})}</div></td>
                        <td class="text-danger fw-bold">₹${pending.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                        <td>${screenshotCol}</td>
                        <td><span class="badge bg-${bgBadge} px-2 py-1 rounded-pill">${row.status}</span></td>
                        <td class="text-end pe-4">${buttonsHtml || '<span class="text-muted small">Processed</span>'}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    })
    .catch(err => {
        console.error('Error loading data:', err);
        if(!isPoll) tbody.innerHTML = '<tr><td colspan="11" class="text-center py-5 text-danger">Failed to load data. Please check connection or console for errors.</td></tr>';
    });
}

function loadCounterPayments(isPoll = false) {
    const tbody = document.getElementById('counterPaymentsTableBody');
    const totalEl = document.getElementById('counterTotalAmount');
    const month = document.getElementById('monthFilter').value;

    fetch(`api/bill_payments.php?action=fetch_counter_payments&month=${month}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastCounterData === currentDataStr) return;
            lastCounterData = currentDataStr;

            totalEl.innerText = `₹${parseFloat(res.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;

            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No counter payments found.</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach((row, index) => {
                let badge = row.payment_type === 'Online' ? 'info' : 'secondary';
                html += `
                    <tr>
                        <td class="ps-4 text-start fw-bold text-muted">${index + 1}</td>
                        <td class="text-start fw-bold text-dark">${row.user_name}</td>
                        <td>${new Date(row.created_at).toLocaleDateString('en-GB')}</td>
                        <td class="fw-bold text-primary">₹${parseFloat(row.amount).toFixed(2)}</td>
                        <td><span class="badge bg-${badge}">${row.payment_type}</span></td>
                        <td><span class="badge bg-success rounded-pill">Paid</span></td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    })
    .catch(err => {
        console.error('Error loading counter data:', err);
        if(!isPoll) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-danger">Failed to load records. Please check connection or console for errors.</td></tr>';
    });
}

function fetchUsers() {
    fetch('api/bill_payments.php?action=fetch_all_users')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const select = document.getElementById('counterUserSelect');
            res.data.forEach(u => {
                select.innerHTML += `<option value="${u.id}">${u.full_name} (${u.mobile})</option>`;
            });
        }
    });
}

function openAddCounterModal() {
    const modal = new bootstrap.Modal(document.getElementById('addCounterModal'));
    modal.show();
}

function submitCounterPayment() {
    const form = document.getElementById('counterPaymentForm');
    const fd = new FormData(form);
    fd.append('action', 'create_counter_payment');

    fetch('api/bill_payments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            Swal.fire({ icon: 'success', title: 'Payment Added', text: res.message, timer: 1500, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('addCounterModal')).hide();
            form.reset();
            $('.select2-user').val(null).trigger('change');
            loadCounterPayments();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    });
}

function viewScreenshot(url) {
    document.getElementById('modalScreenshotImg').src = url;
    new bootstrap.Modal(document.getElementById('screenshotModal')).show();
}

function viewPaymentDetails(id, date, amount, status) {
    const tbody = document.getElementById('paymentDetailsTbody');
    tbody.innerHTML = '<tr><td colspan="3" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div></td></tr>';
    
    new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
    
    fetch(`api/bill_payments.php?action=fetch_history&payment_id=${id}`)
    .then(r => r.json())
    .then(res => {
        if(res.success && res.data.length > 0) {
            let html = '';
            res.data.forEach(r => {
                html += `<tr><td>${r.formatted_date}</td><td class="fw-bold text-primary">₹${parseFloat(r.amount).toFixed(2)}</td><td><span class="badge bg-primary">${r.payment_type}</span></td></tr>`;
            });
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">No history found</td></tr>';
        }
    });
}

function showDeliveryDetails(userId, month, monthLabel, userName) {
    const tbody = document.getElementById('deliveryDetailsTbody');
    const loader = document.getElementById('deliveryDetailsLoader');
    document.getElementById('deliveryDetailsTitle').innerText = `History: ${userName} (${monthLabel})`;
    tbody.innerHTML = '';
    loader.classList.remove('d-none');
    new bootstrap.Modal(document.getElementById('deliveryDetailsModal')).show();

    fetch(`api/bill_payments.php?action=fetch_delivery_details&user_id=${userId}&month=${month}`)
    .then(r => r.json())
    .then(res => {
        loader.classList.add('d-none');
        if (res.success) {
            let html = '';
            res.data.forEach(item => {
                const date = new Date(item.delivery_date).toLocaleDateString('en-GB');
                html += `<tr><td class="ps-4">${date}</td><td class="text-center">${item.quantity}</td><td class="text-center">${item.return_can_count}</td><td class="text-center">${item.can_received=='1'?'✅':'❌'}</td><td class="text-center">${item.status}</td></tr>`;
            });
            tbody.innerHTML = html || '<tr><td colspan="5" class="text-center">No data</td></tr>';
        }
    });
}

function openAmountModal(id, method) {
    document.getElementById('paymentRowId').value = id;
    document.getElementById('paymentMethod').value = method;
    document.getElementById('paymentAmountInput').value = '';
    document.getElementById('amountModalTitle').innerText = `Record ${method === 'Cash Paid' ? 'Cash' : 'Online'} Payment`;
    new bootstrap.Modal(document.getElementById('amountModal')).show();
}

function submitAmount() {
    const id = document.getElementById('paymentRowId').value;
    const status = document.getElementById('paymentMethod').value;
    const amount = document.getElementById('paymentAmountInput').value;
    const fd = new FormData();
    fd.append('action', 'mark_paid');
    fd.append('payment_id', id);
    fd.append('status', status);
    fd.append('amount', amount);

    fetch('api/bill_payments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Saved', text: res.message, timer: 1500, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('amountModal')).hide();
            loadMonthlyBills();
        }
    });
}

function approvePayment(id, status) {
    Swal.fire({ title: 'Approve Payment?', icon: 'question', showCancelButton: true }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'approve');
            fd.append('payment_id', id);
            fd.append('status', status);
            fetch('api/bill_payments.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'Approved', timer: 1500 });
                    loadMonthlyBills();
                }
            });
        }
    });
}
</script>
