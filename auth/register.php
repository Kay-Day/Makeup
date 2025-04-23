<?php
// auth/register.php - User registration page

// Set page title
$page_title = "Register";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is already logged in
if (is_logged_in()) {
    // Redirect based on user role
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('/beautyclick/admin/dashboard.php');
            break;
        case 'artist':
            redirect('/beautyclick/artist/dashboard.php');
            break;
        case 'client':
            redirect('/beautyclick/client/dashboard.php');
            break;
        default:
            redirect('/beautyclick/index.php');
    }
    exit;
}

// Determine registration type (artist or client)
$reg_type = $_GET['type'] ?? 'client';
$is_artist = ($reg_type === 'artist');

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize_input($conn, $_POST['username'] ?? '');
    $email = sanitize_input($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitize_input($conn, $_POST['full_name'] ?? '');
    $phone = sanitize_input($conn, $_POST['phone'] ?? '');
    $address = sanitize_input($conn, $_POST['address'] ?? '');
    $is_student = isset($_POST['is_student']) ? 1 : 0;
    $student_id = sanitize_input($conn, $_POST['student_id'] ?? '');
    $school_name = sanitize_input($conn, $_POST['school_name'] ?? '');
    
    // Additional fields for artists
    $studio_address = $is_artist ? sanitize_input($conn, $_POST['studio_address'] ?? '') : '';
    $bio = $is_artist ? sanitize_input($conn, $_POST['bio'] ?? '') : '';
    $skills = $is_artist ? sanitize_input($conn, $_POST['skills'] ?? '') : '';
    
    // Role ID (2 for artist, 3 for client)
    $role_id = $is_artist ? 2 : 3;
    
    // Validate input
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters.";
    } else {
        // Check if username already exists
        $check_username = get_record($conn, "SELECT user_id FROM users WHERE username = '$username'");
        if ($check_username) {
            $errors[] = "Username already exists. Please choose another one.";
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!is_valid_email($email)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $check_email = get_record($conn, "SELECT user_id FROM users WHERE email = '$email'");
        if ($check_email) {
            $errors[] = "Email already exists. Please use another email or login.";
        }
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!is_valid_password($password)) {
        $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, and numbers.";
    }
    
    // Validate password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Validate full name
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    // Validate phone
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!is_valid_phone($phone)) {
        $errors[] = "Please enter a valid Vietnamese phone number.";
    }
    
    // Validate address
    if (empty($address)) {
        $errors[] = "Address is required.";
    } elseif (!is_in_danang($address)) {
        $errors[] = "Currently, we only provide services in Da Nang. Please enter a Da Nang address.";
    }
    
    // Validate student information if checked
    if ($is_student) {
        if (empty($student_id)) {
            $errors[] = "Student ID is required for student verification.";
        }
        
        if (empty($school_name)) {
            $errors[] = "School/University name is required for student verification.";
        }
    }
    
    // Additional validation for artists
    if ($is_artist) {
        // Validate studio address
        if (empty($studio_address)) {
            $errors[] = "Studio address is required for artists.";
        } elseif (!is_in_danang($studio_address)) {
            $errors[] = "Currently, we only provide services in Da Nang. Please enter a Da Nang studio address.";
        }
        
        // Validate bio
        if (empty($bio)) {
            $errors[] = "Bio is required for artists.";
        }
        
        // Students only check for artists
        if (!$is_student) {
            $errors[] = "Only student makeup artists can register. Please check the 'I am a student' box and provide your student information.";
        }
    }
    
    // Upload avatar if provided
    $avatar = 'default.jpg'; // Default avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        // Log avatar upload attempt
        error_log("Registration avatar upload attempt: " . print_r($_FILES['avatar'], true));
        
        $uploaded_avatar = upload_image($_FILES['avatar'], 'avatars');
        if ($uploaded_avatar) {
            $avatar = $uploaded_avatar;
            error_log("New avatar filename from registration: $avatar");
        } else {
            error_log("Registration avatar upload failed");
            // Không báo lỗi cho người dùng, vẫn sử dụng avatar mặc định
        }
    }
    
    // If no errors, register the user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Prepare user data
        $user_data = [
            'role_id' => $role_id,
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password,
            'full_name' => $full_name,
            'phone' => $phone,
            'avatar' => $avatar,
            'address' => $address,
            'is_student' => $is_student,
            'student_id' => $student_id,
            'school_name' => $school_name,
            'status' => 'pending' // Pending approval by admin
        ];
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert user
            $user_id = insert_record($conn, 'users', $user_data);
            
            // If artist, insert artist profile
            if ($is_artist) {
                $artist_data = [
                    'user_id' => $user_id,
                    'studio_address' => $studio_address,
                    'bio' => $bio,
                    'skills' => $skills
                ];
                
                insert_record($conn, 'artist_profiles', $artist_data);
            }
            
            // Create welcome notification
            $title = "Welcome to BeautyClick!";
            $message = "Thank you for registering with BeautyClick. Your account is pending verification. You will be notified once your account is approved.";
            create_notification($user_id, $title, $message);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Set success message
            set_success_message("Registration successful! Your account is pending verification. Please check your email for verification instructions.");
            
            // Redirect to login page
            redirect('/beautyclick/auth/login.php');
            exit;
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            
            // Set error message
            set_error_message("Registration failed: " . $e->getMessage());
        }
    } else {
        // Set error message
        set_error_message(implode("<br>", $errors));
    }
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold text-primary mb-3">
                        <i class="fas fa-palette me-2"></i>BeautyClick
                    </h1>
                    <p class="text-muted">
                        <?php echo $is_artist ? 'Register as a Makeup Artist' : 'Create Your Client Account'; ?>
                    </p>
                </div>
                
                <ul class="nav nav-pills mb-4 justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_artist ? '' : 'active'; ?>" href="?type=client">
                            <i class="fas fa-user me-1"></i> Client
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $is_artist ? 'active' : ''; ?>" href="?type=artist">
                            <i class="fas fa-paintbrush me-1"></i> Makeup Artist
                        </a>
                    </li>
                </ul>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?type=' . $reg_type; ?>" 
                      method="POST" enctype="multipart/form-data">
                    
                    <!-- Basic Information -->
                    <h5 class="border-bottom pb-2 mb-3">Basic Information</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo $_POST['username'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="progress mt-2" style="height: 5px;">
                                <div id="password-strength" class="progress-bar" role="progressbar" 
                                     style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="form-text">
                                Password must be at least 8 characters with uppercase, lowercase, and numbers.
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   placeholder="e.g., 0905123456" value="<?php echo $_POST['phone'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address in Da Nang *</label>
                        <textarea class="form-control" id="address" name="address" rows="2" 
                                  required><?php echo $_POST['address'] ?? ''; ?></textarea>
                        <div class="form-text text-warning">
                            <i class="fas fa-info-circle me-1"></i>
                            Currently, we only provide services in Da Nang.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="avatar" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control custom-file-input" id="avatar" name="avatar" 
                               accept="image/*">
                        <div class="form-text">Max file size: 5MB. Recommended size: 300x300px.</div>
                        <div class="file-preview mt-2" style="display: none;"></div>
                    </div>
                    
                    <!-- Student Information -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_student" name="is_student" 
                                   <?php echo ($is_artist || (isset($_POST['is_student']) && $_POST['is_student'])) ? 'checked' : ''; ?> 
                                   <?php echo $is_artist ? 'required' : ''; ?>>
                            <label class="form-check-label" for="is_student">
                                I am a student
                                <?php if ($is_artist): ?>
                                <span class="text-danger">*</span>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php if ($is_artist): ?>
                        <div class="form-text text-info">
                            <i class="fas fa-info-circle me-1"></i>
                            Only student makeup artists can register on BeautyClick.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="student-info" class="card bg-light p-3 mb-4" 
                         style="display: <?php echo ($is_artist || (isset($_POST['is_student']) && $_POST['is_student'])) ? 'block' : 'none'; ?>;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="student_id" class="form-label">Student ID *</label>
                                <input type="text" class="form-control" id="student_id" name="student_id" 
                                       value="<?php echo $_POST['student_id'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="school_name" class="form-label">School/University *</label>
                                <input type="text" class="form-control" id="school_name" name="school_name" 
                                       value="<?php echo $_POST['school_name'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Your student status will be verified by our team. We may contact you for additional information.
                        </div>
                    </div>
                    
                    <?php if ($is_artist): ?>
                    <!-- Artist Specific Information -->
                    <h5 class="border-bottom pb-2 mb-3">Artist Information</h5>
                    
                    <div class="mb-3">
                        <label for="studio_address" class="form-label">Studio Address in Da Nang (if different from home address) *</label>
                        <textarea class="form-control" id="studio_address" name="studio_address" rows="2" 
                                  required><?php echo $_POST['studio_address'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio/About Yourself *</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3" 
                                  placeholder="Tell clients about yourself, your experience, and your style..." 
                                  required><?php echo $_POST['bio'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="skills" class="form-label">Skills & Specialties</label>
                        <textarea class="form-control" id="skills" name="skills" rows="2" 
                                  placeholder="e.g., Bridal makeup, Natural looks, Special effects..."><?php echo $_POST['skills'] ?? ''; ?></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Terms & Conditions -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="/beautyclick/terms.php" target="_blank">Terms of Service</a> and 
                                <a href="/beautyclick/privacy.php" target="_blank">Privacy Policy</a>.
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        Create Account
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="/beautyclick/auth/login.php" class="text-decoration-none">Login here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle student information fields
document.addEventListener('DOMContentLoaded', function() {
    const isStudentCheckbox = document.getElementById('is_student');
    const studentInfoDiv = document.getElementById('student-info');
    const studentIdInput = document.getElementById('student_id');
    const schoolNameInput = document.getElementById('school_name');
    
    <?php if ($is_artist): ?>
    // For artists, student info is required
    isStudentCheckbox.addEventListener('change', function() {
        if (this.checked) {
            studentInfoDiv.style.display = 'block';
            studentIdInput.required = true;
            schoolNameInput.required = true;
        } else {
            studentInfoDiv.style.display = 'none';
            studentIdInput.required = false;
            schoolNameInput.required = false;
        }
    });
    <?php else: ?>
    // For clients, student info is optional
    isStudentCheckbox.addEventListener('change', function() {
        if (this.checked) {
            studentInfoDiv.style.display = 'block';
        } else {
            studentInfoDiv.style.display = 'none';
            studentIdInput.value = '';
            schoolNameInput.value = '';
        }
    });
    <?php endif; ?>
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>