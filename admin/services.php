<?php
// admin/services.php - Manage services

// Set page title
$page_title = "Service Management";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !user_has_role('admin')) {
    set_error_message("Access denied. You must be an administrator to view this page.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Process actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $service_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Get service data
    $service = get_record($conn, "SELECT s.*, u.user_id as artist_id FROM services s JOIN users u ON s.artist_id = u.user_id WHERE s.service_id = $service_id");
    
    if ($service) {
        switch ($action) {
            case 'enable':
                if (update_record($conn, 'services', ['is_available' => 1], "service_id = $service_id")) {
                    // Create notification
                    create_notification(
                        $service['artist_id'], 
                        "Service Enabled", 
                        "Your service has been enabled by an administrator."
                    );
                    set_success_message("Service enabled successfully!");
                } else {
                    set_error_message("Failed to enable service.");
                }
                break;
                
            case 'disable':
                if (update_record($conn, 'services', ['is_available' => 0], "service_id = $service_id")) {
                    // Create notification
                    create_notification(
                        $service['artist_id'], 
                        "Service Disabled", 
                        "Your service has been disabled by an administrator. Please contact support for more information."
                    );
                    set_success_message("Service disabled successfully!");
                } else {
                    set_error_message("Failed to disable service.");
                }
                break;
                
            case 'delete':
                // For safety, we won't implement actual deletion
                set_error_message("Service deletion is disabled for data integrity. Please disable services instead.");
                break;
        }
    } else {
        set_error_message("Service not found.");
    }
    
    // Redirect to remove action from URL
    redirect('/beautyclick/admin/services.php');
    exit;
}

// Handle search and filters
$search = sanitize_input($conn, $_GET['search'] ?? '');
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$status_filter = isset($_GET['status']) ? intval($_GET['status']) : -1;
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 0;

// Build SQL query
$sql = "SELECT s.*, c.category_name, u.full_name as artist_name, u.avatar as artist_avatar 
        FROM services s
        JOIN service_categories c ON s.category_id = c.category_id
        JOIN users u ON s.artist_id = u.user_id
        WHERE 1=1";

// Apply filters
if (!empty($search)) {
    $sql .= " AND (s.service_name LIKE '%$search%' OR s.description LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

if ($category_filter > 0) {
    $sql .= " AND s.category_id = $category_filter";
}

if ($status_filter !== -1) {
    $sql .= " AND s.is_available = $status_filter";
}

if ($price_min > 0) {
    $sql .= " AND s.price >= $price_min";
}

if ($price_max > 0) {
    $sql .= " AND s.price <= $price_max";
}

// Order by
$sql .= " ORDER BY s.service_id DESC";

// Get services
$services = get_records($conn, $sql);

// Get categories for filter dropdown
$categories = get_records($conn, "SELECT * FROM service_categories ORDER BY category_name");

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-0">Service Management</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="/beautyclick/admin/dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Services</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="/beautyclick/admin/categories.php" class="btn btn-outline-primary">
                    <i class="fas fa-tags me-2"></i>Manage Categories
                </a>
                <a href="/beautyclick/admin/dashboard.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Search & Filter Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Services</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Service name, description or artist..." value="<?php echo $search; ?>">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Filter by Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_filter == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo $category['category_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="-1" <?php echo ($status_filter === -1) ? 'selected' : ''; ?>>All</option>
                        <option value="1" <?php echo ($status_filter === 1) ? 'selected' : ''; ?>>Available</option>
                        <option value="0" <?php echo ($status_filter === 0) ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="price" class="form-label">Price Range (in VND)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" placeholder="Min" name="price_min" value="<?php echo $price_min ?: ''; ?>" min="0">
                        <span class="input-group-text">-</span>
                        <input type="number" class="form-control" placeholder="Max" name="price_max" value="<?php echo $price_max ?: ''; ?>" min="0">
                    </div>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                    <a href="/beautyclick/admin/services.php" class="btn btn-outline-secondary ms-1">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Services Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php if (!empty($search)): ?>
                    Search Results for "<?php echo $search; ?>"
                <?php elseif ($category_filter > 0): ?>
                    <?php echo get_record($conn, "SELECT category_name FROM service_categories WHERE category_id = $category_filter")['category_name']; ?> Services
                <?php elseif ($status_filter === 1): ?>
                    Available Services
                <?php elseif ($status_filter === 0): ?>
                    Disabled Services
                <?php else: ?>
                    All Services
                <?php endif; ?>
            </h5>
            <span class="badge bg-secondary"><?php echo count($services); ?> services</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Service</th>
                        <th>Artist</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($services) > 0): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td>#<?php echo $service['service_id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="/beautyclick/assets/uploads/services/<?php echo !empty($service['image']) ? $service['image'] : 'default-service.jpg'; ?>" 
                                             alt="<?php echo $service['service_name']; ?>" 
                                             class="rounded me-2" width="50" height="50" style="object-fit: cover;">
                                        <div>
                                            <div class="fw-medium"><?php echo $service['service_name']; ?></div>
                                            <div class="small text-muted">
                                                <?php echo substr($service['description'], 0, 50) . (strlen($service['description']) > 50 ? '...' : ''); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="/beautyclick/assets/uploads/avatars/<?php echo $service['artist_avatar']; ?>" 
                                             alt="<?php echo $service['artist_name']; ?>" 
                                             class="rounded-circle me-2" width="30" height="30">
                                        <div class="small"><?php echo $service['artist_name']; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $service['category_name']; ?>
                                    </span>
                                </td>
                                <td class="fw-medium"><?php echo format_currency($service['price']); ?></td>
                                <td><?php echo $service['duration']; ?> min</td>
                                <td>
                                    <?php if ($service['is_available']): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/beautyclick/services/details.php?id=<?php echo $service['service_id']; ?>" 
                                           class="btn btn-outline-primary" title="View Service">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($service['is_available']): ?>
                                            <a href="/beautyclick/admin/services.php?action=disable&id=<?php echo $service['service_id']; ?>" 
                                               class="btn btn-danger" title="Disable Service"
                                               onclick="return confirm('Are you sure you want to disable this service?');">
                                                <i class="fas fa-toggle-off"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="/beautyclick/admin/services.php?action=enable&id=<?php echo $service['service_id']; ?>" 
                                               class="btn btn-success" title="Enable Service"
                                               onclick="return confirm('Are you sure you want to enable this service?');">
                                                <i class="fas fa-toggle-on"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                <p class="mb-0">No services found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Service Statistics -->
    <div class="row mt-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="fas fa-list fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h2 class="mb-0"><?php echo count_records($conn, 'services'); ?></h2>
                            <div class="text-muted">Total Services</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h2 class="mb-0"><?php echo count_records($conn, 'services', 'is_available = 1'); ?></h2>
                            <div class="text-muted">Available Services</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h2 class="mb-0"><?php echo count_records($conn, 'services', 'is_available = 0'); ?></h2>
                            <div class="text-muted">Disabled Services</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="fas fa-tags fa-2x text-info"></i>
                        </div>
                        <div>
                            <h2 class="mb-0"><?php echo count_records($conn, 'service_categories'); ?></h2>
                            <div class="text-muted">Categories</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>