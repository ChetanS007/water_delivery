<?php
// subscriptions.php - Subscription Management
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Subscription Requests</h3>
        <p class="text-muted small mb-0">Review requests and assign delivery partners</p>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <select class="form-select" id="statusFilter" onchange="loadSubscriptions(1)">
                    <option value="">All Statuses</option>
                    <option value="Pending" selected>Pending Requests</option>
                    <option value="Approved">Approved</option>
                    <option value="Assigned">Assigned</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark w-100" onclick="loadSubscriptions(1)">Refresh</button>
            </div>
        </div>
    </div>
</div>

<!-- Subscriptions Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="subsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Customer</th>
                        <th>Plan & Product</th>
                        <th>Actions</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="subsTableBody">
                    <tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 py-3">
        <nav>
            <ul class="pagination justify-content-end mb-0" id="pagination"></ul>
        </nav>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Assign Delivery Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignForm">
                    <input type="hidden" id="assignOrderId" name="order_id">
                    <div class="mb-3">
                        <label class="form-label">Select Delivery Partner</label>
                        <select class="form-select" name="delivery_boy_id" id="deliveryBoySelect" required>
                            <option value="">Loading...</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary w-100" onclick="submitAssignment()">Assign</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadSubscriptions();
    loadDeliveryBoys();
    // Poll every 10 seconds
    setInterval(() => {
        loadSubscriptions(1, true);
    }, 10000);
});

let lastSubData = null;

function loadSubscriptions(page = 1, isPoll = false) {
    const status = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('subsTableBody');
    
    // Only show spinner on manual load
    if (!isPoll) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
        lastSubData = null;
    }

    fetch(`api/subscriptions.php?action=fetch_all&page=${page}&status=${encodeURIComponent(status)}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            
            const currentDataStr = JSON.stringify(res.data);
            if (lastSubData === currentDataStr) {
                return; // No changes
            }
            lastSubData = currentDataStr;

            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No requests found.</td></tr>';
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            let html = '';
            res.data.forEach(sub => {
                let actions = '';
                if(sub.status === 'Pending') {
                    actions = `
                        <button class="btn btn-sm btn-success me-1" onclick="approve(${sub.id})">Accept</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="reject(${sub.id})">Reject</button>
                    `;
                } else if(sub.status === 'Approved') {
                    actions = `<button class="btn btn-sm btn-primary" onclick="openAssign(${sub.id})">Assign Delivery Boy</button>`;
                } else if(sub.status === 'Assigned') {
                    actions = `<small class="text-muted">Assigned to: <strong>${sub.delivery_boy_name}</strong></small> 
                               <button class="btn btn-sm btn-link p-0 ms-2" onclick="openAssign(${sub.id})"><i class="fa-solid fa-pen"></i></button>`;
                }

                const statusBadge = `<span class="badge bg-${getStatusColor(sub.status)}">${sub.status}</span>`;

                html += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#${sub.id}</td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-medium">${sub.user_name}</span>
                                <small class="text-muted">${sub.mobile}</small>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-medium text-dark">${sub.product_name}</span>
                                <small class="text-muted">${sub.order_type} (${sub.custom_days ? 'Custom' : 'Standard'})</small>
                            </div>
                        </td>
                        <td>${actions}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }
    });
}

function getStatusColor(status) {
    switch(status) {
        case 'Pending': return 'warning';
        case 'Approved': return 'info';
        case 'Assigned': return 'success';
        case 'Rejected': return 'danger';
        default: return 'secondary';
    }
}

function approve(id) {
    if(confirm('Accept this subscription request?')) {
        const fd = new FormData(); fd.append('action', 'approve'); fd.append('id', id);
        fetch('api/subscriptions.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) loadSubscriptions();
            else alert(res.message);
        });
    }
}

function reject(id) {
    if(confirm('Reject this request?')) {
        const fd = new FormData(); fd.append('action', 'reject'); fd.append('id', id);
        fetch('api/subscriptions.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) loadSubscriptions();
            else alert(res.message);
        });
    }
}

let allDeliveryBoys = [];

function loadDeliveryBoys() {
    fetch('api/subscriptions.php?action=fetch_delivery_boys')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            allDeliveryBoys = res.data;
            renderDeliveryBoyOptions();
        }
    });
}

function renderDeliveryBoyOptions(custLat = null, custLng = null) {
    const sel = document.getElementById('deliveryBoySelect');
    sel.innerHTML = '<option value="">Select Partner</option>';
    
    let boys = [...allDeliveryBoys];
    
    if (custLat && custLng) {
        boys.forEach(b => {
            if (b.current_lat && b.current_lng) {
                b.distance = getDistance(custLat, custLng, b.current_lat, b.current_lng);
            } else {
                b.distance = Infinity;
            }
        });
        boys.sort((a, b) => a.distance - b.distance);
    }

    boys.forEach(boy => {
        let distText = '';
        if (boy.distance !== undefined && boy.distance !== Infinity) {
            distText = ` (${boy.distance.toFixed(1)} km away)`;
        }
        sel.innerHTML += `<option value="${boy.id}">${boy.full_name}${distText}</option>`;
    });
}

function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; 
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
              Math.sin(dLon/2) * Math.sin(dLon/2); 
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
    return R * c;
}

function openAssign(id) {
    document.getElementById('assignOrderId').value = id;
    
    // Find subscription data to get customer location
    const subData = JSON.parse(lastSubData);
    const sub = subData.find(s => s.id == id);
    
    if (sub && sub.latitude && sub.longitude) {
        renderDeliveryBoyOptions(parseFloat(sub.latitude), parseFloat(sub.longitude));
    } else {
        renderDeliveryBoyOptions();
    }
    
    new bootstrap.Modal(document.getElementById('assignModal')).show();
}

function submitAssignment() {
    const fd = new FormData(document.getElementById('assignForm'));
    fd.append('action', 'assign');
    
    fetch('api/subscriptions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide();
            loadSubscriptions();
            alert(res.message);
        } else {
            alert(res.message);
        }
    });
}
</script>
<?php include 'includes/footer.php'; ?>
