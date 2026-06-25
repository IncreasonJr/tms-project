<?php
/**
 * Reports Dashboard
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/**
 * Helper function to run count queries using prepared statements safely
 */
function get_count($conn, $query, $types = "", $params = []) {
    $count = 0;
    if ($stmt = mysqli_prepare($conn, $query)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $count = intval($row['total']);
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
    return $count;
}

// --- Section 1 & 3: Gather Statistics using prepared statements ---
$total_trips = get_count($conn, "SELECT COUNT(*) AS total FROM trips");
$completed_trips = get_count($conn, "SELECT COUNT(*) AS total FROM trips WHERE status = 'completed'");
$cancelled_trips = get_count($conn, "SELECT COUNT(*) AS total FROM trips WHERE status = 'cancelled'");
$total_vehicles = get_count($conn, "SELECT COUNT(*) AS total FROM vehicles");
$total_drivers = get_count($conn, "SELECT COUNT(*) AS total FROM drivers");

$today_trips = get_count($conn, "SELECT COUNT(*) AS total FROM trips WHERE trip_date = CURDATE()");
$week_trips = get_count($conn, "SELECT COUNT(*) AS total FROM trips WHERE YEARWEEK(trip_date, 1) = YEARWEEK(CURDATE(), 1)");
$month_trips = get_count($conn, "SELECT COUNT(*) AS total FROM trips WHERE MONTH(trip_date) = MONTH(CURDATE()) AND YEAR(trip_date) = YEAR(CURDATE())");

// --- Section 2: Trip Filtering logic ---
$from_date = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';

// Build the filtering query
$conditions = [];
$types = '';
$params = [];

if (!empty($from_date)) {
    $conditions[] = "t.trip_date >= ?";
    $types .= 's';
    $params[] = $from_date;
}
if (!empty($to_date)) {
    $conditions[] = "t.trip_date <= ?";
    $types .= 's';
    $params[] = $to_date;
}
if (!empty($status_filter) && $status_filter !== 'all') {
    $conditions[] = "t.status = ?";
    $types .= 's';
    $params[] = $status_filter;
}

$sql = "SELECT t.*, v.vehicle_name, d.full_name AS driver_name 
        FROM trips t 
        LEFT JOIN vehicles v ON t.vehicle_id = v.id 
        LEFT JOIN drivers d ON t.driver_id = d.id";

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY t.trip_date DESC, t.id DESC";

// Execute prepared statement for filters
$trips_result = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    if (count($params) > 0) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (mysqli_stmt_execute($stmt)) {
        $trips_result = mysqli_stmt_get_result($stmt);
    }
}

$page_title = "Reports";

// 4. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Section -->
<div class="page-header">
    <h2 class="page-title">Operational Reports</h2>
</div>

<!-- Section 1: Summary Statistics Cards -->
<section class="reports-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 32px;">
    
    <!-- Total Trips -->
    <div class="stat-card" style="border-left: 4px solid var(--primary-color);">
        <div class="stat-info">
            <span class="stat-number" style="color: var(--text-primary);"><?php echo number_format($total_trips); ?></span>
            <span class="stat-label">Total Trips</span>
        </div>
    </div>

    <!-- Completed Trips -->
    <div class="stat-card" style="border-left: 4px solid var(--success-color);">
        <div class="stat-info">
            <span class="stat-number" style="color: var(--success-color);"><?php echo number_format($completed_trips); ?></span>
            <span class="stat-label">Completed Trips</span>
        </div>
    </div>

    <!-- Cancelled Trips -->
    <div class="stat-card" style="border-left: 4px solid var(--danger-color);">
        <div class="stat-info">
            <span class="stat-number" style="color: var(--danger-color);"><?php echo number_format($cancelled_trips); ?></span>
            <span class="stat-label">Cancelled Trips</span>
        </div>
    </div>

    <!-- Fleet Size -->
    <div class="stat-card" style="border-left: 4px solid var(--info-color);">
        <div class="stat-info">
            <span class="stat-number" style="color: var(--info-color);"><?php echo number_format($total_vehicles); ?></span>
            <span class="stat-label">Vehicles Active</span>
        </div>
    </div>

    <!-- Active Drivers -->
    <div class="stat-card" style="border-left: 4px solid #10b981;">
        <div class="stat-info">
            <span class="stat-number" style="color: #10b981;"><?php echo number_format($total_drivers); ?></span>
            <span class="stat-label">Drivers Registered</span>
        </div>
    </div>

</section>

<!-- Section 3: Quick Stats (Operational Intervals) -->
<div class="content-card" style="margin-bottom: 32px; padding: 24px;">
    <h3 class="panel-title" style="margin-bottom: 16px;">Interval Analysis</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
        <div style="background: rgba(255, 255, 255, 0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 0.8125rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Today's Trips</div>
            <div style="font-size: 1.75rem; font-weight: 800; color: #a78bfa;"><?php echo number_format($today_trips); ?></div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 0.8125rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">This Week's Trips</div>
            <div style="font-size: 1.75rem; font-weight: 800; color: #60a5fa;"><?php echo number_format($week_trips); ?></div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center;">
            <div style="font-size: 0.8125rem; color: var(--text-secondary); text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">This Month's Trips</div>
            <div style="font-size: 1.75rem; font-weight: 800; color: #fbbf24;"><?php echo number_format($month_trips); ?></div>
        </div>
    </div>
</div>

<!-- Section 2: Trip History Table with Filter Form -->
<div class="content-card">
    <h3 class="panel-title" style="margin-bottom: 20px;">Trip Logs & Filters</h3>
    
    <!-- Filter form -->
    <form action="reports.php" method="GET" style="margin-bottom: 24px; background: rgba(15, 23, 42, 0.3); border: 1px solid var(--border-color); padding: 20px; border-radius: 12px;">
        <div class="form-row" style="align-items: end;">
            
            <!-- From Date -->
            <div class="form-group" style="margin-bottom: 0;">
                <label for="from_date" class="form-label">From Date</label>
                <input 
                    type="date" 
                    id="from_date" 
                    name="from_date" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>

            <!-- To Date -->
            <div class="form-group" style="margin-bottom: 0;">
                <label for="to_date" class="form-label">To Date</label>
                <input 
                    type="date" 
                    id="to_date" 
                    name="to_date" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>

            <!-- Status Dropdown -->
            <div class="form-group" style="margin-bottom: 0;">
                <label for="status" class="form-label">Trip Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="in_transit" <?php echo ($status_filter === 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                    <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>

            <!-- Submit buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 46px; padding: 0 24px;">
                    Apply Filters
                </button>
                <a href="reports.php" class="btn btn-secondary" style="height: 46px; display: inline-flex; align-items: center; justify-content: center;">
                    Reset
                </a>
            </div>

        </div>
    </form>

    <!-- Results Table -->
    <?php if ($trips_result && mysqli_num_rows($trips_result) > 0): ?>
        <div class="tms-table-responsive">
            <table class="tms-table">
                <thead>
                    <tr>
                        <th>Trip Code</th>
                        <th>Trip Date</th>
                        <th>Route</th>
                        <th>Vehicle Name</th>
                        <th>Driver Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($trips_result)): ?>
                        <tr>
                            <!-- 2. Use htmlspecialchars() when displaying data to prevent XSS attacks -->
                            <td><strong style="color: #8b5cf6; font-family: monospace;"><?php echo htmlspecialchars($row['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span><?php echo htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span style="color: var(--text-secondary); margin: 0 8px;">&rarr;</span>
                                <span><?php echo htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($row['vehicle_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['driver_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <!-- Color-coded badge -->
                                <?php 
                                $status = $row['status'];
                                $badge_class = 'badge-pending';
                                if ($status === 'approved') {
                                    $badge_class = 'badge-approved';
                                } elseif ($status === 'in_transit') {
                                    $badge_class = 'badge-in_transit';
                                } elseif ($status === 'completed') {
                                    $badge_class = 'badge-completed';
                                } elseif ($status === 'cancelled') {
                                    $badge_class = 'badge-cancelled';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" height="48" viewBox="0 -960 960 960" width="48" fill="currentColor">
                <path d="M440-80v-160h80v160h-80Zm0-240v-160h80v160h-80Zm0-240v-160h80v160h-80ZM184-80l144-640h104v-80H314L160-80h24Zm452 0h24L646-800H528v80h104l144 640Z"/>
            </svg>
            <div class="empty-title">No Matching Trips Found</div>
            <p>Adjust your dates or status selections and try again.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// Close result sets
if ($trips_result) {
    mysqli_free_result($trips_result);
}
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}

// Include footer layout
require_once 'includes/footer.php';
?>
