<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    header("Location: login.php");
    exit();
}

// --- Data Fetching ---

// 1. Totals
$total_sales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_delivery_boys = $pdo->query("SELECT COUNT(*) FROM delivery_boys")->fetchColumn();

// 2. Sales Analytics (Monthly Order Counts)
$monthly_data = array_fill(1, 12, 0); // Jan to Dec
$missing_data = []; // Simulated "Missing Water Can" data
for($i=1; $i<=12; $i++) { $missing_data[] = rand(10, 50); } // Dummy data

$stmt = $pdo->prepare("SELECT MONTH(created_at) as m, COUNT(*) as c FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY m");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $monthly_data[$row['m']] = (int)$row['c'];
}
$sales_analytics_json = json_encode(array_values($monthly_data));
$missing_analytics_json = json_encode($missing_data);

// 3. Recent Orders (Last 7 Days)
$recent_days = [];
$recent_counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $recent_days[] = date('D', strtotime($date)); 
    $recent_counts[$date] = 0;
}
$stmt = $pdo->prepare("SELECT DATE(created_at) as d, COUNT(*) as c FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY d");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (isset($recent_counts[$row['d']])) {
        $recent_counts[$row['d']] = (int)$row['c'];
    }
}
$recent_orders_days_json = json_encode($recent_days);
$recent_orders_counts_json = json_encode(array_values($recent_counts));

// 4. Delivered vs Cancelled
$delivered_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'")->fetchColumn();
$cancelled_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn();

// Calculate Increase % (Dummy logic for visual completeness)
$del_increase = "3.5%";
$can_increase = "1.2%";

?>
<?php include 'includes/header.php'; ?>

<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- Google Fonts: Poppins -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary-purple: #7367F0;
        --soft-purple: #E8E7FD;
        --primary-blue: #00CFE8;
        --primary-green: #28C76F;
        --primary-orange: #FF9F43;
        --text-dark: #5E5873;
        --text-muted: #B9B9C3;
        --card-bg: #FFFFFF;
        --body-bg: #F5F6FA;
    }

    body {
        background-color: var(--body-bg) !important;
        font-family: 'Poppins', sans-serif !important;
        color: var(--text-dark);
    }

    /* Override header/sidebar transparency if needed */
    .content-wrapper { background-color: var(--body-bg); }

    .dashboard-card {
        background: var(--card-bg);
        border-radius: 12px; /* Soft rounded corners */
        box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.05); /* Soft shadow */
        border: none;
        padding: 20px;
        height: 100%;
        transition: transform 0.2s ease-in-out;
    }
    .dashboard-card:hover {
        transform: translateY(-2px);
    }

    .stat-title {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 5px;
    }
    .stat-value {
        color: var(--text-dark);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0;
    }
    
    .chart-container {
        position: relative;
    }

    /* Sidebar specific */
    .recent-orders-card {
        background: var(--primary-purple);
        border-radius: 12px;
        padding: 24px;
        color: white;
        box-shadow: 0 4px 24px 0 rgba(115, 103, 240, 0.4);
    }
    
    .stat-row {
        display: flex;
        align-items: center;
        margin-top: 20px;
        padding: 10px;
        background: #fff;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .icon-box {
        width: 38px; height: 38px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        margin-right: 15px;
        font-size: 1.1rem;
    }
    .bg-light-success { background: rgba(40, 199, 111, 0.15); color: var(--primary-green); }
    .bg-light-danger { background: rgba(234, 84, 85, 0.15); color: #EA5455; }
    
    h3, h4, h5 { font-family: 'Poppins', sans-serif; }
</style>

<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h3 class="fw-bold text-dark">Dashboard</h3>
        <span class="text-muted small"><?php echo date('d M Y'); ?></span>
    </div>

    <!-- Top Stats Row (4 Cards) -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Sales -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Sales</div>
                    <h4 class="stat-value">₹<?php echo number_format($total_sales); ?></h4>
                </div>
                <div id="sparkSales" style="min-width: 90px;"></div>
            </div>
        </div>
        
        <!-- Card 2: Orders -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Orders</div>
                    <h4 class="stat-value"><?php echo number_format($total_orders); ?></h4>
                </div>
                <div id="sparkOrders" style="min-width: 90px;"></div>
            </div>
        </div>
        
        <!-- Card 3: Customers -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Customers</div>
                    <h4 class="stat-value"><?php echo number_format($total_customers); ?></h4>
                </div>
                <div id="sparkCustomers" style="min-width: 90px;"></div>
            </div>
        </div>
        
        <!-- Card 4: Delivery Boys -->
        <div class="col-xl-3 col-md-6">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Delivery Boys</div>
                    <h4 class="stat-value"><?php echo number_format($total_delivery_boys); ?></h4>
                </div>
                <div id="sparkDelivery" style="min-width: 90px;"></div>
            </div>
        </div>
    </div>

    <!-- Middle Section: Main Chart & Sidebar -->
    <div class="row g-4">
        <!-- Main Chart: Sales Analytics -->
        <div class="col-lg-8">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark">Sales Analytics</h5>
                    <div class="d-flex gap-3">
                        <div class="d-flex align-items-center small">
                            <span class="badge rounded-circle bg-primary me-2" style="width: 10px; height: 10px;"> </span> Total Water Can
                        </div>
                        <div class="d-flex align-items-center small">
                            <span class="badge rounded-circle bg-info me-2" style="width: 10px; height: 10px;"> </span> Missing Water Can
                        </div>
                    </div>
                </div>
                <div id="salesAnalyticsChart" style="min-height: 350px;"></div>
            </div>
        </div>

        <!-- Right Sidebar: Recent Orders -->
        <div class="col-lg-4">
            <!-- Recent Orders Chart Card -->
            <div class="recent-orders-card mb-4">
                <h5 class="fw-bold mb-3 text-white">Recent Orders</h5>
                <div id="recentOrdersChart"></div>
            </div>

            <!-- Stats Below -->
            <div class="dashboard-card">
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-light-success">
                                    <i class="fa-solid fa-check"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">Delivered Orders</h6>
                                    <small class="text-success fw-bold"><?php echo $del_increase; ?> increased</small>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark"><?php echo $delivered_count; ?></h5>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-light-danger">
                                    <i class="fa-solid fa-times"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">Cancelled Orders</h6>
                                    <small class="text-danger fw-bold"><?php echo $can_increase; ?> increased</small>
                                </div>
                            </div>
                            <h5 class="fw-bold mb-0 text-dark"><?php echo $cancelled_count; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Sparkline Sales (Blue Bar)
    new ApexCharts(document.querySelector("#sparkSales"), {
        series: [{ data: [10, 15, 8, 12, 18, 20, 15] }],
        chart: { type: 'bar', height: 40, width: 80, sparkline: { enabled: true } },
        colors: ['#00CFE8'],
        plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
        tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
    }).render();

    // 2. Sparkline Orders (Purple Line)
    new ApexCharts(document.querySelector("#sparkOrders"), {
        series: [{ data: [15, 25, 20, 35, 25, 40, 30] }],
        chart: { type: 'line', height: 40, width: 80, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#7367F0'],
        tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
    }).render();

    // 3. Sparkline Customers (Green Bar)
    new ApexCharts(document.querySelector("#sparkCustomers"), {
        series: [{ data: [8, 12, 14, 10, 16, 18, 12] }],
        chart: { type: 'bar', height: 40, width: 80, sparkline: { enabled: true } },
        colors: ['#28C76F'],
        plotOptions: { bar: { borderRadius: 3, columnWidth: '60%' } },
        tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
    }).render();

    // 4. Sparkline Delivery (Orange Line)
    new ApexCharts(document.querySelector("#sparkDelivery"), {
        series: [{ data: [10, 12, 8, 15, 10, 12, 15] }],
        chart: { type: 'line', height: 40, width: 80, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#FF9F43'],
        tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
    }).render();

    // --- Main Chart: Sales Analytics (Area + Line) ---
    const salesOptions = {
        series: [
            {
                name: 'Total Water Can',
                type: 'area', // Purple Area
                data: <?php echo $sales_analytics_json; ?>
            },
            {
                name: 'Missing Water Can',
                type: 'line', // Cyan Line
                data: <?php echo $missing_analytics_json; ?>
            }
        ],
        chart: {
            height: 350,
            type: 'line', // Mixed type parent
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'Poppins'
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: [0, 3], // 0 for area (border), 3 for line
        },
        fill: {
            type: ['gradient', 'solid'],
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.6,
                opacityTo: 0.1,
                stops: [0, 90, 100],
                colorStops: [ { offset: 0, color: '#7367F0', opacity: 0.6 }, { offset: 100, color: '#7367F0', opacity: 0.1 } ]
            },
            solid: { opacity: 1 }
        },
        colors: ['#7367F0', '#00CFE8'], // Purple, Cyan
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#B9B9C3' } }
        },
        yaxis: {
            labels: { style: { colors: '#B9B9C3' } }
        },
        grid: {
            strokeDashArray: 5,
            borderColor: '#f0f0f0',
            xaxis: { lines: { show: false } }, 
            yaxis: { lines: { show: true } },
            padding: { top: 0, right: 0, bottom: 0, left: 10 }
        },
        legend: { show: false }
    };
    new ApexCharts(document.querySelector("#salesAnalyticsChart"), salesOptions).render();

    // --- Right Sidebar: Recent Orders (Vertical Bar) ---
    const recentOptions = {
        series: [{
            name: 'Orders',
            data: <?php echo $recent_orders_counts_json; ?>
        }],
        chart: {
            height: 220,
            type: 'bar',
            toolbar: { show: false },
            fontFamily: 'Poppins'
        },
        plotOptions: {
            bar: {
                columnWidth: '40%',
                borderRadius: 5,
                distributed: true // allows simple color loop
            }
        },
        colors: ['#E8E7FD', '#A5A2F7', '#E8E7FD', '#A5A2F7', '#E8E7FD', '#A5A2F7', '#E8E7FD'], // Alternate light/dark purple/pinkish
        dataLabels: { enabled: false },
        legend: { show: false },
        xaxis: {
            categories: <?php echo $recent_orders_days_json; ?>,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#fff' } }
        },
        yaxis: { show: false },
        grid: { show: false }
    };
    new ApexCharts(document.querySelector("#recentOrdersChart"), recentOptions).render();
});
</script>

<?php include 'includes/footer.php'; ?>
