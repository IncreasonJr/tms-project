<?php
/**
 * View All Trips
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = "Trips";

// 10. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';

// 3. Query the database to fetch all records from the trips table
// 4. Join with vehicles and drivers tables to show vehicle and driver names
$query = "SELECT t.*, v.vehicle_name, d.full_name AS driver_name 
          FROM trips t
          LEFT JOIN vehicles v ON t.vehicle_id = v.id
          LEFT JOIN drivers d ON t.driver_id = d.id
          ORDER BY t.id DESC";

$result = mysqli_query($conn, $query);
?>

<!-- Header Section with Actions -->
<div class="page-header">
    <h2 class="page-title">Trip Management</h2>
    <!-- Add a button at the top: "Create New Trip" that links to add_trip.php -->
    <a href="add_trip.php" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20" fill="currentColor">
            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
        </svg>
        Create New Trip
    </a>
</div>

<!-- Main Table Card -->
<div class="content-card">
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <!-- Display the trips in a professional table -->
        <div class="tms-table-responsive">
            <table class="tms-table">
                <thead>
                    <tr>
                        <th>Trip Code</th>
                        <th>Trip Date</th>
                        <th>Origin</th>
                        <th>Destination</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <!-- Explicitly secure htmlspecialchars with ENT_QUOTES and UTF-8 -->
                            <td><strong style="color: #8b5cf6; font-family: monospace; font-size: 1rem;"><?php echo htmlspecialchars($row['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['origin'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['destination'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['vehicle_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['driver_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <!-- Color-coded badges for trip status -->
                                <?php 
                                $status = $row['status'];
                                $badge_class = 'badge-pending'; // Default
                                if ($status === 'approved') {
                                    $badge_class = 'badge-approved';
                                } elseif ($status === 'in_transit') {
                                    $badge_class = 'badge-in_transit';
                                } elseif ($status === 'completed') {
                                    $badge_class = 'badge-completed';
                                } elseif ($status === 'cancelled') {
                                    $badge_class = 'badge-cancelled';
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $status), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px; align-items: center;">
                                    <!-- Track button linking to track.php?code=[trip_code] -->
                                    <a href="track.php?code=<?php echo htmlspecialchars($row['trip_code'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-info" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                                        🔍 Track
                                    </a>
                                    <!-- Edit button linking to edit_trip.php?id=[trip_id] -->
                                    <a href="edit_trip.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <!-- Delete form replacing the GET link to prevent CSRF attacks -->
                                    <form action="delete_trip.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel/delete this trip? The vehicle and driver will be set back to available.');">
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
        <!-- Show "No trips found" if the table is empty -->
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" height="48" viewBox="0 -960 960 960" width="48" fill="currentColor">
                <path d="M440-80v-160h80v160h-80Zm0-240v-160h80v160h-80Zm0-240v-160h80v160h-80ZM184-80l144-640h104v-80H314L160-80h24Zm452 0h24L646-800H528v80h104l144 640Z"/>
            </svg>
            <div class="empty-title">No Trips Scheduled</div>
            <p>Click "Create New Trip" to record details about transport routes and assignments.</p>
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
