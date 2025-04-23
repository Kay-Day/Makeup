<?php
// admin/cities.php - Manage cities/locations

// Set page title
$page_title = "City Management";

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
    // Add new city
    if (isset($_POST['add_city'])) {
        $city_name = sanitize_input($conn, $_POST['city_name']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $description = sanitize_input($conn, $_POST['description']);
        
        // Validate inputs
        if (empty($city_name)) {
            set_error_message("City name is required.");
        } else {
            // Check if city already exists
            $existing = get_record($conn, "SELECT * FROM cities WHERE city_name = '$city_name'");
            
            if ($existing) {
                set_error_message("This city already exists in the database.");
            } else {
                // Insert new city
                $city_data = [
                    'city_name' => $city_name,
                    'is_available' => $is_available,
                    'description' => $description
                ];
                
                if (insert_record($conn, 'cities', $city_data)) {
                    set_success_message("City added successfully!");
                } else {
                    set_error_message("Failed to add city.");
                }
            }
        }
    }
    
    // Edit city
    if (isset($_POST['edit_city'])) {
        $city_id = intval($_POST['city_id']);
        $city_name = sanitize_input($conn, $_POST['city_name']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $description = sanitize_input($conn, $_POST['description']);
        
        // Validate inputs
        if (empty($city_name)) {
            set_error_message("City name is required.");
        } else {
            // Check if city name already exists (excluding current city)
            $existing = get_record($conn, "SELECT * FROM cities WHERE city_name = '$city_name' AND city_id != $city_id");
            
            if ($existing) {
                set_error_message("A city with this name already exists.");
            } else {
                // Update city
                $update_data = [
                    'city_name' => $city_name,
                    'is_available' => $is_available,
                    'description' => $description
                ];
                
                if (update_record($conn, 'cities', $update_data, "city_id = $city_id")) {
                    set_success_message("City updated successfully!");
                } else {
                    set_error_message("Failed to update city.");
                }
            }
        }
    }
    
    // Redirect to refresh the page after form submission
    redirect('/beautyclick/admin/cities.php');
    exit;
}

// Handle bulk update availability
if (isset($_POST['bulk_update'])) {
    $available_cities = $_POST['available_cities'] ?? [];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // First, set all cities as unavailable
        $update_all = mysqli_query($conn, "UPDATE cities SET is_available = 0");
        
        // Then, set selected cities as available
        if (!empty($available_cities)) {
            $city_ids = implode(',', array_map('intval', $available_cities));
            $update_selected = mysqli_query($conn, "UPDATE cities SET is_available = 1 WHERE city_id IN ($city_ids)");
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        set_success_message("Cities availability updated successfully!");
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        set_error_message("Failed to update cities: " . $e->getMessage());
    }
    
    // Redirect to refresh the page
    redirect('/beautyclick/admin/cities.php');
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $city_id = intval($_GET['id']);
    
    // Check if city has users or bookings
    $user_count = count_records($conn, 'users', "address LIKE '%".get_record($conn, "SELECT city_name FROM cities WHERE city_id = $city_id")['city_name']."%'");
    
    if ($user_count > 0) {
        set_error_message("Cannot delete city because it has users associated with it. Please update user addresses first.");
    } else {
        // Delete the city
        if (delete_record($conn, 'cities', "city_id = $city_id")) {
            set_success_message("City deleted successfully!");
        } else {
            set_error_message("Failed to delete city.");
        }
    }
    
    // Redirect to refresh the page
    redirect('/beautyclick/admin/cities.php');
    exit;
}

// Get all cities
$cities = get_records($conn, "SELECT * FROM cities ORDER BY is_available DESC, city_name ASC");

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-0">City Management</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="/beautyclick/admin/dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cities</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCityModal">
                    <i class="fas fa-plus me-2"></i>Add New City
                </button>
                <a href="/beautyclick/admin/dashboard.php" class="btn btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Availability Notice -->
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <i class="fas fa-info-circle fa-2x"></i>
            </div>
            <div>
                <h5 class="alert-heading">Service Area Management</h5>
                <p class="mb-0">Currently, BeautyClick only provides services in <strong>Da Nang</strong>. Use this page to manage which cities are available for service and which ones are coming soon.</p>
            </div>
        </div>
    </div>
    
    <!-- Cities List -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Manage Service Areas</h5>
        </div>
        <div class="card-body">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="bulk-form">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Available</th>
                                <th>City</th>
                                <th>Description</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($cities) > 0): ?>
                                <?php foreach ($cities as $city): ?>
                                    <tr>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="available_cities[]" 
                                                       value="<?php echo $city['city_id']; ?>" 
                                                       <?php echo $city['is_available'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium"><?php echo $city['city_name']; ?></div>
                                            <span class="badge <?php echo $city['is_available'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                <?php echo $city['is_available'] ? 'Available' : 'Coming Soon'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $city['description']; ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary edit-city" 
                                                        data-bs-toggle="modal" data-bs-target="#editCityModal"
                                                        data-id="<?php echo $city['city_id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($city['city_name']); ?>"
                                                        data-available="<?php echo $city['is_available']; ?>"
                                                        data-description="<?php echo htmlspecialchars($city['description']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="/beautyclick/admin/cities.php?action=delete&id=<?php echo $city['city_id']; ?>" 
                                                   class="btn btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this city?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <i class="fas fa-city fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">No cities found. Add a new city to get started.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($cities) > 0): ?>
                    <div class="text-end mt-3">
                        <button type="submit" name="bulk_update" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Availability Changes
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="fas fa-city fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h2 class="mb-0"><?php echo count($cities); ?></h2>
                            <div class="text-muted">Total Cities</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h2 class="mb-0"><?php echo count_records($conn, 'cities', 'is_available = 1'); ?></h2>
                            <div class="text-muted">Available Cities</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h2 class="mb-0"><?php echo count_records($conn, 'cities', 'is_available = 0'); ?></h2>
                            <div class="text-muted">Coming Soon</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add City Modal -->
<div class="modal fade" id="addCityModal" tabindex="-1" aria-labelledby="addCityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCityModalLabel">Add New City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="city_name" class="form-label">City Name *</label>
                        <input type="text" class="form-control" id="city_name" name="city_name" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available">
                            <label class="form-check-label" for="is_available">
                                Available for service
                            </label>
                        </div>
                        <div class="form-text">Check this if the city is currently available for beauty services.</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Add any details about services in this city..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_city" class="btn btn-primary">Add City</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit City Modal -->
<div class="modal fade" id="editCityModal" tabindex="-1" aria-labelledby="editCityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="city_id" id="edit_city_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCityModalLabel">Edit City</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_city_name" class="form-label">City Name *</label>
                        <input type="text" class="form-control" id="edit_city_name" name="city_name" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_available" name="is_available">
                            <label class="form-check-label" for="edit_is_available">
                                Available for service
                            </label>
                        </div>
                        <div class="form-text">Check this if the city is currently available for beauty services.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_city" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for handling edit city modal -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit city button clicks
    const editButtons = document.querySelectorAll('.edit-city');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const cityId = this.getAttribute('data-id');
            const cityName = this.getAttribute('data-name');
            const isAvailable = this.getAttribute('data-available') === '1';
            const description = this.getAttribute('data-description');
            
            // Populate the edit modal
            document.getElementById('edit_city_id').value = cityId;
            document.getElementById('edit_city_name').value = cityName;
            document.getElementById('edit_is_available').checked = isAvailable;
            document.getElementById('edit_description').value = description;
        });
    });
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>