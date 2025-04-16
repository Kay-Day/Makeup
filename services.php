<?php
// Kết nối đến database
require_once 'config/db.php';
session_start();

// Thiết lập tiêu đề trang
$pageTitle = "Dịch vụ";

// CSS bổ sung
$extraCSS = '
<style>
    .services-banner {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url("https://images.unsplash.com/photo-1487412912498-0447579c8d4d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80");
        background-size: cover;
        background-position: center;
        padding: 100px 0;
        color: white;
        text-align: center;
    }
    
    .services-banner h1 {
        font-size: 3rem;
        margin-bottom: 20px;
        color: white;
    }
    
    .services-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 80px 15px;
    }
    
    .services-header {
        text-align: center;
        margin-bottom: 60px;
    }
    
    .services-header h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 20px;
    }
    
    .services-header h2::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: var(--primary-color);
    }
    
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
    }
    
    .service-card {
        background-color: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    
    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }
    
    .service-img {
        height: 250px;
        position: relative;
        overflow: hidden;
    }
    
    .service-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .service-card:hover .service-img img {
        transform: scale(1.1);
    }
    
    .service-price {
        position: absolute;
        top: 20px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        padding: 8px 15px;
        border-radius: 30px;
        font-weight: 600;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .service-content {
        padding: 30px;
    }
    
    .service-title {
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: var(--heading-color);
    }
    
    .service-meta {
        display: flex;
        margin-bottom: 15px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        margin-right: 20px;
        color: #777;
        font-size: 0.9rem;
    }
    
    .meta-item i {
        color: var(--primary-color);
        margin-right: 5px;
    }
    
    .service-description {
        color: var(--text-color);
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .category-filter {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 40px;
        gap: 10px;
    }
    
    .category-btn {
        background-color: white;
        border: 2px solid var(--primary-light);
        color: var(--primary-dark);
        padding: 8px 20px;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .category-btn:hover,
    .category-btn.active {
        background-color: var(--primary-color);
        color: white;
    }
    
    @media (max-width: 991px) {
        .services-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .services-banner h1 {
            font-size: 2.5rem;
        }
        
        .services-header h2 {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 576px) {
        .services-grid {
            grid-template-columns: 1fr;
        }
        
        .services-banner h1 {
            font-size: 2rem;
        }
    }
</style>';

// JavaScript bổ sung
$extraJS = '
// Lọc dịch vụ theo danh mục
const filterButtons = document.querySelectorAll(".category-btn");
const serviceCards = document.querySelectorAll(".service-card");

filterButtons.forEach(button => {
    button.addEventListener("click", function() {
        // Loại bỏ active class từ tất cả các nút
        filterButtons.forEach(btn => btn.classList.remove("active"));
        
        // Thêm active class cho nút được click
        this.classList.add("active");
        
        const category = this.getAttribute("data-category");
        
        // Hiển thị/ẩn các dịch vụ theo danh mục
        serviceCards.forEach(card => {
            if (category === "all") {
                card.style.display = "block";
            } else {
                if (card.getAttribute("data-category") === category) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            }
        });
    });
});';

// Truy vấn danh sách dịch vụ
$query = "SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC";
$result = $conn->query($query);
$services = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Gọi header
include 'includes/header.php';
?>

<!-- Banner Dịch vụ -->
<div class="services-banner">
    <div class="container">
        <h1>Dịch vụ trang điểm</h1>
        <p class="lead">Khám phá các dịch vụ trang điểm chuyên nghiệp của chúng tôi</p>
    </div>
</div>

<div class="services-container">
    <div class="services-header" data-aos="fade-up">
        <h2>Các dịch vụ của chúng tôi</h2>
        <p>Chúng tôi cung cấp nhiều dịch vụ trang điểm chuyên nghiệp, phù hợp với nhu cầu và dịp của bạn</p>
    </div>
    
    <!-- Bộ lọc danh mục -->
    <div class="category-filter" data-aos="fade-up">
        <button class="category-btn active" data-category="all">Tất cả</button>
        <button class="category-btn" data-category="wedding">Cô dâu</button>
        <button class="category-btn" data-category="party">Dự tiệc</button>
        <button class="category-btn" data-category="natural">Nhẹ nhàng</button>
        <button class="category-btn" data-category="photo">Chụp ảnh</button>
    </div>
    
    <!-- Danh sách dịch vụ -->
    <div class="services-grid">
        <?php if (count($services) > 0): ?>
            <?php foreach ($services as $service): 
                // Xác định danh mục dựa trên tên dịch vụ (đơn giản hóa cho demo)
                $category = "party"; // Mặc định là dự tiệc
                if (stripos($service['name'], 'cô dâu') !== false) {
                    $category = "wedding";
                } elseif (stripos($service['name'], 'nhẹ') !== false || stripos($service['name'], 'natural') !== false) {
                    $category = "natural";
                } elseif (stripos($service['name'], 'kỷ yếu') !== false || stripos($service['name'], 'ảnh') !== false) {
                    $category = "photo";
                }
            ?>
                <div class="service-card" data-category="<?php echo $category; ?>" data-aos="fade-up">
                    <div class="service-img">
                        <img src="images/services/<?php echo $service['service_id']; ?>.jpg" alt="<?php echo htmlspecialchars($service['name']); ?>" onerror="this.src='https://via.placeholder.com/400x250?text=<?php echo urlencode($service['name']); ?>'">
                        <div class="service-price"><?php echo number_format($service['price'], 0, ',', '.'); ?> VNĐ</div>
                    </div>
                    <div class="service-content">
                        <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                        <div class="service-meta">
                            <div class="meta-item">
                                <i class="far fa-clock"></i>
                                <?php echo floor($service['duration']/60); ?> giờ <?php echo $service['duration'] % 60; ?> phút
                            </div>
                        </div>
                        <p class="service-description"><?php echo htmlspecialchars($service['description']); ?></p>
                        <a href="booking.php?service=<?php echo $service['service_id']; ?>" class="btn btn-primary">Đặt lịch</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Hiển thị các dịch vụ mẫu nếu không có dữ liệu -->
            <div class="service-card" data-category="wedding" data-aos="fade-up">
                <div class="service-img">
                    <img src="https://via.placeholder.com/400x250?text=Trang+điểm+cô+dâu" alt="Trang điểm cô dâu">
                    <div class="service-price">2,000,000 VNĐ</div>
                </div>
                <div class="service-content">
                    <h3 class="service-title">Trang điểm cô dâu</h3>
                    <div class="service-meta">
                        <div class="meta-item">
                            <i class="far fa-clock"></i>
                            2 giờ
                        </div>
                    </div>
                    <p class="service-description">Gói trang điểm cô dâu hoàn chỉnh bao gồm makeup và làm tóc, giúp bạn tỏa sáng trong ngày trọng đại. Chúng tôi sử dụng các sản phẩm cao cấp với độ bền cao, phù hợp cho ngày cưới kéo dài.</p>
                    <a href="booking.php?service=1" class="btn btn-primary">Đặt lịch</a>
                </div>
            </div>
            
            <div class="service-card" data-category="party" data-aos="fade-up">
                <div class="service-img">
                    <img src="https://via.placeholder.com/400x250?text=Trang+điểm+dự+tiệc" alt="Trang điểm dự tiệc">
                    <div class="service-price">800,000 VNĐ</div>
                </div>
                <div class="service-content">
                    <h3 class="service-title">Trang điểm dự tiệc</h3>
                    <div class="service-meta">
                        <div class="meta-item">
                            <i class="far fa-clock"></i>
                            1 giờ
                        </div>
                    </div>
                    <p class="service-description">Trang điểm cho các sự kiện quan trọng, giúp bạn nổi bật và tự tin trong mọi bữa tiệc. Chúng tôi sẽ tạo nên vẻ đẹp lộng lẫy nhưng vẫn giữ được nét tự nhiên thanh lịch.</p>
                    <a href="booking.php?service=2" class="btn btn-primary">Đặt lịch</a>
                </div>
            </div>
            
            <div class="service-card" data-category="natural" data-aos="fade-up">
                <div class="service-img">
                    <img src="https://via.placeholder.com/400x250?text=Trang+điểm+nhẹ+nhàng" alt="Trang điểm nhẹ nhàng">
                    <div class="service-price">500,000 VNĐ</div>
                </div>
                <div class="service-content">
                    <h3 class="service-title">Trang điểm nhẹ nhàng</h3>
                    <div class="service-meta">
                        <div class="meta-item">
                            <i class="far fa-clock"></i>
                            45 phút
                        </div>
                    </div>
                    <p class="service-description">Trang điểm nhẹ phù hợp cho công sở hoặc đi chơi, tôn lên vẻ đẹp tự nhiên của bạn. Đây là lựa chọn hoàn hảo cho những ai muốn có vẻ ngoài tươi tắn trong sinh hoạt hàng ngày.</p>
                    <a href="booking.php?service=3" class="btn btn-primary">Đặt lịch</a>
                </div>
            </div>
            
            <div class="service-card" data-category="photo" data-aos="fade-up">
                <div class="service-img">
                    <img src="https://via.placeholder.com/400x250?text=Trang+điểm+kỷ+yếu" alt="Trang điểm kỷ yếu">
                    <div class="service-price">600,000 VNĐ</div>
                </div>
                <div class="service-content">
                    <h3 class="service-title">Trang điểm kỷ yếu</h3>
                    <div class="service-meta">
                        <div class="meta-item">
                            <i class="far fa-clock"></i>
                            50 phút
                        </div>
                    </div>
                    <p class="service-description">Trang điểm cho buổi chụp ảnh kỷ yếu, giúp bạn có những bức ảnh đẹp lưu giữ kỷ niệm. Chúng tôi sẽ tạo nên vẻ ngoài trẻ trung, rạng rỡ nhưng vẫn giữ nét đặc trưng của bạn.</p>
                    <a href="booking.php?service=4" class="btn btn-primary">Đặt lịch</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Phần Đặt lịch -->
<section class="cta-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8" data-aos="fade-up">
                <h2 class="cta-title">Bạn đã tìm thấy dịch vụ phù hợp?</h2>
                <p class="lead mb-5">Đặt lịch ngay hôm nay để nhận được ưu đãi đặc biệt!</p>
                <a href="booking.php" class="btn btn-lg" style="background-color: white; color: var(--primary-dark);">Đặt lịch ngay</a>
            </div>
        </div>
    </div>
</section>

<?php
// Gọi footer
include 'includes/footer.php';
?>