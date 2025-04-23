<?php
// artist/post_form.php - Handle post creation/update with image upload

// Check action (add/edit)
$action = isset($_GET['action']) ? $_GET['action'] : 'add';
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get post data if editing
$post = [];
if ($action === 'edit' && $post_id > 0) {
    $post = get_record($conn, "SELECT * FROM posts WHERE post_id = $post_id AND artist_id = $artist_id");
    if (!$post) {
        set_error_message("Post not found or you don't have permission to edit it.");
        redirect('/beautyclick/artist/posts.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $image = $action === 'edit' ? $post['image'] : ''; // Keep existing or empty
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        // Log image upload attempt
        error_log("Post image upload attempt: " . print_r($_FILES['image'], true));
        
        $uploaded_image = upload_image($_FILES['image'], 'posts');
        if ($uploaded_image) {
            $image = $uploaded_image;
            error_log("New post image filename: $image");
        } else {
            error_log("Post image upload failed");
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
?>

<!-- HTML form with file upload -->
<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?action=' . $action . ($post_id ? '&id=' . $post_id : '')); ?>" method="POST" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="title" class="form-label">Post Title *</label>
        <input type="text" class="form-control" id="title" name="title" 
               value="<?php echo isset($post['title']) ? $post['title'] : ''; ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="content" class="form-label">Content *</label>
        <textarea class="form-control" id="content" name="content" rows="6" required><?php echo isset($post['content']) ? $post['content'] : ''; ?></textarea>
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
                        const img = filePreview.querySelector('img');
                        img.src = e.target.result;
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>