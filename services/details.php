<?php
// services/details.php - Service details and booking page

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Get service ID from URL
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if service ID is valid
if ($service_id <= 0) {
    set_error_message("Invalid service ID.");
    redirect('/beautyclick/services/index.php');
    exit;
}

// Get service details
$sql = "SELECT s.*, u.full_name AS artist_name, u.avatar AS artist_avatar, u.user_id AS artist_id,
        c.category_name, ap.avg_rating, ap.bio, ap.skills, ap.studio_address, ap.total_bookings
        FROM services s
        JOIN users u ON s.artist_id = u.user_id
        JOIN service_categories c ON s.category_id = c.category_id
        JOIN artist_profiles ap ON u.user_id = ap.user_id
        WHERE s.service_id = $service_id AND s.is_available = 1 AND u.status = 'active'";

$service = get_record($conn, $sql);

// Check if service exists
if (!$service) {
    set_error_message("Service not found or unavailable.");
    redirect('/beautyclick/services/index.php');
    exit;
}

// Set page title
$page_title = $service['service_name'];

// Get similar services from the same artist
$similar_services_sql = "SELECT s.*, c.category_name 
                        FROM services s
                        JOIN service_categories c ON s.category_id = c.category_id
                        WHERE s.artist_id = {$service['artist_id']} 
                        AND s.service_id != $service_id
                        AND s.is_available = 1
                        LIMIT 3";
$similar_services = get_records($conn, $similar_services_sql);

// Get reviews for this artist
$reviews_sql = "SELECT r.*, b.service_id, s.service_name, u.full_name, u.avatar
                FROM reviews r
                JOIN bookings b ON r.booking_id = b.booking_id
                JOIN services s ON b.service_id = s.service_id
                JOIN users u ON b.client_id = u.user_id
                WHERE b.artist_id = {$service['artist_id']}
                ORDER BY r.created_at DESC
                LIMIT 5";
$reviews = get_records($conn, $reviews_sql);

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_service'])) {
    // Check if user is logged in
    if (!is_logged_in()) {
        // Save intended service in session for redirect after login
        $_SESSION['intended_service_id'] = $service_id;
        set_error_message("Please login to book this service.");
        redirect('/beautyclick/auth/login.php');
        exit;
    }

    // Check if user has client role
    if (!user_has_role('client')) {
        set_error_message("Only clients can book services.");
        redirect('/beautyclick/services/details.php?id=' . $service_id);
        exit;
    }

    // Get form data
    $booking_date = sanitize_input($conn, $_POST['booking_date'] ?? '');
    $booking_time = sanitize_input($conn, $_POST['booking_time'] ?? '');
    $address = sanitize_input($conn, $_POST['address'] ?? '');
    $notes = sanitize_input($conn, $_POST['notes'] ?? '');
    $discount_code = sanitize_input($conn, $_POST['discount_code'] ?? '');
    $use_points = isset($_POST['use_points']) ? 1 : 0;

    // Validate input
    $errors = [];

    // Validate booking date
    if (empty($booking_date)) {
        $errors[] = "Please select a booking date.";
    } else {
        // Check if date is in the past
        $today = date('Y-m-d');
        if ($booking_date < $today) {
            $errors[] = "Please select a future date for booking.";
        }
    }

    // Validate booking time
    if (empty($booking_time)) {
        $errors[] = "Please select a booking time.";
    }

    // Validate address
    if (empty($address)) {
        $errors[] = "Please enter your address.";
    } elseif (!is_in_danang($address)) {
        $errors[] = "Currently, we only provide services in Da Nang. Please enter a Da Nang address.";
    }

    // Check if artist is available at selected date and time
    if (!empty($booking_date) && !empty($booking_time)) {
        if (!is_artist_available($service['artist_id'], $booking_date, $booking_time)) {
            $errors[] = "The artist is not available at the selected date and time. Please choose another slot.";
        }
    }

    // Calculate final price - FIXED LOGIC
    $original_price = $service['price'];
    $discount_amount = 0;
    $points_value = 0;
    $points_used = 0;
    $final_price = $original_price;

    // Apply discount code if provided
    if (!empty($discount_code)) {
        $discount = validate_discount_code($discount_code, $original_price);
        if ($discount) {
            $discount_amount = apply_discount($discount, $original_price);
            $final_price = $original_price - $discount_amount;
        } else {
            $errors[] = "Invalid discount code or minimum purchase amount not met.";
        }
    }

    // Apply points if requested - FIXED LOGIC
    if ($use_points) {
        // Get user's available points
        $user_id = $_SESSION['user_id'];
        $user = get_record($conn, "SELECT points FROM users WHERE user_id = $user_id");
        $available_points = $user['points'] ?? 0;

        if ($available_points > 0 && $final_price > 0) {
            // 1 point = 1,000 VND
            $max_points_value = $available_points * 1000;

            // Don't use more points than needed
            $points_value = min($max_points_value, $final_price);

            // Calculate actual points used
            $points_used = floor($points_value / 1000);

            // Apply points discount
            $final_price = $final_price - $points_value;
        }
    }

    // Ensure final price is not negative
    $final_price = max(0, $final_price);

    // If no errors, create booking
    if (empty($errors)) {
        // Prepare booking data
        $booking_data = [
            'client_id' => $_SESSION['user_id'],
            'artist_id' => $service['artist_id'],
            'service_id' => $service_id,
            'booking_date' => $booking_date,
            'booking_time' => $booking_time,
            'address' => $address,
            'notes' => $notes,
            'status_id' => 1, // Pending
            'original_price' => $original_price,
            'discount_amount' => $discount_amount,
            'final_price' => $final_price,
            'points_earned' => calculate_points($final_price),
            'points_used' => $points_used
        ];

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Insert booking
            $booking_id = insert_record($conn, 'bookings', $booking_data);

            // Update discount usage if applied
            if (!empty($discount_code) && $discount) {
                update_discount_usage($discount['code_id']);
            }

            // Update user points if used
            if ($use_points && $points_used > 0) {
                $new_points = $available_points - $points_used;
                update_record($conn, 'users', ['points' => $new_points], "user_id = $user_id");
            }

            // Create notifications
            // For client
            $client_title = "Booking Confirmed: {$service['service_name']}";
            $client_message = "Your booking for {$service['service_name']} on " . format_date($booking_date) . " at " . format_time($booking_time) . " has been confirmed. The makeup artist will contact you shortly.";
            create_notification($_SESSION['user_id'], $client_title, $client_message);

            // For artist
            $artist_title = "New Booking: {$service['service_name']}";
            $artist_message = "You have a new booking for {$service['service_name']} on " . format_date($booking_date) . " at " . format_time($booking_time) . ". Please check your bookings for details.";
            create_notification($service['artist_id'], $artist_title, $artist_message);

            // Commit transaction
            mysqli_commit($conn);

            // Set success message
            set_success_message("Booking successful! You will receive a confirmation shortly.");

            // Redirect to booking details
            redirect('/beautyclick/client/bookings.php');
            exit;
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);

            // Set error message
            set_error_message("Booking failed: " . $e->getMessage());
        }
    } else {
        // Set error message
        set_error_message(implode("<br>", $errors));
    }
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4">
    <div class="container">
        <h1 class="h3 mb-0"><?php echo $service['service_name']; ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="/beautyclick/services/index.php">Services</a></li>
                <li class="breadcrumb-item">
                    <a href="/beautyclick/services/index.php?category=<?php echo $service['category_id']; ?>">
                        <?php echo $service['category_name']; ?>
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $service['service_name']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Service Details -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm overflow-hidden">
                <!-- Service Image -->
                <div class="position-relative">
                    <img src="/beautyclick/assets/uploads/services/<?php echo !empty($service['image']) ? $service['image'] : 'default-service.jpg'; ?>"
                        class="w-100" style="max-height: 400px; object-fit: cover;"
                        alt="<?php echo $service['service_name']; ?>">
                    <div class="position-absolute top-0 end-0 p-3">
                        <span class="badge bg-primary p-2 fs-6"><?php echo format_currency($service['price']); ?></span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Service Title & Rating -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="card-title h3 mb-0"><?php echo $service['service_name']; ?></h2>
                        <div class="service-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= round($service['avg_rating'])): ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php else: ?>
                                    <i class="far fa-star text-warning"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span class="ms-1 text-muted">(<?php echo round($service['avg_rating'], 1); ?>)</span>
                        </div>
                    </div>

                    <!-- Service Category & Duration -->
                    <div class="mb-4">
                        <span class="badge bg-light text-primary">
                            <i class="fas fa-tag me-1"></i>
                            <?php echo $service['category_name']; ?>
                        </span>
                        <span class="badge bg-light text-secondary ms-2">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo $service['duration']; ?> minutes
                        </span>
                    </div>

                    <!-- Service Description -->
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p><?php echo nl2br($service['description']); ?></p>
                    </div>

                    <!-- Artist Info -->
                    <div class="mb-4">
                        <h5>About the Makeup Artist</h5>
                        <div class="d-flex align-items-center mb-3">
                            <img src="/beautyclick/assets/uploads/avatars/<?php echo $service['artist_avatar']; ?>"
                                class="rounded-circle me-3" width="60" height="60"
                                alt="<?php echo $service['artist_name']; ?>">
                            <div>
                                <h6 class="mb-1"><?php echo $service['artist_name']; ?></h6>
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-star text-warning me-1"></i>
                                        <span><?php echo round($service['avg_rating'], 1); ?></span>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-check text-secondary me-1"></i>
                                        <span><?php echo $service['total_bookings']; ?> bookings</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p><?php echo nl2br($service['bio']); ?></p>

                        <?php if (!empty($service['skills'])): ?>
                            <div class="mt-3">
                                <h6>Skills & Specialties</h6>
                                <p><?php echo nl2br($service['skills']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3">
                            <h6>Studio Location</h6>
                            <p>
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                <?php echo $service['studio_address']; ?>
                            </p>
                        </div>

                        <a href="/beautyclick/artists/profile.php?id=<?php echo $service['artist_id']; ?>"
                            class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user me-1"></i>View Full Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- Reviews Section -->
            <?php if (!empty($reviews)): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Client Reviews</h5>
                    </div>
                    <div class="card-body p-4">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card p-3 mb-3">
                                <div class="review-header">
                                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $review['avatar']; ?>"
                                        alt="<?php echo $review['full_name']; ?>" class="review-avatar">
                                    <div>
                                        <h6 class="review-author"><?php echo $review['full_name']; ?></h6>
                                        <span
                                            class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                        <?php if ($review['service_id'] == $service_id): ?>
                                            <span class="review-service">This service</span>
                                        <?php else: ?>
                                            <span class="review-service"><?php echo $review['service_name']; ?></span>
                                        <?php endif; ?>
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

                        <a href="/beautyclick/artists/profile.php?id=<?php echo $service['artist_id']; ?>#reviews"
                            class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-comments me-1"></i>View All Reviews
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Booking Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px; z-index: 100;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Book This Service</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $service_id; ?>"
                        method="POST">
                        <!-- Date Selection -->
                        <div class="mb-3">
                            <label for="booking_date" class="form-label">Date *</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date"
                                min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <!-- Time Slots -->
                        <div class="mb-3">
                            <label class="form-label">Time *</label>
                            <div id="time-slots" data-artist-id="<?php echo $service['artist_id']; ?>">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Please select a date to see available time slots.
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Address in Da Nang *</label>
                            <textarea class="form-control" id="address" name="address" rows="2"
                                required><?php echo is_logged_in() ? $_SESSION['address'] ?? '' : ''; ?></textarea>
                            <div class="form-text text-warning">
                                <i class="fas fa-info-circle me-1"></i>
                                Currently, we only provide services in Da Nang.
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"
                                placeholder="Special requests, preferences, etc."><?php echo $_POST['notes'] ?? ''; ?></textarea>
                        </div>

                        <!-- Price Summary -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="mb-3">Price Summary</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Original Price:</span>
                                    <span id="original-price"><?php echo format_currency($service['price']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount:</span>
                                    <span id="discount-amount">0 VND</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Points Used:</span>
                                    <span id="points-value">0 VND</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Final Price:</span>
                                    <span id="final-price"><?php echo format_currency($service['price']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Discount Code -->
                        <div class="mb-3">
                            <label for="discount_code" class="form-label">Discount Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="discount_code" name="discount_code"
                                    placeholder="Enter code" value="<?php echo $_POST['discount_code'] ?? ''; ?>">
                                <button class="btn btn-outline-primary" type="button" id="apply-discount">Apply</button>
                            </div>
                            <!-- Hidden field to store discount code ID -->
                            <input type="hidden" id="discount_code_id" name="discount_code_id" value="">
                        </div>

                        <!-- Use Points -->
                        <?php if (is_logged_in() && user_has_role('client')): ?>
                            <?php
                            $user_points = get_record($conn, "SELECT points FROM users WHERE user_id = {$_SESSION['user_id']}");
                            $available_points = $user_points['points'] ?? 0;
                            $points_value = $available_points * 1000; // 1 point = 1,000 VND
                            ?>
                            <?php if ($available_points > 0): ?>
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="use_points" name="use_points"
                                            data-points="<?php echo $available_points; ?>"
                                            data-points-value="<?php echo $points_value; ?>">
                                        <label class="form-check-label" for="use_points">
                                            Use <?php echo $available_points; ?> points
                                            (<?php echo format_currency($points_value); ?>)
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Submit Button -->
                        <button type="submit" name="book_service" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-calendar-check me-2"></i>Book Now
                        </button>

                        <?php if (!is_logged_in()): ?>
                            <div class="alert alert-warning mt-3 mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                You need to <a href="/beautyclick/auth/login.php">login</a> to book this service.
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Similar Services -->
    <?php if (!empty($similar_services)): ?>
        <section class="similar-services-section mt-5">
            <h3 class="mb-4">More Services by this Artist</h3>
            <div class="row">
                <?php foreach ($similar_services as $similar): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card service-card h-100">
                            <img src="/beautyclick/assets/uploads/services/<?php echo !empty($similar['image']) ? $similar['image'] : 'default-service.jpg'; ?>"
                                class="card-img-top service-img" alt="<?php echo $similar['service_name']; ?>">
                            <div class="service-price"><?php echo format_currency($similar['price']); ?></div>
                            <div class="card-body">
                                <h5 class="service-title card-title"><?php echo $similar['service_name']; ?></h5>
                                <p class="service-description card-text small text-muted mb-2">
                                    <?php echo substr($similar['description'], 0, 100) . (strlen($similar['description']) > 100 ? '...' : ''); ?>
                                </p>

                                <div class="service-footer">
                                    <span class="badge bg-light text-primary">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo $similar['category_name']; ?>
                                    </span>
                                    <span class="badge bg-light text-secondary">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo $similar['duration']; ?> min
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <a href="/beautyclick/services/details.php?id=<?php echo $similar['service_id']; ?>"
                                    class="btn btn-primary w-100">
                                    <i class="fas fa-calendar-check me-2"></i>Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Time slots handler
        const dateInput = document.getElementById('booking_date');
        const timeSlotsContainer = document.getElementById('time-slots');

        if (dateInput && timeSlotsContainer) {
            dateInput.addEventListener('change', function () {
                const selectedDate = this.value;
                const artistId = timeSlotsContainer.dataset.artistId;

                if (!selectedDate || !artistId) return;

                // Show loading indicator
                timeSlotsContainer.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Loading available slots...</div>';

                // In a real application, you would make an AJAX request to get available time slots
                // For this demo, let's simulate it with a setTimeout
                setTimeout(function () {
                    // Generate time slots from 9 AM to 8 PM
                    const slots = [];
                    for (let hour = 9; hour <= 20; hour++) {
                        const time = `${hour.toString().padStart(2, '0')}:00`;
                        const formattedTime = hour <= 12 ? `${hour}:00 AM` : `${hour - 12}:00 PM`;
                        // Random availability (in a real app, this would come from the server)
                        const available = Math.random() > 0.3; // 70% chance of being available
                        slots.push({ time, formattedTime, available });
                    }

                    // Create the time slots UI
                    timeSlotsContainer.innerHTML = '';
                    const row = document.createElement('div');
                    row.className = 'row g-2 mt-2';

                    slots.forEach(slot => {
                        const col = document.createElement('div');
                        col.className = 'col-md-4 col-6 mb-2';

                        const label = document.createElement('label');
                        label.className = 'time-slot-label d-block';

                        const input = document.createElement('input');
                        input.type = 'radio';
                        input.name = 'booking_time';
                        input.value = slot.time;
                        input.id = `time-${slot.time.replace(':', '')}`;
                        input.className = 'btn-check';
                        input.required = true;

                        const btn = document.createElement('span');
                        btn.className = 'btn btn-outline-primary w-100';
                        btn.textContent = slot.formattedTime;

                        if (!slot.available) {
                            input.disabled = true;
                            btn.className = 'btn btn-outline-secondary w-100';
                            btn.innerHTML = `${slot.formattedTime} <span class="badge bg-danger">Booked</span>`;
                        }

                        label.appendChild(input);
                        label.appendChild(btn);
                        col.appendChild(label);
                        row.appendChild(col);
                    });

                    timeSlotsContainer.appendChild(row);
                }, 500);
            });
        }

        // Price calculation - FIXED LOGIC
        const discountCodeInput = document.getElementById('discount_code');
    const applyDiscountBtn = document.getElementById('apply-discount');
    const pointsCheckbox = document.getElementById('use_points');
    const originalPriceEl = document.getElementById('original-price');
    const discountAmountEl = document.getElementById('discount-amount');
    const pointsValueEl = document.getElementById('points-value');
    const finalPriceEl = document.getElementById('final-price');
    const discountCodeIdInput = document.getElementById('discount_code_id');
    
    // CRITICAL: Get the original price from PHP and store it as a constant
    const ORIGINAL_PRICE = <?php echo intval($service['price']); ?>;
    let discountAmount = 0;
    let pointsValue = 0;
    let discountCodeId = null;
    
    // Immediately set the original price display to prevent it from changing
    if (originalPriceEl) {
        originalPriceEl.textContent = ORIGINAL_PRICE.toLocaleString('vi-VN') + ' VND';
    }
    
    function updatePriceDisplay() {
        // ALWAYS ensure original price is set correctly
        if (originalPriceEl) {
            originalPriceEl.textContent = ORIGINAL_PRICE.toLocaleString('vi-VN') + ' VND';
        }
        
        // Show discount amount
        if (discountAmountEl) {
            discountAmountEl.textContent = discountAmount.toLocaleString('vi-VN') + ' VND';
        }
        
        // Calculate price after discount
        let priceAfterDiscount = ORIGINAL_PRICE - discountAmount;
        priceAfterDiscount = Math.max(0, priceAfterDiscount); // Ensure not negative
        
        // Calculate points value to use
        pointsValue = 0;
        
        if (pointsCheckbox && pointsCheckbox.checked) {
            const availablePointsValue = parseInt(pointsCheckbox.dataset.pointsValue || 0);
            
            // Only use points if there's still money to pay
            if (priceAfterDiscount > 0) {
                // Don't use more points than needed
                pointsValue = Math.min(availablePointsValue, priceAfterDiscount);
            } else {
                // If price is already 0, uncheck the points checkbox
                pointsCheckbox.checked = false;
            }
        }
        
        // Show points value being used
        if (pointsValueEl) {
            pointsValueEl.textContent = pointsValue.toLocaleString('vi-VN') + ' VND';
        }
        
        // Calculate and display final price - CORRECTED LOGIC
        let finalPrice = ORIGINAL_PRICE - discountAmount - pointsValue;
        
        // Ensure final price is never negative
        finalPrice = Math.max(0, finalPrice);
        
        // Update the final price display
        if (finalPriceEl) {
            finalPriceEl.textContent = finalPrice.toLocaleString('vi-VN') + ' VND';
            
            // Remove any negative sign or class that might make it appear negative
            finalPriceEl.classList.remove('text-danger');
        }
        
        // Debug logging to check values
        console.log('Price calculation:', {
            originalPrice: ORIGINAL_PRICE,
            discountAmount: discountAmount,
            priceAfterDiscount: priceAfterDiscount,
            pointsValue: pointsValue,
            finalPrice: finalPrice,
            discountCodeId: discountCodeId
        });
    }
    
    // Apply discount code
    if (applyDiscountBtn && discountCodeInput) {
        applyDiscountBtn.addEventListener('click', function() {
            const discountCode = discountCodeInput.value.trim();
            if (!discountCode) {
                alert('Vui lòng nhập mã giảm giá.');
                return;
            }
            
            // Show loading indicator
            applyDiscountBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang xử lý...';
            applyDiscountBtn.disabled = true;
            
            // Call API to validate discount code
            fetch('/beautyclick/services/validate_discount.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `code=${encodeURIComponent(discountCode)}&amount=${ORIGINAL_PRICE}`
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                applyDiscountBtn.innerHTML = 'Apply';
                applyDiscountBtn.disabled = false;
                
                if (data.valid) {
                    discountAmount = parseFloat(data.discount_amount);
                    discountCodeId = data.code_id;
                    if (discountCodeIdInput) {
                        discountCodeIdInput.value = data.code_id;
                    }
                    alert('Áp dụng mã giảm giá thành công!');
                } else {
                    discountAmount = 0;
                    discountCodeId = null;
                    if (discountCodeIdInput) {
                        discountCodeIdInput.value = '';
                    }
                    alert(data.message || 'Mã giảm giá không hợp lệ. Vui lòng thử mã khác.');
                }
                
                updatePriceDisplay();
            })
            .catch(error => {
                console.error('Error:', error);
                applyDiscountBtn.innerHTML = 'Apply';
                applyDiscountBtn.disabled = false;
                alert('Đã xảy ra lỗi khi xác thực mã giảm giá. Vui lòng thử lại sau.');
            });
        });
    }
    // Handle points checkbox change
    if (pointsCheckbox) {
        pointsCheckbox.addEventListener('change', function() {
            // Check if price after discount is already 0
            let priceAfterDiscount = ORIGINAL_PRICE - discountAmount;
            
            if (this.checked && priceAfterDiscount <= 0) {
                this.checked = false;
                alert('Giá đã là 0, không thể sử dụng điểm.');
                return;
            }
            
            updatePriceDisplay();
        });
    }
    
    // Initial price display
    updatePriceDisplay();
    
    // Double-check every 500ms to ensure prices are correctly displayed
    setInterval(() => {
        if (originalPriceEl && originalPriceEl.textContent !== ORIGINAL_PRICE.toLocaleString('vi-VN') + ' VND') {
            originalPriceEl.textContent = ORIGINAL_PRICE.toLocaleString('vi-VN') + ' VND';
        }
        
        if (finalPriceEl) {
            // Recalculate the final price to make sure it's correct
            let correctFinalPrice = ORIGINAL_PRICE - discountAmount - pointsValue;
            correctFinalPrice = Math.max(0, correctFinalPrice);
            
            // If the displayed price is wrong, fix it
            if (finalPriceEl.textContent !== correctFinalPrice.toLocaleString('vi-VN') + ' VND') {
                finalPriceEl.textContent = correctFinalPrice.toLocaleString('vi-VN') + ' VND';
                finalPriceEl.classList.remove('text-danger');
            }
        }
    }, 300);
    
    // Check for negative prices in DOM at load and fix them
    window.addEventListener('load', function() {
        // Find all elements with ID containing "final-price" or class containing "final-price"
        const potentialElements = document.querySelectorAll('[id*="final-price"], [class*="final-price"]');
        
        potentialElements.forEach(el => {
            if (el.textContent.includes('-')) {
                // Remove negative sign and reformat
                const numericValue = parseFloat(el.textContent.replace(/[^\d.-]/g, '').replace('-', ''));
                if (!isNaN(numericValue)) {
                    el.textContent = numericValue.toLocaleString('vi-VN') + ' VND';
                    el.classList.remove('text-danger');
                }
            }
        });
    });
});

</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>