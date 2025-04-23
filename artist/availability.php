<?php
// artist/availability.php - Manage artist availability/working hours

// Set page title
$page_title = "Manage Availability";

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Loop through each day of the week
        for ($day = 0; $day <= 6; $day++) {
            $is_available = isset($_POST["available_$day"]) ? 1 : 0;
            $start_time = sanitize_input($conn, $_POST["start_time_$day"] ?? '00:00:00');
            $end_time = sanitize_input($conn, $_POST["end_time_$day"] ?? '00:00:00');
            
            // Check if record already exists
            $existing = get_record($conn, "SELECT * FROM artist_availability WHERE artist_id = $artist_id AND day_of_week = $day");
            
            if ($existing) {
                // Update existing record
                update_record($conn, 'artist_availability', [
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'is_available' => $is_available
                ], "availability_id = {$existing['availability_id']}");
            } else {
                // Insert new record
                insert_record($conn, 'artist_availability', [
                    'artist_id' => $artist_id,
                    'day_of_week' => $day,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'is_available' => $is_available
                ]);
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        set_success_message("Availability updated successfully!");
        redirect('/beautyclick/artist/availability.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        
        set_error_message("Failed to update availability: " . $e->getMessage());
    }
}

// Get current availability
$availability = get_records($conn, "SELECT * FROM artist_availability WHERE artist_id = $artist_id ORDER BY day_of_week");
$avail_by_day = [];
foreach ($availability as $avail) {
    $avail_by_day[$avail['day_of_week']] = $avail;
}

// Include header
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Manage Your Availability</h5>
                </div>
                <div class="card-body">
                    <p class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Set your regular working hours. Clients can book only during these hours.
                    </p>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Day</th>
                                        <th>Available</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                    
                                    foreach ($days as $index => $day):
                                        $day_avail = isset($avail_by_day[$index]) ? $avail_by_day[$index] : null;
                                        $is_available = $day_avail ? $day_avail['is_available'] : 0;
                                        $start_time = $day_avail ? $day_avail['start_time'] : '09:00:00';
                                        $end_time = $day_avail ? $day_avail['end_time'] : '17:00:00';
                                    ?>
                                    <tr>
                                        <td><?php echo $day; ?></td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input day-checkbox" type="checkbox" 
                                                       id="available_<?php echo $index; ?>" 
                                                       name="available_<?php echo $index; ?>" 
                                                       <?php echo $is_available ? 'checked' : ''; ?>
                                                       data-day="<?php echo $index; ?>">
                                                <label class="form-check-label" for="available_<?php echo $index; ?>">
                                                    <?php echo $is_available ? 'Open' : 'Closed'; ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="time" class="form-control time-input-<?php echo $index; ?>" 
                                                   name="start_time_<?php echo $index; ?>" 
                                                   value="<?php echo substr($start_time, 0, 5); ?>"
                                                   <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        </td>
                                        <td>
                                            <input type="time" class="form-control time-input-<?php echo $index; ?>" 
                                                   name="end_time_<?php echo $index; ?>" 
                                                   value="<?php echo substr($end_time, 0, 5); ?>"
                                                   <?php echo !$is_available ? 'disabled' : ''; ?>>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-end mt-3">
                            <a href="/beautyclick/artist/profile.php" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i>Back to Profile
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Availability
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dayCheckboxes = document.querySelectorAll('.day-checkbox');
    
    dayCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const dayId = this.dataset.day;
            const timeInputs = document.querySelectorAll(`.time-input-${dayId}`);
            const label = this.nextElementSibling;
            
            timeInputs.forEach(function(input) {
                input.disabled = !checkbox.checked;
            });
            
            label.textContent = checkbox.checked ? 'Open' : 'Closed';
        });
    });
});
</script>

<?php
// Include footer
include_once $_SERVER['DOCUMENT_ROOT'] . '/beautyclick/includes/footer.php';
?>