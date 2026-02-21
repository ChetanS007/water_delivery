<?php
require_once 'includes/db.php';

// Fetch Products for Shop Section
$products = [];
try {
    $products_stmt = $pdo->query("SELECT * FROM products WHERE status = 1 LIMIT 3");
    $products = $products_stmt->fetchAll();
} catch (Exception $e) {}
?>
<?php include 'includes/header.php'; ?>

<!-- 2. HERO SECTION -->
<section class="hero-section d-flex align-items-center mb-5">
    <div class="hero-bg-shapes"></div>
    <div class="container hero-content position-relative z-2">
        <div class="row align-items-center">
            <!-- Left Side -->
            <div class="col-lg-5 mb-5 mb-lg-0 animate-on-scroll">
                <span class="d-block text-primary fw-bold text-uppercase fs-6 mb-3 font-subheading" style="letter-spacing: 2px;">Welcome into AquaFlow</span>
                <h1 class="hero-title text-dark">Pure & Healthy<br><span class="text-primary">Drinking Water</span></h1>
                <p class="lead text-muted mb-5 pe-lg-5">
                    We deliver the purest natural mineral water directly to your doorstep. Experience hydration like never before.
                </p>
                <div class="d-flex gap-3">
                    <button class="btn btn-primary shadow-lg icon-link-hover" data-bs-toggle="modal" data-bs-target="#orderModal">ORDER TODAY</button>
                    <button class="btn btn-secondary shadow-lg icon-link-hover" data-bs-toggle="modal" data-bs-target="#loginModal">BUY BOTTLE</button>
                </div>
            </div>
            
            <!-- Right Side -->
            <div class="col-lg-7 text-center position-relative animate-on-scroll">
                <div class="hero-image-wrapper">
                    <!-- Using placeholder or existing asset for two big cans with splash -->
                    <img src="assets/img/double-bottle.png" onerror="this.src='assets/img/slider-1.png'" alt="Premium Water Cans" class="img-fluid" style="max-height: 550px;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. FEATURES / TRUST SECTION -->
<section id="features" class="trust-section">
    <div class="container">
        <div class="section-title animate-on-scroll">
            <h2>A Trusted Name In<br>Bottled Water Industry</h2>
            <div class="divider mx-auto bg-primary rounded-pill mt-3" style="width: 80px; height: 4px;"></div>
        </div>

        <div class="row g-4">
            <!-- Card 1 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-droplet"></i></div>
                    <h4 class="feature-title">Maximum Purity</h4>
                    <p class="text-muted small mb-3">7-stage advanced filtration for crystal clear water.</p>
                    <!-- <a href="#" class="read-more">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a> -->
                </div>
            </div>
            <!-- Card 2 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-flask"></i></div>
                    <h4 class="feature-title">5 Steps Filtration</h4>
                    <p class="text-muted small mb-3">Rigorous process to remove all impurities safely.</p>
                    <!-- <a href="#" class="read-more">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a> -->
                </div>
            </div>
            <!-- Card 3 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-leaf"></i></div>
                    <h4 class="feature-title">Chlorine Free</h4>
                    <p class="text-muted small mb-3">100% chemical-free for the healthiest taste.</p>
                    <!-- <a href="#" class="read-more">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a> -->
                </div>
            </div>
            <!-- Card 4 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-4">
                 <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-award"></i></div>
                    <h4 class="feature-title">Quality Certified</h4>
                    <p class="text-muted small mb-3">ISO certified standards for your peace of mind.</p>
                    <!-- <a href="#" class="read-more">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a> -->
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. PRODUCTS SECTION -->
<section id="products" class="products-section">
    <div class="container">
        <div class="section-title text-center mb-5 animate-on-scroll">
            <h6 class="text-primary fw-bold text-uppercase font-subheading mb-2">Our Shop</h6>
            <h2>Bottles We Deliver</h2>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($products as $product): ?>
                <div class="col-md-4 animate-on-scroll">
                    <div class="product-card h-100">
                        <div class="product-img-box">
                             <?php $imgSrc = !empty($product['image_url']) ? $product['image_url'] : 'assets/img/bottle-generic.png'; ?>
                             <img src="<?php echo htmlspecialchars($imgSrc); ?>" onerror="this.src='assets/img/shop-1.jpg'" class="img-fluid" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        </div>
                        <h4 class="font-subheading fw-bold mb-2 text-dark" style="font-size: 1.2rem;"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                        
                        <button class="btn btn-outline-primary rounded-pill w-100 fw-bold"
                            <?php if(isset($_SESSION['user_id'])): ?>
                                onclick="openOrderModal(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['price']; ?>)"
                            <?php else: ?>
                                data-bs-toggle="modal" data-bs-target="#loginModal"
                            <?php endif; ?>
                        >
                            Subscribe Now <i class="fa-solid fa-cart-shopping ms-2"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 5. HELPING SECTION -->
<section class="helping-section">
    <div class="container">
        <div class="row align-items-center animate-on-scroll">
            <div class="col-lg-6">
                <h2 class="display-5 text-dark mb-4">Helping To Improve</h2>
                <div class="bg-primary rounded-pill mb-4" style="width: 60px; height: 4px;"></div>
            </div>
            <div class="col-lg-6">
                 <p class="text-muted lead" style="font-size: 1.1rem;">
                     We are committed to providing sustainable hydration solutions. Our bottles are recyclable, and our delivery process is optimized to reduce carbon footprint.
                 </p>
            </div>
        </div>
    </div>
</section>

<!-- 6. WATER COMPOSITION SECTION -->
<!-- 6. WATER COMPOSITION SECTION -->
<section class="composition-section">
    <div class="container animate-on-scroll">
        <div class="section-title text-center mb-5">
            <h2 class="display-5" style="letter-spacing: -1px; text-transform: capitalize;"> Basic Water<br>Composition</h2>
            <div class="divider mx-auto bg-warning rounded-pill mt-4" style="width: 40px; height: 3px;">~</div>
        </div>
        
        <div class="row align-items-center justify-content-center position-relative">
            <!-- Left Side Composition Details -->
            <div class="col-lg-3 text-center text-lg-end mb-5 mb-lg-0 order-2 order-lg-1">
                <div class="composition-item mb-5 pe-lg-5 position-relative">
                    <h4 class="font-heading fw-bold mb-1">Potassium</h4>
                    <h6 class="text-dark fw-bold mb-2">2.5 mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">To purify water 2.5mg potassium is<br>needed for every litter.</p>
                    <div class="composition-line-left d-none d-lg-block"></div>
                </div>
                
                <div class="composition-item pe-lg-5 position-relative mt-5 pt-4">
                    <h4 class="font-heading fw-bold mb-1">Fluoride</h4>
                    <h6 class="text-dark fw-bold mb-2">0.5 mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">0.5mg fluoride is needed to purify 1<br>litter of water.</p>
                    <div class="composition-line-left d-none d-lg-block" style="top: 40%;"></div>
                </div>
            </div>

            <!-- Center Image -->
            <div class="col-lg-6 text-center position-relative order-1 order-lg-2 mb-5 mb-lg-0">
                <div class="composition-rings">
                    <div class="ring ring-1"></div>
                    <div class="ring ring-2"></div>
                    <div class="ring ring-3"></div>
                </div>
                <img src="assets/img/water-glass-1.png" alt="Water Glass" class="img-fluid position-relative z-2" style="max-height: 400px; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.1));">
            </div>

            <!-- Right Side Composition Details -->
            <div class="col-lg-3 text-center text-lg-start mb-5 mb-lg-0 order-3 order-lg-3">
                <div class="composition-item mb-5 ps-lg-5 position-relative">
                    <h4 class="font-heading fw-bold mb-1">Chloride</h4>
                    <h6 class="text-dark fw-bold mb-2">350a mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">To purify water give 350a mg<br>chlorine for every litter of water...</p>
                    <div class="composition-line-right d-none d-lg-block"></div>
                </div>

                <div class="composition-item ps-lg-5 position-relative mt-5 pt-4">
                    <h4 class="font-heading fw-bold mb-1">Magnesium</h4>
                    <h6 class="text-dark fw-bold mb-2">14.5 mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">14.5mg of magnesium will be<br>required to purify every litter...</p>
                    <div class="composition-line-right d-none d-lg-block" style="top: 40%;"></div>
                </div>
            </div>
        </div>

        <!-- Bottom Stats Row -->
        <div class="row text-center justify-content-center pt-5 mt-4">
             <div class="col-6 col-md-3 mb-4 position-relative">
                 <h5 class="font-heading fw-bold mb-1 text-dark">Nitrates</h5>
                 <span class="text-muted small fw-bold">2 mg/L</span>
                 <div class="vr position-absolute end-0 top-50 translate-middle-y h-50 d-none d-md-block opacity-25"></div>
             </div>
             <div class="col-6 col-md-3 mb-4 position-relative">
                 <h5 class="font-heading fw-bold mb-1 text-dark">Bicarbonates</h5>
                 <span class="text-muted small fw-bold">157 mg/L</span>
                 <div class="vr position-absolute end-0 top-50 translate-middle-y h-50 d-none d-md-block opacity-25"></div>
             </div>
             <div class="col-6 col-md-3 mb-4 position-relative">
                 <h5 class="font-heading fw-bold mb-1 text-dark">Sulphates</h5>
                 <span class="text-muted small fw-bold">5.6 mg/L</span>
                 <div class="vr position-absolute end-0 top-50 translate-middle-y h-50 d-none d-md-block opacity-25"></div>
             </div>
             <div class="col-6 col-md-3 mb-4">
                 <h5 class="font-heading fw-bold mb-1 text-dark">Sodium</h5>
                 <span class="text-muted small fw-bold">0.4 mg/L</span>
             </div>
        </div>
    </div>
</section>

<!-- 7. BLUE CTA STRIP -->
<section class="cta-strip">
    <div class="container animate-on-scroll">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h6 class="text-warning text-uppercase fw-bold mb-2">Premium Service</h6>
                <h2 class="display-5 fw-bold mb-4">Bottled Water Delivery<br>& Service</h2>
                
                <ul class="cta-list">
                    <li><i class="fa-solid fa-circle-check"></i> Free delivery for subscription plans</li>
                    <li><i class="fa-solid fa-circle-check"></i> 24/7 Customer support</li>
                    <li><i class="fa-solid fa-circle-check"></i> No hidden charges</li>
                </ul>

                <div class="d-flex gap-3 mt-4">
                    <button class="btn btn-primary shadow icon-link-hover" data-bs-toggle="modal" data-bs-target="#orderModal">Order Now</button>
                    <button class="btn btn-secondary shadow icon-link-hover">Free Estimate</button>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block position-relative">
                 <!-- Optional graphic or empty space -->
                 <img src="assets/img/video-1.png" class="img-fluid rounded-4 opacity-75" alt="Service">
            </div>
        </div>
    </div>
</section>

<!-- 8. TESTIMONIALS SECTION -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-title mb-5 animate-on-scroll">
            <h6 class="text-primary text-uppercase fw-bold mb-2">Feedback</h6>
            <h2>Our Testimonials</h2>
        </div>

        <div id="testimonialCarousel" class="carousel slide testimonial-card animate-on-scroll" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="stars fs-4">
                        <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                    </div>
                    <p class="testimonial-text">"The water tastes amazing and the delivery is always punctual. Highly recommended for families!"</p>
                    <div class="avatars-box mt-4">
                        <img src="assets/img/author-1.jpg" alt="Client 1">
                        <img src="assets/img/author-2.jpg" alt="Client 2">
                    </div>
                    <h5 class="mt-3 fw-bold text-primary">Sarah & Mike</h5>
                    <small class="text-muted">Loyal Customers</small>
                </div>
                <!-- Add more items if needed -->
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon bg-primary rounded-circle" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon bg-primary rounded-circle" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</section>

<!-- 9. NEWS / BLOG SECTION -->
<section class="py-5 bg-white">
    <div class="container text-center py-5 animate-on-scroll">
        <div class="section-title">
            <h2>The News About AquaFlow</h2>
            <p class="text-muted">Stay updated with our latest hydration tips.</p>
        </div>
        <div class="py-5">
            <!-- Minimal empty space layout as requested -->
            <p class="text-muted fst-italic">No recent news available.</p>
        </div>
    </div>
</section>

<!-- 10. PARTNERS SECTION -->
<section class="py-5 bg-light border-top">
    <div class="container text-center animate-on-scroll">
        <h5 class="text-muted text-uppercase fw-bold mb-5 small letter-spacing-2">Trusted Partners</h5>
        <div class="row justify-content-center align-items-center opacity-50 grayscale-hover transition-all">
             <div class="col-6 col-md-3 mb-4"><i class="fa-brands fa-aws fa-3x"></i></div>
             <div class="col-6 col-md-3 mb-4"><i class="fa-brands fa-google fa-3x"></i></div>
             <div class="col-6 col-md-3 mb-4"><i class="fa-brands fa-stripe fa-3x"></i></div>
             <div class="col-6 col-md-3 mb-4"><i class="fa-brands fa-uber fa-3x"></i></div>
        </div>
    </div>
</section>

<!-- 11. FOOTER -->
<?php include 'includes/footer.php'; ?>

<!-- Modals (Copied from previous iteration) -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card p-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-dark">Login</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="auth_action.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control rounded-pill px-3 bg-light border-0" placeholder="10-digit mobile" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control rounded-pill px-3 bg-light border-0" placeholder="******" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow">Login</button>
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
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg p-0" style="overflow: hidden;">
            <div class="modal-header border-0 bg-primary text-white p-4">
                <h5 class="modal-title fw-bold">Subscribe Now</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <form action="place_order.php" method="POST">
                    <input type="hidden" name="product_id" id="modal_product_id">
                    <input type="hidden" name="price" id="modal_price">
                    
                    <div class="text-center mb-4">
                        <h4 id="modal_product_name" class="fw-bold mb-1 text-primary"></h4>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 border border-success">In Stock</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Frequency</label>
                        <select name="order_type" class="form-select rounded-pill bg-light border-0" onchange="toggleCustomDays(this.value)">
                            <option value="Daily">Daily Delivery</option>
                            <option value="Alternate">Alternate Days</option>
                            <option value="Custom">Custom Days</option>
                        </select>
                    </div>

                     <div class="mb-3 d-none" id="customDaysDiv">
                        <label class="form-label small fw-bold text-muted">Select Days</label>
                        <div class="d-flex flex-wrap gap-2">
                             <?php $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']; ?>
                             <?php foreach($days as $day): ?>
                                <input type="checkbox" class="btn-check" name="days[]" value="<?php echo $day; ?>" id="day-<?php echo $day; ?>" autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="day-<?php echo $day; ?>"><?php echo $day; ?></label>
                             <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Quantity</label>
                            <div class="input-group">
                                <button class="btn btn-outline-secondary rounded-start-pill" type="button" onclick="document.getElementById('orderQty').stepDown(); updateSummary();">-</button>
                                <input type="number" name="quantity" id="orderQty" class="form-control text-center border-secondary" value="1" min="1" onchange="updateSummary()">
                                <button class="btn btn-outline-secondary rounded-end-pill" type="button" onclick="document.getElementById('orderQty').stepUp(); updateSummary();">+</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-light p-3 rounded-4 mb-3 border border-dashed">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Subtotal</span>
                            <span class="fw-bold small" id="summarySubtotal">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span class="small">Discount</span>
                            <span class="fw-bold small" id="summaryDiscount">-₹0.00</span>
                        </div>
                        <div class="border-top pt-2 d-flex justify-content-between align-items-center mt-2">
                            <span class="fw-bold text-dark">Total</span>
                            <span class="fw-bolder fs-5 text-primary" id="summaryTotal">₹0.00</span>
                        </div>
                    </div>

                    <div class="mb-4">
                         <div class="input-group">
                            <input type="text" id="offerCodeInput" name="offer_code_input" class="form-control rounded-start-pill ps-3 bg-light border-0" placeholder="Promo Code">
                            <button type="button" class="btn btn-dark rounded-end-pill px-4" onclick="applyOffer()">Apply</button>
                        </div>
                        <small id="offerMessage" class="d-block mt-1 ms-2"></small>
                    </div>

                    <input type="hidden" name="offer_code" id="finalOfferCode">
                    <input type="hidden" name="discount_amount" id="finalDiscountAmount" value="0">

                    <button type="submit" class="btn btn-secondary w-100 rounded-pill py-3 fw-bold shadow text-white text-uppercase letter-spacing-1">Confirm Subscription</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Animation Observer
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    });
    document.querySelectorAll('.animate-on-scroll').forEach((el) => observer.observe(el));

    // Order Modal Logic (Preserved)
    function openOrderModal(id, name, price) {
        document.getElementById('modal_product_id').value = id;
        document.getElementById('modal_price').value = price;
        document.getElementById('modal_product_name').innerText = name;
        document.getElementById('orderQty').value = 1;
        updateSummary();
        new bootstrap.Modal(document.getElementById('orderModal')).show();
    }

    function updateSummary() {
        const price = parseFloat(document.getElementById('modal_price').value || 0);
        const qty = parseInt(document.getElementById('orderQty').value || 1);
        const discount = parseFloat(document.getElementById('finalDiscountAmount').value || 0);
        const subtotal = price * qty;
        const total = Math.max(0, subtotal - discount);

        document.getElementById('summarySubtotal').innerText = '₹' + subtotal.toFixed(2);
        document.getElementById('summaryDiscount').innerText = '-₹' + discount.toFixed(2);
        document.getElementById('summaryTotal').innerText = '₹' + total.toFixed(2);
    }

    function applyOffer() {
        const code = document.getElementById('offerCodeInput').value;
        const price = parseFloat(document.getElementById('modal_price').value || 0);
        const qty = parseInt(document.getElementById('orderQty').value || 1);
        
        if(!code) return;

        const fd = new FormData();
        fd.append('code', code);
        fd.append('amount', price * qty); 

        fetch('api/verify_offer.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                const msg = document.getElementById('offerMessage');
                msg.innerText = res.message;
                msg.className = res.success ? 'text-success small fw-bold' : 'text-danger small fw-bold';
                
                if(res.success) {
                    document.getElementById('finalOfferCode').value = res.code;
                    document.getElementById('finalDiscountAmount').value = res.discount_amount;
                } else {
                    document.getElementById('finalOfferCode').value = '';
                    document.getElementById('finalDiscountAmount').value = 0;
                }
                updateSummary();
            });
    }

    function toggleCustomDays(val) {
        const div = document.getElementById('customDaysDiv');
        val === 'Custom' ? div.classList.remove('d-none') : div.classList.add('d-none');
    } 
</script>
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
</body>
</html>