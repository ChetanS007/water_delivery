<?php
// Fetch system logo
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo'");
$stmt->execute();
$sysLogo = $stmt->fetchColumn() ?: 'assets/images/logo.png';

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Admin';
?>
<div class="sidebar">
    <a href="dashboard.php" class="sidebar-brand text-decoration-none">
        <img src="../<?php echo htmlspecialchars($sysLogo); ?>" alt="Sudha Jal" style="max-height: 40px; width: auto;">
    </a>
    
    <ul class="sidebar-menu">
        <li class="sidebar-item">
            <a href="dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </li>

        <li class="sidebar-header small text-uppercase text-muted fw-bold px-3 mt-3 mb-1" style="color: white!important;">Management</li>

        <li class="sidebar-item">
            <a href="users.php" class="sidebar-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Users / Customers
            </a>
        </li> 

        <li class="sidebar-item">
            <a href="delivery_boys.php" class="sidebar-link <?php echo $current_page == 'delivery_boys.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-motorcycle"></i> Delivery Boys
            </a>
        </li>

        <?php if ($role === 'Superadmin'): ?>
        <li class="sidebar-item">
            <a href="admins.php" class="sidebar-link <?php echo $current_page == 'admins.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-shield"></i> Admins
            </a>
        </li>
        <?php endif; ?>

        <li class="sidebar-item">
            <a href="products.php" class="sidebar-link <?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-box-open"></i> Products
            </a>
        </li>
 
        <li class="sidebar-item">
            <a href="subscriptions.php" class="sidebar-link <?php echo $current_page == 'subscriptions.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> Subscription Requests
            </a>
        </li>

        <li class="sidebar-item">
            <a href="orders.php" class="sidebar-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-clipboard-list"></i> Order History
            </a>
        </li>

        <li class="sidebar-item">
            <a href="bills.php" class="sidebar-link <?php echo $current_page == 'bills.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i> Bill
            </a>
        </li>

        <li class="sidebar-item">
            <a href="offers.php" class="sidebar-link <?php echo $current_page == 'offers.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-tags"></i> Offer Codes
            </a>
        </li>

        <li class="sidebar-item">
            <a href="fleet_map.php" class="sidebar-link <?php echo $current_page == 'fleet_map.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-map-location-dot"></i> Fleet Tracking
            </a>
        </li>

        <li class="sidebar-header small text-uppercase text-muted fw-bold px-3 mt-3 mb-1" style="color: white!important;">Analytics & Finance</li>

        <li class="sidebar-item">
            <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i> Reports
            </a>
        </li>

        <li class="sidebar-item">
            <a href="payments.php" class="sidebar-link <?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-wallet"></i> Payments
            </a>
        </li>
        <li class="sidebar-item">
            <a href="settings.php" class="sidebar-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i> Settings
            </a>
        </li>
    </ul>
</div>
