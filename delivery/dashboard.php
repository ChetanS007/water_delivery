<?php
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Delivery') {
    header("Location: login.php");
    exit();
}

$boy_id = $_SESSION['user_id'];

// Get Assigned Deliveries
$sql = "SELECT da.id as assignment_id, o.id as order_id, u.full_name, u.address, u.latitude, u.longitude, u.qr_code, o.total_amount, da.delivery_status 
        FROM delivery_assignments da
        JOIN orders o ON da.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE da.delivery_boy_id = ? AND da.delivery_status = 'Pending'
        ORDER BY da.assigned_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$boy_id]);
$deliveries = $stmt->fetchAll();
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
        #map { height: 75vh; width: 100%; border-radius: 10px; }
        .nav-tabs .nav-link { color: #495057; }
        .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd; }
        .leaflet-routing-container { max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <span class="navbar-brand"><i class="fa-solid fa-motorcycle me-2"></i>Delivery Partner</span>
        <a href="/water_delivery/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
</nav>

<div class="container pb-5">
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="viewTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="list-tab" data-bs-toggle="tab" data-bs-target="#listView" type="button" onclick="resetOverview()">List View</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="map-tab" data-bs-toggle="tab" data-bs-target="#mapView" type="button" onclick="showOverviewMap()">Map View</button>
        </li>
    </ul>

    <div class="tab-content">
        
        <!-- List View -->
        <div class="tab-pane fade show active" id="listView">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Assigned Deliveries (<span id="countDisplay"><?php echo count($deliveries); ?></span>)</h5>
                <small class="text-muted" id="locStatus">Getting location...</small>
            </div>

            <div class="row g-3" id="deliveryList">
                <?php foreach ($deliveries as $item): ?>
                <div class="col-md-6 col-lg-4 delivery-item" 
                     data-id="<?php echo $item['assignment_id']; ?>"
                     data-lat="<?php echo $item['latitude']; ?>" 
                     data-lng="<?php echo $item['longitude']; ?>"
                     data-name="<?php echo htmlspecialchars($item['full_name']); ?>">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold text-dark"><?php echo htmlspecialchars($item['full_name']); ?></h6>
                                <span class="badge bg-primary rounded-pill">₹<?php echo $item['total_amount']; ?></span>
                            </div>
                            <p class="small text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i> <?php echo htmlspecialchars($item['address']); ?></p>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="startNavigation(<?php echo $item['latitude']; ?>, <?php echo $item['longitude']; ?>, '<?php echo addslashes($item['full_name']); ?>')">
                                   <i class="fa-solid fa-diamond-turn-right me-1"></i> Navigate
                                </button>
                                <button class="btn btn-success btn-sm" 
                                        onclick="startScan('<?php echo $item['qr_code']; ?>', <?php echo $item['assignment_id']; ?>)">
                                    <i class="fa-solid fa-qrcode me-1"></i> Scan & Deliver
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(count($deliveries) == 0): ?>
                    <div class="col-12 text-center text-muted mt-5">
                        <p>No pending deliveries assigned.</p>
                    </div>
                <?php endif; ?>
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
                <i class="fa-solid fa-info-circle me-1"></i> The blue route shows the optimized delivery sequence starting from your current location.
            </div>
        </div>

    </div>
</div>

<!-- Scanner Modal -->
<div class="modal fade" id="scanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan Customer QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopScan()"></button>
            </div>
            <div class="modal-body text-center">
                <div id="reader" style="width: 100%;"></div>
                <p class="text-muted mt-2">Point camera at customer's QR Code</p>
                <form id="completeForm" action="complete_delivery.php" method="POST">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let html5QrcodeScanner;
    let currentTargetQR = "";
    let currentAssignmentId = "";
    let map;
    let routingControl;
    let currentLat, currentLng;
    let sortedOrders = []; 

    document.addEventListener('DOMContentLoaded', () => {
        initApp();
    });

    function initApp() {
        if (navigator.geolocation) {
            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    currentLat = pos.coords.latitude;
                    currentLng = pos.coords.longitude;
                    document.getElementById('locStatus').innerText = "Location active";
                    
                    optimizeRoute(currentLat, currentLng);
                }, 
                (err) => {
                    console.warn("High accuracy failed, trying low accuracy...", err);
                    
                    // Fallback to Low Accuracy
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            currentLat = pos.coords.latitude;
                            currentLng = pos.coords.longitude;
                            document.getElementById('locStatus').innerText = "Location active (Low Accuracy)";
                            optimizeRoute(currentLat, currentLng);
                        },
                        (err2) => {
                            console.error("Location error:", err2);
                            let msg = "Location access unavailable.";
                            
                            if (err2.code === 1) msg = "Location permission denied.";
                            else if (err2.code === 2) msg = "Position unavailable.";
                            else if (err2.code === 3) msg = "Location request timed out.";
                            
                            document.getElementById('locStatus').innerText = msg;
                            
                            // Only alert if specifically denied
                            if (err2.code === 1) {
                                alert("Please enable location services in your browser settings to use navigation features.");
                            }
                            
                            // Default Fallback for Demo/Testing
                            currentLat = 20.5937; 
                            currentLng = 78.9629;
                            initMap(currentLat, currentLng, []); 
                        },
                        { enableHighAccuracy: false, timeout: 20000, maximumAge: 0 }
                    );
                },
                options
            );
        } else {
            document.getElementById('locStatus').innerText = "Geolocation not supported";
        }
    }

    function optimizeRoute(startLat, startLng) {
        const container = document.getElementById('deliveryList');
        let items = Array.from(container.getElementsByClassName('delivery-item'));
        
        let sortedItems = [];
        let currentPos = { lat: startLat, lng: startLng };
        let remaining = items.map(item => ({
            element: item,
            lat: parseFloat(item.getAttribute('data-lat')),
            lng: parseFloat(item.getAttribute('data-lng')),
            id: item.getAttribute('data-id'),
            name: item.getAttribute('data-name')
        }));

        while (remaining.length > 0) {
            let nearestIndex = -1;
            let minDist = Infinity;

            for (let i = 0; i < remaining.length; i++) {
                const d = getDistance(currentPos.lat, currentPos.lng, remaining[i].lat, remaining[i].lng);
                if (d < minDist) {
                    minDist = d;
                    nearestIndex = i;
                }
            }

            if (nearestIndex !== -1) {
                const nearest = remaining[nearestIndex];
                sortedItems.push(nearest);
                currentPos = { lat: nearest.lat, lng: nearest.lng };
                remaining.splice(nearestIndex, 1);
            }
        }

        container.innerHTML = '';
        sortedItems.forEach(obj => container.appendChild(obj.element));
        sortedOrders = sortedItems;
    }

    // Initialize Map for Overview (All Orders)
    function initMap(myLat, myLng, orders) {
        if (map) {
            map.remove();
            map = null;
        }

        const center = myLat ? [myLat, myLng] : [20.5937, 78.9629]; 
        map = L.map('map').setView(center, 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        if (!myLat) return;

        // My Location
        const myIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        L.marker([myLat, myLng], {icon: myIcon}).addTo(map)
            .bindPopup("<b>You are here</b>").openPopup();

        // Orders
        const orderIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const routePoints = [[myLat, myLng]];

        orders.forEach((o, index) => {
            L.marker([o.lat, o.lng], {icon: orderIcon}).addTo(map)
                .bindPopup(`<b>${index + 1}. ${o.name}</b>`);
            routePoints.push([o.lat, o.lng]);
        });

        if (orders.length > 0) {
            L.polyline(routePoints, {color: 'blue', weight: 4, opacity: 0.7, dashArray: '10, 10'}).addTo(map);
            const bounds = L.latLngBounds(routePoints);
            map.fitBounds(bounds, {padding: [50, 50]});
        }
    }

    // Triggered when clicking "Map View" tab directly
    function showOverviewMap() {
        setTimeout(() => {
            initMap(currentLat, currentLng, sortedOrders);
            document.getElementById('mapInfo').innerHTML = '<i class="fa-solid fa-info-circle me-1"></i> Overview of all assigned deliveries sorted by distance.';
        }, 200);
    }

    function resetOverview() {
        // Just a placeholder if needed when switching back to list
    }

    function invalidateMap() {
        setTimeout(() => { if(map) map.invalidateSize(); }, 200);
    }

    // Start Individual Navigation
    function startNavigation(destLat, destLng, destName) {
        if (!currentLat || !currentLng) {
            alert("Waiting for current location...");
            return;
        }

        // Switch to map tab
        document.getElementById('map-tab').click();
        
        // Wait for tab switch
        setTimeout(() => {
            if (map) {
                map.remove();
                map = null;
            }

            map = L.map('map');

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Routing Control
            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(currentLat, currentLng),
                    L.latLng(destLat, destLng)
                ],
                routeWhileDragging: false,
                geocoder: L.Control.Geocoder ? L.Control.Geocoder.nominatim() : null,
                createMarker: function(i, wp, nWps) {
                    let iconUrl = i === 0 
                        ? 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png'
                        : 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png';
                    
                    return L.marker(wp.latLng, {
                        icon: L.icon({
                            iconUrl: iconUrl,
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).bindPopup(i === 0 ? "<b>Start:</b> You" : `<b>End:</b> ${destName}`);
                }
            }).addTo(map);

            document.getElementById('mapInfo').innerHTML = `<i class="fa-solid fa-diamond-turn-right me-1"></i> Navigating to <b>${destName}</b>. Follow the route on the map.`;

        }, 300);
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
    function startScan(targetQR, assignmentId) {
        currentTargetQR = targetQR;
        currentAssignmentId = assignmentId;
        const modal = new bootstrap.Modal(document.getElementById('scanModal'));
        modal.show();
        
        document.getElementById('scanModal').addEventListener('shown.bs.modal', function () {
            if(!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
                html5QrcodeScanner.render(onScanSuccess);
            }
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (decodedText === currentTargetQR) {
             html5QrcodeScanner.clear();
             document.getElementById('assignment_id').value = currentAssignmentId;
             document.getElementById('completeForm').submit();
        } else {
            alert("Incorrect QR Code! Please scan the correct customer's code.");
        }
    }

    function stopScan() {
        if(html5QrcodeScanner) {
            html5QrcodeScanner.clear();
            html5QrcodeScanner = null;
        }
    }
</script>
</body>
</html>
