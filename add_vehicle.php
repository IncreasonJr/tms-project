<?php
/**
 * Add New Vehicle
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

$error = '';
$success = '';

// 4. Process form submission (POST method)
if (isset($_POST['add_vehicle'])) {
    
    // Validate CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Validate and sanitize input fields
        $vehicle_name = isset($_POST['vehicle_name']) ? sanitize_input(trim($_POST['vehicle_name'])) : '';
        $license_plate = isset($_POST['license_plate']) ? sanitize_input(trim($_POST['license_plate'])) : '';
        $model = isset($_POST['model']) ? sanitize_input(trim($_POST['model'])) : '';
        $capacity = (isset($_POST['capacity']) && $_POST['capacity'] !== '') ? floatval($_POST['capacity']) : null;
        $status = isset($_POST['status']) ? sanitize_input(trim($_POST['status'])) : 'available';

        // Validation checks
        if (empty($vehicle_name) || empty($license_plate)) {
            $error = "Vehicle name and license plate are required fields.";
        } elseif ($capacity !== null && $capacity <= 0) {
            $error = "Vehicle capacity must be greater than zero.";
        } else {
            if ($db_connected && $conn) {
                // Use mysqli_prepare() prepared statements for inserting database records to prevent SQL injection
                $query = "INSERT INTO vehicles (vehicle_name, license_plate, model, capacity, status) VALUES (?, ?, ?, ?, ?)";
                
                if ($stmt = mysqli_prepare($conn, $query)) {
                    // Bind parameters
                    mysqli_stmt_bind_param($stmt, "sssds", $vehicle_name, $license_plate, $model, $capacity, $status);
                    
                    // Execute query
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Vehicle added successfully!";
                        header("Refresh: 1; url=vehicles.php");
                    } else {
                        $error = "Error adding vehicle. The license plate may already be registered.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = "Database preparation error. Please try again.";
                }
            } else {
                // Simulation Mode
                $exists = false;
                if (isset($_SESSION['mock_vehicles'])) {
                    foreach ($_SESSION['mock_vehicles'] as $veh) {
                        if ($veh['license_plate'] === $license_plate) {
                            $exists = true;
                            break;
                        }
                    }
                }
                if ($exists) {
                    $error = "Error adding vehicle. The license plate may already be registered.";
                } else {
                    $new_id = empty($_SESSION['mock_vehicles']) ? 1 : max(array_keys($_SESSION['mock_vehicles'])) + 1;
                    $_SESSION['mock_vehicles'][$new_id] = [
                        'id' => $new_id,
                        'vehicle_name' => $vehicle_name,
                        'license_plate' => $license_plate,
                        'model' => $model,
                        'capacity' => $capacity,
                        'status' => $status
                    ];
                    $success = "Vehicle added successfully (Simulation Mode)!";
                    header("Refresh: 1; url=vehicles.php");
                }
            }
        }
    }
}

$page_title = "Add Vehicle";

// 5. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Layout -->
<div class="page-header">
    <h2 class="page-title">Add Vehicle</h2>
    <a href="vehicles.php" class="btn btn-secondary">
        Back to List
    </a>
</div>

<!-- Add Form Card -->
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

    <!-- Form with required fields -->
    <form action="add_vehicle.php" method="POST" autocomplete="off">
        
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Vehicle Name (text input, required) -->
        <div class="form-group">
            <label for="vehicle_name" class="form-label">Vehicle Name *</label>
            <input 
                type="text" 
                id="vehicle_name" 
                name="vehicle_name" 
                class="form-control" 
                placeholder="e.g. Scania R500 Cargo Truck" 
                required
                value="<?php echo isset($_POST['vehicle_name']) ? htmlspecialchars($_POST['vehicle_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
            >
        </div>

        <!-- License Plate (text input, required) -->
        <div class="form-group">
            <label for="license_plate" class="form-label">License Plate *</label>
            <input 
                type="text" 
                id="license_plate" 
                name="license_plate" 
                class="form-control" 
                placeholder="e.g. CA-456-XY" 
                required
                value="<?php echo isset($_POST['license_plate']) ? htmlspecialchars($_POST['license_plate'], ENT_QUOTES, 'UTF-8') : ''; ?>"
            >
        </div>

        <div class="form-row">
            <!-- Model (text input) -->
            <div class="form-group">
                <label for="model" class="form-label">Model</label>
                <input 
                    type="text" 
                    id="model" 
                    name="model" 
                    class="form-control" 
                    placeholder="e.g. 2025 Streamline"
                    value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>

            <!-- Capacity (number input) -->
            <div class="form-group">
                <label for="capacity" class="form-label">Capacity (Tons)</label>
                <input 
                    type="number" 
                    step="0.01" 
                    id="capacity" 
                    name="capacity" 
                    class="form-control" 
                    placeholder="e.g. 18.5"
                    value="<?php echo isset($_POST['capacity']) ? htmlspecialchars($_POST['capacity'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>
        </div>

        <!-- Status (dropdown select) -->
        <div class="form-group">
            <label for="status" class="form-label">Initial Status</label>
            <select id="status" name="status" class="form-control">
                <option value="available" <?php echo (isset($_POST['status']) && $_POST['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="assigned" <?php echo (isset($_POST['status']) && $_POST['status'] === 'assigned') ? 'selected' : ''; ?>>Assigned</option>
                <option value="under_maintenance" <?php echo (isset($_POST['status']) && $_POST['status'] === 'under_maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                <option value="out_of_service" <?php echo (isset($_POST['status']) && $_POST['status'] === 'out_of_service') ? 'selected' : ''; ?>>Out of Service</option>
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="add_vehicle" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
            Save Vehicle Record
        </button>

    </form>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
