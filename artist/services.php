<?php
// artist/services.php - Manage artist services

// Set page title
$page_title = "Manage Services";

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

// Handle action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Process actions
if ($action === 'delete' && $service_id > 0) {
    // Delete service
    if (delete_record($conn, 'services', "service_id = $service_id AND artist_id = $artist_id")) {
        set_success_message("Service deleted successfully!");
    } else {
        set_error_message("Failed to delete service.");
    }
    redirect('/beautyclick/artist/services.php');
    exit;
}

if ($action === 'toggle' && $service_id > 0) {
    // Toggle service availability
    $service = get_record($conn, "SELECT is_available FROM services WHERE service_id = $service_id AND artist_id = $artist_id");
    if ($service) {
        $new_status = $service['is_available'] ? 0 : 1;
        if (update_record($conn, 'services', ['is_available' => $new_status], "service_id = $service_id AND artist_id = $artist_id")) {
            set_success_message("Service status updated successfully!");
        } else {
            set_error_message("Failed to update service status.");
        }
    }
    redirect('/beautyclick/artist/services.php');
    exit;
}

// Handle form submission for adding/editing services
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    // Get form data
    $category_id = intval($_POST['category_id'] ?? 0);
    $service_name = sanitize_input($conn, $_POST['service_name'] ?? '');
    $description = sanitize_input($conn, $_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration = intval($_POST['duration'] ?? 0);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if ($category_id <= 0) {
        $errors[] = "Please select a valid category.";
    }
    
    if (empty($service_name)) {
        $errors[] = "Service name is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Service description is required.";
    }
    
    if ($price <= 0 || $price > 500000) {
        $errors[] = "Price must be between 1 and 500,000 VND.";
    }
    
    if ($duration <= 0) {
        $errors[] = "Duration must be greater than 0 minutes.";
    }
    
    // Handle image upload
    $image = '';
    if ($action === 'edit' && $service_id > 0) {
        $existing_service = get_record($conn, "SELECT image FROM services WHERE service_id = $service_id AND artist_id = $artist_id");
        $image = $existing_service ? $existing_service['image'] : 'default-service.jpg';
    } else {
        $image = 'default-service.jpg';
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploaded_image = upload_image($_FILES['image'], 'services');
        if ($uploaded_image) {
            $image = $uploaded_image;
        } else {
            $errors[] = "Failed to upload service image. Please try again.";
        }
    }
    
    // If no errors, process the service
    if (empty($errors)) {
        // Prepare service data
        $service_data = [
            'category_id' => $category_id,
            'service_name' => $service_name,
            'description' => $description,
            'price' => $price,
            'duration' => $duration,
            'image' => $image,
            'is_available' => $is_available
        ];
        
        if ($action === 'add') {
            // Add artist_id for new services
            $service_data['artist_id'] = $artist_id;
            
            // Insert new service
            if (insert_record($conn, 'services', $service_data)) {
                set_success_message("Service added successfully!");
                redirect('/beautyclick/artist/services.php');
                exit;
            } else {
                set_error_message("Failed to add service. Please try again.");
            }
        } else {
            // Update existing service
            if (update_record($conn, 'services', $service_data, "service_id = $service_id AND artist_id = $artist_id")) {
                set_success_message("Service updated successfully!");
                redirect('/beautyclick/artist/services.php');
                exit;
            } else {
                set_error_message("Failed to update service. Please try again.");
            }
        }
    } else {
        set_error_message(implode("<br>", $errors));
    }
}

// Get all categories for dropdown
$categories = get_records($conn, "SELECT * FROM service_categories ORDER BY category_name");

// Get service for editing if needed
$service = [];
if ($action === 'edit' && $service_id > 0) {
    $service = get_record($conn, "SELECT * FROM services WHERE service_id = $service_id AND artist_id = $artist_id");
    if (!$service) {
        set_error_message("Service not found or you don't have permission to edit it.");
        redirect('/beautyclick/artist/services.php');
        exit;
    }
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-list me-2"></i>
                    <?php echo $action === 'add' ? 'Add New Service' : ($action === 'edit' ? 'Edit Service' : 'My Services'); ?>
                </h2>
                <?php if ($action !== 'add' && $action !== 'edit'): ?>
                <a href="/beautyclick/artist/services.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Service
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?action=' . $action . ($service_id ? '&id=' . $service_id : '')); ?>" 
                          method="POST" enctype="multipart/form-data">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo (isset($service['category_id']) && $service['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['category_name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="service_name" class="form-label">Service Name *</label>
                                <input type="text" class="form-control" id="service_name" name="service_name" 
                                       value="<?php echo isset($service['service_name']) ? $service['service_name'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($service['description']) ? $service['description'] : ''; ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (VND) *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?php echo isset($service['price']) ? $service['price'] : ''; ?>" 
                                           min="1" max="500000" required>
                                    <span class="input-group-text">VND</span>
                                </div>
                                <div class="form-text text-warning">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Maximum price: 500,000 VND
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="duration" class="form-label">Duration (minutes) *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="duration" name="duration" 
                                           value="<?php echo isset($service['duration']) ? $service['duration'] : ''; ?>" min="1" required>
                                    <span class="input-group-text">minutes</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Service Image</label>
                            <input type="file" class="form-control custom-file-input" id="image" name="image" accept="image/*">
                            <div class="form-text">Max file size: 5MB. Recommended size: 800x600px.</div>
                            <?php if (isset($service['image']) && $service['image'] != 'default-service.jpg'): ?>
                            <div class="file-preview mt-2">
                                <img src="/beautyclick/assets/uploads/services/<?php echo $service['image']; ?>" 
                                     class="img-thumbnail" alt="Service Image" style="max-width: 200px;">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_available" name="is_available" 
                                       <?php echo (!isset($service['is_available']) || $service['is_available']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_available">
                                    Available for booking
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <a href="/beautyclick/artist/services.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?php echo $action === 'add' ? 'Add Service' : 'Update Service'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Services List -->
    <div class="row">
        <?php
        $services = get_records($conn, "SELECT s.*, c.category_name 
                                      FROM services s
                                      JOIN service_categories c ON s.category_id = c.category_id
                                      WHERE s.artist_id = $artist_id
                                      ORDER BY s.created_at DESC");
        
        if (count($services) > 0):
            foreach ($services as $service):
        ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card service-card h-100">
                <img src="/beautyclick/assets/uploads/services/<?php echo !empty($service['image']) ? $service['image'] : 'default-service.jpg'; ?>" 
                     class="card-img-top service-img" alt="<?php echo $service['service_name']; ?>">
                <div class="service-price"><?php echo format_currency($service['price']); ?></div>
                <div class="card-body">
                    <h5 class="service-title card-title"><?php echo $service['service_name']; ?></h5>
                    <p class="text-muted small mb-2">
                        <i class="fas fa-tag me-1"></i><?php echo $service['category_name']; ?>
                        <i class="fas fa-clock ms-3 me-1"></i><?php echo $service['duration']; ?> min
                    </p>
                    <p class="service-description card-text small text-muted">
                        <?php echo substr($service['description'], 0, 100) . (strlen($service['description']) > 100 ? '...' : ''); ?>
                    </p>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge <?php echo $service['is_available'] ? 'bg-success' : 'bg-secondary'; ?>">
                            <?php echo $service['is_available'] ? 'Available' : 'Unavailable'; ?>
                        </span>
                        <div class="btn-group">
                            <a href="/beautyclick/artist/services.php?action=edit&id=<?php echo $service['service_id']; ?>" 
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="/beautyclick/artist/services.php?action=toggle&id=<?php echo $service['service_id']; ?>" 
                               class="btn btn-sm btn-outline-warning" title="Toggle Availability">
                                <i class="fas fa-power-off"></i>
                            </a>
                            <a href="/beautyclick/artist/services.php?action=delete&id=<?php echo $service['service_id']; ?>" 
                               class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this service?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
            endforeach;
        else:
        ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h4>No Services Yet</h4>
                <p class="mb-3">You haven't added any services to your profile. Start adding services to showcase your expertise!</p>
                <a href="/beautyclick/artist/services.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Your First Service
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Preview uploaded image before form submission
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('image');
    const filePreview = document.querySelector('.file-preview');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (!filePreview) {
                        // Create preview container if it doesn't exist
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'file-preview mt-2';
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail';
                        img.alt = 'Service Image Preview';
                        img.style.maxWidth = '200px';
                        previewDiv.appendChild(img);
                        fileInput.parentNode.appendChild(previewDiv);
                    } else {
                        // Update existing preview
                        const img = filePreview.querySelector('img');
                        if (img) {
                            img.src = e.target.result;
                        } else {
                            const newImg = document.createElement('img');
                            newImg.src = e.target.result;
                            newImg.className = 'img-thumbnail';
                            newImg.alt = 'Service Image Preview';
                            newImg.style.maxWidth = '200px';
                            filePreview.appendChild(newImg);
                        }
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>