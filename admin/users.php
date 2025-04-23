<?php
// admin/users.php - Manage users (admins, artists, clients)

// Set page title
$page_title = "User Management";

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
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Get user data
    $user = get_record($conn, "SELECT * FROM users WHERE user_id = $user_id");
    
    if ($user) {
        switch ($action) {
            case 'activate':
                if (update_record($conn, 'users', ['status' => 'active'], "user_id = $user_id")) {
                    // Create notification
                    create_notification(
                        $user_id, 
                        "Account Activated", 
                        "Your account has been activated. You can now login and use BeautyClick services."
                    );
                    set_success_message("User activated successfully!");
                } else {
                    set_error_message("Failed to activate user.");
                }
                break;
                
            case 'deactivate':
                if (update_record($conn, 'users', ['status' => 'inactive'], "user_id = $user_id")) {
                    // Create notification
                    create_notification(
                        $user_id, 
                        "Account Deactivated", 
                        "Your account has been deactivated. Please contact support for more information."
                    );
                    set_success_message("User deactivated successfully!");
                } else {
                    set_error_message("Failed to deactivate user.");
                }
                break;
                
            case 'verify':
                if (update_record($conn, 'artist_profiles', ['is_verified' => 1], "user_id = $user_id")) {
                    // Create notification
                    create_notification(
                        $user_id, 
                        "Account Verified", 
                        "Congratulations! Your artist profile has been verified. This verification badge will be displayed on your profile."
                    );
                    set_success_message("Artist verified successfully!");
                } else {
                    set_error_message("Failed to verify artist.");
                }
                break;
                
            case 'delete':
                // For safety, we won't implement actual deletion
                set_error_message("User deletion is disabled for data integrity. Please deactivate users instead.");
                break;
        }
    } else {
        set_error_message("User not found.");
    }
    
    // Redirect to remove action from URL
    redirect('/beautyclick/admin/users.php');
    exit;
}

// Handle search and filters
$search = sanitize_input($conn, $_GET['search'] ?? '');
$role_filter = isset($_GET['role']) ? intval($_GET['role']) : 0;
$status_filter = sanitize_input($conn, $_GET['status'] ?? '');

// Build SQL query
$sql = "SELECT u.*, r.role_name FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE 1=1";

// Apply filters
if (!empty($search)) {
    $sql .= " AND (u.username LIKE '%$search%' OR u.email LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

if ($role_filter > 0) {
    $sql .= " AND u.role_id = $role_filter";
}

if (!empty($status_filter)) {
    $sql .= " AND u.status = '$status_filter'";
}

// Order by
$sql .= " ORDER BY u.user_id DESC";

// Get users
$users = get_records($conn, $sql);

// Get roles for filter dropdown
$roles = get_records($conn, "SELECT * FROM roles ORDER BY role_id");

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4 border-bottom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-0">User Management</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="/beautyclick/admin/dashboard.php">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="/beautyclick/admin/dashboard.php" class="btn btn-outline-secondary">
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
                    <label for="search" class="form-label">Search Users</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Username, email or name..." value="<?php echo $search; ?>">
                </div>
                <div class="col-md-3">
                    <label for="role" class="form-label">Filter by Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="0">All Roles</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['role_id']; ?>" <?php echo ($role_filter == $role['role_id']) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($role['role_name']); ?>s
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Search
                        </button>
                        <a href="/beautyclick/admin/users.php" class="btn btn-outline-secondary ms-1">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php if (!empty($search)): ?>
                    Search Results for "<?php echo $search; ?>"
                <?php elseif ($role_filter > 0): ?>
                    <?php echo ucfirst(get_record($conn, "SELECT role_name FROM roles WHERE role_id = $role_filter")['role_name']); ?>s
                <?php elseif (!empty($status_filter)): ?>
                    <?php echo ucfirst($status_filter); ?> Users
                <?php else: ?>
                    All Users
                <?php endif; ?>
            </h5>
            <span class="badge bg-secondary"><?php echo count($users); ?> users</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Contact</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?php echo $user['user_id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="/beautyclick/assets/uploads/avatars/<?php echo $user['avatar']; ?>" 
                                             alt="<?php echo $user['full_name']; ?>" 
                                             class="rounded-circle me-2" width="40" height="40">
                                        <div>
                                            <div class="fw-medium"><?php echo $user['full_name']; ?></div>
                                            <div class="small text-muted">@<?php echo $user['username']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $role_badges = [
                                        'admin' => 'danger',
                                        'artist' => 'primary',
                                        'client' => 'success'
                                    ];
                                    $badge_color = $role_badges[$user['role_name']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_color; ?>">
                                        <?php echo ucfirst($user['role_name']); ?>
                                    </span>
                                    
                                    <?php if ($user['role_id'] == 2): // Artist ?>
                                        <?php 
                                        $artist = get_record($conn, "SELECT * FROM artist_profiles WHERE user_id = {$user['user_id']}");
                                        if ($artist && $artist['is_verified']): 
                                        ?>
                                            <span class="badge bg-info ms-1" title="Verified Artist">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['is_student']): ?>
                                        <span class="badge bg-warning text-dark ms-1" title="Student">
                                            <i class="fas fa-user-graduate"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small">
                                        <div><i class="fas fa-envelope me-1 text-muted"></i> <?php echo $user['email']; ?></div>
                                        <div><i class="fas fa-phone me-1 text-muted"></i> <?php echo $user['phone']; ?></div>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $status_badges = [
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'inactive' => 'danger'
                                    ];
                                    $status_badge = $status_badges[$user['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $status_badge; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($user['role_id'] == 2): // Is Artist ?>
                                            <a href="/beautyclick/artists/profile.php?id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-outline-primary" title="View Artist Profile">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            
                                            <?php if ($artist && !$artist['is_verified']): ?>
                                                <a href="/beautyclick/admin/users.php?action=verify&id=<?php echo $user['user_id']; ?>" 
                                                   class="btn btn-info" title="Verify Artist"
                                                   onclick="return confirm('Are you sure you want to verify this artist?');">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php elseif ($user['role_id'] == 3): // Is Client ?>
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#viewPointsModal<?php echo $user['user_id']; ?>"
                                                    title="View Points">
                                                <i class="fas fa-coins"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="/beautyclick/admin/user-details.php?id=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-secondary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($user['status'] === 'pending' || $user['status'] === 'inactive'): ?>
                                            <a href="/beautyclick/admin/users.php?action=activate&id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-success" title="Activate"
                                               onclick="return confirm('Are you sure you want to activate this user?');">
                                                <i class="fas fa-toggle-on"></i>
                                            </a>
                                        <?php elseif ($user['status'] === 'active' && $user['role_id'] != 1): // Don't allow deactivating admins ?>
                                            <a href="/beautyclick/admin/users.php?action=deactivate&id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-danger" title="Deactivate"
                                               onclick="return confirm('Are you sure you want to deactivate this user?');">
                                                <i class="fas fa-toggle-off"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Points Modal for Clients -->
                                    <?php if ($user['role_id'] == 3): ?>
                                        <div class="modal fade" id="viewPointsModal<?php echo $user['user_id']; ?>" tabindex="-1" 
                                             aria-labelledby="pointsModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="pointsModalLabel<?php echo $user['user_id']; ?>">
                                                            Points Details - <?php echo $user['full_name']; ?>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="text-center mb-4">
                                                            <div class="display-4 fw-bold text-primary"><?php echo $user['points']; ?></div>
                                                            <div class="text-muted">Total Points</div>
                                                        </div>
                                                        
                                                        <h6>Points History</h6>
                                                        <?php
                                                        $points_history = get_records($conn, "SELECT b.booking_id, b.booking_date, b.points_earned, b.points_used, s.service_name
                                                                                       FROM bookings b 
                                                                                       JOIN services s ON b.service_id = s.service_id
                                                                                       WHERE b.client_id = {$user['user_id']} AND (b.points_earned > 0 OR b.points_used > 0)
                                                                                       ORDER BY b.booking_date DESC
                                                                                       LIMIT 10");
                                                        
                                                        if (count($points_history) > 0):
                                                        ?>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Date</th>
                                                                            <th>Service</th>
                                                                            <th>Earned</th>
                                                                            <th>Used</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($points_history as $history): ?>
                                                                            <tr>
                                                                                <td><?php echo date('M d, Y', strtotime($history['booking_date'])); ?></td>
                                                                                <td><?php echo $history['service_name']; ?></td>
                                                                                <td>
                                                                                    <?php if ($history['points_earned'] > 0): ?>
                                                                                        <span class="text-success">+<?php echo $history['points_earned']; ?></span>
                                                                                    <?php else: ?>
                                                                                        -
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if ($history['points_used'] > 0): ?>
                                                                                        <span class="text-danger">-<?php echo $history['points_used']; ?></span>
                                                                                    <?php else: ?>
                                                                                        -
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="alert alert-info">No points history found.</div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="mb-0">No users found matching your criteria.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>