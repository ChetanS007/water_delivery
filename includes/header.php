<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaFlow - Premium Water Delivery</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/styles_composition.css">
</head>
<body>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg main-header fixed-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                 <img src="https://uaques.smartdemowp.com/wp-content/themes/uaques/assets/images/logo.png" alt="AquaFlow">
            </a>
            
            <!-- Phone Center -->
            <div class="d-none d-lg-flex mx-auto align-items-center gap-2 text-decoration-none">
                 <div class="bg-light rounded-circle p-2 text-primary d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                     <i class="fa-solid fa-phone fa-sm"></i>
                 </div>
                 <a href="tel:+1234567890" class="fw-bold text-dark text-decoration-none font-subheading" style="font-size: 0.95rem;">+1 (234) 567-890</a>
            </div>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Menu Right -->
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">About</a></li> 
                    <li class="nav-item"><a class="nav-link" href="#products">Shop</a></li>
                    
                    <!-- Conditional Contact / QR Code -->
                    <?php if(isset($_SESSION['qr_code'])): ?>
                        <li class="nav-item">
                            <a class="nav-link fw-bold text-primary" href="#" data-bs-toggle="modal" data-bs-target="#qrCodeModal">
                                <i class="fa-solid fa-qrcode me-1"></i> My QR Code
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li> -->
                    <?php endif; ?>
                    
                    <!-- User & Cart -->
                    <?php if(isset($_SESSION['user_id'])): ?>
                         <li class="nav-item dropdown ms-lg-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px; font-size: 0.8rem;">
                                    <?php echo substr($_SESSION['name'], 0, 1); ?>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 animate slideIn" style="border-radius: 12px;">
                                <li><a class="dropdown-item rounded py-2 small" href="profile.php"><i class="fa-solid fa-id-card me-2 text-muted"></i> My Profile</a></li>
                                <li><a class="dropdown-item rounded py-2 small" href="my_orders.php"><i class="fa-solid fa-box-open me-2 text-muted"></i> My Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item rounded py-2 small text-danger" href="logout.php"><i class="fa-solid fa-power-off me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item ms-lg-3 d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                            <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
                        </li>
                    <?php endif; ?>
                    
                    <!-- <li class="nav-item ms-1">
                        <a href="cart.php" class="position-relative btn btn-light rounded-circle shadow-sm d-flex align-items-center justify-content-center p-0" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-cart-shopping text-primary small"></i>
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle">
                                <span class="visually-hidden">New alerts</span>
                            </span>
                        </a>
                    </li> -->
                </ul>
            </div>
        </div>
    </nav>
    <div style="padding-top: 80px;"></div> <!-- Spacer -->
    
    <!-- QR Code Modal -->
    <?php if(isset($_SESSION['qr_code'])): ?>
    <div class="modal fade" id="qrCodeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4 glass-card">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h5 class="fw-bold mb-3 font-heading text-primary">Your Delivery Code</h5>
                    <div class="d-flex justify-content-center my-3">
                        <div id="navbar_qrcode" class="bg-white p-3 rounded shadow-sm border"></div>
                    </div>
                    <p class="small text-muted mb-0">Show this QR code to the delivery partner upon arrival.</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var qrModalEl = document.getElementById('qrCodeModal');
            if(qrModalEl) {
                qrModalEl.addEventListener('shown.bs.modal', function () {
                    var container = document.getElementById("navbar_qrcode");
                    container.innerHTML = ""; // Clear previous
                    new QRCode(container, {
                        text: "<?php echo $_SESSION['qr_code']; ?>",
                        width: 200,
                        height: 200
                    });
                });
            }
        });
    </script>
    <?php endif; ?>
