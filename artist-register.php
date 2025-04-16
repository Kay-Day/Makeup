<?php
// Kết nối database
require_once 'config/db.php';
session_start();

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=artist-register.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Kiểm tra xem người dùng đã là nghệ sĩ chưa
$check_query = "SELECT * FROM artists WHERE user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Người dùng đã là nghệ sĩ, chuyển hướng đến trang hồ sơ
    header("Location: artist-profile.php");
    exit;
}

// Lấy thông tin người dùng
$user_query = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Xử lý form đăng ký nghệ sĩ
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate và lấy dữ liệu từ form
    $description = trim($_POST['description'] ?? '');
    $experience = (int)($_POST['experience'] ?? 0);
    $home_address = trim($_POST['home_address'] ?? '');
    $work_address = trim($_POST['work_address'] ?? ''); // Không bắt buộc
    
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
    
    // Xử lý upload portfolio (nếu có)
    $portfolio_url = '';
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
    
    // Nếu không có lỗi, thêm nghệ sĩ vào database
    if (empty($errors)) {
        $insert_query = "INSERT INTO artists (user_id, description, experience, portfolio_url, home_address, work_address, is_available) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("isisss", $user_id, $description, $experience, $portfolio_url, $home_address, $work_address);
        
        if ($insert_stmt->execute()) {
            // Cập nhật role của user thành artist
            $update_role = "UPDATE users SET role = 'artist' WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_role);
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            
            $success = true;
            
            // Chuyển hướng đến trang hồ sơ nghệ sĩ sau 3 giây
            header("refresh:3;url=artist-profile.php");
        } else {
            $errors[] = "Đã xảy ra lỗi. Vui lòng thử lại sau: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Làm Nghệ Sĩ - Beauty Makeup Studio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .register-artist-form {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            position: relative;
        }
        
        .form-title::after {
            content: "";
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
        
        .form-section-title {
            font-size: 18px;
            color: #333;
            margin: 30px 0 20px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }