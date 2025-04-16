<?php
// Kết nối database
require_once 'config/db.php';
session_start();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=booking-confirmation.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin đặt lịch từ ID
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    header("Location: my-bookings.php");
    exit;
}

// Lấy thông tin chi tiết đặt lịch
$query = "SELECT b.*, 
          s.name AS service_name, s.price, s.duration,
          u_artist.fullname AS artist_name, a.experience, a.home_address, a.work_address,
          u_customer.fullname AS customer_name, u_customer.email, u_customer.phone
          FROM bookings b
          JOIN services s ON b.service_id = s.service_id
          JOIN artists a ON b.artist_id = a.artist_id
          JOIN users u_artist ON a.user_id = u_artist.user_id
          JOIN users u_customer ON b.customer_id = u_customer.user_id
          WHERE b.booking_id = ? AND (b.customer_id = ? OR a.user_id = ?)";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $booking_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Nếu không tìm thấy hoặc không có quyền xem
    header("Location: my-bookings.php");
    exit;
}

$booking = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Đặt Lịch - Beauty Makeup Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 15px;
        }
        
        .booking-id {
            display: inline-block;
            padding: 8px 15px;
            background: #f5a8c5;
            color: white;
            font-weight: 600;
            border-radius: 30px;
            margin-bottom: 15px;
        }
        
        .confirmation-status {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
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
        
        .confirmation-message {
            text-align: center;
            margin: 20px 0;
            font-size: 1.1rem;
            color: #555;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .booking-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
        }
        
        .detail-section {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .detail-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f5a8c5;
        }
        
        .detail-item {
            margin-bottom: 10px;
            display: flex;
        }
        
        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
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
        
        .contact-info {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
            color: #666;
        }
        
        .contact-info p {
            margin-bottom: 5px;
        }
        
        .contact-info a {
            color: #f5a8c5;
            text-decoration: none;
        }
        
        .contact-info a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .booking-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <h2 class="confirmation-title">Xác Nhận Đặt Lịch</h2>
                <div class="booking-id">Mã đặt lịch: #<?php echo $booking_id; ?></div>
                <div class="confirmation-status">
                    Trạng thái: 
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
                </div>
            </div>
            
            <div class="confirmation-message">
                <?php if ($booking['status'] == 'pending'): ?>
                <p>Cảm ơn bạn đã đặt lịch với Beauty Makeup Studio. Chúng tôi sẽ xác nhận lịch hẹn trong thời gian sớm nhất.</p>
                <?php elseif ($booking['status'] == 'confirmed'): ?>
                <p>Lịch hẹn của bạn đã được xác nhận. Chúng tôi mong được gặp bạn vào ngày <?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?> lúc <?php echo date('H:i', strtotime($booking['booking_time'])); ?>.</p>
                <?php elseif ($booking['status'] == 'completed'): ?>
                <p>Lịch hẹn này đã hoàn thành. Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</p>
                <?php elseif ($booking['status'] == 'cancelled'): ?>
                <p>Lịch hẹn này đã bị hủy. Nếu bạn muốn đặt lại, vui lòng đặt lịch mới.</p>
                <?php endif; ?>
            </div>
            
            <div class="booking-details">
                <div class="detail-section">
                    <h3 class="detail-title">Thông tin dịch vụ</h3>
                    <div class="detail-item">
                        <div class="detail-label">Dịch vụ:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['service_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Giá:</div>
                        <div class="detail-value"><?php echo number_format($booking['price'], 0, ',', '.'); ?> VNĐ</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Thời gian thực hiện:</div>
                        <div class="detail-value"><?php echo floor($booking['duration']/60); ?> giờ <?php echo $booking['duration'] % 60; ?> phút</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Ngày đặt lịch:</div>
                        <div class="detail-value"><?php echo date('d/m/Y', strtotime($booking['booking_date'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Giờ đặt lịch:</div>
                        <div class="detail-value"><?php echo date('H:i', strtotime($booking['booking_time'])); ?></div>
                    </div>
                    <?php if (!empty($booking['notes'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Ghi chú:</div>
                        <div class="detail-value"><?php echo nl2br(htmlspecialchars($booking['notes'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="detail-section">
                    <h3 class="detail-title">Thông tin nghệ sĩ</h3>
                    <div class="detail-item">
                        <div class="detail-label">Nghệ sĩ:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['artist_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Kinh nghiệm:</div>
                        <div class="detail-value"><?php echo $booking['experience']; ?> năm</div>
                    </div>
                    
                    <?php if (!empty($booking['home_address'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Địa chỉ nhà:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['home_address']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($booking['work_address'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">Địa chỉ làm việc:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['work_address']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <div class="detail-label">Khách hàng:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Điện thoại:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['phone']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="actions">
                <?php if ($booking['status'] == 'pending' && $booking['customer_id'] == $user_id): ?>
                <a href="cancel-booking.php?id=<?php echo $booking_id; ?>" class="btn btn-outline" onclick="return confirm('Bạn có chắc chắn muốn hủy đặt lịch này?');">Hủy đặt lịch</a>
                <?php endif; ?>
                
                <?php if ($booking['status'] == 'pending' && $booking['customer_id'] != $user_id): ?>
                <a href="confirm-booking.php?id=<?php echo $booking_id; ?>" class="btn btn-primary">Xác nhận đặt lịch</a>
                <a href="cancel-booking.php?id=<?php echo $booking_id; ?>" class="btn btn-outline" onclick="return confirm('Bạn có chắc chắn muốn hủy đặt lịch này?');">Từ chối</a>
                <?php endif; ?>
                
                <a href="my-bookings.php" class="btn btn-outline">Xem tất cả đặt lịch</a>
            </div>
            
            <div class="contact-info">
                <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi qua:</p>
                <p>Email: <a href="mailto:contact@beautymakeup.com">contact@beautymakeup.com</a> | Hotline: <a href="tel:0987654321">0987.654.321</a></p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>