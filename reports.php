<?php
/**
 * Reports & Logs Dashboard
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authorization
check_login();

// 2. Default Filter Inputs
$from_date = isset($_GET['from_date']) ? sanitize_input($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? sanitize_input($_GET['to_date']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// 3. Summaries Aggregation
$total_trips = 0;
$completed_trips = 0;
$cancelled_trips = 0;
$total_vehicles = 0;
$total_drivers = 0;

$filtered_trips = [];

if ($db_connected && $conn) {
    // Summary aggregation queries (prepared or direct)
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_trips = $row['total']; mysqli_free_result($res); }

    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips WHERE status = 'completed'");
    if ($res) { $row = mysqli_fetch_assoc($res); $completed_trips = $row['total']; mysqli_free_result($res); }

    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM trips WHERE status = 'cancelled'");
    if ($res) { $row = mysqli_fetch_assoc($res); $cancelled_trips = $row['total']; mysqli_free_result($res); }

    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM vehicles");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_vehicles = $row['total']; mysqli_free_result($res); }

    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM drivers");
    if ($res) { $row = mysqli_fetch_assoc($res); $total_drivers = $row['total']; mysqli_free_result($res); }

    // Dynamic prepared statement for filtered trips search
    $query = "SELECT t.*, v.vehicle_name, d.full_name AS driver_name, a.fullname AS creator_name 
              FROM trips t
              LEFT JOIN vehicles v ON t.vehicle_id = v.id
              LEFT JOIN drivers d ON t.driver_id = d.id
              LEFT JOIN admins a ON t.created_by = a.id
              WHERE 1=1";
              
    $params = [];
    $types = "";
    
    if (!empty($from_date)) {
        $query .= " AND t.trip_date >= ?";
        $params[] = $from_date;
        $types .= "s";
    }
    
    if (!empty($to_date)) {
        $query .= " AND t.trip_date <= ?";
        $params[] = $to_date;
        $types .= "s";
    }
    
    if (!empty($status_filter)) {
        $query .= " AND t.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    $query .= " ORDER BY t.trip_date DESC, t.id DESC";
    
    if ($stmt = mysqli_prepare($conn, $query)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $filtered_trips[] = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Simulation Mode Summaries
    $total_vehicles = isset($_SESSION['mock_vehicles']) ? count($_SESSION['mock_vehicles']) : 0;
    $total_drivers = isset($_SESSION['mock_drivers']) ? count($_SESSION['mock_drivers']) : 0;
    if (isset($_SESSION['mock_trips'])) {
        $total_trips = count($_SESSION['mock_trips']);
        foreach ($_SESSION['mock_trips'] as $t) {
            if ($t['status'] === 'completed') $completed_trips++;
            elseif ($t['status'] === 'cancelled') $cancelled_trips++;
        }
        
        // Filter mock trips
        foreach ($_SESSION['mock_trips'] as $trip) {
            $match = true;
            if (!empty($from_date) && strcmp($trip['trip_date'], $from_date) < 0) $match = false;
            if (!empty($to_date) && strcmp($trip['trip_date'], $to_date) > 0) $match = false;
            if (!empty($status_filter) && $trip['status'] !== $status_filter) $match = false;
            
            if ($match) {
                $v_id = $trip['vehicle_id'];
                $d_id = $trip['driver_id'];
                $vehicle_name = '';
                if ($v_id && isset($_SESSION['mock_vehicles'][$v_id])) {
                    $vehicle_name = $_SESSION['mock_vehicles'][$v_id]['vehicle_name'];
                }
                $driver_name = '';
                if ($d_id && isset($_SESSION['mock_drivers'][$d_id])) {
                    $driver_name = $_SESSION['mock_drivers'][$d_id]['fullname'];
                }
                
                $filtered_trips[] = [
                    'id' => $trip['id'],
                    'trip_code' => $trip['trip_code'],
                    'trip_date' => $trip['trip_date'],
                    'origin' => $trip['origin'],
                    'destination' => $trip['destination'],
                    'purpose' => $trip['purpose'],
                    'status' => $trip['status'],
                    'vehicle_name' => $vehicle_name,
                    'driver_name' => $driver_name,
                    'creator_name' => 'System Dispatcher'
                ];
            }
        }
        
        // Sort mock results by date descending
        usort($filtered_trips, function($a, $b) {
            return strcmp($b['trip_date'], $a['trip_date']);
        });
    }
}

$page_title = "Reports & Logs";
require_once 'includes/header.php';
?>

<!-- Header -->
<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">System Reports & Analytics</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Query historical logs, route metrics, and carrier performance</p>
    </div>
</div>

<!-- Summary Statistics Cards Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 2rem;">
    <!-- Stat 1: Total Trips -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(99, 102, 241, 0.1); color: #818cf8;">
            <i data-lucide="navigation"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($total_trips); ?></span>
            <span class="stat-label">Total Bookings</span>
        </div>
    </div>

    <!-- Stat 2: Completed Trips -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(16, 185, 129, 0.1); color: #34d399;">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($completed_trips); ?></span>
            <span class="stat-label">Completed Deliveries</span>
        </div>
    </div>

    <!-- Stat 3: Cancelled Trips -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(239, 68, 68, 0.1); color: #f87171;">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($cancelled_trips); ?></span>
            <span class="stat-label">Cancelled Trips</span>
        </div>
    </div>

    <!-- Stat 4: Vehicles -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(59, 130, 246, 0.1); color: #60a5fa;">
            <i data-lucide="truck"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($total_vehicles); ?></span>
            <span class="stat-label">Total Vehicles</span>
        </div>
    </div>

    <!-- Stat 5: Drivers -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(234, 179, 8, 0.1); color: #fbbf24;">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value"><?php echo number_format($total_drivers); ?></span>
            <span class="stat-label">Active Drivers</span>
        </div>
    </div>
</div>

<!-- Filters Panel -->
<div class="dashboard-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="font-size: 1.1rem; font-weight: 700; color: white; margin-bottom: 1.25rem;">Filter Trip History</h3>
    
    <form action="reports.php" method="GET">
        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <!-- From Date -->
            <div class="form-group">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <!-- To Date -->
            <div class="form-group">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <!-- Status Filter -->
            <div class="form-group">
                <label for="status" class="form-label">Filter Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">-- All Statuses --</option>
                    <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending approval</option>
                    <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved / Staged</option>
                    <option value="in_transit" <?php echo ($status_filter === 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                    <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 1.25rem; justify-content: flex-start; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="filter"></i>
                <span>Apply Filters</span>
            </button>
            <a href="reports.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Table Results Panel -->
<div class="dashboard-panel">
    <div class="panel-header" style="border-bottom: none; padding: 1.5rem 1.5rem 0.5rem 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; color: white;">Filter Results</h3>
    </div>
    <div class="table-container" style="padding: 0 1.5rem 1.5rem 1.5rem;">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>Trip Code</th>
                    <th>Trip Date</th>
                    <th>Route (From → To)</th>
                    <th>Assigned Vehicle</th>
                    <th>Assigned Driver</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered_trips)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No trips match the specified filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filtered_trips as $trip): ?>
                        <tr>
                            <!-- Explicitly secure output with htmlspecialchars -->
                            <td><strong style="color: #8b5cf6; font-family: monospace; font-size: 0.95rem;"><?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($trip['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: white; font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['origin'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <i data-lucide="arrow-right" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                    <span style="color: white; font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($trip['vehicle_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($trip['driver_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php 
                                $status = $trip['status'];
                                $badge_class = 'status-pending'; // Default
                                if ($status === 'approved') {
                                    $badge_class = 'status-approved';
                                } elseif ($status === 'in_transit') {
                                    $badge_class = 'status-in_transit';
                                } elseif ($status === 'completed') {
                                    $badge_class = 'status-completed';
                                } elseif ($status === 'cancelled') {
                                    $badge_class = 'status-cancelled';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
