<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark">Order History</h3>
    <button class="btn btn-outline-secondary" onclick="exportOrders()"><i class="fa-solid fa-download me-2"></i> Export</button>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="orderTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab" onclick="loadorders('all')">All Orders</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="today-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab" onclick="loadorders('today')">Today's Orders</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="delivered-tab" data-bs-toggle="tab" data-bs-target="#orders-pane" type="button" role="tab" onclick="loadorders('delivered')">Delivered Orders</button>
    </li>
</ul>

<!-- Filter & Table Container -->
<div class="tab-content" id="orderTabContent">
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
                    <div class="col-md-5 d-none" id="dateFilterContainer">
                        <div class="input-group">
                            <span class="input-group-text bg-white">From</span>
                            <input type="date" id="startDate" class="form-control" onchange="reloadData()">
                            <span class="input-group-text bg-white">To</span>
                            <input type="date" id="endDate" class="form-control" onchange="reloadData()">
                        </div>
                    </div>
                    <div class="col-md-2 ms-auto">
                        <button class="btn btn-light border w-100" onclick="reloadData()"><i class="fa-solid fa-rotate-right me-2"></i> Refresh</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card card-custom">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light" id="ordersTableHead">
                            <tr>
                                <th class="ps-4">Order ID</th>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody">
                            <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Assign Delivery Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Assign Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="assignOrderId">
                <div class="mb-3">
                    <label class="form-label">Select Delivery Partner</label>
                    <select id="deliveryBoySelect" class="form-select">
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div class="alert alert-info small">
                    <i class="fa-solid fa-info-circle me-1"></i> 
                    Assigning will notify the delivery partner immediately.
                </div>
                <button type="button" class="btn btn-primary w-100" onclick="submitAssignment()">Assign Order</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentTab = 'all';

document.addEventListener('DOMContentLoaded', () => {
    loadorders('all');
    fetchDeliveryBoys();

    // Debounce Search
    let timer;
    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(timer);
        timer = setTimeout(reloadData, 500);
    });
});

function loadorders(type) {
    currentTab = type;
    const search = document.getElementById('searchInput').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const tbody = document.getElementById('ordersTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

    const thead = document.getElementById('ordersTableHead');
    const dateFilter = document.getElementById('dateFilterContainer');
    
    // Update Headers based on Tab
    if (type === 'delivered') {
        dateFilter.classList.remove('d-none');
        thead.innerHTML = `
            <tr>
                <th class="ps-4">Order ID</th>
                <th>Customer Name</th>
                <th>Date & Time of Delivery</th>
                <th>Delivery Boy Name</th>
                <th>Status</th>
            </tr>
        `;
    } else {
        dateFilter.classList.add('d-none');
        thead.innerHTML = `
            <tr>
                <th class="ps-4">Order ID</th>
                <th>Customer</th>
                <th>Date & Time</th>
                <th>Amount</th>
                <th>Status</th>
                <th class="text-end pe-4">Actions</th>
            </tr>
        `;
    }

    fetch(`api/orders.php?action=fetch_orders&type=${type}&search=${encodeURIComponent(search)}&start_date=${startDate}&end_date=${endDate}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            tbody.innerHTML = '';
            let colSpan = type === 'delivered' ? 5 : 6;
            
            if(res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center py-4 text-muted">No orders found.</td></tr>`;
                return;
            }

            res.data.forEach(o => {
                let actionBtn = '';
                let statusBadge = '';

                // Status Logic
                switch(o.status) {
                    case 'Pending':
                        statusBadge = '<span class="badge bg-warning text-dark">Pending</span>';
                        actionBtn = `<button class="btn btn-sm btn-outline-success" onclick="acceptOrder(${o.id})"><i class="fa-solid fa-check me-1"></i> Accept</button>`;
                        break;
                    case 'Accepted':
                        statusBadge = '<span class="badge bg-primary">Accepted</span>';
                        actionBtn = `<button class="btn btn-sm btn-primary" onclick="openAssignModal(${o.id})"><i class="fa-solid fa-motorcycle me-1"></i> Assign</button>`;
                        break;
                    case 'Assigned':
                        statusBadge = '<span class="badge bg-info">Assigned</span>';
                        actionBtn = `
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <span class="badge bg-light text-dark border fw-normal">
                                    <i class="fa-solid fa-user me-1"></i> ${o.delivery_boy_name || 'Rider'}
                                </span>
                                <button class="btn btn-sm btn-outline-primary" onclick="openAssignModal(${o.id})" title="Change Rider">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </div>`;
                        break;
                    case 'Delivered':
                        statusBadge = '<span class="badge bg-success">Delivered</span>';
                        actionBtn = '<button class="btn btn-sm btn-light border" disabled>Completed</button>';
                        break;
                    case 'Cancelled':
                        statusBadge = '<span class="badge bg-danger">Cancelled</span>';
                        actionBtn = '';
                        break;
                }

                const date = new Date(o.created_at).toLocaleString();
                const deliveryDate = o.delivered_at ? new Date(o.delivered_at).toLocaleString() : 'N/A';

                if (currentTab === 'delivered') {
                    tbody.innerHTML += `
                        <tr>
                            <td class="ps-4 fw-bold">#${o.id}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">${o.full_name}</span>
                                    <small class="text-muted" style="font-size: 0.75rem;">${o.mobile}</small>
                                </div>
                            </td>
                            <td><small class="text-muted">${deliveryDate}</small></td>
                            <td><span class="badge bg-light text-dark border"><i class="fa-solid fa-user me-1"></i> ${o.delivery_boy_name || 'Unknown'}</span></td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                } else {
                    tbody.innerHTML += `
                        <tr>
                            <td class="ps-4 fw-bold">#${o.id}</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium">${o.full_name}</span>
                                    <small class="text-muted" style="font-size: 0.75rem;">${o.mobile}</small>
                                </div>
                            </td>
                            <td><small class="text-muted">${date}</small></td>
                            <td class="fw-bold text-dark">₹${o.total_amount}</td>
                            <td>${statusBadge}</td>
                            <td class="text-end pe-4">${actionBtn}</td>
                        </tr>
                    `;
                }
            });
        }
    });
}

function reloadData() {
    loadorders(currentTab);
}

function acceptOrder(id) {
    if(confirm('Accept this order?')) {
        const fd = new FormData();
        fd.append('action', 'accept_order');
        fd.append('order_id', id);

        fetch('api/orders.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                reloadData();
            } else {
                alert(res.message);
            }
        });
    }
}

function fetchDeliveryBoys() {
    fetch('api/orders.php?action=fetch_delivery_boys')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const select = document.getElementById('deliveryBoySelect');
            select.innerHTML = '<option value="">Select Rider...</option>';
            res.data.forEach(db => {
                select.innerHTML += `<option value="${db.id}">${db.full_name}</option>`;
            });
        }
    });
}

function openAssignModal(orderId) {
    document.getElementById('assignOrderId').value = orderId;
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

function submitAssignment() {
    const orderId = document.getElementById('assignOrderId').value;
    const boyId = document.getElementById('deliveryBoySelect').value;

    if(!boyId) {
        alert('Please select a delivery partner.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'assign_order');
    fd.append('order_id', orderId);
    fd.append('delivery_boy_id', boyId);

    fetch('api/orders.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignModal'));
            modal.hide();
            reloadData();
            alert('Order assigned successfully!');
        } else {
            alert(res.message);
        }
    });
}

function exportOrders() {
    alert('Export feature coming soon!');
}
</script>

<?php include 'includes/footer.php'; ?>
