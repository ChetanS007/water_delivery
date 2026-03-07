<?php
require_once '../includes/db.php';
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
    <title>Sudha Jal Admin Panel</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin_style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <h4 class="mb-0 fs-5"><?php echo ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF']))); ?></h4> 
        </div>
        
        <div class="header-right d-flex align-items-center gap-3">
            
            <!-- Notifications Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light position-relative rounded-circle border-0 dropdown-toggle shadow-sm p-2" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="width: 40px; height: 40px;">
                    <i class="fa-regular fa-bell text-dark"></i>
                    <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 0.65rem;">
                        0
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0" aria-labelledby="notificationDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                    <li class="dropdown-header bg-light text-dark fw-bold border-bottom py-2">Notifications</li>
                    <div id="notifList">
                        <li><span class="dropdown-item text-center text-muted py-3 small">Loading...</span></li>
                    </div>
                </ul>
            </div>

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
                    <!-- <li><h6 class="dropdown-header">Account</h6></li>
                    <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="change_password.php"><i class="fa-solid fa-key me-2"></i> Change Password</a></li> -->
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content Starts Here -->
    <div class="container-fluid py-4">

    <!-- Notification Fetch Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const notifBadge = document.getElementById('notifBadge');
        const notifList = document.getElementById('notifList');

        function fetchNotifications() {
            fetch('api/notifications.php')
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const count = data.unread_count || 0;
                    if(count > 0) {
                        notifBadge.innerText = count;
                        notifBadge.classList.remove('d-none');
                    } else {
                        notifBadge.classList.add('d-none');
                    }

                    if(data.data.length === 0) {
                        notifList.innerHTML = '<li><span class="dropdown-item text-center text-muted py-3 small">No new notifications</span></li>';
                    } else {
                        let html = '';
                        data.data.forEach(n => {
                            let icon = n.type === 'subscription' ? '<i class="fa-solid fa-bell text-primary"></i>' : '<i class="fa-solid fa-file-invoice-dollar text-success"></i>';
                            html += `
                                <li>
                                    <a class="dropdown-item py-2 border-bottom text-wrap" href="${n.url}" style="min-width: 250px;">
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="mt-1">${icon}</div>
                                            <div>
                                                <div class="fw-bold small text-dark">${n.title}</div>
                                                <div class="small text-muted lh-sm mt-1" style="font-size: 0.8rem;">${n.message}</div>
                                                <div class="text-muted mt-1" style="font-size: 0.7rem;"><i class="fa-regular fa-clock me-1"></i> ${new Date(n.timestamp).toLocaleString()}</div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            `;
                        });
                        notifList.innerHTML = html;
                    }
                }
            })
            .catch(err => console.error('Error fetching notifications:', err));
        }

        fetchNotifications();
        // Poll every 15 seconds
        setInterval(fetchNotifications, 15000);
    });
    </script>
