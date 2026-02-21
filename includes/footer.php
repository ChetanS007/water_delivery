<?php
// Fetch system logo if not already fetched
if (!isset($sysLogo)) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo'");
    $stmt->execute();
    $sysLogo = $stmt->fetchColumn() ?: 'assets/images/logo.png';
}
?>
<footer class="main-footer">
    <!--<div class="footer-wave"></div>  Wave Divider -->
    <div class="container">
        <div class="row gy-5">
            <div class="col-lg-5">
                <div class="mb-4">
                    <img src="<?php echo htmlspecialchars($sysLogo); ?>" alt="Sudha Jal" style="max-height: 50px;">
                </div>
                <p class="mb-4 small">Delivering health and purity to your doorstep. Join thousands of happy customers today.</p>
                <div class="footer-social">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="footer-widget">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Products</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                 <div class="footer-widget">
                    <h5>Contact</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i> +1 (234) 567-890</li>
                        <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i> info@sudhajal.com</li>
                        <li class="mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i> 123 Water St, NY</li>
                    </ul>
                </div>
            </div>
            <!-- <div class="col-lg-3">
                 <div class="footer-widget">
                    <h5>Newsletter</h5>
                    <form>
                        <input type="email" class="newsletter-input" placeholder="Your Email Address">
                        <button type="button" class="btn btn-secondary w-100 rounded-pill btn-sm">Subscribe</button>
                    </form>
                </div>
            </div> -->
        </div>
        <div class="border-top border-secondary mt-5 pt-4 text-center small text-white-50">
            &copy; <?php echo date("Y"); ?> Sudha Jal. All rights reserved.
        </div>
    </div>
</footer>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
