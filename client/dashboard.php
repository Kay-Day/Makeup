<?php
// client/dashboard.php - Client dashboard page

// Set page title
$page_title = "Client Dashboard";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in and has client role
if (!is_logged_in() || !user_has_role('client')) {
    set_error_message("Access denied. Please login as a client.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Get client ID from session
$client_id = $_SESSION['user_id'];

// Get statistics
// Total bookings
$total_bookings = count_records($conn, 'bookings', "client_id = $client_id");

// Pending bookings
$pending_bookings = count_records($conn, 'bookings', "client_id = $client_id AND status_id = 1");

// Completed bookings
$completed_bookings = count_records($conn, 'bookings', "client_id = $client_id AND status_id = 4");

// Loyalty points
$user_data = get_user_data($client_id);
$loyalty_points = $user_data['points'] ?? 0;
$points_value = $loyalty_points * 1000; // 1 point = 1,000 VND

// Recent bookings
$recent_bookings_sql = "SELECT b.*, s.service_name, s.price, u.full_name as artist_name, u.avatar as artist_avatar, 
                        bs.status_name
                        FROM bookings b
                        JOIN services s ON b.service_id = s.service_id
                        JOIN users u ON b.artist_id = u.user_id
                        JOIN booking_status bs ON b.status_id = bs.status_id
                        WHERE b.client_id = $client_id
                        ORDER BY b.created_at DESC
                        LIMIT 5";
$recent_bookings = get_records($conn, $recent_bookings_sql);

// Get recommended services based on previous bookings
$recommended_services_sql = "SELECT s.*, u.full_name AS artist_name, u.avatar AS artist_avatar, 
                            c.category_name, COALESCE(ap.avg_rating, 0) AS rating
                            FROM services s
                            JOIN users u ON s.artist_id = u.user_id
                            JOIN service_categories c ON s.category_id = c.category_id
                            LEFT JOIN artist_profiles ap ON u.user_id = ap.user_id
                            WHERE s.is_available = 1 AND u.status = 'active'
                            AND (
                                s.category_id IN (
                                    SELECT DISTINCT s2.category_id
                                    FROM bookings b
                                    JOIN services s2 ON b.service_id = s2.service_id
                                    WHERE b.client_id = $client_id
                                )
                                OR 
                                s.artist_id IN (
                                    SELECT DISTINCT b.artist_id
                                    FROM bookings b
                                    WHERE b.client_id = $client_id
                                )
                            )
                            AND s.service_id NOT IN (
                                SELECT b.service_id
                                FROM bookings b
                                WHERE b.client_id = $client_id
                            )
                            ORDER BY ap.avg_rating DESC, RAND()
                            LIMIT 3";
$recommended_services = get_records($conn, $recommended_services_sql);

// If no recommended services based on previous bookings, get top-rated services
if (empty($recommended_services)) {
    $top_services_sql = "SELECT s.*, u.full_name AS artist_name, u.avatar AS artist_avatar, 
                         c.category_name, COALESCE(ap.avg_rating, 0) AS rating
                         FROM services s
                         JOIN users u ON s.artist_id = u.user_id
                         JOIN service_categories c ON s.category_id = c.category_id
                         LEFT JOIN artist_profiles ap ON u.user_id = ap.user_id
                         WHERE s.is_available = 1 AND u.status = 'active'
                         ORDER BY ap.avg_rating DESC, RAND()
                         LIMIT 3";
    $recommended_services = get_records($conn, $top_services_sql);
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-primary text-white py-4 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">Client Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/beautyclick/services/index.php" class="btn btn-light">
                    <i class="fas fa-search me-2"></i>Browse Services
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="dashboard-stat dashboard-stat-primary">
                <div class="dashboard-stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo $total_bookings; ?></div>
                    <div class="dashboard-stat-label">Total Bookings</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="dashboard-stat dashboard-stat-warning">
                <div class="dashboard-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo $pending_bookings; ?></div>
                    <div class="dashboard-stat-label">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="dashboard-stat dashboard-stat-success">
                <div class="dashboard-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo $completed_bookings; ?></div>
                    <div class="dashboard-stat-label">Completed</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="dashboard-stat dashboard-stat-info">
                <div class="dashboard-stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo $loyalty_points; ?></div>
                    <div class="dashboard-stat-label">Loyalty Points</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Recent Bookings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Bookings</h5>
                    <a href="/beautyclick/client/bookings.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Artist</th>
                                    <th>Service</th>
                                    <th>Date & Time</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_bookings) > 0): ?>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['artist_avatar']; ?>" 
                                                     alt="<?php echo $booking['artist_name']; ?>" 
                                                     class="rounded-circle me-2" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $booking['artist_name']; ?></h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $booking['service_name']; ?></td>
                                        <td>
                                            <div><?php echo format_date($booking['booking_date']); ?></div>
                                            <small class="text-muted"><?php echo format_time($booking['booking_time']); ?></small>
                                        </td>
                                        <td><?php echo format_currency($booking['final_price']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($booking['status_id']) {
                                                case 1: // Pending
                                                    $status_class = 'bg-warning text-dark';
                                                    break;
                                                case 2: // Confirmed
                                                    $status_class = 'bg-info text-white';
                                                    break;
                                                case 3: // In Progress
                                                    $status_class = 'bg-primary text-white';
                                                    break;
                                                case 4: // Completed
                                                    $status_class = 'bg-success text-white';
                                                    break;
                                                case 5: // Cancelled
                                                    $status_class = 'bg-danger text-white';
                                                    break;
                                                case 6: // No Show
                                                    $status_class = 'bg-secondary text-white';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $booking['status_name']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/beautyclick/client/bookings.php?action=view&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($booking['status_id'] == 4 && !get_record($conn, "SELECT * FROM reviews WHERE booking_id = {$booking['booking_id']}")): ?>
                                            <a href="/beautyclick/client/reviews.php?action=add&booking_id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-star"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No bookings found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recommended Services -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recommended for You</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if (count($recommended_services) > 0): ?>
                            <?php foreach ($recommended_services as $service): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card service-card h-100">
                                    <img src="/beautyclick/assets/uploads/services/<?php echo !empty($service['image']) ? $service['image'] : 'default-service.jpg'; ?>" 
                                         class="card-img-top service-img" alt="<?php echo $service['service_name']; ?>">
                                    <div class="service-price"><?php echo format_currency($service['price']); ?></div>
                                    <div class="card-body">
                                        <div class="service-artist mb-2">
                                            <img src="/beautyclick/assets/uploads/avatars/<?php echo $service['artist_avatar']; ?>" 
                                                 class="service-artist-img" alt="<?php echo $service['artist_name']; ?>">
                                            <div>
                                                <small class="text-muted">Makeup Artist</small>
                                                <h6 class="service-artist-name mb-0"><?php echo $service['artist_name']; ?></h6>
                                            </div>
                                        </div>
                                        
                                        <h5 class="service-title card-title"><?php echo $service['service_name']; ?></h5>
                                        
                                        <div class="service-rating mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= round($service['rating'])): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <small class="ms-1">(<?php echo round($service['rating'], 1); ?>)</small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-top-0">
                                        <a href="/beautyclick/services/details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-calendar-check me-2"></i>Book Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Explore our services to get personalized recommendations.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Profile Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center">
                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $_SESSION['avatar']; ?>" 
                         alt="<?php echo $_SESSION['full_name']; ?>" 
                         class="rounded-circle mb-3" width="100" height="100">
                    <h5 class="mb-1"><?php echo $_SESSION['full_name']; ?></h5>
                    <p class="text-muted mb-3">Client</p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo $total_bookings; ?></h6>
                            <small class="text-muted">Bookings</small>
                        </div>
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo $completed_bookings; ?></h6>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="px-3">
                            <h6 class="mb-0"><?php echo $loyalty_points; ?></h6>
                            <small class="text-muted">Points</small>
                        </div>
                    </div>
                    
                    <a href="/beautyclick/client/profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Profile
                    </a>
                </div>
            </div>
            
            <!-- Loyalty Points -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Loyalty Points</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="display-5 mb-2 text-primary"><?php echo $loyalty_points; ?></div>
                        <p class="mb-1">Available points</p>
                        <div class="text-success h5"><?php echo format_currency($points_value); ?></div>
                        <small class="text-muted">Can be used on your next booking</small>
                    </div>
                    
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Earn 1 point for every 10,000 VND spent. Each point is worth 1,000 VND on future bookings.
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Bookings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Upcoming Bookings</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    $today = date('Y-m-d');
                    $upcoming_bookings_sql = "SELECT b.*, s.service_name, u.full_name as artist_name, u.avatar as artist_avatar
                                             FROM bookings b
                                             JOIN services s ON b.service_id = s.service_id
                                             JOIN users u ON b.artist_id = u.user_id
                                             WHERE b.client_id = $client_id
                                             AND b.booking_date >= '$today'
                                             AND b.status_id IN (1, 2, 3)
                                             ORDER BY b.booking_date ASC, b.booking_time ASC
                                             LIMIT 3";
                    $upcoming_bookings = get_records($conn, $upcoming_bookings_sql);
                    ?>
                    
                    <?php if (count($upcoming_bookings) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($upcoming_bookings as $booking): ?>
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center">
                                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['artist_avatar']; ?>" 
                                         alt="<?php echo $booking['artist_name']; ?>" 
                                         class="rounded-circle me-3" width="45" height="45">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo $booking['service_name']; ?></h6>
                                        <div class="small text-muted">
                                            <?php echo $booking['artist_name']; ?>
                                        </div>
                                        <div class="small">
                                            <i class="far fa-calendar-alt me-1 text-primary"></i>
                                            <?php echo format_date($booking['booking_date']); ?> at 
                                            <?php echo format_time($booking['booking_time']); ?>
                                        </div>
                                    </div>
                                    <a href="/beautyclick/client/bookings.php?action=view&id=<?php echo $booking['booking_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="far fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <p class="mb-0">No upcoming bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="/beautyclick/services/index.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-search me-2"></i>
                                <span>Browse Services</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/client/bookings.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-calendar-day me-2"></i>
                                <span>My Bookings</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/client/reviews.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-star me-2"></i>
                                <span>My Reviews</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/artists.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-user-friends me-2"></i>
                                <span>View Artists</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-box {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 1.1rem;
}
.bg-warning-subtle {
    background-color: rgba(255, 193, 7, 0.2);
}
.bg-info-subtle {
    background-color: rgba(13, 202, 240, 0.2);
}
.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.2);
}
.bg-success-subtle {
    background-color: rgba(25, 135, 84, 0.2);
}
</style>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>