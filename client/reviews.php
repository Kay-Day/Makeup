<?php
// client/reviews.php - Client reviews page

// Set page title
$page_title = "My Reviews";

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
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// Add/Edit Review
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_review'])) {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $rating = intval($_POST['rating'] ?? 0);
        $comment = sanitize_input($conn, $_POST['comment'] ?? '');
        
        // Validate booking and data
        $booking = get_record($conn, "SELECT b.*, a.user_id as artist_id 
                                      FROM bookings b 
                                      JOIN users a ON b.artist_id = a.user_id 
                                      WHERE b.booking_id = $booking_id 
                                      AND b.client_id = $client_id 
                                      AND b.status_id = 4"); // Only completed bookings
        
        if (!$booking) {
            set_error_message("Invalid booking or you cannot review this service.");
            redirect('/beautyclick/client/reviews.php');
            exit;
        }
        
        if ($rating < 1 || $rating > 5) {
            set_error_message("Please provide a valid rating (1-5 stars).");
        } elseif (empty($comment)) {
            set_error_message("Please provide a review comment.");
        } else {
            // Check if review already exists
            $existing_review = get_record($conn, "SELECT * FROM reviews WHERE booking_id = $booking_id");
            
            if ($existing_review) {
                // Update existing review
                $update_data = [
                    'rating' => $rating,
                    'comment' => $comment,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if (update_record($conn, 'reviews', $update_data, "review_id = {$existing_review['review_id']}")) {
                    // Update artist rating
                    update_artist_rating($booking['artist_id']);
                    
                    set_success_message("Your review has been updated successfully!");
                } else {
                    set_error_message("Failed to update your review. Please try again.");
                }
            } else {
                // Add new review
                $review_data = [
                    'booking_id' => $booking_id,
                    'rating' => $rating,
                    'comment' => $comment
                ];
                
                if (insert_record($conn, 'reviews', $review_data)) {
                    // Update artist rating
                    update_artist_rating($booking['artist_id']);
                    
                    // Create notification for artist
                    create_notification(
                        $booking['artist_id'],
                        "New Review Received",
                        "You have received a new review for booking #{$booking_id}."
                    );
                    
                    set_success_message("Your review has been submitted successfully!");
                } else {
                    set_error_message("Failed to submit your review. Please try again.");
                }
            }
            
            redirect('/beautyclick/client/reviews.php');
            exit;
        }
    }
}

// Delete Review
if ($action === 'delete' && $booking_id > 0) {
    // Verify review belongs to client
    $review = get_record($conn, "SELECT r.*, b.artist_id 
                               FROM reviews r 
                               JOIN bookings b ON r.booking_id = b.booking_id 
                               WHERE r.booking_id = $booking_id AND b.client_id = $client_id");
    
    if ($review) {
        if (delete_record($conn, 'reviews', "booking_id = $booking_id")) {
            // Update artist rating
            update_artist_rating($review['artist_id']);
            
            set_success_message("Review deleted successfully!");
        } else {
            set_error_message("Failed to delete review.");
        }
    } else {
        set_error_message("Review not found or you don't have permission to delete it.");
    }
    
    redirect('/beautyclick/client/reviews.php');
    exit;
}

// Get client reviews
$reviews_sql = "SELECT r.*, b.booking_date, b.artist_id, b.service_id,
                      s.service_name, s.image as service_image,
                      a.full_name as artist_name, a.avatar as artist_avatar
               FROM reviews r
               JOIN bookings b ON r.booking_id = b.booking_id
               JOIN services s ON b.service_id = s.service_id
               JOIN users a ON b.artist_id = a.user_id
               WHERE b.client_id = $client_id
               ORDER BY r.created_at DESC";
$reviews = get_records($conn, $reviews_sql);

// Get bookings ready for review (completed but not reviewed)
$pending_reviews_sql = "SELECT b.booking_id, b.booking_date, b.artist_id, b.service_id,
                             s.service_name, s.image as service_image,
                             a.full_name as artist_name, a.avatar as artist_avatar
                        FROM bookings b
                        JOIN services s ON b.service_id = s.service_id
                        JOIN users a ON b.artist_id = a.user_id
                        WHERE b.client_id = $client_id AND b.status_id = 4
                        AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.booking_id)
                        ORDER BY b.booking_date DESC";
$pending_reviews = get_records($conn, $pending_reviews_sql);

// Get booking details for add/edit review form
$review_booking = null;
$review_data = null;

if ($action === 'add' && $booking_id > 0) {
    $review_booking = get_record($conn, "SELECT b.*, 
                                          s.service_name, s.image as service_image,
                                          a.full_name as artist_name, a.avatar as artist_avatar
                                     FROM bookings b
                                     JOIN services s ON b.service_id = s.service_id
                                     JOIN users a ON b.artist_id = a.user_id
                                     WHERE b.booking_id = $booking_id 
                                     AND b.client_id = $client_id
                                     AND b.status_id = 4");
} elseif ($action === 'edit' && $booking_id > 0) {
    $review_data = get_record($conn, "SELECT r.*, b.booking_date, 
                                      s.service_name, s.image as service_image,
                                      a.full_name as artist_name, a.avatar as artist_avatar
                                 FROM reviews r
                                 JOIN bookings b ON r.booking_id = b.booking_id
                                 JOIN services s ON b.service_id = s.service_id
                                 JOIN users a ON b.artist_id = a.user_id
                                 WHERE r.booking_id = $booking_id AND b.client_id = $client_id");
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4">
    <div class="container">
        <h1 class="h3 mb-0">My Reviews</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="/beautyclick/client/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Reviews</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <?php if ($action === 'add' && $review_booking): ?>
    <!-- Add Review Form -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star text-warning me-2"></i>Write a Review
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-4">
                        <img src="/beautyclick/assets/uploads/services/<?php echo $review_booking['service_image'] ?: 'default-service.jpg'; ?>" 
                             alt="<?php echo $review_booking['service_name']; ?>" 
                             class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                        <div>
                            <h5><?php echo $review_booking['service_name']; ?></h5>
                            <div class="d-flex align-items-center mb-1">
                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $review_booking['artist_avatar']; ?>" 
                                     alt="<?php echo $review_booking['artist_name']; ?>" 
                                     class="rounded-circle me-2" width="24" height="24">
                                <span><?php echo $review_booking['artist_name']; ?></span>
                            </div>
                            <div class="small text-muted">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?php echo date('M d, Y', strtotime($review_booking['booking_date'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <input type="hidden" name="booking_id" value="<?php echo $review_booking['booking_id']; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label">Your Rating <span class="text-danger">*</span></label>
                            <div class="rating-input text-center mb-2">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="5 stars"></label>
                                
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 stars"></label>
                                
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 stars"></label>
                                
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="2 stars"></label>
                                
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="1 star"></label>
                            </div>
                            <div class="text-center small text-muted mb-3">Click to rate</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comment" class="form-label">Your Review <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required
                                      placeholder="Share your experience with this service..."></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="/beautyclick/client/reviews.php" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($action === 'edit' && $review_data): ?>
    <!-- Edit Review Form -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Your Review
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-4">
                        <img src="/beautyclick/assets/uploads/services/<?php echo $review_data['service_image'] ?: 'default-service.jpg'; ?>" 
                             alt="<?php echo $review_data['service_name']; ?>" 
                             class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
                        <div>
                            <h5><?php echo $review_data['service_name']; ?></h5>
                            <div class="d-flex align-items-center mb-1">
                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $review_data['artist_avatar']; ?>" 
                                     alt="<?php echo $review_data['artist_name']; ?>" 
                                     class="rounded-circle me-2" width="24" height="24">
                                <span><?php echo $review_data['artist_name']; ?></span>
                            </div>
                            <div class="small text-muted">
                                <i class="far fa-calendar-alt me-1"></i>
                                <?php echo date('M d, Y', strtotime($review_data['booking_date'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <input type="hidden" name="booking_id" value="<?php echo $review_data['booking_id']; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label">Your Rating <span class="text-danger">*</span></label>
                            <div class="rating-input text-center mb-2">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="edit_star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" 
                                       <?php echo ($review_data['rating'] == $i) ? 'checked' : ''; ?> required>
                                <label for="edit_star<?php echo $i; ?>" title="<?php echo $i; ?> stars"></label>
                                <?php endfor; ?>
                            </div>
                            <div class="text-center small text-muted mb-3">Click to rate</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comment" class="form-label">Your Review <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required><?php echo $review_data['comment']; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/beautyclick/client/reviews.php?action=delete&booking_id=<?php echo $review_data['booking_id']; ?>" 
                               class="btn btn-outline-danger"
                               onclick="return confirm('Are you sure you want to delete this review? This cannot be undone.');">
                                <i class="fas fa-trash me-1"></i>Delete Review
                            </a>
                            <div>
                                <a href="/beautyclick/client/reviews.php" class="btn btn-outline-secondary me-2">Cancel</a>
                                <button type="submit" name="submit_review" class="btn btn-primary">Update Review</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Pending Reviews Section -->
    <?php if (count($pending_reviews) > 0): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-clock text-warning me-2"></i>Pending Reviews
            </h5>
            <div class="text-muted small">Share your feedback for these services you've experienced</div>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($pending_reviews as $booking): ?>
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="/beautyclick/assets/uploads/services/<?php echo $booking['service_image'] ?: 'default-service.jpg'; ?>" 
                                 alt="<?php echo $booking['service_name']; ?>" 
                                 class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0"><?php echo $booking['service_name']; ?></h6>
                                <div class="d-flex align-items-center text-muted small">
                                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $booking['artist_avatar']; ?>" 
                                         alt="<?php echo $booking['artist_name']; ?>" 
                                         class="rounded-circle me-1" width="20" height="20">
                                    <span><?php echo $booking['artist_name']; ?></span>
                                    <span class="mx-2">|</span>
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                </div>
                            </div>
                        </div>
                        <a href="/beautyclick/client/reviews.php?action=add&booking_id=<?php echo $booking['booking_id']; ?>" 
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-star me-1"></i>Write Review
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- My Reviews Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-comments text-primary me-2"></i>My Reviews
            </h5>
            <div class="text-muted small">Reviews you've written for services</div>
        </div>
        <?php if (count($reviews) > 0): ?>
            <div class="card-body p-0">
                <?php foreach ($reviews as $review): ?>
                    <div class="border-bottom p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <img src="/beautyclick/assets/uploads/services/<?php echo $review['service_image'] ?: 'default-service.jpg'; ?>" 
                                     alt="<?php echo $review['service_name']; ?>" 
                                     class="rounded me-3" style="width: 70px; height: 70px; object-fit: cover;">
                                <div>
                                    <h5 class="mb-1"><?php echo $review['service_name']; ?></h5>
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="/beautyclick/assets/uploads/avatars/<?php echo $review['artist_avatar']; ?>" 
                                             alt="<?php echo $review['artist_name']; ?>" 
                                             class="rounded-circle me-2" width="24" height="24">
                                        <span><?php echo $review['artist_name']; ?></span>
                                    </div>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex">
                                <a href="/beautyclick/client/reviews.php?action=edit&booking_id=<?php echo $review['booking_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary me-2" title="Edit Review">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/beautyclick/client/reviews.php?action=delete&booking_id=<?php echo $review['booking_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger" title="Delete Review"
                                   onclick="return confirm('Are you sure you want to delete this review? This cannot be undone.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                        <div class="mb-2">
                            <p class="mb-0"><?php echo nl2br($review['comment']); ?></p>
                        </div>
                        <div class="text-muted small">
                            <i class="far fa-clock me-1"></i>
                            Reviewed on <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            <?php if (strtotime($review['updated_at']) > strtotime($review['created_at'])): ?>
                                (Updated on <?php echo date('M d, Y', strtotime($review['updated_at'])); ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card-body text-center py-5">
                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                <h4>No Reviews Yet</h4>
                <p class="text-muted mb-4">You haven't written any reviews yet. Share your experience after trying our services!</p>
                
                <?php if (count($pending_reviews) > 0): ?>
                <a href="/beautyclick/client/reviews.php?action=add&booking_id=<?php echo $pending_reviews[0]['booking_id']; ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-star me-2"></i>Write Your First Review
                </a>
                <?php else: ?>
                <a href="/beautyclick/services/index.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Browse Services
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php endif; ?>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>