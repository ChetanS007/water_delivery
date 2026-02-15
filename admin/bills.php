<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark">Customer Billing</h3>
    <button class="btn btn-outline-secondary" onclick="exportBills()"><i class="fa-solid fa-download me-2"></i> Export</button>
</div>

<div class="card card-custom mb-3">
    <div class="card-body py-3">
        <div class="row g-3">
            <div class="col-md-3">
                <select id="customerFilter" class="form-select" onchange="loadBills()">
                    <option value="">All Customers</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" id="startDate" class="form-control" onchange="loadBills()">
            </div>
            <div class="col-md-3">
                <input type="date" id="endDate" class="form-control" onchange="loadBills()">
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">Reset Filters</button>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Sub ID</th>
                        <th>Customer Name</th>
                        <th>Subscription Start Date</th>
                        <th>Total Bill (Calculated)</th>
                        <th>Plan Amount (Fixed)</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="billTableBody">
                    <tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bill Details Modal -->
<div class="modal fade" id="billDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Billing Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="billDetailsContent">
                    <p class="text-center py-3">Loading details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadCustomers();
    loadBills();
});

function loadCustomers() {
    fetch('api/bills.php?action=fetch_customers')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const sel = document.getElementById('customerFilter');
            res.data.forEach(c => {
                sel.innerHTML += `<option value="${c.id}">${c.full_name}</option>`;
            });
        }
    });
}

function loadBills() {
    const tbody = document.getElementById('billTableBody');
    const customerId = document.getElementById('customerFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    fetch(`api/bills.php?action=fetch_bills&customer_id=${customerId}&start_date=${startDate}&end_date=${endDate}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No Record Found</td></tr>';
                return;
            }

            let html = '';
            res.data.forEach(bill => {
                const startDate = new Date(bill.start_date).toLocaleDateString();
                const totalBill = parseFloat(bill.calculated_bill).toFixed(2);
                const planAmount = parseFloat(bill.plan_amount).toFixed(2);
                
                const statusBadge = bill.payment_status === 'Paid' ? 'success' : 'warning';
                
                html += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#${bill.sub_id}</td>
                        <td class="fw-medium">${bill.customer_name}</td>
                        <td>${startDate}</td>
                        <td><span class="badge bg-success fs-6">₹${totalBill}</span> <small class="text-muted d-block">${bill.delivered_count} deliveries</small></td>
                        <td>₹${planAmount}</td>
                        <td><span class="badge bg-${statusBadge}">${bill.payment_status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewBillDetails(${bill.sub_id})">
                                <i class="fa-solid fa-eye me-1"></i> View
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        } else {
             tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${res.message}</td></tr>`;
        }
    });
}

function resetFilters() {
    document.getElementById('customerFilter').value = '';
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    loadBills();
}

function viewBillDetails(id) {
    const modalEl = document.getElementById('billDetailsModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    const content = document.getElementById('billDetailsContent');
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading details...</p></div>';

    fetch(`api/bills.php?action=fetch_details&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            if(res.data.length === 0) {
                content.innerHTML = '<p class="text-center py-4 text-muted">No delivered items found for this subscription yet.</p>';
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Cost</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            let total = 0;
            res.data.forEach(item => {
                const date = new Date(item.delivery_date).toLocaleDateString();
                const cost = parseFloat(item.cost); // Ensure number
                const price = parseFloat(item.price);
                total += cost;
                
                html += `
                    <tr>
                        <td>${date}</td>
                        <td>${item.product_name}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td>₹${price.toFixed(2)}</td>
                        <td class="fw-bold">₹${cost.toFixed(2)}</td>
                    </tr>
                `;
            });
            
            html += `
                        <tr class="table-light fw-bold">
                            <td colspan="4" class="text-end">Total Accrued:</td>
                            <td>₹${total.toFixed(2)}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            `;
            content.innerHTML = html;
        } else {
            content.innerHTML = `<p class="text-danger text-center py-3">${res.message}</p>`;
        }
    })
    .catch(err => {
        content.innerHTML = `<p class="text-danger text-center py-3">Failed to load details.</p>`;
    });
}

function exportBills() {
    alert('Export feature coming soon!');
}
</script>

<?php include 'includes/footer.php'; ?>
