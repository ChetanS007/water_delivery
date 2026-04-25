<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark">Order History & Dispatch</h3>
    <button class="btn btn-outline-secondary" onclick="exportOrders()"><i class="fa-solid fa-download me-2"></i> Export</button>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="orderTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="todays-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab" onclick="loadData('fetch_todays_deliveries')">Today's Dispatch</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="van-tab" data-bs-toggle="tab" data-bs-target="#van-pane" type="button" role="tab" onclick="loadVanData()">Delivery Van Details</button>
    </li>
</ul>

<!-- Filter & Table Container -->
<div class="tab-content" id="orderTabContent">
    <!-- Today's Dispatch Pane -->
    <div class="tab-pane fade show active" id="orders-pane" role="tabpanel">
        
        <!-- Filter Bar -->
        <div class="card card-custom mb-3">
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search customer, ID...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card card-custom">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light" id="tableHead">
                            <!-- Dynamic Header -->
                        </thead>
                        <tbody id="tableBody">
                            <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Delivery Van Details Pane -->
    <div class="tab-pane fade" id="van-pane" role="tabpanel">
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-primary" onclick="openAddVanModal()"><i class="fa-solid fa-plus me-2"></i> Add Van Dispatch</button>
        </div>
        <div class="card card-custom">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Van ID</th>
                                <th>Delivery Boy</th>
                                <th>Quantity (Cans)</th>
                                <th>Out Time</th>
                                <th>In Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="vanTableBody">
                            <tr><td colspan="6" class="text-center py-4 text-muted">No Record Found</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Van Modal -->
<div class="modal fade" id="addVanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dispatch New Van</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vanForm">
                    <div class="mb-3">
                        <label class="form-label">Van Number / ID</label>
                        <input type="text" class="form-control" name="van_id" required placeholder="e.g. VAN-01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Partner</label>
                        <select class="form-select" name="boy_id" id="vanBoySelect" required>
                            <option value="">Select Partner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity (Cans)</label>
                        <input type="number" class="form-control" name="quantity" required min="1" value="50">
                    </div>
                    <button type="button" class="btn btn-primary w-100" onclick="submitVanDispatch()">Dispatch</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentAction = 'fetch_todays_deliveries';
let lastData = null;
let lastVanData = null;

document.addEventListener('DOMContentLoaded', () => {
    loadData('fetch_todays_deliveries');
    
    // Search Listener
    document.getElementById('searchInput').addEventListener('input', function() {
        const q = this.value.trim();
        if(q.length > 0) {
            loadData('search_all_customers', false, q);
        } else {
            loadData('fetch_todays_deliveries');
        }
    });

    // Poll for updates every 5 seconds
    setInterval(() => {
        if(document.getElementById('orders-pane').classList.contains('active')) {
             const searchVal = document.getElementById('searchInput').value.trim();
             if(document.activeElement.id !== 'searchInput' && searchVal === '') {
                 loadData('fetch_todays_deliveries', true);
             }
        } else if(document.getElementById('van-pane').classList.contains('active')) {
             loadVanData(true);
        }
    }, 5000);
});

// --- Order Management Functions ---

function loadData(action, isPoll = false, search = '') {
    currentAction = action;
    const tbody = document.getElementById('tableBody');
    const thead = document.getElementById('tableHead');
    
    if (!isPoll) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
        lastData = null; 
    }

    // Set Header
    thead.innerHTML = `
        <tr>
            <th class="ps-4">Sr. No.</th>
            <th>Customer</th>
            <th>Product & Plan</th>
            <th>Assigned To</th>
            <th>Status</th>
            <th>Delivery Date</th>
            <th>Action</th>
        </tr>
    `;

    fetch(`api/orders.php?action=${action}&search=${encodeURIComponent(search)}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastData === currentDataStr) return;
            lastData = currentDataStr;

            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No records found.</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach((item, index) => {
                let srNo = index + 1;
                const statusClass = item.today_status === 'Delivered' ? 'success' : (item.today_status === 'Missed' ? 'danger' : 'warning');
                
                let boyDisplay = '<span class="text-danger small">Unassigned</span>';
                if (item.delivery_boy_name) {
                    boyDisplay = `<span class="fw-bold text-dark small">${item.delivery_boy_name}</span>`;
                }

                let dateStr = '-';
                if (item.today_status === 'Delivered' && item.delivered_at) {
                    dateStr = new Date(item.delivered_at).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' });
                }
                
                let actionBtn = '';
                if (item.today_status !== 'Delivered') {
                    actionBtn = `<button class="btn btn-sm btn-success py-1 px-2" onclick="markAsDelivered(${item.sub_id})"><i class="fa-solid fa-check me-1"></i> Delivered</button>`;
                } else {
                    actionBtn = `<span class="badge bg-light text-success border"><i class="fa-solid fa-circle-check me-1"></i> Done</span>`;
                }

                html += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">${srNo}</td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-medium">${item.customer_name}</span>
                                <small class="text-muted" style="font-size: 0.75rem;">${item.mobile}</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border me-1" style="font-size: 0.7rem;">${item.order_type}</span>
                            <small class="d-block text-muted" style="font-size: 0.7rem;">${item.custom_days ? JSON.parse(item.custom_days).join(', ') : ''}</small>
                        </td>
                        <td>${boyDisplay}</td>
                        <td><span class="badge bg-${statusClass}" style="font-size: 0.7rem;">${item.today_status}</span></td>
                        <td><small>${dateStr}</small></td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${res.message}</td></tr>`;
        }
    });
}

function markAsDelivered(subId) {
    Swal.fire({
        title: 'Mark as Delivered?',
        text: "Confirm delivery for this customer today.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delivered'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'mark_delivered');
            fd.append('sub_id', subId);
            fetch('api/orders.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    let searchVal = document.getElementById('searchInput').value.trim();
                    if(searchVal) {
                        loadData('search_all_customers', false, searchVal);
                    } else {
                        loadData('fetch_todays_deliveries');
                    }
                    Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            });
        }
    });
}


// --- Van Management Functions ---

function loadVanData(isPoll = false) {
    const tbody = document.getElementById('vanTableBody');
    const tableHead = document.querySelector('#van-pane table thead');

    // Ensure header matches new columns
    if(tableHead) {
        tableHead.innerHTML = `
            <tr>
                <th class="ps-4">Van ID</th>
                <th>Delivery Boy</th>
                <th>Total Cans</th>
                <th>Delivered</th>
                <th>Remaining</th>
                <th>Out Time</th>
                <th>In Time</th>
                <th>Action</th>
            </tr>
        `;
    }

    fetch('api/van_management.php?action=fetch_logs')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastVanData === currentDataStr) return;
            lastVanData = currentDataStr;

            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No Record Found</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach(van => {
                const outTime = van.out_time ? new Date(van.out_time).toLocaleString() : '-';
                const inTime = van.in_time ? new Date(van.in_time).toLocaleString() : '-';
                
                // Calculations
                const delivered = parseInt(van.delivered_count) || 0;
                const total = parseInt(van.quantity) || 0;
                const remaining = total - delivered;

                let actionBtn = '';
                
                if (van.status === 'Pending') {
                    // Pending -> Show Out Button
                    actionBtn = `<button class="btn btn-sm btn-info text-white fw-bold" onclick="markVanOut(${van.id})">Out</button>`;
                } else if (van.status === 'Out') {
                    // Out -> Show In Button
                    actionBtn = `<button class="btn btn-sm btn-warning fw-bold" onclick="markVanIn(${van.id})">In</button>`;
                } else {
                    // In -> Completed
                    actionBtn = `<span class="badge bg-success">Completed</span>`;
                }
                
                html += `
                    <tr>
                        <td class="ps-4 fw-bold">${van.van_id}</td>
                        <td>${van.boy_name || 'Unknown'}</td>
                        <td>${total}</td>
                        <td><span class="badge bg-success">${delivered}</span></td>
                        <td><span class="badge bg-warning text-dark">${remaining}</span></td>
                        <td>${outTime}</td>
                        <td>${inTime}</td>
                        <td>${actionBtn}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    });
}

function openAddVanModal() {
    // Load delivery boys first
    fetch('api/subscriptions.php?action=fetch_delivery_boys')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const sel = document.getElementById('vanBoySelect');
            sel.innerHTML = '<option value="">Select Partner</option>';
            res.data.forEach(boy => {
                sel.innerHTML += `<option value="${boy.id}">${boy.full_name}</option>`;
            });
            new bootstrap.Modal(document.getElementById('addVanModal')).show();
        }
    });
}

function submitVanDispatch() {
    const fd = new FormData(document.getElementById('vanForm'));
    fd.append('action', 'add_van');
    fetch('api/van_management.php?action=add_van', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('addVanModal')).hide();
            lastVanData = null; 
            loadVanData();
            document.getElementById('vanForm').reset();
            Swal.fire({ icon: 'success', title: 'Dispatched!', text: 'Van has been dispatched.', timer: 2000, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Error adding van' });
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'System Error', text: 'Could not add van.' });
    });
}

function markVanOut(id) {
    Swal.fire({
        title: 'Dispatch Van?',
        text: "Mark van as Dispatched (Out)?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Out'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'mark_out');
            fd.append('id', id);
            fetch('api/van_management.php?action=mark_out', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    lastVanData = null; 
                    loadVanData();
                    Swal.fire('Dispatched!', 'Van status updated to OUT.', 'success');
                }
            });
        }
    });
}

function markVanIn(id) {
    Swal.fire({
        title: 'Return Van?',
        text: "Mark van as Returned (In)?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, In'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'mark_in');
            fd.append('id', id);
            fetch('api/van_management.php?action=mark_in', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    lastVanData = null; 
                    loadVanData();
                    Swal.fire('Returned!', 'Van status updated to IN.', 'success');
                }
            });
        }
    });
}

function reloadData() {
    loadData(currentAction);
}

function exportOrders() {
    Swal.fire({
        icon: 'info',
        title: 'Coming Soon',
        text: 'Export feature coming soon!'
    });
}
</script>

<?php include 'includes/footer.php'; ?>
