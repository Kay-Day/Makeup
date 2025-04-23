<?php
// auth/login.php - User login page

// Set page title
$page_title = "Login";

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

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get login credentials
    $identifier = sanitize_input($conn, $_POST['identifier'] ?? ''); // Email or username
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validate input
    if (empty($identifier) || empty($password)) {
        set_error_message("Please enter both username/email and password.");
    } else {
        // Check if identifier is email or username
        $is_email = is_valid_email($identifier);
        $field = $is_email ? 'email' : 'username';
        
        // Get user from database
        $sql = "SELECT u.*, r.role_name FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.$field = '$identifier'";
        
        $user = get_record($conn, $sql);
        
        if ($user) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['status'] === 'active') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role'] = $user['role_name'];
                    $_SESSION['avatar'] = $user['avatar'];
                    
                    // Set remember me cookie if requested
                    if ($remember_me) {
                        $token = generate_random_string(32);
                        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                        
                        // Store token in database (you would need to create a remember_me_tokens table)
                        // For simplicity, we'll just set the cookie
                        setcookie('remember_token', $token, $expiry, '/');
                    }
                    
                    // Redirect based on user role
                    switch ($user['role_name']) {
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
                } else if ($user['status'] === 'pending') {
                    set_error_message("Your account is pending approval. Please check your email for verification instructions.");
                } else {
                    set_error_message("Your account has been deactivated. Please contact support.");
                }
            } else {
                set_error_message("Invalid password. Please try again.");
            }
        } else {
            set_error_message("No account found with that username or email.");
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
                    <p class="text-muted">Sign in to your account</p>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="mb-3">
                        <label for="identifier" class="form-label">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="identifier" name="identifier" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                            <label class="form-check-label" for="remember_me">
                                Remember me
                            </label>
                        </div>
                        <a href="/beautyclick/auth/forgot-password.php" class="text-decoration-none">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        Sign In
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? 
                            <a href="/beautyclick/auth/register.php" class="text-decoration-none">Register here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- User Types -->
        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                                <i class="fas fa-paintbrush me-1"></i> For Artists
                            </span>
                        </div>
                        <h5 class="card-title">Makeup Artists</h5>
                        <p class="card-text small text-muted">
                            Showcase your makeup skills and connect with clients. Exclusive platform for student makeup artists.
                        </p>
                        <a href="/beautyclick/auth/register.php?type=artist" class="btn btn-sm btn-outline-primary">
                            Register as Artist
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <span class="badge rounded-pill bg-secondary-subtle text-secondary px-3 py-2">
                                <i class="fas fa-user me-1"></i> For Clients
                            </span>
                        </div>
                        <h5 class="card-title">Clients</h5>
                        <p class="card-text small text-muted">
                            Find affordable makeup services from talented student artists for any occasion.
                        </p>
                        <a href="/beautyclick/auth/register.php?type=client" class="btn btn-sm btn-outline-secondary">
                            Register as Client
                        </a>
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