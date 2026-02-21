<?php
// delivery_boys.php
?>
<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Delivery Partners</h3>
        <p class="text-muted small mb-0">Manage your delivery fleet</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#boyModal" onclick="resetForm()">
        <i class="fa-solid fa-plus me-2"></i> Register New
    </button>
</div>

<!-- Search & Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search by name or mobile...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="boyTableBody">
                    <tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-end mt-3">
            <nav><ul class="pagination pagination-sm" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="boyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Delivery Partner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="boyForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="boyId">

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="fullName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" id="mobile" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span id="passReq" class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control">
                        <small class="text-muted" id="passHelp">Required for new accounts.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Details</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewBoyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-body p-4 text-center">
                <div class="bg-light rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-user fa-3x text-primary"></i>
                </div>
                <h4 class="fw-bold mb-1" id="viewName">Loading...</h4>
                <p class="text-muted mb-3" id="viewMobile">...</p>
                <div id="viewStatusBadge" class="mb-4"></div>
                
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">ID</small>
                            <span class="fw-bold" id="viewId">...</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted d-block text-uppercase" style="font-size: 0.7rem;">Created</small>
                            <span class="fw-bold" id="viewDate">...</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary mt-4 w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadBoys();
    
    // Search Debounce
    let timer;
    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadBoys(1), 500);
    });

    // Form Submit
    document.getElementById('boyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        fetch('api/delivery_boys.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                bootstrap.Modal.getInstance(document.getElementById('boyModal')).hide();
                loadBoys();
                alert(res.message);
            } else {
                alert(res.message);
            }
        });
    });
});

function loadBoys(page = 1) {
    const search = document.getElementById('searchInput').value;
    const tbody = document.getElementById('boyTableBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

    fetch(`api/delivery_boys.php?action=fetch_all&page=${page}&search=${encodeURIComponent(search)}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            tbody.innerHTML = '';
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No delivery partners found.</td></tr>';
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            res.data.forEach(boy => {
                const status = boy.status == 1 
                    ? '<span class="badge bg-success-subtle text-success">Active</span>' 
                    : '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
                
                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#${boy.id}</td>
                        <td class="fw-medium text-dark">${boy.full_name}</td>
                        <td>${boy.mobile}</td>
                        <td>${status}</td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-white border" onclick="viewBoy(${boy.id})" title="View"><i class="fa-solid fa-eye text-dark"></i></button>
                                <button class="btn btn-sm btn-white border" onclick="editBoy(${boy.id})" title="Edit"><i class="fa-solid fa-pen text-primary"></i></button>
                                <button class="btn btn-sm btn-white border" onclick="deleteBoy(${boy.id})" title="Delete"><i class="fa-solid fa-trash text-danger"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            // Pagination
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            for(let i=1; i<=res.pagination.pages; i++) {
                const active = i === res.pagination.page ? 'active' : '';
                pagination.innerHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadBoys(${i})">${i}</a></li>`;
            }
        }
    });
}

function resetForm() {
    document.getElementById('boyForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('boyId').value = '';
    document.getElementById('modalTitle').innerText = 'Add Delivery Partner';
    document.getElementById('password').required = true;
    document.getElementById('passReq').style.display = 'inline';
    document.getElementById('passHelp').innerText = 'Required for new accounts.';
}

function editBoy(id) {
    fetch(`api/delivery_boys.php?action=fetch_one&id=${id}`).then(r => r.json()).then(res => {
        if(res.success) {
            const d = res.data;
            document.getElementById('modalTitle').innerText = 'Edit Delivery Partner';
            document.getElementById('formAction').value = 'update';
            document.getElementById('boyId').value = d.id;
            document.getElementById('fullName').value = d.full_name;
            document.getElementById('mobile').value = d.mobile;
            document.getElementById('status').value = d.status;
            
            // Password optional update
            document.getElementById('password').required = false;
            document.getElementById('password').value = ''; 
            document.getElementById('passReq').style.display = 'none';
            document.getElementById('passHelp').innerText = 'Leave blank to keep current password.';

            new bootstrap.Modal(document.getElementById('boyModal')).show();
        }
    });
}

function viewBoy(id) {
    fetch(`api/delivery_boys.php?action=fetch_one&id=${id}`).then(r => r.json()).then(res => {
        if(res.success) {
            const d = res.data;
            document.getElementById('viewName').innerText = d.full_name;
            document.getElementById('viewMobile').innerText = d.mobile;
            document.getElementById('viewId').innerText = '#' + d.id;
            document.getElementById('viewDate').innerText = new Date(d.created_at).toLocaleDateString();
            document.getElementById('viewStatusBadge').innerHTML = d.status == 1 
                ? '<span class="badge bg-success">Active Account</span>' 
                : '<span class="badge bg-danger">Inactive Account</span>';
            
            new bootstrap.Modal(document.getElementById('viewBoyModal')).show();
        }
    });
}

function deleteBoy(id) {
    if(confirm('Are you sure you want to remove this delivery partner?')) {
        const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
        fetch('api/delivery_boys.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) loadBoys(); else alert(res.message);
        });
    }
}
</script>
<?php include 'includes/footer.php'; ?>
