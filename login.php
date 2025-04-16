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

// Xử lý đăng nhập
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Kiểm tra không để trống
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu';
    } else {
        // Truy vấn kiểm tra tài khoản
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                
                // Chuyển hướng đến trang đã yêu cầu
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Mật khẩu không chính xác';
            }
        } else {
            $error = 'Tài khoản không tồn tại';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Beauty Makeup Studio</title>
    
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
        
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 0;
        }
        
        .login-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-title {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .login-body {
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
        
        .btn-login {
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
        
        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
        
        .login-footer a {
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .login-footer a:hover {
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
        
        .social-login {
            margin-top: 30px;
            text-align: center;
        }
        
        .social-login-title {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .social-login-title::before,
        .social-login-title::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .social-login-title span {
            margin: 0 15px;
            color: #777;
        }
        
        .social-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 0 10px;
            font-size: 22px;
            color: white;
            transition: all 0.3s;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-facebook {
            background: #3b5998;
        }
        
        .btn-google {
            background: #db4437;
        }
        
        /* For small devices */
        @media (max-width: 576px) {
            .login-card {
                border-radius: 0;
                box-shadow: none;
            }
            
            .login-container {
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
                        <a class="nav-link" href="register.php">Đăng ký</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="login-card">
                        <div class="login-header">
                            <h2 class="login-title">Đăng nhập</h2>
                            <p>Đăng nhập để đặt lịch và sử dụng dịch vụ</p>
                        </div>
                        <div class="login-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect='.htmlspecialchars($_GET['redirect']) : ''; ?>" method="POST">
                                <div class="form-group">
                                    <label for="username" class="form-label">Tên đăng nhập</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Nhập tên đăng nhập" value="<?php echo htmlspecialchars($username); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password" class="form-label">Mật khẩu</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                                        <span class="input-group-text toggle-password" onclick="togglePassword()">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="form-group d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="remember">
                                        <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                                    </div>
                                    <a href="forgot-password.php" class="text-decoration-none">Quên mật khẩu?</a>
                                </div>
                                
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-login w-100">Đăng nhập</button>
                                </div>
                            </form>
                            
                            <div class="social-login">
                                <div class="social-login-title">
                                    <span>Hoặc đăng nhập với</span>
                                </div>
                                <a href="#" class="social-btn btn-facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-btn btn-google">
                                    <i class="fab fa-google"></i>
                                </a>
                            </div>
                            
                            <div class="login-footer">
                                <p>Bạn chưa có tài khoản? <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect='.htmlspecialchars($_GET['redirect']) : ''; ?>">Đăng ký ngay</a></p>
                            </div>
                        </div>
                    </div>
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password i');
            
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
    </script>
</body>
</html>