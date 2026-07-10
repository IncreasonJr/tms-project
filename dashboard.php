<?php
/**
 * Dashboard Page
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authorization
check_login();

// 2. Fetch statistics and updates
$total_vehicles = 0;
$total_drivers = 0;
$today_trips = 0;
$total_trips = 0;
$pending_trips = 0;
$approved_trips = 0;
$transit_trips = 0;
$completed_trips = 0;
$maintenance_count = 0;

$recent_updates = [];

if ($db_connected && $conn) {
    // Total Vehicles
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM vehicles");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_vehicles = $row['total']; mysqli_free_result($res); }

    // Total Drivers
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM drivers");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_drivers = $row['total']; mysqli_free_result($res); }

    // Today's Trips
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips WHERE trip_date = CURDATE()");
    if ($res) { $row = mysqli_fetch_assoc($res); $today_trips = $row['total']; mysqli_free_result($res); }

    // Total Trips
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_trips = $row['total']; mysqli_free_result($res); }

    // Status-specific trip counts
    $res = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM trips GROUP BY status");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if ($row['status'] === 'pending') $pending_trips = $row['total'];
            elseif ($row['status'] === 'approved') $approved_trips = $row['total'];
            elseif ($row['status'] === 'in_transit') $transit_trips = $row['total'];
            elseif ($row['status'] === 'completed') $completed_trips = $row['total'];
        }
        mysqli_free_result($res);
    }

    // Maintenance logs count
    // Wait, let's check if the maintenance table exists before querying it to prevent SQL crashes
    $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'maintenance'");
    if ($table_check && mysqli_num_rows($table_check) > 0) {
        $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM maintenance WHERE status = 'in_progress' OR status = 'scheduled'");
        if ($res) { $row = mysqli_fetch_assoc($res); $maintenance_count = $row['total']; mysqli_free_result($res); }
    }
    
    // Recent 5 Tracking Updates
    $recent_query = "SELECT tu.*, t.trip_code FROM tracking_updates tu JOIN trips t ON tu.trip_id = t.id ORDER BY tu.updated_at DESC, tu.id DESC LIMIT 5";
    if ($r_res = mysqli_query($conn, $recent_query)) {
        while ($row = mysqli_fetch_assoc($r_res)) {
            $recent_updates[] = $row;
        }
        mysqli_free_result($r_res);
    }
} else {
    // Simulation Mode stats
    $total_vehicles = isset($_SESSION['mock_vehicles']) ? count($_SESSION['mock_vehicles']) : 0;
    $total_drivers = isset($_SESSION['mock_drivers']) ? count($_SESSION['mock_drivers']) : 0;
    
    $today_str = date('Y-m-d');
    if (isset($_SESSION['mock_trips'])) {
        $total_trips = count($_SESSION['mock_trips']);
        foreach ($_SESSION['mock_trips'] as $t) {
            if ($t['trip_date'] === $today_str) $today_trips++;
            if ($t['status'] === 'pending') $pending_trips++;
            elseif ($t['status'] === 'approved') $approved_trips++;
            elseif ($t['status'] === 'in_transit') $transit_trips++;
            elseif ($t['status'] === 'completed') $completed_trips++;
        }
    }
    
    if (isset($_SESSION['mock_maintenance'])) {
        foreach ($_SESSION['mock_maintenance'] as $m) {
            if ($m['status'] === 'in_progress' || $m['status'] === 'scheduled') $maintenance_count++;
        }
    }

    // Simulation recent updates
    if (isset($_SESSION['mock_tracking_updates'])) {
        $mock_updates = $_SESSION['mock_tracking_updates'];
        // Sort by date descending
        usort($mock_updates, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        
        $limit = min(5, count($mock_updates));
        for ($i = 0; $i < $limit; $i++) {
            $update = $mock_updates[$i];
            $trip_code = 'TRP-UNKNOWN';
            if (isset($_SESSION['mock_trips'][$update['trip_id']])) {
                $trip_code = $_SESSION['mock_trips'][$update['trip_id']]['trip_code'];
            }
            $recent_updates[] = [
                'id' => $update['id'],
                'trip_id' => $update['trip_id'],
                'trip_code' => $trip_code,
                'status' => $update['status'],
                'location' => $update['location'],
                'description' => $update['description'],
                'updated_at' => $update['created_at']
            ];
        }
    }
}

$page_title = "Dashboard";
require_once 'includes/header.php';
?>

<!-- Message Notification Banner -->
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #a7f3d0; padding: 14px 16px; border-radius: 10px; margin-bottom: 2rem;">
        <i data-lucide="check-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
        <?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<!-- Welcome User Banner -->
<div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.2) 0%, rgba(129, 140, 248, 0.05) 100%); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 16px; padding: 24px; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">Welcome back, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>!</h2>
    <p style="font-size: 0.9rem; color: var(--text-secondary);">Here is the real-time operational status of the transportation fleet for today.</p>
</div>

<!-- Stat Grid Dashboard -->
<div class="stats-grid">
    <!-- Stat Card: Vehicles -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(99, 102, 241, 0.1); color: #818cf8;">
            <i data-lucide="truck"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($total_vehicles); ?></span>
            <span class="stat-label">Total Fleet Vehicles</span>
        </div>
    </div>

    <!-- Stat Card: Drivers -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(16, 185, 129, 0.1); color: #34d399;">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($total_drivers); ?></span>
            <span class="stat-label">Registered Drivers</span>
        </div>
    </div>

    <!-- Stat Card: Today's Dispatch -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(245, 158, 11, 0.1); color: #fbbf24;">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($today_trips); ?></span>
            <span class="stat-label">Today's Dispatch Route</span>
        </div>
    </div>

    <!-- Stat Card: Total Trips -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(59, 130, 246, 0.1); color: #60a5fa;">
            <i data-lucide="navigation"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($total_trips); ?></span>
            <span class="stat-label">Total Booked Trips</span>
        </div>
    </div>
</div>

<!-- Status Breakdowns and Updates -->
<div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-top: 2rem;">
    
    <!-- Row 1: Recent Tracking Updates -->
    <div class="dashboard-panel" style="margin-bottom: 0;">
        <div class="panel-header" style="padding: 1.5rem 1.5rem 0.5rem 1.5rem; border-bottom: none;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Live Delivery Updates</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary);">Latest shipment progress logs across all active transit routes</p>
            </div>
        </div>
        <div class="table-container" style="padding: 0 1.5rem 1.5rem 1.5rem;">
            <table class="tms-table">
                <thead>
                    <tr>
                        <th>Trip Code</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th style="text-align: right;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_updates)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No tracking status updates logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_updates as $update): ?>
                            <tr>
                                <td><strong style="color: #8b5cf6; font-family: monospace; font-size: 0.95rem;"><?php echo htmlspecialchars($update['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td>
                                    <?php
                                    $status = $update['status'];
                                    $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; // fallback purple
                                    if (isset($TRACKING_STATUSES[$status])) {
                                        $badge_color = $TRACKING_STATUSES[$status];
                                        if ($badge_color === 'blue') { $badge_style = "background: rgba(59, 130, 246, 0.15); color: #60a5fa;"; }
                                        elseif ($badge_color === 'purple') { $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; }
                                        elseif ($badge_color === 'orange') { $badge_style = "background: rgba(245, 158, 11, 0.15); color: #fbbf24;"; }
                                        elseif ($badge_color === 'yellow') { $badge_style = "background: rgba(234, 179, 8, 0.15); color: #fef08a;"; }
                                        elseif ($badge_color === 'green') { $badge_style = "background: rgba(16, 185, 129, 0.15); color: #34d399;"; }
                                        elseif ($badge_color === 'darkgreen') { $badge_style = "background: rgba(4, 120, 87, 0.2); color: #059669;"; }
                                    }
                                    ?>
                                    <span style="<?php echo $badge_style; ?> padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; display: inline-block;">
                                        <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($update['location'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="text-align: right; color: var(--text-secondary); font-size: 0.85rem;"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Row 2: Status Breakdown Summary Panels -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
        <!-- Pending Card -->
        <div class="dashboard-panel" style="padding: 1.5rem; text-align: center; margin-bottom: 0;">
            <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 0.5rem;">Pending Approval</h4>
            <div style="font-size: 2rem; font-weight: 900; color: #fbbf24;"><?php echo $pending_trips; ?></div>
        </div>

        <!-- Approved Card -->
        <div class="dashboard-panel" style="padding: 1.5rem; text-align: center; margin-bottom: 0;">
            <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 0.5rem;">Staged / Approved</h4>
            <div style="font-size: 2rem; font-weight: 900; color: #60a5fa;"><?php echo $approved_trips; ?></div>
        </div>

        <!-- In Transit Card -->
        <div class="dashboard-panel" style="padding: 1.5rem; text-align: center; margin-bottom: 0;">
            <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 0.5rem;">In Transit</h4>
            <div style="font-size: 2rem; font-weight: 900; color: #c084fc;"><?php echo $transit_trips; ?></div>
        </div>

        <!-- Completed Card -->
        <div class="dashboard-panel" style="padding: 1.5rem; text-align: center; margin-bottom: 0;">
            <h4 style="font-size: 0.85rem; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 0.5rem;">Completed</h4>
            <div style="font-size: 2rem; font-weight: 900; color: #34d399;"><?php echo $completed_trips; ?></div>
        </div>
    </div>

</div>

<!-- Quick Action Shortcuts -->
<div class="dashboard-panel" style="margin-top: 2rem;">
    <div class="panel-header" style="border-bottom: none; padding: 1.5rem 1.5rem 0.5rem 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: white;">Quick Operations Shortcuts</h3>
    </div>
    <div style="display: flex; flex-wrap: wrap; gap: 15px; padding: 0 1.5rem 1.5rem 1.5rem; margin-top: 1rem;">
        <a href="add_vehicle.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
            <span>Add Vehicle</span>
        </a>
        <a href="add_driver.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
            <span>Add Driver</span>
        </a>
        <a href="add_trip.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i data-lucide="navigation-2" style="width: 16px; height: 16px;"></i>
            <span>Schedule Trip</span>
        </a>
        <a href="update_tracking.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i data-lucide="activity" style="width: 16px; height: 16px;"></i>
            <span>Post Tracking Log</span>
        </a>
        <a href="reports.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
            <span>View Reports</span>
        </a>
    </div>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
