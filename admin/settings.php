<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
    header("Location: login.php");
    exit();
}

// Handle AJAX Post Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    $setting_key = $_POST['setting_key'] ?? '';
    
    if ($setting_key && isset($_FILES[$setting_key])) {
        $targetDir = "../uploads/settings/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = $setting_key . "_" . time() . "_" . basename($_FILES[$setting_key]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

        $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'svg', 'webp');
        if (in_array(strtolower($fileType), $allowTypes)) {
            if (move_uploaded_file($_FILES[$setting_key]["tmp_name"], $targetFilePath)) {
                $dbPath = "uploads/settings/" . $fileName;
                
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                if ($stmt->execute([$dbPath, $setting_key])) {
                    echo json_encode(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $setting_key)) . ' updated successfully!', 'path' => $dbPath]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update database.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Error uploading file.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WEBP, SVG.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing data.']);
    }
    exit();
}

// Fetch current setting
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('logo', 'payment_qr')");
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$currentLogo = $settings['logo'] ?? 'assets/images/logo.png';
$currentPaymentQR = $settings['payment_qr'] ?? '';

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold text-dark">System Settings</h3>
    </div>

    <div class="row g-4">
        <!-- Logo Config -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4"><i class="fa-solid fa-image me-2 text-primary"></i>Logo Configuration</h5>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small">Current Logo Preview</label>
                        <div class="bg-light p-4 rounded text-center border">
                            <img id="logoPreview" src="../<?php echo htmlspecialchars($currentLogo); ?>" alt="Current Logo" style="max-height: 80px; width: auto;">
                        </div>
                    </div>

                    <form id="logoForm" onsubmit="updateSetting(event, 'logo')">
                        <div class="mb-3">
                            <label for="logo" class="form-label fw-600">Upload New Logo</label>
                            <input class="form-control" type="file" id="logo" name="logo" required>
                            <div class="form-text">Recommended size: 200x50px. Max 2MB.</div>
                        </div>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">
                            <i class="fa-solid fa-cloud-arrow-up me-2"></i> Update Logo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment QR Config -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4"><i class="fa-solid fa-qrcode me-2 text-success"></i>Payment QR Code</h5>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small">Current QR Code</label>
                        <div class="bg-light p-4 rounded text-center border" id="qrContainer">
                            <?php if ($currentPaymentQR): ?>
                                <img id="qrPreview" src="../<?php echo htmlspecialchars($currentPaymentQR); ?>" alt="Payment QR" style="max-height: 150px; width: auto;">
                            <?php else: ?>
                                <div class="text-muted small py-4" id="noQrMsg">No QR code uploaded yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form id="qrForm" onsubmit="updateSetting(event, 'payment_qr')">
                        <div class="mb-3">
                            <label for="payment_qr" class="form-label fw-600">Upload Payment QR Code</label>
                            <input class="form-control" type="file" id="payment_qr" name="payment_qr" required>
                            <div class="form-text">This QR will be shown to customers for payments.</div>
                        </div>
                        <button type="submit" class="btn btn-success px-4 fw-bold">
                            <i class="fa-solid fa-cloud-arrow-up me-2"></i> Upload QR Code
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateSetting(e, key) {
    e.preventDefault();
    const form = e.target;
    const fd = new FormData(form);
    fd.append('setting_key', key);

    Swal.fire({
        title: 'Updating...',
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('settings.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'Updated!', text: res.message, timer: 2000, showConfirmButton: false });
            // Update preview dynamically
            if (key === 'logo') {
                document.getElementById('logoPreview').src = '../' + res.path;
            } else if (key === 'payment_qr') {
                let img = document.getElementById('qrPreview');
                if (!img) {
                    const msg = document.getElementById('noQrMsg');
                    if(msg) msg.remove();
                    img = document.createElement('img');
                    img.id = 'qrPreview';
                    img.style.maxHeight = '150px';
                    img.style.width = 'auto';
                    document.getElementById('qrContainer').appendChild(img);
                }
                img.src = '../' + res.path;
            }
            form.reset();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    })
    .catch(err => {
        Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong.' });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
