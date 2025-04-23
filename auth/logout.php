<?php
// auth/logout.php - User logout script

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (is_logged_in()) {
    // Clear session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Clear remember_me cookie if exists
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Set success message in a temporary cookie
    setcookie('logout_message', 'You have been successfully logged out.', time() + 60, '/');
}

// Redirect to home page
redirect('/beautyclick/index.php');
exit;
?>