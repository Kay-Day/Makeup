<?php
// admin/categories.php - Manage service categories

// Set page title
$page_title = "Service Categories";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !user_has_role('admin')) {
    set_error_message("Access denied. You must be an administrator to view this page.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $category_name = sanitize_input($conn, $_POST['category_name']);
        $description = sanitize_input($conn, $_POST['description']);
        
        // Validate inputs
        if (empty($category_name)) {
            set_error_message("Category name is required.");
        } else {
            // Check if category already exists
            $existing = get_record($conn, "SELECT * FROM service_categories WHERE category_name = '$category_name'");
            
            if ($existing) {
                set_error_message("A category with this name already exists.");
            } else {
                // Upload image if provided
                $image = 'default-category.jpg'; // Default image
                
                if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                    $uploaded_image = upload_image($_FILES['image'], 'categories');
                    if ($uploaded_image) {
                        $image = $uploaded_image;
                    }
                }
                
                // Insert new category
                $category_data = [
                    'category_name' => $category_name,
                    'description' => $description,
                    'image' => $image
                ];
                
                if (insert_record($conn, 'service_categories', $category_data)) {
                    set_success_message("Category added successfully!");
                } else {
                    set_error_message("Failed to add category.");
                }
            }
        }
    }
    
    // Edit category
    if (isset($_POST['edit_category'])) {
        $category_id = intval($_POST['category_id']);
        $category_name = sanitize_input($conn, $_POST['category_name']);
        $description = sanitize_input($conn, $_POST['description']);
        
        // Validate inputs
        if (empty($category_name)) {
            set_error_message("Category name is required.");
        } else {
            // Check if category name already exists (excluding current category)
            $existing = get_record($conn, "SELECT * FROM service_categories WHERE category_name = '$category_name' AND category_id != $category_id");
            
            if ($existing) {
                set_error_message("A category with this name already exists.");
            } else {
                // Get current category data
                $current_category = get_record($conn, "SELECT * FROM service_categories WHERE category_id = $category_id");
                
                if (!$current_category) {
                    set_error_message("Category not found.");
                } else {
                    // Prepare update data
                    $update_data = [
                        'category_name' => $category_name,
                        'description' => $description
                    ];
                    
                    // Upload new image if provided
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                        $uploaded_image = upload_image($_FILES['image'], 'categories');
                        if ($uploaded_image) {
                            $update_data['image'] = $uploaded_image;
                        }
                    }
                    
                    // Update category
                    if (update_record($conn, 'service_categories', $update_data, "category_id = $category_id")) {
                        set_success_message("Category updated successfully!");
                    } else {
                        set_error_message("Failed to update category.");
                    }
                }
            }
        }
    }
    
    // Redirect to refresh the page after form submission
    redirect('/beautyclick/admin/categories.php');
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    
    // Check if category has services
    $service_count = count_records($conn, 'services', "category_id = $category_id");
    
    if ($service_count > 0) {
        set_error_message("Cannot delete category because it has $service_count services. Please reassign or delete those services first.");
    } else {
        // Delete the category
        if (delete_record($conn, 'service_categories', "category_id = $category_id")) {
            set_success_message("Category deleted successfully!");
        } else {
            set_error_message("Failed to delete category.");
        }
    }
    
    // Redirect to refresh the page
    redirect('/beautyclick/admin/categories.php');
    exit;
}

// Get all categories
$categories = get_records($conn, "SELECT c.*, 
                                (SELECT COUNT(*) FROM services WHERE category_id = c.category_id) as service_count 
                                FROM service_categories c 
                                ORDER BY c.category_name");

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-0">Service Categories</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="/beautyclick/admin/dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Categories</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus me-2"></i>Add New Category
                </button>
                <a href="/beautyclick/admin/services.php" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-list me-2"></i>Manage Services
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Categories List -->
    <div class="row">
        <?php if (count($categories) > 0): ?>
            <?php foreach ($categories as $category): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-0">
                            <div class="position-relative">
                                <img src="/beautyclick/assets/uploads/categories/<?php echo !empty($category['image']) ? $category['image'] : 'default-category.jpg'; ?>" 
                                     class="card-img-top" alt="<?php echo $category['category_name']; ?>" 
                                     style="height: 180px; object-fit: cover;">
                                <div class="position-absolute bottom-0 start-0 w-100 p-3" 
                                     style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);">
                                    <h5 class="text-white mb-0"><?php echo $category['category_name']; ?></h5>
                                </div>
                            </div>
                            <div class="p-3">
                                <p class="text-muted small mb-3">
                                    <?php echo !empty($category['description']) ? 
                                           substr($category['description'], 0, 100) . (strlen($category['description']) > 100 ? '...' : '') : 
                                           'No description available.'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-list me-1"></i><?php echo $category['service_count']; ?> Services
                                    </span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary edit-category" 
                                                data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                data-id="<?php echo $category['category_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                                data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                data-image="<?php echo $category['image']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($category['service_count'] == 0): ?>
                                            <a href="/beautyclick/admin/categories.php?action=delete&id=<?php echo $category['category_id']; ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this category?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-danger" disabled title="Cannot delete categories with services">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-tags fa-3x mb-3"></i>
                    <h4>No Categories Found</h4>
                    <p class="mb-0">No service categories have been added yet. Click the "Add New Category" button to create one.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Category Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Recommended size: 800x400px. Max file size: 5MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <div class="text-center mb-2">
                            <img id="edit_image_preview" src="" alt="Category Image" 
                                 class="img-fluid rounded" style="max-height: 150px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">Upload New Image</label>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                        <div class="form-text">Leave empty to keep the current image.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for handling edit category modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit category button clicks
    const editButtons = document.querySelectorAll('.edit-category');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-id');
            const categoryName = this.getAttribute('data-name');
            const description = this.getAttribute('data-description');
            const image = this.getAttribute('data-image');
            
            // Populate the edit modal
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_category_name').value = categoryName;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_image_preview').src = '/beautyclick/assets/uploads/categories/' + 
                                                                (image || 'default-category.jpg');
        });
    });
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>