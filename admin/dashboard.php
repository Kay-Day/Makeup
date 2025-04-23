<?php
// admin/dashboard.php - Admin dashboard page

// Set page title
$page_title = "Admin Dashboard";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in and has admin role
if (!is_logged_in() || !user_has_role('admin')) {
    set_error_message("Access denied. Please login as an administrator.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Get statistics
// Total users
$total_users = count_records($conn, 'users', "role_id != 1"); // Exclude admin users

// Total artists
$total_artists = count_records($conn, 'users', "role_id = 2");

// Total clients
$total_clients = count_records($conn, 'users', "role_id = 3");

// Pending verifications
$pending_verifications = count_records($conn, 'users', "status = 'pending'");

// Total services
$total_services = count_records($conn, 'services');

// Total bookings
$total_bookings = count_records($conn, 'bookings');

// Total earnings
$earnings_sql = "SELECT SUM(final_price) as total FROM bookings WHERE status_id = 4";
$earnings_data = get_record($conn, $earnings_sql);
$total_earnings = $earnings_data['total'] ?? 0;

// Recent bookings
$recent_bookings_sql = "SELECT b.*, s.service_name, s.price, 
                        a.full_name as artist_name, a.avatar as artist_avatar,
                        c.full_name as client_name, c.avatar as client_avatar,
                        bs.status_name
                        FROM bookings b
                        JOIN services s ON b.service_id = s.service_id
                        JOIN users a ON b.artist_id = a.user_id
                        JOIN users c ON b.client_id = c.user_id
                        JOIN booking_status bs ON b.status_id = bs.status_id
                        ORDER BY b.created_at DESC
                        LIMIT 5";
$recent_bookings = get_records($conn, $recent_bookings_sql);

// Recent users
$recent_users_sql = "SELECT u.*, r.role_name 
                    FROM users u
                    JOIN roles r ON u.role_id = r.role_id
                    WHERE u.role_id != 1
                    ORDER BY u.created_at DESC
                    LIMIT 5";
$recent_users = get_records($conn, $recent_users_sql);

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-primary text-white py-4 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">Admin Dashboard</h1>
                <p class="mb-0">Welcome back, <?php echo $_SESSION['full_name']; ?>!</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/beautyclick/admin/users.php?action=add" class="btn btn-light">
                    <i class="fas fa-user-plus me-2"></i>Add New User
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
                    <i class="fas fa-users"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo $total_users; ?></div>
                    <div class="dashboard-stat-label">Total Users</div>
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
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="dashboard-stat-content">
                    <div class="dashboard-stat-value"><?php echo $pending_verifications; ?></div>
                    <div class="dashboard-stat-label">Pending Verifications</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User Statistics</h5>
                    <a href="/beautyclick/admin/users.php" class="btn btn-sm btn-outline-primary">
                        Manage Users
                    </a>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <canvas id="userChart" width="100%" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Makeup Artists</span>
                                    <span><?php echo $total_artists; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo ($total_users > 0) ? ($total_artists / $total_users * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Clients</span>
                                    <span><?php echo $total_clients; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo ($total_users > 0) ? ($total_clients / $total_users * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Pending Verification</span>
                                    <span><?php echo $pending_verifications; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo ($total_users > 0) ? ($pending_verifications / $total_users * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Booking Statistics</h5>
                    <a href="/beautyclick/admin/bookings.php" class="btn btn-sm btn-outline-primary">
                        View All Bookings
                    </a>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <canvas id="bookingChart" width="100%" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <?php
                            $pending_count = count_records($conn, 'bookings', "status_id = 1");
                            $confirmed_count = count_records($conn, 'bookings', "status_id = 2");
                            $in_progress_count = count_records($conn, 'bookings', "status_id = 3");
                            $completed_count = count_records($conn, 'bookings', "status_id = 4");
                            $cancelled_count = count_records($conn, 'bookings', "status_id = 5");
                            $no_show_count = count_records($conn, 'bookings', "status_id = 6");
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Pending</span>
                                    <span><?php echo $pending_count; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" role="progressbar" 
                                         style="width: <?php echo ($total_bookings > 0) ? ($pending_count / $total_bookings * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Confirmed/In Progress</span>
                                    <span><?php echo $confirmed_count + $in_progress_count; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo ($total_bookings > 0) ? (($confirmed_count + $in_progress_count) / $total_bookings * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Completed</span>
                                    <span><?php echo $completed_count; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo ($total_bookings > 0) ? ($completed_count / $total_bookings * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span>Cancelled/No-show</span>
                                    <span><?php echo $cancelled_count + $no_show_count; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" role="progressbar" 
                                         style="width: <?php echo ($total_bookings > 0) ? (($cancelled_count + $no_show_count) / $total_bookings * 100) : 0; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <a href="/beautyclick/admin/bookings.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
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
                                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['client_avatar']; ?>" 
                                                     alt="<?php echo $booking['client_name']; ?>" 
                                                     class="rounded-circle me-2" width="30" height="30">
                                                <span><?php echo $booking['client_name']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['artist_avatar']; ?>" 
                                                     alt="<?php echo $booking['artist_name']; ?>" 
                                                     class="rounded-circle me-2" width="30" height="30">
                                                <span><?php echo $booking['artist_name']; ?></span>
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
                                            <a href="/beautyclick/admin/bookings.php?action=view&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">No bookings found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Users</h5>
                    <a href="/beautyclick/admin/users.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_users) > 0): ?>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $user['avatar']; ?>" 
                                                     alt="<?php echo $user['full_name']; ?>" 
                                                     class="rounded-circle me-2" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0"><?php echo $user['full_name']; ?></h6>
                                                    <small class="text-muted">@<?php echo $user['username']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td>
                                            <?php if ($user['role_name'] === 'artist'): ?>
                                                <span class="badge bg-primary">Makeup Artist</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Client</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php elseif ($user['status'] === 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="/beautyclick/admin/users.php?action=view&id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($user['status'] === 'pending'): ?>
                                            <a href="/beautyclick/admin/users.php?action=approve&id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Pending Verifications -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Pending Verifications</h5>
                </div>
                <div class="card-body p-0">
                    <?php
                    $pending_users_sql = "SELECT u.*, r.role_name 
                                         FROM users u
                                         JOIN roles r ON u.role_id = r.role_id
                                         WHERE u.status = 'pending'
                                         ORDER BY u.created_at DESC
                                         LIMIT 5";
                    $pending_users = get_records($conn, $pending_users_sql);
                    ?>
                    
                    <?php if (count($pending_users) > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pending_users as $user): ?>
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center">
                                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $user['avatar']; ?>" 
                                         alt="<?php echo $user['full_name']; ?>" 
                                         class="rounded-circle me-3" width="45" height="45">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo $user['full_name']; ?></h6>
                                        <div class="small">
                                            <?php echo $user['email']; ?>
                                            <span class="badge bg-primary ms-1"><?php echo ucfirst($user['role_name']); ?></span>
                                        </div>
                                        <div class="small text-muted">
                                            Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="/beautyclick/admin/users.php?action=approve&id=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-success mb-1 d-block w-100">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </a>
                                        <a href="/beautyclick/admin/users.php?action=reject&id=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-danger d-block w-100">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if ($pending_verifications > 5): ?>
                        <div class="text-center p-3 border-top">
                            <a href="/beautyclick/admin/users.php?status=pending" class="text-decoration-none">
                                View all <?php echo $pending_verifications; ?> pending verifications
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="mb-0">No pending verifications</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <div class="card-body py-4">
                            <i class="fas fa-list fa-2x text-primary mb-3"></i>
                            <h3 class="mb-1"><?php echo count_records($conn, 'services'); ?></h3>
                            <p class="text-muted mb-0">Total Services</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <div class="card-body py-4">
                            <i class="fas fa-tags fa-2x text-info mb-3"></i>
                            <h3 class="mb-1"><?php echo count_records($conn, 'service_categories'); ?></h3>
                            <p class="text-muted mb-0">Categories</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <div class="card-body py-4">
                            <i class="fas fa-star fa-2x text-warning mb-3"></i>
                            <h3 class="mb-1"><?php echo count_records($conn, 'reviews'); ?></h3>
                            <p class="text-muted mb-0">Reviews</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center h-100">
                        <div class="card-body py-4">
                            <i class="fas fa-percent fa-2x text-success mb-3"></i>
                            <h3 class="mb-1"><?php echo count_records($conn, 'discount_codes', "is_active = 1"); ?></h3>
                            <p class="text-muted mb-0">Active Discounts</p>
                        </div>
                    </div>
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
                            <a href="/beautyclick/admin/users.php?action=add" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-user-plus me-2"></i>
                                <span>Add User</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/admin/categories.php?action=add" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-folder-plus me-2"></i>
                                <span>Add Category</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/admin/discounts.php?action=add" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-percentage me-2"></i>
                                <span>Add Discount</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/beautyclick/admin/cities.php" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="fas fa-city me-2"></i>
                                <span>Manage Cities</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Chart
    const userChart = document.getElementById('userChart').getContext('2d');
    new Chart(userChart, {
        type: 'doughnut',
        data: {
            labels: ['Artists', 'Clients', 'Pending'],
            datasets: [{
                data: [<?php echo $total_artists; ?>, <?php echo $total_clients; ?>, <?php echo $pending_verifications; ?>],
                backgroundColor: ['#ff6b6b', '#4ecdc4', '#ffa726'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12
                    }
                }
            }
        }
    });
    
    // Booking Chart
    const bookingChart = document.getElementById('bookingChart').getContext('2d');
    new Chart(bookingChart, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Confirmed/In Progress', 'Completed', 'Cancelled/No-show'],
            datasets: [{
                data: [
                    <?php echo $pending_count; ?>, 
                    <?php echo $confirmed_count + $in_progress_count; ?>, 
                    <?php echo $completed_count; ?>, 
                    <?php echo $cancelled_count + $no_show_count; ?>
                ],
                backgroundColor: ['#ffa726', '#29b6f6', '#66bb6a', '#ff5252'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12
                    }
                }
            }
        }
    });
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>