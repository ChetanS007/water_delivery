<?php
// products.php
?>
<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Product Inventory</h3>
        <p class="text-muted small mb-0">Manage water cans, bottles, and accessories</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
        <i class="fa-solid fa-plus me-2"></i> Add Product
    </button>
</div>

<!-- Search & Table -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search products...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Added On</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
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
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="productForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="productId">

                    <div class="row g-4">
                        <div class="col-md-4 text-center">
                            <label class="form-label fw-bold d-block">Product Image</label>
                            <div class="position-relative d-inline-block">
                                <img id="previewImage" src="https://placehold.co/150x150?text=No+Image" class="img-thumbnail rounded mb-2" style="width: 150px; height: 150px; object-fit: cover;">
                                <input type="file" name="image" id="productImage" class="form-control form-control-sm" accept="image/*" onchange="previewFile(this)">
                            </div>
                            <small class="text-muted d-block mt-2">Allowed: JPG, PNG, WEBP</small>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="product_name" id="productName" class="form-control" required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Price (₹) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="price" id="productPrice" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" name="stock_quantity" id="productStock" class="form-control" value="0">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="productDesc" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="productStatus" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">Save Product</button>
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"></button>
                <div class="row g-0">
                    <div class="col-md-5 bg-light d-flex align-items-center justify-content-center">
                        <img id="viewImg" src="" class="img-fluid" style="max-height: 300px; object-fit: contain;">
                    </div>
                    <div class="col-md-7 p-4">
                        <h4 class="fw-bold mb-2" id="viewTitle">Product Name</h4>
                        <div class="mb-3">
                            <span class="h4 text-primary fw-bold" id="viewPrice">₹0.00</span>
                            <span id="viewStatus" class="ms-2"></span>
                        </div>
                        <p class="text-muted" id="viewDesc">Description goes here...</p>
                        
                        <div class="d-flex justify-content-between border-top pt-3 mt-4">
                            <div>
                                <small class="text-uppercase text-muted d-block" style="font-size: 0.7rem;">Stock</small>
                                <span class="fw-bold" id="viewStock">0</span>
                            </div>
                            <div>
                                <small class="text-uppercase text-muted d-block" style="font-size: 0.7rem;">Created At</small>
                                <span class="fw-bold" id="viewDate">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let lastProductData = null;
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    
    // Polling
    setInterval(() => {
        const modal = document.getElementById('productModal');
        const isModalOpen = modal.classList.contains('show');
        if (!isModalOpen) {
            loadProducts(1, true);
        }
    }, 15000);
    
    // Search Debounce
    let timer;
    document.getElementById('searchInput').addEventListener('keyup', () => {
        clearTimeout(timer);
        timer = setTimeout(() => loadProducts(1), 500);
    });
});

function loadProducts(page = 1, isPoll = false) {
    const search = document.getElementById('searchInput').value;
    const tbody = document.getElementById('productTableBody');
    
    if(!isPoll) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
        lastProductData = null;
    }

    fetch(`api/products.php?action=fetch_all&page=${page}&search=${encodeURIComponent(search)}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if (lastProductData === currentDataStr) return;
            lastProductData = currentDataStr;

            tbody.innerHTML = '';
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No products found.</td></tr>';
                document.getElementById('pagination').innerHTML = '';
                return;
            }

            res.data.forEach(p => {
                const status = p.status == 1 
                    ? '<span class="badge bg-success-subtle text-success">Active</span>' 
                    : '<span class="badge bg-danger-subtle text-danger">Inactive</span>';
                
                const img = p.image_url ? `../${p.image_url}` : 'https://placehold.co/50x50?text=No+Img';
                
                tbody.innerHTML += `
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <img src="${img}" class="rounded me-3 border" width="48" height="48" style="object-fit: cover;">
                                <div>
                                    <span class="fw-medium text-dark d-block">${p.product_name}</span>
                                    <small class="text-muted">ID: #${p.id}</small>
                                </div>
                            </div>
                        </td>
                        <td class="fw-bold text-dark">₹${parseFloat(p.price).toFixed(2)}</td>
                        <td>${p.stock_quantity} units</td>
                        <td>${status}</td>
                        <td><small class="text-muted">${new Date(p.created_at).toLocaleDateString()}</small></td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-white border" onclick="viewProduct(${p.id})" title="View"><i class="fa-solid fa-eye text-dark"></i></button>
                                <button class="btn btn-sm btn-white border" onclick="editProduct(${p.id})" title="Edit"><i class="fa-solid fa-pen text-primary"></i></button>
                                <button class="btn btn-sm btn-white border" onclick="deleteProduct(${p.id})" title="Delete"><i class="fa-solid fa-trash text-danger"></i></button>
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
                pagination.innerHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadProducts(${i})">${i}</a></li>`;
            }
        }
    });
}

function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('productId').value = '';
    document.getElementById('modalTitle').innerText = 'Add New Product';
    document.getElementById('previewImage').src = 'https://placehold.co/150x150?text=No+Image';
}

function previewFile(input) {
    const file = input.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}

function submitForm() {
    const form = document.getElementById('productForm');
    if(!form.checkValidity()) { form.reportValidity(); return; }
    
    const fd = new FormData(form);
    fetch('api/products.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
            loadProducts();
            alert(res.message);
        } else alert(res.message);
    });
}

function editProduct(id) {
    fetch(`api/products.php?action=fetch_one&id=${id}`).then(r => r.json()).then(res => {
        if(res.success) {
            const d = res.data;
            document.getElementById('modalTitle').innerText = 'Edit Product';
            document.getElementById('formAction').value = 'update';
            document.getElementById('productId').value = d.id;
            document.getElementById('productName').value = d.product_name;
            document.getElementById('productPrice').value = d.price;
            document.getElementById('productStock').value = d.stock_quantity;
            document.getElementById('productDesc').value = d.description;
            document.getElementById('productStatus').value = d.status;
            
            if(d.image_url) document.getElementById('previewImage').src = '../' + d.image_url;
            else document.getElementById('previewImage').src = 'https://placehold.co/150x150?text=No+Image';

            new bootstrap.Modal(document.getElementById('productModal')).show();
        }
    });
}

function deleteProduct(id) {
    if(confirm('Delete this product permanently?')) {
        const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
        fetch('api/products.php', { method: 'POST', body: fd }).then(r => r.json()).then(res => {
            if(res.success) loadProducts(); else alert(res.message);
        });
    }
}

function viewProduct(id) {
    fetch(`api/products.php?action=fetch_one&id=${id}`).then(r => r.json()).then(res => {
        if(res.success) {
            const d = res.data;
            document.getElementById('viewTitle').innerText = d.product_name;
            document.getElementById('viewPrice').innerText = '₹' + parseFloat(d.price).toFixed(2);
            document.getElementById('viewDesc').innerText = d.description || 'No description provided.';
            document.getElementById('viewStock').innerText = d.stock_quantity;
            document.getElementById('viewDate').innerText = new Date(d.created_at).toDateString();
            
            document.getElementById('viewStatus').innerHTML = d.status == 1 
                ? '<span class="badge bg-success">Active</span>' 
                : '<span class="badge bg-danger">Inactive</span>';

            if(d.image_url) document.getElementById('viewImg').src = '../' + d.image_url;
            else document.getElementById('viewImg').src = 'https://placehold.co/400x300?text=No+Image';
            
            new bootstrap.Modal(document.getElementById('viewProductModal')).show();
        }
    });
}
</script>
<?php include 'includes/footer.php'; ?>
