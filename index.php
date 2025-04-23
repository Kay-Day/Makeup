<?php
// index.php - Homepage

// Set page title
$page_title = "Home";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Get service categories for display
$categories = get_service_categories();

// Get popular services (limit to 6)
$popular_services_sql = "SELECT s.*, u.full_name AS artist_name, u.avatar AS artist_avatar, 
                         c.category_name, COALESCE(ap.avg_rating, 0) AS rating
                         FROM services s
                         JOIN users u ON s.artist_id = u.user_id
                         JOIN service_categories c ON s.category_id = c.category_id
                         LEFT JOIN artist_profiles ap ON u.user_id = ap.user_id
                         WHERE s.is_available = 1 AND u.status = 'active'
                         ORDER BY ap.avg_rating DESC, s.price ASC
                         LIMIT 6";
$popular_services = get_records($conn, $popular_services_sql);

// Get top-rated artists (limit to 4)
$top_artists = get_top_artists(4);

// Get recent posts (limit to 3)
$recent_posts = get_recent_posts(3);

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 hero-content">
                <h1 class="hero-title mb-4">Booking makeup dành cho sinh viên tại Đà Nẵng</h1>
                <p class="hero-subtitle mb-4">Đặt dịch vụ trang điểm giá cả phải chăng từ các thợ trang điểm dành cho sinh viên cho mọi dịp.</p>
                <div class="d-flex gap-3">
                    <a href="/beautyclick/services/index.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Tìm dịch vụ
                    </a>
                    <a href="/beautyclick/auth/register.php?type=artist" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-paintbrush me-2"></i>Tham gia với tư cách là thợ trang điểm 
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Search Box Section -->
<section class="search-section mb-5">
    <div class="container">
        <div class="search-box">
            <form action="/beautyclick/services/search.php" method="GET">
                <div class="row align-items-end g-3">
                    <div class="col-lg-4">
                        <label for="keyword" class="form-label fw-semibold">
                            <i class="fas fa-magnifying-glass me-2"></i>Bạn quan tâm dịch vụ gì?
                        </label>
                        <input type="text" class="form-control form-control-lg" id="keyword" name="keyword" placeholder="Search for services...">
                    </div>
                    <div class="col-lg-3">
                        <label for="category" class="form-label fw-semibold">
                            <i class="fas fa-list me-2"></i>Danh mục dịch vụ
                        </label>
                        <select class="form-select form-select-lg" id="category" name="category">
                            <option value="">Tất cả dịch vụ</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo $category['category_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="location" class="form-label fw-semibold">
                            <i class="fas fa-location-dot me-2"></i>Quận
                        </label>
                        <select class="form-select form-select-lg" id="location" name="location">
                            <option value="">Tất cả quận</option>
                            <option value="Hai Chau">Hai Chau</option>
                            <option value="Thanh Khe">Thanh Khe</option>
                            <option value="Son Tra">Son Tra</option>
                            <option value="Ngu Hanh Son">Ngu Hanh Son</option>
                            <option value="Lien Chieu">Lien Chieu</option>
                            <option value="Cam Le">Cam Le</option>
                            <option value="Hoa Vang">Hoa Vang</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Service Categories Section -->
<section class="categories-section mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Service Categories</h2>
            <a href="/beautyclick/services/index.php" class="btn btn-outline-primary">
                View All <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row">
            <?php foreach (array_slice($categories, 0, 6) as $category): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="/beautyclick/services/index.php?category=<?php echo $category['category_id']; ?>" class="text-decoration-none">
                    <div class="category-card">
                        <img src="/beautyclick/assets/images/categories/<?php echo !empty($category['image']) ? $category['image'] : 'default-category.jpg'; ?>" 
                             alt="<?php echo $category['category_name']; ?>" class="category-img">
                        <div class="category-overlay">
                            <h3 class="category-name"><?php echo $category['category_name']; ?></h3>
                            <p class="category-services mb-0">
                                <?php 
                                    $count = count_records($conn, "services s JOIN users u ON s.artist_id = u.user_id", 
                                                           "s.category_id = {$category['category_id']} AND s.is_available = 1 AND u.status = 'active'");
                                    echo $count . ' ' . ($count == 1 ? 'service' : 'services');
                                ?>
                            </p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Popular Services Section -->
<section class="popular-services-section mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Popular Services</h2>
            <a href="/beautyclick/services/index.php" class="btn btn-outline-primary">
                View All <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row">
            <?php foreach ($popular_services as $service): ?>
            <div class="col-lg-4 col-md-6 mb-4">
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
                        <p class="service-description card-text small text-muted mb-2">
                            <?php echo substr($service['description'], 0, 100) . (strlen($service['description']) > 100 ? '...' : ''); ?>
                        </p>
                        
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
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works-section mb-5 py-5 bg-light">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">How It Works</h2>
            <p class="text-muted">Book your makeup service in 3 simple steps</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <span class="display-5 text-primary">
                                <i class="fas fa-search"></i>
                            </span>
                        </div>
                        <h4>1. Find a Service</h4>
                        <p class="text-muted">Browse through our wide range of affordable makeup services offered by talented student artists.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <span class="display-5 text-primary">
                                <i class="fas fa-calendar-alt"></i>
                            </span>
                        </div>
                        <h4>2. Book an Appointment</h4>
                        <p class="text-muted">Choose your preferred date, time, and location for the makeup service.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <span class="display-5 text-primary">
                                <i class="fas fa-smile"></i>
                            </span>
                        </div>
                        <h4>3. Enjoy Your Makeup</h4>
                        <p class="text-muted">Relax and let our skilled student artists transform your look with their creative skills.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Top Artists Section -->
<section class="top-artists-section mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Top Makeup Artists</h2>
            <a href="/beautyclick/artists.php" class="btn btn-outline-primary">
                View All <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row">
            <?php foreach ($top_artists as $artist): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card artist-card h-100">
                    <div class="artist-cover"></div>
                    <!-- <img src="/beautyclick/assets/uploads/avatars/<?php echo $artist['avatar']; ?>" 
                         class="artist-avatar" alt="<?php echo $artist['full_name']; ?>"> -->
                    <div class="card-body artist-info">
                        <h5 class="artist-name"><?php echo $artist['full_name']; ?></h5>
                        
                        <div class="artist-rating mb-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= round($artist['avg_rating'])): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <small class="ms-1">(<?php echo round($artist['avg_rating'], 1); ?>)</small>
                        </div>
                        
                        <div class="artist-stats">
                            <div class="artist-stat">
                                <div class="artist-stat-value"><?php echo $artist['total_bookings']; ?></div>
                                <div class="artist-stat-label">Bookings</div>
                            </div>
                            <div class="artist-stat">
                                <?php 
                                $services_count = count_records($conn, 'services', "artist_id = {$artist['user_id']} AND is_available = 1");
                                ?>
                                <div class="artist-stat-value"><?php echo $services_count; ?></div>
                                <div class="artist-stat-label">Services</div>
                            </div>
                        </div>
                        
                        <a href="/beautyclick/artists/profile.php?id=<?php echo $artist['user_id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                            View Profile
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Recent Posts Section -->
<?php if (!empty($recent_posts)): ?>
<section class="recent-posts-section mb-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title">Recent Posts from Artists</h2>
            <a href="/beautyclick/posts/index.php" class="btn btn-outline-primary">
                View All <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        <div class="row">
            <?php foreach ($recent_posts as $post): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card post-card h-100">
                    <?php if (!empty($post['image'])): ?>
                    <img src="/beautyclick/assets/uploads/posts/<?php echo $post['image']; ?>" 
                         class="card-img-top post-img" alt="<?php echo $post['title']; ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <p class="post-date text-muted">
                            <i class="far fa-calendar-alt me-1"></i>
                            <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                        </p>
                        <h5 class="card-title"><?php echo $post['title']; ?></h5>
                        <p class="card-text text-muted">
                            <?php echo substr(strip_tags($post['content']), 0, 120) . (strlen(strip_tags($post['content'])) > 120 ? '...' : ''); ?>
                        </p>
                        <div class="post-footer">
                            <div class="d-flex align-items-center">
                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $post['avatar']; ?>" 
                                     alt="<?php echo $post['full_name']; ?>" 
                                     class="rounded-circle me-2" width="30" height="30">
                                <span><?php echo $post['full_name']; ?></span>
                            </div>
                            <div class="post-stats">
                                <span class="post-stat">
                                    <i class="far fa-heart"></i> <?php echo $post['likes']; ?>
                                </span>
                                <span class="post-stat">
                                    <?php 
                                    $comments_count = count_records($conn, 'post_comments', "post_id = {$post['post_id']}");
                                    ?>
                                    <i class="far fa-comment"></i> <?php echo $comments_count; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="/beautyclick/posts/details.php?id=<?php echo $post['post_id']; ?>" class="btn btn-outline-primary w-100">
                            Read More
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action -->
<section class="cta-section py-5 bg-primary text-white mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2 class="mb-2">Are you a student makeup artist?</h2>
                <p class="mb-0">Join BeautyClick to showcase your skills and connect with clients. Exclusive platform for student makeup artists in Da Nang.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="/beautyclick/auth/register.php?type=artist" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-paintbrush me-2"></i>Join as Artist
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section mb-5">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title">What Our Clients Say</h2>
            <p class="text-muted">Real reviews from satisfied clients</p>
        </div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text mb-4">"I was amazed by the quality of makeup for such an affordable price! The student artist was professional and talented. Will definitely book again!"</p>
                        <div class="d-flex align-items-center">
                            <img src="/beautyclick/assets/images/testimonials/user1.jpg" alt="Client" class="rounded-circle me-3" width="50" height="50">
                            <div>
                                <h6 class="mb-0">Linh Nguyen</h6>
                                <small class="text-muted">Party Makeup Client</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text mb-4">"BeautyClick made it so easy to find a makeup artist for my graduation photoshoot. The service was excellent and the price was student-friendly!"</p>
                        <div class="d-flex align-items-center">
                            <img src="/beautyclick/assets/images/testimonials/user2.jpg" alt="Client" class="rounded-circle me-3" width="50" height="50">
                            <div>
                                <h6 class="mb-0">Tran Minh</h6>
                                <small class="text-muted">Photoshoot Makeup Client</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="far fa-star"></i>
                        </div>
                        <p class="card-text mb-4">"As a student with a tight budget, I was thrilled to find BeautyClick. The makeup artist was skilled and listened to my preferences. Highly recommend!"</p>
                        <div class="d-flex align-items-center">
                            <img src="/beautyclick/assets/images/testimonials/user3.jpg" alt="Client" class="rounded-circle me-3" width="50" height="50">
                            <div>
                                <h6 class="mb-0">Phuong Anh</h6>
                                <small class="text-muted">Basic Makeup Client</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>