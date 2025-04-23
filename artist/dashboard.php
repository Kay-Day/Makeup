<?php
// artist/dashboard.php - Artist dashboard page

// Set page title
$page_title = "Artist Dashboard";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in and has artist role
if (!is_logged_in() || !user_has_role('artist')) {
    set_error_message("Access denied. Please login as an artist.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Get artist ID from session
$artist_id = $_SESSION['user_id'];

// Get artist profile
$artist_profile = get_artist_profile($artist_id);
if (!$artist_profile) {
    set_error_message("Artist profile not found.");
    redirect('/beautyclick/index.php');
    exit;
}

// Get statistics
// Total services
$total_services = count_records($conn, 'services', "artist_id = $artist_id AND is_available = 1");

// Total bookings
$total_bookings = count_records($conn, 'bookings', "artist_id = $artist_id");

// Pending bookings
$pending_bookings = count_records($conn, 'bookings', "artist_id = $artist_id AND status_id = 1");

// Completed bookings
$completed_bookings = count_records($conn, 'bookings', "artist_id = $artist_id AND status_id = 4");

// Total earnings
$earnings_sql = "SELECT SUM(final_price) as total FROM bookings WHERE artist_id = $artist_id AND status_id = 4";
$earnings_data = get_record($conn, $earnings_sql);
$total_earnings = $earnings_data['total'] ?? 0;

// Average rating
$avg_rating = $artist_profile['avg_rating'] ?? 0;

// Recent bookings
$recent_bookings_sql = "SELECT b.*, s.service_name, s.price, u.full_name as client_name, u.avatar as client_avatar, 
                        bs.status_name
                        FROM bookings b
                        JOIN services s ON b.service_id = s.service_id
                        JOIN users u ON b.client_id = u.user_id
                        JOIN booking_status bs ON b.status_id = bs.status_id
                        WHERE b.artist_id = $artist_id
                        ORDER BY b.booking_date DESC, b.booking_time DESC
                        LIMIT 5";
$recent_bookings = get_records($conn, $recent_bookings_sql);

// Recent reviews
$recent_reviews_sql = "SELECT r.*, b.service_id, s.service_name, u.full_name, u.avatar
                      FROM reviews r
                      JOIN bookings b ON r.booking_id = b.booking_id
                      JOIN services s ON b.service_id = s.service_id
                      JOIN users u ON b.client_id = u.user_id
                      WHERE b.artist_id = $artist_id
                      ORDER BY r.created_at DESC
                      LIMIT 3";
$recent_reviews = get_records($conn, $recent_reviews_sql);

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-primary text-white py-4 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">Artist Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/beautyclick/artist/services.php?action=add" class="btn btn-light">
                    <i class="fas fa-plus me-2"></i>Add New Service
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
                    <i class="fas fa-list"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo $total_services; ?></div>
                    <div class="dashboard-stat-label">Services</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="dashboard-stat dashboard-stat-info">
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
            <div class="dashboard-stat dashboard-stat-success">
                <div class="dashboard-stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo format_currency($total_earnings); ?></div>
                    <div class="dashboard-stat-label">Total Earnings</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="dashboard-stat dashboard-stat-warning">
                <div class="dashboard-stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo number_format($avg_rating, 1); ?></div>
                    <div class="dashboard-stat-label">Average Rating</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Pending Bookings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Bookings</h5>
                    <a href="/beautyclick/artist/bookings.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
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
                                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['client_avatar']; ?>" 
                                                     alt="<?php echo $booking['client_name']; ?>" 
                                                     class="rounded-circle me-2" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $booking['client_name']; ?></h6>
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
                                            <a href="/beautyclick/artist/bookings.php?action=view&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($booking['status_id'] == 1): // Pending ?>
                                            <a href="/beautyclick/artist/bookings.php?action=confirm&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check"></i>
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
            
            <!-- Recent Reviews -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Reviews</h5>
                    <span class="text-warning">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= round($avg_rating)): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span class="ms-1">(<?php echo number_format($avg_rating, 1); ?>)</span>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (count($recent_reviews) > 0): ?>
                        <?php foreach ($recent_reviews as $review): ?>
                        <div class="review-card p-3 mb-3">
                            <div class="review-header">
                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $review['avatar']; ?>" 
                                     alt="<?php echo $review['full_name']; ?>" class="review-avatar">
                                <div>
                                    <h6 class="review-author"><?php echo $review['full_name']; ?></h6>
                                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                    <span class="review-service"><?php echo $review['service_name']; ?></span>
                                </div>
                            </div>
                            <div class="review-rating my-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="review-content">
                                <p class="mb-0"><?php echo nl2br($review['comment']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="far fa-comment-dots fa-3x text-muted mb-3"></i>
                            <p class="mb-0">No reviews yet</p>
                        </div>
                    <?php endif; ?>
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
                    <p class="text-muted mb-3">Makeup Artist</p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo $total_services; ?></h6>
                            <small class="text-muted">Services</small>
                        </div>
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo $total_bookings; ?></h6>
                            <small class="text-muted">Bookings</small>
                        </div>
                        <div class="px-3">
                            <h6 class="mb-0"><?php echo number_format($avg_rating, 1); ?></h6>
                            <small class="text-muted">Rating</small>
                        </div>
                    </div>
                    
                    <a href="/beautyclick/artist/profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Profile
                    </a>
                </div>
            </div>
            
            <!-- Booking Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Booking Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-warning-subtle text-warning me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo $pending_bookings; ?></h6>
                                    <small class="text-muted">Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-info-subtle text-info me-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">
                                        <?php echo count_records($conn, 'bookings', "artist_id = $artist_id AND status_id = 2"); ?>
                                    </h6>
                                    <small class="text-muted">Confirmed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-primary-subtle text-primary me-3">
                                    <i class="fas fa-spinner"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">
                                        <?php echo count_records($conn, 'bookings', "artist_id = $artist_id AND status_id = 3"); ?>
                                    </h6>
                                    <small class="text-muted">In Progress</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-success-subtle text-success me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo $completed_bookings; ?></h6>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                        </div>
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
                    $upcoming_bookings_sql = "SELECT b.*, s.service_name, u.full_name as client_name, u.avatar as client_avatar
                                             FROM bookings b
                                             JOIN services s ON b.service_id = s.service_id
                                             JOIN users u ON b.client_id = u.user_id
                                             WHERE b.artist_id = $artist_id
                                             AND b.booking_date >= '$today'
                                             AND b.status_id IN (1, 2, 3)
                                             ORDER BY b.booking_date ASC, b.booking_time ASC
                                             LIMIT 5";
                    $upcoming_bookings = get_records($conn, $upcoming_bookings_sql);
                    ?>
                    
                    <?php if (count($upcoming_bookings) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($upcoming_bookings as $booking): ?>
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center">
                                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['client_avatar']; ?>" 
                                         alt="<?php echo $booking['client_name']; ?>" 
                                         class="rounded-circle me-3" width="45" height="45">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo $booking['client_name']; ?></h6>
                                        <div class="small text-muted">
                                            <?php echo $booking['service_name']; ?>
                                        </div>
                                        <div class="small">
                                            <i class="far fa-calendar-alt me-1 text-primary"></i>
                                            <?php echo format_date($booking['booking_date']); ?> at 
                                            <?php echo format_time($booking['booking_time']); ?>
                                        </div>
                                    </div>
                                    <a href="/beautyclick/artist/bookings.php?action=view&id=<?php echo $booking['booking_id']; ?>" 
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
                            <a href="/beautyclick/artist/services.php?action=add" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-plus me-2"></i>
                                <span>Add Service</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/artist/posts.php?action=add" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-image me-2"></i>
                                <span>Create Post</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/artist/bookings.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-calendar-day me-2"></i>
                                <span>All Bookings</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/artist/services.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-list me-2"></i>
                                <span>My Services</span>
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