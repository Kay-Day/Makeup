<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-5">
                <h4>Về Beauty Makeup Studio</h4>
                <p>Chúng tôi cung cấp dịch vụ trang điểm chuyên nghiệp, với đội ngũ nghệ sĩ tài năng và giàu kinh nghiệm. Chúng tôi cam kết mang đến vẻ đẹp rạng rỡ nhất cho khách hàng trong mọi dịp quan trọng.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6 mb-5">
                <h4>Liên kết nhanh</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="services.php">Dịch vụ</a></li>
                    <li><a href="artists.php">Nghệ sĩ</a></li>
                    <li><a href="gallery.php">Thư viện</a></li>
                    <li><a href="booking.php">Đặt lịch</a></li>
                    <li><a href="contact.php">Liên hệ</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-5">
                <h4>Dịch vụ</h4>
                <ul class="footer-links">
                    <li><a href="services.php">Trang điểm cô dâu</a></li>
                    <li><a href="services.php">Trang điểm dự tiệc</a></li>
                    <li><a href="services.php">Trang điểm nhẹ nhàng</a></li>
                    <li><a href="services.php">Trang điểm kỷ yếu</a></li>
                    <li><a href="services.php">Tất cả dịch vụ</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <h4>Liên hệ</h4>
                <div class="footer-contact">
                    <p><i class="fas fa-map-marker-alt"></i> 123 Nguyễn Huệ, Quận 1, TP HCM</p>
                    <p><i class="fas fa-phone"></i> 0987.654.321</p>
                    <p><i class="fas fa-envelope"></i> contact@beautymakeup.com</p>
                    <p><i class="fas fa-clock"></i> 08:00 - 20:00, T2 - CN</p>
                </div>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> Beauty Makeup Studio. Tất cả quyền được bảo lưu.</p>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</a>

<!-- JavaScript -->
<script>
    // Mobile Menu Toggle
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        document.getElementById('mainNav').classList.toggle('active');
        
        const icon = this.querySelector('i');
        if (icon.classList.contains('fa-bars')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });
    
    // Back to top button
    const backToTopButton = document.getElementById('backToTop');
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('show');
        } else {
            backToTopButton.classList.remove('show');
        }
    });
    
    backToTopButton.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    <?php if (isset($extraJS)) echo $extraJS; ?>
</script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>

</body>
</html>