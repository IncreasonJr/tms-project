<?php
/**
 * Create New Trip
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// 5. Auto-generate a unique trip code (e.g. TRIP-0001)
$new_trip_code = "TRIP-0001";
$code_query = "SELECT trip_code FROM trips ORDER BY id DESC LIMIT 1";
$code_result = mysqli_query($conn, $code_query);
if ($code_result && mysqli_num_rows($code_result) > 0) {
    $last_row = mysqli_fetch_assoc($code_result);
    $last_code = $last_row['trip_code'];
    // Parse the number part, increment and pad
    $num = intval(str_replace('TRIP-', '', $last_code));
    $new_trip_code = 'TRIP-' . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
}
if ($code_result) {
    mysqli_free_result($code_result);
}

// 6. Process form submission (POST method)
if (isset($_POST['add_trip'])) {
    
    // Validate CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Validate inputs
        $trip_date = isset($_POST['trip_date']) ? trim($_POST['trip_date']) : '';
        $origin = isset($_POST['origin']) ? trim($_POST['origin']) : '';
        $destination = isset($_POST['destination']) ? trim($_POST['destination']) : '';
        $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
        $vehicle_id = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : 0;
        $driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';

        // Simple validation
        if (empty($trip_date) || empty($origin) || empty($destination) || $vehicle_id <= 0 || $driver_id <= 0) {
            $error = "Date, Origin, Destination, Vehicle, and Driver are required fields.";
        } else {
            // Check if vehicle exists
            $v_check = false;
            $v_check_query = "SELECT id FROM vehicles WHERE id = ? LIMIT 1";
            if ($v_stmt = mysqli_prepare($conn, $v_check_query)) {
                mysqli_stmt_bind_param($v_stmt, "i", $vehicle_id);
                if (mysqli_stmt_execute($v_stmt)) {
                    mysqli_stmt_store_result($v_stmt);
                    if (mysqli_stmt_num_rows($v_stmt) > 0) {
                        $v_check = true;
                    }
                }
                mysqli_stmt_close($v_stmt);
            }

            // Check if driver exists
            $d_check = false;
            $d_check_query = "SELECT id FROM drivers WHERE id = ? LIMIT 1";
            if ($d_stmt = mysqli_prepare($conn, $d_check_query)) {
                mysqli_stmt_bind_param($d_stmt, "i", $driver_id);
                if (mysqli_stmt_execute($d_stmt)) {
                    mysqli_stmt_store_result($d_stmt);
                    if (mysqli_stmt_num_rows($d_stmt) > 0) {
                        $d_check = true;
                    }
                }
                mysqli_stmt_close($d_stmt);
            }

            if (!$v_check) {
                $error = "The selected vehicle does not exist.";
            } elseif (!$d_check) {
                $error = "The selected driver does not exist.";
            } else {
                // Start a database transaction for data consistency
                mysqli_begin_transaction($conn);
                
                $transaction_success = true;
                
                // Step A: Insert new trip into trips table using prepared statement
                $insert_query = "INSERT INTO trips (trip_code, trip_date, origin, destination, purpose, vehicle_id, driver_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $insert_query)) {
                    mysqli_stmt_bind_param($stmt, "sssssiis", $new_trip_code, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $status);
                    if (!mysqli_stmt_execute($stmt)) {
                        $transaction_success = false;
                        $error = "Failed to insert trip: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $transaction_success = false;
                    $error = "Trip statement preparation failed.";
                }
                
                // Step B: Update vehicle status to 'assigned'
                if ($transaction_success) {
                    $update_vehicle_query = "UPDATE vehicles SET status = 'assigned' WHERE id = ?";
                    if ($stmt_v = mysqli_prepare($conn, $update_vehicle_query)) {
                        mysqli_stmt_bind_param($stmt_v, "i", $vehicle_id);
                        if (!mysqli_stmt_execute($stmt_v)) {
                            $transaction_success = false;
                            $error = "Failed to update vehicle status.";
                        }
                        mysqli_stmt_close($stmt_v);
                    } else {
                        $transaction_success = false;
                        $error = "Vehicle statement preparation failed.";
                    }
                }
                
                // Step C: Update driver status to 'on_trip'
                if ($transaction_success) {
                    $update_driver_query = "UPDATE drivers SET status = 'on_trip' WHERE id = ?";
                    if ($stmt_d = mysqli_prepare($conn, $update_driver_query)) {
                        mysqli_stmt_bind_param($stmt_d, "i", $driver_id);
                        if (!mysqli_stmt_execute($stmt_d)) {
                            $transaction_success = false;
                            $error = "Failed to update driver status.";
                        }
                        mysqli_stmt_close($stmt_d);
                    } else {
                        $transaction_success = false;
                        $error = "Driver statement preparation failed.";
                    }
                }
                
                // Step D: Auto-create the first tracking update
                if ($transaction_success) {
                    $trip_id = mysqli_insert_id($conn);
                    if ($trip_id > 0) {
                        if (!addTrackingUpdate($trip_id, 'Order Received', $origin, 'Delivery order created and confirmed')) {
                            $transaction_success = false;
                            $error = "Failed to auto-create initial tracking update.";
                        }
                    } else {
                        $transaction_success = false;
                        $error = "Failed to retrieve new trip ID for tracking.";
                    }
                }
                
                // Commit or Rollback transaction
                if ($transaction_success) {
                    mysqli_commit($conn);
                    $success = "Trip created successfully!";
                    // Redirect on success
                    header("Refresh: 1; url=trips.php");
                } else {
                    mysqli_rollback($conn);
                    if (empty($error)) {
                        $error = "Transaction failed. Please try again.";
                    }
                }
            }
        }
    }
}

// 3. Query the database to fetch:
// - All available vehicles (status = 'available')
$vehicles_list = [];
$vehicles_query = "SELECT id, vehicle_name, license_plate FROM vehicles WHERE status = 'available' ORDER BY vehicle_name ASC";
if ($v_res = mysqli_query($conn, $vehicles_query)) {
    while ($row = mysqli_fetch_assoc($v_res)) {
        $vehicles_list[] = $row;
    }
    mysqli_free_result($v_res);
}

// - All available drivers (status = 'available')
$drivers_list = [];
$drivers_query = "SELECT id, full_name, license_number FROM drivers WHERE status = 'available' ORDER BY full_name ASC";
if ($d_res = mysqli_query($conn, $drivers_query)) {
    while ($row = mysqli_fetch_assoc($d_res)) {
        $drivers_list[] = $row;
    }
    mysqli_free_result($d_res);
}

$page_title = "Create Trip";

// 7. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Layout -->
<div class="page-header">
    <h2 class="page-title">Create Trip</h2>
    <a href="trips.php" class="btn btn-secondary">
        Back to List
    </a>
</div>

<!-- Form Container Card -->
<div class="content-card" style="max-width: 600px; margin: 0 auto;">
    
    <!-- User feedback messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?> Redirecting to trip schedule...
        </div>
    <?php endif; ?>

    <!-- 4. Create a form with specific fields -->
    <form action="add_trip.php" method="POST" autocomplete="off">
        
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
        
        <!-- Trip Code (auto-generated, display only) -->
        <div class="form-group">
            <label class="form-label">Trip Code (Auto Generated)</label>
            <input 
                type="text" 
                class="form-control" 
                value="<?php echo htmlspecialchars($new_trip_code, ENT_QUOTES, 'UTF-8'); ?>" 
                disabled 
                style="font-family: monospace; font-weight: bold; color: #a78bfa; background: rgba(139, 92, 246, 0.08);"
            >
        </div>

        <div class="form-row">
            <!-- Trip Date (date input, required) -->
            <div class="form-group">
                <label for="trip_date" class="form-label">Trip Date *</label>
                <input 
                    type="date" 
                    id="trip_date" 
                    name="trip_date" 
                    class="form-control" 
                    required 
                    value="<?php echo isset($_POST['trip_date']) ? htmlspecialchars($_POST['trip_date'], ENT_QUOTES, 'UTF-8') : date('Y-m-d'); ?>"
                >
            </div>

            <!-- Status (dropdown select) -->
            <div class="form-group">
                <label for="status" class="form-label">Trip Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo (isset($_POST['status']) && $_POST['status'] === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="in_transit" <?php echo (isset($_POST['status']) && $_POST['status'] === 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                    <option value="completed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <!-- Origin (text input, required) -->
            <div class="form-group">
                <label for="origin" class="form-label">Origin *</label>
                <input 
                    type="text" 
                    id="origin" 
                    name="origin" 
                    class="form-control" 
                    placeholder="e.g. Warehouse A, Houston" 
                    required
                    value="<?php echo isset($_POST['origin']) ? htmlspecialchars($_POST['origin'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>

            <!-- Destination (text input, required) -->
            <div class="form-group">
                <label for="destination" class="form-label">Destination *</label>
                <input 
                    type="text" 
                    id="destination" 
                    name="destination" 
                    class="form-control" 
                    placeholder="e.g. Retail Outlet 4, Austin" 
                    required
                    value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>
        </div>

        <!-- Purpose (textarea) -->
        <div class="form-group">
            <label for="purpose" class="form-label">Purpose / Cargo Details</label>
            <textarea 
                id="purpose" 
                name="purpose" 
                class="form-control" 
                rows="2" 
                placeholder="e.g. Delivery of electronics equipment"
            ><?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
        </div>

        <div class="form-row">
            <!-- Vehicle dropdown -->
            <div class="form-group">
                <label for="vehicle_id" class="form-label">Assign Vehicle *</label>
                <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                    <option value="">-- Select Available Vehicle --</option>
                    <?php foreach ($vehicles_list as $v): ?>
                        <option value="<?php echo htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($_POST['vehicle_id']) && intval($_POST['vehicle_id']) === intval($v['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['vehicle_name'] . ' (' . $v['license_plate'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($vehicles_list)): ?>
                    <p style="font-size: 0.75rem; color: var(--danger-color); margin-top: 4px;">No available vehicles in fleet.</p>
                <?php endif; ?>
            </div>

            <!-- Driver dropdown -->
            <div class="form-group">
                <label for="driver_id" class="form-label">Assign Driver *</label>
                <select id="driver_id" name="driver_id" class="form-control" required>
                    <option value="">-- Select Available Driver --</option>
                    <?php foreach ($drivers_list as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo (isset($_POST['driver_id']) && intval($_POST['driver_id']) === intval($d['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['full_name'] . ' (' . $d['license_number'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($drivers_list)): ?>
                    <p style="font-size: 0.75rem; color: var(--danger-color); margin-top: 4px;">No available drivers registered.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="add_trip" class="btn btn-primary" style="width: 100%; margin-top: 12px;" <?php echo (empty($vehicles_list) || empty($drivers_list)) ? 'disabled' : ''; ?>>
            Schedule Trip
        </button>

    </form>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
