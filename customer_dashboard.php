<?php
/**
 * Customer Dashboard
 * Transport Management System (TMS)
 */

// Include config
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Force login check
check_login();

// Restrict access to customers only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: index.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$my_orders = [];

if ($db_connected && $conn) {
    $sql = "SELECT t.*, v.vehicle_name, v.license_plate, d.full_name AS driver_name, d.phone AS driver_phone 
            FROM trips t 
            LEFT JOIN vehicles v ON t.vehicle_id = v.id 
            LEFT JOIN drivers d ON t.driver_id = d.id 
            WHERE t.customer_id = ? 
            ORDER BY t.trip_date DESC, t.id DESC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $my_orders[] = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Simulation Mode order list
    if (isset($_SESSION['mock_trips'])) {
        foreach ($_SESSION['mock_trips'] as $t) {
            if (intval($t['customer_id']) === $customer_id) {
                $v_name = '';
                $v_plate = '';
                if ($t['vehicle_id'] && isset($_SESSION['mock_vehicles'][$t['vehicle_id']])) {
                    $v_name = $_SESSION['mock_vehicles'][$t['vehicle_id']]['vehicle_name'];
                    $v_plate = $_SESSION['mock_vehicles'][$t['vehicle_id']]['license_plate'];
                }
                $d_name = '';
                $d_phone = '';
                if ($t['driver_id'] && isset($_SESSION['mock_drivers'][$t['driver_id']])) {
                    $d_name = $_SESSION['mock_drivers'][$t['driver_id']]['fullname'];
                    $d_phone = $_SESSION['mock_drivers'][$t['driver_id']]['phone'];
                }
                $my_orders[] = array_merge($t, [
                    'vehicle_name' => $v_name,
                    'license_plate' => $v_plate,
                    'driver_name' => $d_name,
                    'driver_phone' => $d_phone
                ]);
            }
        }
    }
}

$page_title = "Customer Portal";
require_once 'includes/header.php';
?>

<!-- Message Notification Banner -->
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #a7f3d0; padding: 14px 16px; border-radius: 10px; margin-bottom: 2rem;">
        <i data-lucide="check-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
        <?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<!-- Welcome Banner -->
<div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.05) 100%); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 16px; padding: 24px; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">Welcome to your Customer Portal, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>!</h2>
    <p style="font-size: 0.9rem; color: var(--text-secondary);">Query order dispatches, follow shipment timelines, and monitor routes.</p>
</div>

<!-- Search / Track Shipment Box -->
<div class="dashboard-panel" style="padding: 2rem; margin-bottom: 2rem; text-align: center;">
    <h3 style="font-size: 1.1rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">🔍 Track Any Dispatch Shipment</h3>
    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;">Enter your unique trip code to view the live journey log.</p>
    
    <form action="track.php" method="GET" style="display: flex; gap: 1rem; max-width: 600px; margin: 0 auto; flex-wrap: wrap;">
        <input type="hidden" name="search" value="1">
        <div style="flex-grow: 1; min-width: 250px;">
            <input type="text" name="code" class="form-control" placeholder="e.g. TRP-987214" required style="text-transform: uppercase; font-family: monospace; font-weight: 700;">
        </div>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="search" style="width: 16px; height: 16px;"></i>
            <span>Track Order</span>
        </button>
    </form>
</div>

<!-- Customer Order History List -->
<div class="dashboard-panel">
    <div class="panel-header" style="border-bottom: none; padding: 1.5rem 1.5rem 0.5rem 1.5rem;">
        <div>
            <h3 style="font-size: 1.1rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">My Cargo Bookings History</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary);">Monitor route details and status of all your dispatches.</p>
        </div>
    </div>
    
    <div class="table-container" style="padding: 0 1.5rem 1.5rem 1.5rem;">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>Trip Code</th>
                    <th>Date Scheduled</th>
                    <th>Route (From → To)</th>
                    <th>Driver Contact</th>
                    <th>Vehicle</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($my_orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No dispatches found in your booking history.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($my_orders as $trip): ?>
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
                            <td>
                                <?php if ($trip['driver_name']): ?>
                                    <strong><?php echo htmlspecialchars($trip['driver_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($trip['driver_phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">Not Assigned</span>
                                <?php endif; ?>
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
                                <a href="track.php?code=<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                    <i data-lucide="map" style="width: 12px; height: 12px; vertical-align: middle; margin-right: 4px;"></i>
                                    Track Journey
                                </a>
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
