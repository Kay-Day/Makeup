<?php
// artists/profile.php - Artist public profile page

// Set page title
$page_title = "Artist Profile";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Get artist ID from URL
$artist_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate artist ID
if ($artist_id <= 0) {
    set_error_message("Invalid artist ID.");
    redirect('/beautyclick/artists.php');
    exit;
}

// Get artist data
$artist = get_record($conn, "SELECT u.*, ap.* FROM users u 
                            JOIN artist_profiles ap ON u.user_id = ap.user_id
                            WHERE u.user_id = $artist_id AND u.role_id = 2 AND u.status = 'active'");

// Check if artist exists
if (!$artist) {
    set_error_message("Artist not found.");
    redirect('/beautyclick/artists.php');
    exit;
}

// Update page title
$page_title = $artist['full_name'] . " - Artist Profile";

// Get artist services
$services = get_records($conn, "SELECT s.*, c.category_name 
                               FROM services s
                               JOIN service_categories c ON s.category_id = c.category_id
                               WHERE s.artist_id = $artist_id AND s.is_available = 1
                               ORDER BY s.price ASC");

// Get artist reviews
$reviews = get_records($conn, "SELECT r.*, b.service_id, s.service_name, u.full_name, u.avatar
                              FROM reviews r
                              JOIN bookings b ON r.booking_id = b.booking_id
                              JOIN services s ON b.service_id = s.service_id
                              JOIN users u ON b.client_id = u.user_id
                              WHERE b.artist_id = $artist_id
                              ORDER BY r.created_at DESC
                              LIMIT 10");

// Get artist posts
$posts = get_records($conn, "SELECT p.*, 
                            (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) AS comments_count
                            FROM posts p
                            WHERE p.artist_id = $artist_id
                            ORDER BY p.created_at DESC
                            LIMIT 5");

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Artist Profile Header -->
<div class="container-fluid bg-light py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-3 col-md-4 text-center text-md-start mb-4 mb-md-0">
                <img src="/beautyclick/assets/uploads/avatars/<?php echo $artist['avatar']; ?>" 
                     alt="<?php echo $artist['full_name']; ?>" 
                     class="rounded-circle border border-4 border-white shadow" width="180" height="180">
            </div>
            <div class="col-lg-9 col-md-8">
                <h1 class="h2 mb-2">
                    <?php echo $artist['full_name']; ?>
                    <?php if ($artist['is_verified']): ?>
                    <span class="badge bg-primary ms-2" title="Verified Makeup Artist">
                        <i class="fas fa-check-circle me-1"></i>Verified
                    </span>
                    <?php endif; ?>
                </h1>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="me-4">
                        <span class="text-warning">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= round($artist['avg_rating'])): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </span>
                        <span class="ms-1"><?php echo number_format($artist['avg_rating'], 1); ?> (<?php echo count($reviews); ?> reviews)</span>
                    </div>
                    <div>
                        <i class="fas fa-calendar-check text-primary me-1"></i>
                        <span><?php echo $artist['total_bookings']; ?> bookings</span>
                    </div>
                </div>
                
                <p class="mb-3"><?php echo $artist['bio']; ?></p>
                
                <?php if (!empty($artist['skills'])): ?>
                <p class="mb-3">
                    <strong>Skills & Specialties:</strong> <?php echo $artist['skills']; ?>
                </p>
                <?php endif; ?>
                
                <p class="mb-4">
                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                    <strong>Studio:</strong> <?php echo $artist['studio_address']; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4" id="artistTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab" aria-controls="services" aria-selected="true">
                <i class="fas fa-list me-1"></i>Services
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab" aria-controls="reviews" aria-selected="false">
                <i class="fas fa-star me-1"></i>Reviews
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab" aria-controls="posts" aria-selected="false">
                <i class="fas fa-images me-1"></i>Posts
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="availability-tab" data-bs-toggle="tab" data-bs-target="#availability" type="button" role="tab" aria-controls="availability" aria-selected="false">
                <i class="fas fa-clock me-1"></i>Availability
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="artistTabsContent">
        <!-- Services Tab -->
        <div class="tab-pane fade show active" id="services" role="tabpanel" aria-labelledby="services-tab">
            <div class="row">
                <?php if (count($services) > 0): ?>
                    <?php foreach ($services as $service): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card service-card h-100">
                            <img src="/beautyclick/assets/uploads/services/<?php echo !empty($service['image']) ? $service['image'] : 'default-service.jpg'; ?>" 
                                 class="card-img-top service-img" alt="<?php echo $service['service_name']; ?>">
                            <div class="service-price"><?php echo format_currency($service['price']); ?></div>
                            <div class="card-body">
                                <h5 class="service-title card-title"><?php echo $service['service_name']; ?></h5>
                                <p class="service-description card-text small text-muted mb-2">
                                    <?php echo substr($service['description'], 0, 100) . (strlen($service['description']) > 100 ? '...' : ''); ?>
                                </p>
                                
                                <div class="service-footer">
                                    <span class="badge bg-light text-primary">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo $service['category_name']; ?>
                                    </span>
                                    <span class="badge bg-light text-secondary">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo $service['duration']; ?> min
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <a href="/beautyclick/services/details.php?id=<?php echo $service['service_id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-calendar-check me-2"></i>Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <p class="mb-0">This artist hasn't added any services yet.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews Tab -->
        <div class="tab-pane fade" id="reviews" role="tabpanel" aria-labelledby="reviews-tab">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
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
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-star fa-2x mb-3"></i>
                    <p class="mb-0">This artist hasn't received any reviews yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Posts Tab -->
        <div class="tab-pane fade" id="posts" role="tabpanel" aria-labelledby="posts-tab">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                <div class="card mb-4">
                    <?php if (!empty($post['image'])): ?>
                    <img src="/beautyclick/assets/uploads/posts/<?php echo $post['image']; ?>" 
                         class="card-img-top" alt="<?php echo $post['title']; ?>" style="max-height: 300px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $post['title']; ?></h5>
                        <p class="text-muted small">
                            <i class="far fa-calendar-alt me-1"></i>
                            <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                        </p>
                        <p class="card-text"><?php echo substr(strip_tags($post['content']), 0, 200) . (strlen(strip_tags($post['content'])) > 200 ? '...' : ''); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-3">
                                    <i class="far fa-heart me-1"></i><?php echo $post['likes']; ?>
                                </span>
                                <span>
                                    <i class="far fa-comment me-1"></i><?php echo $post['comments_count']; ?>
                                </span>
                            </div>
                            <a href="/beautyclick/posts/details.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">
                                Read More
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-4">
                    <i class="fas fa-images fa-2x mb-3"></i>
                    <p class="mb-0">This artist hasn't published any posts yet.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Availability Tab -->
        <div class="tab-pane fade" id="availability" role="tabpanel" aria-labelledby="availability-tab">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Working Hours</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Day</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                $availability = get_records($conn, "SELECT * FROM artist_availability WHERE artist_id = $artist_id ORDER BY day_of_week");
                                $avail_by_day = [];
                                
                                foreach ($availability as $avail) {
                                    $avail_by_day[$avail['day_of_week']] = $avail;
                                }
                                
                                foreach ($days as $index => $day):
                                    $day_avail = isset($avail_by_day[$index]) ? $avail_by_day[$index] : null;
                                ?>
                                <tr>
                                    <td><?php echo $day; ?></td>
                                    <td>
                                        <?php if ($day_avail && $day_avail['is_available']): ?>
                                            <?php echo date('h:i A', strtotime($day_avail['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($day_avail['end_time'])); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($day_avail && $day_avail['is_available']): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted mt-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Note: Availability may vary on holidays or special occasions. Contact the artist for details.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>