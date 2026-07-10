<?php
/**
 * Update Tracking Status Console
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Include header layout
require_once 'includes/header.php';

// Force login check
check_login();

// Fetch all trips for selector dropdown
$all_trips = get_all_trips();

// Get selected trip ID
$selected_trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;
$trip = null;
$updates = [];
$latest_update = null;

if ($selected_trip_id > 0) {
    if ($db_connected && $conn) {
        $trip = getTripById($selected_trip_id);
        if ($trip) {
            $updates = getTrackingHistory($selected_trip_id);
            if (!empty($updates)) {
                $latest_update = $updates[0];
            }
        }
    } else {
        $trip = get_trip_by_id($selected_trip_id);
        if ($trip) {
            $updates = get_tracking_updates($selected_trip_id);
            if (!empty($updates)) {
                $latest_update = $updates[0];
            }
        }
    }
}

$error = '';
$success = isset($_GET['msg']) ? sanitize_input($_GET['msg']) : '';

// Process POST form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $trip_id = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';
        $location = isset($_POST['location']) ? sanitize_input($_POST['location']) : '';
        $description = isset($_POST['description']) ? sanitize_input($_POST['description']) : '';
        
        if (empty($trip_id) || empty($status) || empty($location) || empty($description)) {
            $error = 'All fields are required to log a tracking update.';
        } else {
            // Call insert_tracking_update helper (handles both DB and Simulation)
            $ok = insert_tracking_update($trip_id, $status, $location, $description);
            if ($ok) {
                header("Location: update_tracking.php?trip_id=$trip_id&msg=Tracking+update+added+successfully!&type=success");
                exit();
            } else {
                $error = 'Failed to record tracking update.';
            }
        }
    }
}
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Trip Tracking Updates Console</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Add logs, update current locations, and coordinate delivery statuses</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="trips.php" class="btn btn-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Back to Trips</span>
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="login-error" style="text-align: left; max-width: 100%; margin-bottom: 1.5rem;">
        <i data-lucide="alert-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #a7f3d0; padding: 14px 16px; border-radius: 10px; margin-bottom: 1.5rem;">
        <i data-lucide="check-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<!-- Step 1: Select Active Voyage -->
<div class="dashboard-panel" style="margin-bottom: 2rem;">
    <h3 class="panel-title" style="margin-bottom: 1rem;">Select Active Trip Voyage</h3>
    <form action="update_tracking.php" method="GET" id="trip-selector-form" style="display: flex; gap: 1rem; align-items: flex-end;">
        <div class="form-group" style="flex-grow: 1;">
            <label for="trip_id" class="form-label">Active Dispatches</label>
            <select id="trip_id" name="trip_id" onchange="document.getElementById('trip-selector-form').submit();" class="form-control">
                <option value="">-- Choose active trip to update --</option>
                <?php foreach ($all_trips as $t): ?>
                    <option value="<?php echo htmlspecialchars($t['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected_trip_id === intval($t['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($t['trip_code'] . ' (' . explode(',', $t['origin'])[0] . ' -> ' . explode(',', $t['destination'])[0] . ')', ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<?php if ($trip): ?>
    <?php 
    // Resolve current status and colors
    $current_status = $latest_update ? $latest_update['status'] : 'Order Received';
    $badge_class = 'status-' . strtolower(str_replace(' ', '_', $current_status));
    ?>

    <!-- Prominent Status Overview Banner -->
    <div class="dashboard-panel" style="margin-bottom: 2rem; border-left: 4px solid var(--status-color); background-image: linear-gradient(to right, rgba(255,255,255,0.01), transparent);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="form-label" style="font-size: 0.7rem;">Prominent Status Summary</span>
                <h3 style="color: white; font-weight: 800; font-size: 1.5rem; margin-top: 0.25rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span><?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px; color: var(--text-muted);"></i>
                    <span class="badge <?php echo $badge_class; ?>" style="font-size: 0.85rem; padding: 0.3rem 0.75rem;">
                        <?php echo htmlspecialchars($current_status, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </h3>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-top: 0.5rem;">
                    Location: <strong style="color: white;"><?php echo htmlspecialchars($latest_update ? $latest_update['location'] : $trip['origin'], ENT_QUOTES, 'UTF-8'); ?></strong> | 
                    Driver: <strong style="color: white;"><?php echo htmlspecialchars(get_driver_name($trip['driver_id']), ENT_QUOTES, 'UTF-8'); ?></strong>
                </p>
            </div>
            <a href="track.php?code=<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                <i data-lucide="external-link"></i>
                <span>🔍 Track Public Portal</span>
            </a>
        </div>
    </div>

    <div class="dashboard-row" style="grid-template-columns: 1fr 1fr;">
        
        <!-- Left: Form Panel to Add Tracking Update -->
        <div class="dashboard-panel">
            <h3 class="panel-title" style="margin-bottom: 1.5rem;">Add Dispatch Tracking Update</h3>
            
            <form action="update_tracking.php?trip_id=<?php echo $selected_trip_id; ?>" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                
                <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip['id'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label for="status" class="form-label">Tracking Status *</label>
                    <select id="status" name="status" required class="form-control">
                        <option value="Order Received" <?php echo $current_status === 'Order Received' ? 'selected' : ''; ?>>Order Received</option>
                        <option value="Vehicle Assigned" <?php echo $current_status === 'Vehicle Assigned' ? 'selected' : ''; ?>>Vehicle Assigned</option>
                        <option value="Departing" <?php echo $current_status === 'Departing' ? 'selected' : ''; ?>>Departing</option>
                        <option value="In Transit" <?php echo $current_status === 'In Transit' ? 'selected' : ''; ?>>In Transit</option>
                        <option value="Arrived" <?php echo $current_status === 'Arrived' ? 'selected' : ''; ?>>Arrived</option>
                        <option value="Delivered" <?php echo $current_status === 'Delivered' ? 'selected' : ''; ?>>Delivered (Releases Vehicle & Driver)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="location" class="form-label">Current Transit Location *</label>
                    <input type="text" id="location" name="location" required placeholder="e.g. Nsawam Toll Booth, Eastern Region" class="form-control">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Timeline Details / Description *</label>
                    <textarea id="description" name="description" required rows="4" placeholder="Describe delay alerts, fuel stops, traffic conditions, or POD signoff notes..." class="form-control"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="justify-content: center; padding: 0.8rem; margin-top: 0.5rem;">
                    <i data-lucide="plus-circle"></i>
                    <span>Log Update Entry</span>
                </button>
            </form>
        </div>

        <!-- Right: Previous Updates Timeline List -->
        <div class="dashboard-panel">
            <h3 class="panel-title" style="margin-bottom: 1.5rem;">Voyage Updates History</h3>
            
            <?php if (empty($updates)): ?>
                <div style="text-align: center; padding: 3rem; border: 1px dashed var(--border-color); border-radius: var(--radius-md);">
                    <p style="color: var(--text-secondary); font-size: 0.875rem;">No updates logged for this voyage yet.</p>
                </div>
            <?php else: ?>
                <div class="timeline-flow">
                    <?php foreach ($updates as $update): ?>
                        <?php 
                        $step_class = 'status-' . strtolower(str_replace(' ', '_', $update['status']));
                        
                        $timeline_icon = 'circle';
                        if ($update['status'] === 'Order Received') $timeline_icon = 'clipboard-list';
                        elseif ($update['status'] === 'Vehicle Assigned') $timeline_icon = 'truck';
                        elseif ($update['status'] === 'Departing') $timeline_icon = 'log-out';
                        elseif ($update['status'] === 'In Transit') $timeline_icon = 'navigation';
                        elseif ($update['status'] === 'Arrived') $timeline_icon = 'map-pin';
                        elseif ($update['status'] === 'Delivered') $timeline_icon = 'check-circle2';
                        ?>
                        <div class="timeline-step <?php echo $step_class; ?>">
                            <div class="timeline-dot">
                                <i data-lucide="<?php echo $timeline_icon; ?>"></i>
                            </div>
                            <div class="timeline-info" style="padding: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                    <strong style="color: var(--status-color); font-size: 0.9rem;"><?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span style="font-size: 0.7rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars(date('H:i A, M d', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.75rem; color: white; font-weight: 600; margin-top: 0.25rem;">
                                    <i data-lucide="map-pin" style="width: 10px; height: 10px; display: inline-block; vertical-align: middle; color: var(--text-secondary);"></i>
                                    <span><?php echo htmlspecialchars($update['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <p class="timeline-desc" style="font-size: 0.8rem; margin-top: 0.4rem;"><?php echo htmlspecialchars($update['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
<?php else: ?>
    <!-- Selector Empty State -->
    <div style="text-align: center; padding: 4rem 2rem; border: 1px dashed var(--border-color); border-radius: var(--radius-lg); background-color: rgba(255,255,255,0.01);">
        <i data-lucide="activity" style="width: 48px; height: 48px; color: var(--text-muted); margin-bottom: 1rem;"></i>
        <h3 style="color: white; font-weight: 700; margin-bottom: 0.5rem;">Select a Voyage</h3>
        <p style="color: var(--text-secondary); font-size: 0.875rem; max-width: 320px; margin: 0 auto;">Select an active shipment trip from the dropdown above to manage its route logs.</p>
    </div>
<?php endif; ?>

<?php 
// Include footer layout
require_once 'includes/footer.php'; 
?>
