<?php
// Kết nối database
require_once 'config/db.php';
session_start();

// Nếu đã đăng nhập, chuyển hướng về trang chủ hoặc trang được yêu cầu
if (isset($_SESSION['user_id'])) {
    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
    header("Location: $redirect");
    exit;
}

// Xử lý đăng ký
$errors = [];
$success = false;
$formData = [
    'username' => '',
    'email' => '',
    'fullname' => '',
    'phone' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $formData = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'fullname' => trim($_POST['fullname']),
        'phone' => trim($_POST['phone']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password']
    ];
    
    // Kiểm tra tên đăng nhập
    if (empty($formData['username'])) {
        $errors[] = 'Vui lòng nhập tên đăng nhập';
    } elseif (strlen($formData['username']) < 4) {
        $errors[] = 'Tên đăng nhập phải có ít nhất 4 ký tự';
    } else {
        // Kiểm tra tên đăng nhập đã tồn tại chưa
        $checkUsername = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($checkUsername);
        $stmt->bind_param("s", $formData['username']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Tên đăng nhập đã tồn tại, vui lòng chọn tên khác';
        }
    }
    
    // Kiểm tra email
    if (empty($formData['email'])) {
        $errors[] = 'Vui lòng nhập email';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ';
    } else {
        // Kiểm tra email đã tồn tại chưa
        $checkEmail = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkEmail);
        $stmt->bind_param("s", $formData['email']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Email đã được sử dụng, vui lòng dùng email khác';
        }
    }
    
    // Kiểm tra họ tên
    if (empty($formData['fullname'])) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    
    // Kiểm tra số điện thoại
    if (empty($formData['phone'])) {
        $errors[] = 'Vui lòng nhập số điện thoại';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $formData['phone'])) {
        $errors[] = 'Số điện thoại không hợp lệ';
    }
    
    // Kiểm tra mật khẩu
    if (empty($formData['password'])) {
        $errors[] = 'Vui lòng nhập mật khẩu';
    } elseif (strlen($formData['password']) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
    }
    
    // Kiểm tra xác nhận mật khẩu
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Xác nhận mật khẩu không khớp';
    }
    
    // Nếu không có lỗi, thêm người dùng vào database
    if (empty($errors)) {
        // Mã hóa mật khẩu
        $hashed_password = password_hash($formData['password'], PASSWORD_DEFAULT);
        
        // Thêm người dùng mới
        $query = "INSERT INTO users (username, password, email, fullname, phone, role) VALUES (?, ?, ?, ?, ?, 'customer')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $formData['username'], $hashed_password, $formData['email'], $formData['fullname'], $formData['phone']);
        
        if ($stmt->execute()) {
            $success = true;
            
            // Tự động đăng nhập
            $user_id = $stmt->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $formData['username'];
            $_SESSION['fullname'] = $formData['fullname'];
            $_SESSION['role'] = 'customer';
            
            // Chuyển hướng sau đăng ký thành công
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            header("refresh:3;url=$redirect");
        } else {
            $errors[] = 'Có lỗi xảy ra, vui lòng thử lại: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Beauty Makeup Studio</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #f5a8c5;
            --primary-light: #ffd1e3;
            --primary-dark: #ff4e8a;
            --secondary-color: #8a4fff;
            --dark-color: #333;
            --light-color: #f9f9f9;
            --text-color: #555;
            --heading-color: #222;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            color: var(--heading-color);
        }
        
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--primary-dark);
        }
        
        .register-container {
            flex: 1;
            padding: 60px 0;
        }
        
        .register-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-title {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .register-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-color);
        }
        
        .form-control {
            height: 50px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(245, 168, 197, 0.25);
        }
        
        .btn-register {
            background: var(--primary-color);
            color: white;
            border: none;
            height: 50px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .register-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #777;
        }
        
        .register-footer a {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .register-footer a:hover {
            color: var(--secondary-color);
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px 0 0 8px;
        }
        
        .toggle-password {
            cursor: pointer;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #777;
            margin-top: 5px;
        }
        
        /* Password strength indicator */
        .password-strength {
            display: none;
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 5px;
            background-color: #ddd;
            position: relative;
            margin-bottom: 5px;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .strength-bar-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s;
        }
        
        .strength-text {
            font-size: 0.85rem;
        }
        
        /* For small devices */
        @media (max-width: 576px) {
            .register-card {
                border-radius: 0;
                box-shadow: none;
            }
            
            .register-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header & Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-spa me-2"></i>Beauty Makeup
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Dịch vụ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Đăng nhập</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="register-card">
                        <div class="register-header">
                            <h2 class="register-title">Đăng ký tài khoản</h2>
                            <p>Tạo tài khoản để đặt lịch và sử dụng dịch vụ</p>
                        </div>
                        <div class="register-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>Đăng ký thành công! Bạn sẽ được chuyển hướng sau vài giây...
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>Vui lòng kiểm tra lại thông tin:
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$success): ?>
                                <form action="register.php<?php echo isset($_GET['redirect']) ? '?redirect='.htmlspecialchars($_GET['redirect']) : ''; ?>" method="POST" id="registerForm" novalidate>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                                                </div>
                                                <small class="form-text text-muted">Tên đăng nhập phải có ít nhất 4 ký tự</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                    <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="fullname" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                                    <input type="text" class="form-control" id="fullname" name="fullname" placeholder="Nhập họ và tên" value="<?php echo htmlspecialchars($formData['fullname']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="Nhập số điện thoại" value="<?php echo htmlspecialchars($formData['phone']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                                                    <span class="input-group-text toggle-password" onclick="togglePassword('password')">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                                <div class="password-requirements">
                                                    Mật khẩu phải có ít nhất 6 ký tự
                                                </div>
                                                <div class="password-strength">
                                                    <div class="strength-bar">
                                                        <div class="strength-bar-fill"></div>
                                                    </div>
                                                    <div class="strength-text"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                                                    <span class="input-group-text toggle-password" onclick="togglePassword('confirm_password')">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="agree" required>
                                            <label class="form-check-label" for="agree">Tôi đồng ý với <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Điều khoản dịch vụ</a> và <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Chính sách bảo mật</a></label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-register w-100">Đăng ký</button>
                                    </div>
                                </form>
                                
                                <div class="register-footer">
                                    <p>Đã có tài khoản? <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect='.htmlspecialchars($_GET['redirect']) : ''; ?>">Đăng nhập ngay</a></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Điều khoản dịch vụ -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Điều khoản dịch vụ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. Giới thiệu</h5>
                    <p>Chào mừng bạn đến với Beauty Makeup Studio. Khi bạn sử dụng dịch vụ của chúng tôi, bạn đồng ý tuân theo các điều khoản này.</p>
                    
                    <h5>2. Đặt lịch và thanh toán</h5>
                    <p>Bạn có thể đặt lịch sử dụng dịch vụ thông qua trang web của chúng tôi. Việc đặt lịch chỉ được xác nhận khi bạn nhận được xác nhận từ hệ thống hoặc từ nghệ sĩ trang điểm.</p>
                    
                    <h5>3. Hủy lịch</h5>
                    <p>Bạn có thể hủy lịch ít nhất 24 giờ trước thời gian đã đặt. Việc hủy lịch trễ hơn có thể dẫn đến phí hủy lịch.</p>
                    
                    <h5>4. Quyền và trách nhiệm</h5>
                    <p>Chúng tôi cam kết cung cấp dịch vụ chất lượng cao. Bạn có quyền đưa ra yêu cầu và phản hồi về dịch vụ.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Chính sách bảo mật -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Chính sách bảo mật</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5>1. Thu thập thông tin</h5>
                    <p>Chúng tôi thu thập thông tin cá nhân khi bạn đăng ký tài khoản, đặt lịch hoặc liên hệ với chúng tôi. Thông tin bao gồm tên, email, số điện thoại và các thông tin liên quan đến dịch vụ.</p>
                    
                    <h5>2. Sử dụng thông tin</h5>
                    <p>Thông tin của bạn được sử dụng để cung cấp dịch vụ, xác nhận đặt lịch, liên hệ khi cần thiết và cải thiện trải nghiệm của bạn.</p>
                    
                    <h5>3. Bảo mật thông tin</h5>
                    <p>Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn bằng các biện pháp bảo mật thích hợp.</p>
                    
                    <h5>4. Chia sẻ thông tin</h5>
                    <p>Chúng tôi không chia sẻ thông tin cá nhân của bạn với bên thứ ba nào khác mà không có sự đồng ý của bạn, trừ khi có yêu cầu từ pháp luật.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 bg-dark text-white">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Beauty Makeup Studio. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.querySelector(`#${inputId} + .toggle-password i`);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.querySelector('.strength-bar-fill');
            const strengthText = document.querySelector('.strength-text');
            const strengthSection = document.querySelector('.password-strength');
            
            // Hiển thị thanh đánh giá mật khẩu
            if (password.length > 0) {
                strengthSection.style.display = 'block';
            } else {
                strengthSection.style.display = 'none';
                return;
            }
            
            // Kiểm tra độ mạnh mật khẩu
            let strength = 0;
            let feedback = '';
            
            // Độ dài
            if (password.length >= 6) {
                strength += 1;
            }
            
            // Kết hợp chữ thường và chữ hoa
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
                strength += 1;
            }
            
            // Số
            if (password.match(/\d/)) {
                strength += 1;
            }
            
            // Ký tự đặc biệt
            if (password.match(/[^a-zA-Z\d]/)) {
                strength += 1;
            }
            
            // Cập nhật giao diện dựa trên độ mạnh
            switch (strength) {
                case 0:
                    strengthBar.style.width = '10%';
                    strengthBar.style.backgroundColor = '#ff4e4e';
                    strengthText.textContent = 'Rất yếu';
                    strengthText.style.color = '#ff4e4e';
                    break;
                case 1:
                    strengthBar.style.width = '25%';
                    strengthBar.style.backgroundColor = '#ff4e4e';
                    strengthText.textContent = 'Yếu';
                    strengthText.style.color = '#ff4e4e';
                    break;
                case 2:
                    strengthBar.style.width = '50%';
                    strengthBar.style.backgroundColor = '#ffaa00';
                    strengthText.textContent = 'Trung bình';
                    strengthText.style.color = '#ffaa00';
                    break;
                case 3:
                    strengthBar.style.width = '75%';
                    strengthBar.style.backgroundColor = '#2ad13f';
                    strengthText.textContent = 'Tốt';
                    strengthText.style.color = '#2ad13f';
                    break;
                case 4:
                    strengthBar.style.width = '100%';
                    strengthBar.style.backgroundColor = '#00b800';
                    strengthText.textContent = 'Mạnh';
                    strengthText.style.color = '#00b800';
                    break;
            }
        });
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const agree = document.getElementById('agree').checked;
            
            let isValid = true;
            
            if (password !== confirmPassword) {
                alert('Mật khẩu và xác nhận mật khẩu không khớp');
                isValid = false;
            }
            
            if (!agree) {
                alert('Bạn phải đồng ý với điều khoản dịch vụ và chính sách bảo mật');
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>