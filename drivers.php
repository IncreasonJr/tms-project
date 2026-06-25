<?php
/**
 * View All Drivers
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Drivers";

// 9. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';

// 3. Query the database to fetch all records from the drivers table
// We fetch drivers sorted by ID descending to see new additions first
$query = "SELECT * FROM drivers ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!-- Header Section with Actions -->
<div class="page-header">
    <h2 class="page-title">Driver Directory</h2>
    <!-- Add a button at the top: "Add New Driver" that links to add_driver.php -->
    <a href="add_driver.php" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor">
            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
        </svg>
        Add New Driver
    </a>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <!-- Display the drivers in a professional table -->
        <div class="tms-table-responsive">
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
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <!-- Explicitly secure htmlspecialchars with ENT_QUOTES and UTF-8 -->
                            <td>#<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><code style="background: rgba(255,255,255,0.06); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($row['license_number'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['email'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <!-- Color-coded badges for status -->
                                <?php 
                                $status = $row['status'];
                                $badge_class = 'badge-available'; // Default to available (green)
                                if ($status === 'on_trip') {
                                    $badge_class = 'badge-assigned'; // Orange badge-assigned styling
                                } elseif ($status === 'unavailable') {
                                    $badge_class = 'badge-under_maintenance'; // Red badge-under_maintenance styling
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center;">
                                    <!-- Edit button linking to edit_driver.php?id=[driver_id] -->
                                    <a href="edit_driver.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <!-- Delete form replacing the GET link to prevent CSRF attacks -->
                                    <form action="delete_driver.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this driver? This action cannot be undone.');">
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
        <!-- Show "No drivers found" if the table is empty -->
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" height="48" viewBox="0 -960 960 960" width="48" fill="currentColor">
                <path d="M480-480q-66 0-113-47t-113-113q0-66 113-113t113-47q66 0 113 47t113 113q0 66-113 113t-113 113ZM160-160v-32q0-34 17.5-62.5T224-296q64-35 135-51.5t121-16.5q50 0 121 16.5t135 51.5q31 15 48.5 43.5T800-192v32H160Zm80-80h480v-16q0-11-5.5-20T700-290q-54-29-109-42.5T480-346q-56 0-111 13.5T260-290q-9 5-14.5 14t-5.5 20v16Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/>
            </svg>
            <div class="empty-title">No Drivers Registered</div>
            <p>Click "Add New Driver" to add driver records to the system.</p>
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
