<?php
/**
 * Cancel/Delete Trip
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check CSRF token validation on POST deletion request
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!validateCSRFToken($csrf_token)) {
    die("Security token validation failed. Deletion aborted.");
}

// 3. Get the trip ID from the POST parameter
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// 9. If trip ID is invalid or not found, redirect to trips.php
if ($id > 0) {
    
    // Start database transaction
    mysqli_begin_transaction($conn);
    $transaction_success = true;
    
    $vehicle_id = 0;
    $driver_id = 0;
    
    // 4. Before deleting, get the vehicle_id and driver_id from the trip
    // We use prepared statements for all database operations
    $select_query = "SELECT vehicle_id, driver_id FROM trips WHERE id = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $select_query)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $vehicle_id = intval($row['vehicle_id']);
                $driver_id = intval($row['driver_id']);
            } else {
                // Trip record does not exist
                $transaction_success = false;
            }
            mysqli_free_result($result);
        } else {
            $transaction_success = false;
        }
        mysqli_stmt_close($stmt);
    } else {
        $transaction_success = false;
    }
    
    if ($transaction_success) {
        // 5. Update the vehicle status back to 'available'
        if ($vehicle_id > 0) {
            $update_v_query = "UPDATE vehicles SET status = 'available' WHERE id = ?";
            if ($stmt_v = mysqli_prepare($conn, $update_v_query)) {
                mysqli_stmt_bind_param($stmt_v, "i", $vehicle_id);
                mysqli_stmt_execute($stmt_v);
                mysqli_stmt_close($stmt_v);
            }
        }
        
        // 6. Update the driver status back to 'available'
        if ($driver_id > 0) {
            $update_d_query = "UPDATE drivers SET status = 'available' WHERE id = ?";
            if ($stmt_d = mysqli_prepare($conn, $update_d_query)) {
                mysqli_stmt_bind_param($stmt_d, "i", $driver_id);
                mysqli_stmt_execute($stmt_d);
                mysqli_stmt_close($stmt_d);
            }
        }
        
        // 7. Delete the trip from the trips table using the ID
        $delete_query = "DELETE FROM trips WHERE id = ?";
        if ($stmt_del = mysqli_prepare($conn, $delete_query)) {
            mysqli_stmt_bind_param($stmt_del, "i", $id);
            mysqli_stmt_execute($stmt_del);
            mysqli_stmt_close($stmt_del);
        }
    }
    
    // Commit transaction if successful, otherwise rollback
    if ($transaction_success) {
        mysqli_commit($conn);
    } else {
        mysqli_rollback($conn);
    }
}

// 8. Redirect to trips.php after deletion
header("Location: trips.php");
exit();
?>
