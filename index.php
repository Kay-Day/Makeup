<?php
// Kết nối đến database
require_once 'config/db.php';
session_start();

// Thiết lập tiêu đề trang
$pageTitle = "Trang Chủ";

// CSS bổ sung cho trang chủ
$extraCSS = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
<style>
    /* Hero Section */
    .hero {
        height: 100vh;
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url("https://images.unsplash.com/photo-1562616261-37cd69a9e547?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80");
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        color: white;
        display: flex;
        align-items: center;
        position: relative;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
        text-transform: uppercase;
        letter-spacing: 3px;
        margin-bottom: 20px;
        color: var(--primary-light);
        position: relative;
        padding-left: 40px;
        display: inline-block;
    }
    
    .hero-subtitle::before {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        width: 30px;
        height: 2px;
        background: var(--primary-light);
    }
    
    .hero-title {
        font-size: 4rem;
        margin-bottom: 25px;
        line-height: 1.2;
    }
    
    .hero-scroll {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        animation: bounce 2s infinite;
        color: white;
        font-size: 20px;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% {
            transform: translateY(0) translateX(-50%);
        }
        40% {
            transform: translateY(-20px) translateX(-50%);
        }
        60% {
            transform: translateY(-10px) translateX(-50%);
        }
    }
    
    /* About Section */
    .about-img {
        position: relative;
        border-radius: var(--border-radius);
        overflow: hidden;
    }
    
    .about-img img {
        border-radius: var(--border-radius);
        transition: transform 0.5s ease;
    }
    
    .about-img:hover img {
        transform: scale(1.05);
    }
    
    .about-img::before {
        content: "";
        position: absolute;
        bottom: -20px;
        right: -20px;
        width: 70%;
        height: 70%;
        border: 5px solid var(--primary-color);
        border-radius: var(--border-radius);
        z-index: -1;
    }
    
    .about-subtitle {
        color: var(--primary-dark);
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 15px;
        position: relative;
        padding-left: 40px;
        display: inline-block;
    }
    
    .about-subtitle::before {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        width: 30px;
        height: 2px;
        background: var(--primary-dark);
    }
    
    .feature-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .feature-icon {
        width: 40px;
        height: 40px;
        background: var(--primary-light);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-dark);
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    /* Section Styles */
    .section-header {
        text-align: center;
        margin-bottom: 60px;
    }
    
    .section-subtitle {
        display: inline-block;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--primary-dark);
        margin-bottom: 15px;
        position: relative;
    }
    
    .section-subtitle::before, .section-subtitle::after {
        content: "";
        position: absolute;
        top: 50%;
        width: 30px;
        height: 1px;
        background: var(--primary-dark);
    }
    
    .section-subtitle::before {
        left: -40px;
    }
    
    .section-subtitle::after {
        right: -40px;
    }
    
    .section-title {
        font-size: 2.5rem;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 20px;
    }
    
    .section-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: var(--primary-color);
    }
    
    /* Testimonials Section */
    .testimonials-section {
        background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), url("https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80");
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        color: white;
        padding: 100px 0;
    }
    
    .testimonials-section .section-subtitle,
    .testimonials-section .section-title {
        color: white;
    }
    
    .testimonials-section .section-subtitle::before,
    .testimonials-section .section-subtitle::after {
        background: white;
    }
    
    .testimonial-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-radius: var(--border-radius);
        padding: 30px;
        position: relative;
        margin: 20px 10px;
    }
    
    .testimonial-card::before {
        content: "\f10d";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        position: absolute;
        top: 20px;
        left: 20px;
        font-size: 24px;
        color: rgba(255, 255, 255, 0.2);
    }
    
    .testimonial-content {
        margin-bottom: 20px;
        font-size: 1rem;
        line-height: 1.8;
    }
    
    .testimonial-author {
        display: flex;
        align-items: center;
    }
    
    .author-img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 15px;
        border: 3px solid var(--primary-color);
    }
    
    .author-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .author-info {
        text-align: left;
    }
    
    .author-name {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 5px;
    }
    
    .author-title {
        color: #bbb;
        font-size: 0.9rem;
    }
    
    /* CTA Section */
    .cta-section {
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: white;
        text-align: center;
        padding: 80px 0;
    }
    
    .cta-title {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: white;
    }
    
    .row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -15px;
    }
    
    .col-lg-6, .col-lg-4, .col-lg-3, .col-lg-2, .col-md-6 {
        padding: 0 15px;
        box-sizing: border-box;
    }
    
    .col-lg-6 {
        width: 50%;
    }
    
    .col-lg-4 {
        width: 33.333333%;
    }
    
    .col-lg-3 {
        width: 25%;
    }
    
    .col-lg-2 {
        width: 16.666667%;
    }
    
    .lead {
        font-size: 1.2rem;
        line-height: 1.5;
        margin-bottom: 1.5rem;
    }
    
    .mb-5 {
        margin-bottom: 3rem;
    }
    
    .mt-5 {
        margin-top: 3rem;
    }
    
    .text-center {
        text-align: center;
    }
    
    .justify-content-center {
        justify-content: center;
    }
    
    .owl-carousel .owl-dots {
        text-align: center;
        margin-top: 20px;
    }
    
    .owl-carousel .owl-dot {
        display: inline-block;
        margin: 0 5px;
    }
    
    .owl-carousel .owl-dot span {
        display: block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.3);
        transition: all 0.3s;
    }
    
    .owl-carousel .owl-dot.active span {
        background-color: var(--primary-color);
        width: 16px;
        border-radius: 5px;
    }
    
    @media (max-width: 991px) {
        .col-lg-6, .col-lg-4, .col-lg-3, .col-lg-2 {
            width: 100%;
        }
        
        .hero-title {
            font-size: 3rem;
        }
        
        .about-img::before {
            display: none;
        }
        
        .about-img {
            margin-bottom: 30px;
        }
    }
    
    @media (max-width: 991px) and (min-width: 768px) {
        .col-md-6 {
            width: 50%;
        }
    }
    
    @media (max-width: 767px) {
        .col-md-6 {
            width: 100%;
        }
        
        .hero-title {
            font-size: 2.2rem;
        }
    }
    
    .back-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 40px;
        height: 40px;
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        z-index: 99;
    }
    
    .back-to-top.show {
        opacity: 1;
        visibility: visible;
    }
    
    .back-to-top:hover {
        background-color: var(--primary-dark);
        transform: translateY(-3px);
    }
</style>';

// Script bổ sung cho trang chủ
$extraScripts = '
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });
    
    // Initialize Owl Carousel for testimonials
    $(document).ready(function(){
        $(".testimonials-carousel").owlCarousel({
            items: 1,
            loop: true,
            margin: 20,
            nav: false,
            dots: true,
            autoplay: true,
            autoplayTimeout: 5000,
            smartSpeed: 1000,
            responsive: {
                768: {
                    items: 2
                }
            }
        });
        
        // Smooth scroll for anchor links
        $(\'a[href^="#"]\').on(\'click\', function(e) {
            e.preventDefault();
            var target = $(this.hash);
            if (target.length) {
                $(\'html, body\').animate({
                    scrollTop: target.offset().top - 70
                }, 800);
            }
        });
    });
</script>';

// Lấy danh sách dịch vụ nổi bật
$services_query = "SELECT * FROM services WHERE is_active = 1 ORDER BY price DESC LIMIT 4";
$services_result = $conn->query($services_query);
$services = [];
if ($services_result && $services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Lấy danh sách nghệ sĩ nổi bật
$artists_query = "SELECT a.*, u.fullname, u.email, u.phone 
                 FROM artists a 
                 JOIN users u ON a.user_id = u.user_id 
                 WHERE a.is_available = 1 
                 ORDER BY a.experience DESC LIMIT 3";
$artists_result = $conn->query($artists_query);
$artists = [];
if ($artists_result && $artists_result->num_rows > 0) {
    while ($row = $artists_result->fetch_assoc()) {
        $artists[] = $row;
    }
}

// Lấy đánh giá từ khách hàng
$reviews_query = "SELECT r.*, u.fullname, s.name as service_name 
                FROM reviews r
                JOIN bookings b ON r.booking_id = b.booking_id
                JOIN users u ON b.customer_id = u.user_id
                JOIN services s ON b.service_id = s.service_id
                ORDER BY r.rating DESC, r.created_at DESC
                LIMIT 5";
$reviews_result = $conn->query($reviews_query);
$reviews = [];
if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Gọi header
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="container">
        <div class="row">
            <div class="col-lg-6 col-md-10">
                <div data-aos="fade-up" data-aos-delay="100">
                    <span class="hero-subtitle">Chào mừng đến Beauty Makeup</span>
                    <h1 class="hero-title">Tôn vinh vẻ đẹp tự nhiên của bạn</h1>
                    <p class="lead mb-5">Chúng tôi cung cấp dịch vụ trang điểm chuyên nghiệp với các nghệ sĩ tài năng. Đặt lịch ngay để có vẻ ngoài hoàn hảo cho mọi dịp.</p>
                    <div style="display: flex; gap: 15px;">
                        <a href="booking.php" class="btn btn-primary btn-lg">Đặt lịch ngay</a>
                        <a href="#services" class="btn btn-outline">Xem dịch vụ</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a href="#about" class="hero-scroll">
        <i class="fas fa-chevron-down"></i>
    </a>
</section>

<!-- About Section -->
<section class="section" id="about">
    <div class="container">
        <div class="row">
            <div class="col-lg-6" data-aos="fade-right">
                <div class="about-img">
                    <img src="https://images.unsplash.com/photo-1560869713-7d0a29430803?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Về chúng tôi" style="width: 100%;">
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <span class="about-subtitle">Về chúng tôi</span>
                <h2 class="h1" style="margin-bottom: 20px;">Mang đến vẻ đẹp hoàn hảo cho mọi khoảnh khắc</h2>
                <p class="lead" style="margin-bottom: 20px;">Beauty Makeup Studio là thương hiệu trang điểm chuyên nghiệp hàng đầu, với đội ngũ nghệ sĩ trang điểm tài năng và kinh nghiệm giúp tôn vinh vẻ đẹp tự nhiên của bạn.</p>
                
                <div class="row" style="margin-top: 30px;">
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h5>Chất lượng hàng đầu</h5>
                                <p>Dịch vụ và sản phẩm chất lượng cao</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-gem"></i>
                            </div>
                            <div>
                                <h5>Nghệ sĩ tài năng</h5>
                                <p>Đội ngũ chuyên nghiệp, giàu kinh nghiệm</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-smile"></i>
                            </div>
                            <div>
                                <h5>100% hài lòng</h5>
                                <p>Cam kết đem lại sự hài lòng tuyệt đối</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h5>Đúng giờ</h5>
                                <p>Tôn trọng thời gian của khách hàng</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <a href="booking.php" class="btn btn-primary" style="margin-top: 30px;">Đặt lịch ngay</a>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="section" id="services" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-subtitle">Dịch vụ của chúng tôi</span>
            <h2 class="section-title">Các dịch vụ trang điểm</h2>
            <p>Chúng tôi cung cấp đa dạng dịch vụ trang điểm chuyên nghiệp, phù hợp với mọi dịp từ cưới hỏi, sự kiện đến trang điểm hàng ngày.</p>
        </div>
        
        <div class="row">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="card" style="margin-bottom: 30px;">
                            <div class="card-img">
                                <img src="images/services/<?php echo $service['service_id']; ?>.jpg" alt="<?php echo htmlspecialchars($service['name']); ?>" onerror="this.src='https://via.placeholder.com/400x250?text=<?php echo urlencode($service['name']); ?>'">
                                <div style="position: absolute; top: 15px; right: 15px; background: var(--primary-color); color: white; padding: 5px 15px; border-radius: 30px; font-weight: 600;"><?php echo number_format($service['price'], 0, ',', '.'); ?> VNĐ</div>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                                <div style="display: flex; margin-bottom: 15px;">
                                    <div style="display: flex; align-items: center; margin-right: 15px; font-size: 14px; color: #777;">
                                        <i class="far fa-clock" style="color: var(--primary-color); margin-right: 5px;"></i>
                                        <?php echo floor($service['duration']/60); ?> giờ <?php echo $service['duration'] % 60; ?> phút
                                    </div>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                <a href="booking.php?service=<?php echo $service['service_id']; ?>" class="btn btn-primary">Đặt lịch</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-md-4" data-aos="fade-up">
                    <div class="card" style="margin-bottom: 30px;">
                        <div class="card-img">
                            <img src="https://via.placeholder.com/400x250?text=Trang+điểm+cô+dâu" alt="Trang điểm cô dâu">
                            <div style="position: absolute; top: 15px; right: 15px; background: var(--primary-color); color: white; padding: 5px 15px; border-radius: 30px; font-weight: 600;">2,000,000 VNĐ</div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">Trang điểm cô dâu</h3>
                            <div style="display: flex; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; margin-right: 15px; font-size: 14px; color: #777;">
                                    <i class="far fa-clock" style="color: var(--primary-color); margin-right: 5px;"></i>
                                    2 giờ
                                </div>
                            </div>
                            <p class="card-text">Gói trang điểm cô dâu hoàn chỉnh bao gồm makeup và làm tóc, giúp bạn tỏa sáng trong ngày trọng đại.</p>
                            <a href="booking.php?service=1" class="btn btn-primary">Đặt lịch</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card" style="margin-bottom: 30px;">
                        <div class="card-img">
                            <img src="https://via.placeholder.com/400x250?text=Trang+điểm+dự+tiệc" alt="Trang điểm dự tiệc">
                            <div style="position: absolute; top: 15px; right: 15px; background: var(--primary-color); color: white; padding: 5px 15px; border-radius: 30px; font-weight: 600;">800,000 VNĐ</div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">Trang điểm dự tiệc</h3>
                            <div style="display: flex; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; margin-right: 15px; font-size: 14px; color: #777;">
                                    <i class="far fa-clock" style="color: var(--primary-color); margin-right: 5px;"></i>
                                    1 giờ
                                </div>
                            </div>
                            <p class="card-text">Trang điểm cho các sự kiện quan trọng, giúp bạn nổi bật và tự tin trong mọi bữa tiệc.</p>
                            <a href="booking.php?service=2" class="btn btn-primary">Đặt lịch</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card" style="margin-bottom: 30px;">
                        <div class="card-img">
                            <img src="https://via.placeholder.com/400x250?text=Trang+điểm+nhẹ+nhàng" alt="Trang điểm nhẹ nhàng">
                            <div style="position: absolute; top: 15px; right: 15px; background: var(--primary-color); color: white; padding: 5px 15px; border-radius: 30px; font-weight: 600;">500,000 VNĐ</div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">Trang điểm nhẹ nhàng</h3>
                            <div style="display: flex; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; margin-right: 15px; font-size: 14px; color: #777;">
                                    <i class="far fa-clock" style="color: var(--primary-color); margin-right: 5px;"></i>
                                    45 phút
                                </div>
                            </div>
                            <p class="card-text">Trang điểm nhẹ phù hợp cho công sở hoặc đi chơi, tôn lên vẻ đẹp tự nhiên của bạn.</p>
                            <a href="booking.php?service=3" class="btn btn-primary">Đặt lịch</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="services.php" class="btn btn-primary">Xem tất cả dịch vụ</a>
        </div>
    </div>
</section>

<!-- Artists Section -->
<section class="section" id="artists">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-subtitle">Đội ngũ chuyên nghiệp</span>
            <h2 class="section-title">Nghệ sĩ trang điểm của chúng tôi</h2>
            <p>Gặp gỡ đội ngũ nghệ sĩ trang điểm tài năng và giàu kinh nghiệm của chúng tôi, những người sẽ giúp bạn tỏa sáng.</p>
        </div>
        
        <div class="row">
            <?php if (count($artists) > 0): ?>
                <?php foreach ($artists as $artist): ?>
                    <div class="col-lg-4 col-md-6" data-aos="fade-up">
                        <div class="card" style="margin-bottom: 30px; text-align: center;">
                            <div style="height: 120px; background: linear-gradient(to right, var(--primary-color), var(--secondary-color)); position: relative;">
                                <div style="position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%); width: 100px; height: 100px; border-radius: 50%; border: 5px solid white; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); overflow: hidden;">
                                    <img src="images/artists/<?php echo $artist['artist_id']; ?>.jpg" alt="<?php echo htmlspecialchars($artist['fullname']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://via.placeholder.com/100x100?text=<?php echo substr($artist['fullname'], 0, 1); ?>'">
                                </div>
                            </div>
                            <div class="card-body" style="padding-top: 60px;">
                                <h3 class="card-title"><?php echo htmlspecialchars($artist['fullname']); ?></h3>
                                <p style="color: var(--primary-color); font-weight: 600; margin-bottom: 15px;"><?php echo $artist['experience']; ?> năm kinh nghiệm</p>
                                <p class="card-text" style="margin-bottom: 15px;"><?php echo htmlspecialchars(substr($artist['description'], 0, 120)); ?>...</p>
                                
                                <?php if (!empty($artist['home_address']) || !empty($artist['work_address'])): ?>
                                    <div style="margin-bottom: 15px;">
                                        <?php if (!empty($artist['home_address'])): ?>
                                            <small><i class="fas fa-home" style="margin-right: 5px;"></i><?php echo htmlspecialchars($artist['home_address']); ?></small><br>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($artist['work_address'])): ?>
                                            <small><i class="fas fa-building" style="margin-right: 5px;"></i><?php echo htmlspecialchars($artist['work_address']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="display: flex; justify-content: center; gap: 10px; margin: 20px 0;">
                                    <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.3s ease;"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.3s ease;"><i class="fab fa-instagram"></i></a>
                                    <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.3s ease;"><i class="fab fa-tiktok"></i></a>
                                </div>
                                
                                <a href="artist-detail.php?id=<?php echo $artist['artist_id']; ?>" class="btn btn-outline">Xem chi tiết</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Thêm các nghệ sĩ mẫu nếu không có dữ liệu -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="card" style="margin-bottom: 30px; text-align: center;">
                        <div style="height: 120px; background: linear-gradient(to right, var(--primary-color), var(--secondary-color)); position: relative;">
                            <div style="position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%); width: 100px; height: 100px; border-radius: 50%; border: 5px solid white; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); overflow: hidden;">
                                <img src="https://via.placeholder.com/100x100?text=HM" alt="Nguyễn Hà My" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        </div>
                        <div class="card-body" style="padding-top: 60px;">
                            <h3 class="card-title">Nguyễn Hà My</h3>
                            <p style="color: var(--primary-color); font-weight: 600; margin-bottom: 15px;">5 năm kinh nghiệm</p>
                            <p class="card-text" style="margin-bottom: 15px;">Chuyên gia trang điểm cô dâu và sự kiện với phong cách tự nhiên, nhẹ nhàng...</p>
                            <div style="margin-bottom: 15px;">
                                <small><i class="fas fa-home" style="margin-right: 5px;"></i>Số 10, Đường Trần Phú, Quận Hoàn Kiếm, Hà Nội</small><br>
                                <small><i class="fas fa-building" style="margin-right: 5px;"></i>Diamond Beauty Studio, 25 Hai Bà Trưng, Quận 1, TP HCM</small>
                            </div>
                            <div style="display: flex; justify-content: center; gap: 10px; margin: 20px 0;">
                                <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.3s ease;"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.3s ease;"><i class="fab fa-instagram"></i></a>
                                <a href="#" style="width: 36px; height: 36px; border-radius: 50%; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #666; transition: all 0.3s ease;"><i class="fab fa-tiktok"></i></a>
                            </div>
                            <a href="artist-detail.php?id=1" class="btn btn-outline">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
                <!-- Thêm các nghệ sĩ mẫu khác nếu cần -->
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="artists.php" class="btn btn-primary">Xem tất cả nghệ sĩ</a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section" id="testimonials">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-subtitle">Phản hồi</span>
            <h2 class="section-title">Khách hàng nói gì về chúng tôi</h2>
            <p>Những chia sẻ chân thật từ khách hàng đã sử dụng dịch vụ của Beauty Makeup Studio</p>
        </div>
        
        <div class="row">
            <div class="col-lg-10" style="margin: 0 auto;">
                <div class="owl-carousel testimonials-carousel">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div data-aos="fade-up">
                                <div class="testimonial-card">
                                    <div class="testimonial-content">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </div>
                                    <div class="testimonial-author">
                                        <div class="author-img">
                                            <img src="https://via.placeholder.com/60x60?text=<?php echo substr($review['fullname'], 0, 1); ?>" alt="<?php echo htmlspecialchars($review['fullname']); ?>">
                                        </div>
                                        <div class="author-info">
                                            <h4 class="author-name"><?php echo htmlspecialchars($review['fullname']); ?></h4>
                                            <p class="author-title">Khách hàng dịch vụ <?php echo htmlspecialchars($review['service_name']); ?></p>
                                            <div class="rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'active' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Thêm đánh giá mẫu nếu không có dữ liệu -->
                        <div data-aos="fade-up">
                            <div class="testimonial-card">
                                <div class="testimonial-content">
                                    "Tôi đã rất hài lòng với dịch vụ trang điểm cô dâu tại Beauty Makeup Studio. Nghệ sĩ đã lắng nghe ý kiến của tôi và tạo ra một vẻ ngoài hoàn hảo cho ngày trọng đại của tôi. Tất cả mọi người đều khen ngợi về lớp trang điểm tự nhiên và bền đẹp suốt cả ngày."
                                </div>
                                <div class="testimonial-author">
                                    <div class="author-img">
                                        <img src="https://via.placeholder.com/60x60?text=N" alt="Nguyễn Thị Anh">
                                    </div>
                                    <div class="author-info">
                                        <h4 class="author-name">Nguyễn Thị Anh</h4>
                                        <p class="author-title">Khách hàng dịch vụ trang điểm cô dâu</p>
                                        <div class="rating">
                                            <i class="fas fa-star active"></i>
                                            <i class="fas fa-star active"></i>
                                            <i class="fas fa-star active"></i>
                                            <i class="fas fa-star active"></i>
                                            <i class="fas fa-star active"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Thêm đánh giá mẫu khác nếu cần -->
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section" id="cta">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h2 class="cta-title">Sẵn sàng để tỏa sáng?</h2>
                <p class="lead mb-5">Đặt lịch ngay hôm nay và để chúng tôi giúp bạn trở nên xinh đẹp hơn.</p>
                <a href="booking.php" class="btn btn-lg" style="background-color: white; color: var(--primary-dark);">Đặt lịch ngay</a>
            </div>
        </div>
    </div>
</section>

<?php
// Gọi footer
include 'includes/footer.php';
?>