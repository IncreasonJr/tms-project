<?php
// dashboard.php - Central Operations Console
// Conforms to spec.pdf requirements (Module 2: Dashboard)

require_once __DIR__ . '/includes/header.php';

// Fetch statistics
$total_vehicles = 0;
$total_drivers = 0;
$total_trips = 0;

$pending_trips = 0;
$completed_trips = 0;
$in_transit_trips = 0;

if ($db_connected) {
    // Total Counts
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM vehicles");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_vehicles = $row['c']; }
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM drivers");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_drivers = $row['c']; }
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM trips");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_trips = $row['c']; }
    
    // Status Counts
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM trips WHERE status = 'pending'");
    if ($res) { $row = mysqli_fetch_assoc($res); $pending_trips = $row['c']; }
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM trips WHERE status = 'completed'");
    if ($res) { $row = mysqli_fetch_assoc($res); $completed_trips = $row['c']; }
    
    $res = mysqli_query($conn, "SELECT COUNT(*) as c FROM trips WHERE status = 'in_transit'");
    if ($res) { $row = mysqli_fetch_assoc($res); $in_transit_trips = $row['c']; }
} else {
    // Simulation fallback counts
    $total_vehicles = count($_SESSION['mock_vehicles']);
    $total_drivers = count($_SESSION['mock_drivers']);
    $total_trips = count($_SESSION['mock_trips']);
    
    foreach ($_SESSION['mock_trips'] as $trip) {
        if ($trip['status'] === 'pending') $pending_trips++;
        if ($trip['status'] === 'completed') $completed_trips++;
        if ($trip['status'] === 'in_transit') $in_transit_trips++;
    }
}

// Fetch recent 5 trips for table log
$recent_trips = [];
if ($db_connected) {
    $sql = "SELECT * FROM trips ORDER BY id DESC LIMIT 5";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $recent_trips[] = $row;
        }
    }
} else {
    $recent_trips = array_slice(array_reverse(array_values($_SESSION['mock_trips'])), 0, 5);
}
?>

<div class="dashboard-viewport">
    
    <!-- KPI Analytics Grid -->
    <div class="kpi-grid">
        <div class="kpi-card blue">
            <div class="kpi-content">
                <h3>Total Vehicles</h3>
                <div class="kpi-value"><?php echo $total_vehicles; ?></div>
            </div>
            <div class="kpi-icon">
                <i data-lucide="truck"></i>
            </div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-content">
                <h3>Active Drivers</h3>
                <div class="kpi-value"><?php echo $total_drivers; ?></div>
            </div>
            <div class="kpi-icon">
                <i data-lucide="users"></i>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-content">
                <h3>Total Trips</h3>
                <div class="kpi-value"><?php echo $total_trips; ?></div>
            </div>
            <div class="kpi-icon">
                <i data-lucide="navigation"></i>
            </div>
        </div>
        <div class="kpi-card orange">
            <div class="kpi-content">
                <h3>In Transit</h3>
                <div class="kpi-value"><?php echo $in_transit_trips; ?></div>
            </div>
            <div class="kpi-icon">
                <i data-lucide="compass"></i>
            </div>
        </div>
    </div>

    <!-- Quick Navigation Modules -->
    <div class="dashboard-row">
        
        <!-- Recent Shipments Panel -->
        <div class="dashboard-panel">
            <div class="panel-header">
                <h3 class="panel-title">Recent Dispatch Activity Logs</h3>
                <a href="trips.php" class="panel-action-btn">
                    <span>Manage Trips</span>
                    <i data-lucide="arrow-right"></i>
                </a>
            </div>
            
            <div class="table-container">
                <table class="tms-table">
                    <thead>
                        <tr>
                            <th>Trip Code</th>
                            <th>Trip Date</th>
                            <th>Route (From -> To)</th>
                            <th>Assigned Driver</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_trips)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem;">No trips scheduled yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_trips as $trip): ?>
                                <tr>
                                    <td style="font-weight: 700; color: white;"><?php echo $trip['trip_code']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($trip['trip_date'])); ?></td>
                                    <td>
                                        <span style="color: white; font-weight: 500;"><?php echo explode(',', $trip['origin'])[0]; ?></span>
                                        <i data-lucide="arrow-right-left" style="width: 12px; height: 12px; display: inline-block; margin: 0 0.25rem; vertical-align: middle; color: var(--text-muted);"></i>
                                        <span style="color: white; font-weight: 500;"><?php echo explode(',', $trip['destination'])[0]; ?></span>
                                    </td>
                                    <td><?php echo get_driver_name($trip['driver_id']); ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $trip['status']; ?>">
                                            <?php echo str_replace('_', ' ', $trip['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions & Status Summary Panel -->
        <div class="dashboard-panel">
            <div class="panel-header">
                <h3 class="panel-title">Operations Control</h3>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="background-color: rgba(255,255,255,0.02); border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md);">
                    <h4 style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.75rem;">Trip Breakdown</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary);">Completed</span>
                            <strong style="color: var(--status-available);"><?php echo $completed_trips; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary);">In Transit</span>
                            <strong style="color: var(--accent-blue);"><?php echo $in_transit_trips; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-secondary);">Pending Approval</span>
                            <strong style="color: var(--status-maintenance);"><?php echo $pending_trips; ?></strong>
                        </div>
                    </div>
                </div>

                <a href="add_trip.php" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.8rem;">
                    <i data-lucide="plus-circle"></i>
                    <span>Schedule New Trip</span>
                </a>
                <a href="maintenance.php" class="btn btn-secondary" style="width: 100%; justify-content: center; padding: 0.8rem;">
                    <i data-lucide="wrench"></i>
                    <span>Log Maintenance</span>
                </a>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
