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
    <link rel="stylesheet" href="/water_delivery/assets/css/style.css">
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="/water_delivery/index.php">
            <i class="fa-solid fa-droplet"></i> AquaFlow
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#products">Products</a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/water_delivery/my_orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold text-primary" href="/water_delivery/profile.php">
                             <i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-danger ms-2 btn-sm" href="/water_delivery/logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                    </li>
                    <li class="nav-item ms-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerModal">Get Started</button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
