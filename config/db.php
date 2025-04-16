<?php
// Thông tin kết nối đến cơ sở dữ liệu
$db_host = 'localhost'; // Máy chủ MySQL
$db_user = 'root';      // Tên người dùng MySQL
$db_pass = '';          // Mật khẩu MySQL
$db_name = 'makeup_booking'; // Tên cơ sở dữ liệu

// Tạo kết nối
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Đặt charset cho kết nối
$conn->set_charset("utf8mb4");

// Hàm để thoát và vệ sinh dữ liệu đầu vào
function sanitize($conn, $input) {
    if (is_array($input)) {
        $output = [];
        foreach ($input as $key => $value) {
            $output[$key] = sanitize($conn, $value);
        }
        return $output;
    } else {
        return $conn->real_escape_string(trim($input));
    }
}

// Hàm để hiển thị thông báo
function showAlert($message, $type = 'success') {
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

// Hàm để chuyển hướng với thông báo Flash
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

// Hàm để hiển thị thông báo Flash
function showFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return showAlert($message, $type);
    }
    return '';
}

// Hàm định dạng tiền tệ
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

// Hàm định dạng thời gian
function formatTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return ($hours > 0 ? $hours . ' giờ ' : '') . ($mins > 0 ? $mins . ' phút' : '');
}

// Hàm kiểm tra đăng nhập
function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        // Lưu URL hiện tại để sau khi đăng nhập chuyển về
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        header("Location: login.php?redirect=$redirect");
        exit;
    }
}

// Hàm kiểm tra quyền truy cập
function checkRole($allowedRoles = ['admin']) {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: index.php");
        exit;
    }
}