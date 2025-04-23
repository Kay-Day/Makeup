<?php
// auth/forgot-password.php - Forgot password page

// Set page title
$page_title = "Forgot Password";

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($conn, $_POST['email'] ?? '');
    
    // Validate email
    if (empty($email)) {
        set_error_message("Please enter your email address.");
    } elseif (!is_valid_email($email)) {
        set_error_message("Please enter a valid email address.");
    } else {
        // Check if email exists in the database
        $sql = "SELECT user_id, username, full_name FROM users WHERE email = '$email' AND status = 'active'";
        $user = get_record($conn, $sql);
        
        if ($user) {
            // Generate reset token
            $token = generate_random_string(32);
            $expiry = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours from now
            
            // Store token in database
            // Note: In a real application, you would create a password_resets table
            // For this demo, we'll just set a success message
            
            // Here you would also send an email with the reset link
            // For example: /beautyclick/auth/reset-password.php?token=$token
            
            set_success_message("Password reset instructions have been sent to your email. Please check your inbox.");
            redirect('/beautyclick/auth/login.php');
            exit;
        } else {
            set_error_message("No active account found with that email address.");
        }
    }
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <h1 class="h3 fw-bold text-primary mb-3">
                        <i class="fas fa-palette me-2"></i>BeautyClick
                    </h1>
                    <p class="text-muted">Reset Your Password</p>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="form-text">
                            Enter the email address associated with your account and we'll send you instructions to reset your password.
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        Send Reset Instructions
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">
                            <a href="/beautyclick/auth/login.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i> Back to Login
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>