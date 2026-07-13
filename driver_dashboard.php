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
<div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(139, 92, 246, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.35); border-radius: 16px; padding: 24px; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">Hello Officer, <?php echo htmlspecialchars($driver_record ? ($driver_record['full_name'] ?? $driver_record['fullname'] ?? 'Driver') : 'Driver', ENT_QUOTES, 'UTF-8'); ?>!</h2>
    <p style="font-size: 0.9rem; color: var(--text-secondary);">Here are your currently assigned dispatches, transport status updates, and route schedules.</p>
</div>

<!-- Driver Profile Cards -->
<div class="driver-profile-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">

    <!-- Identity Card -->
    <div style="
        position: relative; overflow: hidden;
        background: linear-gradient(145deg, rgba(59,130,246,0.18) 0%, rgba(37,99,235,0.06) 100%);
        border: 1px solid rgba(59,130,246,0.3);
        border-radius: 20px; padding: 1.75rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: cardEntrance 0.5s cubic-bezier(0.34,1.56,0.64,1) both 0.05s;
    " onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 40px rgba(59,130,246,0.25)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(59,130,246,0.2); border: 1px solid rgba(59,130,246,0.3); display: flex; align-items: center; justify-content: center; color: #60a5fa; flex-shrink: 0;">
                <i data-lucide="user" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #93c5fd; margin-bottom: 0.2rem;">Driver Identity</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em;"><?php echo htmlspecialchars($driver_record ? ($driver_record['full_name'] ?? $driver_record['fullname'] ?? 'Driver Profile') : 'Driver Profile', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.15); border-radius: 10px; padding: 0.75rem 1rem;">
            <i data-lucide="credit-card" style="width: 16px; height: 16px; color: #60a5fa; flex-shrink: 0;"></i>
            <div>
                <div style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; color: #93c5fd; font-weight: 600;">License Number</div>
                <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary); font-family: monospace; letter-spacing: 1px;"><?php echo htmlspecialchars($driver_record['license_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <div style="position: absolute; bottom: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(59,130,246,0.12); filter: blur(25px);"></div>
    </div>

    <!-- Contact & Status Card -->
    <div style="
        position: relative; overflow: hidden;
        background: linear-gradient(145deg, rgba(16,185,129,0.18) 0%, rgba(5,150,105,0.06) 100%);
        border: 1px solid rgba(16,185,129,0.3);
        border-radius: 20px; padding: 1.75rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: cardEntrance 0.5s cubic-bezier(0.34,1.56,0.64,1) both 0.12s;
    " onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 20px 40px rgba(16,185,129,0.25)'" onmouseleave="this.style.transform='';this.style.boxShadow=''">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.3); display: flex; align-items: center; justify-content: center; color: #34d399; flex-shrink: 0;">
                <i data-lucide="phone" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6ee7b7; margin-bottom: 0.2rem;">Contact</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-primary);"><?php echo htmlspecialchars($driver_record['phone'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.15); border-radius: 10px; padding: 0.75rem 1rem;">
            <?php 
            $dstatus = strtolower($driver_record['status'] ?? 'available');
            $status_dot_color = $dstatus === 'available' ? '#34d399' : ($dstatus === 'on_trip' ? '#fbbf24' : '#f87171');
            ?>
            <div style="width: 10px; height: 10px; border-radius: 50%; background: <?php echo $status_dot_color; ?>; box-shadow: 0 0 8px <?php echo $status_dot_color; ?>; flex-shrink: 0; animation: pulse 2s infinite;"></div>
            <div>
                <div style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.08em; color: #6ee7b7; font-weight: 600;">Duty Status</div>
                <div style="font-size: 0.9rem; font-weight: 700; color: <?php echo $status_dot_color; ?>;"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $driver_record['status'] ?? 'Available')), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <div style="position: absolute; bottom: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(16,185,129,0.12); filter: blur(25px);"></div>
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
                                    <a href="track.php?code=<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                        <i data-lucide="activity" style="width: 12px; height: 12px; vertical-align: middle; margin-right: 4px;"></i>
                                        Log Status
                                    </a>
                                    <a href="track.php?code=<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-sm btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                        <i data-lucide="search" style="width: 12px; height: 12px; vertical-align: middle; margin-right: 2px;"></i> Tracker
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
