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

// New: Bill / Payment Stats
$total_received = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE status = 'Approved'")->fetchColumn();
$total_bill = $pdo->query("
    SELECT COALESCE(SUM(oi.quantity * p.price), 0)
    FROM daily_deliveries dd
    JOIN orders o ON dd.subscription_id = o.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE dd.status = 'Delivered'
")->fetchColumn();
$pending_bill = max(0, $total_bill - $total_received);

// 2. Sales Analytics (Daily Delivered Cans for Current Month)
$current_month_days = date('t'); // Number of days in current month
$daily_data = array_fill(1, $current_month_days, 0);

$stmt = $pdo->prepare("
    SELECT DAY(dd.delivery_date) as d, SUM(oi.quantity) as c 
    FROM daily_deliveries dd 
    JOIN order_items oi ON dd.subscription_id = oi.order_id 
    WHERE dd.status = 'Delivered' 
    AND MONTH(dd.delivery_date) = MONTH(CURDATE()) 
    AND YEAR(dd.delivery_date) = YEAR(CURDATE()) 
    GROUP BY d
");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $daily_data[$row['d']] = (int)$row['c'];
}
$sales_analytics_json = json_encode(array_values($daily_data));
$analytics_labels_json = json_encode(range(1, $current_month_days));

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

// Fetch Recent Orders for Table
$recent_orders_table = $pdo->query("
    SELECT o.id, u.full_name, o.total_amount, o.status, o.created_at 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 6
")->fetchAll();

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

    <!-- Top Stats Row (6 Cards) -->
    <div class="row g-4 mb-4">
        <!-- Card 1: Sales -->
        <div class="col-xl-2 col-md-4">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Sales</div>
                    <h4 class="stat-value" id="statSales">₹<?php echo number_format($total_sales); ?></h4>
                </div>
                <div id="sparkSales" style="min-width: 50px;"></div>
            </div>
        </div>
        
        <!-- Card 2: Orders -->
        <div class="col-xl-2 col-md-4">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Orders</div>
                    <h4 class="stat-value" id="statOrders"><?php echo number_format($total_orders); ?></h4>
                </div>
                <div id="sparkOrders" style="min-width: 50px;"></div>
            </div>
        </div>
        
        <!-- Card 3: Customers -->
        <div class="col-xl-2 col-md-4">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Customers</div>
                    <h4 class="stat-value" id="statCustomers"><?php echo number_format($total_customers); ?></h4>
                </div>
                <div id="sparkCustomers" style="min-width: 50px;"></div>
            </div>
        </div>
        
        <!-- Card 4: Delivery Boys -->
        <div class="col-xl-2 col-md-4">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Total Delivery Boys</div>
                    <h4 class="stat-value" id="statBoys"><?php echo number_format($total_delivery_boys); ?></h4>
                </div>
                <div id="sparkDelivery" style="min-width: 50px;"></div>
            </div>
        </div>

        <!-- Card 5: Total Received -->
        <div class="col-xl-2 col-md-4">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Received Bill</div>
                    <h4 class="stat-value text-success" id="statReceived">₹<?php echo number_format($total_received); ?></h4>
                </div>
                <div id="sparkReceived" style="min-width: 50px;"></div>
            </div>
        </div>

        <!-- Card 6: Pending Bill -->
        <div class="col-xl-2 col-md-4">
            <div class="dashboard-card d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-title">Pending Bill</div>
                    <h4 class="stat-value text-danger" id="statPending">₹<?php echo number_format($pending_bill); ?></h4>
                </div>
                <div id="sparkPending" style="min-width: 50px;"></div>
            </div>
        </div>
    </div>

    <!-- Middle Section: Main Chart & Sidebar -->
    <div class="row g-4">
        <!-- Main Chart: Sales Analytics -->
        <div class="col-lg-8">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark">Daily Deliveries (<?php echo date('F'); ?>)</h5>
                    <div class="d-flex gap-3">
                        <div class="d-flex align-items-center small">
                            <span class="badge rounded-circle bg-primary me-2" style="width: 10px; height: 10px;"> </span> Delivered Cans
                        </div>
                    </div>
                </div>
                <div id="salesAnalyticsChart" style="min-height: 350px;"></div>
            </div>
        </div>

        <!-- Right Sidebar: Recent Orders Bar Chart -->
        <div class="col-lg-4">
            <div class="dashboard-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 text-dark">Day-wise Orders</h5>
                    <span class="text-muted small">Last 7 Days</span>
                </div>
                <!-- Container for Bar Chart -->
                <div id="recentOrdersChart" style="min-height: 350px;"></div>
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

    // 5. Sparkline Received (Green Line)
    new ApexCharts(document.querySelector("#sparkReceived"), {
        series: [{ data: [5, 10, 15, 12, 20, 18, 25] }],
        chart: { type: 'line', height: 40, width: 60, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#28C76F'],
        tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
    }).render();

    // 6. Sparkline Pending (Red Line)
    new ApexCharts(document.querySelector("#sparkPending"), {
        series: [{ data: [20, 15, 10, 18, 12, 22, 16] }],
        chart: { type: 'line', height: 40, width: 60, sparkline: { enabled: true } },
        stroke: { curve: 'smooth', width: 2 },
        colors: ['#EA5455'],
        tooltip: { fixed: { enabled: false }, x: { show: false }, marker: { show: false } }
    }).render();

    // --- Main Chart: Sales Analytics (Area) ---
    const salesOptions = {
        series: [
            {
                name: 'Delivered Cans',
                type: 'area', 
                data: <?php echo $sales_analytics_json; ?>
            }
        ],
        chart: {
            height: 350,
            type: 'area', 
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'Poppins'
        },
        dataLabels: { enabled: false },
        stroke: {
            curve: 'smooth',
            width: 3, 
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.6,
                opacityTo: 0.1,
                stops: [0, 90, 100],
                colorStops: [ { offset: 0, color: '#7367F0', opacity: 0.6 }, { offset: 100, color: '#7367F0', opacity: 0.1 } ]
            }
        },
        colors: ['#7367F0'], 
        xaxis: {
            categories: <?php echo $analytics_labels_json; ?>,
            title: { text: 'Day of Month', style: { color: '#B9B9C3', fontSize: '10px' } },
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
    const salesChart = new ApexCharts(document.querySelector("#salesAnalyticsChart"), salesOptions);
    salesChart.render();

    // --- Right Sidebar: Day-wise Orders (Vertical Bar Chart) ---
    const recentOptions = {
        series: [{
            name: 'Orders',
            data: <?php echo $recent_orders_counts_json; ?>
        }],
        chart: {
            height: 350,
            type: 'bar',
            toolbar: { show: false },
            fontFamily: 'Poppins'
        },
        plotOptions: {
            bar: {
                columnWidth: '50%',
                borderRadius: 7,
                distributed: true
            }
        },
        colors: ['#7367F0', '#00CFE8', '#28C76F', '#FF9F43', '#EA5455', '#7367F0', '#00CFE8'],
        dataLabels: { enabled: false },
        legend: { show: false },
        xaxis: {
            categories: <?php echo $recent_orders_days_json; ?>,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#B9B9C3' } }
        },
        yaxis: {
            labels: { style: { colors: '#B9B9C3' } }
        },
        grid: {
            borderColor: '#f0f0f0',
            strokeDashArray: 5,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } }
        }
    };
    const recentChart = new ApexCharts(document.querySelector("#recentOrdersChart"), recentOptions);
    recentChart.render();


    // --- Polling Logic ---
    let lastDashboardData = null;

    function refreshDashboard() {
        fetch('api/dashboard_data.php')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const currentDataStr = JSON.stringify(res);
                if (lastDashboardData === currentDataStr) return;
                lastDashboardData = currentDataStr;

                // Update Stats
                document.getElementById('statSales').innerText = '₹' + res.stats.total_sales;
                document.getElementById('statOrders').innerText = res.stats.total_orders;
                document.getElementById('statCustomers').innerText = res.stats.total_customers;
                document.getElementById('statBoys').innerText = res.stats.total_delivery_boys;
                document.getElementById('statReceived').innerText = '₹' + res.stats.total_received;
                document.getElementById('statPending').innerText = '₹' + res.stats.pending_bill;
                document.getElementById('statDelivered').innerText = res.stats.delivered_count;
                document.getElementById('statCancelled').innerText = res.stats.cancelled_count;

                // Update Charts
                salesChart.updateSeries([
                    { name: 'Delivered Cans', data: res.charts.sales_analytics }
                ]);

                if(res.charts.recent_orders) {
                    recentChart.updateSeries([{
                        name: 'Orders',
                        data: res.charts.recent_orders
                    }]);
                }
            }
        });
    }

    // Set polling interval (e.g., 30 seconds)
    setInterval(refreshDashboard, 30000);
});
</script>

<?php include 'includes/footer.php'; ?>
