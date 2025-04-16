<?php
// Kiểm tra xem người dùng có đăng nhập không
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $isLoggedIn ? $_SESSION['role'] : '';
$activeClass = function($page) {
    $current = basename($_SERVER['PHP_SELF']);
    return ($current == $page) ? 'active' : '';
};
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Beauty Makeup Studio</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-container">
                <a href="index.php" class="logo">
                    <i class="fas fa-spa"></i> Beauty Makeup
                </a>
                
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul class="main-nav" id="mainNav">
                    <li><a href="index.php" class="<?php echo $activeClass('index.php'); ?>">Trang chủ</a></li>
                    <li><a href="services.php" class="<?php echo $activeClass('services.php'); ?>">Dịch vụ</a></li>
                    <li><a href="artists.php" class="<?php echo $activeClass('artists.php'); ?>">Nghệ sĩ</a></li>
                    <li><a href="gallery.php" class="<?php echo $activeClass('gallery.php'); ?>">Thư viện</a></li>
                    <li><a href="contact.php" class="<?php echo $activeClass('contact.php'); ?>">Liên hệ</a></li>
                    
                    <?php if ($isLoggedIn): ?>
                        <li><a href="my-bookings.php" class="<?php echo $activeClass('my-bookings.php'); ?>">Lịch hẹn của tôi</a></li>
                        
                        <?php if ($userRole == 'artist'): ?>
                            <li><a href="artist-profile.php" class="<?php echo $activeClass('artist-profile.php'); ?>">Hồ sơ nghệ sĩ</a></li>
                        <?php elseif ($userRole == 'admin'): ?>
                            <li><a href="admin/dashboard.php">Quản trị</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <div class="auth-buttons">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-menu">
                            <a href="profile.php" class="btn btn-sm btn-outline">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['fullname']); ?>
                            </a>
                            <a href="logout.php" class="btn btn-sm btn-primary">Đăng xuất</a>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-sm btn-outline">Đăng nhập</a>
                        <a href="register.php" class="btn btn-sm btn-primary">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>