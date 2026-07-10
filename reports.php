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

// Enforce admin-only access check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

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

<!-- Page Header Banner -->
<div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(16, 185, 129, 0.06) 100%); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 20px; padding: 2rem 2.5rem; margin-bottom: 2.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.25); display: flex; align-items: center; justify-content: center; color: var(--accent-blue);">
                <i data-lucide="trending-up" style="width: 22px; height: 22px;"></i>
            </div>
            <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px;">System Reports &amp; Analytics</h2>
        </div>
        <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5;">Query historical logs, route metrics, and carrier performance data.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
        <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
        <span>Report generated: <?php echo date('M d, Y H:i'); ?></span>
    </div>
</div>

<!-- Premium Summary Metrics Grid -->
<?php
$completion_rate = $total_trips > 0 ? round(($completed_trips / $total_trips) * 100) : 0;
$cancellation_rate = $total_trips > 0 ? round(($cancelled_trips / $total_trips) * 100) : 0;
$in_progress = $total_trips - $completed_trips - $cancelled_trips;
?>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">

    <!-- Card 1: Total Bookings (Indigo) -->
    <div style="
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, rgba(99,102,241,0.18) 0%, rgba(99,102,241,0.06) 100%);
        border: 1px solid rgba(99,102,241,0.3);
        border-radius: 20px; padding: 1.75rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        cursor: default;
    " onmouseenter="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 20px 40px rgba(99,102,241,0.25)'; this.style.borderColor='rgba(99,102,241,0.5)'"
       onmouseleave="this.style.transform=''; this.style.boxShadow=''; this.style.borderColor='rgba(99,102,241,0.3)'">
        <!-- Top row: icon + label -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #a5b4fc;">Total Bookings</span>
            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.3); display: flex; align-items: center; justify-content: center; color: #818cf8;">
                <i data-lucide="navigation" style="width: 18px; height: 18px;"></i>
            </div>
        </div>
        <!-- Big number -->
        <div style="font-size: 3rem; font-weight: 900; color: #818cf8; line-height: 1; letter-spacing: -0.04em; margin-bottom: 0.75rem;">
            <?php echo number_format($total_trips); ?>
        </div>
        <!-- Sub-label -->
        <div style="font-size: 0.75rem; color: rgba(165,180,252,0.7);">All dispatched routes</div>
        <!-- Glow blob -->
        <div style="position: absolute; bottom: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(99,102,241,0.12); filter: blur(20px);"></div>
    </div>

    <!-- Card 2: Completed Deliveries (Emerald) -->
    <div style="
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, rgba(16,185,129,0.18) 0%, rgba(16,185,129,0.06) 100%);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: 20px; padding: 1.75rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        cursor: default;
    " onmouseenter="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 20px 40px rgba(16,185,129,0.25)'; this.style.borderColor='rgba(16,185,129,0.5)'"
       onmouseleave="this.style.transform=''; this.style.boxShadow=''; this.style.borderColor='rgba(16,185,129,0.3)'">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6ee7b7;">Completed</span>
            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.3); display: flex; align-items: center; justify-content: center; color: #34d399;">
                <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
            </div>
        </div>
        <div style="font-size: 3rem; font-weight: 900; color: #34d399; line-height: 1; letter-spacing: -0.04em; margin-bottom: 0.75rem;">
            <?php echo number_format($completed_trips); ?>
        </div>
        <!-- Progress bar -->
        <div style="margin-bottom: 0.5rem;">
            <div style="height: 4px; background: rgba(16,185,129,0.15); border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; width: <?php echo $completion_rate; ?>%; background: linear-gradient(90deg, #34d399, #10b981); border-radius: 2px; transition: width 1s ease;"></div>
            </div>
        </div>
        <div style="font-size: 0.75rem; color: rgba(110,231,183,0.7);"><?php echo $completion_rate; ?>% delivery success rate</div>
        <div style="position: absolute; bottom: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(16,185,129,0.12); filter: blur(20px);"></div>
    </div>

    <!-- Card 3: Cancelled (Red) -->
    <div style="
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, rgba(239,68,68,0.15) 0%, rgba(239,68,68,0.05) 100%);
        border: 1px solid rgba(239,68,68,0.25);
        border-radius: 20px; padding: 1.75rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        cursor: default;
    " onmouseenter="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 20px 40px rgba(239,68,68,0.2)'; this.style.borderColor='rgba(239,68,68,0.45)'"
       onmouseleave="this.style.transform=''; this.style.boxShadow=''; this.style.borderColor='rgba(239,68,68,0.25)'">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #fca5a5;">Cancelled Trips</span>
            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.25); display: flex; align-items: center; justify-content: center; color: #f87171;">
                <i data-lucide="x-circle" style="width: 18px; height: 18px;"></i>
            </div>
        </div>
        <div style="font-size: 3rem; font-weight: 900; color: #f87171; line-height: 1; letter-spacing: -0.04em; margin-bottom: 0.75rem;">
            <?php echo number_format($cancelled_trips); ?>
        </div>
        <div style="margin-bottom: 0.5rem;">
            <div style="height: 4px; background: rgba(239,68,68,0.1); border-radius: 2px; overflow: hidden;">
                <div style="height: 100%; width: <?php echo $cancellation_rate; ?>%; background: linear-gradient(90deg, #f87171, #ef4444); border-radius: 2px;"></div>
            </div>
        </div>
        <div style="font-size: 0.75rem; color: rgba(252,165,165,0.7);"><?php echo $cancellation_rate; ?>% cancellation rate</div>
        <div style="position: absolute; bottom: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(239,68,68,0.1); filter: blur(20px);"></div>
    </div>

    <!-- Card 4: Total Vehicles (Blue) -->
    <div style="
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, rgba(59,130,246,0.18) 0%, rgba(59,130,246,0.06) 100%);
        border: 1px solid rgba(59,130,246,0.3);
        border-radius: 20px; padding: 1.75rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        cursor: default;
    " onmouseenter="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 20px 40px rgba(59,130,246,0.25)'; this.style.borderColor='rgba(59,130,246,0.5)'"
       onmouseleave="this.style.transform=''; this.style.boxShadow=''; this.style.borderColor='rgba(59,130,246,0.3)'">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #93c5fd;">Fleet Vehicles</span>
            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.3); display: flex; align-items: center; justify-content: center; color: #60a5fa;">
                <i data-lucide="truck" style="width: 18px; height: 18px;"></i>
            </div>
        </div>
        <div style="font-size: 3rem; font-weight: 900; color: #60a5fa; line-height: 1; letter-spacing: -0.04em; margin-bottom: 0.75rem;">
            <?php echo number_format($total_vehicles); ?>
        </div>
        <div style="font-size: 0.75rem; color: rgba(147,197,253,0.7);">Registered in fleet registry</div>
        <div style="position: absolute; bottom: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(59,130,246,0.12); filter: blur(20px);"></div>
    </div>

    <!-- Card 5: Active Drivers (Amber) -->
    <div style="
        position: relative; overflow: hidden;
        background: linear-gradient(135deg, rgba(234,179,8,0.18) 0%, rgba(234,179,8,0.06) 100%);
        border: 1px solid rgba(234,179,8,0.3);
        border-radius: 20px; padding: 1.75rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        cursor: default;
    " onmouseenter="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 20px 40px rgba(234,179,8,0.2)'; this.style.borderColor='rgba(234,179,8,0.5)'"
       onmouseleave="this.style.transform=''; this.style.boxShadow=''; this.style.borderColor='rgba(234,179,8,0.3)'">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <span style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #fde68a;">Active Drivers</span>
            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(234,179,8,0.2); border: 1px solid rgba(234,179,8,0.3); display: flex; align-items: center; justify-content: center; color: #fbbf24;">
                <i data-lucide="users" style="width: 18px; height: 18px;"></i>
            </div>
        </div>
        <div style="font-size: 3rem; font-weight: 900; color: #fbbf24; line-height: 1; letter-spacing: -0.04em; margin-bottom: 0.75rem;">
            <?php echo number_format($total_drivers); ?>
        </div>
        <div style="font-size: 0.75rem; color: rgba(253,230,138,0.7);">Licensed &amp; registered</div>
        <div style="position: absolute; bottom: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(234,179,8,0.1); filter: blur(20px);"></div>
    </div>

</div>

<!-- Filters Panel -->
<div class="dashboard-panel" style="padding: 1.75rem; margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
        <i data-lucide="filter" style="width: 18px; height: 18px; color: var(--accent-blue);"></i>
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-primary);">Filter Trip History</h3>
    </div>
    
    <form action="reports.php" method="GET">
        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Trip Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">— All Statuses —</option>
                    <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending Approval</option>
                    <option value="approved" <?php echo ($status_filter === 'approved') ? 'selected' : ''; ?>>Approved / Staged</option>
                    <option value="in_transit" <?php echo ($status_filter === 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                    <option value="completed" <?php echo ($status_filter === 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 1.5rem; justify-content: flex-start; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="filter"></i>
                <span>Apply Filters</span>
            </button>
            <a href="reports.php" class="btn btn-secondary">
                <i data-lucide="rotate-ccw"></i>
                <span>Reset</span>
            </a>
        </div>
    </form>
</div>

<!-- Table Results Panel -->
<div class="dashboard-panel">
    <div class="panel-header" style="padding: 1.5rem 1.75rem 1rem 1.75rem; border-bottom: 1px solid var(--border-color); margin-bottom: 0;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary);">Trip Log Results</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.25rem;">
                <?php echo count($filtered_trips); ?> trip<?php echo count($filtered_trips) !== 1 ? 's' : ''; ?> found
                <?php if (!empty($status_filter) || !empty($from_date) || !empty($to_date)): ?>
                    with current filters
                <?php endif; ?>
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
            <i data-lucide="database" style="width: 14px; height: 14px;"></i>
            <span><?php echo $db_connected ? 'Live Database' : 'Simulation Mode'; ?></span>
        </div>
    </div>
    <div class="table-container" style="padding: 0 1.75rem 1.75rem 1.75rem;">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>Trip Code</th>
                    <th>Trip Date</th>
                    <th>Route (From → To)</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filtered_trips)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                                <i data-lucide="search-x" style="width: 32px; height: 32px; color: var(--text-muted);"></i>
                                <span>No trips match the specified filters.</span>
                                <a href="reports.php" style="color: var(--accent-blue); font-size: 0.8rem; font-weight: 600;">Clear all filters</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filtered_trips as $trip): ?>
                        <?php 
                        $status = $trip['status'];
                        $badge_class = 'status-pending';
                        if ($status === 'approved') $badge_class = 'status-approved';
                        elseif ($status === 'in_transit') $badge_class = 'status-in_transit';
                        elseif ($status === 'completed') $badge_class = 'status-completed';
                        elseif ($status === 'cancelled') $badge_class = 'status-cancelled';
                        ?>
                        <tr>
                            <td>
                                <code style="color: #a78bfa; font-weight: 700; font-size: 0.9rem; background: rgba(167, 139, 250, 0.08); padding: 0.2rem 0.5rem; border-radius: 6px; border: 1px solid rgba(167, 139, 250, 0.15);">
                                    <?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>
                                </code>
                            </td>
                            <td style="white-space: nowrap; color: var(--text-secondary);">
                                <?php echo htmlspecialchars(date('M d, Y', strtotime($trip['trip_date'])), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['origin'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <i data-lucide="arrow-right" style="width: 12px; height: 12px; color: var(--text-muted); flex-shrink: 0;"></i>
                                    <span style="color: var(--text-primary); font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </td>
                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($trip['vehicle_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($trip['driver_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="text-align: right;">
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?>
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
require_once 'includes/footer.php';
?>
