<?php
// includes/footer.php - Footer component for all pages
?>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <!-- About BeautyClick -->
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="text-uppercase mb-4">
                        <i class="fas fa-palette me-2"></i>BeautyClick
                    </h5>
                    <p class="mb-4">
                        BeautyClick connects talented student makeup artists with clients looking for affordable 
                        and quality makeup services in Da Nang.
                    </p>
                    <div class="d-flex">
                        <a href="#" class="text-white me-3">
                            <i class="fab fa-facebook-f fa-lg"></i>
                        </a>
                        <a href="#" class="text-white me-3">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="#" class="text-white me-3">
                            <i class="fab fa-tiktok fa-lg"></i>
                        </a>
                        <a href="#" class="text-white">
                            <i class="fab fa-youtube fa-lg"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5 class="text-uppercase mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="/beautyclick/index.php" class="text-white text-decoration-none">
                                <i class="fas fa-home me-2"></i>Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="/beautyclick/services/index.php" class="text-white text-decoration-none">
                                <i class="fas fa-list me-2"></i>Services
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="/beautyclick/artists.php" class="text-white text-decoration-none">
                                <i class="fas fa-users me-2"></i>Artists
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="/beautyclick/about.php" class="text-white text-decoration-none">
                                <i class="fas fa-info-circle me-2"></i>About Us
                            </a>
                        </li>
                        <li>
                            <a href="/beautyclick/contact.php" class="text-white text-decoration-none">
                                <i class="fas fa-envelope me-2"></i>Contact
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h5 class="text-uppercase mb-4">Services</h5>
                    <ul class="list-unstyled">
                        <?php
                        // Get service categories
                        $categories = get_service_categories();
                        foreach (array_slice($categories, 0, 5) as $category):
                        ?>
                        <li class="mb-2">
                            <a href="/beautyclick/services/index.php?category=<?php echo $category['category_id']; ?>" 
                               class="text-white text-decoration-none">
                                <i class="fas fa-angle-right me-2"></i><?php echo $category['category_name']; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div class="col-lg-4 col-md-4">
                    <h5 class="text-uppercase mb-4">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            254 Nguyen Van Linh, Da Nang, Vietnam
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone me-2"></i>
                            +84 123 456 789
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope me-2"></i>
                            info@beautyclick.com
                        </li>
                        <li>
                            <i class="fas fa-clock me-2"></i>
                            Mon - Sat: 9:00 AM - 10:00 PM
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Locations -->
            <div class="row mt-4 pt-4 border-top">
                <div class="col-12 mb-3">
                    <h5 class="text-uppercase mb-3">Our Locations</h5>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="location-icon me-3">
                            <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Da Nang (Available)</h6>
                            <p class="small mb-0">254 Nguyen Van Linh, Da Nang, Vietnam</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="location-icon me-3">
                            <i class="fas fa-map-marker-alt fa-2x text-secondary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Ho Chi Minh City <span class="badge bg-warning text-dark">Coming Soon</span></h6>
                            <p class="small mb-0">123 Nguyen Hue, District 1, Ho Chi Minh City</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="location-icon me-3">
                            <i class="fas fa-map-marker-alt fa-2x text-secondary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Hanoi <span class="badge bg-warning text-dark">Coming Soon</span></h6>
                            <p class="small mb-0">45 Hang Bac, Hoan Kiem, Hanoi</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="location-icon me-3">
                            <i class="fas fa-map-marker-alt fa-2x text-secondary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Hue <span class="badge bg-warning text-dark">Coming Soon</span></h6>
                            <p class="small mb-0">18 Tran Hung Dao, Hue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="bg-primary text-center py-3 mt-5">
            <div class="container">
                <p class="mb-0">
                    &copy; <?php echo date('Y'); ?> BeautyClick. All rights reserved. Made with 
                    <i class="fas fa-heart text-danger"></i> for students.
                </p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="/beautyclick/assets/js/main.js"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>