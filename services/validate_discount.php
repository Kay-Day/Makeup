<?php
// services/validate_discount.php - API endpoint to validate discount codes

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get parameters
$code = sanitize_input($conn, $_POST['code'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);

if (empty($code) || $amount <= 0) {
    echo json_encode(['valid' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Check discount code in database
$today = date('Y-m-d');
$sql = "SELECT * FROM discount_codes 
        WHERE code = '$code' 
        AND is_active = 1 
        AND start_date <= '$today' 
        AND end_date >= '$today' 
        AND (usage_limit IS NULL OR used_count < usage_limit)
        AND min_purchase <= $amount";

$discount = get_record($conn, $sql);

if (!$discount) {
    echo json_encode([
        'valid' => false, 
        'message' => 'Invalid discount code or minimum purchase amount not met'
    ]);
    exit;
}

// Calculate discount amount
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

// Return successful response
echo json_encode([
    'valid' => true,
    'discount_amount' => $discount_amount,
    'message' => 'Discount code applied successfully',
    'code_id' => $discount['code_id']
]);