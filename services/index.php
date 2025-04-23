<?php
// services/index.php - Services listing page

// Set page title
$page_title = "Services";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Get filter parameters
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$location = sanitize_input($conn, $_GET['location'] ?? '');
$min_price = isset($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? intval($_GET['max_price']) : 500000; // Max 500k as per requirement
$sort = sanitize_input($conn, $_GET['sort'] ?? 'price_asc');

// Build SQL query
$sql = "SELECT s.*, u.full_name AS artist_name, u.avatar AS artist_avatar, 
         c.category_name, COALESCE(ap.avg_rating, 0) AS rating
         FROM services s
         JOIN users u ON s.artist_id = u.user_id
         JOIN service_categories c ON s.category_id = c.category_id
         LEFT JOIN artist_profiles ap ON u.user_id = ap.user_id
         WHERE s.is_available = 1 AND u.status = 'active'";

// Apply filters
if ($category_id) {
    $sql .= " AND s.category_id = $category_id";
}

if (!empty($location)) {
    // Location filtering requires address to contain the specified location
    $sql .= " AND (ap.studio_address LIKE '%$location%' OR u.address LIKE '%$location%')";
}

$sql .= " AND s.price BETWEEN $min_price AND $max_price";

// Apply sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY s.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY s.price DESC";
        break;
    case 'rating_desc':
        $sql .= " ORDER BY rating DESC, s.price ASC";
        break;
    case 'newest':
        $sql .= " ORDER BY s.created_at DESC";
        break;
    default:
        $sql .= " ORDER BY s.price ASC";
}

// Get services
$services = get_records($conn, $sql);

// Get all categories for filter
$categories = get_service_categories();

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4">
    <div class="container">
        <h1 class="h3 mb-0">Makeup Services</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Services</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Filter Services</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" id="filter-form">
                        <!-- Categories -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Categories</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" id="category-all" value="" 
                                       <?php echo !$category_id ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="category-all">
                                    All Categories
                                </label>
                            </div>
                            <?php foreach ($categories as $category): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" 
                                       id="category-<?php echo $category['category_id']; ?>" 
                                       value="<?php echo $category['category_id']; ?>" 
                                       <?php echo $category_id == $category['category_id'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="category-<?php echo $category['category_id']; ?>">
                                    <?php echo $category['category_name']; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Location -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Location in Da Nang</h6>
                            <select class="form-select" name="location" id="location">
                                <option value="">All Districts</option>
                                <option value="Hai Chau" <?php echo $location == 'Hai Chau' ? 'selected' : ''; ?>>Hai Chau</option>
                                <option value="Thanh Khe" <?php echo $location == 'Thanh Khe' ? 'selected' : ''; ?>>Thanh Khe</option>
                                <option value="Son Tra" <?php echo $location == 'Son Tra' ? 'selected' : ''; ?>>Son Tra</option>
                                <option value="Ngu Hanh Son" <?php echo $location == 'Ngu Hanh Son' ? 'selected' : ''; ?>>Ngu Hanh Son</option>
                                <option value="Lien Chieu" <?php echo $location == 'Lien Chieu' ? 'selected' : ''; ?>>Lien Chieu</option>
                                <option value="Cam Le" <?php echo $location == 'Cam Le' ? 'selected' : ''; ?>>Cam Le</option>
                                <option value="Hoa Vang" <?php echo $location == 'Hoa Vang' ? 'selected' : ''; ?>>Hoa Vang</option>
                            </select>
                            <div class="form-text text-warning small">
                                <i class="fas fa-info-circle me-1"></i>
                                Currently, we only provide services in Da Nang.
                            </div>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Price Range</h6>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="min_price" class="form-label small">Min (VND)</label>
                                        <input type="number" class="form-control" id="min_price" name="min_price" 
                                               value="<?php echo $min_price; ?>" min="0" max="499000" step="10000">
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="max_price" class="form-label small">Max (VND)</label>
                                        <input type="number" class="form-control" id="max_price" name="max_price" 
                                               value="<?php echo $max_price; ?>" min="1000" max="500000" step="10000">
                                    </div>
                                </div>
                            </div>
                            <div class="text-center price-display">
                                <span id="price-range-display"><?php echo format_currency($min_price); ?> - <?php echo format_currency($max_price); ?></span>
                            </div>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="mb-4">
                            <h6 class="fw-bold mb-3">Sort By</h6>
                            <select class="form-select" name="sort" id="sort">
                                <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>Rating: Highest First</option>
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Services List -->
        <div class="col-lg-9">
            <!-- Search & Sort Bar -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8 mb-3 mb-md-0">
                            <div class="input-group">
                                <input type="text" class="form-control" id="service-search" placeholder="Search services...">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <span class="text-muted">Showing <?php echo count($services); ?> services</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Grid -->
            <div class="row" id="services-container">
                <?php if (count($services) > 0): ?>
                    <?php foreach ($services as $service): ?>
                    <div class="col-md-6 col-lg-4 mb-4 service-card-wrapper">
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
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-5">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h4>No Services Found</h4>
                            <p>We couldn't find any services matching your criteria. Try adjusting your filters.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Price range display update
document.addEventListener('DOMContentLoaded', function() {
    const minPriceInput = document.getElementById('min_price');
    const maxPriceInput = document.getElementById('max_price');
    const priceRangeDisplay = document.getElementById('price-range-display');
    
    function updatePriceDisplay() {
        const minPrice = parseInt(minPriceInput.value);
        const maxPrice = parseInt(maxPriceInput.value);
        
        // Format the prices with comma separators and VND
        const formattedMinPrice = minPrice.toLocaleString('vi-VN') + ' VND';
        const formattedMaxPrice = maxPrice.toLocaleString('vi-VN') + ' VND';
        
        priceRangeDisplay.textContent = `${formattedMinPrice} - ${formattedMaxPrice}`;
    }
    
    minPriceInput.addEventListener('input', function() {
        // Ensure min price doesn't exceed max price
        if (parseInt(minPriceInput.value) > parseInt(maxPriceInput.value)) {
            minPriceInput.value = maxPriceInput.value;
        }
        updatePriceDisplay();
    });
    
    maxPriceInput.addEventListener('input', function() {
        // Ensure max price doesn't go below min price
        if (parseInt(maxPriceInput.value) < parseInt(minPriceInput.value)) {
            maxPriceInput.value = minPriceInput.value;
        }
        updatePriceDisplay();
    });
    
    // Service search functionality
    const searchInput = document.getElementById('service-search');
    const serviceCards = document.querySelectorAll('.service-card-wrapper');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        serviceCards.forEach(function(cardWrapper) {
            const title = cardWrapper.querySelector('.service-title').textContent.toLowerCase();
            const description = cardWrapper.querySelector('.service-description').textContent.toLowerCase();
            const artist = cardWrapper.querySelector('.service-artist-name').textContent.toLowerCase();
            const category = cardWrapper.querySelector('.badge.text-primary').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || 
                description.includes(searchTerm) || 
                artist.includes(searchTerm) || 
                category.includes(searchTerm)) {
                cardWrapper.style.display = '';
            } else {
                cardWrapper.style.display = 'none';
            }
        });
    });
    
    // Auto-submit form when select fields change
    document.getElementById('sort').addEventListener('change', function() {
        document.getElementById('filter-form').submit();
    });
    
    document.getElementById('location').addEventListener('change', function() {
        document.getElementById('filter-form').submit();
    });
    
    // Initialize price display
    updatePriceDisplay();
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>