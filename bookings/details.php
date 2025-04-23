<?php
// client/bookings.php - Client bookings page

// Set page title
$page_title = "My Bookings";

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

// Handle action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Process cancel booking
if ($action === 'cancel' && $booking_id > 0) {
    // Verify booking belongs to client
    $booking = get_record($conn, "SELECT * FROM bookings WHERE booking_id = $booking_id AND client_id = $client_id");
    
    if ($booking && in_array($booking['status_id'], [1, 2])) { // Only pending and confirmed can be cancelled
        if (update_record($conn, 'bookings', ['status_id' => 5], "booking_id = $booking_id")) {
            // Create notification for artist
            create_notification(
                $booking['artist_id'], 
                "Booking Cancelled", 
                "Booking #$booking_id has been cancelled by the client."
            );
            
            set_success_message("Booking cancelled successfully!");
        } else {
            set_error_message("Failed to cancel booking.");
        }
    } else {
        set_error_message("Booking not found or cannot be cancelled.");
    }
    redirect('/beautyclick/client/bookings.php');
    exit;
}

// Get active filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filter_condition = "";

switch ($filter) {
    case 'upcoming':
        $today = date('Y-m-d');
        $filter_condition = "AND (b.booking_date > '$today' OR (b.booking_date = '$today' AND b.booking_time >= CURRENT_TIME()))
                            AND b.status_id IN (1, 2, 3)"; // Pending, Confirmed, In Progress
        break;
    case 'completed':
        $filter_condition = "AND b.status_id = 4"; // Completed
        break;
    case 'cancelled':
        $filter_condition = "AND b.status_id IN (5, 6)"; // Cancelled, No Show
        break;
    case 'pending':
        $filter_condition = "AND b.status_id = 1"; // Pending
        break;
    case 'confirmed':
        $filter_condition = "AND b.status_id = 2"; // Confirmed
        break;
    default:
        $filter_condition = ""; // All
}

// Get bookings
$bookings_sql = "SELECT b.*, s.service_name, s.price, s.image AS service_image, 
                a.full_name as artist_name, a.avatar as artist_avatar,
                bs.status_name
                FROM bookings b
                JOIN services s ON b.service_id = s.service_id
                JOIN users a ON b.artist_id = a.user_id
                JOIN booking_status bs ON b.status_id = bs.status_id
                WHERE b.client_id = $client_id $filter_condition
                ORDER BY 
                CASE 
                    WHEN b.status_id IN (1, 2, 3) THEN 0 
                    ELSE 1
                END, 
                b.booking_date ASC, 
                b.booking_time ASC";

$bookings = get_records($conn, $bookings_sql);

// Get booking statistics
$total_bookings = count_records($conn, 'bookings', "client_id = $client_id");
$upcoming_bookings = count_records($conn, 'bookings', "client_id = $client_id AND 
                                 status_id IN (1, 2, 3) AND 
                                 (booking_date > CURRENT_DATE() OR (booking_date = CURRENT_DATE() AND booking_time >= CURRENT_TIME()))");
$completed_bookings = count_records($conn, 'bookings', "client_id = $client_id AND status_id = 4");
$cancelled_bookings = count_records($conn, 'bookings', "client_id = $client_id AND status_id IN (5, 6)");

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4">
    <div class="container">
        <h1 class="h3 mb-0">My Bookings</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="/beautyclick/client/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Bookings</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- Booking Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body py-4">
                    <div class="rounded-circle bg-primary bg-opacity-10 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-calendar-check text-primary fa-2x"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $total_bookings; ?></h3>
                    <p class="text-muted mb-0">Total Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body py-4">
                    <div class="rounded-circle bg-info bg-opacity-10 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-clock text-info fa-2x"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $upcoming_bookings; ?></h3>
                    <p class="text-muted mb-0">Upcoming</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body py-4">
                    <div class="rounded-circle bg-success bg-opacity-10 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-check-circle text-success fa-2x"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $completed_bookings; ?></h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card border-0 shadow-sm text-center h-100">
                <div class="card-body py-4">
                    <div class="rounded-circle bg-danger bg-opacity-10 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-times-circle text-danger fa-2x"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $cancelled_bookings; ?></h3>
                    <p class="text-muted mb-0">Cancelled</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <ul class="nav nav-pills nav-fill mb-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="/beautyclick/client/bookings.php?filter=all">
                        <i class="fas fa-th-list me-1"></i>All Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'upcoming' ? 'active' : ''; ?>" href="/beautyclick/client/bookings.php?filter=upcoming">
                        <i class="fas fa-calendar me-1"></i>Upcoming
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'completed' ? 'active' : ''; ?>" href="/beautyclick/client/bookings.php?filter=completed">
                        <i class="fas fa-check-circle me-1"></i>Completed
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" href="/beautyclick/client/bookings.php?filter=cancelled">
                        <i class="fas fa-times-circle me-1"></i>Cancelled
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Bookings List -->
    <?php if (count($bookings) > 0): ?>
        <div class="row">
            <?php foreach ($bookings as $booking): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <div class="h-100 position-relative" style="min-height: 180px;">
                                        <img src="/beautyclick/assets/uploads/services/<?php echo !empty($booking['service_image']) ? $booking['service_image'] : 'default-service.jpg'; ?>" 
                                             class="w-100 h-100 object-fit-cover rounded-start" alt="<?php echo $booking['service_name']; ?>">
                                        <?php
                                        $status_classes = [
                                            1 => 'bg-warning text-dark',  // Pending
                                            2 => 'bg-info text-white',    // Confirmed
                                            3 => 'bg-primary text-white', // In Progress
                                            4 => 'bg-success text-white', // Completed
                                            5 => 'bg-danger text-white',  // Cancelled
                                            6 => 'bg-secondary text-white' // No Show
                                        ];
                                        $status_class = $status_classes[$booking['status_id']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $status_class; ?> position-absolute top-0 end-0 m-2">
                                            <?php echo $booking['status_name']; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="p-3">
                                        <h5 class="mb-1"><?php echo $booking['service_name']; ?></h5>
                                        
                                        <div class="d-flex align-items-center mt-2 mb-3">
                                            <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['artist_avatar']; ?>" 
                                                 alt="<?php echo $booking['artist_name']; ?>" 
                                                 class="rounded-circle me-2" width="30" height="30">
                                            <div>
                                                <div class="small text-muted">Artist</div>
                                                <div class="fw-medium"><?php echo $booking['artist_name']; ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <div class="small text-muted">Date & Time</div>
                                                <div class="fw-medium">
                                                    <i class="far fa-calendar-alt text-primary me-1"></i> 
                                                    <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                    <span class="mx-1">|</span>
                                                    <i class="far fa-clock text-primary me-1"></i>
                                                    <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted">Price</div>
                                                <div class="fw-bold"><?php echo format_currency($booking['final_price']); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex mt-3">
                                            <a href="/beautyclick/bookings/details.php?id=<?php echo $booking['booking_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary flex-grow-1 me-2">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                            
                                            <?php if ($booking['status_id'] == 4 && !get_record($conn, "SELECT * FROM reviews WHERE booking_id = {$booking['booking_id']}")): ?>
                                                <a href="/beautyclick/client/reviews.php?action=add&booking_id=<?php echo $booking['booking_id']; ?>" 
                                                   class="btn btn-sm btn-outline-success flex-grow-1">
                                                    <i class="fas fa-star me-1"></i> Write Review
                                                </a>
                                            <?php elseif (in_array($booking['status_id'], [1, 2]) && strtotime($booking['booking_date']) > time()): ?>
                                                <a href="/beautyclick/client/bookings.php?action=cancel&id=<?php echo $booking['booking_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger flex-grow-1"
                                                   onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                    <i class="fas fa-times me-1"></i> Cancel
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body py-5 text-center">
                <img src="/beautyclick/assets/images/empty-bookings.svg" alt="No Bookings" style="max-width: 200px; margin-bottom: 1.5rem;" onerror="this.parentElement.innerHTML = '<i class=\'fas fa-calendar-alt fa-5x text-muted mb-4\'></i>'">
                <h4>No Bookings Found</h4>
                <p class="text-muted mb-4">You haven't made any <?php echo $filter !== 'all' ? strtolower($filter) : ''; ?> bookings yet.</p>
                <a href="/beautyclick/services/index.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Browse Services
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>