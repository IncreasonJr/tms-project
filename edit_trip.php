<?php
// edit_trip.php - Modify Scheduled Trip Order
// Conforms to spec.pdf requirements (Module 5: Trip Management - Update)

require_once __DIR__ . '/includes/header.php';

$trip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$trip = get_trip_by_id($trip_id);

if (!$trip) {
    header("Location: trips.php?msg=Trip+not+found.&type=error");
    exit;
}

// Fetch available vehicles and drivers for assignment dropdown lists
$vehicles = [];
$drivers = [];

if ($db_connected) {
    // Fetch available vehicles OR the one currently assigned to this trip
    $sql = "SELECT id, vehicle_name, license_plate, capacity FROM vehicles 
            WHERE status = 'available' OR id = " . intval($trip['vehicle_id']) . " 
            ORDER BY vehicle_name ASC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $vehicles[] = $row;
        }
    }
    
    // Fetch available drivers OR the one currently assigned to this trip
    $sql = "SELECT id, fullname, license_number FROM drivers 
            WHERE status = 'available' OR id = " . intval($trip['driver_id']) . " 
            ORDER BY fullname ASC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $drivers[] = $row;
        }
    }
} else {
    // Fallback simulation filtering
    foreach ($_SESSION['mock_vehicles'] as $v) {
        if ($v['status'] === 'available' || $v['id'] == $trip['vehicle_id']) {
            $vehicles[] = $v;
        }
    }
    foreach ($_SESSION['mock_drivers'] as $d) {
        if ($d['status'] === 'available' || $d['id'] == $trip['driver_id']) {
            $drivers[] = $d;
        }
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
    $status = sanitize_input($_POST['status']);
    
    if (empty($trip_date) || empty($origin) || empty($destination) || empty($status)) {
        $error = 'Trip date, origin, destination, and status are required.';
    } else {
        $success = update_trip($trip_id, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $status);
        
        if ($success) {
            header("Location: trips.php?msg=Trip+" . urlencode($trip['trip_code']) . "+updated+successfully!&type=success");
            exit;
        } else {
            $error = 'Failed to update trip. Please verify parameters.';
        }
    }
}
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Modify Dispatch Order: <?php echo $trip['trip_code']; ?></h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Edit scheduled timings, route coordinates, and transit progress</p>
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

    <form action="edit_trip.php?id=<?php echo $trip['id']; ?>" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label for="trip_date" class="form-label">Trip Schedule Date *</label>
                <input type="date" id="trip_date" name="trip_date" required class="form-control" value="<?php echo $trip['trip_date']; ?>">
            </div>
            
            <div class="form-group">
                <label for="status" class="form-label">Transit Progress Status *</label>
                <select id="status" name="status" class="form-control" style="font-weight: 600;">
                    <option value="pending" <?php echo $trip['status'] === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                    <option value="approved" <?php echo $trip['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="in_transit" <?php echo $trip['status'] === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                    <option value="completed" <?php echo $trip['status'] === 'completed' ? 'selected' : ''; ?>>Completed / Delivered</option>
                    <option value="cancelled" <?php echo $trip['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <div class="form-group">
                <label for="origin" class="form-label">Starting Origin Address *</label>
                <input type="text" id="origin" name="origin" required placeholder="e.g. Accra Central, Greater Accra" class="form-control" value="<?php echo $trip['origin']; ?>">
            </div>

            <div class="form-group">
                <label for="destination" class="form-label">Ending Destination Address *</label>
                <input type="text" id="destination" name="destination" required placeholder="e.g. Tamale Airport, Northern" class="form-control" value="<?php echo $trip['destination']; ?>">
            </div>

            <div class="form-group">
                <label for="vehicle_id" class="form-label">Assigned Vehicle</label>
                <select id="vehicle_id" name="vehicle_id" class="form-control">
                    <option value="">-- No vehicle assignment (Hold) --</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo $v['id'] == $trip['vehicle_id'] ? 'selected' : ''; ?>>
                            <?php echo $v['vehicle_name'] . ' (' . $v['license_plate'] . ') - Cap: ' . number_format($v['capacity']) . ' lbs'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="driver_id" class="form-label">Assigned Driver</label>
                <select id="driver_id" name="driver_id" class="form-control">
                    <option value="">-- No driver assignment (Hold) --</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $d['id'] == $trip['driver_id'] ? 'selected' : ''; ?>>
                            <?php echo $d['fullname'] . ' (DL: ' . $d['license_number'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="purpose" class="form-label">Transit Purpose / Cargo Description</label>
                <textarea id="purpose" name="purpose" rows="4" placeholder="Describe the cargo type..." class="form-control"><?php echo $trip['purpose']; ?></textarea>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
