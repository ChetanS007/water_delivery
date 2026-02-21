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
        #map { height: 75vh; width: 100%; border-radius: 10px; }
        .nav-tabs .nav-link { color: #495057; }
        .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd; }
        .leaflet-routing-container { max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <span class="navbar-brand">
            <i class="fa-solid fa-motorcycle me-2"></i>
            Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Partner'); ?>
        </span>
        <a href="/water_delivery/logout.php" class="btn btn-sm btn-outline-light">Logout</a>
    </div>
</nav>

<!-- Stats -->
    <div class="row g-3 mb-4 px-2 text-center">
        <div class="col-4">
            <div class="card bg-primary text-white border-0 shadow-sm py-2">
                <h6 class="small mb-1">Total Cans </h6>
                <h3 class="fw-bold mb-0" id="statTotal">0</h3>
            </div>
        </div>
        <div class="col-4">
            <div class="card bg-success text-white border-0 shadow-sm py-2">
                <h6 class="small mb-1">Delivered</h6>
                <h3 class="fw-bold mb-0" id="statDelivered">0</h3>
            </div>
        </div>
        <div class="col-4">
            <div class="card bg-secondary text-white border-0 shadow-sm py-2">
                <h6 class="small mb-1">Remaining </h6>
                <h3 class="fw-bold mb-0" id="statRemaining">0</h3>
            </div>
        </div>
    </div>

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
                <h5 class="mb-0">Today's Dispatch List (<span id="countDisplay"><?php echo count($deliveries); ?></span>)</h5>
                <small class="text-muted" id="locStatus">Getting location...</small>
            </div>

            <div class="row g-3" id="deliveryList">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Loading Assigned Deliveries...</p>
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

<!-- Navigation Modal -->
<div class="modal fade" id="navModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="navTargetName">Navigation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopNavigation()"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <div id="navMap" style="height: 100%; width: 100%;"></div>
                <div class="position-absolute bottom-0 start-0 p-3 w-100" style="z-index: 1000;">
                    <button class="btn btn-danger w-100 shadow fw-bold" data-bs-dismiss="modal" onclick="stopNavigation()">
                        Exit Navigation
                    </button>
                </div>
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
    let currentMarker;
    let routingControl;
    let currentLat = null, currentLng = null;
    let sortedOrders = []; 
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
        
        // Polling (No Full Page Reload)
        setInterval(() => {
            if(!html5QrcodeScanner) { 
                fetchDeliveries();
            }
        }, 30000);
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

    function fetchDeliveries() {
        const container = document.getElementById('deliveryList');
        
        fetch('api/fetch_deliveries.php')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const currentDataStr = JSON.stringify(res.data);
                if(lastDeliveryData === currentDataStr) return; // No Change
                
                lastDeliveryData = currentDataStr;
                renderDeliveries(res.data);
                
                // Update Stats
                if(res.stats) {
                    document.getElementById('statTotal').innerText = res.stats.total_cans;
                    document.getElementById('statDelivered').innerText = res.stats.delivered_cans;
                    document.getElementById('statRemaining').innerText = res.stats.remaining_cans;
                }

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
            container.innerHTML = '<div class="col-12 text-center text-muted mt-5"><p>No order assigned.</p></div>';
            return;
        }

        displayItems.forEach((item, index) => {
             // Calculate distance purely for informational display if desired, 
             // but user requested removing 'nearest' condition, implies logic change.
             // We can keep the badge as "X km away" but not sort by it.
             let distStr = "";
             if (currentLat && currentLng) {
                 const d = getDistance(currentLat, currentLng, parseFloat(item.latitude), parseFloat(item.longitude));
                 distStr = `<span class="badge bg-secondary ms-2">${d.toFixed(1)} km</span>`;
             }

            const html = `
                <div class="col-md-6 col-lg-4 delivery-item" 
                     data-id="${item.assignment_id}">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">${index + 1}. ${item.full_name}</h6>
                                    <small class="text-muted">${item.product_name} x ${item.quantity}</small>
                                </div>
                                ${distStr}
                            </div>
                            
                            <p class="small text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i> ${item.address}</p>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="startNavigation(${item.latitude}, ${item.longitude}, '${item.full_name.replace(/'/g, "\\'")}')">
                                   <i class="fa-solid fa-diamond-turn-right me-1"></i> Navigate
                                </button>
                                <button class="btn btn-success btn-sm" 
                                        onclick="startScan('${item.qr_code}', ${item.assignment_id})">
                                    <i class="fa-solid fa-qrcode me-1"></i> Scan & Deliver
                                </button>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="completeDelivery(${item.assignment_id})">
                                    <i class="fa-solid fa-check me-1"></i> Delivered
                                </button>
                            </div>
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

    function completeDelivery(assignmentId) {
        if(confirm('Are you sure you want to mark this order as DELIVERED?')) {
            document.getElementById('assignment_id').value = assignmentId;
            document.getElementById('completeForm').submit();
        }
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
            alert("Waiting for your current location. Please ensure GPS is active.");
            return;
        }

        navTargetLat = targetLat;
        navTargetLng = targetLng;
        document.getElementById('navTargetName').innerText = "Navigating to: " + targetName;
        
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
</script>
</body>
</html>
