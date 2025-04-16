<?php
// Kết nối database
require_once 'config/db.php';
session_start();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=artist-profile.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kiểm tra xem người dùng có phải là nghệ sĩ không
$check_query = "SELECT a.*, u.fullname, u.email, u.phone 
                FROM artists a 
                JOIN users u ON a.user_id = u.user_id 
                WHERE a.user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    // Người dùng chưa là nghệ sĩ, chuyển hướng đến trang đăng ký
    header("Location: artist-register.php");
    exit;
}

$artist = $result->fetch_assoc();

// Xử lý form cập nhật thông tin
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate và lấy dữ liệu từ form
    $description = trim($_POST['description'] ?? '');
    $experience = (int)($_POST['experience'] ?? 0);
    $home_address = trim($_POST['home_address'] ?? '');
    $work_address = trim($_POST['work_address'] ?? ''); // Không bắt buộc
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Kiểm tra các trường bắt buộc
    if (empty($description)) {
        $errors[] = "Vui lòng nhập mô tả về bạn";
    }
    
    if ($experience < 0) {
        $errors[] = "Số năm kinh nghiệm không hợp lệ";
    }
    
    if (empty($home_address)) {
        $errors[] = "Vui lòng nhập địa chỉ nhà";
    }
    
    // Xử lý upload portfolio mới (nếu có)
    $portfolio_url = $artist['portfolio_url']; // Giữ nguyên nếu không upload mới
    
    if (isset($_FILES['portfolio']) && $_FILES['portfolio']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $filename = $_FILES['portfolio']['name'];
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (!in_array(strtolower($file_ext), $allowed)) {
            $errors[] = "File portfolio phải có định dạng PDF, DOC, DOCX, JPG, JPEG hoặc PNG";
        } else {
            // Tạo thư mục nếu chưa tồn tại
            $upload_dir = 'uploads/portfolios/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'portfolio_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['portfolio']['tmp_name'], $upload_path)) {
                $portfolio_url = $upload_path;
            } else {
                $errors[] = "Có lỗi xảy ra khi upload file. Vui lòng thử lại.";
            }
        }
    }
    
    // Nếu không có lỗi, cập nhật thông tin nghệ sĩ
    if (empty($errors)) {
        $update_query = "UPDATE artists SET 
                        description = ?, 
                        experience = ?, 
                        portfolio_url = ?, 
                        home_address = ?, 
                        work_address = ?, 
                        is_available = ? 
                        WHERE user_id = ?";
                        
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sisssii", $description, $experience, $portfolio_url, $home_address, $work_address, $is_available, $user_id);
        
        if ($update_stmt->execute()) {
            $success = true;
            
            // Cập nhật dữ liệu hiển thị
            $artist['description'] = $description;
            $artist['experience'] = $experience;
            $artist['portfolio_url'] = $portfolio_url;
            $artist['home_address'] = $home_address;
            $artist['work_address'] = $work_address;
            $artist['is_available'] = $is_available;
        } else {
            $errors[] = "Đã xảy ra lỗi khi cập nhật thông tin: " . $conn->error;
        }
    }
}

// Lấy danh sách các lịch đặt của nghệ sĩ
$bookings_query = "SELECT b.*, u.fullname AS customer_name, s.name AS service_name, s.price 
                  FROM bookings b 
                  JOIN users u ON b.customer_id = u.user_id 
                  JOIN services s ON b.service_id = s.service_id 
                  WHERE b.artist_id = ? 
                  ORDER BY b.booking_date DESC, b.booking_time DESC
                  LIMIT 10";
                  
$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $artist['artist_id']);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Nghệ Sĩ - Beauty Makeup Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 5px solid #f5a8c5;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }
        
        .profile-meta {
            margin: 20px 0;
        }
        
        .profile-meta p {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            color: #555;
        }
        
        .profile-meta i {
            width: 25px;
            color: #f5a8c5;
            margin-right: 10px;
        }
        
        .profile-actions {
            margin-top: 25px;
        }
        
        .profile-actions a {
            display: block;
            text-align: center;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
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
        
        .btn-primary:hover, .btn-outline:hover {
            background: #ff4e8a;
            color: white;
        }
        
        .profile-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .profile-tabs {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
        }
        
        .profile-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .profile-tab.active {
            color: #f5a8c5;
        }
        
        .profile-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: #f5a8c5;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
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
            min-height: 150px;
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
            padding: 12px 25px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            min-width: 150px;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background: #ff4e8a;
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
        
        /* Bookings Table */
        .bookings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .bookings-table th, .bookings-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .bookings-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        
        .bookings-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #f5a8c5;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #f5a8c5;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .availability-status {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }
        
        .availability-status label {
            margin-right: 15px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="profile-container">
            <div class="profile-sidebar">
                <img src="images/artists/<?php echo $artist['artist_id']; ?>.jpg" alt="<?php echo htmlspecialchars($artist['fullname']); ?>" class="profile-avatar" onerror="this.src='images/placeholder.jpg'">
                <h2 class="profile-name"><?php echo htmlspecialchars($artist['fullname']); ?></h2>
                <div class="profile-meta">
                    <p><i class="fas fa-star"></i> <?php echo $artist['experience']; ?> năm kinh nghiệm</p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($artist['email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($artist['phone']); ?></p>
                    
                    <?php if (!empty($artist['home_address'])): ?>
                    <p><i class="fas fa-home"></i> <?php echo htmlspecialchars($artist['home_address']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($artist['work_address'])): ?>
                    <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($artist['work_address']); ?></p>
                    <?php endif; ?>
                    
                    <p><i class="fas fa-check-circle"></i> Trạng thái: 
                        <span class="<?php echo $artist['is_available'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $artist['is_available'] ? 'Đang hoạt động' : 'Tạm ngưng'; ?>
                        </span>
                    </p>
                </div>
                
                <div class="profile-actions">
                    <a href="artist-detail.php?id=<?php echo $artist['artist_id']; ?>" class="btn-outline">Xem trang công khai</a>
                    <?php if (!empty($artist['portfolio_url'])): ?>
                    <a href="<?php echo htmlspecialchars($artist['portfolio_url']); ?>" class="btn-outline" target="_blank">Xem portfolio</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="profile-content">
                <div class="profile-tabs">
                    <div class="profile-tab active" data-tab="profile">Thông tin cá nhân</div>
                    <div class="profile-tab" data-tab="bookings">Lịch đặt</div>
                </div>
                
                <!-- Tab thông tin cá nhân -->
                <div class="tab-content active" id="profile-tab">
                    <h3 class="section-title">Thông tin nghệ sĩ</h3>
                    
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
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <strong>Cập nhật thành công!</strong>
                            <p>Thông tin nghệ sĩ của bạn đã được cập nhật.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="artist-profile.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="description" class="required-label">Mô tả về bạn</label>
                            <textarea class="form-control" id="description" name="description" placeholder="Mô tả về kinh nghiệm, phong cách trang điểm và các kỹ năng đặc biệt của bạn..."><?php echo htmlspecialchars($artist['description']); ?></textarea>
                            <div class="form-note">Mô tả chi tiết về kỹ năng, sở trường và phong cách trang điểm của bạn</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="experience" class="required-label">Số năm kinh nghiệm</label>
                            <input type="number" class="form-control" id="experience" name="experience" min="0" value="<?php echo htmlspecialchars($artist['experience']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="home_address" class="required-label">Địa chỉ nhà</label>
                            <input type="text" class="form-control" id="home_address" name="home_address" placeholder="Nhập địa chỉ nhà của bạn" value="<?php echo htmlspecialchars($artist['home_address']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="work_address">Địa chỉ làm việc (không bắt buộc)</label>
                            <input type="text" class="form-control" id="work_address" name="work_address" placeholder="Nhập địa chỉ làm việc nếu có" value="<?php echo htmlspecialchars($artist['work_address']); ?>">
                            <div class="form-note">Địa chỉ nơi bạn làm việc hiện tại (nếu có)</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="portfolio">Portfolio mới (không bắt buộc)</label>
                            <input type="file" class="form-control" id="portfolio" name="portfolio">
                            <div class="form-note">
                                Chấp nhận file PDF, DOC, DOCX, JPG, JPEG, PNG. Kích thước tối đa 5MB
                                <?php if (!empty($artist['portfolio_url'])): ?>
                                <br>Portfolio hiện tại: <a href="<?php echo htmlspecialchars($artist['portfolio_url']); ?>" target="_blank"><?php echo htmlspecialchars($artist['portfolio_url']); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="availability-status">
                            <label for="is_available">Trạng thái hoạt động:</label>
                            <label class="switch">
                                <input type="checkbox" id="is_available" name="is_available" <?php echo $artist['is_available'] ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="form-note" style="margin-left: 10px;">Bật để hiển thị bạn đang sẵn sàng nhận đặt lịch</span>
                        </div>
                        
                        <button type="submit" class="submit-btn">Cập nhật thông tin</button>
                    </form>
                </div>
                
                <!-- Tab lịch đặt -->
                <div class="tab-content" id="bookings-tab">
                    <h3 class="section-title">Lịch đặt gần đây</h3>
                    
                    <?php if ($bookings_result->num_rows > 0): ?>
                        <table class="bookings-table">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Giờ</th>
                                    <th>Khách hàng</th>
                                    <th>Dịch vụ</th>
                                    <th>Giá</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($booking['booking_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td><?php echo number_format($booking['price'], 0, ',', '.'); ?> VNĐ</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php 
                                                switch ($booking['status']) {
                                                    case 'pending':
                                                        echo 'Chờ xác nhận';
                                                        break;
                                                    case 'confirmed':
                                                        echo 'Đã xác nhận';
                                                        break;
                                                    case 'completed':
                                                        echo 'Hoàn thành';
                                                        break;
                                                    case 'cancelled':
                                                        echo 'Đã hủy';
                                                        break;
                                                    default:
                                                        echo $booking['status'];
                                                }
                                                ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 20px; text-align: center;">
                            <a href="artist-bookings.php" class="btn-outline">Xem tất cả lịch đặt</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>Bạn chưa có lịch đặt nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Chức năng chuyển tab
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.profile-tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Xóa active class từ tất cả tabs và contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Thêm active class cho tab được chọn
                    this.classList.add('active');
                    document.getElementById(tabId + '-tab').classList.add('active');
                });
            });
        });
    </script>
</body>
</html>