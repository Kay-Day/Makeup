<?php
// includes/navbar.php - Navigation bar component
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/beautyclick/index.php">
            <img src="/beautyclick/assets/images/logo.jpg" alt="BeautyClick Logo" class="rounded-circle"
                style="max-height: 60px; width: 60px; object-fit: cover;">
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
            aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Search Form -->
            <form class="d-flex mx-auto my-2 my-lg-0" action="/beautyclick/services/search.php" method="GET">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Tìm kiếm dịch vụ..." name="keyword"
                        required>
                    <button class="btn btn-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Main Menu -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/beautyclick/index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/beautyclick/services/index.php">Dịch vụ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/beautyclick/artists.php">Thợ trang điểm</a>
                </li>

                <?php if (is_logged_in()): ?>
                    <?php if (user_has_role('artist')): ?>
                        <!-- Makeup Artist Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="artistDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Tổng quan thợ trang điểm
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="artistDropdown">
                                <li><a class="dropdown-item" href="/beautyclick/artist/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Trang tổng quan
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/artist/profile.php">
                                        <i class="fas fa-user-circle me-2"></i>Hồ sơ của tôi
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/artist/services.php">
                                        <i class="fas fa-list me-2"></i>Dịch vụ của tôi
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/artist/bookings.php">
                                        <i class="fas fa-calendar-check me-2"></i>Đặt chỗ
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/artist/posts.php">
                                        <i class="fas fa-images me-2"></i>Bài đăng của tôi
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="/beautyclick/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                            </ul>
                        </li>
                    <?php elseif (user_has_role('client')): ?>
                        <!-- Client Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="clientDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                My Account
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="clientDropdown">
                                <li><a class="dropdown-item" href="/beautyclick/client/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Trang tổng quan
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/client/profile.php">
                                        <i class="fas fa-user-circle me-2"></i>Hồ sơ của tôi
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/client/bookings.php">
                                        <i class="fas fa-calendar-check me-2"></i>Đặt chỗ của tôi
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/client/reviews.php">
                                        <i class="fas fa-star me-2"></i>Đánh giá của tôi
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="/beautyclick/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                    </a></li>
                            </ul>
                        </li>
                    <?php elseif (user_has_role('admin')): ?>
                        <!-- Admin Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Admin Panel
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="/beautyclick/admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/admin/users.php">
                                        <i class="fas fa-users me-2"></i>Manage Users
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/admin/services.php">
                                        <i class="fas fa-list me-2"></i>Manage Services
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/admin/categories.php">
                                        <i class="fas fa-tags me-2"></i>Categories
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/admin/discounts.php">
                                        <i class="fas fa-percent me-2"></i>Discount Codes
                                    </a></li>
                                <li><a class="dropdown-item" href="/beautyclick/admin/cities.php">
                                        <i class="fas fa-city me-2"></i>Cities
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="/beautyclick/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a></li>
                            </ul>
                        </li>
                       
                    <?php endif; ?>

                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php
                            // Get unread notifications count
                            $user_id = $_SESSION['user_id'];
                            $unread_count = count_records($conn, 'notifications', "user_id = $user_id AND is_read = 0");
                            if ($unread_count > 0):
                                ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $unread_count; ?>
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notification-dropdown"
                            aria-labelledby="notificationDropdown">
                            <h6 class="dropdown-header">Notifications</h6>
                            <div class="notification-list">
                                <?php
                                // Get recent notifications
                                $notifications = get_records($conn, "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");

                                if (count($notifications) > 0):
                                    foreach ($notifications as $notification):
                                        ?>
                                        <a class="dropdown-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>"
                                            href="/beautyclick/notifications.php?id=<?php echo $notification['notification_id']; ?>">
                                            <div class="d-flex">
                                                <div class="notification-icon">
                                                    <i class="fas fa-bell text-primary"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <h6 class="mb-1"><?php echo $notification['title']; ?></h6>
                                                    <p class="small text-muted mb-0">
                                                        <?php echo substr($notification['message'], 0, 50) . (strlen($notification['message']) > 50 ? '...' : ''); ?>
                                                    </p>
                                                    <span class="notification-time">
                                                        <?php echo date('d M, H:i', strtotime($notification['created_at'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </a>
                                        <?php
                                    endforeach;
                                else:
                                    ?>
                                    <div class="dropdown-item text-center">
                                        <p class="mb-0">No notifications</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center" href="/beautyclick/notifications.php">
                                View All Notifications
                            </a>
                        </div>
                    </li>

                <?php else: ?>
                    <!-- Not Logged In -->
                    <li class="nav-item">
                        <a class="nav-link" href="/beautyclick/auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm rounded-pill px-3"
                            href="/beautyclick/auth/register.php">
                            Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>