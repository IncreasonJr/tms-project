<?php
// reports.php - System Reports & Usage Analytics
// Conforms to spec.pdf requirements (Module 7: Reports)

require_once __DIR__ . '/includes/header.php';

// Default Date Ranges (Last 30 days)
$from_date = isset($_GET['from_date']) ? sanitize_input($_GET['from_date']) : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? sanitize_input($_GET['to_date']) : date('Y-m-d');

$trips_in_range = [];
$total_trips_count = 0;
$completed_count = 0;
$pending_count = 0;
$transit_count = 0;
$cancelled_count = 0;

if ($db_connected) {
    // Database query with date filtering
    $sql = "SELECT t.*, a.fullname as creator_name FROM trips t 
            JOIN admins a ON t.created_by = a.id 
            WHERE t.trip_date BETWEEN '$from_date' AND '$to_date' 
            ORDER BY t.trip_date DESC, t.id DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $trips_in_range[] = $row;
            $total_trips_count++;
            if ($row['status'] === 'completed') $completed_count++;
            if ($row['status'] === 'pending') $pending_count++;
            if ($row['status'] === 'in_transit') $transit_count++;
            if ($row['status'] === 'cancelled') $cancelled_count++;
        }
    }
} else {
    // Fallback simulation filtering
    foreach ($_SESSION['mock_trips'] as $trip) {
        if ($trip['trip_date'] >= $from_date && $trip['trip_date'] <= $to_date) {
            $trip['creator_name'] = isset($_SESSION['mock_admin_name']) ? $_SESSION['mock_admin_name'] : 'System Dispatcher';
            $trips_in_range[] = $trip;
            $total_trips_count++;
            if ($trip['status'] === 'completed') $completed_count++;
            if ($trip['status'] === 'pending') $pending_count++;
            if ($trip['status'] === 'in_transit') $transit_count++;
            if ($trip['status'] === 'cancelled') $cancelled_count++;
        }
    }
    // sort by date descending
    usort($trips_in_range, function($a, $b) {
        return strcmp($b['trip_date'], $a['trip_date']);
    });
}

// Calculate percentages for vehicle utilization reports
$completed_pct = $total_trips_count > 0 ? Math.round(($completed_count / $total_trips_count) * 100) : 0;
$transit_pct = $total_trips_count > 0 ? Math.round(($transit_count / $total_trips_count) * 100) : 0;
$pending_pct = $total_trips_count > 0 ? Math.round(($pending_count / $total_trips_count) * 100) : 0;
$cancelled_pct = $total_trips_count > 0 ? Math.round(($cancelled_count / $total_trips_count) * 100) : 0;

// Custom PHP Math round helper mapping to standard JS math round
class Math {
    public static function round($val) {
        return round($val);
    }
}
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Analytics & Reports</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Query historical trip logs, vehicle utilisation share, and dispatch performance metrics</p>
    </div>
</div>

<!-- Date Filters Bar -->
<div class="filter-bar">
    <form action="reports.php" method="GET" class="filter-form">
        <div class="form-group">
            <label for="from_date" class="form-label">Range From Date</label>
            <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
        </div>
        <div class="form-group">
            <label for="to_date" class="form-label">Range To Date</label>
            <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
            <i data-lucide="filter"></i>
            <span>Filter Report</span>
        </button>
    </form>
</div>

<!-- KPI Summary Indicators -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-content">
            <h3>Dispatched Trips</h3>
            <div class="kpi-value"><?php echo $total_trips_count; ?></div>
        </div>
        <div class="kpi-icon">
            <i data-lucide="navigation"></i>
        </div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-content">
            <h3>Completed Routes</h3>
            <div class="kpi-value"><?php echo $completed_count; ?></div>
        </div>
        <div class="kpi-icon">
            <i data-lucide="check-circle"></i>
        </div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-content">
            <h3>Transit Active</h3>
            <div class="kpi-value"><?php echo $transit_count; ?></div>
        </div>
        <div class="kpi-icon">
            <i data-lucide="truck"></i>
        </div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-content">
            <h3>Pending / Hold</h3>
            <div class="kpi-value"><?php echo $pending_count; ?></div>
        </div>
        <div class="kpi-icon">
            <i data-lucide="clock"></i>
        </div>
    </div>
</div>

<div class="dashboard-row" style="grid-template-cols: 2fr 1fr;">
    
    <!-- Left Panel: Trip History Report Table -->
    <div class="dashboard-panel">
        <h3 class="panel-title" style="margin-bottom: 1.5rem;">Trip History Log List</h3>
        
        <div class="table-container">
            <table class="tms-table">
                <thead>
                    <tr>
                        <th>Trip Code</th>
                        <th>Trip Date</th>
                        <th>Origin -> Destination</th>
                        <th>Assigned Vehicle</th>
                        <th>Assigned Driver</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trips_in_range)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">No trips found within the selected date range.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($trips_in_range as $trip): ?>
                            <tr>
                                <td style="font-weight: 700; color: white;"><?php echo $trip['trip_code']; ?></td>
                                <td><?php echo date('d M Y', strtotime($trip['trip_date'])); ?></td>
                                <td>
                                    <div style="color: white; font-weight: 600;"><?php echo explode(',', $trip['origin'])[0]; ?> -> <?php echo explode(',', $trip['destination'])[0]; ?></div>
                                </td>
                                <td><?php echo get_vehicle_name($trip['vehicle_id']); ?></td>
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

    <!-- Right Panel: Vehicle Utilization Visual Report -->
    <div class="dashboard-panel">
        <h3 class="panel-title" style="margin-bottom: 1.5rem;">Route Utilization Share</h3>
        
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.5rem;">
                    <span>Completed Routes</span>
                    <strong style="color: var(--status-available);"><?php echo $completed_pct; ?>%</strong>
                </div>
                <div style="height: 8px; background-color: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo $completed_pct; ?>%; height: 100%; background-color: var(--status-available); border-radius: 10px; transition: width 0.5s ease-out;"></div>
                </div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.5rem;">
                    <span>In Transit Active</span>
                    <strong style="color: var(--accent-blue);"><?php echo $transit_pct; ?>%</strong>
                </div>
                <div style="height: 8px; background-color: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo $transit_pct; ?>%; height: 100%; background-color: var(--accent-blue); border-radius: 10px; transition: width 0.5s ease-out;"></div>
                </div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.5rem;">
                    <span>Pending Action</span>
                    <strong style="color: var(--status-maintenance);"><?php echo $pending_pct; ?>%</strong>
                </div>
                <div style="height: 8px; background-color: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo $pending_pct; ?>%; height: 100%; background-color: var(--status-maintenance); border-radius: 10px; transition: width 0.5s ease-out;"></div>
                </div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.5rem;">
                    <span>Cancelled Orders</span>
                    <strong style="color: var(--status-danger);"><?php echo $cancelled_pct; ?>%</strong>
                </div>
                <div style="height: 8px; background-color: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                    <div style="width: <?php echo $cancelled_pct; ?>%; height: 100%; background-color: var(--status-danger); border-radius: 10px; transition: width 0.5s ease-out;"></div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
