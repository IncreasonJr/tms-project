<?php
/**
 * Customer Profile
 * Transport Management System (TMS)
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

check_login();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: index.php");
    exit();
}

$customer_id = $_SESSION['user_id'];
$customer_record = null;
$delivery_history = [];

if ($db_connected && $conn) {
    $sql = "SELECT id, fullname, username, email, created_at FROM admins WHERE id = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $customer_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $customer_record = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }

    $history_sql = "SELECT t.*, v.vehicle_name, v.license_plate, d.full_name AS driver_name, d.phone AS driver_phone 
                    FROM trips t 
                    LEFT JOIN vehicles v ON t.vehicle_id = v.id 
                    LEFT JOIN drivers d ON t.driver_id = d.id 
                    WHERE t.customer_id = ? 
                    ORDER BY t.created_at DESC, t.id DESC";
    if ($stmt = mysqli_prepare($conn, $history_sql)) {
        mysqli_stmt_bind_param($stmt, 'i', $customer_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $delivery_history[] = $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $customer_record = get_mock_account_by_id($customer_id);
    if (isset($_SESSION['mock_trips'])) {
        foreach ($_SESSION['mock_trips'] as $trip) {
            if (isset($trip['customer_id']) && intval($trip['customer_id']) === $customer_id) {
                $trip['vehicle_name'] = $trip['vehicle_id'] && isset($_SESSION['mock_vehicles'][$trip['vehicle_id']]) ? $_SESSION['mock_vehicles'][$trip['vehicle_id']]['vehicle_name'] : '';
                $trip['license_plate'] = $trip['vehicle_id'] && isset($_SESSION['mock_vehicles'][$trip['vehicle_id']]) ? $_SESSION['mock_vehicles'][$trip['vehicle_id']]['license_plate'] : '';
                $trip['driver_name'] = $trip['driver_id'] && isset($_SESSION['mock_drivers'][$trip['driver_id']]) ? $_SESSION['mock_drivers'][$trip['driver_id']]['fullname'] : '';
                $trip['driver_phone'] = $trip['driver_id'] && isset($_SESSION['mock_drivers'][$trip['driver_id']]) ? $_SESSION['mock_drivers'][$trip['driver_id']]['phone'] : '';
                $delivery_history[] = $trip;
            }
        }
        usort($delivery_history, function ($a, $b) {
            return strcmp($b['trip_date'], $a['trip_date']);
        });
    }
}

$customer_name = $customer_record['fullname'] ?? ($_SESSION['user_name'] ?? 'Customer');
$customer_username = $customer_record['username'] ?? ($_SESSION['login_identifier'] ?? '');
$customer_email = $customer_record['email'] ?? '';
$customer_since = $customer_record['created_at'] ?? '';

$delivery_count = count($delivery_history);
$completed_count = 0;
$pending_count = 0;
foreach ($delivery_history as $trip) {
    if (($trip['status'] ?? '') === 'completed') {
        $completed_count++;
    } elseif (($trip['status'] ?? '') === 'pending') {
        $pending_count++;
    }
}

$page_title = 'Customer Profile';
require_once 'includes/header.php';
?>

<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <div class="dashboard-panel" style="padding: 1.75rem;">
        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: space-between;">
            <div>
                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.35rem;">Customer Profile</h2>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0;">Your account details and delivery history in one place.</p>
            </div>
            <a href="customer_dashboard.php" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
        <div class="dashboard-panel" style="padding: 1.5rem;">
            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-secondary); margin-bottom: 0.5rem;">Full Name</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary);"> <?php echo htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="dashboard-panel" style="padding: 1.5rem;">
            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-secondary); margin-bottom: 0.5rem;">Username</div>
            <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-primary);"><?php echo htmlspecialchars($customer_username, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <div class="dashboard-panel" style="padding: 1.5rem;">
            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-secondary); margin-bottom: 0.5rem;">Email</div>
            <div style="font-size: 1.05rem; font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars($customer_email ?: 'Not available', ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <div class="dashboard-panel" style="padding: 1.4rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: 900; color: #60a5fa;"><?php echo $delivery_count; ?></div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em;">Total Deliveries</div>
        </div>
        <div class="dashboard-panel" style="padding: 1.4rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: 900; color: #34d399;"><?php echo $completed_count; ?></div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em;">Completed</div>
        </div>
        <div class="dashboard-panel" style="padding: 1.4rem; text-align: center;">
            <div style="font-size: 2rem; font-weight: 900; color: #fbbf24;"><?php echo $pending_count; ?></div>
            <div style="font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em;">Pending</div>
        </div>
    </div>

    <div class="dashboard-panel" style="padding: 1.5rem;">
        <div class="panel-header" style="border-bottom: none; padding: 0 0 1rem 0;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Delivery History</h3>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin: 0;">Past and current dispatch requests tied to your account.</p>
            </div>
        </div>

        <div class="table-container">
            <table class="tms-table">
                <thead>
                    <tr>
                        <th>Trip Code</th>
                        <th>Date</th>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($delivery_history)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">You have not created any delivery requests yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($delivery_history as $trip): ?>
                            <tr>
                                <td><strong style="color: #8b5cf6; font-family: monospace;"><?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($trip['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(explode(',', $trip['origin'])[0] . ' → ' . explode(',', $trip['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($trip['driver_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($trip['vehicle_name'] ? $trip['vehicle_name'] . ' (' . $trip['license_plate'] . ')' : 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge status-<?php echo htmlspecialchars(str_replace(' ', '_', $trip['status']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $trip['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td style="text-align: right;">
                                    <a href="track.php?code=<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; border-radius: 6px;">Track</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>