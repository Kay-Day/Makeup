<?php
// admin/discounts.php - Discount codes management

// Set page title
$page_title = "Manage Discount Codes";

// Include functions file
require_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/functions.php';

// Check if user is logged in and has admin role
if (!is_logged_in() || !user_has_role('admin')) {
    set_error_message("Access denied. Please login as an administrator.");
    redirect('/beautyclick/auth/login.php');
    exit;
}

// Get action type
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$code_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle delete action
if ($action === 'delete' && $code_id > 0) {
    if (delete_record($conn, 'discount_codes', "code_id = $code_id")) {
        set_success_message("Discount code deleted successfully!");
    } else {
        set_error_message("Failed to delete discount code.");
    }
    redirect('/beautyclick/admin/discounts.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $code = sanitize_input($conn, $_POST['code'] ?? '');
    $discount_type = sanitize_input($conn, $_POST['discount_type'] ?? '');
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_purchase = floatval($_POST['min_purchase'] ?? 0);
    $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
    $start_date = sanitize_input($conn, $_POST['start_date'] ?? '');
    $end_date = sanitize_input($conn, $_POST['end_date'] ?? '');
    $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    $errors = [];
    
    if (empty($code)) {
        $errors[] = "Code is required.";
    }
    
    if (empty($discount_type) || !in_array($discount_type, ['percentage', 'fixed'])) {
        $errors[] = "Valid discount type is required.";
    }
    
    if ($discount_value <= 0) {
        $errors[] = "Discount value must be greater than 0.";
    }
    
    if ($discount_type === 'percentage' && $discount_value > 100) {
        $errors[] = "Percentage discount cannot exceed 100%.";
    }
    
    if (empty($start_date) || empty($end_date)) {
        $errors[] = "Start date and end date are required.";
    } elseif ($start_date > $end_date) {
        $errors[] = "End date must be after start date.";
    }
    
    // Check if code already exists (for add operation)
    if ($action === 'add') {
        $check_code = get_record($conn, "SELECT code_id FROM discount_codes WHERE code = '$code'");
        if ($check_code) {
            $errors[] = "Discount code already exists. Please use a different code.";
        }
    }
    
    // If no errors, process the data
    if (empty($errors)) {
        // Prepare discount data
        $discount_data = [
            'code' => $code,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'min_purchase' => $min_purchase,
            'max_discount' => $max_discount,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'usage_limit' => $usage_limit,
            'is_active' => $is_active
        ];
        
        if ($action === 'add') {
            // Add new discount code
            if (insert_record($conn, 'discount_codes', $discount_data)) {
                set_success_message("Discount code added successfully!");
                redirect('/beautyclick/admin/discounts.php');
                exit;
            } else {
                set_error_message("Failed to add discount code.");
            }
        } elseif ($action === 'edit' && $code_id > 0) {
            // Update existing discount code
            if (update_record($conn, 'discount_codes', $discount_data, "code_id = $code_id")) {
                set_success_message("Discount code updated successfully!");
                redirect('/beautyclick/admin/discounts.php');
                exit;
            } else {
                set_error_message("Failed to update discount code.");
            }
        }
    } else {
        set_error_message(implode("<br>", $errors));
    }
}

// Get discount code data for editing
$discount = [];
if (($action === 'edit' || $action === 'view') && $code_id > 0) {
    $discount = get_record($conn, "SELECT * FROM discount_codes WHERE code_id = $code_id");
    if (!$discount) {
        set_error_message("Discount code not found.");
        redirect('/beautyclick/admin/discounts.php');
        exit;
    }
}

// Get all discount codes for listing
$discount_codes = [];
if ($action === 'list') {
    $discount_codes = get_records($conn, "SELECT * FROM discount_codes ORDER BY created_at DESC");
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-percent me-2"></i>
                    <?php echo $action === 'add' ? 'Add New Discount Code' : 
                         ($action === 'edit' ? 'Edit Discount Code' : 
                         ($action === 'view' ? 'Discount Code Details' : 'Manage Discount Codes')); ?>
                </h2>
                <?php if ($action === 'list'): ?>
                <a href="/beautyclick/admin/discounts.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Discount
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?action=' . $action . ($code_id ? '&id=' . $code_id : '')); ?>" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="code" class="form-label">Discount Code *</label>
                                <input type="text" class="form-control" id="code" name="code" 
                                       value="<?php echo $discount['code'] ?? ''; ?>" required>
                                <div class="form-text">Code is case-insensitive (e.g., WELCOME10, Summer50)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="discount_type" class="form-label">Discount Type *</label>
                                <select class="form-select" id="discount_type" name="discount_type" required>
                                    <option value="">Select Type</option>
                                    <option value="percentage" <?php echo (isset($discount['discount_type']) && $discount['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                    <option value="fixed" <?php echo (isset($discount['discount_type']) && $discount['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount (VND)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="discount_value" class="form-label">Discount Value *</label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value" 
                                       value="<?php echo $discount['discount_value'] ?? ''; ?>" step="0.01" min="0" required>
                                <div class="form-text">For percentage: 10 = 10%, For fixed: amount in VND</div>
                            </div>
                            <div class="col-md-6">
                                <label for="min_purchase" class="form-label">Minimum Purchase Amount</label>
                                <input type="number" class="form-control" id="min_purchase" name="min_purchase" 
                                       value="<?php echo $discount['min_purchase'] ?? 0; ?>" min="0">
                                <div class="form-text">Minimum order amount required to use this code (VND)</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="max_discount" class="form-label">Maximum Discount (for %)</label>
                                <input type="number" class="form-control" id="max_discount" name="max_discount" 
                                       value="<?php echo $discount['max_discount'] ?? ''; ?>" min="0">
                                <div class="form-text">Maximum discount amount for percentage discounts (VND)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="usage_limit" class="form-label">Usage Limit</label>
                                <input type="number" class="form-control" id="usage_limit" name="usage_limit" 
                                       value="<?php echo $discount['usage_limit'] ?? ''; ?>" min="1">
                                <div class="form-text">Maximum number of times this code can be used (leave empty for unlimited)</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $discount['start_date'] ?? date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date *</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $discount['end_date'] ?? date('Y-m-d', strtotime('+30 days')); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?php echo (!isset($discount['is_active']) || $discount['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <a href="/beautyclick/admin/discounts.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?php echo $action === 'add' ? 'Add Discount Code' : 'Update Discount Code'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($action === 'view'): ?>
    <!-- View Discount Details -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%;">Code:</th>
                            <td><?php echo $discount['code']; ?></td>
                        </tr>
                        <tr>
                            <th>Discount Type:</th>
                            <td><?php echo $discount['discount_type'] === 'percentage' ? 'Percentage (%)' : 'Fixed Amount (VND)'; ?></td>
                        </tr>
                        <tr>
                            <th>Discount Value:</th>
                            <td>
                                <?php echo $discount['discount_value']; ?>
                                <?php echo $discount['discount_type'] === 'percentage' ? '%' : ' VND'; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Minimum Purchase:</th>
                            <td><?php echo format_currency($discount['min_purchase']); ?></td>
                        </tr>
                        <?php if ($discount['discount_type'] === 'percentage' && !empty($discount['max_discount'])): ?>
                        <tr>
                            <th>Maximum Discount:</th>
                            <td><?php echo format_currency($discount['max_discount']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Valid Period:</th>
                            <td>
                                <?php echo format_date($discount['start_date']); ?> to 
                                <?php echo format_date($discount['end_date']); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Usage Limit:</th>
                            <td>
                                <?php echo $discount['usage_limit'] ? $discount['usage_limit'] : 'Unlimited'; ?>
                                <?php if ($discount['usage_limit']): ?>
                                    (Used: <?php echo $discount['used_count']; ?>)
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php if ($discount['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td><?php echo date('M d, Y H:i', strtotime($discount['created_at'])); ?></td>
                        </tr>
                    </table>
                    
                    <div class="text-end mt-3">
                        <a href="/beautyclick/admin/discounts.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i>Back to List
                        </a>
                        <a href="/beautyclick/admin/discounts.php?action=edit&id=<?php echo $discount['code_id']; ?>" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <a href="/beautyclick/admin/discounts.php?action=delete&id=<?php echo $discount['code_id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to delete this discount code?');">
                            <i class="fas fa-trash me-1"></i>Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- List Discount Codes -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Min. Purchase</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($discount_codes) > 0): ?>
                            <?php foreach ($discount_codes as $code): ?>
                            <tr>
                                <td><?php echo $code['code']; ?></td>
                                <td><?php echo $code['discount_type'] === 'percentage' ? 'Percentage' : 'Fixed'; ?></td>
                                <td>
                                    <?php echo $code['discount_value']; ?>
                                    <?php echo $code['discount_type'] === 'percentage' ? '%' : ' VND'; ?>
                                    <?php if ($code['discount_type'] === 'percentage' && !empty($code['max_discount'])): ?>
                                        <span class="small text-muted">(Max: <?php echo format_currency($code['max_discount']); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo format_currency($code['min_purchase']); ?></td>
                                <td>
                                    <small>
                                        <?php echo format_date($code['start_date']); ?> to <br>
                                        <?php echo format_date($code['end_date']); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php 
                                    $today = date('Y-m-d');
                                    $status = 'Active';
                                    $status_class = 'bg-success';
                                    
                                    if (!$code['is_active']) {
                                        $status = 'Inactive';
                                        $status_class = 'bg-danger';
                                    } elseif ($code['start_date'] > $today) {
                                        $status = 'Scheduled';
                                        $status_class = 'bg-info';
                                    } elseif ($code['end_date'] < $today) {
                                        $status = 'Expired';
                                        $status_class = 'bg-secondary';
                                    } elseif ($code['usage_limit'] && $code['used_count'] >= $code['usage_limit']) {
                                        $status = 'Exhausted';
                                        $status_class = 'bg-warning text-dark';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status; ?></span>
                                </td>
                                <td>
                                    <?php echo $code['used_count']; ?><?php echo $code['usage_limit'] ? '/' . $code['usage_limit'] : ''; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="/beautyclick/admin/discounts.php?action=view&id=<?php echo $code['code_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/beautyclick/admin/discounts.php?action=edit&id=<?php echo $code['code_id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="/beautyclick/admin/discounts.php?action=delete&id=<?php echo $code['code_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this discount code?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No discount codes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>