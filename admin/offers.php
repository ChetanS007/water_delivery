<?php
// offers.php
?>
<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Offer Codes</h3>
        <p class="text-muted small mb-0">Manage discount codes for customers</p>
    </div>
    <button class="btn btn-primary shadow-sm" onclick="openModal()">
        <i class="fa-solid fa-plus me-2"></i> Create Offer
    </button>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Code</th>
                        <th>Discount Type</th>
                        <th>Value</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="offersTableBody">
                    <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="offerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Create Offer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="offerForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="offerId">

                    <div class="mb-3">
                        <label class="form-label">Offer Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="code" class="form-control text-uppercase" placeholder="e.g. SUMMER50" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Type</label>
                            <select name="discount_type" id="discountType" class="form-select">
                                <option value="Percentage">Percentage (%)</option>
                                <option value="Fixed">Fixed Amount (₹)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="discount_value" id="discountValue" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2" placeholder="Brief description visible to admins"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">Save Offer</button>
            </div>
        </div>
    </div>
</div>

<script>
let lastOfferData = null;
document.addEventListener('DOMContentLoaded', () => {
    loadOffers();

    // Polling
    setInterval(() => {
        const modal = document.getElementById('offerModal');
        const isModalOpen = modal && modal.classList.contains('show');
        if (!isModalOpen) {
            loadOffers(true);
        }
    }, 15000);
});

function loadOffers(isPoll = false) {
    const tbody = document.getElementById('offersTableBody');
    
    if(!isPoll) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
        lastOfferData = null;
    }

    fetch('api/offers.php?action=fetch_all')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastOfferData === currentDataStr) return;
            lastOfferData = currentDataStr;

            tbody.innerHTML = '';
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No offers found.</td></tr>';
                return;
            }

            res.data.forEach(item => {
                const status = item.status == 1 
                    ? '<span class="badge bg-success">Active</span>' 
                    : '<span class="badge bg-secondary">Inactive</span>';
                
                const valueDisplay = item.discount_type === 'Percentage' 
                    ? item.discount_value + '%' 
                    : '₹' + item.discount_value;

                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 fw-bold text-primary">${item.code}</td>
                        <td>${item.discount_type}</td>
                        <td class="fw-bold">${valueDisplay}</td>
                        <td><small class="text-muted">${item.description || '-'}</small></td>
                        <td>${status}</td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-white border me-1" onclick="editOffer(${item.id})"><i class="fa-solid fa-pen text-primary"></i></button>
                            <button class="btn btn-sm btn-white border" onclick="deleteOffer(${item.id})"><i class="fa-solid fa-trash text-danger"></i></button>
                        </td>
                    </tr>
                `;
            });
        }
    });
}

function openModal() {
    document.getElementById('offerForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('offerId').value = '';
    document.getElementById('modalTitle').innerText = 'Create Offer';
    new bootstrap.Modal(document.getElementById('offerModal')).show();
}

function submitForm() {
    const form = document.getElementById('offerForm');
    if(!form.checkValidity()) { form.reportValidity(); return; }

    const fd = new FormData(form);
    fetch('api/offers.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('offerModal')).hide();
            loadOffers();
            alert(res.message);
        } else {
            alert(res.message);
        }
    });
}

function editOffer(id) {
    fetch(`api/offers.php?action=fetch_one&id=${id}`).then(r => r.json()).then(res => {
        if(res.success) {
            const d = res.data;
            document.getElementById('modalTitle').innerText = 'Edit Offer';
            document.getElementById('formAction').value = 'update';
            document.getElementById('offerId').value = d.id;
            document.getElementById('code').value = d.code;
            document.getElementById('discountType').value = d.discount_type;
            document.getElementById('discountValue').value = d.discount_value;
            document.getElementById('description').value = d.description;
            document.getElementById('status').value = d.status;
            
            new bootstrap.Modal(document.getElementById('offerModal')).show();
        }
    });
}

function deleteOffer(id) {
    if(confirm('Delete this offer code?')) {
        const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
        fetch('api/offers.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) loadOffers(); else alert(res.message);
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>