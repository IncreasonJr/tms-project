<?php
/**
 * Delete Vehicle
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Enforce admin-only access check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Check CSRF token validation on POST deletion request
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validateCSRFToken($csrf_token)) {
    die("Security token validation failed. Deletion aborted.");
}

// 3. Get the vehicle ID from the POST parameter
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// 6. Check if vehicle ID is valid, otherwise skip deletion and redirect
if ($id > 0) {
    if ($db_connected && $conn) {
        // Use prepared statement to delete the vehicle, preventing SQL injection
        $query = "DELETE FROM vehicles WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $query)) {
            // Bind the ID parameter
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            // Execute the delete query
            mysqli_stmt_execute($stmt);
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    } else {
        // Simulation Mode delete
        if (isset($_SESSION['mock_vehicles'][$id])) {
            unset($_SESSION['mock_vehicles'][$id]);
        }
    }
}

// 5. Redirect to vehicles.php after deletion
header("Location: vehicles.php");
exit();
?>
