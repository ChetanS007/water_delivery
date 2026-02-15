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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uaques Style - Premium Water Delivery</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Header / Navbar -->
    <header class="main-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a class="navbar-brand" href="index.php">
                    <img src="https://uaques.smartdemowp.com/wp-content/themes/uaques/assets/images/logo.png" alt="Uaques Logo">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                    <ul class="navbar-nav align-items-center">
                        <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#products">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li class="nav-item ms-lg-3">
                                <a href="user/dashboard.php" class="theme-btn style-one text-white px-4 py-2" style="font-size: 14px;">Dashboard</a>
                            </li>
                            <li class="nav-item ms-2">
                                <a href="logout.php" class="theme-btn style-two text-white px-4 py-2" style="font-size: 14px;">Logout</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item ms-lg-3">
                                <button class="theme-btn style-one text-white px-4 py-2" style="font-size: 14px; border:none;" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                                <button class="theme-btn style-one text-white px-4 py-2" style="font-size: 14px; border:none;" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item ms-3">
                            <a href="#" class="nav-link position-relative">
                                <i class="fa-solid fa-cart-shopping fs-5"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>
    </header> 
    <!-- Main Slider / Hero Section -->
    <section class="main-slider">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="content-box">
                        <div class="top-text">Understand the importance of life</div>
                        <h1>Pure & Healthy <br> Drinking Water</h1>
                        <div class="btn-box mt-4">
                            <a href="#products" class="theme-btn style-one me-3">Order Today!</a>
                            <a href="#contact" class="theme-btn style-two">Free Estimate</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="image-box">
                        <img src="https://uaques.smartdemowp.com/wp-content/uploads/2021/04/double-bottle.png" alt="Pure Water Bottle" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="services" class="feature-section">
        <div class="container">
            <div class="sec-title text-center">
                <h1>A Trusted Name In <br> Bottled Water Industry</h1>
                <div class="text mt-3">We provide the highest quality water services ensuring purity and health for your family.</div>
            </div>
            <div class="row">
                <!-- Feature 1 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-block-one">
                        <div class="inner-box">
                            <div class="icon-box"><i class="fa-solid fa-leaf"></i></div>
                            <h3>Maximum Purity</h3>
                            <div class="text">99.9% pure water delivered to your doorstep with our advanced filtration.</div>
                            <div class="link"><a href="#">Read More</a></div>
                        </div>
                    </div>
                </div>
                <!-- Feature 2 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-block-one">
                        <div class="inner-box">
                            <div class="icon-box"><i class="fa-solid fa-filter"></i></div>
                            <h3>5 Steps Filtration</h3>
                            <div class="text">Rigorous 5-step process removes all impurities while retaining minerals.</div>
                            <div class="link"><a href="#">Read More</a></div>
                        </div>
                    </div>
                </div>
                <!-- Feature 3 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-block-one">
                        <div class="inner-box">
                            <div class="icon-box"><i class="fa-solid fa-droplet"></i></div>
                            <h3>Chlorine Free</h3>
                            <div class="text">100% Chlorine-free water for a healthier lifestyle free from chemicals.</div>
                            <div class="link"><a href="#">Read More</a></div>
                        </div>
                    </div>
                </div>
                <!-- Feature 4 -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-block-one">
                        <div class="inner-box">
                            <div class="icon-box"><i class="fa-solid fa-certificate"></i></div>
                            <h3>Quality Certified</h3>
                            <div class="text">Certified by international health organizations for safety and quality.</div>
                            <div class="link"><a href="#">Read More</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Delivery / Products Section -->
    <section id="products" class="delivery-section">
        <div class="container">
            <div class="sec-title text-center">
                <h1>Bottles We Deliver</h1>
                <div class="text mt-3">Choose from our wide range of bottle sizes tailored for your needs.</div>
            </div>
            
            <div class="row">
                <?php foreach($products as $product): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="single-shop-block">
                        <div class="image-box">
                            <!-- Placeholder if no image, using external asset as fallback -->
                            <img src="<?php echo !empty($product['image_url']) ? $product['image_url'] : 'https://uaques.smartdemowp.com/wp-content/uploads/2020/06/shop-details-4.png'; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="img-fluid">
                        </div>
                        <span class="size">Refill / New Bottle</span>
                        <h3><a href="#"><?php echo htmlspecialchars($product['product_name']); ?></a></h3>
                        <div class="text mb-3"><?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?></div>
                        <span class="price">₹<?php echo htmlspecialchars($product['price']); ?></span>
                        
                        <div class="cart-btn mt-3">
                            <button 
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    onclick="openOrderModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['price']; ?>)"
                                <?php else: ?>
                                    data-bs-toggle="modal" data-bs-target="#loginModal"
                                <?php endif; ?>
                            >
                                <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Composition Section -->
    <section class="composition-section">
        <div class="container">
            <div class="sec-title text-center">
                <h1>Uaques Basic Water <br> Composition</h1>
            </div>
            <div class="row align-items-center">
                <!-- Left Column -->
                <div class="col-lg-4">
                    <div class="single-item text-end pe-4">
                        <div class="d-flex align-items-center justify-content-end mb-2">
                            <h3 class="me-3 mb-0"><a href="#">Potassium</a></h3>
                            <div class="icon-box">K+</div>
                        </div>
                        <h5>2.5 mg/L</h5>
                        <p class="text-muted">Essential for body balance.</p>
                    </div>
                    <div class="single-item text-end pe-4 mt-5">
                        <div class="d-flex align-items-center justify-content-end mb-2">
                            <h3 class="me-3 mb-0"><a href="#">Fluoride</a></h3>
                            <div class="icon-box">Fl</div>
                        </div>
                        <h5>0.5 mg/L</h5>
                        <p class="text-muted">For healthy teeth and bones.</p>
                    </div>
                </div>
                
                <!-- Center Image -->
                <div class="col-lg-4 text-center">
                     <img src="https://uaques.smartdemowp.com/wp-content/uploads/water-glass-1.png" alt="Water Glass" class="img-fluid" style="max-height: 400px;">
                </div>
                
                <!-- Right Column -->
                <div class="col-lg-4">
                    <div class="single-item ps-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="icon-box">Cl</div>
                            <h3 class="ms-3 mb-0"><a href="#">Chloride</a></h3>
                        </div>
                        <h5>350 mg/L</h5>
                        <p class="text-muted">Maintains fluid balance.</p>
                    </div>
                    <div class="single-item ps-4 mt-5">
                        <div class="d-flex align-items-center mb-2">
                            <div class="icon-box">Mg</div>
                            <h3 class="ms-3 mb-0"><a href="#">Magnesium</a></h3>
                        </div>
                        <h5>14.5 mg/L</h5>
                        <p class="text-muted">Supports muscle function.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Section -->
    <section class="video-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="video-box" style="background-image: url('https://uaques.smartdemowp.com/wp-content/uploads/video-1.jpg');">
                        <div class="video-btn">
                            <a href="#"><i class="fa-solid fa-play"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5 d-flex flex-column justify-content-center">
                    <div class="sec-title text-start mb-4">
                        <h1>Helping To Improve</h1>
                    </div>
                    <div class="text mb-4" style="color: #666; font-size: 18px;">
                        Another name for water is life. We want to help you improve your water quality so that you and your family can drink pure, healthy, and safe water.
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <img src="https://uaques.smartdemowp.com/wp-content/uploads/video-1-1.jpg" class="img-fluid rounded-3 mb-3" alt="Gallery 1">
                        </div>
                        <div class="col-6">
                            <img src="https://uaques.smartdemowp.com/wp-content/uploads/video-2-1.jpg" class="img-fluid rounded-3 mb-3" alt="Gallery 2">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Info Section -->
    <section class="info-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-lg-6">
                    <div class="content-box bg-white p-5 rounded shadow-sm">
                        <div class="sec-title text-start">
                            <h1>Bottled Water <br> Delivery & Service</h1>
                        </div>
                        <p class="text-muted mb-4">
                            We provide our services to more than 10 countries. We offer delivery within 2 hours anywhere in the city using our dedicated courier fleet.
                        </p>
                        <ul class="list-item">
                            <li>Hygienic and Ergonomic Bottles</li>
                            <li>Free Delivery On Weekends</li>
                            <li>5 Steps Filtration Plants</li>
                            <li>Best For Health & Hydration</li>
                        </ul>
                        <div class="btn-box mt-4">
                            <a href="#products" class="theme-btn style-one me-3">Order Today!</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonial-section">
        <div class="container">
            <div class="sec-title text-center">
                <h1>Our Testimonials</h1>
            </div>
            <div class="testimonial-content">
                <div class="inner-box">
                    <div class="text">
                        "Compared to other companies in the market, the water Uaques supplies tastes much better. Its quality and purity are unmatched, and the free delivery service is a huge plus!"
                    </div>
                    <div class="author-info">
                        <div class="name">Brendon Taylor</div>
                        <div class="designation">CEO & Founder</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="auth/login_process.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email / Phone</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="theme-btn style-one w-100">Login</button>
                    </form>
                    <div class="text-center mt-3">
                        <small>Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Register here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Register</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="auth/register_process.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="theme-btn style-one w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Place Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="user/place_order.php" method="POST">
                        <input type="hidden" name="product_id" id="modal_product_id">
                        <div class="mb-3">
                            <h5 id="modal_product_name" class="text-primary fw-bold"></h5>
                            <p class="text-muted">Price: ₹<span id="modal_product_price"></span></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery Address</label>
                            <textarea name="address" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="theme-btn style-one w-100">Confirm Order</button>
                    </form>
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

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Subscription</h5>
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

                    <button type="submit" class="btn btn-primary w-100">Send Request</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- <?php include 'includes/footer.php'; ?> -->

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
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function openOrderModal(id, name, price) {
            document.getElementById('modal_product_id').value = id;
            document.getElementById('modal_product_name').innerText = name;
            document.getElementById('modal_product_price').innerText = price;
            new bootstrap.Modal(document.getElementById('orderModal')).show();
        }
    </script>
</body>
</html>
