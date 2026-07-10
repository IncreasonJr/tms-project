<?php
/**
 * Update Tracking Status
 * Transport Management System (TMS)
 */

// 1. Include config file (starts session and provides DB link)
require_once 'includes/config.php';

// 2. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// Determine active trip selection
$selected_trip_id = isset($_REQUEST['trip_id']) ? intval($_REQUEST['trip_id']) : 0;

// 3. Process form submission (POST method)
if (isset($_POST['update_tracking'])) {
    // Validate CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Sanitize inputs
        $trip_id = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        // Validation checks
        if ($trip_id <= 0) {
            $error = "Please select a valid trip to update.";
        } elseif (empty($status)) {
            $error = "Please select a tracking status.";
        } elseif (empty($location)) {
            $error = "Location is required.";
        } else {
            // Verify trip exists
            $trip_check = getTripById($trip_id);
            if (!$trip_check) {
                $error = "The selected trip does not exist in the database.";
            } else {
                // Call config function to write update
                if (addTrackingUpdate($trip_id, $status, $location, $description)) {
                    $success = "Tracking status updated successfully!";
                    $selected_trip_id = $trip_id; // Keep updated trip selected
                    
                    // Optional: If status is 'completed' or 'cancelled', we sync the main trip status too
                    if ($status === 'Arrived' || $status === 'Delivered') {
                        $update_trip_sql = "UPDATE trips SET status = 'completed' WHERE id = ?";
                        if ($stmt_sync = mysqli_prepare($conn, $update_trip_sql)) {
                            mysqli_stmt_bind_param($stmt_sync, "i", $trip_id);
                            mysqli_stmt_execute($stmt_sync);
                            mysqli_stmt_close($stmt_sync);
                        }
                    }
                } else {
                    $error = "Failed to write tracking update. Please try again.";
                }
            }
        }
    }
}

// Fetch all trips for selection dropdown
$trips_list = getAllTripsForDropdown();

// Fetch tracking data for the active selected trip
$trip_details = null;
$tracking_history = [];
if ($selected_trip_id > 0) {
    $trip_details = getTripById($selected_trip_id);
    if ($trip_details) {
        $tracking_history = getTrackingHistory($selected_trip_id);
    } else {
        $selected_trip_id = 0; // Reset if invalid
    }
}

$page_title = "Update Tracking";

// Include header layout (includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Layout -->
<div class="page-header">
    <h2 class="page-title">Update Tracking Status</h2>
    <a href="trips.php" class="btn btn-secondary">
        Back to Trips
    </a>
</div>

<!-- Main Container -->
<div class="form-container" style="display: grid; grid-template-columns: 1fr; gap: 30px; max-width: 1000px; margin: 0 auto; padding-bottom: 50px;">
    
    <!-- Two Column Grid for Desktop -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
        
        <!-- Column 1: Update Status Form -->
        <div class="content-card">
            <h3 class="panel-title" style="margin-bottom: 20px;">📦 Post Status Update</h3>
            
            <!-- User feedback messages -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="update_tracking.php" method="POST" autocomplete="off">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                
                <!-- Trip Dropdown Selection -->
                <div class="form-group">
                    <label for="trip_id" class="form-label">Select Delivery / Trip *</label>
                    <select id="trip_id" name="trip_id" class="form-control" required onchange="window.location.href='update_tracking.php?trip_id=' + this.value;">
                        <option value="">-- Choose Scheduled Trip --</option>
                        <?php foreach ($trips_list as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selected_trip_id === intval($t['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['display_text'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status Selection -->
                <div class="form-group">
                    <label for="status" class="form-label">Tracking Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="">-- Select Current Status --</option>
                        <?php foreach ($TRACKING_STATUSES as $status_name => $color): ?>
                            <option value="<?php echo htmlspecialchars($status_name, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($_POST['status']) && $_POST['status'] === $status_name) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status_name, ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Location Input -->
                <div class="form-group">
                    <label for="location" class="form-label">Current Location *</label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        class="form-control" 
                        placeholder="e.g. Accra Depot, Tema Interchange, Kumasi Warehouse" 
                        required
                        value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    >
                </div>

                <!-- Description Textarea -->
                <div class="form-group">
                    <label for="description" class="form-label">Status Description / Remarks</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-control" 
                        rows="3" 
                        placeholder="e.g. Loading goods, in transit with moderate traffic, arrived at destination depot"
                    ><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="update_tracking" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
                    Post Status Update
                </button>
            </form>
        </div>
        
        <!-- Column 2: Selected Trip Tracking History -->
        <div class="content-card">
            <h3 class="panel-title" style="margin-bottom: 20px;">📜 Tracking Log</h3>
            
            <?php if ($selected_trip_id > 0 && $trip_details): ?>
                
                <!-- Quick Trip Details Card -->
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 24px;">
                    <div style="font-family: monospace; font-size: 1.1rem; font-weight: 700; color: #8b5cf6; margin-bottom: 8px;">
                        <?php echo htmlspecialchars($trip_details['trip_code'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 4px;">
                        <strong>Route:</strong> <?php echo htmlspecialchars($trip_details['origin'], ENT_QUOTES, 'UTF-8'); ?> → <?php echo htmlspecialchars($trip_details['destination'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary);">
                        <strong>Date:</strong> <?php echo htmlspecialchars(date('M d, Y', strtotime($trip_details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>

                <!-- Tracking history timeline -->
                <div class="tracking-timeline" style="position: relative; padding-left: 20px; border-left: 2px solid rgba(255,255,255,0.08);">
                    <?php if (!empty($tracking_history)): ?>
                        <?php foreach ($tracking_history as $update): ?>
                            <div class="timeline-item" style="margin-bottom: 16px; position: relative;">
                                <div style="position: absolute; left: -26px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--primary-color); border: 2px solid #0f172a;"></div>
                                <div style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 2px;">
                                    <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($update['location']): ?>
                                        • <span style="color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($update['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 2px;">
                                    <?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php if ($update['description']): ?>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo htmlspecialchars($update['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="font-size: 0.9rem; color: var(--text-secondary);">No tracking log recorded yet.</p>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                    <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="currentColor" style="opacity: 0.3; margin-bottom: 16px;">
                        <path d="M480-120q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T120-480q0-75 28.5-140.5t77-114q48.5-48.5 114-77T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm0-80q116 0 198-82t82-198q0-116-82-198t-198-82q-116 0-198 82t-82 198q0 116 82 198t198 82Zm-40-120h80v-200h-80v200Zm0-280h80v-80h-80v80Z"/>
                    </svg>
                    <p>Please select a delivery trip from the dropdown on the left to view its tracking log.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
