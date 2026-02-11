<?php
// users.php - User Management
require_once '../includes/db.php';
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Customer Management</h3>
        <p class="text-muted small mb-0">Manage and view all registered customers</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
        <i class="fa-solid fa-plus me-2"></i> Add Customer
    </button>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search by name, mobile, or email...">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-dark w-100" onclick="loadUsers(1)">Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Customer</th>
                        <th>Contacts</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <!-- Data Loaded via AJAX -->
                    <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
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

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="userForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">
                    
                <form id="userForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="fullName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Customer Type</label>
                            <select name="customer_type" id="customerType" class="form-select">
                                <option value="Home">Home</option>
                                <option value="Shop">Shop</option>
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
                        
                        <!-- Map Section -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Select Location</label>
                            <div class="input-group mb-2">
                                <input type="text" id="addressSearch" class="form-control" placeholder="Search for area or locality...">
                                <button type="button" class="btn btn-outline-primary" onclick="searchLocation()">Search</button>
                                <button type="button" class="btn btn-success" onclick="detectLocation()" title="Detect Current Location">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </button>
                            </div>
                            <div id="map" style="height: 300px; border-radius: 8px; border: 1px solid #ccc;"></div>
                            <small class="text-muted">Click on map or drag marker to auto-fetch address.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Full Address <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" class="form-control" rows="2" required></textarea>
                            <small class="text-muted">Auto-filled from map. Edit if necessary.</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="city" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="state" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" id="pincode" class="form-control">
                        </div>
                        
                        <!-- Hidden Lat/Long -->
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitUserForm()">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- View / QR Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="bg-primary text-white p-4 text-center rounded-top position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                    <div class="avatar-circle mx-auto mb-3 bg-white text-primary display-4 fw-bold shadow" style="width: 80px; height: 80px; line-height: 80px; border-radius: 50%;">
                        <span id="viewAvatar">U</span>
                    </div>
                    <h5 class="fw-bold mb-0" id="viewName">User Name</h5>
                    <p class="opacity-75 small mb-0" id="viewMobile">Mobile</p>
                </div>
                <div class="p-4">
                    <div class="text-center mb-4">
                        <label class="small text-muted text-uppercase fw-bold mb-2">Customer QR Code</label>
                        <div id="qrcode" class="d-flex justify-content-center p-3 bg-light rounded mx-auto border" style="width: fit-content; border: 1px solid #ddd;"></div>
                        <div class="mt-3 d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="downloadQR()"><i class="fa-solid fa-download me-1"></i> Download</button>
                            <button class="btn btn-sm btn-outline-dark" onclick="printQR()"><i class="fa-solid fa-print me-1"></i> Print</button>
                        </div>
                        <p class="small text-muted mt-2">Scan to assign orders quickly</p>
                    </div>
                    
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Customer ID</span>
                            <span class="fw-bold" id="viewId">#0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Type</span>
                            <span id="viewType">Home</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Email</span>
                            <span id="viewEmail">-</span>
                        </li>
                        <li class="list-group-item px-0">
                            <span class="text-muted d-block mb-1">Address</span>
                            <span class="fw-medium" id="viewAddress">-</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Status</span>
                            <span id="viewStatusBadge">Active</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
// Global Variables
let map, marker, debounceTimer;
let searchTimeout = null;

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();

    // Map Initialization on Modal Open
    const userModal = document.getElementById('userModal');
    userModal.addEventListener('shown.bs.modal', function () {
        initMap();
        if (document.getElementById('formAction').value === 'create') {
            detectLocation(true); // Auto-detect for new users
        }
    });

    // Event Listeners
    document.getElementById('statusFilter').addEventListener('change', () => loadUsers(1));
    
    document.getElementById('searchInput').addEventListener('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadUsers(1), 500);
    });
});

// --- Map & Location Functions ---
function initMap() {
    if (map) { map.invalidateSize(); return; }
    const defaultLat = 20.5937; 
    const defaultLng = 78.9629; 

    map = L.map('map').setView([defaultLat, defaultLng], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: 'OSM' }).addTo(map);
    marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);

    map.on('click', function(e) { updateMarker(e.latlng.lat, e.latlng.lng); });
    marker.on('dragend', function(e) { const c = e.target.getLatLng(); updateMarker(c.lat, c.lng); });
}

function updateMarker(lat, lng) {
    if(!map || !marker) return;
    marker.setLatLng([lat, lng]);
    document.getElementById('latitude').value = parseFloat(lat).toFixed(6);
    document.getElementById('longitude').value = parseFloat(lng).toFixed(6);

    clearTimeout(debounceTimer);
    document.getElementById('address').placeholder = "Fetching address...";
    debounceTimer = setTimeout(() => fetchAddress(lat, lng), 1000);
}

function fetchAddress(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`)
    .then(r => {
        if (!r.ok) throw new Error('Geocoding failed');
        return r.json();
    })
    .then(data => {
        if(data && data.address) {
            document.getElementById('address').value = data.display_name; 
            const addr = data.address;
            document.getElementById('city').value = addr.city || addr.town || addr.village || addr.municipality || addr.district || addr.county || '';
            document.getElementById('state').value = addr.state || addr.region || '';
            document.getElementById('pincode').value = addr.postcode || '';
        }
    })
    .catch(error => {
        console.error('Error fetching address:', error);
        document.getElementById('address').value = "Unable to fetch address. Please enter manually.";
    });
}

function searchLocation() {
    const q = document.getElementById('addressSearch').value;
    if(!q) return;
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1&accept-language=en`)
    .then(r => r.json())
    .then(data => {
        if(data.length > 0) {
            map.setView([data[0].lat, data[0].lon], 16);
            updateMarker(data[0].lat, data[0].lon);
        } else alert('Location not found');
    })
    .catch(error => console.error('Error searching location:', error));
}

function detectLocation(isAuto = false) {
    if (!navigator.geolocation) { if(!isAuto) alert("Geolocation not supported"); return; }
    const btn = document.querySelector('button[title="Detect Current Location"]');
    const old = btn ? btn.innerHTML : '';
    if(btn) { btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; btn.disabled = true; }

    navigator.geolocation.getCurrentPosition(
        pos => {
            if(map) {
                map.setView([pos.coords.latitude, pos.coords.longitude], 18);
                updateMarker(pos.coords.latitude, pos.coords.longitude);
            }
            if(btn) { btn.innerHTML = old; btn.disabled = false; }
        },
        err => {
            if(!isAuto) {
                let msg = "Unable to retrieve your location";
                if (err.code === err.PERMISSION_DENIED) {
                    msg = "Location permission denied. Please enable it in browser settings.";
                }
                alert(msg);
            }
            if(btn) { btn.innerHTML = old; btn.disabled = false; }
        },
        { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
    );
}

// --- CRUD Functions ---
function loadUsers(page = 1) {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const tbody = document.getElementById('userTableBody');
    const pagination = document.getElementById('pagination');

    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

    fetch(`api/users.php?action=fetch_all&page=${page}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            tbody.innerHTML = '';
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No customers found.</td></tr>';
                pagination.innerHTML = '';
                return;
            }
            
            res.data.forEach(user => {
                const statusBadge = user.status == 1 
                    ? '<span class="badge bg-success-subtle text-success">Active</span>' 
                    : '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
                
                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4 fw-bold text-muted">#${user.id}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle text-center me-2 text-primary fw-bold" style="width: 35px; height: 35px; line-height: 35px;">
                                    ${user.full_name.charAt(0).toUpperCase()}
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-medium text-dark">${user.full_name}</span>
                                    <small class="text-muted" style="font-size: 0.75rem;">Joined: ${new Date(user.created_at).toLocaleDateString()}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small">
                                <div class="mb-1"><i class="fa-solid fa-phone me-2 text-muted" style="width: 15px;"></i>${user.mobile}</div>
                                <div><i class="fa-solid fa-envelope me-2 text-muted" style="width: 15px;"></i>${user.email || '-'}</div>
                            </div>
                        </td>
                        <td><span class="badge border text-dark">${user.customer_type}</span></td>
                        <td>${statusBadge}</td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-white border" onclick="viewUser(${user.id})" title="View & QR"><i class="fa-solid fa-qrcode text-dark"></i></button>
                                <button class="btn btn-sm btn-white border" onclick="editUser(${user.id})" title="Edit"><i class="fa-solid fa-pen text-primary"></i></button>
                                <button class="btn btn-sm btn-white border" onclick="deleteUser(${user.id})" title="Delete"><i class="fa-solid fa-trash text-danger"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            pagination.innerHTML = '';
            for(let i = 1; i <= res.pagination.pages; i++) {
                const active = i === res.pagination.page ? 'active' : '';
                pagination.innerHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadUsers(${i})">${i}</a></li>`;
            }
        }
    });
}

function resetForm() {
    document.getElementById('userForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').innerText = 'Add New Customer';
}

function submitUserForm() {
    const form = document.getElementById('userForm');
    if(!form.checkValidity()) { form.reportValidity(); return; }
    const fd = new FormData(form);
    fetch('api/users.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
            alert(res.message);
        } else alert(res.message);
    });
}

function editUser(id) {
    fetch(`api/users.php?action=fetch_one&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const u = res.data;
            document.getElementById('userId').value = u.id;
            document.getElementById('fullName').value = u.full_name;
            document.getElementById('mobile').value = u.mobile;
            document.getElementById('email').value = u.email;
            document.getElementById('customerType').value = u.customer_type;
            document.getElementById('address').value = u.address;
            document.getElementById('city').value = u.city || '';
            document.getElementById('state').value = u.state || '';
            document.getElementById('pincode').value = u.pincode || '';
            document.getElementById('latitude').value = u.latitude || '';
            document.getElementById('longitude').value = u.longitude || '';
            document.getElementById('status').value = u.status;
            
            document.getElementById('formAction').value = 'update';
            document.getElementById('modalTitle').innerText = 'Edit Customer';
            new bootstrap.Modal(document.getElementById('userModal')).show();

            if(u.latitude && u.longitude) {
                setTimeout(() => {
                    if(map && marker) {
                        const lat = parseFloat(u.latitude);
                        const lng = parseFloat(u.longitude);
                        map.setView([lat, lng], 16);
                        marker.setLatLng([lat, lng]);
                    }
                }, 500);
            }
        }
    });
}

function deleteUser(id) {
    if(confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
        const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
        fetch('api/users.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) loadUsers();
            else alert(res.message);
        });
    }
}

function viewUser(id) {
    fetch(`api/users.php?action=fetch_one&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const u = res.data;
            document.getElementById('viewName').innerText = u.full_name;
            document.getElementById('viewMobile').innerText = u.mobile;
            document.getElementById('viewId').innerText = '#' + u.id;
            document.getElementById('viewType').innerText = u.customer_type;
            document.getElementById('viewEmail').innerText = u.email || 'N/A';
            document.getElementById('viewAddress').innerText = u.address;
            document.getElementById('viewAvatar').innerText = u.full_name.charAt(0).toUpperCase();
            
            const badge = u.status == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            document.getElementById('viewStatusBadge').innerHTML = badge;
            
            const qrContainer = document.getElementById('qrcode');
            qrContainer.innerHTML = '';
            new QRCode(qrContainer, { text: JSON.stringify({id: u.id, name: u.full_name}), width: 128, height: 128 });
            
            new bootstrap.Modal(document.getElementById('viewUserModal')).show();
        }
    });
}

function downloadQR() {
    const link = document.createElement('a');
    link.href = document.getElementById('qrcode').querySelector('img').src;
    link.download = `QR_${document.getElementById('viewName').innerText.replace(/\s+/g, '_')}.png`;
    document.body.appendChild(link); link.click(); document.body.removeChild(link);
}

function printQR() {
    const imgData = document.getElementById('qrcode').querySelector('img').src;
    const name = document.getElementById('viewName').innerText;
    const id = document.getElementById('viewId').innerText;
    const win = window.open('', '', 'height=600,width=600');
    win.document.write(`<html><head><title>Print QR</title><style>body{font-family:sans-serif;text-align:center;padding:20px}.card{border:2px solid #333;padding:20px;display:inline-block;border-radius:10px}h2{margin:10px 0;color:#333}p{font-size:14px;color:#666;margin:0}img{margin-top:10px;max-width:100%;border:1px solid #eee}</style></head><body><div class="card"><h2>AquaFlow Delivery</h2><p>Customer: <strong>${name}</strong></p><p>ID: <strong>${id}</strong></p><img src="${imgData}" width="200" /><p style="margin-top:10px">Scan to Deliver</p></div></body></html>`);
    win.document.close(); win.print();
}
</script>
<?php include 'includes/footer.php'; ?>
