<?php
/**
 * Schedule New Dispatch Order (Add Trip)
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authorization
check_login();

// Fetch only AVAILABLE vehicles and drivers for assignment validation
$available_vehicles = [];
$available_drivers = [];

if ($db_connected && $conn) {
    // Database query for available vehicles
    $sql = "SELECT id, vehicle_name, license_plate, capacity FROM vehicles WHERE status = 'available' ORDER BY vehicle_name ASC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $available_vehicles[] = $row;
        }
        mysqli_free_result($res);
    }
    
    // Database query for available drivers
    $sql = "SELECT id, full_name, license_number FROM drivers WHERE status = 'available' ORDER BY full_name ASC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $available_drivers[] = $row;
        }
        mysqli_free_result($res);
    }
} else {
    // Fallback simulation filtering
    if (isset($_SESSION['mock_vehicles'])) {
        foreach ($_SESSION['mock_vehicles'] as $v) {
            if ($v['status'] === 'available') {
                $available_vehicles[] = $v;
            }
        }
    }
    if (isset($_SESSION['mock_drivers'])) {
        foreach ($_SESSION['mock_drivers'] as $d) {
            if ($d['status'] === 'available') {
                $available_drivers[] = $d;
            }
        }
    }
}

$error = '';

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
        $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
        $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
        $created_by = $_SESSION['user_id'];
        
        if (empty($trip_date) || empty($origin) || empty($destination)) {
            $error = 'Trip date, origin, and destination are required.';
        } else {
            // Generate unique trip code using the config/functions helper
            $new_trip_code = generate_trip_code();
            
            if ($db_connected && $conn) {
                // DB Mode: Execute transaction
                mysqli_begin_transaction($conn);
                $transaction_success = true;
                
                // Step 1: Insert trip
                $insert_query = "INSERT INTO trips (trip_code, trip_date, origin, destination, purpose, vehicle_id, driver_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
                if ($stmt = mysqli_prepare($conn, $insert_query)) {
                    mysqli_stmt_bind_param($stmt, "sssssiis", $new_trip_code, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $created_by);
                    if (!mysqli_stmt_execute($stmt)) {
                        $transaction_success = false;
                        $error = "Failed to insert trip record.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $transaction_success = false;
                    $error = "Statement preparation failed.";
                }
                
                // Retrieve the new trip ID
                $new_trip_id = 0;
                if ($transaction_success) {
                    $new_trip_id = mysqli_insert_id($conn);
                    if ($new_trip_id <= 0) {
                        $transaction_success = false;
                        $error = "Failed to retrieve new trip ID.";
                    }
                }
                
                // Step 2: Update vehicle status to 'assigned'
                if ($transaction_success && $vehicle_id) {
                    $update_vehicle_query = "UPDATE vehicles SET status = 'assigned' WHERE id = ?";
                    if ($stmt_v = mysqli_prepare($conn, $update_vehicle_query)) {
                        mysqli_stmt_bind_param($stmt_v, "i", $vehicle_id);
                        if (!mysqli_stmt_execute($stmt_v)) {
                            $transaction_success = false;
                            $error = "Failed to update vehicle status.";
                        }
                        mysqli_stmt_close($stmt_v);
                    }
                }
                
                // Step 3: Update driver status to 'on_trip'
                if ($transaction_success && $driver_id) {
                    $update_driver_query = "UPDATE drivers SET status = 'on_trip' WHERE id = ?";
                    if ($stmt_d = mysqli_prepare($conn, $update_driver_query)) {
                        mysqli_stmt_bind_param($stmt_d, "i", $driver_id);
                        if (!mysqli_stmt_execute($stmt_d)) {
                            $transaction_success = false;
                            $error = "Failed to update driver status.";
                        }
                        mysqli_stmt_close($stmt_d);
                    }
                }
                
                // Step 4: Write initial tracking update
                if ($transaction_success) {
                    if (!addTrackingUpdate($new_trip_id, 'Order Received', $origin, 'Delivery order created and confirmed')) {
                        $transaction_success = false;
                        $error = "Failed to auto-create initial tracking log.";
                    }
                }
                
                // Commit/Rollback
                if ($transaction_success) {
                    mysqli_commit($conn);
                    header("Location: trips.php?msg=Trip+" . urlencode($new_trip_code) . "+scheduled+successfully!&type=success");
                    exit();
                } else {
                    mysqli_rollback($conn);
                }
            } else {
                // Simulation Mode
                $success = insert_trip($new_trip_code, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $created_by);
                if ($success) {
                    // Find the newly created mock trip ID
                    $new_id = empty($_SESSION['mock_trips']) ? 1 : max(array_keys($_SESSION['mock_trips']));
                    
                    // Add initial tracking update in mock session
                    addTrackingUpdate($new_id, 'Order Received', $origin, 'Delivery order created and confirmed');
                    
                    header("Location: trips.php?msg=Trip+" . urlencode($new_trip_code) . "+scheduled+successfully!&type=success");
                    exit();
                } else {
                    $error = 'Failed to schedule trip in simulation mode.';
                }
            }
        }
    }
}

$page_title = "Schedule Trip";
require_once 'includes/header.php';
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Schedule New Dispatch Order</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Book cargo routes and assign available vehicles/drivers</p>
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

    <form action="add_trip.php" method="POST">
        <!-- Hidden CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="trip_date" class="form-label">Trip Schedule Date *</label>
                <input type="date" id="trip_date" name="trip_date" required class="form-control" value="<?php echo isset($_POST['trip_date']) ? htmlspecialchars($_POST['trip_date'], ENT_QUOTES, 'UTF-8') : date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="trip_code_dummy" class="form-label">Trip Code (Auto-Generated)</label>
                <input type="text" id="trip_code_dummy" disabled class="form-control" style="opacity: 0.6; font-family: monospace; font-weight: 700; color: var(--accent-blue);" value="TRP-XXXXXX">
            </div>

            <div class="form-group">
                <label for="origin" class="form-label">Starting Origin Address *</label>
                <input type="text" id="origin" name="origin" required placeholder="e.g. Accra Central, Greater Accra" class="form-control" value="<?php echo isset($_POST['origin']) ? htmlspecialchars($_POST['origin'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="destination" class="form-label">Ending Destination Address *</label>
                <input type="text" id="destination" name="destination" required placeholder="e.g. Tamale Airport, Northern" class="form-control" value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="vehicle_id" class="form-label">Assign Available Vehicle</label>
                <select id="vehicle_id" name="vehicle_id" class="form-control">
                    <option value="">-- No vehicle assignment (Hold) --</option>
                    <?php foreach ($available_vehicles as $v): ?>
                        <option value="<?php echo htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($_POST['vehicle_id']) && intval($_POST['vehicle_id']) === intval($v['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['vehicle_name'] . ' (' . $v['license_plate'] . ') - Cap: ' . number_format($v['capacity']) . ' lbs', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($available_vehicles)): ?>
                    <span style="font-size: 0.7rem; color: var(--status-danger); margin-top: 0.25rem;">Warning: All fleet vehicles are currently busy.</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="driver_id" class="form-label">Assign Available Driver</label>
                <select id="driver_id" name="driver_id" class="form-control">
                    <option value="">-- No driver assignment (Hold) --</option>
                    <?php foreach ($available_drivers as $d): ?>
                        <?php 
                        $driver_name = isset($d['full_name']) ? $d['full_name'] : (isset($d['fullname']) ? $d['fullname'] : '');
                        ?>
                        <option value="<?php echo htmlspecialchars($d['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($_POST['driver_id']) && intval($_POST['driver_id']) === intval($d['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($driver_name . ' (DL: ' . $d['license_number'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($available_drivers)): ?>
                    <span style="font-size: 0.7rem; color: var(--status-danger); margin-top: 0.25rem;">Warning: All drivers are currently on trip.</span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="purpose" class="form-label">Transit Purpose / Cargo Description</label>
                <textarea id="purpose" name="purpose" rows="4" placeholder="Describe the cargo type, client details, weight, and delivery guidelines..." class="form-control"><?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="trips.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i data-lucide="check-square"></i>
                <span>Schedule Trip</span>
            </button>
        </div>
    </form>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
