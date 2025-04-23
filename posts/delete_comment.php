<?php
// posts/delete_comment.php - Delete comment from post

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_error_message("Please login to perform this action.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Get comment ID and post ID from URL
$comment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// Check if IDs are valid
if ($comment_id <= 0 || $post_id <= 0) {
    set_error_message("Invalid request.");
    redirect('/beautyclick/posts/index.php');
    exit;
}

// Check if comment exists and belongs to the current user or if user is admin
$user_id = $_SESSION['user_id'];
$is_admin = user_has_role('admin');

$comment_sql = "SELECT c.*, p.artist_id, p.title AS post_title 
                FROM post_comments c
                JOIN posts p ON c.post_id = p.post_id
                WHERE c.comment_id = $comment_id AND c.post_id = $post_id";
$comment = get_record($conn, $comment_sql);

if (!$comment) {
    set_error_message("Comment not found.");
    redirect('/beautyclick/posts/details.php?id=' . $post_id);
    exit;
}

// Check if user has permission to delete this comment
if ($comment['user_id'] != $user_id && !$is_admin && $comment['artist_id'] != $user_id) {
    set_error_message("You don't have permission to delete this comment.");
    redirect('/beautyclick/posts/details.php?id=' . $post_id);
    exit;
}

// Delete the comment
$result = delete_record($conn, 'post_comments', "comment_id = $comment_id");

if ($result) {
    // Create notification for the comment owner if deleted by admin or artist
    if ($comment['user_id'] != $user_id) {
        $notification_title = "Comment Deleted";
        $notification_message = "Your comment on the post \"{$comment['post_title']}\" has been deleted by " . 
                               ($is_admin ? "an administrator" : "the artist");
        create_notification($comment['user_id'], $notification_title, $notification_message);
    }
    
    set_success_message("Comment deleted successfully.");
} else {
    set_error_message("Failed to delete comment. Please try again.");
}

// Redirect back to the post
redirect('/beautyclick/posts/details.php?id=' . $post_id . '#comments');
exit;
?>