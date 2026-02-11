<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

$action = $_GET['action'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

switch($action) {
    case 'consumption':
        getConsumptionData($pdo, $startDate, $endDate);
        break;
    case 'billing':
        getBillingData($pdo, $startDate, $endDate);
        break;
    case 'supply':
        getSupplyData($pdo, $startDate, $endDate);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getConsumptionData($pdo, $start, $end) {
    // 1. Bar Chart: Consumption by Date
    $daily = $pdo->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY date ORDER BY date");
    $daily->execute(["$start 00:00:00", "$end 23:59:59"]);
    $dailyData = $daily->fetchAll(PDO::FETCH_ASSOC);

    // 2. Pie Chart: By Consumer Type
    $type = $pdo->prepare("SELECT u.customer_type, COUNT(*) as count FROM orders o JOIN users u ON o.user_id = u.id WHERE o.created_at BETWEEN ? AND ? GROUP BY u.customer_type");
    $type->execute(["$start 00:00:00", "$end 23:59:59"]);
    $typeData = $type->fetchAll(PDO::FETCH_ASSOC);

    // 3. Donut Chart: By Zone (Mocking Zones based on address or random for demo)
    // In a real app, we'd have a zone_id. Here we'll simulate.
    $zones = [
        ['label' => 'North Zone', 'value' => rand(30, 100)],
        ['label' => 'South Zone', 'value' => rand(30, 100)],
        ['label' => 'East Zone', 'value' => rand(30, 100)],
        ['label' => 'West Zone', 'value' => rand(30, 100)],
    ];

    echo json_encode([
        'success' => true,
        'bar' => $dailyData,
        'pie' => $typeData,
        'donut' => $zones
    ]);
}

function getBillingData($pdo, $start, $end) {
    // 1. Bar Chart: Billed vs Collected
    // Billed = All orders, Collected = Delivered orders
    $billed = $pdo->prepare("SELECT DATE(created_at) as date, SUM(total_amount) as amount FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY date ORDER BY date");
    $billed->execute(["$start 00:00:00", "$end 23:59:59"]);
    $billedData = $billed->fetchAll(PDO::FETCH_ASSOC);

    $collected = $pdo->prepare("SELECT DATE(created_at) as date, SUM(total_amount) as amount FROM orders WHERE status = 'Delivered' AND created_at BETWEEN ? AND ? GROUP BY date ORDER BY date");
    $collected->execute(["$start 00:00:00", "$end 23:59:59"]);
    $collectedData = $collected->fetchAll(PDO::FETCH_ASSOC);

    // 2. Pie Chart: Revenue by Category
    $revenue = $pdo->prepare("SELECT u.customer_type, SUM(o.total_amount) as amount FROM orders o JOIN users u ON o.user_id = u.id WHERE o.created_at BETWEEN ? AND ? GROUP BY u.customer_type");
    $revenue->execute(["$start 00:00:00", "$end 23:59:59"]);
    $revenueData = $revenue->fetchAll(PDO::FETCH_ASSOC);

    // 3. Donut: Paid vs Unpaid
    $status = $pdo->prepare("
        SELECT 
            CASE WHEN status = 'Delivered' THEN 'Paid' ELSE 'Unpaid' END as status_group,
            COUNT(*) as count 
        FROM orders 
        WHERE created_at BETWEEN ? AND ? 
        GROUP BY status_group
    ");
    $status->execute(["$start 00:00:00", "$end 23:59:59"]);
    $statusData = $status->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'bar' => ['billed' => $billedData, 'collected' => $collectedData],
        'pie' => $revenueData,
        'donut' => $statusData
    ]);
}

function getSupplyData($pdo, $start, $end) {
    // 1. Bar Chart: Supply Volume (Sum of Quantities)
    $supply = $pdo->prepare("
        SELECT DATE(o.created_at) as date, SUM(oi.quantity) as qty 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.created_at BETWEEN ? AND ? 
        GROUP BY date 
        ORDER BY date
    ");
    $supply->execute(["$start 00:00:00", "$end 23:59:59"]);
    $supplyData = $supply->fetchAll(PDO::FETCH_ASSOC);

    // 2. Pie Chart: Sources (Mock)
    $sources = [
        ['label' => 'River', 'value' => 45],
        ['label' => 'Borewell', 'value' => 30],
        ['label' => 'Tanker', 'value' => 15],
        ['label' => 'Rainwater', 'value' => 10]
    ];

    // 3. Donut: Supplied vs Consumed (Efficiency - Mock or real)
    // Assuming Supplied is slightly higher than consumed (waste/loss)
    $totalConsumed = 0;
    foreach($supplyData as $d) $totalConsumed += $d['qty'];
    
    $efficiency = [
        ['label' => 'Consumed', 'value' => $totalConsumed],
        ['label' => 'Loss/Wastage', 'value' => round($totalConsumed * 0.1)] // 10% loss
    ];

    echo json_encode([
        'success' => true,
        'bar' => $supplyData,
        'pie' => $sources,
        'donut' => $efficiency
    ]);
}
?>
