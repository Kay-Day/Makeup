<?php
// check_uploads.php - Script to check upload directory permissions

// Set header to display as plain text
header('Content-Type: text/plain');

echo "BeautyClick Upload Directory Check\n";
echo "================================\n\n";

// Check base uploads directory
$base_upload_dir = __DIR__ . '/assets/uploads';
echo "Base uploads directory: $base_upload_dir\n";
echo "Exists: " . (file_exists($base_upload_dir) ? 'Yes' : 'No') . "\n";

// Create directory if it doesn't exist
if (!file_exists($base_upload_dir)) {
    echo "Creating base uploads directory...\n";
    if (mkdir($base_upload_dir, 0755, true)) {
        echo "Successfully created base uploads directory.\n";
    } else {
        echo "Failed to create base uploads directory. Error: " . error_get_last()['message'] . "\n";
    }
}

echo "Is writable: " . (is_writable($base_upload_dir) ? 'Yes' : 'No') . "\n\n";

// Check subdirectories
$subdirs = ['avatars', 'services', 'posts'];

foreach ($subdirs as $subdir) {
    $dir = $base_upload_dir . '/' . $subdir;
    echo "Checking $subdir directory: $dir\n";
    echo "Exists: " . (file_exists($dir) ? 'Yes' : 'No') . "\n";
    
    // Create subdirectory if it doesn't exist
    if (!file_exists($dir)) {
        echo "Creating $subdir directory...\n";
        if (mkdir($dir, 0755, true)) {
            echo "Successfully created $subdir directory.\n";
        } else {
            echo "Failed to create $subdir directory. Error: " . error_get_last()['message'] . "\n";
        }
    }
    
    echo "Is writable: " . (is_writable($dir) ? 'Yes' : 'No') . "\n\n";
    
    // Try to create a test file in each directory
    $test_file = $dir . '/test_write.txt';
    $result = file_put_contents($test_file, 'This is a test file to check write permissions. You can delete this file.');
    
    if ($result !== false) {
        echo "Successfully wrote test file to $subdir directory.\n";
        // Delete the test file
        if (unlink($test_file)) {
            echo "Successfully deleted test file from $subdir directory.\n";
        } else {
            echo "Created test file but could not delete it. Please check permissions.\n";
        }
    } else {
        echo "FAILED to write test file to $subdir directory. Please check permissions.\n";
    }
    echo "\n";
}

// Check PHP configuration
echo "PHP Configuration:\n";
echo "==================\n";
echo "file_uploads enabled: " . (ini_get('file_uploads') ? 'Yes' : 'No') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n\n";

echo "Temporary directory path: " . sys_get_temp_dir() . "\n";
echo "Temporary directory is writable: " . (is_writable(sys_get_temp_dir()) ? 'Yes' : 'No') . "\n\n";

echo "Server information:\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "Server software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Running as user: " . get_current_user() . "\n";

echo "\n\nIf you're still having issues with file uploads, try the following:\n";
echo "1. Set the correct permissions: chmod -R 755 " . __DIR__ . "/assets/uploads\n";
echo "2. Make sure the web server has ownership: chown -R www-data:www-data " . __DIR__ . "/assets/uploads\n";
echo "   (On Windows, make sure IIS_IUSRS or IUSR have write permissions)\n";
echo "3. Increase memory limits in php.ini if needed\n";
echo "4. Check your PHP error logs for more detailed error messages\n";
?>

// Try to create a test file in each directory