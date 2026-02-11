<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['name'] ?? 'Admin';
$user_role = $_SESSION['role'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaFlow Admin Panel</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_style.css">
</head>
<body>

<!-- Sidebar Include -->
<?php include 'sidebar.php'; ?>

<!-- Main Wrapper -->
<div class="main-content">
    
    <!-- Top Header -->
    <header class="top-header">
        <div class="header-left d-flex align-items-center">
            <button class="btn btn-link text-dark me-3 d-md-none" id="sidebarToggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h4>Dashboard</h4> 
        </div>
        
        <div class="header-right">
            <div class="dropdown">
                <div class="user-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-details me-2">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user_role); ?></span>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><h6 class="dropdown-header">Account</h6></li>
                    <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="change_password.php"><i class="fa-solid fa-key me-2"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content Starts Here -->
    <div class="container-fluid py-4">
