<?php
/**
 * Modify Dispatch Order (Edit Trip)
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authorization
check_login();

$error = '';
$success = '';

// Get the trip ID from the GET parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: trips.php");
    exit();
}

// Fetch the trip details
$trip = null;
if ($db_connected && $conn) {
    $trip = getTripById($id);
} else {
    $trip = get_trip_by_id($id);
}

if (!$trip) {
    header("Location: trips.php");
    exit();
}

// Store the old vehicle and driver ID to check if they have changed
$old_vehicle_id = isset($trip['vehicle_id']) ? intval($trip['vehicle_id']) : null;
$old_driver_id = isset($trip['driver_id']) ? intval($trip['driver_id']) : null;
$old_status = isset($trip['status']) ? $trip['status'] : 'pending';

// Process POST form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Sanitize and validate inputs
        $trip_date = sanitize_input($_POST['trip_date']);
        $origin = sanitize_input($_POST['origin']);
        $destination = sanitize_input($_POST['destination']);
        $purpose = sanitize_input($_POST['purpose']);
        $status = sanitize_input($_POST['status']);
        $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
        $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
        
        if (empty($trip_date) || empty($origin) || empty($destination) || empty($status)) {
            $error = 'Trip date, origin, destination, and status are required.';
        } else {
            if ($db_connected && $conn) {
                // DB Mode: Execute transaction
                mysqli_begin_transaction($conn);
                $transaction_success = true;
                
                // Step 1: Update trip record
                $update_query = "UPDATE trips SET trip_date = ?, origin = ?, destination = ?, purpose = ?, vehicle_id = ?, driver_id = ?, status = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $update_query)) {
                    mysqli_stmt_bind_param($stmt, "ssssiisi", $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $status, $id);
                    if (!mysqli_stmt_execute($stmt)) {
                        $transaction_success = false;
                        $error = "Failed to update trip record.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $transaction_success = false;
                    $error = "Statement preparation failed.";
                }
                
                // Step 2: Handle vehicle status changes
                if ($transaction_success) {
                    // If vehicle changed, set old vehicle back to available
                    if ($old_vehicle_id && $old_vehicle_id !== $vehicle_id) {
                        $free_v = "UPDATE vehicles SET status = 'available' WHERE id = ?";
                        if ($stmt_fv = mysqli_prepare($conn, $free_v)) {
                            mysqli_stmt_bind_param($stmt_fv, "i", $old_vehicle_id);
                            mysqli_stmt_execute($stmt_fv);
                            mysqli_stmt_close($stmt_fv);
                        }
                    }
                    
                    // If trip status is completed/cancelled, set vehicle back to available
                    if ($status === 'completed' || $status === 'cancelled') {
                        if ($vehicle_id) {
                            $free_v = "UPDATE vehicles SET status = 'available' WHERE id = ?";
                            if ($stmt_fv = mysqli_prepare($conn, $free_v)) {
                                mysqli_stmt_bind_param($stmt_fv, "i", $vehicle_id);
                                mysqli_stmt_execute($stmt_fv);
                                mysqli_stmt_close($stmt_fv);
                            }
                        }
                    } else {
                        // Mark assigned vehicle as assigned
                        if ($vehicle_id) {
                            $assign_v = "UPDATE vehicles SET status = 'assigned' WHERE id = ?";
                            if ($stmt_av = mysqli_prepare($conn, $assign_v)) {
                                mysqli_stmt_bind_param($stmt_av, "i", $vehicle_id);
                                mysqli_stmt_execute($stmt_av);
                                mysqli_stmt_close($stmt_av);
                            }
                        }
                    }
                }
                
                // Step 3: Handle driver status changes
                if ($transaction_success) {
                    // If driver changed, set old driver back to available
                    if ($old_driver_id && $old_driver_id !== $driver_id) {
                        $free_d = "UPDATE drivers SET status = 'available' WHERE id = ?";
                        if ($stmt_fd = mysqli_prepare($conn, $free_d)) {
                            mysqli_stmt_bind_param($stmt_fd, "i", $old_driver_id);
                            mysqli_stmt_execute($stmt_fd);
                            mysqli_stmt_close($stmt_fd);
                        }
                    }
                    
                    // If trip status is completed/cancelled, set driver back to available
                    if ($status === 'completed' || $status === 'cancelled') {
                        if ($driver_id) {
                            $free_d = "UPDATE drivers SET status = 'available' WHERE id = ?";
                            if ($stmt_fd = mysqli_prepare($conn, $free_d)) {
                                mysqli_stmt_bind_param($stmt_fd, "i", $driver_id);
                                mysqli_stmt_execute($stmt_fd);
                                mysqli_stmt_close($stmt_fd);
                            }
                        }
                    } else {
                        // Mark assigned driver as on_trip
                        if ($driver_id) {
                            $assign_d = "UPDATE drivers SET status = 'on_trip' WHERE id = ?";
                            if ($stmt_ad = mysqli_prepare($conn, $assign_d)) {
                                mysqli_stmt_bind_param($stmt_ad, "i", $driver_id);
                                mysqli_stmt_execute($stmt_ad);
                                mysqli_stmt_close($stmt_ad);
                            }
                        }
                    }
                }
                
                // Commit/Rollback
                if ($transaction_success) {
                    mysqli_commit($conn);
                    $success = "Trip schedule updated successfully!";
                    // Reload data
                    $trip = getTripById($id);
                    $old_vehicle_id = isset($trip['vehicle_id']) ? intval($trip['vehicle_id']) : null;
                    $old_driver_id = isset($trip['driver_id']) ? intval($trip['driver_id']) : null;
                    $old_status = isset($trip['status']) ? $trip['status'] : 'pending';
                } else {
                    mysqli_rollback($conn);
                }
            } else {
                // Simulation Mode
                $success_sim = update_trip($id, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $status);
                if ($success_sim) {
                    $success = "Trip schedule updated successfully!";
                    // Reload mock data
                    $trip = get_trip_by_id($id);
                    $old_vehicle_id = isset($trip['vehicle_id']) ? intval($trip['vehicle_id']) : null;
                    $old_driver_id = isset($trip['driver_id']) ? intval($trip['driver_id']) : null;
                    $old_status = isset($trip['status']) ? $trip['status'] : 'pending';
                } else {
                    $error = 'Failed to update trip in simulation mode.';
                }
            }
        }
    }
}

// Fetch list of vehicles and drivers (available OR currently assigned to this trip)
$vehicles = [];
$drivers = [];

if ($db_connected && $conn) {
    // Vehicles query
    $vehicle_query = "SELECT id, vehicle_name, license_plate, capacity FROM vehicles WHERE status = 'available'";
    if ($old_vehicle_id) {
        $vehicle_query .= " OR id = " . intval($old_vehicle_id);
    }
    $vehicle_query .= " ORDER BY vehicle_name ASC";
    $res = mysqli_query($conn, $vehicle_query);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $vehicles[] = $row;
        }
        mysqli_free_result($res);
    }
    
    // Drivers query
    $driver_query = "SELECT id, full_name, license_number FROM drivers WHERE status = 'available'";
    if ($old_driver_id) {
        $driver_query .= " OR id = " . intval($old_driver_id);
    }
    $driver_query .= " ORDER BY full_name ASC";
    $res = mysqli_query($conn, $driver_query);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $drivers[] = $row;
        }
        mysqli_free_result($res);
    }
} else {
    // Simulation Mode Lists
    if (isset($_SESSION['mock_vehicles'])) {
        foreach ($_SESSION['mock_vehicles'] as $v) {
            if ($v['status'] === 'available' || intval($v['id']) === $old_vehicle_id) {
                $vehicles[] = $v;
            }
        }
    }
    if (isset($_SESSION['mock_drivers'])) {
        foreach ($_SESSION['mock_drivers'] as $d) {
            if ($d['status'] === 'available' || intval($d['id']) === $old_driver_id) {
                $drivers[] = $d;
            }
        }
    }
}

$page_title = "Edit Trip";
require_once 'includes/header.php';
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Modify Dispatch Order</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Update route destination details or change vehicle/driver bookings</p>
    </div>
    <a href="trips.php" class="btn btn-secondary">
        <i data-lucide="arrow-left"></i>
        <span>Back to Directory</span>
    </a>
</div>

<div class="form-container">
    <?php if (!empty($error)): ?>
        <div class="login-error" style="text-align: left; margin-bottom: 1.5rem;">
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

    <form action="edit_trip.php?id=<?php echo $id; ?>" method="POST">
        <!-- Hidden CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="trip_date" class="form-label">Trip Schedule Date *</label>
                <input type="date" id="trip_date" name="trip_date" required class="form-control" value="<?php echo htmlspecialchars($trip['trip_date'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            
            <div class="form-group">
                <label for="trip_code_dummy" class="form-label">Trip Code</label>
                <input type="text" id="trip_code_dummy" disabled class="form-control" style="opacity: 0.6; font-family: monospace; font-weight: 700; color: var(--accent-blue);" value="<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="origin" class="form-label">Starting Origin Address *</label>
                <input type="text" id="origin" name="origin" required class="form-control" value="<?php echo htmlspecialchars($trip['origin'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="destination" class="form-label">Ending Destination Address *</label>
                <input type="text" id="destination" name="destination" required class="form-control" value="<?php echo htmlspecialchars($trip['destination'], ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="vehicle_id" class="form-label">Assigned Vehicle</label>
                <select id="vehicle_id" name="vehicle_id" class="form-control">
                    <option value="">-- No vehicle assignment (Hold) --</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo intval($v['id']) === $old_vehicle_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['vehicle_name'] . ' (' . $v['license_plate'] . ') - Cap: ' . number_format($v['capacity']) . ' lbs', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="driver_id" class="form-label">Assigned Driver</label>
                <select id="driver_id" name="driver_id" class="form-control">
                    <option value="">-- No driver assignment (Hold) --</option>
                    <?php foreach ($drivers as $d): ?>
                        <?php 
                        $driver_name = isset($d['full_name']) ? $d['full_name'] : (isset($d['fullname']) ? $d['fullname'] : '');
                        ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo intval($d['id']) === $old_driver_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($driver_name . ' (DL: ' . $d['license_number'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Dispatch Status *</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="pending" <?php echo $old_status === 'pending' ? 'selected' : ''; ?>>Pending approval</option>
                    <option value="approved" <?php echo $old_status === 'approved' ? 'selected' : ''; ?>>Approved / Staged</option>
                    <option value="in_transit" <?php echo $old_status === 'in_transit' ? 'selected' : ''; ?>>In transit</option>
                    <option value="completed" <?php echo $old_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $old_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="purpose" class="form-label">Transit Purpose / Cargo Description</label>
                <textarea id="purpose" name="purpose" rows="4" class="form-control"><?php echo htmlspecialchars($trip['purpose'] ?: '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="trips.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Save Changes</span>
            </button>
        </div>
    </form>
</div>

<!-- 📦 Tracking Status Section -->
<div class="form-container" style="margin-top: 30px; border-top: 2px solid var(--border-color); padding-top: 30px; max-width: 100%;">
    <h3 style="font-size: 1.1rem; font-weight: 700; color: white; margin-bottom: 20px;">📦 Tracking Status & Journey</h3>
    
    <?php
    $latest_status = getLatestTrackingStatus($id);
    $tracking_history = getTrackingHistory($id);
    ?>
    
    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <strong>Current Status:</strong>
        <?php if ($latest_status): ?>
            <?php
            $badge_color = isset($TRACKING_STATUSES[$latest_status]) ? $TRACKING_STATUSES[$latest_status] : 'gray';
            $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; // fallback purple
            if ($badge_color === 'blue') { $badge_style = "background: rgba(59, 130, 246, 0.15); color: #60a5fa;"; }
            elseif ($badge_color === 'purple') { $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; }
            elseif ($badge_color === 'orange') { $badge_style = "background: rgba(245, 158, 11, 0.15); color: #fbbf24;"; }
            elseif ($badge_color === 'yellow') { $badge_style = "background: rgba(234, 179, 8, 0.15); color: #fef08a;"; }
            elseif ($badge_color === 'green') { $badge_style = "background: rgba(16, 185, 129, 0.15); color: #34d399;"; }
            elseif ($badge_color === 'darkgreen') { $badge_style = "background: rgba(4, 120, 87, 0.2); color: #059669;"; }
            ?>
            <span class="badge" style="<?php echo $badge_style; ?> padding: 6px 12px; font-weight: 600; border-radius: 6px; text-transform: uppercase;">
                <?php echo htmlspecialchars($latest_status, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        <?php else: ?>
            <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: #94a3b8; padding: 6px 12px; font-weight: 600; border-radius: 6px; text-transform: uppercase;">
                No status updates yet
            </span>
        <?php endif; ?>
    </div>

    <!-- Timeline of updates -->
    <div class="tracking-timeline" style="position: relative; padding-left: 20px; border-left: 2px solid rgba(255,255,255,0.08); margin-bottom: 24px; margin-top: 20px;">
        <?php if (!empty($tracking_history)): ?>
            <?php foreach ($tracking_history as $update): ?>
                <div class="timeline-item" style="margin-bottom: 16px; position: relative;">
                    <div style="position: absolute; left: -26px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--primary-color); border: 2px solid #0f172a;"></div>
                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 2px;">
                        <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($update['location']): ?>
                            • <span style="color: var(--text-primary); font-weight: 500;"><?php echo htmlspecialchars($update['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-weight: 600; font-size: 0.95rem; margin-bottom: 2px; color: white;">
                        <?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php if ($update['description']): ?>
                        <div style="font-size: 0.9rem; color: var(--text-secondary);"><?php echo htmlspecialchars($update['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="font-size: 0.9rem; color: var(--text-secondary);">No tracking events logged for this trip.</p>
        <?php endif; ?>
    </div>

    <!-- Link to update tracking page -->
    <a href="update_tracking.php?trip_id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none; margin-top: 15px; width: 220px;">
        <i data-lucide="activity" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;"></i>
        <span>Update Status Log</span>
    </a>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
