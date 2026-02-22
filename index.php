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
                <span class="d-block text-primary fw-bold text-uppercase fs-6 mb-3 font-subheading" style="letter-spacing: 1px;">सुधा जल प्लेटिनम प्लेटेड आयोनाइज़्ड हाइड्रोजन वॉटर</span>
                <h1 class="hero-title text-dark">शुद्ध आणि आरोग्यदायी<br><span class="text-primary">पिण्याचे पाणी</span></h1>
                <p class="lead text-muted mb-5 pe-lg-5">
                    आम्ही सर्वात शुद्ध नैसर्गिक खनिज पाणी थेट तुमच्या दारापर्यंत पोहोचवतो. हायड्रेशनचा असा अनुभव आधी कधीही घेतला नसेल.
                </p>
                <div class="d-flex gap-3">
                    <!-- <button class="btn btn-primary shadow-lg icon-link-hover" data-bs-toggle="modal" data-bs-target="#orderModal">आजच ऑर्डर करा</button> -->
                    <button class="btn btn-secondary shadow-lg icon-link-hover" onclick="window.location.href='#products'">बाटली खरेदी करा</button>
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
            <h2>बॉटल पाणी उद्योगातील<br>एक विश्वासार्ह नाव</h2>
            <div class="divider mx-auto bg-primary rounded-pill mt-3" style="width: 80px; height: 4px;"></div>
        </div>

        <div class="row g-4">
            <!-- Card 1 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-droplet"></i></div>
                    <h4 class="feature-title">जास्तीत जास्त शुद्धता</h4>
                    <p class="text-muted small mb-3">स्फटिकासारख्या स्वच्छ पाण्यासाठी ७-टप्प्यांची प्रगत गाळण प्रक्रिया.</p>
                    <!-- <a href="#" class="read-more">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a> -->
                </div>
            </div>
            <!-- Card 2 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-flask"></i></div>
                    <h4 class="feature-title">५ टप्प्यांची गाळण</h4>
                    <p class="text-muted small mb-3">सर्व अशुद्धता सुरक्षितपणे काढून टाकण्यासाठी कठोर प्रक्रिया.</p>
                    <!-- <a href="#" class="read-more">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a> -->
                </div>
            </div>
            <!-- Card 3 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-leaf"></i></div>
                    <h4 class="feature-title">क्लोरीन मुक्त</h4>
                    <p class="text-muted small mb-3">आरोग्यदायी चवीसाठी १००% रसायने विरहित पाणी.</p>
                    <!-- <a href="#" class="read-more">Read More <i class="fa-solid fa-arrow-right ms-1"></i></a> -->
                </div>
            </div>
            <!-- Card 4 -->
            <div class="col-lg-3 col-md-6 animate-on-scroll delay-4">
                 <div class="feature-card">
                    <div class="feature-icon"><i class="fa-solid fa-award"></i></div>
                    <h4 class="feature-title">गुणवत्ता प्रमाणित</h4>
                    <p class="text-muted small mb-3">तुमच्या समाधानासाठी आयएसओ (ISO) प्रमाणित मानके.</p>
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
            <h6 class="text-primary fw-bold text-uppercase font-subheading mb-2">आमचे दुकान</h6>
            <h2>आम्ही पोहोचवत असलेल्या पाण्याच्या बाटल्या</h2>
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
                            आत्ताच सबस्क्राईब करा <i class="fa-solid fa-cart-shopping ms-2"></i>
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
        <div class="row align-items-center animate-on-scroll mb-5">
            <div class="col-lg-7">
                <h6 class="text-primary fw-bold text-uppercase font-subheading mb-2">आरोग्यदायी फायदे</h6>
                <h2 class="display-5 text-dark mb-4">लोक सुधा जल हायड्रोजन वॉटरच का पित आहेत?</h2>
                <div class="bg-primary rounded-pill mb-4" style="width: 60px; height: 4px;"></div>
                <p class="text-muted lead" style="font-size: 1.15rem;">
                    आम्ही केवळ पाणीच पोहोचवत नाही, तर तुमच्या आरोग्याला प्राधान्य देतो. आमचे आयोनाइज़्ड हायड्रोजन पाणी खालीलप्रमाणे सुधारणा करण्यास मदत करते.
                </p>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-end">
                <img src="assets/img/helping-water.png" onerror="this.src='assets/img/double-bottle.png'" class="img-fluid rounded-4" alt="Helping" style="max-height: 250px; filter: drop-shadow(0 15px 30px rgba(0,0,0,0.1));">
            </div>
        </div>

        <div class="benefit-grid animate-on-scroll">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-shield-heart"></i></div>
                <h6 class="benefit-label">रोगप्रतिकारक शक्ती वाढवते</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <h6 class="benefit-label">अँटी-एजिंग</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-brain"></i></div>
                <h6 class="benefit-label">स्मरणशक्ती सुधारते</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                <h6 class="benefit-label">रक्तदाब नियंत्रित करण्यास मदत करते</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-vial-circle-check"></i></div>
                <h6 class="benefit-label">अॅसिडिटी कमी करते</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-moon"></i></div>
                <h6 class="benefit-label">झोप सुधारते</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-filter"></i></div>
                <h6 class="benefit-label">डिटॉक्सिफिकेशन</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-weight-scale"></i></div>
                <h6 class="benefit-label">वजन कमी करण्यास मदत करते</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-apple-whole"></i></div>
                <h6 class="benefit-label">मधुमेह टाळण्यास मदत करते</h6>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fa-solid fa-dna"></i></div>
                <h6 class="benefit-label">कर्करोग टाळण्यास मदत करते</h6>
            </div>
        </div>
    </div>
</section>

<!-- 6. WATER COMPOSITION SECTION -->
<!-- 6. WATER COMPOSITION SECTION -->
<section class="composition-section">
    <div class="container animate-on-scroll">
        <div class="section-title text-center mb-5">
            <h2 class="display-5" style="letter-spacing: -1px; text-transform: capitalize;">पाण्याचे मूलभूत घटक</h2>
            <div class="divider mx-auto bg-warning rounded-pill mt-4" style="width: 40px; height: 3px;">~</div>
        </div>
        
        <div class="row align-items-center justify-content-center position-relative">
            <!-- Left Side Composition Details -->
            <div class="col-lg-3 text-center text-lg-end mb-5 mb-lg-0 order-2 order-lg-1">
                <div class="composition-item mb-5 pe-lg-5 position-relative">
                    <h4 class="font-heading fw-bold mb-1">पोटॅशियम</h4>
                    <h6 class="text-dark fw-bold mb-2">2.5 mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">पाणी शुद्ध करण्यासाठी प्रत्येक लिटरमागे २.५ मिलीग्राम पोटॅशियम आवश्यक असते.</p>
                    <div class="composition-line-left d-none d-lg-block"></div>
                </div>
                
                <div class="composition-item pe-lg-5 position-relative mt-5 pt-4">
                    <h4 class="font-heading fw-bold mb-1">फ्लोराईड</h4>
                    <h6 class="text-dark fw-bold mb-2">0.5 mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">१ लिटर पाणी शुद्ध करण्यासाठी ०.५ मिलीग्राम फ्लोराईड आवश्यक आहे.</p>
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
                    <h4 class="font-heading fw-bold mb-1">क्लोराईड</h4>
                    <h6 class="text-dark fw-bold mb-2">350 mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">पाणी शुद्ध करण्यासाठी प्रत्येक लिटरमागे ३५० मिलीग्राम क्लोरीन द्यावे लागते...</p>
                    <div class="composition-line-right d-none d-lg-block"></div>
                </div>

                <div class="composition-item ps-lg-5 position-relative mt-5 pt-4">
                    <h4 class="font-heading fw-bold mb-1">मॅग्नेशियम</h4>
                    <h6 class="text-dark fw-bold mb-2">14.5 mg/L</h6>
                    <p class="text-muted small lh-sm mb-0">प्रत्येक लिटर शुद्ध करण्यासाठी १४.५ मिलीग्राम मॅग्नेशियम आवश्यक असेल...</p>
                    <div class="composition-line-right d-none d-lg-block" style="top: 40%;"></div>
                </div>
            </div>
        </div>

        <!-- Bottom Stats Row -->
        <div class="row text-center justify-content-center pt-5 mt-4">
             <div class="col-6 col-md-3 mb-4 position-relative">
                 <h5 class="font-heading fw-bold mb-1 text-dark">नायट्रेट्स</h5>
                 <span class="text-muted small fw-bold">2 mg/L</span>
                 <div class="vr position-absolute end-0 top-50 translate-middle-y h-50 d-none d-md-block opacity-25"></div>
             </div>
             <div class="col-6 col-md-3 mb-4 position-relative">
                 <h5 class="font-heading fw-bold mb-1 text-dark">बायकार्बोनेट्स</h5>
                 <span class="text-muted small fw-bold">157 mg/L</span>
                 <div class="vr position-absolute end-0 top-50 translate-middle-y h-50 d-none d-md-block opacity-25"></div>
             </div>
             <div class="col-6 col-md-3 mb-4 position-relative">
                 <h5 class="font-heading fw-bold mb-1 text-dark">सल्फेट्स</h5>
                 <span class="text-muted small fw-bold">5.6 mg/L</span>
                 <div class="vr position-absolute end-0 top-50 translate-middle-y h-50 d-none d-md-block opacity-25"></div>
             </div>
             <div class="col-6 col-md-3 mb-4">
                 <h5 class="font-heading fw-bold mb-1 text-dark">सोडियम</h5>
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
                <h6 class="text-warning text-uppercase fw-bold mb-2">प्रीमियम सेवा</h6>
                <h2 class="display-5 fw-bold mb-4">पाण्याच्या बाटल्यांची डिलिव्हरी<br>आणि सेवा</h2>
                
                <ul class="cta-list">
                    <li><i class="fa-solid fa-circle-check"></i> सबस्क्रिप्शन प्लॅन्ससाठी मोफत डिलिव्हरी</li>
                    <li><i class="fa-solid fa-circle-check"></i> २४/७ ग्राहक सेवा</li>
                    <li><i class="fa-solid fa-circle-check"></i> कोणतेही लपलेले शुल्क नाही</li>
                </ul>

                <div class="d-flex gap-3 mt-4">
                    <button class="btn btn-primary shadow icon-link-hover" onclick="window.location.href='#products'">आत्ताच ऑर्डर करा</button>
                    <!-- <button class="btn btn-secondary shadow icon-link-hover">विनामूल्य अंदाज</button> -->
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
<?php
$testimonials = [];
try {
    $testimonials_stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC");
    $testimonials = $testimonials_stmt->fetchAll();
} catch (Exception $e) {}
?>
<section class="testimonials-section">
    <div class="container">
        <div class="section-title mb-5 animate-on-scroll text-center">
            <h6 class="text-primary text-uppercase fw-bold mb-2">अभिप्राय</h6>
            <h2>आमचे प्रशंसापत्र</h2>
        </div>

        <?php if (!empty($testimonials)): ?>
        <div id="testimonialCarousel" class="carousel slide testimonial-card animate-on-scroll" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($testimonials as $index => $t): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <div class="stars fs-4">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="fa-<?php echo $i <= $t['rating'] ? 'solid' : 'regular'; ?> fa-star text-warning"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="testimonial-text">"<?php echo htmlspecialchars($t['content']); ?>"</p>
                        <div class="avatars-box mt-4">
                            <img src="<?php echo !empty($t['photo_url']) ? htmlspecialchars($t['photo_url']) : 'https://img.freepik.com/free-photo/cheerful-indian-businessman-smiling-closeup-portrait-jobs-career-campaign_53876-129416.jpg'; ?>" alt="<?php echo htmlspecialchars($t['name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                        </div>
                        <h5 class="mt-3 fw-bold text-primary"><?php echo htmlspecialchars($t['name']); ?></h5>
                        <small class="text-muted">आनंदी ग्राहक</small>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($testimonials) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="text-muted">आमचे ग्राहक आमच्यावर प्रेम करतात! तुमचा अनुभव शेअर करणारे पहिले व्हा.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
 

<!-- 11. FOOTER -->
<?php include 'includes/footer.php'; ?>

<!-- Modals -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card p-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-dark">लॉगिन</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="auth_action.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">मोबाईल नंबर</label>
                        <input type="text" name="mobile" class="form-control rounded-pill px-3 bg-light border-0" placeholder="१०-अंकी मोबाईल नंबर" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">पासवर्ड</label>
                        <input type="password" name="password" class="form-control rounded-pill px-3 bg-light border-0" placeholder="******" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold shadow">लॉगिन करा</button>
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
                <h5 class="modal-title fw-bold">नवीन ग्राहक नोंदणी</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form action="auth_action.php" method="POST" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">पूर्ण नाव</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">मोबाईल नंबर</label>
                            <input type="text" name="mobile" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ग्राहकाचा प्रकार</label>
                            <select name="customer_type" class="form-select" required>
                                <option value="Home">घर (Home)</option>
                                <option value="Shop">दुकान (Shop)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">पासवर्ड</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">पत्ता</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">तुमचे स्थान निवडा (मार्कर ड्रॅग करा)</label>
                        <div id="map" style="height: 300px; border-radius: 10px;"></div>
                        <small class="text-muted">अधिक अचूकतेसाठी स्थान प्रवेशाची (Location Access) परवानगी द्या.</small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">नोंदणी करा</button>
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
                <h5 class="modal-title fw-bold">आत्ताच सबस्क्राईब करा</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <form action="place_order.php" method="POST">
                    <input type="hidden" name="product_id" id="modal_product_id">
                    <input type="hidden" name="price" id="modal_price">
                    
                    <div class="text-center mb-4">
                        <h4 id="modal_product_name" class="fw-bold mb-1 text-primary"></h4>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 border border-success">स्टॉकमध्ये आहे</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">वारंवारता (Frequency)</label>
                        <select name="order_type" class="form-select rounded-pill bg-light border-0" onchange="toggleCustomDays(this.value)">
                            <option value="Daily">दररोज डिलिव्हरी</option>
                            <option value="Alternate">एक दिवसाआड</option>
                            <option value="Custom">निवडक दिवस</option>
                        </select>
                    </div>

                     <div class="mb-3 d-none" id="customDaysDiv">
                        <label class="form-label small fw-bold text-muted">दिवस निवडा</label>
                        <div class="d-flex flex-wrap gap-2">
                             <?php 
                             $days_en = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                             $days_mr = ['सोम', 'मंगळ', 'बुध', 'गुरु', 'शुक्र', 'शनी', 'रवी'];
                             foreach($days_en as $index => $day): ?>
                                <input type="checkbox" class="btn-check" name="days[]" value="<?php echo $day; ?>" id="day-<?php echo $day; ?>" autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="day-<?php echo $day; ?>"><?php echo $days_mr[$index]; ?></label>
                             <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row align-items-center mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">प्रमाण (Quantity)</label>
                            <div class="input-group">
                                <button class="btn btn-outline-secondary rounded-start-pill" type="button" onclick="document.getElementById('orderQty').stepDown(); updateSummary();">-</button>
                                <input type="number" name="quantity" id="orderQty" class="form-control text-center border-secondary" value="1" min="1" onchange="updateSummary()">
                                <button class="btn btn-outline-secondary rounded-end-pill" type="button" onclick="document.getElementById('orderQty').stepUp(); updateSummary();">+</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-light p-3 rounded-4 mb-3 border border-dashed">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">उप-एकूण (Subtotal)</span>
                            <span class="fw-bold small" id="summarySubtotal">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span class="small">सवलत (Discount)</span>
                            <span class="fw-bold small" id="summaryDiscount">-₹0.00</span>
                        </div>
                        <div class="border-top pt-2 d-flex justify-content-between align-items-center mt-2">
                            <span class="fw-bold text-dark">एकूण (Total)</span>
                            <span class="fw-bolder fs-5 text-primary" id="summaryTotal">₹0.00</span>
                        </div>
                    </div>

                    <div class="mb-4">
                         <div class="input-group">
                            <input type="text" id="offerCodeInput" name="offer_code_input" class="form-control rounded-start-pill ps-3 bg-light border-0" placeholder="प्रोमो कोड">
                            <button type="button" class="btn btn-dark rounded-end-pill px-4" onclick="applyOffer()">लागू करा</button>
                        </div>
                        <small id="offerMessage" class="d-block mt-1 ms-2"></small>
                    </div>

                    <input type="hidden" name="offer_code" id="finalOfferCode">
                    <input type="hidden" name="discount_amount" id="finalDiscountAmount" value="0">

                    <button type="submit" class="btn btn-secondary w-100 rounded-pill py-3 fw-bold shadow text-white text-uppercase letter-spacing-1">सबस्क्रिप्शनची पुष्टी करा</button>
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
        addressBox.value = "पत्ता मिळवत आहे...";

        // Reverse Geocoding via Nominatim
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                if(data && data.display_name) {
                    addressBox.value = data.display_name;
                } else {
                    addressBox.value = "पत्त्याचा तपशील सापडला नाही. कृपया स्वतः टाईप करा.";
                }
            })
            .catch(err => {
                console.error("Geocoding error:", err);
                addressBox.value = ""; 
                addressBox.placeholder = "पत्ता मिळवता आला नाही. कृपया मॅन्युअली प्रविष्ट करा.";
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