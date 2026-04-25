<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Event Bookings</h3>
        <p class="text-muted small mb-0">Manage customer event water supply requests</p>
    </div>
</div>

<!-- Search & Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search events...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Customer Name</th>
                        <th>Mobile</th>
                        <th>Event Date</th>
                        <th>Order Details</th>
                        <th>Can Qty</th>
                        <th>Location</th>
                        <th>Delivery Date</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody id="eventTableBody">
                    <tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-end mt-3">
            <nav><ul class="pagination pagination-sm" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<!-- Book Now Modal -->
<div class="modal fade" id="bookNowModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">Add Order Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="bookNowForm">
                    <input type="hidden" name="action" value="book_now">
                    <input type="hidden" name="id" id="bookingId">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Can Quantity</label>
                        <input type="number" name="can_quantity" id="canQuantity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location Name</label>
                        <input type="text" name="location_name" id="locationName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Delivery Date</label>
                        <input type="date" name="delivery_date" id="deliveryDate" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Save Details</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark">Customer Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <label class="text-muted small text-uppercase fw-bold d-block mb-1">Customer Info</label>
                    <div class="bg-light p-3 rounded-3">
                        <h5 class="fw-bold mb-1" id="viewName">-</h5>
                        <p class="text-primary mb-0 fw-bold" id="viewMobile">-</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="text-muted small text-uppercase fw-bold d-block mb-1">Customer Request Details</label>
                    <div class="border p-3 rounded-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Event Date:</span>
                            <span class="fw-bold text-dark" id="viewEventDate">-</span>
                        </div>
                        <div class="border-top pt-2">
                            <span class="text-muted d-block mb-1">Items Description:</span>
                            <p class="mb-0 text-dark" id="viewOrderDetails" style="white-space: pre-line;">-</p>
                        </div>
                    </div>
                </div>

                <div id="adminDetailsArea" class="d-none">
                    <label class="text-muted small text-uppercase fw-bold d-block mb-1 text-success">Admin Order Details</label>
                    <div class="bg-success bg-opacity-10 border border-success border-opacity-25 p-3 rounded-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success small fw-bold">Can Quantity:</span>
                            <span class="fw-bold text-dark" id="viewCanQuantity">-</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success small fw-bold">Location:</span>
                            <span class="fw-bold text-dark" id="viewLocationName">-</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-success small fw-bold">Planned Delivery:</span>
                            <span class="fw-bold text-dark" id="viewPlannedDelivery">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let lastEventData = null;

document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
    
    // Polling
    setInterval(() => {
        if (!document.querySelector('.modal.show')) {
            loadEvents(1, true);
        }
    }, 15000);
    
    // Search Debounce
    let timer;
    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadEvents(1), 500);
    });

    // Form Submit
    document.getElementById('bookNowForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('api/events.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                bootstrap.Modal.getInstance(document.getElementById('bookNowModal')).hide();
                loadEvents();
                Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        });
    });
});

function loadEvents(page = 1, isPoll = false) {
    const search = document.getElementById('searchInput').value;
    const tbody = document.getElementById('eventTableBody');
    
    if(!isPoll) tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

    fetch(`api/events.php?action=fetch_all&page=${page}&search=${encodeURIComponent(search)}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            if (isPoll && JSON.stringify(res.data) === lastEventData) return;
            lastEventData = JSON.stringify(res.data);

            tbody.innerHTML = '';
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No event bookings found.</td></tr>';
                return;
            }

            res.data.forEach(e => {
                let status_badge = '';
                let action_btn = '';

                let eventDate = new Date(e.event_date).toLocaleDateString(undefined, {dateStyle:'medium'});
                let deliveryDate = e.delivery_date ? new Date(e.delivery_date).toLocaleDateString(undefined, {dateStyle:'medium'}) : '-';
                let canQty = e.can_quantity || '-';
                let location = e.location_name || '-';
                let orderDetailsSnippet = e.order_details ? (e.order_details.length > 30 ? e.order_details.substring(0, 30) + '...' : e.order_details) : '-';

                if(e.status === 'Pending') {
                    status_badge = '<span class="badge bg-warning-subtle text-warning">Pending</span>';
                    action_btn = `<button class="btn btn-primary btn-sm px-3 rounded-pill fw-bold" onclick="openBookNowModal(${e.id})">Book Now</button>`;
                } else if(e.status === 'Accepted') {
                    status_badge = '<span class="badge bg-info-subtle text-info">Booked</span>';
                    action_btn = `<button class="btn btn-success btn-sm px-3 rounded-pill fw-bold" onclick="markDelivered(${e.id})">Delivery</button>`;
                } else if(e.status === 'Delivered') {
                    status_badge = '<span class="badge bg-success-subtle text-success">Delivered</span>';
                    action_btn = `<span class="badge bg-success py-2 px-3 rounded-pill fw-bold">Delivered</span>`;
                }

                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4">
                            <a href="javascript:void(0)" class="fw-bold text-primary text-decoration-none" onclick="viewDetails(${e.id})">${e.name}</a>
                        </td>
                        <td class="text-muted font-monospace">${e.mobile}</td>
                        <td class="fw-medium text-dark">${eventDate}</td>
                        <td class="small text-muted" title="${e.order_details || ''}">${orderDetailsSnippet}</td>
                        <td class="fw-bold">${canQty}</td>
                        <td class="small">${location}</td>
                        <td class="small">${deliveryDate}</td>
                        <td>${status_badge}</td>
                        <td class="text-end pe-4">${action_btn}</td>
                    </tr>
                `;
            });

            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            for(let i=1; i<=res.pagination.pages; i++) {
                pagination.innerHTML += `<li class="page-item ${i === res.pagination.page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadEvents(${i})">${i}</a></li>`;
            }
        }
    });
}

function openBookNowModal(id) {
    document.getElementById('bookingId').value = id;
    document.getElementById('bookNowForm').reset();
    new bootstrap.Modal(document.getElementById('bookNowModal')).show();
}

function markDelivered(id) {
    Swal.fire({
        title: 'Confirm Delivery',
        text: "Mark this event supply as delivered?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Yes, Delivered!'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('action', 'mark_delivered');
            fd.append('id', id);
            fetch('api/events.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    loadEvents();
                    Swal.fire('Delivered!', res.message, 'success');
                }
            });
        }
    });
}

function viewDetails(id) {
    fetch(`api/events.php?action=fetch_one&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const d = res.data;
            document.getElementById('viewName').innerText = d.name;
            document.getElementById('viewMobile').innerText = d.mobile;
            document.getElementById('viewEventDate').innerText = new Date(d.event_date).toLocaleDateString(undefined, {dateStyle:'full'});
            document.getElementById('viewOrderDetails').innerText = d.order_details || 'No specific details provided.';
            
            if(d.status !== 'Pending') {
                document.getElementById('adminDetailsArea').classList.remove('d-none');
                document.getElementById('viewCanQuantity').innerText = d.can_quantity + ' Cans';
                document.getElementById('viewLocationName').innerText = d.location_name;
                document.getElementById('viewPlannedDelivery').innerText = new Date(d.delivery_date).toLocaleDateString();
            } else {
                document.getElementById('adminDetailsArea').classList.add('d-none');
            }
            
            new bootstrap.Modal(document.getElementById('viewDetailsModal')).show();
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
