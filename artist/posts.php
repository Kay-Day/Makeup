<?php
// artist/posts.php - Manage artist posts

// Set page title
$page_title = "Manage Posts";

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

// Handle action
$action = isset($_GET['action']) ? $_GET['action'] : '';
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Process actions
if ($action === 'delete' && $post_id > 0) {
    // Delete post
    if (delete_record($conn, 'posts', "post_id = $post_id AND artist_id = $artist_id")) {
        set_success_message("Post deleted successfully!");
    } else {
        set_error_message("Failed to delete post.");
    }
    redirect('/beautyclick/artist/posts.php');
    exit;
}

// Handle form submission for adding/editing posts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    // Get form data
    // Get form data
    $title = sanitize_input($conn, $_POST['title'] ?? '');
    $content = sanitize_input($conn, $_POST['content'] ?? '');
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Post title is required.";
    }
    
    if (empty($content)) {
        $errors[] = "Post content is required.";
    }
    
    // Handle image upload
    $image = '';
    if ($action === 'edit' && $post_id > 0) {
        $existing_post = get_record($conn, "SELECT image FROM posts WHERE post_id = $post_id AND artist_id = $artist_id");
        $image = $existing_post ? $existing_post['image'] : '';
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploaded_image = upload_image($_FILES['image'], 'posts');
        if ($uploaded_image) {
            $image = $uploaded_image;
        } else {
            $errors[] = "Failed to upload post image. Please try again.";
        }
    }
    
    // If no errors, process the post
    if (empty($errors)) {
        // Prepare post data
        $post_data = [
            'title' => $title,
            'content' => $content
        ];
        
        // Add image if exists
        if (!empty($image)) {
            $post_data['image'] = $image;
        }
        
        if ($action === 'add') {
            // Add artist_id for new posts
            $post_data['artist_id'] = $artist_id;
            
            // Insert new post
            if (insert_record($conn, 'posts', $post_data)) {
                set_success_message("Post published successfully!");
                redirect('/beautyclick/artist/posts.php');
                exit;
            } else {
                set_error_message("Failed to publish post. Please try again.");
            }
        } else {
            // Update existing post
            if (update_record($conn, 'posts', $post_data, "post_id = $post_id AND artist_id = $artist_id")) {
                set_success_message("Post updated successfully!");
                redirect('/beautyclick/artist/posts.php');
                exit;
            } else {
                set_error_message("Failed to update post. Please try again.");
            }
        }
    } else {
        set_error_message(implode("<br>", $errors));
    }
}

// Get post for editing if needed
$post = [];
if ($action === 'edit' && $post_id > 0) {
    $post = get_record($conn, "SELECT * FROM posts WHERE post_id = $post_id AND artist_id = $artist_id");
    if (!$post) {
        set_error_message("Post not found or you don't have permission to edit it.");
        redirect('/beautyclick/artist/posts.php');
        exit;
    }
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-edit me-2"></i>
                    <?php echo $action === 'add' ? 'Create New Post' : ($action === 'edit' ? 'Edit Post' : 'My Posts'); ?>
                </h2>
                <?php if ($action !== 'add' && $action !== 'edit'): ?>
                <a href="/beautyclick/artist/posts.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Post
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?action=' . $action . ($post_id ? '&id=' . $post_id : '')); ?>" 
                          method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Post Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo isset($post['title']) ? $post['title'] : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content *</label>
                            <textarea class="form-control" id="content" name="content" rows="8" required><?php echo isset($post['content']) ? $post['content'] : ''; ?></textarea>
                            <div class="form-text">Use plain text. For formatting, use basic HTML tags if needed.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="image" class="form-label">Post Image</label>
                            <input type="file" class="form-control custom-file-input" id="image" name="image" accept="image/*">
                            <div class="form-text">Max file size: 5MB. Images make your posts more engaging!</div>
                            <?php if (isset($post['image']) && !empty($post['image'])): ?>
                            <div class="file-preview mt-2">
                                <img src="/beautyclick/assets/uploads/posts/<?php echo $post['image']; ?>" 
                                     class="img-thumbnail" alt="Post Image" style="max-width: 300px;">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-end">
                            <a href="/beautyclick/artist/posts.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i><?php echo $action === 'add' ? 'Publish Post' : 'Update Post'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Posts List -->
    <div class="row">
        <?php
        $posts = get_records($conn, "SELECT p.*, 
                                   (SELECT COUNT(*) FROM post_comments WHERE post_id = p.post_id) AS comments_count
                                   FROM posts p
                                   WHERE p.artist_id = $artist_id
                                   ORDER BY p.created_at DESC");
        
        if (count($posts) > 0):
            foreach ($posts as $post):
        ?>
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <?php if (!empty($post['image'])): ?>
                <img src="/beautyclick/assets/uploads/posts/<?php echo $post['image']; ?>" 
                     class="card-img-top" alt="<?php echo $post['title']; ?>" style="height: 200px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $post['title']; ?></h5>
                    <p class="card-text text-muted small">
                        <i class="far fa-calendar-alt me-1"></i>
                        <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                        <span class="mx-2">|</span>
                        <i class="far fa-heart me-1"></i><?php echo $post['likes']; ?> likes
                        <span class="mx-2">|</span>
                        <i class="far fa-comment me-1"></i><?php echo $post['comments_count']; ?> comments
                    </p>
                    <p class="card-text">
                        <?php echo substr(strip_tags($post['content']), 0, 150) . (strlen(strip_tags($post['content'])) > 150 ? '...' : ''); ?>
                    </p>
                </div>
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/beautyclick/posts/details.php?id=<?php echo $post['post_id']; ?>" 
                           class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-eye me-1"></i>View Post
                        </a>
                        <div class="btn-group">
                            <a href="/beautyclick/artist/posts.php?action=edit&id=<?php echo $post['post_id']; ?>" 
                               class="btn btn-outline-secondary btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="/beautyclick/artist/posts.php?action=delete&id=<?php echo $post['post_id']; ?>" 
                               class="btn btn-outline-danger btn-sm" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this post?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
            endforeach;
        else:
        ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-edit fa-3x mb-3"></i>
                <h4>No Posts Yet</h4>
                <p class="mb-3">You haven't published any posts. Share your work and expertise with your clients!</p>
                <a href="/beautyclick/artist/posts.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Your First Post
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Preview uploaded image before form submission
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('image');
    const filePreview = document.querySelector('.file-preview');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (!filePreview) {
                        // Create preview container if it doesn't exist
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'file-preview mt-2';
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail';
                        img.alt = 'Post Image Preview';
                        img.style.maxWidth = '300px';
                        previewDiv.appendChild(img);
                        fileInput.parentNode.appendChild(previewDiv);
                    } else {
                        // Update existing preview
                        let img = filePreview.querySelector('img');
                        if (!img) {
                            img = document.createElement('img');
                            img.className = 'img-thumbnail';
                            img.alt = 'Post Image Preview';
                            img.style.maxWidth = '300px';
                            filePreview.appendChild(img);
                        }
                        img.src = e.target.result;
                    }
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