<?php
require_once '../includes/db.php';
// admins.php - Admin Management (Super Admin Only)
if ($_SESSION['role'] !== 'Superadmin') {
    header("Location: dashboard.php");
    exit();
}
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Admin Management</h3>
        <p class="text-muted small mb-0">Manage system administrators (Super Admin Only)</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#adminModal" onclick="resetForm()">
        <i class="fa-solid fa-plus me-2"></i> Add Admin
    </button>
</div>

<!-- Admin Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="adminsTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Address</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="adminTableBody">
                     <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Admin Modal -->
<div class="modal fade" id="adminModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="adminForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="adminId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="fullName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" id="role" class="form-select">
                                <option value="Admin">Admin</option>
                                <option value="Superadmin">Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" id="mobile" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" required>
                             <small class="text-muted" id="passHelp">Required for creation.</small>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control" rows="3" placeholder="Enter full address"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitAdminForm()">Save Admin</button>
            </div>
        </div>
    </div>
</div>

<script>
let lastAdminData = null;
document.addEventListener('DOMContentLoaded', () => {
    loadAdmins();
    // Poll every 10 seconds
    setInterval(() => {
        const modal = document.getElementById('adminModal');
        if (!modal.classList.contains('show')) {
            loadAdmins(true);
        }
    }, 10000);
});

function loadAdmins(isPoll = false) {
    const tbody = document.getElementById('adminTableBody');
    
    if (!isPoll) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
        lastAdminData = null;
    }

    fetch('api/admins.php?action=fetch_all')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastAdminData === currentDataStr) return;
            lastAdminData = currentDataStr;

            tbody.innerHTML = '';
            res.data.forEach(admin => {
                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 fw-bold">#${admin.id}</td>
                        <td>${admin.full_name}</td>
                        <td>${admin.mobile}<br><small class='text-muted'>${admin.email || ''}</small></td>
                        <td><span class="badge bg-info text-dark">${admin.role}</span></td>
                        <td><small>${admin.address ? admin.address.substring(0, 30)+'...' : '-'}</small></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border" onclick="editAdmin(${admin.id})"><i class="fa-solid fa-pen text-primary"></i></button>
                            ${admin.id != <?php echo $_SESSION['user_id']; ?> ? `<button class="btn btn-sm btn-light border" onclick="deleteAdmin(${admin.id})"><i class="fa-solid fa-trash text-danger"></i></button>` : ''}
                        </td>
                    </tr>
                `;
            });
        }
    });
}

function resetForm() {
    document.getElementById('adminForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('adminId').value = '';
    document.getElementById('modalTitle').innerText = 'Add New Admin';
    document.getElementById('password').required = true;
    document.getElementById('passHelp').innerText = "Required for creation.";
}

function submitAdminForm() {
    const form = document.getElementById('adminForm');
    if(!form.checkValidity()) { form.reportValidity(); return; }
    const fd = new FormData(form);
    fetch('api/admins.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if(res.success) {
            Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 2000, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('adminModal')).hide();
            loadAdmins();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    });
}

function editAdmin(id) {
    fetch(`api/admins.php?action=fetch_one&id=${id}`).then(r => r.json()).then(res => {
        if(res.success) {
            const d = res.data;
            document.getElementById('adminId').value = d.id;
            document.getElementById('fullName').value = d.full_name;
            document.getElementById('mobile').value = d.mobile;
            document.getElementById('email').value = d.email;
            document.getElementById('role').value = d.role;
            document.getElementById('address').value = d.address;
            
            // Password optional update
            document.getElementById('password').required = false;
            document.getElementById('passHelp').innerText = "Leave blank to keep current password.";

            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').innerText = 'Edit Admin';
            
            new bootstrap.Modal(document.getElementById('adminModal')).show();
        }
    });
}

function deleteAdmin(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Delete this admin?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete!'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
            fetch('api/admins.php', {method: 'POST', body: fd}).then(r=>r.json()).then(res=>{
                if(res.success) {
                    Swal.fire('Deleted!', 'Admin has been removed.', 'success');
                    loadAdmins();
                } else {
                    Swal.fire('Error!', res.message, 'error');
                }
            });
        }
    });
}
</script>
<?php include 'includes/footer.php'; ?>