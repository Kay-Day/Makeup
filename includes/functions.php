<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/config/database.php';

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function user_has_role($role) {
    if (!is_logged_in()) {
        return false;
    }
    
    return $_SESSION['role'] === $role;
}

// Function to redirect to a specific page
function redirect($url) {
    header("Location: $url");
    exit;
}

// Function to display success message
function set_success_message($message) {
    $_SESSION['success_message'] = $message;
}

// Function to display error message
function set_error_message($message) {
    $_SESSION['error_message'] = $message;
}

// Function to get and clear messages
function get_messages() {
    $messages = [
        'success' => isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null,
        'error' => isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null
    ];
    
    // Clear messages
    unset($_SESSION['success_message']);
    unset($_SESSION['error_message']);
    
    return $messages;
}

// Function to validate email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate password strength
function is_valid_password($password) {
    // At least 8 characters, at least one uppercase letter, one lowercase letter, and one number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

// Function to validate phone number (Vietnamese format)
function is_valid_phone($phone) {
    // Vietnamese phone number validation
    return preg_match('/^(0|\+84)(\d{9,10})$/', $phone);
}

// Function to upload image
// function upload_image($file, $destination_folder) {
//     global $conn;
    
//     // Check if file was uploaded without errors
//     if ($file['error'] === 0) {
//         $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/assets/uploads/' . $destination_folder . '/';
        
//         // Create directory if it doesn't exist
//         if (!file_exists($upload_dir)) {
//             mkdir($upload_dir, 0777, true);
//         }
        
//         // Get file info
//         $file_name = basename($file['name']);
//         $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
//         // Generate unique filename
//         $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
//         $target_file = $upload_dir . $new_file_name;
        
//         // Check file type
//         $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
//         if (!in_array($file_ext, $allowed_types)) {
//             set_error_message("Only JPG, JPEG, PNG, and GIF files are allowed.");
//             return false;
//         }
        
//         // Check file size (max 5MB)
//         if ($file['size'] > 5 * 1024 * 1024) {
//             set_error_message("File is too large. Maximum size is 5MB.");
//             return false;
//         }
        
//         // Upload file
//         if (move_uploaded_file($file['tmp_name'], $target_file)) {
//             return $new_file_name;
//         } else {
//             set_error_message("There was an error uploading your file.");
//             return false;
//         }
//     } else {
//         set_error_message("Error in file upload: " . $file['error']);
//         return false;
//     }
// }

// Function to upload image
function upload_image($file, $destination_folder) {
    global $conn;
    
    // Check if file was uploaded without errors
    if ($file['error'] === 0) {
        // Detailed logging for debugging
        error_log("Upload attempt for file: " . $file['name']);
        error_log("File size: " . $file['size'] . " bytes");
        error_log("File type: " . $file['type']);
        
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/assets/uploads/' . $destination_folder . '/';
        
        // Log upload directory 
        error_log("Upload directory: " . $upload_dir);
        
        // Check if directory exists and is writable
        if (!file_exists($upload_dir)) {
            error_log("Directory does not exist, attempting to create it");
            if (!mkdir($upload_dir, 0777, true)) {
                error_log("Failed to create directory: " . error_get_last()['message']);
                set_error_message("Upload directory does not exist and could not be created.");
                return false;
            }
        }
        
        if (!is_writable($upload_dir)) {
            error_log("Directory is not writable: " . $upload_dir);
            set_error_message("Upload directory is not writable. Please check permissions.");
            return false;
        }
        
        // Get file info
        $file_name = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Generate unique filename
        $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $new_file_name;
        
        error_log("Target file path: " . $target_file);
        
        // Check file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_types)) {
            error_log("Invalid file type: " . $file_ext);
            set_error_message("Only JPG, JPEG, PNG, and GIF files are allowed.");
            return false;
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            error_log("File too large: " . $file['size'] . " bytes");
            set_error_message("File is too large. Maximum size is 5MB.");
            return false;
        }
        
        // Upload file with detailed error checking
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            error_log("File uploaded successfully to: " . $target_file);
            return $new_file_name;
        } else {
            $error_msg = error_get_last()['message'] ?? 'Unknown error';
            error_log("File upload failed. Error: " . $error_msg);
            error_log("PHP temp file: " . $file['tmp_name']);
            set_error_message("Upload failed. Please try again. Server error: " . $error_msg);
            return false;
        }
    } else {
        // Map error codes to messages
        $error_messages = [
            1 => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            2 => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
            3 => "The uploaded file was only partially uploaded",
            4 => "No file was uploaded",
            6 => "Missing a temporary folder",
            7 => "Failed to write file to disk",
            8 => "A PHP extension stopped the file upload"
        ];
        
        $error_msg = isset($error_messages[$file['error']]) ? $error_messages[$file['error']] : "Unknown upload error";
        error_log("File upload error code: " . $file['error'] . " - " . $error_msg);
        set_error_message("Error in file upload: " . $error_msg);
        return false;
    }
}

// Function to get user data
function get_user_data($user_id) {
    global $conn;
    
    $sql = "SELECT u.*, r.role_name FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = $user_id";
    
    return get_record($conn, $sql);
}

// Function to get artist profile
function get_artist_profile($user_id) {
    global $conn;
    
    $sql = "SELECT * FROM artist_profiles WHERE user_id = $user_id";
    return get_record($conn, $sql);
}

// Function to check if a city is available
function is_city_available($city_name) {
    global $conn;
    
    $city_name = sanitize_input($conn, $city_name);
    $sql = "SELECT is_available FROM cities WHERE city_name = '$city_name'";
    $result = get_record($conn, $sql);
    
    return $result && $result['is_available'] == 1;
}

// Function to get all available cities
function get_available_cities() {
    global $conn;
    
    $sql = "SELECT * FROM cities WHERE is_available = 1";
    return get_records($conn, $sql);
}

// Function to check if location is in Da Nang
function is_in_danang($address) {
    global $conn;
    
    // Simple check - just see if the address contains "Da Nang" or "Đà Nẵng"
    return (stripos($address, 'Da Nang') !== false || 
            stripos($address, 'Đà Nẵng') !== false);
}

// Function to generate a random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

// Function to calculate points for booking
function calculate_points($amount) {
    // 1 point for every 10,000 VND
    return floor($amount / 10000);
}

// Function to format currency (VND)
function format_currency($amount) {
    return number_format($amount, 0, ',', '.') . ' VND';
}

// Function to format date to Vietnamese format
function format_date($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

// Function to format time
function format_time($time) {
    $timestamp = strtotime($time);
    return date('H:i', $timestamp);
}

// Function to create notification
function create_notification($user_id, $title, $message) {
    global $conn;
    
    $data = [
        'user_id' => $user_id,
        'title' => $title,
        'message' => $message
    ];
    
    return insert_record($conn, 'notifications', $data);
}

// Function to check if a discount code is valid
function validate_discount_code($code, $purchase_amount) {
    global $conn;
    
    $code = sanitize_input($conn, $code);
    $today = date('Y-m-d');
    
    $sql = "SELECT * FROM discount_codes 
            WHERE code = '$code' 
            AND is_active = 1 
            AND start_date <= '$today' 
            AND end_date >= '$today' 
            AND (usage_limit IS NULL OR used_count < usage_limit)
            AND min_purchase <= $purchase_amount";
    
    $discount = get_record($conn, $sql);
    
    if (!$discount) {
        return false;
    }
    
    return $discount;
}

// Function to apply discount
function apply_discount($discount, $amount) {
    if ($discount['discount_type'] === 'percentage') {
        $discount_amount = $amount * ($discount['discount_value'] / 100);
        
        // Apply max discount if set
        if ($discount['max_discount'] !== null && $discount_amount > $discount['max_discount']) {
            $discount_amount = $discount['max_discount'];
        }
    } else {
        // Fixed amount discount
        $discount_amount = $discount['discount_value'];
    }
    
    // Make sure discount doesn't exceed original amount
    if ($discount_amount > $amount) {
        $discount_amount = $amount;
    }
    
    return $discount_amount;
}

// Function to update discount usage
function update_discount_usage($code_id) {
    global $conn;
    
    $sql = "UPDATE discount_codes SET used_count = used_count + 1 WHERE code_id = $code_id";
    return mysqli_query($conn, $sql);
}

// Function to check if user is a verified student
function is_verified_student($user_id) {
    global $conn;
    
    $sql = "SELECT is_student FROM users WHERE user_id = $user_id";
    $user = get_record($conn, $sql);
    
    return $user && $user['is_student'] == 1;
}

// Function to get recent posts
function get_recent_posts($limit = 5) {
    global $conn;
    
    $sql = "SELECT p.*, u.full_name, u.avatar FROM posts p
            JOIN users u ON p.artist_id = u.user_id
            ORDER BY p.created_at DESC
            LIMIT $limit";
    
    return get_records($conn, $sql);
}

// Function to get top-rated artists
function get_top_artists($limit = 5) {
    global $conn;
    
    $sql = "SELECT u.user_id, u.full_name, u.avatar, ap.avg_rating, ap.total_bookings 
            FROM users u
            JOIN artist_profiles ap ON u.user_id = ap.user_id
            WHERE u.role_id = 2 AND u.status = 'active'
            ORDER BY ap.avg_rating DESC, ap.total_bookings DESC
            LIMIT $limit";
    
    return get_records($conn, $sql);
}

// Function to get service categories
function get_service_categories() {
    global $conn;
    
    $sql = "SELECT * FROM service_categories ORDER BY category_name";
    return get_records($conn, $sql);
}

// Function to get artist services
function get_artist_services($artist_id) {
    global $conn;
    
    $sql = "SELECT s.*, c.category_name FROM services s
            JOIN service_categories c ON s.category_id = c.category_id
            WHERE s.artist_id = $artist_id
            ORDER BY s.price";
    
    return get_records($conn, $sql);
}

// Function to get artist availability
function get_artist_availability($artist_id) {
    global $conn;
    
    $sql = "SELECT * FROM artist_availability 
            WHERE artist_id = $artist_id 
            ORDER BY day_of_week, start_time";
    
    return get_records($conn, $sql);
}

// Function to check if artist is available at specific time
function is_artist_available($artist_id, $date, $time) {
    global $conn;
    
    // Get day of week from date (0 = Sunday, 1 = Monday, etc.)
    $day_of_week = date('w', strtotime($date));
    
    // Check if artist has availability for this day of week
    $sql = "SELECT * FROM artist_availability 
            WHERE artist_id = $artist_id 
            AND day_of_week = $day_of_week 
            AND is_available = 1
            AND '$time' BETWEEN start_time AND end_time";
    
    $availability = get_record($conn, $sql);
    
    if (!$availability) {
        return false;
    }
    
    // Check if artist already has booking at this time
    $sql = "SELECT * FROM bookings 
            WHERE artist_id = $artist_id 
            AND booking_date = '$date' 
            AND booking_time = '$time'
            AND status_id NOT IN (5, 6)"; // Exclude cancelled and no_show bookings
    
    $booking = get_record($conn, $sql);
    
    return !$booking; // Available if no booking exists
}

// Function to update artist rating after a review
function update_artist_rating($artist_id) {
    global $conn;
    
    $sql = "SELECT AVG(r.rating) as avg_rating 
            FROM reviews r
            JOIN bookings b ON r.booking_id = b.booking_id
            WHERE b.artist_id = $artist_id";
    
    $result = get_record($conn, $sql);
    $avg_rating = $result['avg_rating'] ?: 0;
    
    $sql = "UPDATE artist_profiles SET avg_rating = $avg_rating WHERE user_id = $artist_id";
    return mysqli_query($conn, $sql);
}

// Function to update booking status
function update_booking_status($booking_id, $status_id) {
    global $conn;
    
    $sql = "UPDATE bookings SET status_id = $status_id WHERE booking_id = $booking_id";
    return mysqli_query($conn, $sql);
}