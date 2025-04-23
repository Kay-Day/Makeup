<?php
// Kết nối đến cơ sở dữ liệu
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'beautyclick');

$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn === false) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Mật khẩu mới cho từng loại tài khoản
$admin_password = 'Admin123';
$artist_password = 'Artist123';
$client_password = 'Client123';

// Mã hóa các mật khẩu
$hashed_admin_password = password_hash($admin_password, PASSWORD_DEFAULT);
$hashed_artist_password = password_hash($artist_password, PASSWORD_DEFAULT);
$hashed_client_password = password_hash($client_password, PASSWORD_DEFAULT);

// Cập nhật mật khẩu cho admin
$sql_admin = "UPDATE users SET password = '$hashed_admin_password' WHERE role_id = 1";
if (mysqli_query($conn, $sql_admin)) {
    echo "Mật khẩu Admin đã được cập nhật thành: $admin_password <br>";
} else {
    echo "Lỗi: " . mysqli_error($conn) . "<br>";
}

// Cập nhật mật khẩu cho thợ makeup
$sql_artist = "UPDATE users SET password = '$hashed_artist_password' WHERE role_id = 2";
if (mysqli_query($conn, $sql_artist)) {
    echo "Mật khẩu cho tất cả Thợ makeup đã được cập nhật thành: $artist_password <br>";
} else {
    echo "Lỗi: " . mysqli_error($conn) . "<br>";
}

// Cập nhật mật khẩu cho khách hàng
$sql_client = "UPDATE users SET password = '$hashed_client_password' WHERE role_id = 3";
if (mysqli_query($conn, $sql_client)) {
    echo "Mật khẩu cho tất cả Khách hàng đã được cập nhật thành: $client_password <br>";
} else {
    echo "Lỗi: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
echo "<br>Đã hoàn tất cập nhật mật khẩu. Bạn nên xóa file này sau khi sử dụng.";
?>