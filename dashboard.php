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

// Enforce admin-only access check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

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

<!-- Premium Animated Stats Grid -->
<style>
@keyframes cardEntrance {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes numberPop {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.08); }
    100% { transform: scale(1); }
}
@keyframes shimmer {
    0%   { background-position: -200% center; }
    100% { background-position: 200% center; }
}
.dash-stat-card {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 2rem 1.75rem;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    cursor: default;
    transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1),
                box-shadow 0.35s ease,
                border-color 0.35s ease;
    animation: cardEntrance 0.55s cubic-bezier(0.34,1.56,0.64,1) both;
}
.dash-stat-card:nth-child(1) { animation-delay: 0.05s; }
.dash-stat-card:nth-child(2) { animation-delay: 0.12s; }
.dash-stat-card:nth-child(3) { animation-delay: 0.19s; }
.dash-stat-card:nth-child(4) { animation-delay: 0.26s; }
.dash-stat-card:hover {
    transform: translateY(-8px) scale(1.02);
}
.dash-stat-card:hover .dash-stat-number {
    animation: numberPop 0.4s ease;
}
.dash-stat-number {
    font-size: 3.25rem;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -0.05em;
    display: block;
    margin-bottom: 0.5rem;
    background-size: 200% auto;
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
.dash-stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    margin-bottom: 1.5rem;
}
.dash-stat-label {
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    opacity: 0.75;
}
.dash-stat-divider {
    height: 1px;
    margin: 1.25rem 0;
    opacity: 0.15;
}
.dash-stat-footer {
    font-size: 0.72rem;
    font-weight: 500;
    opacity: 0.6;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.dash-stat-blob {
    position: absolute;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    filter: blur(40px);
    bottom: -30px;
    right: -30px;
    pointer-events: none;
}
</style>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem;">

    <!-- Card 1: Fleet Vehicles (Indigo/Purple) -->
    <div class="dash-stat-card" style="
        background: linear-gradient(145deg, rgba(99,102,241,0.2) 0%, rgba(139,92,246,0.08) 100%);
        border: 1px solid rgba(99,102,241,0.35);
        box-shadow: 0 4px 24px rgba(99,102,241,0.12);
    " onmouseenter="this.style.boxShadow='0 24px 48px rgba(99,102,241,0.3)'; this.style.borderColor='rgba(99,102,241,0.6)'"
       onmouseleave="this.style.boxShadow='0 4px 24px rgba(99,102,241,0.12)'; this.style.borderColor='rgba(99,102,241,0.35)'">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.3); color: #a5b4fc;">
            <i data-lucide="truck" style="width: 24px; height: 24px;"></i>
        </div>
        <span class="dash-stat-number" data-target="<?php echo $total_vehicles; ?>" style="background-image: linear-gradient(135deg, #a5b4fc 0%, #818cf8 50%, #6366f1 100%);">
            <?php echo number_format($total_vehicles); ?>
        </span>
        <div class="dash-stat-label" style="color: #a5b4fc;">Total Fleet Vehicles</div>
        <div class="dash-stat-divider" style="background: #818cf8;"></div>
        <div class="dash-stat-footer" style="color: #a5b4fc;">
            <i data-lucide="activity" style="width: 12px; height: 12px;"></i>
            <span>Registered in fleet registry</span>
        </div>
        <div class="dash-stat-blob" style="background: rgba(99,102,241,0.25);"></div>
    </div>

    <!-- Card 2: Registered Drivers (Emerald) -->
    <div class="dash-stat-card" style="
        background: linear-gradient(145deg, rgba(16,185,129,0.2) 0%, rgba(5,150,105,0.08) 100%);
        border: 1px solid rgba(16,185,129,0.35);
        box-shadow: 0 4px 24px rgba(16,185,129,0.12);
    " onmouseenter="this.style.boxShadow='0 24px 48px rgba(16,185,129,0.3)'; this.style.borderColor='rgba(16,185,129,0.6)'"
       onmouseleave="this.style.boxShadow='0 4px 24px rgba(16,185,129,0.12)'; this.style.borderColor='rgba(16,185,129,0.35)'">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.3); color: #6ee7b7;">
            <i data-lucide="users" style="width: 24px; height: 24px;"></i>
        </div>
        <span class="dash-stat-number" data-target="<?php echo $total_drivers; ?>" style="background-image: linear-gradient(135deg, #6ee7b7 0%, #34d399 50%, #10b981 100%);">
            <?php echo number_format($total_drivers); ?>
        </span>
        <div class="dash-stat-label" style="color: #6ee7b7;">Registered Drivers</div>
        <div class="dash-stat-divider" style="background: #34d399;"></div>
        <div class="dash-stat-footer" style="color: #6ee7b7;">
            <i data-lucide="shield-check" style="width: 12px; height: 12px;"></i>
            <span>Licensed &amp; active personnel</span>
        </div>
        <div class="dash-stat-blob" style="background: rgba(16,185,129,0.2);"></div>
    </div>

    <!-- Card 3: Today's Dispatch (Amber) -->
    <div class="dash-stat-card" style="
        background: linear-gradient(145deg, rgba(245,158,11,0.2) 0%, rgba(217,119,6,0.08) 100%);
        border: 1px solid rgba(245,158,11,0.35);
        box-shadow: 0 4px 24px rgba(245,158,11,0.12);
    " onmouseenter="this.style.boxShadow='0 24px 48px rgba(245,158,11,0.3)'; this.style.borderColor='rgba(245,158,11,0.6)'"
       onmouseleave="this.style.boxShadow='0 4px 24px rgba(245,158,11,0.12)'; this.style.borderColor='rgba(245,158,11,0.35)'">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.3); color: #fde68a;">
            <i data-lucide="calendar-check" style="width: 24px; height: 24px;"></i>
        </div>
        <span class="dash-stat-number" data-target="<?php echo $today_trips; ?>" style="background-image: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f59e0b 100%);">
            <?php echo number_format($today_trips); ?>
        </span>
        <div class="dash-stat-label" style="color: #fde68a;">Today's Dispatch Route</div>
        <div class="dash-stat-divider" style="background: #fbbf24;"></div>
        <div class="dash-stat-footer" style="color: #fde68a;">
            <i data-lucide="clock" style="width: 12px; height: 12px;"></i>
            <span><?php echo date('D, M d Y'); ?></span>
        </div>
        <div class="dash-stat-blob" style="background: rgba(245,158,11,0.2);"></div>
    </div>

    <!-- Card 4: Total Trips (Blue) -->
    <div class="dash-stat-card" style="
        background: linear-gradient(145deg, rgba(59,130,246,0.2) 0%, rgba(37,99,235,0.08) 100%);
        border: 1px solid rgba(59,130,246,0.35);
        box-shadow: 0 4px 24px rgba(59,130,246,0.12);
    " onmouseenter="this.style.boxShadow='0 24px 48px rgba(59,130,246,0.3)'; this.style.borderColor='rgba(59,130,246,0.6)'"
       onmouseleave="this.style.boxShadow='0 4px 24px rgba(59,130,246,0.12)'; this.style.borderColor='rgba(59,130,246,0.35)'">
        <div class="dash-stat-icon" style="background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.3); color: #93c5fd;">
            <i data-lucide="navigation" style="width: 24px; height: 24px;"></i>
        </div>
        <span class="dash-stat-number" data-target="<?php echo $total_trips; ?>" style="background-image: linear-gradient(135deg, #93c5fd 0%, #60a5fa 50%, #3b82f6 100%);">
            <?php echo number_format($total_trips); ?>
        </span>
        <div class="dash-stat-label" style="color: #93c5fd;">Total Booked Trips</div>
        <div class="dash-stat-divider" style="background: #60a5fa;"></div>
        <div class="dash-stat-footer" style="color: #93c5fd;">
            <i data-lucide="bar-chart-2" style="width: 12px; height: 12px;"></i>
            <span>All dispatched routes</span>
        </div>
        <div class="dash-stat-blob" style="background: rgba(59,130,246,0.2);"></div>
    </div>

</div>

<script>
// Count-up animation for stat numbers
document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('.dash-stat-number[data-target]');
    counters.forEach(el => {
        const target = parseInt(el.getAttribute('data-target'), 10);
        if (isNaN(target) || target === 0) return;
        let start = 0;
        const duration = 900;
        const step = Math.ceil(target / (duration / 16));
        const timer = setInterval(() => {
            start += step;
            if (start >= target) {
                start = target;
                clearInterval(timer);
            }
            el.textContent = start.toLocaleString();
        }, 16);
    });
});
</script>


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
<div style="margin-top: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem;">
        <i data-lucide="zap" style="width: 18px; height: 18px; color: var(--accent-blue);"></i>
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-primary);">Quick Operations</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">

        <a href="add_vehicle.php" style="
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.75rem; padding: 1.5rem 1rem; text-decoration: none;
            background: linear-gradient(145deg, rgba(59,130,246,0.15), rgba(59,130,246,0.05));
            border: 1px solid rgba(59,130,246,0.25); border-radius: 16px;
            color: #60a5fa; text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        " onmouseenter="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 32px rgba(59,130,246,0.25)';this.style.borderColor='rgba(59,130,246,0.5)'"
           onmouseleave="this.style.transform='';this.style.boxShadow='';this.style.borderColor='rgba(59,130,246,0.25)'">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.3); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="truck" style="width: 22px; height: 22px;"></i>
            </div>
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);">Add Vehicle</span>
            <span style="font-size: 0.7rem; color: #93c5fd; opacity: 0.8;">Register fleet unit</span>
        </a>

        <a href="add_driver.php" style="
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.75rem; padding: 1.5rem 1rem; text-decoration: none;
            background: linear-gradient(145deg, rgba(16,185,129,0.15), rgba(16,185,129,0.05));
            border: 1px solid rgba(16,185,129,0.25); border-radius: 16px;
            color: #34d399; text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        " onmouseenter="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 32px rgba(16,185,129,0.25)';this.style.borderColor='rgba(16,185,129,0.5)'"
           onmouseleave="this.style.transform='';this.style.boxShadow='';this.style.borderColor='rgba(16,185,129,0.25)'">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.3); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="user-plus" style="width: 22px; height: 22px;"></i>
            </div>
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);">Add Driver</span>
            <span style="font-size: 0.7rem; color: #6ee7b7; opacity: 0.8;">Register personnel</span>
        </a>

        <a href="add_trip.php" style="
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.75rem; padding: 1.5rem 1rem; text-decoration: none;
            background: linear-gradient(145deg, rgba(167,139,250,0.15), rgba(167,139,250,0.05));
            border: 1px solid rgba(167,139,250,0.25); border-radius: 16px;
            color: #a78bfa; text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        " onmouseenter="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 32px rgba(167,139,250,0.25)';this.style.borderColor='rgba(167,139,250,0.5)'"
           onmouseleave="this.style.transform='';this.style.boxShadow='';this.style.borderColor='rgba(167,139,250,0.25)'">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(167,139,250,0.2); border: 1px solid rgba(167,139,250,0.3); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="navigation-2" style="width: 22px; height: 22px;"></i>
            </div>
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);">Schedule Trip</span>
            <span style="font-size: 0.7rem; color: #c4b5fd; opacity: 0.8;">Dispatch a route</span>
        </a>

        <a href="update_tracking.php" style="
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.75rem; padding: 1.5rem 1rem; text-decoration: none;
            background: linear-gradient(145deg, rgba(245,158,11,0.15), rgba(245,158,11,0.05));
            border: 1px solid rgba(245,158,11,0.25); border-radius: 16px;
            color: #fbbf24; text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        " onmouseenter="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 32px rgba(245,158,11,0.25)';this.style.borderColor='rgba(245,158,11,0.5)'"
           onmouseleave="this.style.transform='';this.style.boxShadow='';this.style.borderColor='rgba(245,158,11,0.25)'">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.3); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="activity" style="width: 22px; height: 22px;"></i>
            </div>
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);">Post Tracking Log</span>
            <span style="font-size: 0.7rem; color: #fde68a; opacity: 0.8;">Update status</span>
        </a>

        <a href="reports.php" style="
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.75rem; padding: 1.5rem 1rem; text-decoration: none;
            background: linear-gradient(145deg, rgba(244,63,94,0.15), rgba(244,63,94,0.05));
            border: 1px solid rgba(244,63,94,0.25); border-radius: 16px;
            color: #fb7185; text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        " onmouseenter="this.style.transform='translateY(-5px)';this.style.boxShadow='0 16px 32px rgba(244,63,94,0.25)';this.style.borderColor='rgba(244,63,94,0.5)'"
           onmouseleave="this.style.transform='';this.style.boxShadow='';this.style.borderColor='rgba(244,63,94,0.25)'">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(244,63,94,0.2); border: 1px solid rgba(244,63,94,0.3); display: flex; align-items: center; justify-content: center;">
                <i data-lucide="trending-up" style="width: 22px; height: 22px;"></i>
            </div>
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-primary);">View Reports</span>
            <span style="font-size: 0.7rem; color: #fda4af; opacity: 0.8;">Analytics &amp; logs</span>
        </a>

    </div>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
