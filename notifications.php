<?php
// notifications.php - User notifications page

// Set page title
$page_title = "Notifications";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in 
if (!is_logged_in()) {
    set_error_message("Please login to view your notifications.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Handle mark as read functionality
$notification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($notification_id > 0) {
    // Mark specific notification as read
    $notification = get_record($conn, "SELECT * FROM notifications WHERE notification_id = $notification_id AND user_id = $user_id");
    
    if ($notification) {
        update_record($conn, 'notifications', ['is_read' => 1], "notification_id = $notification_id");
    }
}

// Mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    update_record($conn, 'notifications', ['is_read' => 1], "user_id = $user_id AND is_read = 0");
    set_success_message("All notifications marked as read.");
    redirect('/beautyclick/notifications.php');
    exit;
}

// Delete notification
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Verify notification belongs to user
    $notification = get_record($conn, "SELECT * FROM notifications WHERE notification_id = $delete_id AND user_id = $user_id");
    
    if ($notification) {
        delete_record($conn, 'notifications', "notification_id = $delete_id");
        set_success_message("Notification deleted successfully.");
    } else {
        set_error_message("Notification not found or you don't have permission to delete it.");
    }
    
    redirect('/beautyclick/notifications.php');
    exit;
}

// Clear all notifications
if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    delete_record($conn, 'notifications', "user_id = $user_id");
    set_success_message("All notifications cleared successfully.");
    redirect('/beautyclick/notifications.php');
    exit;
}

// Apply filters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filter_condition = "";

if ($filter === 'unread') {
    $filter_condition = " AND is_read = 0";
} elseif ($filter === 'read') {
    $filter_condition = " AND is_read = 1";
}

// Get notifications with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total notifications count
$total_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = $user_id$filter_condition";
$total_result = get_record($conn, $total_query);
$total_notifications = $total_result['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get notifications for current page
$notifications_query = "SELECT * FROM notifications 
                      WHERE user_id = $user_id$filter_condition 
                      ORDER BY created_at DESC 
                      LIMIT $offset, $per_page";
$notifications = get_records($conn, $notifications_query);

// Get notification counts
$unread_count = count_records($conn, 'notifications', "user_id = $user_id AND is_read = 0");
$read_count = count_records($conn, 'notifications', "user_id = $user_id AND is_read = 1");

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4">
    <div class="container">
        <h1 class="h3 mb-0">My Notifications</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Notifications</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- Notification Filter & Actions -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="btn-group" role="group">
                        <a href="/beautyclick/notifications.php?filter=all" 
                           class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All (<?php echo $total_notifications; ?>)
                        </a>
                        <a href="/beautyclick/notifications.php?filter=unread" 
                           class="btn <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Unread (<?php echo $unread_count; ?>)
                        </a>
                        <a href="/beautyclick/notifications.php?filter=read" 
                           class="btn <?php echo $filter === 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Read (<?php echo $read_count; ?>)
                        </a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <?php if ($unread_count > 0): ?>
                        <a href="/beautyclick/notifications.php?action=mark_all_read" class="btn btn-outline-success me-2">
                            <i class="fas fa-check-double me-1"></i>Mark All as Read
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($total_notifications > 0): ?>
                        <a href="/beautyclick/notifications.php?action=clear_all" 
                           class="btn btn-outline-danger"
                           onclick="return confirm('Are you sure you want to delete all notifications? This cannot be undone.');">
                            <i class="fas fa-trash me-1"></i>Clear All
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notifications List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-bell text-primary me-2"></i>
                <?php 
                if ($filter === 'unread') {
                    echo 'Unread Notifications';
                } elseif ($filter === 'read') {
                    echo 'Read Notifications';
                } else {
                    echo 'All Notifications';
                }
                ?>
            </h5>
        </div>
        
        <?php if (count($notifications) > 0): ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item p-0">
                        <div class="notification-item p-3 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex">
                                <div class="notification-icon me-3">
                                    <i class="fas fa-bell <?php echo $notification['is_read'] ? 'text-muted' : 'text-primary'; ?>"></i>
                                </div>
                                <div class="notification-content flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 <?php echo $notification['is_read'] ? '' : 'fw-bold'; ?>">
                                            <?php echo $notification['title']; ?>
                                        </h6>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-muted p-0" type="button" 
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if (!$notification['is_read']): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="/beautyclick/notifications.php?id=<?php echo $notification['notification_id']; ?>">
                                                            <i class="fas fa-check me-2"></i>Mark as Read
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                <li>
                                                    <a class="dropdown-item text-danger" 
                                                       href="/beautyclick/notifications.php?action=delete&id=<?php echo $notification['notification_id']; ?>"
                                                       onclick="return confirm('Are you sure you want to delete this notification?');">
                                                        <i class="fas fa-trash me-2"></i>Delete
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <p class="mb-2 <?php echo $notification['is_read'] ? 'text-muted' : ''; ?>">
                                        <?php echo nl2br($notification['message']); ?>
                                    </p>
                                    <div class="notification-time small text-muted">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('M d, Y - h:i A', strtotime($notification['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Notification pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/beautyclick/notifications.php?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            if ($end_page - $start_page < 4) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="/beautyclick/notifications.php?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/beautyclick/notifications.php?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="card-body text-center py-5">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h4>No Notifications</h4>
                <p class="text-muted mb-0">
                    <?php 
                    if ($filter === 'unread') {
                        echo 'You have no unread notifications.';
                    } elseif ($filter === 'read') {
                        echo 'You have no read notifications.';
                    } else {
                        echo 'You don\'t have any notifications yet.';
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    border-radius: 50%;
    font-size: 1.2rem;
}

.notification-item {
    transition: background-color 0.2s ease;
    border-left: 3px solid transparent;
}

.notification-item:hover {
    background-color: rgba(var(--bs-light-rgb), 0.7) !important;
}

.notification-item:not(.bg-light) {
    border-left-color: transparent;
}

.notification-item.bg-light {
    border-left-color: var(--bs-primary);
}
</style>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>