<?php
// Kết nối database
require_once 'config/db.php';
session_start();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=booking.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Lấy dịch vụ đã chọn (nếu có)
$selected_service = null;
if (isset($_GET['service'])) {
    $service_id = (int)$_GET['service'];
    $service_query = "SELECT * FROM services WHERE service_id = ? AND is_active = 1";
    $service_stmt = $conn->prepare($service_query);
    $service_stmt->bind_param("i", $service_id);
    $service_stmt->execute();
    $service_result = $service_stmt->get_result();
    
    if ($service_result->num_rows > 0) {
        $selected_service = $service_result->fetch_assoc();
    }
}

// Lấy nghệ sĩ đã chọn (nếu có)
$selected_artist = null;
if (isset($_GET['artist'])) {
    $artist_id = (int)$_GET['artist'];
    $artist_query = "SELECT a.*, u.fullname, u.email, u.phone 
                    FROM artists a
                    JOIN users u ON a.user_id = u.user_id
                    WHERE a.artist_id = ? AND a.is_available = 1";
    $artist_stmt = $conn->prepare($artist_query);
    $artist_stmt->bind_param("i", $artist_id);
    $artist_stmt->execute();
    $artist_result = $artist_stmt->get_result();
    
    if ($artist_result->num_rows > 0) {
        $selected_artist = $artist_result->fetch_assoc();
    }
}

// Lấy danh sách tất cả dịch vụ đang hoạt động
$services_query = "SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC";
$services_result = $conn->query($services_query);
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}

// Lấy danh sách nghệ sĩ đang hoạt động
$artists_query = "SELECT a.*, u.fullname, u.email, u.phone 
                FROM artists a
                JOIN users u ON a.user_id = u.user_id
                WHERE a.is_available = 1
                ORDER BY a.experience DESC";
$artists_result = $conn->query($artists_query);
$artists = [];
while ($row = $artists_result->fetch_assoc()) {
    $artists[] = $row;
}

// Xử lý form đặt lịch
$errors = [];
$success = false;
$booking_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate dữ liệu từ form
    $service_id = (int)($_POST['service_id'] ?? 0);
    $artist_id = (int)($_POST['artist_id'] ?? 0);
    $booking_date = $_POST['booking_date'] ?? '';
    $booking_time = $_POST['booking_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    // Kiểm tra dịch vụ
    if ($service_id <= 0) {
        $errors[] = "Vui lòng chọn dịch vụ";
    } else {
        // Kiểm tra dịch vụ có tồn tại và đang hoạt động
        $check_service = "SELECT * FROM services WHERE service_id = ? AND is_active = 1";
        $stmt = $conn->prepare($check_service);
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $errors[] = "Dịch vụ không hợp lệ";
        }
    }
    
    // Kiểm tra nghệ sĩ
    if ($artist_id <= 0) {
        $errors[] = "Vui lòng chọn nghệ sĩ";
    } else {
        // Kiểm tra nghệ sĩ có tồn tại và đang khả dụng
        $check_artist = "SELECT * FROM artists WHERE artist_id = ? AND is_available = 1";
        $stmt = $conn->prepare($check_artist);
        $stmt->bind_param("i", $artist_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $errors[] = "Nghệ sĩ không hợp lệ hoặc không khả dụng";
        }
    }
    
    // Kiểm tra ngày đặt lịch
    if (empty($booking_date)) {
        $errors[] = "Vui lòng chọn ngày";
    } else {
        $booking_date_obj = new DateTime($booking_date);
        $today = new DateTime();
        
        if ($booking_date_obj < $today) {
            $errors[] = "Ngày đặt lịch không được ở quá khứ";
        }
    }
    
    // Kiểm tra giờ đặt lịch
    if (empty($booking_time)) {
        $errors[] = "Vui lòng chọn giờ";
    } else {
        // Kiểm tra xem nghệ sĩ có lịch trùng vào thời điểm này không
        $check_time = "SELECT * FROM bookings 
                      WHERE artist_id = ? AND booking_date = ? AND booking_time = ? 
                      AND status IN ('pending', 'confirmed')";
        $stmt = $conn->prepare($check_time);
        $stmt->bind_param("iss", $artist_id, $booking_date, $booking_time);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Nghệ sĩ đã có lịch hẹn vào thời gian này. Vui lòng chọn thời gian khác.";
        }
    }
    
    // Nếu không có lỗi, thêm đặt lịch vào database
    if (empty($errors)) {
        $insert_query = "INSERT INTO bookings (customer_id, artist_id, service_id, booking_date, booking_time, notes, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiisss", $user_id, $artist_id, $service_id, $booking_date, $booking_time, $notes);
        
        if ($insert_stmt->execute()) {
            $booking_id = $conn->insert_id;
            $success = true;
            
            // Gửi thông báo cho nghệ sĩ (có thể thêm chức năng gửi email ở đây)
            
            // Chuyển hướng đến trang xác nhận sau 3 giây
            header("refresh:3;url=booking-confirmation.php?id=$booking_id");
        } else {
            $errors[] = "Đã xảy ra lỗi khi đặt lịch: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lịch Makeup - Beauty Makeup Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .booking-container {
            max-width: 1200px;
            margin: 40px auto;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .booking-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .booking-summary {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .section-title {
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: #333;
            text-align: center;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: #f5a8c5;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #f5a8c5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(245, 168, 197, 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-note {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }
        
        .required-label::after {
            content: "*";
            color: #e74c3c;
            margin-left: 4px;
        }
        
        .submit-btn {
            background: #f5a8c5;
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: block;
            margin: 30px auto 0;
            min-width: 200px;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #ff4e8a;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 78, 138, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .alert-danger {
            background-color: #fee8e7;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
        }
        
        .alert-success {
            background-color: #e7f4e4;
            border-left: 4px solid #2ecc71;
            color: #27ae60;
        }
        
        .alert ul {
            margin: 10px 0 0 20px;
        }
        
        /* Services Selection */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .service-card {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .service-card:hover {
            border-color: #f5a8c5;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .service-card.selected {
            border-color: #f5a8c5;
            background-color: rgba(245, 168, 197, 0.1);
        }
        
        .service-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .service-icon {
            font-size: 32px;
            color: #f5a8c5;
            margin-bottom: 10px;
        }
        
        .service-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .service-price {
            color: #f5a8c5;
            font-weight: 600;
        }
        
        /* Artists Selection */
        .artists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .artist-card {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .artist-card:hover {
            border-color: #f5a8c5;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .artist-card.selected {
            border-color: #f5a8c5;
            background-color: rgba(245, 168, 197, 0.1);
        }
        
        .artist-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .artist-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 10px;
            border: 3px solid #f5a8c5;
        }
        
        .artist-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .artist-experience {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .artist-address {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
            text-align: left;
        }
        
        /* Time picker */
        .time-picker {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        
        .time-option {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .time-option:hover {
            border-color: #f5a8c5;
            background-color: rgba(245, 168, 197, 0.1);
        }
        
        .time-option.selected {
            background-color: #f5a8c5;
            color: white;
            border-color: #f5a8c5;
        }
        
        .time-option input[type="radio"] {
            display: none;
        }
        
        /* Summary */
        .summary-section {
            margin-bottom: 20px;
        }
        
        .summary-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            font-weight: 600;
            color: #333;
        }
        
        .total-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #f5a8c5;
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #eee;
        }
        
        @media (max-width: 768px) {
            .booking-container {
                grid-template-columns: 1fr;
            }
            
            .booking-summary {
                position: static;
                order: -1;
            }
            
            .services-grid, .artists-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .time-picker {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="page-banner">
            <div class="banner-content">
                <h1>Đặt Lịch Makeup</h1>
                <p>Đặt lịch với các nghệ sĩ trang điểm chuyên nghiệp của chúng tôi</p>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="max-width: 800px; margin: 40px auto;">
                <strong>Đặt lịch thành công!</strong>
                <p>Cảm ơn bạn đã đặt lịch với Beauty Makeup Studio. Chúng tôi sẽ xác nhận lịch hẹn của bạn trong thời gian sớm nhất.</p>
                <p>Mã đặt lịch của bạn là: <strong>#<?php echo $booking_id; ?></strong></p>
                <p>Bạn sẽ được chuyển đến trang xác nhận đặt lịch sau vài giây...</p>
            </div>
        <?php else: ?>
            <div class="booking-container">
                <div class="booking-form">
                    <h2 class="section-title">Thông tin đặt lịch</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Có lỗi xảy ra:</strong>
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="booking.php" method="POST" id="booking-form">
                        <!-- Chọn dịch vụ -->
                        <div class="form-group">
                            <label class="required-label">Chọn dịch vụ</label>
                            <div class="services-grid">
                                <?php foreach ($services as $service): ?>
                                    <label class="service-card <?php echo ($selected_service && $selected_service['service_id'] == $service['service_id']) ? 'selected' : ''; ?>">
                                        <input type="radio" name="service_id" value="<?php echo $service['service_id']; ?>" <?php echo ($selected_service && $selected_service['service_id'] == $service['service_id']) ? 'checked' : ''; ?> required>
                                        <div class="service-icon">
                                            <i class="fas fa-magic"></i>
                                        </div>
                                        <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                                        <div class="service-price"><?php echo number_format($service['price'], 0, ',', '.'); ?> VNĐ</div>
                                        <div class="form-note"><?php echo floor($service['duration']/60); ?> giờ <?php echo $service['duration'] % 60; ?> phút</div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Chọn nghệ sĩ -->
                        <div class="form-group">
                            <label class="required-label">Chọn nghệ sĩ</label>
                            <div class="artists-grid">
                                <?php foreach ($artists as $artist): ?>
                                    <label class="artist-card <?php echo ($selected_artist && $selected_artist['artist_id'] == $artist['artist_id']) ? 'selected' : ''; ?>">
                                        <input type="radio" name="artist_id" value="<?php echo $artist['artist_id']; ?>" <?php echo ($selected_artist && $selected_artist['artist_id'] == $artist['artist_id']) ? 'checked' : ''; ?> required>
                                        <img src="images/artists/<?php echo $artist['artist_id']; ?>.jpg" alt="<?php echo htmlspecialchars($artist['fullname']); ?>" class="artist-img" onerror="this.src='images/placeholder.jpg'">
                                        <div class="artist-name"><?php echo htmlspecialchars($artist['fullname']); ?></div>
                                        <div class="artist-experience"><?php echo $artist['experience']; ?> năm kinh nghiệm</div>
                                        
                                        <?php if (!empty($artist['home_address']) || !empty($artist['work_address'])): ?>
                                        <div class="artist-address">
                                            <?php if (!empty($artist['home_address'])): ?>
                                            <small><i class="fas fa-home"></i> <?php echo htmlspecialchars($artist['home_address']); ?></small><br>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($artist['work_address'])): ?>
                                            <small><i class="fas fa-building"></i> <?php echo htmlspecialchars($artist['work_address']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Chọn ngày -->
                        <div class="form-group">
                            <label for="booking_date" class="required-label">Chọn ngày</label>
                            <input type="date" id="booking_date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <!-- Chọn giờ -->
                        <div class="form-group">
                            <label class="required-label">Chọn giờ</label>
                            <div class="time-picker">
                                <?php
                                // Tạo các khoảng thời gian từ 8h sáng đến 8h tối, mỗi khoảng 1 giờ
                                $start_time = strtotime('08:00');
                                $end_time = strtotime('20:00');
                                $interval = 60 * 60; // 1 giờ
                                
                                for ($time = $start_time; $time <= $end_time; $time += $interval) {
                                    $formatted_time = date('H:i', $time);
                                ?>
                                    <label class="time-option">
                                        <input type="radio" name="booking_time" value="<?php echo $formatted_time; ?>" required>
                                        <?php echo $formatted_time; ?>
                                    </label>
                                <?php } ?>
                            </div>
                        </div>
                        
                        <!-- Ghi chú -->
                        <div class="form-group">
                            <label for="notes">Ghi chú (không bắt buộc)</label>
                            <textarea id="notes" name="notes" class="form-control" placeholder="Nhập yêu cầu đặc biệt hoặc ghi chú khác"></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">Đặt lịch ngay</button>
                    </form>
                </div>
                
                <div class="booking-summary">
                    <h3 class="section-title">Thông tin đặt lịch</h3>
                    
                    <div class="summary-section">
                        <h4 class="summary-title">Khách hàng</h4>
                        <div class="summary-item">
                            <span class="summary-label">Họ tên:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($user['fullname']); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Email:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Điện thoại:</span>
                            <span class="summary-value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                    </div>
                    
                    <div class="summary-section" id="selected-service-summary">
                        <h4 class="summary-title">Dịch vụ</h4>
                        <div class="summary-placeholder">Vui lòng chọn dịch vụ</div>
                    </div>
                    
                    <div class="summary-section" id="selected-artist-summary">
                        <h4 class="summary-title">Nghệ sĩ</h4>
                        <div class="summary-placeholder">Vui lòng chọn nghệ sĩ</div>
                    </div>
                    
                    <div class="summary-section" id="selected-time-summary">
                        <h4 class="summary-title">Thời gian</h4>
                        <div class="summary-placeholder">Vui lòng chọn ngày và giờ</div>
                    </div>
                    
                    <div class="total-price" id="total-price">
                        Tổng tiền: <span>0 VNĐ</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý chọn dịch vụ
            const serviceCards = document.querySelectorAll('.service-card');
            const serviceInputs = document.querySelectorAll('input[name="service_id"]');
            const selectedServiceSummary = document.getElementById('selected-service-summary');
            
            serviceCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Xóa class selected từ tất cả thẻ
                    serviceCards.forEach(c => c.classList.remove('selected'));
                    // Thêm class selected cho thẻ được chọn
                    this.classList.add('selected');
                    // Chọn radio input
                    const input = this.querySelector('input[type="radio"]');
                    input.checked = true;
                    
                    // Cập nhật thông tin dịch vụ trong tóm tắt
                    updateServiceSummary(input.value);
                    // Cập nhật tổng tiền
                    updateTotalPrice();
                });
            });
            
            // Xử lý chọn nghệ sĩ
            const artistCards = document.querySelectorAll('.artist-card');
            const artistInputs = document.querySelectorAll('input[name="artist_id"]');
            const selectedArtistSummary = document.getElementById('selected-artist-summary');
            
            artistCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Xóa class selected từ tất cả thẻ
                    artistCards.forEach(c => c.classList.remove('selected'));
                    // Thêm class selected cho thẻ được chọn
                    this.classList.add('selected');
                    // Chọn radio input
                    const input = this.querySelector('input[type="radio"]');
                    input.checked = true;
                    
                    // Cập nhật thông tin nghệ sĩ trong tóm tắt
                    updateArtistSummary(input.value);
                });
            });
            
            // Xử lý chọn thời gian
            const timeOptions = document.querySelectorAll('.time-option');
            const timeInputs = document.querySelectorAll('input[name="booking_time"]');
            const selectedTimeSummary = document.getElementById('selected-time-summary');
            const bookingDateInput = document.getElementById('booking_date');
            
            timeOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Xóa class selected từ tất cả thẻ
                    timeOptions.forEach(o => o.classList.remove('selected'));
                    // Thêm class selected cho thẻ được chọn
                    this.classList.add('selected');
                    // Chọn radio input
                    const input = this.querySelector('input[type="radio"]');
                    input.checked = true;
                    
                    // Cập nhật thông tin thời gian trong tóm tắt
                    updateTimeSummary();
                });
            });
            
            bookingDateInput.addEventListener('change', function() {
                updateTimeSummary();
            });
            
            // Hàm cập nhật thông tin dịch vụ trong tóm tắt
            function updateServiceSummary(serviceId) {
                const service = getServiceById(serviceId);
                if (service) {
                    selectedServiceSummary.innerHTML = `
                        <h4 class="summary-title">Dịch vụ</h4>
                        <div class="summary-item">
                            <span class="summary-label">Tên:</span>
                            <span class="summary-value">${service.name}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Giá:</span>
                            <span class="summary-value">${formatCurrency(service.price)}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Thời gian:</span>
                            <span class="summary-value">${Math.floor(service.duration/60)} giờ ${service.duration % 60} phút</span>
                        </div>
                    `;
                } else {
                    selectedServiceSummary.innerHTML = `
                        <h4 class="summary-title">Dịch vụ</h4>
                        <div class="summary-placeholder">Vui lòng chọn dịch vụ</div>
                    `;
                }
            }
            
            // Hàm cập nhật thông tin nghệ sĩ trong tóm tắt
            function updateArtistSummary(artistId) {
                const artist = getArtistById(artistId);
                if (artist) {
                    selectedArtistSummary.innerHTML = `
                        <h4 class="summary-title">Nghệ sĩ</h4>
                        <div class="summary-item">
                            <span class="summary-label">Tên:</span>
                            <span class="summary-value">${artist.fullname}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Kinh nghiệm:</span>
                            <span class="summary-value">${artist.experience} năm</span>
                        </div>
                    `;
                } else {
                    selectedArtistSummary.innerHTML = `
                        <h4 class="summary-title">Nghệ sĩ</h4>
                        <div class="summary-placeholder">Vui lòng chọn nghệ sĩ</div>
                    `;
                }
            }
            
            // Hàm cập nhật thông tin thời gian trong tóm tắt
            function updateTimeSummary() {
                const bookingDate = bookingDateInput.value;
                const selectedTime = document.querySelector('input[name="booking_time"]:checked');
                
                if (bookingDate && selectedTime) {
                    const formattedDate = new Date(bookingDate).toLocaleDateString('vi-VN', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    
                    selectedTimeSummary.innerHTML = `
                        <h4 class="summary-title">Thời gian</h4>
                        <div class="summary-item">
                            <span class="summary-label">Ngày:</span>
                            <span class="summary-value">${formattedDate}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Giờ:</span>
                            <span class="summary-value">${selectedTime.value}</span>
                        </div>
                    `;
                } else {
                    selectedTimeSummary.innerHTML = `
                        <h4 class="summary-title">Thời gian</h4>
                        <div class="summary-placeholder">Vui lòng chọn ngày và giờ</div>
                    `;
                }
            }
            
            // Hàm cập nhật tổng tiền
            function updateTotalPrice() {
                const selectedService = document.querySelector('input[name="service_id"]:checked');
                const totalPriceElement = document.getElementById('total-price');
                
                if (selectedService) {
                    const service = getServiceById(selectedService.value);
                    if (service) {
                        totalPriceElement.innerHTML = `Tổng tiền: <span>${formatCurrency(service.price)}</span>`;
                    }
                } else {
                    totalPriceElement.innerHTML = `Tổng tiền: <span>0 VNĐ</span>`;
                }
            }
            
            // Hàm lấy thông tin dịch vụ từ ID
            function getServiceById(id) {
                const services = <?php echo json_encode($services); ?>;
                return services.find(service => service.service_id == id);
            }
            
            // Hàm lấy thông tin nghệ sĩ từ ID
            function getArtistById(id) {
                const artists = <?php echo json_encode($artists); ?>;
                return artists.find(artist => artist.artist_id == id);
            }
            
            // Hàm định dạng tiền tệ
            function formatCurrency(amount) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
            }
            
            // Khởi tạo trạng thái ban đầu
            if (document.querySelector('input[name="service_id"]:checked')) {
                updateServiceSummary(document.querySelector('input[name="service_id"]:checked').value);
                updateTotalPrice();
            }
            
            if (document.querySelector('input[name="artist_id"]:checked')) {
                updateArtistSummary(document.querySelector('input[name="artist_id"]:checked').value);
            }
        });
    </script>
</body>
</html>