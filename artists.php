<?php
// Kết nối database
require_once 'config/db.php';

// Truy vấn danh sách nghệ sĩ
$query = "SELECT a.*, u.fullname, u.email, u.phone 
          FROM artists a
          JOIN users u ON a.user_id = u.user_id
          WHERE a.is_available = 1
          ORDER BY a.experience DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nghệ Sĩ Trang Điểm - Beauty Makeup Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .artists-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .artists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        
        .artist-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .artist-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .artist-image {
            height: 250px;
            position: relative;
            overflow: hidden;
        }
        
        .artist-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .artist-card:hover .artist-image img {
            transform: scale(1.1);
        }
        
        .artist-info {
            padding: 20px;
        }
        
        .artist-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .artist-experience {
            color: #f5a8c5;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .artist-experience i {
            margin-right: 5px;
        }
        
        .artist-meta {
            margin: 15px 0;
            color: #666;
        }
        
        .artist-meta p {
            margin: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .artist-meta i {
            width: 20px;
            margin-right: 8px;
            color: #f5a8c5;
        }
        
        .artist-description {
            margin: 15px 0;
            color: #555;
            line-height: 1.6;
            height: 80px;
            overflow: hidden;
            position: relative;
        }
        
        .artist-description::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40px;
            background: linear-gradient(transparent, white);
        }
        
        .artist-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .artist-actions .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #f5a8c5;
            color: white;
        }
        
        .btn-outline {
            border: 2px solid #f5a8c5;
            color: #f5a8c5;
        }
        
        .btn-primary:hover {
            background: #ff4e8a;
        }
        
        .btn-outline:hover {
            background: #f5a8c5;
            color: white;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 15px;
        }
        
        .page-header p {
            max-width: 700px;
            margin: 0 auto;
            color: #666;
            line-height: 1.6;
        }
        
        .page-header::after {
            content: "";
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: #f5a8c5;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="page-banner">
        <div class="banner-content">
            <h1>Nghệ Sĩ Trang Điểm</h1>
            <p>Khám phá đội ngũ nghệ sĩ trang điểm tài năng và chuyên nghiệp của chúng tôi</p>
        </div>
    </div>

    <section class="artists-section">
        <div class="artists-container">
            <div class="page-header">
                <h1>Đội Ngũ Nghệ Sĩ</h1>
                <p>Đội ngũ nghệ sĩ trang điểm của chúng tôi luôn cập nhật xu hướng mới nhất và cam kết mang đến dịch vụ chất lượng cao nhất cho khách hàng.</p>
            </div>

            <div class="artists-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($artist = $result->fetch_assoc()): ?>
                        <div class="artist-card">
                            <div class="artist-image">
                                <img src="images/artists/<?php echo $artist['artist_id']; ?>.jpg" alt="<?php echo htmlspecialchars($artist['fullname']); ?>" onerror="this.src='images/placeholder.jpg'">
                            </div>
                            <div class="artist-info">
                                <h3 class="artist-name"><?php echo htmlspecialchars($artist['fullname']); ?></h3>
                                <div class="artist-experience">
                                    <i class="fas fa-star"></i> <?php echo $artist['experience']; ?> năm kinh nghiệm
                                </div>
                                
                                <div class="artist-meta">
                                    <?php if (!empty($artist['home_address'])): ?>
                                    <p><i class="fas fa-home"></i> <?php echo htmlspecialchars($artist['home_address']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($artist['work_address'])): ?>
                                    <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($artist['work_address']); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="artist-description">
                                    <?php echo htmlspecialchars($artist['description']); ?>
                                </div>
                                
                                <div class="artist-actions">
                                    <a href="artist-detail.php?id=<?php echo $artist['artist_id']; ?>" class="btn btn-outline">Xem chi tiết</a>
                                    <a href="booking.php?artist=<?php echo $artist['artist_id']; ?>" class="btn btn-primary">Đặt lịch</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <p>Hiện tại chưa có nghệ sĩ nào khả dụng. Vui lòng quay lại sau.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>