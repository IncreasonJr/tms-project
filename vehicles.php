<?php
/**
 * View All Vehicles
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authorization
check_login();

// Enforce admin-only access check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// 2. Fetch vehicles from database or fallback to mock simulation list
$vehicles = [];
if ($db_connected && $conn) {
    $query = "SELECT * FROM vehicles ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $vehicles[] = $row;
        }
        mysqli_free_result($result);
    }
} else {
    $vehicles = get_all_vehicles();
}

$page_title = "Vehicles";
require_once 'includes/header.php';
?>

<!-- Header Section with Actions -->
<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Active Vehicle Fleet</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Manage trucks, vans, and cargo carriers in service</p>
    </div>
    <!-- Add a button at the top: "Add New Vehicle" -->
    <a href="add_vehicle.php" class="btn btn-primary">
        <i data-lucide="plus"></i>
        <span>Add New Vehicle</span>
    </a>
</div>

<!-- Main Table Card -->
<div class="dashboard-panel">
    <div class="table-container">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle Name</th>
                    <th>License Plate</th>
                    <th>Model</th>
                    <th>Capacity (Tons)</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehicles)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">No vehicles found in fleet registry.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <!-- Explicitly secure output with htmlspecialchars -->
                            <td style="font-weight: 700; color: white;">#<?php echo htmlspecialchars($vehicle['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="color: white; font-weight: 600;"><?php echo htmlspecialchars($vehicle['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span style="font-family: monospace; background-color: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: white;">
                                    <?php echo htmlspecialchars($vehicle['license_plate'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($vehicle['model'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($vehicle['capacity'] !== null ? number_format($vehicle['capacity']) : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php 
                                $status = $vehicle['status'];
                                $badge_class = 'status-available'; // Default to available
                                if ($status === 'assigned') {
                                    $badge_class = 'status-assigned';
                                } elseif ($status === 'under_maintenance') {
                                    $badge_class = 'status-under_maintenance';
                                } elseif ($status === 'out_of_service') {
                                    $badge_class = 'status-out_of_service';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; width: 100%;">
                                    <!-- Edit button linking to edit_vehicle.php?id=[vehicle_id] -->
                                    <a href="edit_vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-sm btn-warning" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                        Edit
                                    </a>
                                    <!-- Delete form with CSRF protection -->
                                    <form action="delete_vehicle.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.');">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($vehicle['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px; background-color: var(--danger-color); color: white;">
                                            Delete
                                        </button>
                                    </form>
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
