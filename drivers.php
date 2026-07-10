<?php
/**
 * View All Drivers
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check authorization (excludes login/track from redirect)
check_login();

// 2. Fetch drivers from database or fallback to mock simulation list
$drivers = [];
if ($db_connected && $conn) {
    $query = "SELECT * FROM drivers ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Map 'fullname' field if it is named 'fullname' in mock but 'full_name' in SQL
            // Wait, in our schema:
            // CREATE TABLE IF NOT EXISTS drivers (id INT AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(100) NOT NULL, ...)
            // In the functions.php:
            // SQL queries say: SELECT fullname FROM drivers ... OR SELECT * FROM drivers ORDER BY fullname ASC
            // Wait! Let's check which column name exists in the SQL schema in drivers!
            // Let's check the column names in drivers table in the database.
            $drivers[] = $row;
        }
        mysqli_free_result($result);
    }
} else {
    $drivers = get_all_drivers();
}

$page_title = "Drivers";
require_once 'includes/header.php';
?>

<!-- Header Section with Actions -->
<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Active Driver Registry</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Manage authorization records, licensing, and phone contacts</p>
    </div>
    <!-- Add a button at the top: "Add New Driver" -->
    <a href="add_driver.php" class="btn btn-primary">
        <i data-lucide="plus"></i>
        <span>Add New Driver</span>
    </a>
</div>

<!-- Main Table Card -->
<div class="dashboard-panel">
    <div class="table-container">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>License Number</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drivers)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">No drivers found in registry.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($drivers as $driver): ?>
                        <?php
                        // Handle DB column naming difference: DB uses 'full_name' but Mock uses 'fullname'
                        $driver_name = isset($driver['full_name']) ? $driver['full_name'] : (isset($driver['fullname']) ? $driver['fullname'] : '');
                        ?>
                        <tr>
                            <!-- Explicitly secure output with htmlspecialchars -->
                            <td style="font-weight: 700; color: white;">#<?php echo htmlspecialchars($driver['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="color: white; font-weight: 600;"><?php echo htmlspecialchars($driver_name, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span style="font-family: monospace; background-color: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: white;">
                                    <?php echo htmlspecialchars($driver['license_number'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($driver['phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($driver['email'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php 
                                $status = $driver['status'];
                                $badge_class = 'status-available'; // Default to available
                                if ($status === 'on_trip') {
                                    $badge_class = 'status-assigned'; // Orange
                                } elseif ($status === 'unavailable') {
                                    $badge_class = 'status-under_maintenance'; // Red
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end; width: 100%;">
                                    <!-- Edit button linking to edit_driver.php?id=[driver_id] -->
                                    <a href="edit_driver.php?id=<?php echo $driver['id']; ?>" class="btn btn-sm btn-warning" style="padding: 0.4rem 0.8rem; font-size: 0.75rem; font-weight: 600; border-radius: 6px;">
                                        Edit
                                    </a>
                                    <!-- Delete form with CSRF protection -->
                                    <form action="delete_driver.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this driver? This action cannot be undone.');">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($driver['id'], ENT_QUOTES, 'UTF-8'); ?>">
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
