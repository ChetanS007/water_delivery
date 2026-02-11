<?php
require_once 'includes/db.php';

// Fetch Products for Landing Page
$products = [];
try {
    $products_stmt = $pdo->query("SELECT * FROM products WHERE status = 1");
    $products = $products_stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently or log it
}
?>
<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section text-center">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="hero-title animate-on-scroll">Hydration Delivered<br><span class="text-gradient">Effortlessly</span></h1>
        <p class="hero-subtitle mb-5">Premium water can and bottle delivery service for your home and shop.<br>Schedule recurring deliveries and never run dry.</p>
        <button class="btn btn-primary btn-lg shadow-lg" data-bs-toggle="modal" data-bs-target="#registerModal">Start Delivery Now</button>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5">
    <div class="container py-5">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold display-5">Why Choose Us?</h2>
                <p class="lead text-muted">Smart features designed for your convenience.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card h-100 text-center">
                    <i class="fa-solid fa-calendar-days feature-icon"></i>
                    <h4>Flexible Scheduling</h4>
                    <p>Daily, weekly, or custom days. Set it and forget it.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card h-100 text-center">
                    <i class="fa-solid fa-location-dot feature-icon"></i>
                    <h4>Live Tracking</h4>
                    <p>Track your delivery partner in real-time on the map.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card h-100 text-center">
                    <i class="fa-solid fa-qrcode feature-icon"></i>
                    <h4>QR Verification</h4>
                    <p>Secure delivery with unique QR code scanning for every customer.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Products Section -->
<section id="products" class="py-5 bg-light">
    <div class="container py-5">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold display-5">Our Products</h2>
                <p class="lead text-muted">Pure, mineral-rich water options.</p>
            </div>
        </div>
        <?php if (count($products) > 0): ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100 product-card">
                            <div class="card-body p-4 text-center">
                                <div class="mb-3">
                                    <i class="fa-solid fa-bottle-water fa-3x text-primary"></i>
                                </div>
                                <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                <h5 class="text-secondary mb-3">₹<?php echo htmlspecialchars($product['price']); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                                
                                <button class="btn btn-primary w-100 rounded-pill"
                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        onclick="openOrderModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['price']; ?>)"
                                    <?php else: ?>
                                        data-bs-toggle="modal" data-bs-target="#loginModal"
                                    <?php endif; ?>
                                >
                                    Order Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No products available at the moment.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card p-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="auth_action.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" placeholder="10-digit mobile" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="******" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="#" class="small text-muted">Forgot Password?</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Registration Modal -->
<div class="modal fade" id="registerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-card p-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">New Customer Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="auth_action.php" method="POST" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Type</label>
                            <select name="customer_type" class="form-select" required>
                                <option value="Home">Home</option>
                                <option value="Shop">Shop</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pin Your Location (Drag Marker)</label>
                        <div id="map" style="height: 300px; border-radius: 10px;"></div>
                        <small class="text-muted">Allow location access for better accuracy.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Place Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="place_order.php" method="POST">
                    <input type="hidden" name="product_id" id="modal_product_id">
                    <input type="hidden" name="price" id="modal_price">
                    
                    <div class="mb-3">
                        <label class="fw-bold">Product:</label>
                        <span id="modal_product_name"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Frequency</label>
                        <select name="order_type" class="form-select" onchange="toggleCustomDays(this.value)">
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Alternate">Alternate Days</option>
                            <option value="Custom">Custom Days</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="customDaysDiv">
                        <label class="form-label">Select Days</label> <br>
                        <div class="btn-group" role="group">
                            <input type="checkbox" class="btn-check" name="days[]" value="Mon" id="mon" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="mon">Mon</label>
                            
                            <input type="checkbox" class="btn-check" name="days[]" value="Tue" id="tue" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="tue">Tue</label>

                            <input type="checkbox" class="btn-check" name="days[]" value="Wed" id="wed" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="wed">Wed</label>

                            <input type="checkbox" class="btn-check" name="days[]" value="Thu" id="thu" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="thu">Thu</label>

                            <input type="checkbox" class="btn-check" name="days[]" value="Fri" id="fri" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="fri">Fri</label>

                            <input type="checkbox" class="btn-check" name="days[]" value="Sat" id="sat" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="sat">Sat</label>

                            <input type="checkbox" class="btn-check" name="days[]" value="Sun" id="sun" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="sun">Sun</label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" id="orderQty" class="form-control" value="1" min="1" required onchange="updateSummary()">
                        </div>
                    </div>

                    <!-- Offer Code -->
                    <div class="mb-3">
                        <label class="form-label">Offer Code</label>
                        <div class="input-group">
                            <input type="text" id="offerCodeInput" name="offer_code_input" class="form-control text-uppercase" placeholder="Enter code">
                            <button type="button" class="btn btn-outline-primary" onclick="applyOffer()">Apply</button>
                        </div>
                        <small id="offerMessage" class="form-text"></small>
                    </div>

                    <!-- Pricing Summary -->
                    <div class="bg-light p-3 rounded mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal:</span>
                            <span id="summarySubtotal">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span>Discount:</span>
                            <span id="summaryDiscount">-₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-top pt-2">
                            <span>Total:</span>
                            <span id="summaryTotal">₹0.00</span>
                        </div>
                    </div>
                    
                    <input type="hidden" name="offer_code" id="finalOfferCode">
                    <input type="hidden" name="discount_amount" id="finalDiscountAmount" value="0">

                    <button type="submit" class="btn btn-primary w-100">Confirm Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Initialize Map in Registration Modal
    let map;
    let marker;

    function updateAddress(lat, lng) {
        // Update hidden inputs
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Show loading state in address box
        const addressBox = document.querySelector('textarea[name="address"]');
        addressBox.value = "Fetching location address...";

        // Reverse Geocoding via Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                if(data && data.display_name) {
                    addressBox.value = data.display_name;
                } else {
                    addressBox.value = "Address details not found. Please type manually.";
                }
            })
            .catch(err => {
                console.error("Geocoding error:", err);
                addressBox.value = ""; 
                addressBox.placeholder = "Could not fetch address. Please enter manually.";
            });
    }

    const registerModal = document.getElementById('registerModal');
    if(registerModal) {
        registerModal.addEventListener('shown.bs.modal', function () {
            // Resize map to ensure tiles render correctly after modal open
            if(map) {
                map.invalidateSize();
                return; 
            }

            // Default: India Center
            const defaultLat = 20.5937; 
            const defaultLng = 78.9629;

            map = L.map('map').setView([defaultLat, defaultLng], 5);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

            // Event: Drag End
            marker.on('dragend', function (e) {
                const position = marker.getLatLng();
                updateAddress(position.lat, position.lng);
            });

            // Event: Map Click (Move marker to click)
            map.on('click', function(e) {
                marker.setLatLng(e.latlng);
                updateAddress(e.latlng.lat, e.latlng.lng);
            });

            // Initial Geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    map.setView([lat, lng], 16);
                    marker.setLatLng([lat, lng]);
                    
                    // Fetch address for initial location
                    updateAddress(lat, lng);
                }, function(error) {
                    console.log("Geolocation error: ", error);
                });
            }
        });
    }

    // Order Modal Logic
    function openOrderModal(id, name, price) {
        document.getElementById('modal_product_id').value = id;
        document.getElementById('modal_price').value = price;
        document.getElementById('modal_product_name').innerText = name + " (₹" + price + ")";
        
        // Reset Coupon
        document.getElementById('offerCodeInput').value = '';
        document.getElementById('offerMessage').innerText = '';
        document.getElementById('offerMessage').className = 'form-text';
        document.getElementById('finalOfferCode').value = '';
        document.getElementById('finalDiscountAmount').value = '0';
        document.getElementById('orderQty').value = 1;

        updateSummary();
        new bootstrap.Modal(document.getElementById('orderModal')).show();
    }

    function updateSummary() {
        const price = parseFloat(document.getElementById('modal_price').value || 0);
        const qty = parseInt(document.getElementById('orderQty').value || 1);
        const subtotal = price * qty;
        const discount = parseFloat(document.getElementById('finalDiscountAmount').value || 0);
        const total = subtotal - discount;

        document.getElementById('summarySubtotal').innerText = '₹' + subtotal.toFixed(2);
        document.getElementById('summaryDiscount').innerText = '-₹' + discount.toFixed(2);
        document.getElementById('summaryTotal').innerText = '₹' + (total > 0 ? total : 0).toFixed(2);
    }

    function applyOffer() {
        const code = document.getElementById('offerCodeInput').value;
        const price = parseFloat(document.getElementById('modal_price').value || 0);
        const qty = parseInt(document.getElementById('orderQty').value || 1);
        const amount = price * qty;
        
        if(!code) return;

        const fd = new FormData();
        fd.append('code', code);
        fd.append('amount', amount);

        fetch('api/verify_offer.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            const msg = document.getElementById('offerMessage');
            if(res.success) {
                msg.innerText = res.message;
                msg.className = 'text-success small';
                document.getElementById('finalOfferCode').value = res.code;
                document.getElementById('finalDiscountAmount').value = res.discount_amount;
                updateSummary();
            } else {
                msg.innerText = res.message;
                msg.className = 'text-danger small';
                document.getElementById('finalOfferCode').value = '';
                document.getElementById('finalDiscountAmount').value = 0;
                updateSummary();
            }
        });
    }

    function toggleCustomDays(val) {
        const div = document.getElementById('customDaysDiv');
        if(val === 'Custom') {
            div.classList.remove('d-none');
        } else {
            div.classList.add('d-none');
        }
    }
</script>
