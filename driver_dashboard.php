<?php
/**
 * Driver Dashboard
 * Transport Management System (TMS)
 */

// Include config
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Force login check
check_login();

// Restrict access to drivers only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'driver') {
    header("Location: index.php");
    exit();
}

// 1. Resolve logged-in email from database or simulation
$user_email = '';
if ($db_connected && $conn) {
    $sql = "SELECT email FROM admins WHERE id = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $user_email = $row['email'];
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Simulation Mode Kwame Mensah
    if ($_SESSION['user_id'] == 3) {
        $user_email = 'yard@fleet.com';
    }
}

// 2. Lookup driver record
$driver_id = 0;
$driver_record = null;
if ($db_connected && $conn) {
    $sql = "SELECT id, full_name, phone, license_number, status FROM drivers WHERE email = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $user_email);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $driver_id = $row['id'];
                $driver_record = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Simulation Mode lookup
    if ($user_email === 'yard@fleet.com') {
        $driver_id = 1;
        if (isset($_SESSION['mock_drivers'][1])) {
            $driver_record = $_SESSION['mock_drivers'][1];
        }
    }
}

// 3. Retrieve dispatches assigned to this driver
$my_trips = [];
if ($driver_id > 0) {
    if ($db_connected && $conn) {
        $sql = "SELECT t.*, v.vehicle_name, v.license_plate 
                FROM trips t 
                LEFT JOIN vehicles v ON t.vehicle_id = v.id 
                WHERE t.driver_id = ? 
                ORDER BY t.trip_date DESC, t.id DESC";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $driver_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $my_trips[] = $row;
                }
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Simulation Mode dispatches
        if (isset($_SESSION['mock_trips'])) {
            foreach ($_SESSION['mock_trips'] as $t) {
                if (intval($t['driver_id']) === $driver_id) {
                    $v_name = '';
                    $v_plate = '';
                    if ($t['vehicle_id'] && isset($_SESSION['mock_vehicles'][$t['vehicle_id']])) {
                        $v_name = $_SESSION['mock_vehicles'][$t['vehicle_id']]['vehicle_name'];
                        $v_plate = $_SESSION['mock_vehicles'][$t['vehicle_id']]['license_plate'];
                    }
                    $my_trips[] = array_merge($t, [
                        'vehicle_name' => $v_name,
                        'license_plate' => $v_plate
                    ]);
                }
            }
        }
    }
}

$page_title = "Driver Console";
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
<div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(16, 185, 129, 0.05) 100%); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 16px; padding: 24px; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">Hello Officer, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>!</h2>
    <p style="font-size: 0.9rem; color: var(--text-secondary);">Here are your currently assigned dispatches, transport status updates, and route schedules.</p>
</div>

<!-- Driver Meta Info & Active Vehicle Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 2rem;">
    <!-- Driver Info Card -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(59, 130, 246, 0.1); color: #60a5fa;">
            <i data-lucide="user"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" style="font-size: 1.2rem; word-break: break-all;"><?php echo htmlspecialchars($driver_record ? ($driver_record['full_name'] ?? $driver_record['fullname']) : 'Driver Profile', ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="stat-label">License: <?php echo htmlspecialchars($driver_record['license_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
    </div>

    <!-- Contact & Status Card -->
    <div class="stat-card">
        <div class="stat-icon-container" style="background-color: rgba(16, 185, 129, 0.1); color: #34d399;">
            <i data-lucide="phone"></i>
        </div>
        <div class="stat-details">
            <span class="stat-value" style="font-size: 1.2rem;"><?php echo htmlspecialchars($driver_record['phone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="stat-label">Status: <strong style="color: white;"><?php echo htmlspecialchars(ucfirst($driver_record['status'] ?? 'Available'), ENT_QUOTES, 'UTF-8'); ?></strong></span>
        </div>
    </div>
</div>

<!-- My Assigned dispatches -->
<div class="dashboard-panel">
    <div class="panel-header" style="border-bottom: none; padding: 1.5rem 1.5rem 0.5rem 1.5rem;">
        <div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">My Scheduled Voyages</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary);">Dispatches assigned to you. Click "Log status" to post tracking locations.</p>
        </div>
    </div>
    
    <div class="table-container" style="padding: 0 1.5rem 1.5rem 1.5rem;">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>Trip Code</th>
                    <th>Trip Date</th>
                    <th>Route (From → To)</th>
                    <th>Assigned Vehicle</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($my_trips)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No voyages assigned to you at this time.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($my_trips as $trip): ?>
                        <tr>
                            <td><strong style="color: #8b5cf6; font-family: monospace; font-size: 0.95rem;"><?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($trip['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: white; font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['origin'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <i data-lucide="arrow-right" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                    <span style="color: white; font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($trip['vehicle_name'] ? $trip['vehicle_name'] . ' (' . $trip['license_plate'] . ')' : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php 
                                $status = $trip['status'];
                                $badge_class = 'status-pending';
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
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; width: 100%;">
                                    <a href="update_tracking.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                        <i data-lucide="activity" style="width: 12px; height: 12px; vertical-align: middle; margin-right: 4px;"></i>
                                        Log Status
                                    </a>
                                    <a href="track.php?code=<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-sm btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                        🔍 Tracker
                                    </a>
                                </div>
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
