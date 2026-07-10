<?php
/**
 * Edit Existing Vehicle
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Enforce admin-only access check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 3. Get the vehicle ID from the URL parameter (GET method)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 8. If vehicle ID is invalid, redirect to vehicles.php
if ($id <= 0) {
    header("Location: vehicles.php");
    exit();
}

$error = '';
$success = '';

// 7. Process form submission (POST method)
if (isset($_POST['edit_vehicle'])) {
    
    // Validate CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Validate and sanitize inputs
        $vehicle_name = isset($_POST['vehicle_name']) ? trim($_POST['vehicle_name']) : '';
        $license_plate = isset($_POST['license_plate']) ? trim($_POST['license_plate']) : '';
        $model = isset($_POST['model']) ? trim($_POST['model']) : '';
        $capacity = (isset($_POST['capacity']) && $_POST['capacity'] !== '') ? floatval($_POST['capacity']) : null;
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'available';

        if (empty($vehicle_name) || empty($license_plate)) {
            $error = "Vehicle name and license plate are required fields.";
        } elseif ($capacity !== null && $capacity <= 0) {
            $error = "Vehicle capacity must be greater than zero.";
        } else {
            // Update the vehicle in the vehicles table using prepared statements
            $update_query = "UPDATE vehicles SET vehicle_name = ?, license_plate = ?, model = ?, capacity = ?, status = ? WHERE id = ?";
            
            if ($stmt = mysqli_prepare($conn, $update_query)) {
                // Bind parameters
                mysqli_stmt_bind_param($stmt, "sssdsi", $vehicle_name, $license_plate, $model, $capacity, $status, $id);
                
                // Execute update
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Vehicle updated successfully!";
                    // Redirect to vehicles.php on success
                    header("Refresh: 1; url=vehicles.php");
                } else {
                    $error = "Error updating vehicle. The license plate might already be registered by another vehicle.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            } else {
                $error = "Database preparation error. Please try again.";
            }
        }
    }
}

// 4. Query the database to fetch vehicle data for the given ID
$vehicle = null;
$fetch_query = "SELECT * FROM vehicles WHERE id = ? LIMIT 1";

if ($stmt = mysqli_prepare($conn, $fetch_query)) {
    // Bind parameter
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    // Execute query
    if (mysqli_stmt_execute($stmt)) {
        $fetch_result = mysqli_stmt_get_result($stmt);
        $vehicle = mysqli_fetch_assoc($fetch_result);
    }
    
    // Close statement
    mysqli_stmt_close($stmt);
}

// 8. If vehicle not found, redirect to vehicles.php
if (!$vehicle) {
    header("Location: vehicles.php");
    exit();
}

$page_title = "Edit Vehicle";

// 9. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Layout -->
<div class="page-header">
    <h2 class="page-title">Edit Vehicle</h2>
    <a href="vehicles.php" class="btn btn-secondary">
        Back to List
    </a>
</div>

<!-- Edit Form Card -->
<div class="content-card" style="max-width: 600px; margin: 0 auto;">
    
    <!-- User feedback messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?> Redirecting to fleet list...
        </div>
    <?php endif; ?>

    <!-- Form with the same fields as add_vehicle.php -->
    <form action="edit_vehicle.php?id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" method="POST" autocomplete="off">
        
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Pre-fill the form with existing vehicle data using htmlspecialchars() -->
        
        <!-- Vehicle Name -->
        <div class="form-group">
            <label for="vehicle_name" class="form-label">Vehicle Name *</label>
            <input 
                type="text" 
                id="vehicle_name" 
                name="vehicle_name" 
                class="form-control" 
                required
                value="<?php echo htmlspecialchars(isset($_POST['vehicle_name']) ? $_POST['vehicle_name'] : $vehicle['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <!-- License Plate -->
        <div class="form-group">
            <label for="license_plate" class="form-label">License Plate *</label>
            <input 
                type="text" 
                id="license_plate" 
                name="license_plate" 
                class="form-control" 
                required
                value="<?php echo htmlspecialchars(isset($_POST['license_plate']) ? $_POST['license_plate'] : $vehicle['license_plate'], ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <div class="form-row">
            <!-- Model -->
            <div class="form-group">
                <label for="model" class="form-label">Model</label>
                <input 
                    type="text" 
                    id="model" 
                    name="model" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars(isset($_POST['model']) ? $_POST['model'] : $vehicle['model'], ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>

            <!-- Capacity (number) -->
            <div class="form-group">
                <label for="capacity" class="form-label">Capacity (Tons)</label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="capacity" 
                    name="capacity" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars(isset($_POST['capacity']) ? $_POST['capacity'] : $vehicle['capacity'], ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <!-- Status -->
        <div class="form-group">
            <label for="status" class="form-label">Status</label>
            <?php 
            $current_status = isset($_POST['status']) ? $_POST['status'] : $vehicle['status'];
            ?>
            <select id="status" name="status" class="form-control">
                <option value="available" <?php echo ($current_status === 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="assigned" <?php echo ($current_status === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                <option value="under_maintenance" <?php echo ($current_status === 'under_maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                <option value="out_of_service" <?php echo ($current_status === 'out_of_service') ? 'selected' : ''; ?>>Out of Service</option>
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="edit_vehicle" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
            Update Vehicle Record
        </button>

    </form>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
