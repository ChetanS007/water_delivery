<?php
// reports.php
require_once '../includes/db.php';
?>
<?php include 'includes/header.php'; ?>

<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-0">Reports & Analytics</h3>
        <p class="text-muted small mb-0">Comprehensive insights into consumption, billing, and supply.</p>
    </div>
    <div class="d-flex gap-2">
        <input type="date" id="startDate" class="form-control" value="<?php echo date('Y-m-01'); ?>">
        <input type="date" id="endDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        <button class="btn btn-primary" onclick="loadAllReports()"><i class="fa-solid fa-filter"></i> Filter</button>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="consumption-tab" data-bs-toggle="tab" data-bs-target="#consumption" type="button" role="tab">
            <i class="fa-solid fa-glass-water me-2"></i> Consumption & Usage
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button" role="tab">
            <i class="fa-solid fa-file-invoice-dollar me-2"></i> Billing & Revenue
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="supply-tab" data-bs-toggle="tab" data-bs-target="#supply" type="button" role="tab">
            <i class="fa-solid fa-truck-droplet me-2"></i> Supply & Distribution
        </button>
    </li>
</ul>

<div class="tab-content" id="reportTabContent">
    
    <!-- 1. Consumption Tab -->
    <div class="tab-pane fade show active" id="consumption" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Daily Water Consumption</div>
                    <div class="card-body">
                        <div id="consumptionBar"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Consumer Categories</div>
                    <div class="card-body">
                        <div id="consumptionPie"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Zone-wise Consumption</div>
                    <div class="card-body">
                        <div id="consumptionDonut"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Billing Tab -->
    <div class="tab-pane fade" id="billing" role="tabpanel">
         <div class="row g-4">
            <div class="col-lg-8">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Billed vs Collected Revenue</div>
                    <div class="card-body">
                        <div id="billingBar"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Revenue by Category</div>
                    <div class="card-body">
                        <div id="billingPie"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Payment Status (Paid vs Unpaid)</div>
                    <div class="card-body">
                        <div id="billingDonut"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Supply Tab -->
    <div class="tab-pane fade" id="supply" role="tabpanel">
         <div class="row g-4">
            <div class="col-lg-8">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Daily Water Supply Volume</div>
                    <div class="card-body">
                        <div id="supplyBar"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Supply Sources</div>
                    <div class="card-body">
                        <div id="supplyPie"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 fw-bold">Efficiency (Supplied vs Consumed)</div>
                    <div class="card-body">
                        <div id="supplyDonut"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
let charts = {};

document.addEventListener('DOMContentLoaded', loadAllReports);

function loadAllReports() {
    loadConsumption();
    loadBilling();
    loadSupply();
}

const commonOptions = {
    chart: { height: 350, fontFamily: 'Poppins, sans-serif' },
    responsive: [{ breakpoint: 480, options: { chart: { width: 300 }, legend: { position: 'bottom' } } }]
};

function fetchReport(action, callback) {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    fetch(`api/reports.php?action=${action}&start_date=${start}&end_date=${end}`)
        .then(r => r.json())
        .then(res => { if(res.success) callback(res); });
}

// 1. Consumption Charts
function loadConsumption() {
    fetchReport('consumption', (data) => {
        // Bar
        const barOpts = { ...commonOptions, series: [{ name: 'Orders', data: data.bar.map(i => i.count) }], xaxis: { categories: data.bar.map(i => i.date) }, chart: { type: 'bar', height: 300 }, colors: ['#7367F0'] };
        renderChart('#consumptionBar', barOpts, 'cBar');

        // Pie
        const pieOpts = { ...commonOptions, series: data.pie.map(i => i.count), labels: data.pie.map(i => i.customer_type), chart: { type: 'pie', height: 300 }, colors: ['#00CFE8', '#28C76F'] };
        renderChart('#consumptionPie', pieOpts, 'cPie');

        // Donut
        const donutOpts = { ...commonOptions, series: data.donut.map(i => i.value), labels: data.donut.map(i => i.label), chart: { type: 'donut', height: 300 }, colors: ['#EA5455', '#FF9F43', '#7367F0', '#28C76F'] };
        renderChart('#consumptionDonut', donutOpts, 'cDonut');
    });
}

// 2. Billing Charts
function loadBilling() {
    fetchReport('billing', (data) => {
        // Bar
        // Merge dates for comparison
        const dates = [...new Set([...data.bar.billed.map(i=>i.date), ...data.bar.collected.map(i=>i.date)])].sort();
        const billed = dates.map(d => data.bar.billed.find(i=>i.date==d)?.amount || 0);
        const collected = dates.map(d => data.bar.collected.find(i=>i.date==d)?.amount || 0);

        const barOpts = { ...commonOptions, series: [{ name: 'Billed', data: billed }, { name: 'Collected', data: collected }], xaxis: { categories: dates }, chart: { type: 'bar', height: 300 }, colors: ['#7367F0', '#28C76F'] };
        renderChart('#billingBar', barOpts, 'bBar');

        // Pie
        const pieOpts = { ...commonOptions, series: data.pie.map(i => parseFloat(i.amount)), labels: data.pie.map(i => i.customer_type), chart: { type: 'pie', height: 300 }, colors: ['#FF9F43', '#00CFE8'] };
        renderChart('#billingPie', pieOpts, 'bPie');

        // Donut
        const donutOpts = { ...commonOptions, series: data.donut.map(i => i.count), labels: data.donut.map(i => i.status_group), chart: { type: 'donut', height: 300 }, colors: ['#28C76F', '#EA5455'] };
        renderChart('#billingDonut', donutOpts, 'bDonut');
    });
}

// 3. Supply Charts
function loadSupply() {
    fetchReport('supply', (data) => {
        // Bar
        const barOpts = { ...commonOptions, series: [{ name: 'Volume (Units)', data: data.bar.map(i => i.qty) }], xaxis: { categories: data.bar.map(i => i.date) }, chart: { type: 'bar', height: 300 }, colors: ['#00CFE8'] };
        renderChart('#supplyBar', barOpts, 'sBar');

        // Pie
        const pieOpts = { ...commonOptions, series: data.pie.map(i => i.value), labels: data.pie.map(i => i.label), chart: { type: 'pie', height: 300 } };
        renderChart('#supplyPie', pieOpts, 'sPie');

        // Donut
        const donutOpts = { ...commonOptions, series: data.donut.map(i => i.value), labels: data.donut.map(i => i.label), chart: { type: 'donut', height: 300 }, colors: ['#28C76F', '#EA5455'] };
        renderChart('#supplyDonut', donutOpts, 'sDonut');
    });
}

function renderChart(selector, options, id) {
    if(charts[id]) {
        charts[id].updateOptions(options);
    } else {
        charts[id] = new ApexCharts(document.querySelector(selector), options);
        charts[id].render();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
