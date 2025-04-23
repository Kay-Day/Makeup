<?php
// posts/index.php - Display all posts from makeup artists

// Set page title
$page_title = "Beauty Stories";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Get filter parameters
$artist_id = isset($_GET['artist']) ? intval($_GET['artist']) : null;
$category = sanitize_input($conn, $_GET['category'] ?? '');
$sort = sanitize_input($conn, $_GET['sort'] ?? 'newest');

// Build SQL query
$sql = "SELECT p.*, u.full_name AS artist_name, u.avatar AS artist_avatar, 
         COUNT(DISTINCT c.comment_id) AS comment_count,
         (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.post_id) AS like_count
         FROM posts p
         JOIN users u ON p.artist_id = u.user_id
         LEFT JOIN post_comments c ON p.post_id = c.post_id
         WHERE u.status = 'active'";

// Apply artist filter
if ($artist_id) {
    $sql .= " AND p.artist_id = $artist_id";
}

// Apply category filter through content search (simple approach)
if (!empty($category)) {
    $sql .= " AND (p.title LIKE '%$category%' OR p.content LIKE '%$category%')";
}

// Group by post to avoid duplicates due to comments
$sql .= " GROUP BY p.post_id";

// Apply sorting
switch ($sort) {
    case 'likes':
        $sql .= " ORDER BY like_count DESC, p.created_at DESC";
        break;
    case 'comments':
        $sql .= " ORDER BY comment_count DESC, p.created_at DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY p.created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

// Get posts
$posts = get_records($conn, $sql);

// Get top artists for sidebar
$top_artists = get_top_artists(5);

// Get popular post categories (simple approach using service categories as topics)
$popular_categories = get_service_categories();

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<!-- Page Header -->
<div class="bg-light py-4 mb-4">
    <div class="container">
        <h1 class="h3 mb-0">Beauty Stories</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/beautyclick/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Beauty Stories</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8 mb-4">
            <!-- Sort Options -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php if ($artist_id): 
                                $artist = get_user_data($artist_id);
                                echo "Stories by " . htmlspecialchars($artist['full_name']);
                            elseif (!empty($category)): 
                                echo "Stories about " . htmlspecialchars($category);
                            else:
                                echo "Latest Beauty Stories";
                            endif; ?>
                        </h5>
                        <div class="d-flex align-items-center">
                            <label for="sort-select" class="form-label me-2 mb-0">Sort by:</label>
                            <select class="form-select form-select-sm" id="sort-select" onchange="updateSort(this.value)">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="likes" <?php echo $sort == 'likes' ? 'selected' : ''; ?>>Most Liked</option>
                                <option value="comments" <?php echo $sort == 'comments' ? 'selected' : ''; ?>>Most Discussed</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Posts List -->
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                <div class="card mb-4 post-card border-0 shadow-sm">
                    <div class="card-body">
                        <!-- Post Author & Date -->
                        <div class="d-flex align-items-center mb-3">
                            <img src="/beautyclick/assets/uploads/avatars/<?php echo $post['artist_avatar']; ?>" 
                                 class="rounded-circle me-2" width="40" height="40" alt="<?php echo $post['artist_name']; ?>">
                            <div>
                                <a href="/beautyclick/artists/profile.php?id=<?php echo $post['artist_id']; ?>" 
                                   class="text-decoration-none fw-bold"><?php echo $post['artist_name']; ?></a>
                                <div class="text-muted small">
                                    <i class="far fa-clock me-1"></i> 
                                    <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Post Title & Content -->
                        <h4 class="card-title mb-3"><?php echo $post['title']; ?></h4>
                        
                        <!-- Post Image if exists -->
                        <?php if (!empty($post['image'])): ?>
                        <div class="post-image-container mb-3">
                            <img src="/beautyclick/assets/uploads/posts/<?php echo $post['image']; ?>" 
                                 class="img-fluid rounded" alt="<?php echo $post['title']; ?>">
                        </div>
                        <?php endif; ?>
                        
                        <!-- Post Content Preview -->
                        <div class="post-content mb-3">
                            <?php 
                            $content = $post['content'];
                            $max_length = 300;
                            
                            if (strlen($content) > $max_length) {
                                echo nl2br(substr($content, 0, $max_length)) . '...';
                            } else {
                                echo nl2br($content);
                            }
                            ?>
                        </div>
                        
                        <!-- Post Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="post-actions">
                                <a href="/beautyclick/posts/view.php?id=<?php echo $post['post_id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i> Read More
                                </a>
                                
                                <?php if (is_logged_in()): ?>
                                <!-- Check if user already liked the post -->
                                <?php 
                                $user_id = $_SESSION['user_id'];
                                $liked = get_record($conn, "SELECT like_id FROM post_likes WHERE post_id = {$post['post_id']} AND user_id = $user_id");
                                ?>
                                
                                <button class="btn btn-outline-secondary btn-sm ms-2 like-button <?php echo $liked ? 'liked' : ''; ?>"
                                        data-post-id="<?php echo $post['post_id']; ?>">
                                    <i class="<?php echo $liked ? 'fas' : 'far'; ?> fa-heart me-1"></i> 
                                    <span class="like-count"><?php echo $post['like_count']; ?></span>
                                </button>
                                <?php else: ?>
                                <a href="/beautyclick/auth/login.php" class="btn btn-outline-secondary btn-sm ms-2">
                                    <i class="far fa-heart me-1"></i> <?php echo $post['like_count']; ?>
                                </a>
                                <?php endif; ?>
                                
                                <a href="/beautyclick/posts/view.php?id=<?php echo $post['post_id']; ?>#comments" 
                                   class="btn btn-outline-secondary btn-sm ms-2">
                                    <i class="far fa-comment me-1"></i> <?php echo $post['comment_count']; ?>
                                </a>
                            </div>
                            
                            <div class="post-share">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-share-alt"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/beautyclick/posts/view.php?id=' . $post['post_id']); ?>" target="_blank">
                                            <i class="fab fa-facebook text-primary me-2"></i> Facebook
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/beautyclick/posts/view.php?id=' . $post['post_id']); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank">
                                            <i class="fab fa-twitter text-info me-2"></i> Twitter
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item copy-link" href="#" data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/beautyclick/posts/view.php?id=' . $post['post_id']; ?>">
                                            <i class="fas fa-link text-secondary me-2"></i> Copy Link
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Pagination (if needed) -->
                <nav aria-label="Posts pagination">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                        </li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h4>No Stories Found</h4>
                    <p>We couldn't find any beauty stories matching your criteria. Please try different filters or check back later.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Search Box -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Search Stories</h5>
                    <form action="/beautyclick/posts/index.php" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control" name="category" placeholder="Search for beauty tips, tutorials...">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Categories -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Popular Topics</h5>
                    <div class="list-group list-group-flush">
                        <?php foreach ($popular_categories as $category): ?>
                        <a href="/beautyclick/posts/index.php?category=<?php echo urlencode($category['category_name']); ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <?php echo $category['category_name']; ?>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo rand(2, 15); // Simulate post count per category ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Featured Artists -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Top Makeup Artists</h5>
                    <?php foreach ($top_artists as $artist): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="/beautyclick/assets/uploads/avatars/<?php echo $artist['avatar']; ?>" 
                             class="rounded-circle me-3" width="50" height="50" alt="<?php echo $artist['full_name']; ?>">
                        <div>
                            <h6 class="mb-0">
                                <a href="/beautyclick/artists/profile.php?id=<?php echo $artist['user_id']; ?>" 
                                   class="text-decoration-none"><?php echo $artist['full_name']; ?></a>
                            </h6>
                            <div class="text-muted small">
                                <i class="fas fa-star text-warning me-1"></i> 
                                <?php echo number_format($artist['avg_rating'], 1); ?> 
                                <span class="mx-1">•</span> 
                                <?php echo $artist['total_bookings']; ?> bookings
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <a href="/beautyclick/artists.php" class="btn btn-outline-primary btn-sm w-100 mt-2">
                        <i class="fas fa-users me-1"></i> View All Artists
                    </a>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div class="card mb-4 border-0 shadow-sm bg-primary text-white">
                <div class="card-body text-center py-4">
                    <h5 class="card-title">Ready for a Makeover?</h5>
                    <p class="mb-3">Book with our talented student makeup artists for affordable, quality services.</p>
                    <a href="/beautyclick/services/index.php" class="btn btn-light">
                        <i class="fas fa-calendar-check me-1"></i> Book Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle sorting
    window.updateSort = function(sortValue) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', sortValue);
        window.location.href = window.location.pathname + '?' + urlParams.toString();
    };
    
    // Handle post likes
    const likeButtons = document.querySelectorAll('.like-button');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.getAttribute('data-post-id');
            const likeCount = this.querySelector('.like-count');
            const isLiked = this.classList.contains('liked');
            
            // Optimistic UI update
            if (isLiked) {
                this.classList.remove('liked');
                this.querySelector('i').classList.remove('fas');
                this.querySelector('i').classList.add('far');
                likeCount.textContent = parseInt(likeCount.textContent) - 1;
            } else {
                this.classList.add('liked');
                this.querySelector('i').classList.remove('far');
                this.querySelector('i').classList.add('fas');
                likeCount.textContent = parseInt(likeCount.textContent) + 1;
            }
            
            // Send AJAX request to update like status
            // In a real implementation, you would use fetch or XMLHttpRequest
            // to send a request to a like.php endpoint
            
            // For demonstration, we'll just simulate the API call with a timeout
            setTimeout(function() {
                console.log(`Post ${postId} like status updated to ${!isLiked}`);
            }, 300);
        });
    });
    
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