<?php
require_once '../includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Superadmin'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Fleet Live Tracking</h3>
            <p class="text-muted small mb-0">Real-time location of your delivery partners.</p>
        </div>
        <div id="fleetStatus" class="small fw-bold">
            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                <i class="fa-solid fa-satellite-dish me-1"></i> Live Feedback Active
            </span>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div id="fleetMap" style="height: 75vh; width: 100%;"></div>
        </div>
    </div>
</div>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    let map;
    let markers = {};

    document.addEventListener('DOMContentLoaded', () => {
        initMap();
        fetchFleetLocations();
        setInterval(fetchFleetLocations, 10000); // Update every 10 seconds
    });

    function initMap() {
        map = L.map('fleetMap').setView([20.5937, 78.9629], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
    }

    function fetchFleetLocations() {
        fetch('api/fetch_fleet.php')
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                const boys = res.data;
                const activeIds = boys.map(b => b.id);

                // Remove markers for boys not in the current list
                Object.keys(markers).forEach(id => {
                    if (!activeIds.includes(parseInt(id))) {
                        map.removeLayer(markers[id]);
                        delete markers[id];
                    }
                });

                boys.forEach(boy => {
                    if (boy.current_lat && boy.current_lng) {
                        const pos = [parseFloat(boy.current_lat), parseFloat(boy.current_lng)];
                        
                        if (markers[boy.id]) {
                            markers[boy.id].setLatLng(pos);
                        } else {
                            const icon = L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                                iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                            });

                            markers[boy.id] = L.marker(pos, { icon: icon })
                                .addTo(map)
                                .bindPopup(`<b>${boy.full_name}</b><br>${boy.mobile}`);
                        }
                    }
                });

                // Auto-center on first marker if not moved by user
                if (boys.length > 0 && !map._userMoved) {
                    const first = boys.find(b => b.current_lat);
                    if (first) {
                        map.setView([first.current_lat, first.current_lng], 13);
                        map._userMoved = true;
                    }
                }
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
