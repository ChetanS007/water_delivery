<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    header("Location: login.php");
    exit();
}

$boy_id = $_SESSION['user_id'];

// Get Assigned Deliveries (Initial Empty State)
$deliveries = []; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/water_delivery/assets/css/style.css">
    
    <!-- Leaflet & QR Scanner -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet Routing Machine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
    <style>
        :root {
            --brand-primary: #0E3A66;
            --brand-accent: #2DB5E8;
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        #map { height: 75vh; width: 100%; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .nav-tabs { border-bottom: none; gap: 10px; }
        .nav-tabs .nav-link { 
            border: none; 
            border-radius: 12px; 
            background: #fff; 
            color: #64748b; 
            font-weight: 500; 
            padding: 10px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .nav-tabs .nav-link.active { 
            background: var(--brand-primary); 
            color: #fff !important; 
            box-shadow: 0 4px 12px rgba(14, 58, 102, 0.2);
        }
        .stat-card { border-radius: 16px; transition: transform 0.2s; }
        .stat-card:active { transform: scale(0.95); }
        .leaflet-routing-container { max-height: 150px; overflow-y: auto; border-radius: 10px; font-size: 11px; }
        .navbar { background: linear-gradient(90deg, #0E3A66 0%, #1a324b 100%) !important; }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <span class="navbar-brand">
            <i class="fa-solid fa-motorcycle me-2"></i>
            स्वागत आहे, <?php echo htmlspecialchars($_SESSION['name'] ?? 'जोडीदार'); ?>
        </span>
        <a href="../logout.php" class="btn btn-sm btn-outline-light">बाहेर पडा</a>
    </div>
</nav>

<!-- Stats -->
    <div class="row g-3 mb-4 px-2 text-center">
        <div class="col-4">
            <div class="card stat-card text-white border-0 shadow-sm py-2" style="background: linear-gradient(135deg, #0E3A66 0%, #1a324b 100%);">
                <h6 class="small mb-1 opacity-75">एकूण कॅन</h6>
                <h3 class="fw-bold mb-0" id="statTotal">0</h3>
            </div>
        </div>
        <div class="col-4">
            <div class="card stat-card text-white border-0 shadow-sm py-2" style="background: linear-gradient(135deg, #2DB5E8 0%, #1a9fd6 100%);">
                <h6 class="small mb-1 opacity-75">पोहोचवले</h6>
                <h3 class="fw-bold mb-0" id="statDelivered">0</h3>
            </div>
        </div>
        <div class="col-4">
            <div class="card stat-card text-white border-0 shadow-sm py-2" style="background: linear-gradient(135deg, #F9A826 0%, #e09214 100%);">
                <h6 class="small mb-1 opacity-75">शिल्लक</h6>
                <h3 class="fw-bold mb-0" id="statRemaining">0</h3>
            </div>
        </div>
    </div>

<div class="container pb-5">
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="viewTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView" type="button" onclick="resetOverview()">यादी</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="map-tab" data-bs-toggle="tab" data-bs-target="#mapView" type="button" onclick="showOverviewMap()">नकाशा</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="van-tab" data-bs-toggle="tab" data-bs-target="#vanView" type="button" onclick="loadVanData()">व्हॅन तपशील</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customerView" type="button" onclick="loadCustomers()">ग्राहक</button>
        </li>
    </ul>

     
    <div class="tab-content">
        
        <!-- List View -->
        <div class="tab-pane fade show active" id="listView">
            

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">आजची डिलिव्हरी यादी (<span id="countDisplay"><?php echo count($deliveries); ?></span>)</h5>
                <small class="text-muted" id="locStatus">स्थान मिळवत आहे...</small>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="deliverySearchInput" class="form-control border-start-0 ps-0" placeholder="ग्राहक शोधा (Search customer)...">
                    </div>
                </div>
            </div>

            <div class="row g-3" id="deliveryList">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">लोड होत आहे...</span>
                    </div>
                    <p class="text-muted mt-2">नेमून दिलेली डिलिव्हरी लोड होत आहे...</p>
                </div>
            </div>
        </div>

        <!-- Map View -->
        <div class="tab-pane fade" id="mapView">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div id="map"></div>
                </div>
            </div>
            <div class="alert alert-info mt-3 small" id="mapInfo">
                <i class="fa-solid fa-info-circle me-1"></i> निळा मार्ग तुमच्या सध्याच्या ठिकाणापासून सुरू होणारा सर्वोत्तम डिलिव्हरी मार्ग दर्शवतो.
            </div>
        </div>

        <!-- Van Details View -->
        <div class="tab-pane fade" id="vanView">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary btn-sm shadow-sm" onclick="openAddVanModal()">
                    <i class="fa-solid fa-plus me-1"></i> नवीन व्हॅन पाठवा
                </button>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">व्हॅन आयडी</th>
                                    <th>जोडीदार</th>
                                    <th>एकूण</th>
                                    <th>पोहोचवले</th>
                                    <th>शिल्लक</th>
                                    <th>बाहेर पडण्याची वेळ</th>
                                    <th>परतण्याची वेळ</th>
                                    <th>कृती</th>
                                </tr>
                            </thead>
                            <tbody id="vanTableBody">
                                <tr><td colspan="6" class="text-center py-4 text-muted">लोड होत आहे...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Management View -->
        <div class="tab-pane fade" id="customerView">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">ग्राहक यादी</h5>
                <button class="btn btn-primary btn-sm shadow-sm" onclick="openAddCustomerModal()">
                    <i class="fa-solid fa-plus me-1"></i> नवीन ग्राहक जोडा
                </button>
            </div>
            
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="custSearchInput" class="form-control border-start-0 ps-0" placeholder="नाव किंवा मोबाईल नंबरने शोधा...">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">ग्राहक</th>
                                    <th>मोबाईल</th>
                                    <th>प्रकार</th>
                                    <th class="text-end pe-3">कृती</th>
                                </tr>
                            </thead>
                            <tbody id="customerTableBody">
                                <tr><td colspan="4" class="text-center py-4 text-muted">लोड होत आहे...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-2">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0" id="custPagination"></ul>
                    </nav>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Scanner Modal -->
<div class="modal fade" id="scanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ग्राहक QR स्कॅन करा</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopScan()"></button>
            </div>
            <div class="modal-body text-center">
                <div id="reader" style="width: 100%;"></div>
                <p class="text-muted mt-2">कॅमेरा ग्राहकाच्या QR कोडकडे धरा</p>
                <form id="completeForm" action="complete_delivery.php" method="POST">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                    <input type="hidden" name="order_id" id="order_id_flow">
                    <input type="hidden" name="can_received" id="can_received_input" value="0">
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Can Received Confirmation Modal -->
<div class="modal fade" id="canReceivedModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-body p-4 text-center">
                <i class="fa-solid fa-bottle-water text-primary fs-1 mb-3"></i>
                <h4 class="fw-bold mb-3">रिकामी कॅन प्राप्त झाली का?</h4>
                <p class="text-muted mb-4">(Has the water can been returned?)</p>
                
                <div id="canReturnQuestion">
                    <div class="row g-3">
                        <div class="col-6">
                            <button type="button" class="btn btn-success w-100 py-2 fw-bold rounded-pill" onclick="showCanCounter(1)">
                                <i class="fa-solid fa-check me-2"></i> हो (Yes)
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-danger w-100 py-2 fw-bold rounded-pill" onclick="confirmCanStatus(0, 0)">
                                <i class="fa-solid fa-times me-2"></i> नाही (No)
                            </button>
                        </div>
                    </div>
                </div>

                <div id="canReturnCounter" class="d-none mt-4">
                    <h6 class="fw-bold text-dark mb-3">किती कॅन परत मिळाल्या? (Return Count)</h6>
                    <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                        <button class="btn btn-outline-primary rounded-circle" style="width: 45px; height: 45px;" onclick="updateReturnQty(-1)">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <h2 class="fw-bold mb-0 mx-3" id="returnCountDisplay">0</h2>
                        <button class="btn btn-outline-primary rounded-circle" style="width: 45px; height: 45px;" onclick="updateReturnQty(1)">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary w-100 py-2 fw-bold rounded-pill" onclick="submitCanReturn()">
                            पुष्टी करा (Confirm)
                        </button>
                        <button type="button" class="btn btn-link btn-sm text-muted text-decoration-none" onclick="resetCanModal()">
                            मागे जा (Back)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Modal -->
<div class="modal fade" id="navModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="navTargetName">मार्गदर्शन (Navigation)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopNavigation()"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <div id="navMap" style="height: 100%; width: 100%;"></div>
                <div class="position-absolute bottom-0 start-0 p-3 w-100" style="z-index: 1000;">
                    <button class="btn btn-danger w-100 shadow fw-bold" data-bs-dismiss="modal" onclick="stopNavigation()">
                        नेव्हिगेशन बंद करा
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">नवीन ग्राहक जोडा</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="userForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">पूर्ण नाव <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="fullName" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ग्राहकाचा प्रकार</label>
                            <select name="customer_type" id="customerType" class="form-select">
                                <option value="Home">घर (Home)</option>
                                <option value="Shop">दुकान (Shop)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">मोबाईल नंबर <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" id="mobile" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ईमेल पत्ता</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        
                        <!-- Map Section -->
                        <div class="col-12">
                            <label class="form-label small fw-bold">स्थान निवडा (Select Location)</label>
                            <div class="input-group mb-2">
                                <input type="text" id="addressSearch" class="form-control" placeholder="परिसर किंवा ठिकाण शोधा...">
                                <button type="button" class="btn btn-outline-primary" onclick="searchCustomerLocation()">शोधा</button>
                                <button type="button" class="btn btn-success" onclick="detectCustomerLocation()" title="Detect Current Location">
                                    <i class="fa-solid fa-location-crosshairs"></i>
                                </button>
                            </div>
                            <div id="customerMap" style="height: 250px; border-radius: 8px; border: 1px solid #ccc; z-index: 1;"></div>
                            <small class="text-muted">नकाशावर क्लिक करा किंवा मार्कर फिरवून पत्ता मिळवा.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">पूर्ण पत्ता <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" class="form-control" rows="2" required></textarea>
                            <small class="text-muted">नकाशावरून स्वयंचलित भरले जाईल. आवश्यक असल्यास बदला.</small>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">शहर</label>
                            <input type="text" name="city" id="city" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">राज्य</label>
                            <input type="text" name="state" id="state" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">पिनकोड</label>
                            <input type="text" name="pincode" id="pincode" class="form-control">
                        </div>
                        
                        <!-- Hidden Lat/Long -->
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">स्थिती (Status)</label>
                            <select name="status" id="status" class="form-select">
                                <option value="1">सक्रिय (Active)</option>
                                <option value="0">निष्क्रिय (Inactive)</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">रद्द करा</button>
                <button type="button" class="btn btn-primary" onclick="submitUserForm()">ग्राहक जतन करा</button>
            </div>
        </div>
    </div>
</div>

<!-- View / QR Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-body p-0">
                <div class="bg-primary text-white p-4 text-center rounded-top position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                    <div class="avatar-circle mx-auto mb-3 bg-white text-primary display-4 fw-bold shadow" style="width: 80px; height: 80px; line-height: 80px; border-radius: 50%;">
                        <span id="viewAvatar">U</span>
                    </div>
                    <h5 class="fw-bold mb-0" id="viewName">User Name</h5>
                    <p class="opacity-75 small mb-0" id="viewMobile">Mobile</p>
                </div>
                <div class="p-4">
                    <div class="text-center mb-4">
                        <label class="small text-muted text-uppercase fw-bold mb-2">ग्राहक QR कोड</label>
                        <div id="qrcode" class="d-flex justify-content-center p-3 bg-light rounded mx-auto border" style="width: fit-content; border: 1px solid #ddd;"></div>
                        <div class="mt-3 d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="downloadQR()"><i class="fa-solid fa-download me-1"></i> डाउनलोड</button>
                            <button class="btn btn-sm btn-outline-dark" onclick="printQR()"><i class="fa-solid fa-print me-1"></i> प्रिंट</button>
                        </div>
                    </div>
                    
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">ग्राहक आयडी</span>
                            <span class="fw-bold" id="viewId">#0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">प्रकार</span>
                            <span id="viewType">Home</span>
                        </li>
                        <li class="list-group-item px-0">
                            <span class="text-muted d-block mb-1">पत्ता</span>
                            <span class="fw-medium" id="viewAddress">-</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Van Modal -->
<div class="modal fade" id="addVanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">नवीन व्हॅन पाठवा</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vanForm">
                    <input type="hidden" name="boy_id" value="<?php echo $boy_id; ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">व्हॅन क्रमांक / आयडी</label>
                        <input type="text" class="form-control" name="van_id" required placeholder="उदा. VAN-01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">प्रमाण (कॅन)</label>
                        <input type="number" class="form-control" name="quantity" required min="1" value="50">
                    </div>
                    <button type="button" class="btn btn-primary w-100 shadow-sm fw-bold" onclick="submitVanDispatch()">
                        पाठवल्याची पुष्टी करा
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let html5QrcodeScanner;
    let currentTargetQR = "";
    let currentAssignmentId = "";
    let currentOrderId = "";
    let map;
    let currentMarker;
    let routingControl;
    let currentLat = null, currentLng = null;
    let sortedOrders = []; 
    let returnQty = 0;
    let lastDeliveryData = null;

    // Navigation State
    let isNavigating = false;
    let navMap = null;
    let navRouting = null;
    let navTargetLat = null;
    let navTargetLng = null;
    let navMyMarker = null;
    let navTargetMarker = null;

    function showOverviewMap() {
        setTimeout(() => {
            initMap(currentLat, currentLng, sortedOrders);
        }, 300);
    }

    function resetOverview() {
        // No action needed for list view
    }

    document.addEventListener('DOMContentLoaded', () => {
        startTracking();
        fetchDeliveries(); // Initial Load
        
        // Search Listener
        document.getElementById('deliverySearchInput').addEventListener('input', function() {
            fetchDeliveries(this.value.trim());
        });

        // Polling (No Full Page Reload)
        setInterval(() => {
            if(!html5QrcodeScanner) { 
                const searchVal = document.getElementById('deliverySearchInput').value.trim();
                if (document.activeElement.id !== 'deliverySearchInput') {
                    fetchDeliveries(searchVal);
                }
                
                if(document.getElementById('vanView').classList.contains('active')) {
                    loadVanData(true);
                }
            }
        }, 10000); // 10 seconds for more live feeling
    });

    let watchId = null;

    function startTracking() {
        if (!navigator.geolocation) {
            document.getElementById('locStatus').innerText = "Geolocation not supported";
            return;
        }

        if (watchId) navigator.geolocation.clearWatch(watchId);

        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                currentLat = pos.coords.latitude;
                currentLng = pos.coords.longitude;
                
                // Fetch Address Name (Reverse Geocoding)
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${currentLat}&lon=${currentLng}&zoom=18&addressdetails=1`, {
                    headers: { 'Accept-Language': 'en' }
                })
                .then(r => r.json())
                .then(data => {
                    const addr = data.display_name.split(',')[0] + ', ' + (data.address.suburb || data.address.city || data.address.town || '');
                    document.getElementById('locStatus').innerHTML = `
                        <div class="d-flex flex-column align-items-end">
                            <span class="text-success fw-bold"><i class="fa-solid fa-location-dot me-1"></i> ${addr}</span>
                            <span style="font-size: 10px;" class="text-muted">Live: ${currentLat.toFixed(4)}, ${currentLng.toFixed(4)}</span>
                        </div>
                    `;
                })
                .catch(() => {
                    document.getElementById('locStatus').innerHTML = `
                        <div class="d-flex flex-column align-items-end">
                            <span class="text-success fw-bold"><i class="fa-solid fa-location-crosshairs me-1"></i> Live Tracking</span>
                            <span style="font-size: 10px;" class="text-muted">Lat: ${currentLat.toFixed(4)}, Lng: ${currentLng.toFixed(4)}</span>
                        </div>
                    `;
                });
                
                // Push to server
                updateLiveLocation(currentLat, currentLng);

                // Re-render distance badges
                if(sortedOrders.length > 0) renderDeliveries(sortedOrders);

                // Update Map Marker if map is active
                if(map && currentMarker) {
                    currentMarker.setLatLng([currentLat, currentLng]);
                }

                // Update Active Navigation
                if(isNavigating) {
                    updateNavigation(currentLat, currentLng);
                }
            },
            (err) => {
                document.getElementById('locStatus').innerHTML = `
                    <button class="btn btn-link btn-sm text-danger fw-bold p-0 text-decoration-none" onclick="startTracking()">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Location access denied. <span class="text-primary text-decoration-underline">Try again</span>
                    </button>
                `;
                console.warn("Location access denied or failed.");
            },
            { enableHighAccuracy: true }
        );
    }

    function updateLiveLocation(lat, lng) {
        const fd = new FormData();
        fd.append('lat', lat);
        fd.append('lng', lng);
        fetch('api/update_location.php', { method: 'POST', body: fd })
        .catch(err => console.error("Location update failed", err));
    }

    function fetchDeliveries(search = '') {
        const container = document.getElementById('deliveryList');
        
        fetch('api/fetch_deliveries.php?search=' + encodeURIComponent(search))
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                // Update Stats ALWAYS if they exist (or include in comparison)
                if(res.stats) {
                    document.getElementById('statTotal').innerText = res.stats.total_cans;
                    document.getElementById('statDelivered').innerText = res.stats.delivered_cans;
                    document.getElementById('statRemaining').innerText = res.stats.remaining_cans;
                }

                // State Comparison (Include stats to detect van count changes)
                const currentDataStr = JSON.stringify({ data: res.data, stats: res.stats });
                
                if(lastDeliveryData === currentDataStr) return; // Full state match
                lastDeliveryData = currentDataStr;

                renderDeliveries(res.data);
                
                // Update counter
                document.getElementById('countDisplay').innerText = res.data.length;
            } else {
                container.innerHTML = `<div class="col-12 text-center text-danger mt-5"><i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i><p>${res.message}</p></div>`;
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = `<div class="col-12 text-center text-danger mt-5"><i class="fa-solid fa-triangle-exclamation fa-2x mb-2"></i><p>Network Error: Could not load data.</p></div>`;
        });
    }

    // --- SORTING LOGIC ---
    function sortDeliveriesByDistance(items, startLat, startLng) {
        let sorted = [];
        let currentPos = { lat: startLat, lng: startLng };
        // Clone items to avoid mutating original array mid-loop
        let remaining = [...items];

        while (remaining.length > 0) {
            let nearestIndex = -1;
            let minDist = Infinity;

            for (let i = 0; i < remaining.length; i++) {
                const item = remaining[i];
                const d = getDistance(currentPos.lat, currentPos.lng, parseFloat(item.latitude), parseFloat(item.longitude));
                if (d < minDist) {
                    minDist = d;
                    nearestIndex = i;
                }
            }

            if (nearestIndex !== -1) {
                const nearest = remaining[nearestIndex];
                sorted.push(nearest);
                currentPos = { lat: parseFloat(nearest.latitude), lng: parseFloat(nearest.longitude) };
                remaining.splice(nearestIndex, 1);
            }
        }
        return sorted;
    }

    function renderDeliveries(items) {
        const container = document.getElementById('deliveryList');
        container.innerHTML = '';

        // Show Today's Dispatch List (Sorted by Distance if location available)
        let displayItems = [...items];
        
        if (currentLat && currentLng) {
            displayItems = sortDeliveriesByDistance(items, currentLat, currentLng);
        }
        
        sortedOrders = displayItems;

        if(displayItems.length === 0) {
            container.innerHTML = '<div class="col-12 text-center text-muted mt-5"><p>कोणतीही ऑर्डर नेमून दिली नाही.</p></div>';
            return;
        }

        displayItems.forEach((item, index) => {
             let distStr = "";
             if (currentLat && currentLng) {
                 const d = getDistance(currentLat, currentLng, parseFloat(item.latitude), parseFloat(item.longitude));
                 distStr = `<span class="badge bg-secondary ms-2">${d.toFixed(1)} किमी दूर</span>`;
             }


            let actionButtons = '';
            if (item.today_status === 'Delivered') {
                actionButtons = `
                    <div class="alert alert-success py-2 px-3 mb-0 text-center small fw-bold">
                        <i class="fa-solid fa-circle-check me-1"></i> आजची डिलिव्हरी पूर्ण झाली (Done)
                    </div>
                `;
            } else {
                actionButtons = `
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="startNavigation(${item.latitude}, ${item.longitude}, '${item.full_name.replace(/'/g, "\\'")}')">
                           <i class="fa-solid fa-diamond-turn-right me-1"></i> मार्ग दाखवा (Navigate)
                        </button>
                        <button class="btn btn-success btn-sm" 
                                onclick="startScan('${item.qr_code}', '${item.assignment_id || ''}', ${item.order_id})">
                            <i class="fa-solid fa-qrcode me-1"></i> स्कॅन आणि डिलिव्हर
                        </button>
                        <button class="btn btn-primary btn-sm" 
                                onclick="completeDelivery('${item.assignment_id || ''}', ${item.order_id})">
                            <i class="fa-solid fa-check me-1"></i> पोहोचवले (Delivered)
                        </button>
                    </div>
                `;
            }

            const deliveryKey = item.assignment_id || `ord_${item.order_id}`;

            const html = `
                <div class="col-md-6 col-lg-4 delivery-item" 
                     data-id="${deliveryKey}">
                    <div class="card shadow-sm border-0 h-100 ${item.today_status === 'Delivered' ? 'opacity-75' : ''}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">${index + 1}. ${item.full_name}</h6>
                                    <small class="text-muted">${item.product_name} x ${item.quantity}</small>
                                </div>
                                ${distStr}
                            </div>
                            
                            <p class="small text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i> ${item.address}</p>
                            
                            ${item.today_status !== 'Delivered' ? `
                            <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded">
                                <span class="small fw-bold">नगाची संख्या:</span>
                                <div class="input-group input-group-sm" style="width: 100px;">
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQty('${deliveryKey}', -1)">-</button>
                                    <span class="form-control text-center fw-bold" id="qty_${deliveryKey}">${item.quantity}</span>
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQty('${deliveryKey}', 1)">+</button>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${actionButtons}
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML += html;
        });
        
        // Update map if it's already open (optional, but good practice)
        if(map && document.getElementById('mapView').classList.contains('active')) {
             initMap(currentLat, currentLng, sortedOrders);
        }
    }

    function initMap(myLat, myLng, orders) {
        if (!map) {
            const center = myLat ? [myLat, myLng] : [20.5937, 78.9629]; 
            map = L.map('map').setView(center, 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }

        // Add or Update My Location Marker
        if (myLat && myLng) {
            const myIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
            });

            if (currentMarker) {
                currentMarker.setLatLng([myLat, myLng]);
            } else {
                currentMarker = L.marker([myLat, myLng], { icon: myIcon }).addTo(map).bindPopup("<b>Your Location</b>");
            }
        }

        // Clear existing route
        if (routingControl) {
            map.removeControl(routingControl);
        }

        if (!myLat || orders.length === 0) return;

        // Prepare waypoints
        let waypoints = [L.latLng(myLat, myLng)];
        orders.forEach(o => {
            if(o.latitude && o.longitude) {
                waypoints.push(L.latLng(parseFloat(o.latitude), parseFloat(o.longitude)));
            }
        });

        if(waypoints.length < 2) return;

        routingControl = L.Routing.control({
            waypoints: waypoints,
            lineOptions: {
                styles: [{color: '#0d6efd', opacity: 0.7, weight: 5}]
            },
            createMarker: function(i, wp, nWps) {
                if (i === 0) return currentMarker; // Don't recreate my marker

                let popupText = `<b>${i}.</b> ${orders[i-1].full_name}<br><small>${orders[i-1].address}</small>`;
                const custIcon = L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                });

                return L.marker(wp.latLng, { icon: custIcon }).bindPopup(popupText);
            },
            addWaypoints: false,
            draggableWaypoints: false,
            show: false
        }).addTo(map);
    }
    
    function optimizeRoute(lat, lng) {
        // Rerender to sort
        // We need to fetch/store data globally first or just use what we have
        // But renderDeliveries handles sorting if currentLat is set.
        // So just re-calling render with current data is enough if we have it?
        // We fetch data in initApp calls via fetchDeliveries.
        // Let's manually trigger a refetch or re-render if we have data.
        fetchDeliveries(); 
    }

    function getDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; 
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                  Math.sin(dLon/2) * Math.sin(dLon/2); 
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        return R * c;
    }

    function deg2rad(deg) { return deg * (Math.PI/180); }

    // Scanner Functions
    function startScan(targetQR, assignmentId, orderId = null) {
        currentTargetQR = targetQR;
        currentAssignmentId = assignmentId;
        currentOrderId = orderId;
        const modal = new bootstrap.Modal(document.getElementById('scanModal'));
        modal.show();
        
        document.getElementById('scanModal').addEventListener('shown.bs.modal', function () {
            if(!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
                html5QrcodeScanner.render(onScanSuccess);
            }
        });
    }

    function updateQty(key, delta) {
        const span = document.getElementById('qty_' + key);
        let current = parseInt(span.innerText);
        current += delta;
        if (current < 1) current = 1;
        span.innerText = current;
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (decodedText === currentTargetQR) {
             html5QrcodeScanner.clear();
             const scanModal = bootstrap.Modal.getInstance(document.getElementById('scanModal'));
             if(scanModal) scanModal.hide();
             
             document.getElementById('assignment_id').value = currentAssignmentId || '';
             document.getElementById('order_id_flow').value = currentOrderId || '';
             showCanReceivedModal();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'चुकीचा QR कोड',
                text: 'कृपया योग्य ग्राहकाचा कोड स्कॅन करा.',
                confirmButtonText: 'ठीक आहे'
            });
        }
    }

    function completeDelivery(assignmentId, orderId = null) {
        document.getElementById('assignment_id').value = assignmentId || '';
        document.getElementById('order_id_flow').value = orderId || '';
        showCanReceivedModal();
    }

    function showCanReceivedModal() {
        resetCanModal();
        const modal = new bootstrap.Modal(document.getElementById('canReceivedModal'));
        modal.show();
    }

    function showCanCounter(status) {
        document.getElementById('can_received_input').value = status;
        document.getElementById('canReturnQuestion').classList.add('d-none');
        document.getElementById('canReturnCounter').classList.remove('d-none');
    }

    function updateReturnQty(delta) {
        returnQty += delta;
        if (returnQty < 0) returnQty = 0;
        document.getElementById('returnCountDisplay').innerText = returnQty;
    }

    function resetCanModal() {
        returnQty = 0;
        document.getElementById('returnCountDisplay').innerText = "0";
        document.getElementById('canReturnQuestion').classList.remove('d-none');
        document.getElementById('canReturnCounter').classList.add('d-none');
    }

    function submitCanReturn() {
        const status = document.getElementById('can_received_input').value;
        confirmCanStatus(status, returnQty);
    }

    function confirmCanStatus(status, count = 0) {
        document.getElementById('can_received_input').value = status;
        
        const assignmentId = document.getElementById('assignment_id').value;
        const orderId = document.getElementById('order_id_flow').value;
        const key = assignmentId || 'ord_' + orderId;
        const qty = document.getElementById('qty_' + key).innerText;

        const fd = new FormData();
        fd.append('assignment_id', assignmentId);
        fd.append('order_id', orderId);
        fd.append('can_received', status);
        fd.append('return_can_count', count);
        fd.append('quantity', qty);

        // Hide Modal if open
        const modalEl = document.getElementById('canReceivedModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if(modal) modal.hide();

        fetch('complete_delivery.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                Swal.fire({
                    icon: res.already_done ? 'info' : 'success',
                    title: res.already_done ? 'माहिती' : 'यशस्वी',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                fetchDeliveries(); // Dynamically update the list
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'त्रुटी!',
                    text: res.message,
                    confirmButtonText: 'ठीक आहे'
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'नेटवर्क त्रुटी', text: 'डिलिव्हरी सबमिट करता आली नाही.' });
        });
    }

    function stopScan() {
        if(html5QrcodeScanner) {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
        }
    }

    // --- NAVIGATION LOGIC ---
    function startNavigation(targetLat, targetLng, targetName) {
        if (!currentLat || !currentLng) {
            Swal.fire({
                icon: 'info',
                title: 'प्रतीक्षा करा',
                text: 'तुमचे सध्याचे स्थान मिळण्याची प्रतीक्षा करत आहे. कृपया GPS सुरू असल्याची खात्री करा.',
                confirmButtonText: 'ठीक आहे'
            });
            return;
        }

        navTargetLat = targetLat;
        navTargetLng = targetLng;
        document.getElementById('navTargetName').innerText = "येथे जात आहे: " + targetName;
        
        isNavigating = true;
        const modal = new bootstrap.Modal(document.getElementById('navModal'));
        modal.show();

        document.getElementById('navModal').addEventListener('shown.bs.modal', function () {
            if (!navMap) {
                navMap = L.map('navMap').setView([currentLat, currentLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OSM'
                }).addTo(navMap);
            } else {
                navMap.invalidateSize();
            }
            updateNavigation(currentLat, currentLng);
        }, { once: true });
    }

    function updateNavigation(myLat, myLng) {
        if (!isNavigating || !navMap) return;

        // Update markers
        const myIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        const targetIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        if (navMyMarker) {
            navMyMarker.setLatLng([myLat, myLng]);
        } else {
            navMyMarker = L.marker([myLat, myLng], { icon: myIcon }).addTo(navMap).bindPopup("You");
        }

        if (navTargetMarker) {
            navTargetMarker.setLatLng([navTargetLat, navTargetLng]);
        } else {
            navTargetMarker = L.marker([navTargetLat, navTargetLng], { icon: targetIcon }).addTo(navMap).bindPopup("Customer");
        }

        // Update Routing
        if (navRouting) {
            navRouting.setWaypoints([
                L.latLng(myLat, myLng),
                L.latLng(navTargetLat, navTargetLng)
            ]);
        } else {
            navRouting = L.Routing.control({
                waypoints: [
                    L.latLng(myLat, myLng),
                    L.latLng(navTargetLat, navTargetLng)
                ],
                lineOptions: {
                    styles: [{color: '#0d6efd', opacity: 0.8, weight: 6}]
                },
                createMarker: function() { return null; }, // Use our custom markers
                addWaypoints: false,
                draggableWaypoints: false,
                show: false
            }).addTo(navMap);
        }
        
        // Auto-center (optional, maybe just follow)
        // navMap.setView([myLat, myLng]);
    }

    function stopNavigation() {
        isNavigating = false;
        if (navRouting) {
            navMap.removeControl(navRouting);
            navRouting = null;
        }
        if (navMyMarker) {
            navMap.removeLayer(navMyMarker);
            navMyMarker = null;
        }
        if (navTargetMarker) {
            navMap.removeLayer(navTargetMarker);
            navTargetMarker = null;
        }
    }

    // --- VAN MANAGEMENT LOGIC ---
    let lastVanData = null;
    function loadVanData(isPoll = false) {
        const tbody = document.getElementById('vanTableBody');
        if(!isPoll) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>';
            lastVanData = null; // Force re-render on manual load
        }

        fetch('../admin/api/van_management.php?action=fetch_logs')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                // Filter for current delivery boy ONLY
                const myLogs = res.data.filter(log => log.delivery_boy_id == "<?php echo $boy_id; ?>");
                
                const currentDataStr = JSON.stringify(myLogs);
                if (isPoll && lastVanData === currentDataStr) return;
                lastVanData = currentDataStr;
                
                if(isPoll) fetchDeliveries(); // Keep stats in sync

                if(myLogs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No records found.</td></tr>';
                    return;
                }

                let html = '';
                myLogs.forEach(van => {
                    const outTime = van.out_time ? new Date(van.out_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-';
                    const inTime = van.in_time ? new Date(van.in_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : '-';
                    const delivered = parseInt(van.delivered_count) || 0;
                    const total = parseInt(van.quantity) || 0;
                    const remaining = total - delivered;

                    let actionBtn = '';
                    if (van.status === 'Pending') {
                        actionBtn = `<button class="btn btn-xs btn-primary py-0 px-2" style="font-size: 11px;" onclick="markVanOut(${van.id})">Out</button>`;
                    } else if (van.status === 'Out') {
                        actionBtn = `<button class="btn btn-xs btn-warning py-0 px-2" style="font-size: 11px;" onclick="markVanIn(${van.id})">In</button>`;
                    } else {
                        actionBtn = `<span class="badge bg-success">Done</span>`;
                    }
                    
                    html += `
                        <tr>
                            <td class="ps-3 fw-bold">${van.van_id}</td>
                            <td><small>${van.boy_name || 'Me'}</small></td>
                            <td>${total}</td>
                            <td><span class="text-success fw-bold">${delivered}</span></td>
                            <td><span class="text-danger fw-bold">${remaining}</span></td>
                            <td><small>${outTime}</small></td>
                            <td><small>${inTime}</small></td>
                            <td>${actionBtn}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            }
        });
    }

    function markVanOut(id) {
        Swal.fire({
            title: 'डिलिव्हरी सुरू करायची का?',
            text: 'स्थिती OUT मध्ये बदलली जाईल.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'हो, सुरू करा',
            cancelButtonText: 'रद्द करा'
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('id', id);
                fetch('../admin/api/van_management.php?action=mark_out', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        lastVanData = null; 
                        loadVanData();
                        fetchDeliveries();
                    }
                });
            }
        });
    }

    function markVanIn(id) {
        Swal.fire({
            title: 'डिलिव्हरी पूर्ण झाली का?',
            text: 'स्थिती IN मध्ये बदलली जाईल.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'हो, पूर्ण झाली',
            cancelButtonText: 'रद्द करा'
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('id', id);
                fetch('../admin/api/van_management.php?action=mark_in', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if(res.success) {
                        lastVanData = null; 
                        loadVanData();
                        fetchDeliveries();
                    }
                });
            }
        });
    }

    // --- FORM SUBMISSION ---
    function openAddVanModal() {
        new bootstrap.Modal(document.getElementById('addVanModal')).show();
    }

    function submitVanDispatch() {
        const form = document.getElementById('vanForm');
        const fd = new FormData(form);
        
        // Use the same API as Admin
        fetch('../admin/api/van_management.php?action=add_van', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                bootstrap.Modal.getInstance(document.getElementById('addVanModal')).hide();
                form.reset();
                lastVanData = null; 
                loadVanData();
                fetchDeliveries();
                Swal.fire({
                    icon: 'success',
                    title: 'यशस्वी!',
                    text: 'व्हॅन यशस्वीरित्या पाठवली!',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'त्रुटी!',
                    text: res.message || "व्हॅन जोडताना त्रुटी आली"
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'सिस्टम त्रुटी',
                text: 'व्हॅन पाठवता आली नाही.'
            });
        });
    }
    // --- CUSTOMER MANAGEMENT LOGIC ---
    let customerMap, customerMarker, custDebounceTimer;
    let lastCustomerData = null;
    let custSearchTimeout = null;

    function loadCustomers(page = 1, isPoll = false) {
        const search = document.getElementById('custSearchInput').value;
        const tbody = document.getElementById('customerTableBody');
        const pagination = document.getElementById('custPagination');
        
        if(!isPoll) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr>';
            lastCustomerData = null;
        }

        fetch(`api/users.php?action=fetch_all&page=${page}&search=${encodeURIComponent(search)}`)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const currentDataStr = JSON.stringify(res.data);
                if (isPoll && lastCustomerData === currentDataStr) return;
                lastCustomerData = currentDataStr;

                tbody.innerHTML = '';
                if(res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">ग्राहक सापडले नाहीत.</td></tr>';
                    pagination.innerHTML = '';
                    return;
                }
                
                res.data.forEach((user) => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle text-center me-2 text-primary fw-bold" style="width: 30px; height: 30px; line-height: 30px; font-size: 0.8rem;">
                                        ${user.full_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium text-dark">${user.full_name}</span>
                                        <small class="text-muted" style="font-size: 0.7rem;">${user.customer_type}</small>
                                    </div>
                                </div>
                            </td>
                            <td><small>${user.mobile}</small></td>
                            <td><span class="badge border text-dark" style="font-size: 0.7rem;">${user.customer_type}</span></td>
                            <td class="text-end pe-3">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-white border" onclick="viewUser(${user.id})" title="QR Code"><i class="fa-solid fa-qrcode text-dark"></i></button>
                                    <button class="btn btn-sm btn-white border" onclick="editUser(${user.id})" title="Edit"><i class="fa-solid fa-pen text-primary"></i></button>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                pagination.innerHTML = '';
                if(res.pagination.pages > 1) {
                    for(let i = 1; i <= res.pagination.pages; i++) {
                        const active = i === res.pagination.page ? 'active' : '';
                        pagination.innerHTML += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="loadCustomers(${i})">${i}</a></li>`;
                    }
                }
            }
        });
    }

    document.getElementById('custSearchInput').addEventListener('keyup', function() {
        clearTimeout(custSearchTimeout);
        custSearchTimeout = setTimeout(() => loadCustomers(1), 500);
    });

    function openAddCustomerModal() {
        document.getElementById('userForm').reset();
        document.getElementById('formAction').value = 'create';
        document.getElementById('userId').value = '';
        document.getElementById('modalTitle').innerText = 'नवीन ग्राहक जोडा';
        
        const modal = new bootstrap.Modal(document.getElementById('userModal'));
        modal.show();
        
        document.getElementById('userModal').addEventListener('shown.bs.modal', function () {
            initCustomerMap();
            detectCustomerLocation(true);
        }, { once: true });
    }

    function initCustomerMap() {
        if (customerMap) { customerMap.invalidateSize(); return; }
        const defaultLat = 20.5937; 
        const defaultLng = 78.9629; 

        customerMap = L.map('customerMap').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: 'OSM' }).addTo(customerMap);
        customerMarker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(customerMap);

        customerMap.on('click', function(e) { updateCustomerMarker(e.latlng.lat, e.latlng.lng); });
        customerMarker.on('dragend', function(e) { const c = e.target.getLatLng(); updateCustomerMarker(c.lat, c.lng); });
    }

    function updateCustomerMarker(lat, lng) {
        if(!customerMap || !customerMarker) return;
        customerMarker.setLatLng([lat, lng]);
        document.getElementById('latitude').value = parseFloat(lat).toFixed(6);
        document.getElementById('longitude').value = parseFloat(lng).toFixed(6);

        clearTimeout(custDebounceTimer);
        document.getElementById('address').placeholder = "पत्ता मिळवत आहे...";
        custDebounceTimer = setTimeout(() => fetchCustomerAddress(lat, lng), 1000);
    }

    function fetchCustomerAddress(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
            headers: { 'Accept-Language': 'en' }
        })
        .then(r => r.json())
        .then(data => {
            if(data && data.display_name) {
                document.getElementById('address').value = data.display_name; 
                const addr = data.address;
                document.getElementById('city').value = addr.city || addr.town || addr.village || '';
                document.getElementById('state').value = addr.state || '';
                document.getElementById('pincode').value = addr.postcode || '';
            }
        });
    }

    function searchCustomerLocation() {
        const q = document.getElementById('addressSearch').value;
        if(!q) return;
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`)
        .then(r => r.json())
        .then(data => {
            if(data.length > 0) {
                customerMap.setView([data[0].lat, data[0].lon], 16);
                updateCustomerMarker(data[0].lat, data[0].lon);
            }
        });
    }

    function detectCustomerLocation(isAuto = false) {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(pos => {
            if(customerMap) {
                customerMap.setView([pos.coords.latitude, pos.coords.longitude], 18);
                updateCustomerMarker(pos.coords.latitude, pos.coords.longitude);
            }
        }, err => {
            if(!isAuto) Swal.fire({ icon: 'warning', title: 'Location Error', text: "Unable to retrieve your location" });
        });
    }

    function submitUserForm() {
        const form = document.getElementById('userForm');
        if(!form.checkValidity()) { form.reportValidity(); return; }
        const fd = new FormData(form);
        fetch('api/users.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
                loadCustomers();
                Swal.fire({ icon: 'success', title: 'यशस्वी', text: res.message, timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'त्रुटी', text: res.message });
            }
        });
    }

    function editUser(id) {
        fetch(`api/users.php?action=fetch_one&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const u = res.data;
                document.getElementById('userId').value = u.id;
                document.getElementById('fullName').value = u.full_name;
                document.getElementById('mobile').value = u.mobile;
                document.getElementById('email').value = u.email;
                document.getElementById('customerType').value = u.customer_type;
                document.getElementById('address').value = u.address;
                document.getElementById('city').value = u.city || '';
                document.getElementById('state').value = u.state || '';
                document.getElementById('pincode').value = u.pincode || '';
                document.getElementById('latitude').value = u.latitude || '';
                document.getElementById('longitude').value = u.longitude || '';
                document.getElementById('status').value = u.status;
                
                document.getElementById('formAction').value = 'update';
                document.getElementById('modalTitle').innerText = 'ग्राहक माहिती बदला';
                
                const modal = new bootstrap.Modal(document.getElementById('userModal'));
                modal.show();

                document.getElementById('userModal').addEventListener('shown.bs.modal', function () {
                    initCustomerMap();
                    if(u.latitude && u.longitude) {
                        const lat = parseFloat(u.latitude);
                        const lng = parseFloat(u.longitude);
                        customerMap.setView([lat, lng], 16);
                        customerMarker.setLatLng([lat, lng]);
                    }
                }, { once: true });
            }
        });
    }

    function viewUser(id) {
        fetch(`api/users.php?action=fetch_one&id=${id}`)
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const u = res.data;
                document.getElementById('viewName').innerText = u.full_name;
                document.getElementById('viewMobile').innerText = u.mobile;
                document.getElementById('viewId').innerText = '#' + u.id;
                document.getElementById('viewType').innerText = u.customer_type;
                document.getElementById('viewAddress').innerText = u.address;
                document.getElementById('viewAvatar').innerText = u.full_name.charAt(0).toUpperCase();
                
                const qrContainer = document.getElementById('qrcode');
                qrContainer.innerHTML = '';
                new QRCode(qrContainer, { text: u.mobile, width: 128, height: 128 });
                
                new bootstrap.Modal(document.getElementById('viewUserModal')).show();
            }
        });
    }

    function downloadQR() {
        const link = document.createElement('a');
        const img = document.getElementById('qrcode').querySelector('img');
        if(!img) return;
        link.href = img.src;
        link.download = `QR_${document.getElementById('viewName').innerText.replace(/\s+/g, '_')}.png`;
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }

    function printQR() {
        const img = document.getElementById('qrcode').querySelector('img');
        if(!img) return;
        const imgData = img.src;
        const name = document.getElementById('viewName').innerText;
        const id = document.getElementById('viewId').innerText;
        const win = window.open('', '', 'height=600,width=600');
        win.document.write(`<html><head><title>Print QR</title><style>body{font-family:sans-serif;text-align:center;padding:20px}.card{border:2px solid #333;padding:20px;display:inline-block;border-radius:10px}h2{margin:10px 0;color:#333}p{font-size:14px;color:#666;margin:0}img{margin-top:10px;max-width:100%;border:1px solid #eee}</style></head><body><div class="card"><h2>Sudha Jal Delivery</h2><p>Customer: <strong>${name}</strong></p><p>ID: <strong>${id}</strong></p><img src="${imgData}" width="200" /><p style="margin-top:10px">Scan to Deliver</p></div></body></html>`);
        win.document.close(); win.print();
    }
</script>
</body>
</html>
