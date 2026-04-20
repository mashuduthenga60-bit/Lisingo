    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 pt-5 pb-3">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5><i class="bi bi-shop"></i> <?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">South Africa's trusted C2C marketplace. Buy and sell directly with other customers. Safe, simple, and secure.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/" class="text-muted text-decoration-none">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php" class="text-muted text-decoration-none">Browse</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/sell.php" class="text-muted text-decoration-none">Sell Item</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6>Categories</h6>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=1" class="text-muted text-decoration-none">Electronics</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=2" class="text-muted text-decoration-none">Fashion</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=3" class="text-muted text-decoration-none">Home & Garden</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/products.php?category=4" class="text-muted text-decoration-none">Vehicles</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6>Contact Us</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="bi bi-geo-alt"></i> Pretoria, South Africa</li>
                        <li><i class="bi bi-telephone"></i> +27 12 345 6789</li>
                        <li><i class="bi bi-envelope"></i> support@lisingo.co.za</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <small>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. | C2C E-Commerce Platform</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
