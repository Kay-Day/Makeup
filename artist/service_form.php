<?php
// artist/service_form.php - Handle service creation/update with image upload

// Check action (add/edit)
$action = isset($_GET['action']) ? $_GET['action'] : 'add';
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get service data if editing
$service = [];
if ($action === 'edit' && $service_id > 0) {
    $service = get_record($conn, "SELECT * FROM services WHERE service_id = $service_id AND artist_id = $artist_id");
    if (!$service) {
        set_error_message("Service not found or you don't have permission to edit it.");
        redirect('/beautyclick/artist/services.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $image = $action === 'edit' ? $service['image'] : 'default-service.jpg'; // Keep existing or use default
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        // Log image upload attempt
        error_log("Service image upload attempt: " . print_r($_FILES['image'], true));
        
        $uploaded_image = upload_image($_FILES['image'], 'services');
        if ($uploaded_image) {
            $image = $uploaded_image;
            error_log("New service image filename: $image");
        } else {
            error_log("Service image upload failed");
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
?>

<!-- HTML form with file upload -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?action=' . $action . ($service_id ? '&id=' . $service_id : '')); ?>" method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="category_id" class="form-label">Category *</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($service['category_id']) && $service['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
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
                        img.src = e.target.result;
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>