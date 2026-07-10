<?php
/**
 * View All Trips
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authorization (excludes login/track from redirect)
check_login();

// Restrict customer role access
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer') {
    header("Location: customer_dashboard.php");
    exit();
}

// 2. Resolve driver_id if user is a driver
$driver_id = 0;
if ($_SESSION['user_role'] === 'driver') {
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
        
        if ($user_email !== '') {
            $sql = "SELECT id FROM drivers WHERE email = ? LIMIT 1";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $user_email);
                if (mysqli_stmt_execute($stmt)) {
                    $result = mysqli_stmt_get_result($stmt);
                    if ($row = mysqli_fetch_assoc($result)) {
                        $driver_id = $row['id'];
                    }
                    mysqli_free_result($result);
                }
                mysqli_stmt_close($stmt);
            }
        }
    } else {
        // Simulation Kwame Mensah
        if ($_SESSION['user_id'] == 3) {
            $driver_id = 1;
        }
    }
}

// 3. Fetch trips from database or fallback to mock simulation list
$trips = [];
if ($db_connected && $conn) {
    if ($_SESSION['user_role'] === 'driver') {
        $query = "SELECT t.*, v.vehicle_name, v.license_plate, d.full_name AS driver_name, a.fullname AS creator_name 
                  FROM trips t
                  LEFT JOIN vehicles v ON t.vehicle_id = v.id
                  LEFT JOIN drivers d ON t.driver_id = d.id
                  LEFT JOIN admins a ON t.created_by = a.id
                  WHERE t.driver_id = ?
                  ORDER BY t.id DESC";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $driver_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $trips[] = $row;
                }
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $query = "SELECT t.*, v.vehicle_name, v.license_plate, d.full_name AS driver_name, a.fullname AS creator_name 
                  FROM trips t
                  LEFT JOIN vehicles v ON t.vehicle_id = v.id
                  LEFT JOIN drivers d ON t.driver_id = d.id
                  LEFT JOIN admins a ON t.created_by = a.id
                  ORDER BY t.id DESC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $trips[] = $row;
            }
            mysqli_free_result($result);
        }
    }
} else {
    if ($_SESSION['user_role'] === 'driver') {
        $all_trips = get_all_trips();
        foreach ($all_trips as $t) {
            if (intval($t['driver_id']) === $driver_id) {
                $trips[] = $t;
            }
        }
    } else {
        $trips = get_all_trips();
    }
}

$page_title = "Trips";
require_once 'includes/header.php';
?>

<!-- Header Section with Actions -->
<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Trips & Dispatch Console</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Monitor route status, scheduling, and carrier assignments</p>
    </div>
    <!-- Add a button at the top: "Schedule New Trip" -->
    <?php if ($_SESSION['user_role'] === 'admin'): ?>
    <a href="add_trip.php" class="btn btn-primary">
        <i data-lucide="plus"></i>
        <span>Schedule New Trip</span>
    </a>
    <?php endif; ?>
</div>

<!-- Main Table Card -->
<div class="dashboard-panel">
    <div class="table-container">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>Trip Code</th>
                    <th>Trip Date</th>
                    <th>Route (From → To)</th>
                    <th>Assigned Vehicle</th>
                    <th>Assigned Driver</th>
                    <th>Status</th>
                    <th>Dispatcher</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem;">No trips have been scheduled.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trips as $trip): ?>
                        <?php
                        $vehicle_info = '';
                        if (isset($trip['vehicle_name'])) {
                            $vehicle_info = $trip['vehicle_name'] . (isset($trip['license_plate']) ? ' (' . $trip['license_plate'] . ')' : '');
                        } else {
                            $vehicle_info = get_vehicle_name($trip['vehicle_id']);
                        }
                        
                        $driver_info = isset($trip['driver_name']) ? $trip['driver_name'] : get_driver_name($trip['driver_id']);
                        $dispatcher_name = isset($trip['creator_name']) ? $trip['creator_name'] : 'System Dispatcher';
                        ?>
                        <tr>
                            <!-- Explicitly secure output with htmlspecialchars -->
                            <td><strong style="color: #8b5cf6; font-family: monospace; font-size: 1rem;"><?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($trip['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 4px;">
                                    <span style="color: white; font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['origin'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <i data-lucide="arrow-right" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                    <span style="color: white; font-weight: 600;"><?php echo htmlspecialchars(explode(',', $trip['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <?php if (!empty($trip['purpose'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-secondary); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($trip['purpose'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($trip['purpose'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($vehicle_info, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($driver_info, ENT_QUOTES, 'UTF-8'); ?></td>
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
                            <td style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo htmlspecialchars($dispatcher_name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; width: 100%;">
                                    <!-- Track button linking to track.php?code=[trip_code] -->
                                    <a href="track.php?code=<?php echo htmlspecialchars($trip['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-info" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px; background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                                        Track
                                    </a>
                                    <!-- Edit button linking to edit_trip.php?id=[trip_id] -->
                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="edit_trip.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-warning" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                        Edit
                                    </a>
                                    <!-- Delete form with CSRF protection -->
                                    <form action="delete_trip.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel/delete this trip? The vehicle and driver will be set back to available.');">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($trip['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px; background-color: var(--danger-color); color: white;">
                                            Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
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
// Include footer layout
require_once 'includes/footer.php';
?>
