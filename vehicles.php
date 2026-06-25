<?php
/**
 * View All Vehicles
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Vehicles";

// 9. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';

// 3. Query the database to fetch all records from the vehicles table
// We fetch vehicles sorted by ID descending to see new additions first
$query = "SELECT * FROM vehicles ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!-- Header Section with Actions -->
<div class="page-header">
    <h2 class="page-title">Vehicle Fleet</h2>
    <!-- Add a button at the top: "Add New Vehicle" that links to add_vehicle.php -->
    <a href="add_vehicle.php" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor">
            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
        </svg>
        Add New Vehicle
    </a>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <!-- Display the vehicles in a professional table -->
        <div class="tms-table-responsive">
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
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <!-- Explicitly secure htmlspecialchars with ENT_QUOTES and UTF-8 -->
                            <td>#<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['vehicle_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><code style="background: rgba(255,255,255,0.06); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($row['license_plate'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><?php echo htmlspecialchars($row['model'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['capacity'] !== null ? $row['capacity'] : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <!-- Color-coded badges for status -->
                                <?php 
                                $status = $row['status'];
                                $badge_class = 'badge-available'; // Default to available (green)
                                if ($status === 'assigned') {
                                    $badge_class = 'badge-assigned'; // Orange
                                } elseif ($status === 'under_maintenance') {
                                    $badge_class = 'badge-under_maintenance'; // Red
                                } elseif ($status === 'out_of_service') {
                                    $badge_class = 'badge-out_of_service'; // Gray
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center;">
                                    <!-- Edit button linking to edit_vehicle.php?id=[vehicle_id] -->
                                    <a href="edit_vehicle.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <!-- Delete form replacing the GET link to prevent CSRF attacks -->
                                    <form action="delete_vehicle.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this vehicle? This action cannot be undone.');">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <!-- Show "No vehicles found" if the table is empty -->
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" height="48" viewBox="0 -960 960 960" width="48" fill="currentColor">
                <path d="M240-160q-33 0-56.5-23.5T160-240q0-33 23.5-56.5T240-320q33 0 56.5 23.5T320-240q0 33-23.5 56.5T240-160Zm360 0q-33 0-56.5-23.5T520-240q0-33 23.5-56.5T600-320q33 0 56.5 23.5T720-240q0 33-23.5 56.5T600-160ZM120-640v320h45l31-62q15-30 46-49t68-19q37 0 68 19.5t45 50.5l4 9h166q14-31 45-50.5t68-19.5q37 0 68 19t45 49l31 62h80v-200L680-640H120Zm0-80h560l160 200v280h-80q0-66-47-113t-113-47q-66 0-113 47t-47 113H360q0-66-47-113t-113-47q-66 0-113 47t-47 113H40v-400l80-80Zm0 80h560-560Z"/>
            </svg>
            <div class="empty-title">No Vehicles Registered</div>
            <p>Click "Add New Vehicle" to add fleet records to the system.</p>
        </div>
    <?php endif; ?>
</div>

<?php
// Close result set
if ($result) {
    mysqli_free_result($result);
}

// Include footer layout
require_once 'includes/footer.php';
?>
