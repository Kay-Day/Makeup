<?php
// includes/header.php - Header component for all pages

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions file if not already included
if (!function_exists('is_logged_in')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';
}

// Get messages for alert display
$messages = get_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - BeautyClick' : 'BeautyClick - Student Makeup Booking'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/beautyclick/assets/css/style.css">
    
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header>
        <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/navbar.php'; ?>
    </header>
    
    <!-- Main Content Container -->
    <main class="container py-4">
        <?php if (isset($messages['success']) && $messages['success']): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $messages['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($messages['error']) && $messages['error']): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $messages['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>