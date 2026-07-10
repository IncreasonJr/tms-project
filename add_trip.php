<?php
// add_trip.php - Schedule New Trip
// Conforms to spec.pdf requirements (Module 5: Trip Management - Create)

require_once __DIR__ . '/includes/header.php';

// Fetch only AVAILABLE vehicles and drivers for assignment logic validation
$available_vehicles = [];
$available_drivers = [];

if ($db_connected) {
    // Database query for available vehicles
    $sql = "SELECT id, vehicle_name, license_plate, capacity FROM vehicles WHERE status = 'available' ORDER BY vehicle_name ASC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $available_vehicles[] = $row;
        }
    }
    
    // Database query for available drivers
    $sql = "SELECT id, fullname, license_number FROM drivers WHERE status = 'available' ORDER BY fullname ASC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $available_drivers[] = $row;
        }
    }
} else {
    // Fallback simulation filtering
    foreach ($_SESSION['mock_vehicles'] as $v) {
        if ($v['status'] === 'available') $available_vehicles[] = $v;
    }
    foreach ($_SESSION['mock_drivers'] as $d) {
        if ($d['status'] === 'available') $available_drivers[] = $d;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_date = sanitize_input($_POST['trip_date']);
    $origin = sanitize_input($_POST['origin']);
    $destination = sanitize_input($_POST['destination']);
    $purpose = sanitize_input($_POST['purpose']);
    $vehicle_id = !empty($_POST['vehicle_id']) ? intval($_POST['vehicle_id']) : null;
    $driver_id = !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null;
    
    if (empty($trip_date) || empty($origin) || empty($destination)) {
        $error = 'Trip date, origin, and destination are required.';
    } else {
        // Generate unique trip code
        $trip_code = generate_trip_code();
        $created_by = $_SESSION['user_id'];
        
        $success = insert_trip($trip_code, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $created_by);
        
        if ($success) {
            header("Location: trips.php?msg=Trip+" . urlencode($trip_code) . "+scheduled+successfully!&type=success");
            exit;
        } else {
            $error = 'Failed to schedule trip. Please check your data.';
        }
    }
}
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
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="add_trip.php" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label for="trip_date" class="form-label">Trip Schedule Date *</label>
                <input type="date" id="trip_date" name="trip_date" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label for="trip_code_dummy" class="form-label">Trip Code (Auto-Generated)</label>
                <input type="text" id="trip_code_dummy" disabled class="form-control" style="opacity: 0.6; font-family: monospace; font-weight: 700; color: var(--accent-blue);" value="TRP-XXXXXX">
            </div>

            <div class="form-group">
                <label for="origin" class="form-label">Starting Origin Address *</label>
                <input type="text" id="origin" name="origin" required placeholder="e.g. Accra Central, Greater Accra" class="form-control">
            </div>

            <div class="form-group">
                <label for="destination" class="form-label">Ending Destination Address *</label>
                <input type="text" id="destination" name="destination" required placeholder="e.g. Tamale Airport, Northern" class="form-control">
            </div>

            <div class="form-group">
                <label for="vehicle_id" class="form-label">Assign Available Vehicle</label>
                <select id="vehicle_id" name="vehicle_id" class="form-control">
                    <option value="">-- No vehicle assignment (Hold) --</option>
                    <?php foreach ($available_vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo $v['vehicle_name'] . ' (' . $v['license_plate'] . ') - Cap: ' . number_format($v['capacity']) . ' lbs'; ?>
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
                        <option value="<?php echo $d['id']; ?>">
                            <?php echo $d['fullname'] . ' (DL: ' . $d['license_number'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($available_drivers)): ?>
                    <span style="font-size: 0.7rem; color: var(--status-danger); margin-top: 0.25rem;">Warning: All drivers are currently on trip.</span>
                <?php endif; ?>
            </div>

            <div class="form-group full-width">
                <label for="purpose" class="form-label">Transit Purpose / Cargo Description</label>
                <textarea id="purpose" name="purpose" rows="4" placeholder="Describe the cargo type, client details, weight, and delivery guidelines..." class="form-control"></textarea>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
