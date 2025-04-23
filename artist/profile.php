<?php
// artist/profile.php - Artist profile management page

// Set page title
$page_title = "Edit Profile";

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

// Get artist data
$artist = get_user_data($artist_id);

// Get artist profile
$artist_profile = get_artist_profile($artist_id);
if (!$artist_profile) {
    set_error_message("Artist profile not found.");
    redirect('/beautyclick/index.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = sanitize_input($conn, $_POST['full_name'] ?? '');
    $phone = sanitize_input($conn, $_POST['phone'] ?? '');
    $address = sanitize_input($conn, $_POST['address'] ?? '');
    $studio_address = sanitize_input($conn, $_POST['studio_address'] ?? '');
    $bio = sanitize_input($conn, $_POST['bio'] ?? '');
    $skills = sanitize_input($conn, $_POST['skills'] ?? '');
    $experience = sanitize_input($conn, $_POST['experience'] ?? '');
    $portfolio_links = sanitize_input($conn, $_POST['portfolio_links'] ?? '');
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
    
    if (empty($studio_address)) {
        $errors[] = "Studio address is required.";
    } elseif (!is_in_danang($studio_address)) {
        $errors[] = "Currently, we only provide services in Da Nang. Please enter a Da Nang studio address.";
    }
    
    if (empty($bio)) {
        $errors[] = "Bio is required.";
    }
    
    // Check if changing password
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        // Verify current password
        if (!password_verify($current_password, $artist['password'])) {
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
    $avatar = $artist['avatar']; // Default to current avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        // Log avatar upload attempt
        error_log("Artist avatar upload attempt: " . print_r($_FILES['avatar'], true));
        
        $uploaded_avatar = upload_image($_FILES['avatar'], 'avatars');
        if ($uploaded_avatar) {
            $avatar = $uploaded_avatar;
            error_log("New artist avatar filename: $avatar");
        } else {
            error_log("Artist avatar upload failed");
            $errors[] = "Failed to upload avatar. Please try again.";
        }
    }
    
    // If no errors, update the profile
    if (empty($errors)) {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
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
            update_record($conn, 'users', $user_data, "user_id = $artist_id");
            
            // Prepare artist profile data
            $profile_data = [
                'studio_address' => $studio_address,
                'bio' => $bio,
                'skills' => $skills,
                'experience' => $experience,
                'portfolio_links' => $portfolio_links
            ];
            
            // Update artist profile
            update_record($conn, 'artist_profiles', $profile_data, "user_id = $artist_id");
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Update session data
            $_SESSION['full_name'] = $full_name;
            $_SESSION['avatar'] = $avatar;
            
            set_success_message("Profile updated successfully!");
            redirect('/beautyclick/artist/profile.php');
            exit;
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            
            set_error_message("Profile update failed: " . $e->getMessage());
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
                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $artist['avatar']; ?>" 
                         alt="<?php echo $artist['full_name']; ?>" 
                         class="rounded-circle mb-3" width="150" height="150">
                    <h5 class="mb-1"><?php echo $artist['full_name']; ?></h5>
                    <p class="text-muted mb-3">Makeup Artist</p>
                    
                    <div class="d-flex justify-content-center mb-3">
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo count_records($conn, 'services', "artist_id = $artist_id AND is_available = 1"); ?></h6>
                            <small class="text-muted">Services</small>
                        </div>
                        <div class="px-3 border-end">
                            <h6 class="mb-0"><?php echo $artist_profile['total_bookings']; ?></h6>
                            <small class="text-muted">Bookings</small>
                        </div>
                        <div class="px-3">
                            <h6 class="mb-0"><?php echo number_format($artist_profile['avg_rating'], 1); ?></h6>
                            <small class="text-muted">Rating</small>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center">
                        <a href="/beautyclick/artist/dashboard.php" class="btn btn-outline-primary btn-sm me-2">
                            <i class="fas fa-arrow-left me-1"></i>Dashboard
                        </a>
                        <a href="/beautyclick/artists/profile.php?id=<?php echo $artist_id; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-eye me-1"></i>Public View
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Availability Section -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Availability</h5>
                    <a href="/beautyclick/artist/availability.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
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
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $day; ?></strong>
                                </div>
                                <div>
                                    <?php if ($day_avail && $day_avail['is_available']): ?>
                                        <?php echo date('h:i A', strtotime($day_avail['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($day_avail['end_time'])); ?>
                                    <?php else: ?>
                                        <span class="text-secondary">Closed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                        <h5 class="border-bottom pb-2 mb-3">Basic Information</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo $artist['full_name']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo $artist['phone']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Home Address in Da Nang *</label>
                            <textarea class="form-control" id="address" name="address" rows="2" 
                                      required><?php echo $artist['address']; ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="avatar" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control custom-file-input" id="avatar" name="avatar" 
                                   accept="image/*">
                            <div class="form-text">Max file size: 5MB. Recommended size: 300x300px.</div>
                            <div class="file-preview mt-2">
                                <img src="/beautyclick/assets/uploads/avatars/<?php echo $artist['avatar']; ?>" 
                                     class="img-thumbnail" alt="Profile Picture" style="max-width: 150px;">
                            </div>
                        </div>
                        
                        <h5 class="border-bottom pb-2 mb-3">Artist Information</h5>
                        <div class="mb-3">
                            <label for="studio_address" class="form-label">Studio Address in Da Nang *</label>
                            <textarea class="form-control" id="studio_address" name="studio_address" rows="2" 
                                      required><?php echo $artist_profile['studio_address']; ?></textarea>
                            <div class="form-text text-warning">
                                <i class="fas fa-info-circle me-1"></i>
                                Currently, we only provide services in Da Nang.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio/About Yourself *</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" 
                                      required><?php echo $artist_profile['bio']; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="skills" class="form-label">Skills & Specialties</label>
                            <textarea class="form-control" id="skills" name="skills" rows="2"><?php echo $artist_profile['skills']; ?></textarea>
                            <div class="form-text">List your makeup skills, specialties, and techniques separated by commas.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="experience" class="form-label">Experience</label>
                            <textarea class="form-control" id="experience" name="experience" rows="2"><?php echo $artist_profile['experience']; ?></textarea>
                            <div class="form-text">Share your makeup experience, education, and certifications.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="portfolio_links" class="form-label">Portfolio Links</label>
                            <textarea class="form-control" id="portfolio_links" name="portfolio_links" rows="2"><?php echo $artist_profile['portfolio_links']; ?></textarea>
                            <div class="form-text">Add links to your social media profiles, Instagram, etc. (one per line)</div>
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