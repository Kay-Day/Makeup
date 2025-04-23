<?php
// client/profile.php - Client profile management page

// Set page title
$page_title = "Edit Profile";

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

// Get client data
$client = get_user_data($client_id);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = sanitize_input($conn, $_POST['full_name'] ?? '');
    $phone = sanitize_input($conn, $_POST['phone'] ?? '');
    $address = sanitize_input($conn, $_POST['address'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!is_valid_phone($phone)) {
        $errors[] = "Please enter a valid Vietnamese phone number.";
    }
    
    if (empty($address)) {
        $errors[] = "Address is required.";
    } elseif (!is_in_danang($address)) {
        $errors[] = "Currently, we only provide services in Da Nang. Please enter a Da Nang address.";
    }
    
    // Check if changing password
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        // Verify current password
        if (!password_verify($current_password, $client['password'])) {
            $errors[] = "Current password is incorrect.";
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = "New password is required.";
        } elseif (!is_valid_password($new_password)) {
            $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, and numbers.";
        }
        
        // Validate password confirmation
        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }
    
    // Upload avatar if provided
    $avatar = $client['avatar']; // Default to current avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        // Log avatar upload attempt
        error_log("Avatar upload attempt: " . print_r($_FILES['avatar'], true));
        
        $uploaded_avatar = upload_image($_FILES['avatar'], 'avatars');
        if ($uploaded_avatar) {
            $avatar = $uploaded_avatar;
            error_log("New avatar filename: $avatar");
        } else {
            error_log("Avatar upload failed");
            $errors[] = "Failed to upload avatar. Please try again.";
        }
    }
    
    // If no errors, update the profile
    if (empty($errors)) {
        // Prepare user data
        $user_data = [
            'full_name' => $full_name,
            'phone' => $phone,
            'address' => $address,
            'avatar' => $avatar
        ];
        
        // Add new password if changing
        if (!empty($new_password)) {
            $user_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        
        // Update user
        if (update_record($conn, 'users', $user_data, "user_id = $client_id")) {
            // Update session data
            $_SESSION['full_name'] = $full_name;
            $_SESSION['avatar'] = $avatar;
            
            set_success_message("Profile updated successfully!");
            redirect('/beautyclick/client/profile.php');
            exit;
        } else {
            set_error_message("Profile update failed. Please try again.");
        }
    } else {
        set_error_message(implode("<br>", $errors));
    }
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $client['avatar']; ?>" 
                         alt="<?php echo $client['full_name']; ?>" 
                         class="rounded-circle mb-3" width="150" height="150">
                    <h5 class="mb-1"><?php echo $client['full_name']; ?></h5>
                    <p class="text-muted mb-3">Client</p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo count_records($conn, 'bookings', "client_id = $client_id"); ?></h6>
                            <small class="text-muted">Bookings</small>
                        </div>
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo count_records($conn, 'bookings', "client_id = $client_id AND status_id = 4"); ?></h6>
                            <small class="text-muted">Completed</small>
                        </div>
                        <div class="px-3">
                            <h6 class="mb-0"><?php echo $client['points']; ?></h6>
                            <small class="text-muted">Points</small>
                        </div>
                    </div>
                    
                    <a href="/beautyclick/client/dashboard.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <!-- Debug information -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div class="alert alert-info">
                        <?php
                        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/assets/uploads/avatars/';
                        echo "Upload directory: $upload_dir<br>";
                        echo "Directory exists: " . (file_exists($upload_dir) ? 'Yes' : 'No') . "<br>";
                        echo "Is writable: " . (is_writable($upload_dir) ? 'Yes' : 'No') . "<br>";
                        ?>
                    </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo $client['full_name']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo $client['phone']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address in Da Nang *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" 
                                      required><?php echo $client['address']; ?></textarea>
                            <div class="form-text text-warning">
                                <i class="fas fa-info-circle me-1"></i>
                                Currently, we only provide services in Da Nang.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="avatar" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control custom-file-input" id="avatar" name="avatar" 
                                   accept="image/*">
                            <div class="form-text">Max file size: 5MB. Recommended size: 300x300px.</div>
                            <div class="file-preview mt-2">
                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $client['avatar']; ?>" 
                                     class="img-thumbnail" alt="Profile Picture" style="max-width: 150px;">
                            </div>
                        </div>
                        
                        <h5 class="border-bottom pb-2 mb-3">Change Password</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">
                                    Password must be at least 8 characters with uppercase, lowercase, and numbers.
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview uploaded image before form submission
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('avatar');
    const filePreview = document.querySelector('.file-preview img');
    
    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    filePreview.src = e.target.result;
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