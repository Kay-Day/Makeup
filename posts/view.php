<?php
// posts/view.php - Display post details and comments

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Get post ID from URL
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if post ID is valid
if ($post_id <= 0) {
    set_error_message("Invalid post ID.");
    redirect('/beautyclick/posts/index.php');
    exit;
}

// Get post details
$sql = "SELECT p.*, u.full_name AS artist_name, u.avatar AS artist_avatar, 
        ap.bio AS artist_bio, ap.avg_rating
        FROM posts p 
        JOIN users u ON p.artist_id = u.user_id
        JOIN artist_profiles ap ON u.user_id = ap.user_id
        WHERE p.post_id = $post_id";

$post = get_record($conn, $sql);

// Check if post exists
if (!$post) {
    set_error_message("Post not found.");
    redirect('/beautyclick/posts/index.php');
    exit;
}

// Set page title
$page_title = $post['title'];

// Get comments for this post
$comments_sql = "SELECT c.*, u.full_name, u.avatar, u.role_id, r.role_name 
                FROM post_comments c
                JOIN users u ON c.user_id = u.user_id
                JOIN roles r ON u.role_id = r.role_id
                WHERE c.post_id = $post_id
                ORDER BY c.created_at ASC";
$comments = get_records($conn, $comments_sql);

// Get like count and check if user has liked
$like_count = count_records($conn, 'post_likes', "post_id = $post_id");
$user_liked = false;

if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $user_liked = get_record($conn, "SELECT like_id FROM post_likes WHERE post_id = $post_id AND user_id = $user_id") ? true : false;
}

// Process comment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    // Check if user is logged in
    if (!is_logged_in()) {
        set_error_message("Please login to comment on this post.");
        redirect('/beautyclick/auth/login.php');
        exit;
    }
    
    // Get form data
    $comment = sanitize_input($conn, $_POST['comment'] ?? '');
    
    // Validate input
    if (empty($comment)) {
        set_error_message("Comment cannot be empty.");
    } else {
        // Prepare comment data
        $comment_data = [
            'post_id' => $post_id,
            'user_id' => $_SESSION['user_id'],
            'comment' => $comment
        ];
        
        // Insert comment
        $result = insert_record($conn, 'post_comments', $comment_data);
        
        if ($result) {
            // Create notification for the artist
            if ($_SESSION['user_id'] != $post['artist_id']) {
                $notification_title = "New Comment on Your Post";
                $notification_message = "{$_SESSION['full_name']} commented on your post: \"{$post['title']}\"";
                create_notification($post['artist_id'], $notification_title, $notification_message);
            }
            
            set_success_message("Comment added successfully.");
            redirect('/beautyclick/posts/view.php?id=' . $post_id . '#comments');
            exit;
        } else {
            set_error_message("Failed to add comment. Please try again.");
        }
    }
}

// Process like/unlike action
if (isset($_GET['action']) && ($_GET['action'] == 'like' || $_GET['action'] == 'unlike') && is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    
    if ($_GET['action'] == 'like') {
        // Check if already liked
        $existing_like = get_record($conn, "SELECT like_id FROM post_likes WHERE post_id = $post_id AND user_id = $user_id");
        
        if (!$existing_like) {
            // Insert like
            $like_data = [
                'post_id' => $post_id,
                'user_id' => $user_id
            ];
            
            insert_record($conn, 'post_likes', $like_data);
            
            // Update post like count
            execute_query($conn, "UPDATE posts SET likes = likes + 1 WHERE post_id = $post_id");
            
            // Create notification for the artist
            if ($user_id != $post['artist_id']) {
                $notification_title = "New Like on Your Post";
                $notification_message = "{$_SESSION['full_name']} liked your post: \"{$post['title']}\"";
                create_notification($post['artist_id'], $notification_title, $notification_message);
            }
        }
    } else { // unlike
        // Delete like
        delete_record($conn, 'post_likes', "post_id = $post_id AND user_id = $user_id");
        
        // Update post like count
        execute_query($conn, "UPDATE posts SET likes = GREATEST(likes - 1, 0) WHERE post_id = $post_id");
    }
    
    // Redirect to remove the action from URL
    redirect('/beautyclick/posts/view.php?id=' . $post_id);
    exit;
}

// Get related posts from the same artist
$related_posts_sql = "SELECT p.*, 
                       COUNT(DISTINCT c.comment_id) AS comment_count
                       FROM posts p
                       LEFT JOIN post_comments c ON p.post_id = c.post_id
                       WHERE p.artist_id = {$post['artist_id']} 
                       AND p.post_id != $post_id
                       GROUP BY p.post_id
                       ORDER BY p.created_at DESC
                       LIMIT 3";
$related_posts = get_records($conn, $related_posts_sql);

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4">
    <div class="container">
        <h1 class="h3 mb-0"><?php echo $post['title']; ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="/beautyclick/posts/index.php">Beauty Stories</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $post['title']; ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <!-- Post Author & Date -->
                    <div class="d-flex align-items-center mb-4">
                        <img src="/beautyclick/assets/uploads/avatars/<?php echo $post['artist_avatar']; ?>" 
                             class="rounded-circle me-3" width="60" height="60" alt="<?php echo $post['artist_name']; ?>">
                        <div>
                            <h5 class="mb-1">
                                <a href="/beautyclick/artists/profile.php?id=<?php echo $post['artist_id']; ?>" 
                                   class="text-decoration-none"><?php echo $post['artist_name']; ?></a>
                            </h5>
                            <div class="artist-info d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-star text-warning me-1"></i>
                                    <span><?php echo number_format($post['avg_rating'], 1); ?></span>
                                </div>
                                <div>
                                    <i class="fas fa-calendar-alt text-secondary me-1"></i>
                                    <span><?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Post Title -->
                    <h2 class="post-title mb-4"><?php echo $post['title']; ?></h2>
                    
                    <!-- Post Image if exists -->
                    <?php if (!empty($post['image'])): ?>
                    <div class="post-image-container mb-4">
                        <img src="/beautyclick/assets/uploads/posts/<?php echo $post['image']; ?>" 
                             class="img-fluid rounded" alt="<?php echo $post['title']; ?>">
                    </div>
                    <?php endif; ?>
                    
                    <!-- Post Content -->
                    <div class="post-content mb-4">
                        <?php echo nl2br($post['content']); ?>
                    </div>
                    
                    <!-- Post Meta & Actions -->
                    <div class="post-meta border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="post-actions">
                                <!-- Like Button -->
                                <?php if (is_logged_in()): ?>
                                    <?php if ($user_liked): ?>
                                        <a href="?id=<?php echo $post_id; ?>&action=unlike" class="btn btn-primary">
                                            <i class="fas fa-heart me-1"></i> Liked (<?php echo $like_count; ?>)
                                        </a>
                                    <?php else: ?>
                                        <a href="?id=<?php echo $post_id; ?>&action=like" class="btn btn-outline-primary">
                                            <i class="far fa-heart me-1"></i> Like (<?php echo $like_count; ?>)
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="/beautyclick/auth/login.php" class="btn btn-outline-primary">
                                        <i class="far fa-heart me-1"></i> Like (<?php echo $like_count; ?>)
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Comment Button -->
                                <a href="#comments" class="btn btn-outline-secondary ms-2">
                                    <i class="far fa-comment me-1"></i> Comments (<?php echo count($comments); ?>)
                                </a>
                            </div>
                            
                            <!-- Share Options -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                        id="shareDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-share-alt me-1"></i> Share
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="shareDropdown">
                                    <li>
                                        <a class="dropdown-item" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/beautyclick/posts/view.php?id=' . $post_id); ?>" target="_blank">
                                            <i class="fab fa-facebook text-primary me-2"></i> Facebook
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/beautyclick/posts/view.php?id=' . $post_id); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank">
                                            <i class="fab fa-twitter text-info me-2"></i> Twitter
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item copy-link" href="#" data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/beautyclick/posts/view.php?id=' . $post_id; ?>">
                                            <i class="fas fa-link text-secondary me-2"></i> Copy Link
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="card border-0 shadow-sm mb-4" id="comments">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Comments (<?php echo count($comments); ?>)</h4>
                </div>
                <div class="card-body p-4">
                    <!-- Comment List -->
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item mb-4">
                                <div class="d-flex">
                                    <img src="/beautyclick/assets/uploads/avatars/<?php echo $comment['avatar']; ?>" 
                                         class="rounded-circle me-3" width="50" height="50" alt="<?php echo $comment['full_name']; ?>">
                                    <div class="comment-content flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <h6 class="mb-0"><?php echo $comment['full_name']; ?></h6>
                                                <span class="text-muted small">
                                                    <?php if ($comment['role_id'] == 2): ?>
                                                        <span class="badge bg-primary-subtle text-primary">Makeup Artist</span>
                                                    <?php elseif ($comment['role_id'] == 1): ?>
                                                        <span class="badge bg-danger-subtle text-danger">Admin</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary">Client</span>
                                                    <?php endif; ?>
                                                    <span class="ms-2"><?php echo date('M d, Y g:i A', strtotime($comment['created_at'])); ?></span>
                                                </span>
                                            </div>
                                            <!-- Comment actions (if needed) -->
                                            <?php if (is_logged_in() && ($_SESSION['user_id'] == $comment['user_id'] || user_has_role('admin'))): ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-link text-muted p-0" type="button" 
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <!-- We would add delete functionality in a real implementation -->
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#">
                                                                <i class="fas fa-trash-alt me-2"></i> Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo nl2br($comment['comment']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($comment !== end($comments)): ?>
                                <hr class="comment-divider">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="far fa-comment-dots fa-3x text-muted mb-3"></i>
                            <h5>No Comments Yet</h5>
                            <p class="text-muted">Be the first to share your thoughts on this post!</p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Add Comment Form -->
                    <?php if (is_logged_in()): ?>
                        <div class="add-comment-form mt-4 pt-4 border-top">
                            <h5 class="mb-3">Leave a Comment</h5>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $post_id; ?>" method="POST">
                                <div class="mb-3">
                                    <textarea class="form-control" id="comment" name="comment" rows="3" 
                                              placeholder="Share your thoughts..." required></textarea>
                                </div>
                                <button type="submit" name="add_comment" class="btn btn-primary">
                                    <i class="far fa-paper-plane me-1"></i> Post Comment
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 mt-4 border-top">
                            <p class="mb-0">
                                <a href="/beautyclick/auth/login.php" class="text-decoration-none">Login</a> or 
                                <a href="/beautyclick/auth/register.php" class="text-decoration-none">Register</a> 
                                to leave a comment.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Artist Profile Card -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">About the Author</h5>
                    <div class="d-flex align-items-center mb-3">
                        <img src="/beautyclick/assets/uploads/avatars/<?php echo $post['artist_avatar']; ?>" 
                             class="rounded-circle me-3" width="60" height="60" alt="<?php echo $post['artist_name']; ?>">
                        <div>
                            <h6 class="mb-1">
                                <a href="/beautyclick/artists/profile.php?id=<?php echo $post['artist_id']; ?>" 
                                   class="text-decoration-none"><?php echo $post['artist_name']; ?></a>
                            </h6>
                            <div class="artist-info d-flex align-items-center">
                                <i class="fas fa-star text-warning me-1"></i>
                                <span><?php echo number_format($post['avg_rating'], 1); ?> rating</span>
                            </div>
                        </div>
                    </div>
                    <p class="artist-bio mb-3">
                        <?php 
                        $bio = $post['artist_bio'];
                        $max_length = 150;
                        
                        if (strlen($bio) > $max_length) {
                            echo substr($bio, 0, $max_length) . '...';
                        } else {
                            echo $bio;
                        }
                        ?>
                    </p>
                    <a href="/beautyclick/artists/profile.php?id=<?php echo $post['artist_id']; ?>" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user me-1"></i> View Full Profile
                    </a>
                </div>
            </div>
            
            <!-- Services from this Artist -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Book a Service</h5>
                    <p class="text-muted mb-3">Liked this artist's work? Book one of their services now!</p>
                    
                    <?php
                    // Get services from this artist
                    $artist_services = get_records($conn, "SELECT s.*, c.category_name 
                                                          FROM services s
                                                          JOIN service_categories c ON s.category_id = c.category_id
                                                          WHERE s.artist_id = {$post['artist_id']}
                                                          AND s.is_available = 1
                                                          ORDER BY s.price ASC
                                                          LIMIT 3");
                    
                    if (count($artist_services) > 0):
                        foreach ($artist_services as $service):
                    ?>
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo $service['service_name']; ?></h6>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="me-2">
                                    <i class="fas fa-tag me-1"></i> <?php echo $service['category_name']; ?>
                                </span>
                                <span>
                                    <i class="fas fa-clock me-1"></i> <?php echo $service['duration']; ?> min
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary mb-1"><?php echo format_currency($service['price']); ?></div>
                            <a href="/beautyclick/services/details.php?id=<?php echo $service['service_id']; ?>" 
                               class="btn btn-sm btn-primary">
                                Book Now
                            </a>
                        </div>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        No services available from this artist at the moment.
                    </div>
                    <?php endif; ?>
                    
                    <a href="/beautyclick/services/index.php?artist=<?php echo $post['artist_id']; ?>" 
                       class="btn btn-outline-primary btn-sm w-100 mt-2">
                        <i class="fas fa-list me-1"></i> View All Services
                    </a>
                </div>
            </div>
            
            <!-- Related Posts -->
            <?php if (count($related_posts) > 0): ?>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">More from this Artist</h5>
                    
                    <?php foreach ($related_posts as $related): ?>
                    <div class="related-post mb-3 pb-3 border-bottom">
                        <?php if (!empty($related['image'])): ?>
                        <a href="/beautyclick/posts/view.php?id=<?php echo $related['post_id']; ?>" class="d-block mb-2">
                            <img src="/beautyclick/assets/uploads/posts/<?php echo $related['image']; ?>" 
                                 class="img-fluid rounded" alt="<?php echo $related['title']; ?>">
                        </a>
                        <?php endif; ?>
                        <h6>
                            <a href="/beautyclick/posts/view.php?id=<?php echo $related['post_id']; ?>" 
                               class="text-decoration-none"><?php echo $related['title']; ?></a>
                        </h6>
                        <div class="post-meta small text-muted">
                            <span class="me-2">
                                <i class="far fa-calendar me-1"></i> <?php echo date('M d, Y', strtotime($related['created_at'])); ?>
                            </span>
                            <span class="me-2">
                                <i class="far fa-heart me-1"></i> <?php echo $related['likes']; ?>
                            </span>
                            <span>
                                <i class="far fa-comment me-1"></i> <?php echo $related['comment_count']; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <a href="/beautyclick/posts/index.php?artist=<?php echo $post['artist_id']; ?>" 
                       class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-book-open me-1"></i> View All Stories
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle copy link functionality
    const copyLinks = document.querySelectorAll('.copy-link');
    copyLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            
            // Create a temporary input element to copy the URL
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Show a temporary tooltip
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check text-success me-2"></i> Copied!';
            
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>