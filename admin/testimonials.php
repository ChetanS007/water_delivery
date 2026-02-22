<?php
// admin/testimonials.php
?>
<?php include 'includes/header.php'; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Customer Testimonials</h3>
        <p class="text-muted small mb-0">Manage feedback displayed on the landing page</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#testimonialModal" onclick="resetForm()">
        <i class="fa-solid fa-plus me-2"></i> Add Testimonial
    </button>
</div>

<!-- Table Container -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Feedback</th>
                        <th>Rating</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="testimonialTableBody">
                    <tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="testimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="testimonialForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="testimonialId">
                    <input type="hidden" name="existing_photo" id="existingPhoto">

                    <div class="row g-4">
                        <div class="col-md-4 text-center">
                            <label class="form-label fw-bold d-block">User Photo</label>
                            <div class="position-relative d-inline-block">
                                <img id="previewImage" src="../assets/img/user-bg.png" class="img-thumbnail rounded-circle mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                                <input type="file" name="photo" id="userPhoto" class="form-control form-control-sm" accept="image/*" onchange="previewFile(this)">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">User Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="userName" class="form-control" placeholder="e.g. John Doe" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <select name="rating" id="userRating" class="form-select">
                                    <option value="5">5 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="2">2 Stars</option>
                                    <option value="1">1 Star</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Testimonial Content <span class="text-danger">*</span></label>
                                <textarea name="content" id="userContent" class="form-control" rows="4" placeholder="Write the feedback here..." required></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" onclick="submitForm()">Save Testimonial</button>
            </div>
        </div>
    </div>
</div>

<script>
let lastTestimonialData = null;

document.addEventListener('DOMContentLoaded', () => {
    loadTestimonials();
    setInterval(() => {
        const modal = document.getElementById('testimonialModal');
        if (!modal.classList.contains('show')) {
            loadTestimonials(true);
        }
    }, 10000);
});

function loadTestimonials(isPoll = false) {
    const tbody = document.getElementById('testimonialTableBody');
    if(!isPoll) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';
        lastTestimonialData = null;
    }

    fetch('api/testimonials.php?action=fetch_all')
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const currentDataStr = JSON.stringify(res.data);
            if(lastTestimonialData === currentDataStr) return;
            lastTestimonialData = currentDataStr;

            tbody.innerHTML = '';
            if(res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No testimonials found.</td></tr>';
                return;
            }

            res.data.forEach(t => {
                const img = t.photo_url ? `../${t.photo_url}` : '../assets/img/user-bg.png';
                let stars = '';
                for(let i=1; i<=5; i++) {
                    stars += i <= t.rating ? '<i class="fa-solid fa-star text-warning small"></i>' : '<i class="fa-regular fa-star text-muted small"></i>';
                }

                tbody.innerHTML += `
                    <tr class="animate__animated animate__fadeIn">
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <img src="${img}" class="rounded-circle me-3 border" width="40" height="40" style="object-fit: cover;">
                                <div class="fw-bold text-dark">${t.name}</div>
                            </div>
                        </td>
                        <td><small class="text-muted">${t.content.substring(0, 60)}...</small></td>
                        <td>${stars}</td>
                        <td><small class="text-muted">${new Date(t.created_at).toLocaleDateString()}</small></td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <button class="btn btn-sm btn-white border" onclick="editTestimonial(${t.id})"><i class="fa-solid fa-pen text-primary"></i></button>
                                <button class="btn btn-sm btn-white border" onclick="deleteTestimonial(${t.id})"><i class="fa-solid fa-trash text-danger"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
    });
}

function resetForm() {
    document.getElementById('testimonialForm').reset();
    document.getElementById('testimonialId').value = '';
    document.getElementById('existingPhoto').value = '';
    document.getElementById('modalTitle').innerText = 'Add Testimonial';
    document.getElementById('previewImage').src = '../assets/img/user-bg.png';
}

function previewFile(input) {
    const file = input.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = (e) => document.getElementById('previewImage').src = e.target.result;
        reader.readAsDataURL(file);
    }
}

function submitForm() {
    const form = document.getElementById('testimonialForm');
    const fd = new FormData(form);
    
    fetch('api/testimonials.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            bootstrap.Modal.getInstance(document.getElementById('testimonialModal')).hide();
            loadTestimonials();
        } else {
            alert(res.message);
        }
    });
}

function editTestimonial(id) {
    fetch(`api/testimonials.php?action=fetch_one&id=${id}`)
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            const t = res.data;
            document.getElementById('modalTitle').innerText = 'Edit Testimonial';
            document.getElementById('testimonialId').value = t.id;
            document.getElementById('userName').value = t.name;
            document.getElementById('userRating').value = t.rating;
            document.getElementById('userContent').value = t.content;
            document.getElementById('existingPhoto').value = t.photo_url;
            document.getElementById('previewImage').src = t.photo_url ? `../${t.photo_url}` : '../assets/img/user-bg.png';
            new bootstrap.Modal(document.getElementById('testimonialModal')).show();
        }
    });
}

function deleteTestimonial(id) {
    if(confirm('Are you sure you want to delete this testimonial?')) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch('api/testimonials.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => loadTestimonials());
    }
}
</script>

<?php include 'includes/footer.php'; ?>
