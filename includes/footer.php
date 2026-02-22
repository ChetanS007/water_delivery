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
                <p class="mb-4 small">तुमच्या दारात आरोग्य आणि शुद्धता पोहोचवत आहोत. <br> आजच हजारो आनंदी ग्राहकांमध्ये सामील व्हा.</p>
                <div class="footer-social">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="footer-widget">
                    <h5>द्रुत दुवे</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="index.php" class="text-white text-decoration-none">मुख्यपृष्ठ</a></li>
                        <li class="mb-2"><a href="#features" class="text-white text-decoration-none">आमच्याबद्दल</a></li>
                        <li class="mb-2"><a href="#products" class="text-white text-decoration-none">उत्पादने</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">संपर्क</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                 <div class="footer-widget">
                    <h5>संपर्क</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="fa-solid fa-phone me-2 text-warning"></i> +91 9423121274</li>
                        <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i> info@sudhajal.com</li>
                        <li class="mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i> रवी नगर, परतवाडा, महाराष्ट्र ४४४८०५</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-top border-secondary mt-5 pt-4 text-center small text-white-50">
            &copy; <?php echo date("Y"); ?> सुधा जल. सर्व हक्क राखीव.
        </div>
    </div>
</footer>
<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
