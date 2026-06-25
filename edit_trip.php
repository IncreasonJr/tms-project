<?php
/**
 * Edit/Update Trip Status
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 3. Get the trip ID from the URL parameter (GET method)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 9. If trip ID is invalid, redirect to trips.php
if ($id <= 0) {
    header("Location: trips.php");
    exit();
}

$error = '';
$success = '';

// 4. Query the database to fetch trip data for the given ID using prepared statement
$trip = null;
$fetch_query = "SELECT * FROM trips WHERE id = ? LIMIT 1";
if ($stmt = mysqli_prepare($conn, $fetch_query)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $fetch_result = mysqli_stmt_get_result($stmt);
        $trip = mysqli_fetch_assoc($fetch_result);
    }
    mysqli_stmt_close($stmt);
}

// 9. If trip not found, redirect to trips.php
if (!$trip) {
    header("Location: trips.php");
    exit();
}

// Store old vehicle, driver, and status for update logic
$old_vehicle_id = intval($trip['vehicle_id']);
$old_driver_id = intval($trip['driver_id']);
$old_status = $trip['status'];

// 8. Process form submission (POST method)
if (isset($_POST['edit_trip'])) {
    
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
                // Start a database transaction for consistency
                mysqli_begin_transaction($conn);
                $transaction_success = true;
                
                // Step A: Update the trip in the trips table using prepared statement
                $update_query = "UPDATE trips SET trip_date = ?, origin = ?, destination = ?, purpose = ?, vehicle_id = ?, driver_id = ?, status = ? WHERE id = ?";
                if ($stmt = mysqli_prepare($conn, $update_query)) {
                    mysqli_stmt_bind_param($stmt, "ssssiisi", $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $status, $id);
                    if (!mysqli_stmt_execute($stmt)) {
                        $transaction_success = false;
                        $error = "Failed to update trip record: " . mysqli_stmt_error($stmt);
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $transaction_success = false;
                    $error = "Trip statement preparation failed.";
                }
                
                // Step B: Update vehicle and driver statuses based on trip status transition
                if ($transaction_success) {
                    // If the trip is completed or cancelled, release vehicle and driver
                    if ($status === 'completed' || $status === 'cancelled') {
                        
                        // Set old vehicle and current selected vehicle to available
                        $release_v = "UPDATE vehicles SET status = 'available' WHERE id = ? OR id = ?";
                        if ($stmt_v = mysqli_prepare($conn, $release_v)) {
                            mysqli_stmt_bind_param($stmt_v, "ii", $old_vehicle_id, $vehicle_id);
                            mysqli_stmt_execute($stmt_v);
                            mysqli_stmt_close($stmt_v);
                        }
                        
                        // Set old driver and current selected driver to available
                        $release_d = "UPDATE drivers SET status = 'available' WHERE id = ? OR id = ?";
                        if ($stmt_d = mysqli_prepare($conn, $release_d)) {
                            mysqli_stmt_bind_param($stmt_d, "ii", $old_driver_id, $driver_id);
                            mysqli_stmt_execute($stmt_d);
                            mysqli_stmt_close($stmt_d);
                        }
                        
                    } else {
                        // Trip is active (pending, approved, in_transit)
                        
                        // If vehicle was changed, set old vehicle back to available
                        if ($old_vehicle_id !== $vehicle_id) {
                            $release_ov = "UPDATE vehicles SET status = 'available' WHERE id = ?";
                            if ($stmt_ov = mysqli_prepare($conn, $release_ov)) {
                                mysqli_stmt_bind_param($stmt_ov, "i", $old_vehicle_id);
                                mysqli_stmt_execute($stmt_ov);
                                mysqli_stmt_close($stmt_ov);
                            }
                        }
                        
                        // Set new vehicle to assigned
                        $assign_nv = "UPDATE vehicles SET status = 'assigned' WHERE id = ?";
                        if ($stmt_nv = mysqli_prepare($conn, $assign_nv)) {
                            mysqli_stmt_bind_param($stmt_nv, "i", $vehicle_id);
                            mysqli_stmt_execute($stmt_nv);
                            mysqli_stmt_close($stmt_nv);
                        }

                        // If driver was changed, set old driver back to available
                        if ($old_driver_id !== $driver_id) {
                            $release_od = "UPDATE drivers SET status = 'available' WHERE id = ?";
                            if ($stmt_od = mysqli_prepare($conn, $release_od)) {
                                mysqli_stmt_bind_param($stmt_od, "i", $old_driver_id);
                                mysqli_stmt_execute($stmt_od);
                                mysqli_stmt_close($stmt_od);
                            }
                        }
                        
                        // Set new driver to on_trip
                        $assign_nd = "UPDATE drivers SET status = 'on_trip' WHERE id = ?";
                        if ($stmt_nd = mysqli_prepare($conn, $assign_nd)) {
                            mysqli_stmt_bind_param($stmt_nd, "i", $driver_id);
                            mysqli_stmt_execute($stmt_nd);
                            mysqli_stmt_close($stmt_nd);
                        }
                    }
                }
                
                // Commit or Rollback transaction
                if ($transaction_success) {
                    mysqli_commit($conn);
                    $success = "Trip updated successfully!";
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
    
    // Refresh the loaded trip details after POST to update display values on errors
    if (!$transaction_success) {
        if ($stmt = mysqli_prepare($conn, $fetch_query)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                $fetch_result = mysqli_stmt_get_result($stmt);
                $trip = mysqli_fetch_assoc($fetch_result);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// 5. Query available vehicles and drivers for dropdowns
// Include vehicles that are available OR currently assigned to this trip
$vehicles_list = [];
$vehicles_query = "SELECT id, vehicle_name, license_plate FROM vehicles WHERE status = 'available' OR id = ? ORDER BY vehicle_name ASC";
if ($v_stmt = mysqli_prepare($conn, $vehicles_query)) {
    mysqli_stmt_bind_param($v_stmt, "i", $old_vehicle_id);
    if (mysqli_stmt_execute($v_stmt)) {
        $v_res = mysqli_stmt_get_result($v_stmt);
        while ($row = mysqli_fetch_assoc($v_res)) {
            $vehicles_list[] = $row;
        }
    }
    mysqli_stmt_close($v_stmt);
}

// Include drivers that are available OR currently assigned to this trip
$drivers_list = [];
$drivers_query = "SELECT id, full_name, license_number FROM drivers WHERE status = 'available' OR id = ? ORDER BY full_name ASC";
if ($d_stmt = mysqli_prepare($conn, $drivers_query)) {
    mysqli_stmt_bind_param($d_stmt, "i", $old_driver_id);
    if (mysqli_stmt_execute($d_stmt)) {
        $d_res = mysqli_stmt_get_result($d_stmt);
        while ($row = mysqli_fetch_assoc($d_res)) {
            $drivers_list[] = $row;
        }
    }
    mysqli_stmt_close($d_stmt);
}

$page_title = "Edit Trip";

// 10. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Layout -->
<div class="page-header">
    <h2 class="page-title">Edit Trip</h2>
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

    <!-- 7. Create a form with the same fields as add_trip.php -->
    <form action="edit_trip.php?id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" method="POST" autocomplete="off">
        
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
        
        <!-- 6. Pre-fill the form with existing trip data -->
        
        <!-- Trip Code (Display Only) -->
        <div class="form-group">
            <label class="form-label">Trip Code</label>
            <input 
                type="text" 
                class="form-control" 
                value="<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" 
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
                    value="<?php echo htmlspecialchars(isset($_POST['trip_date']) ? $_POST['trip_date'] : $trip['trip_date'], ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>

            <!-- Status (dropdown select) -->
            <div class="form-group">
                <label for="status" class="form-label">Trip Status</label>
                <?php 
                $current_status = isset($_POST['status']) ? $_POST['status'] : $trip['status'];
                ?>
                <select id="status" name="status" class="form-control">
                    <option value="pending" <?php echo ($current_status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo ($current_status === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="in_transit" <?php echo ($current_status === 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                    <option value="completed" <?php echo ($current_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($current_status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
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
                    required
                    value="<?php echo htmlspecialchars(isset($_POST['origin']) ? $_POST['origin'] : $trip['origin'], ENT_QUOTES, 'UTF-8'); ?>"
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
                    required
                    value="<?php echo htmlspecialchars(isset($_POST['destination']) ? $_POST['destination'] : $trip['destination'], ENT_QUOTES, 'UTF-8'); ?>"
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
            ><?php echo htmlspecialchars(isset($_POST['purpose']) ? $_POST['purpose'] : $trip['purpose'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-row">
            <!-- Vehicle dropdown -->
            <div class="form-group">
                <label for="vehicle_id" class="form-label">Assign Vehicle *</label>
                <?php 
                $selected_vehicle = isset($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : $old_vehicle_id;
                ?>
                <select id="vehicle_id" name="vehicle_id" class="form-control" required>
                    <?php foreach ($vehicles_list as $v): ?>
                        <option value="<?php echo htmlspecialchars($v['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selected_vehicle === intval($v['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['vehicle_name'] . ' (' . $v['license_plate'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Driver dropdown -->
            <div class="form-group">
                <label for="driver_id" class="form-label">Assign Driver *</label>
                <?php 
                $selected_driver = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : $old_driver_id;
                ?>
                <select id="driver_id" name="driver_id" class="form-control" required>
                    <?php foreach ($drivers_list as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($selected_driver === intval($d['id'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['full_name'] . ' (' . $d['license_number'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="edit_trip" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
            Update Trip Schedule
        </button>

    </form>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
