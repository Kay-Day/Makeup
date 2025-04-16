<?php
// Kết nối database
require_once 'config/db.php';

// Lấy ID của nghệ sĩ từ tham số URL
$artist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Truy vấn thông tin chi tiết nghệ sĩ
$query = "SELECT a.*, u.fullname, u.email, u.phone 
          FROM artists a
          JOIN users u ON a.user_id = u.user_id
          WHERE a.artist_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $artist_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Nếu không tìm thấy nghệ sĩ, chuyển hướng về trang danh sách
    header("Location: artists.php");
    exit;
}

$artist = $result->fetch_assoc();

// Truy vấn các dịch vụ mà nghệ sĩ này thực hiện (nếu có)
$query_services = "SELECT DISTINCT s.* 
                  FROM services s
                  JOIN bookings b ON s.service_id = b.service_id
                  WHERE b.artist_id = ?";

$stmt_services = $conn->prepare($query_services);
$stmt_services->bind_param("i", $artist_id);
$stmt_services->execute();
$services_result = $stmt_services->get_result();

// Truy vấn đánh giá của nghệ sĩ
$query_reviews = "SELECT r.*, u.fullname, b.service_id, s.name as service_name 
                 FROM reviews r
                 JOIN bookings b ON r.booking_id = b.booking_id
                 JOIN users u ON b.customer_id = u.user_id
                 JOIN services s ON b.service_id = s.service_id
                 WHERE b.artist_id = ?
                 ORDER BY r.created_at DESC
                 LIMIT 5";

$stmt_reviews = $conn->prepare($query_reviews);
$stmt_reviews->bind_param("i", $artist_id);
$stmt_reviews->execute();
$reviews_result = $stmt_reviews->get_result();

// Truy vấn ảnh trong gallery của nghệ sĩ
$query_gallery = "SELECT * FROM gallery WHERE artist_id = ? LIMIT 8";
$stmt_gallery = $conn->prepare($query_gallery);
$stmt_gallery->bind_param("i", $artist_id);
$stmt_gallery->execute();
$gallery_result = $stmt_gallery->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($artist['fullname']); ?> - Beauty Makeup Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="artist-detail section">
        <div class="container">
            <div class="artist-profile">
                <div class="artist-image">
                    <img src="images/artists/<?php echo $artist_id; ?>.jpg" alt="<?php echo htmlspecialchars($artist['fullname']); ?>" onerror="this.src='images/placeholder.jpg'">
                </div>
                <div class="artist-info">
                    <h1><?php echo htmlspecialchars($artist['fullname']); ?></h1>
                    <div class="artist-meta">
                        <p><i class="fas fa-star"></i> Kinh nghiệm: <?php echo $artist['experience']; ?> năm</p>
                        <p><i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($artist['email']); ?></p>
                        <p><i class="fas fa-phone"></i> Điện thoại: <?php echo htmlspecialchars($artist['phone']); ?></p>
                        
                        <?php if (!empty($artist['home_address'])): ?>
                        <p><i class="fas fa-home"></i> Địa chỉ nhà: <?php echo htmlspecialchars($artist['home_address']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($artist['work_address'])): ?>
                        <p><i class="fas fa-building"></i> Địa chỉ làm việc: <?php echo htmlspecialchars($artist['work_address']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="artist-description">
                        <h3>Giới thiệu</h3>
                        <p><?php echo nl2br(htmlspecialchars($artist['description'])); ?></p>
                    </div>
                    
                    <div class="artist-actions">
                        <a href="booking.php?artist=<?php echo $artist_id; ?>" class="btn btn-primary">Đặt lịch với nghệ sĩ này</a>
                        <?php if (!empty($artist['portfolio_url'])): ?>
                        <a href="<?php echo htmlspecialchars($artist['portfolio_url']); ?>" class="btn" target="_blank">Xem portfolio</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Hiển thị dịch vụ của nghệ sĩ -->
            <?php if ($services_result->num_rows > 0): ?>
            <div class="artist-services">
                <h2>Dịch vụ cung cấp</h2>
                <div class="services-grid">
                    <?php while ($service = $services_result->fetch_assoc()): ?>
                    <div class="service-card">
                        <img src="images/services/<?php echo $service['service_id']; ?>.jpg" alt="<?php echo htmlspecialchars($service['name']); ?>" class="service-img" onerror="this.src='images/placeholder.jpg'">
                        <div class="service-content">
                            <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                            <p class="service-price"><?php echo number_format($service['price'], 0, ',', '.'); ?> VNĐ</p>
                            <p class="service-desc"><?php echo htmlspecialchars($service['description']); ?></p>
                            <a href="booking.php?service=<?php echo $service['service_id']; ?>&artist=<?php echo $artist_id; ?>" class="btn">Đặt lịch</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Hiển thị đánh giá của nghệ sĩ -->
            <?php if ($reviews_result->num_rows > 0): ?>
            <div class="artist-reviews">
                <h2>Đánh giá từ khách hàng</h2>
                <div class="reviews-list">
                    <?php while ($review = $reviews_result->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-author"><?php echo htmlspecialchars($review['fullname']); ?></div>
                            <div class="review-service">Dịch vụ: <?php echo htmlspecialchars($review['service_name']); ?></div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                        </div>
                        <div class="review-content"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Hiển thị gallery -->
            <?php if ($gallery_result->num_rows > 0): ?>
            <div class="artist-gallery">
                <h2>Gallery</h2>
                <div class="gallery-grid">
                    <?php while ($image = $gallery_result->fetch_assoc()): ?>
                    <div class="gallery-item">
                        <a href="images/gallery/<?php echo $image['image_url']; ?>" data-lightbox="artist-gallery" data-title="<?php echo htmlspecialchars($image['description']); ?>">
                            <img src="images/gallery/thumbs/<?php echo $image['image_url']; ?>" alt="<?php echo htmlspecialchars($image['description']); ?>" onerror="this.src='images/placeholder.jpg'">
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</body>
</html>