<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';

// Handle Logo Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $targetDir = "../uploads/settings/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = "logo_" . time() . "_" . basename($_FILES["logo"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'svg', 'webp');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFilePath)) {
            $dbPath = "uploads/settings/" . $fileName;
            
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'logo'");
            if ($stmt->execute([$dbPath])) {
                $message = "Logo uploaded and updated successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to update logo in database.";
                $messageType = "danger";
            }
        } else {
            $message = "Sorry, there was an error uploading your file.";
            $messageType = "danger";
        }
    } else {
        $message = "Sorry, only JPG, JPEG, PNG, GIF, SVG & WEBP files are allowed.";
        $messageType = "danger";
    }
}

// Handle Payment QR Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_qr'])) {
    $targetDir = "../uploads/settings/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = "payment_qr_" . time() . "_" . basename($_FILES["payment_qr"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $allowTypes = array('jpg', 'png', 'jpeg', 'gif', 'svg', 'webp');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($_FILES["payment_qr"]["tmp_name"], $targetFilePath)) {
            $dbPath = "uploads/settings/" . $fileName;
            
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'payment_qr'");
            if ($stmt->execute([$dbPath])) {
                $message = "Payment QR code updated successfully.";
                $messageType = "success";
            } else {
                $message = "Failed to update payment QR in database.";
                $messageType = "danger";
            }
        } else {
            $message = "Sorry, there was an error uploading your file.";
            $messageType = "danger";
        }
    } else {
        $message = "Sorry, only JPG, JPEG, PNG, GIF, SVG & WEBP files are allowed.";
        $messageType = "danger";
    }
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

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Logo Config -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4"><i class="fa-solid fa-image me-2 text-primary"></i>Logo Configuration</h5>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted small">Current Logo Preview</label>
                        <div class="bg-light p-4 rounded text-center border">
                            <img src="../<?php echo htmlspecialchars($currentLogo); ?>" alt="Current Logo" style="max-height: 80px; width: auto;">
                        </div>
                    </div>

                    <form action="settings.php" method="POST" enctype="multipart/form-data">
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
                        <div class="bg-light p-4 rounded text-center border">
                            <?php if ($currentPaymentQR): ?>
                                <img src="../<?php echo htmlspecialchars($currentPaymentQR); ?>" alt="Payment QR" style="max-height: 150px; width: auto;">
                            <?php else: ?>
                                <div class="text-muted small py-4">No QR code uploaded yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form action="settings.php" method="POST" enctype="multipart/form-data">
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

<?php include 'includes/footer.php'; ?>
