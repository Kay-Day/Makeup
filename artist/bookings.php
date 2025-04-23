<?php
// artist/bookings.php - Manage artist bookings

// Set page title
$page_title = "Manage Bookings";

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

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $booking_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Verify booking belongs to artist
    $booking = get_record($conn, "SELECT * FROM bookings WHERE booking_id = $booking_id AND artist_id = $artist_id");
    
    if ($booking) {
        switch ($action) {
            case 'confirm':
                if ($booking['status_id'] == 1) { // Pending
                    if (update_record($conn, 'bookings', ['status_id' => 2], "booking_id = $booking_id")) {
                        // Create notification for client
                        create_notification(
                            $booking['client_id'], 
                            "Booking Confirmed", 
                            "Your booking #$booking_id has been confirmed by the artist."
                        );
                        
                        set_success_message("Booking confirmed successfully!");
                    } else {
                        set_error_message("Failed to confirm booking.");
                    }
                }
                break;
                
            case 'start':
                if ($booking['status_id'] == 2) { // Confirmed
                    if (update_record($conn, 'bookings', ['status_id' => 3], "booking_id = $booking_id")) {
                        // Create notification for client
                        create_notification(
                            $booking['client_id'], 
                            "Booking In Progress", 
                            "Your booking #$booking_id is now in progress."
                        );
                        
                        set_success_message("Booking marked as in progress!");
                    } else {
                        set_error_message("Failed to update booking status.");
                    }
                }
                break;
                
            case 'complete':
                if ($booking['status_id'] == 3) { // In Progress
                    if (update_record($conn, 'bookings', ['status_id' => 4], "booking_id = $booking_id")) {
                        // Calculate and add points
                        $points_earned = calculate_points($booking['final_price']);
                        update_record($conn, 'bookings', ['points_earned' => $points_earned], "booking_id = $booking_id");
                        update_record($conn, 'users', ['points = points + ' . $points_earned], "user_id = {$booking['client_id']}");
                        
                        // Update artist total bookings
                        update_record($conn, 'artist_profiles', ['total_bookings = total_bookings + 1'], "user_id = $artist_id");
                        
                        // Create notification for client
                        create_notification(
                            $booking['client_id'], 
                            "Booking Completed", 
                            "Your booking #$booking_id has been completed. Please leave a review!"
                        );
                        
                        set_success_message("Booking marked as completed!");
                    } else {
                        set_error_message("Failed to complete booking.");
                    }
                }
                break;
                
            case 'cancel':
                if (in_array($booking['status_id'], [1, 2])) { // Pending or Confirmed
                    if (update_record($conn, 'bookings', ['status_id' => 5], "booking_id = $booking_id")) {
                        // Create notification for client
                        create_notification(
                            $booking['client_id'], 
                            "Booking Cancelled", 
                            "Your booking #$booking_id has been cancelled by the artist."
                        );
                        
                        set_success_message("Booking cancelled successfully!");
                    } else {
                        set_error_message("Failed to cancel booking.");
                    }
                }
                break;
                
            case 'noshow':
                if ($booking['status_id'] == 2) { // Confirmed
                    if (update_record($conn, 'bookings', ['status_id' => 6], "booking_id = $booking_id")) {
                        // Create notification for client
                        create_notification(
                            $booking['client_id'], 
                            "No-Show Recorded", 
                            "Your booking #$booking_id has been marked as a no-show."
                        );
                        
                        set_success_message("Booking marked as no-show!");
                    } else {
                        set_error_message("Failed to update booking status.");
                    }
                }
                break;
        }
    } else {
        set_error_message("Booking not found or you don't have permission.");
    }
    
    redirect('/beautyclick/artist/bookings.php');
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? intval($_GET['status']) : 0;
$date_filter = isset($_GET['date']) ? sanitize_input($conn, $_GET['date']) : '';

// Build query
$sql = "SELECT b.*, u.full_name as client_name, u.phone as client_phone, u.avatar as client_avatar,
               s.service_name, s.duration, s.image as service_image,
               bs.status_name
        FROM bookings b
        JOIN users u ON b.client_id = u.user_id
        JOIN services s ON b.service_id = s.service_id
        JOIN booking_status bs ON b.status_id = bs.status_id
        WHERE b.artist_id = $artist_id";

// Apply filters
if ($status_filter > 0) {
    $sql .= " AND b.status_id = $status_filter";
}

if (!empty($date_filter)) {
    $sql .= " AND b.booking_date = '$date_filter'";
}

// Order by date, with upcoming bookings first
$sql .= " ORDER BY 
            CASE 
                WHEN b.status_id IN (1, 2, 3) THEN 0 
                ELSE 1
            END, 
            b.booking_date ASC, 
            b.booking_time ASC";

// Get bookings
$bookings = get_records($conn, $sql);

// Get all booking status options
$statuses = get_records($conn, "SELECT * FROM booking_status ORDER BY status_id");

// Get upcoming booking dates for filter dropdown
$dates_sql = "SELECT DISTINCT booking_date FROM bookings 
              WHERE artist_id = $artist_id AND booking_date >= CURDATE()
              ORDER BY booking_date ASC
              LIMIT 10";
$upcoming_dates = get_records($conn, $dates_sql);

// Get booking statistics
$stats = [
    'pending' => count_records($conn, 'bookings', "artist_id = $artist_id AND status_id = 1"),
    'confirmed' => count_records($conn, 'bookings', "artist_id = $artist_id AND status_id = 2"),
    'completed' => count_records($conn, 'bookings', "artist_id = $artist_id AND status_id = 4"),
    'today' => count_records($conn, 'bookings', "artist_id = $artist_id AND booking_date = CURDATE()")
];

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-0">Manage Bookings</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="/beautyclick/artist/dashboard.php">Artist Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Bookings</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="/beautyclick/artist/availability.php" class="btn btn-outline-primary">
                    <i class="fas fa-clock me-2"></i>Manage Availability
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Booking Stats -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="card bg-primary text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Today's Bookings</h6>
                            <h2 class="display-6 mb-0 mt-1"><?php echo $stats['today']; ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-calendar-day fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-primary border-light py-2">
                    <a href="?date=<?php echo date('Y-m-d'); ?>" class="text-white text-decoration-none small">
                        <i class="fas fa-eye me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="card bg-warning text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Pending</h6>
                            <h2 class="display-6 mb-0 mt-1"><?php echo $stats['pending']; ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-hourglass-half fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-warning border-light py-2">
                    <a href="?status=1" class="text-white text-decoration-none small">
                        <i class="fas fa-eye me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Confirmed</h6>
                            <h2 class="display-6 mb-0 mt-1"><?php echo $stats['confirmed']; ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-info border-light py-2">
                    <a href="?status=2" class="text-white text-decoration-none small">
                        <i class="fas fa-eye me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-0">Completed</h6>
                            <h2 class="display-6 mb-0 mt-1"><?php echo $stats['completed']; ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-flag-checkered fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-success border-light py-2">
                    <a href="?status=4" class="text-white text-decoration-none small">
                        <i class="fas fa-eye me-1"></i>View Details
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Controls -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="0">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status['status_id']; ?>" <?php echo ($status_filter == $status['status_id']) ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $status['status_name'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date" class="form-label">Filter by Date</label>
                    <select class="form-select" id="date" name="date">
                        <option value="">All Dates</option>
                        <option value="<?php echo date('Y-m-d'); ?>" <?php echo ($date_filter == date('Y-m-d')) ? 'selected' : ''; ?>>Today</option>
                        <option value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" <?php echo ($date_filter == date('Y-m-d', strtotime('+1 day'))) ? 'selected' : ''; ?>>Tomorrow</option>
                        <?php if (count($upcoming_dates) > 0): ?>
                            <optgroup label="Upcoming Dates">
                                <?php foreach ($upcoming_dates as $date): ?>
                                    <?php if ($date['booking_date'] != date('Y-m-d') && $date['booking_date'] != date('Y-m-d', strtotime('+1 day'))): ?>
                                        <option value="<?php echo $date['booking_date']; ?>" <?php echo ($date_filter == $date['booking_date']) ? 'selected' : ''; ?>>
                                            <?php echo date('M d, Y (D)', strtotime($date['booking_date'])); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="/beautyclick/artist/bookings.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bookings List -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php if ($status_filter > 0): ?>
                        <?php echo ucfirst(str_replace('_', ' ', get_record($conn, "SELECT status_name FROM booking_status WHERE status_id = $status_filter")['status_name'])); ?> Bookings
                    <?php elseif (!empty($date_filter)): ?>
                        Bookings for <?php echo date('F d, Y', strtotime($date_filter)); ?>
                    <?php else: ?>
                        All Bookings
                    <?php endif; ?>
                </h5>
                <span class="badge bg-secondary"><?php echo count($bookings); ?> booking(s)</span>
            </div>
        </div>
        <?php if (count($bookings) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#ID</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['client_avatar']; ?>" 
                                             alt="<?php echo $booking['client_name']; ?>" 
                                             class="rounded-circle me-2" width="30" height="30">
                                        <div>
                                            <div class="fw-medium"><?php echo $booking['client_name']; ?></div>
                                            <div class="small text-muted"><?php echo $booking['client_phone']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><?php echo $booking['service_name']; ?></div>
                                    <div class="small text-muted"><?php echo $booking['duration']; ?> min</div>
                                </td>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                                    <div class="small text-muted"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></div>
                                </td>
                                <td><?php echo format_currency($booking['final_price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        switch($booking['status_id']) {
                                            case 1: echo 'warning'; break; // Pending
                                            case 2: echo 'info'; break;    // Confirmed
                                            case 3: echo 'primary'; break; // In Progress
                                            case 4: echo 'success'; break; // Completed
                                            case 5: echo 'danger'; break;  // Cancelled
                                            case 6: echo 'secondary'; break; // No Show
                                        }
                                    ?>">
                                        <?php echo $booking['status_name']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($booking['status_id'] == 1): ?>
                                            <!-- For pending bookings -->
                                            <a href="/beautyclick/artist/bookings.php?action=confirm&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-success" title="Confirm">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="/beautyclick/artist/bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-danger" title="Cancel"
                                               onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php elseif ($booking['status_id'] == 2): ?>
                                            <!-- For confirmed bookings -->
                                            <a href="/beautyclick/artist/bookings.php?action=start&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-primary" title="Start Service">
                                                <i class="fas fa-hourglass-start"></i>
                                            </a>
                                            <a href="/beautyclick/artist/bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-danger" title="Cancel"
                                               onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                            <a href="/beautyclick/artist/bookings.php?action=noshow&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-secondary" title="Mark as No-Show"
                                               onclick="return confirm('Mark this client as no-show?');">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php elseif ($booking['status_id'] == 3): ?>
                                            <!-- For in-progress bookings -->
                                            <a href="/beautyclick/artist/bookings.php?action=complete&id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-success" title="Complete">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="/beautyclick/bookings/details.php?id=<?php echo $booking['booking_id']; ?>" 
                                           class="btn btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="card-body text-center py-5">
                <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                <h4>No Bookings Found</h4>
                <p class="text-muted">You don't have any bookings matching the selected criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>