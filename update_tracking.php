<?php
/**
 * Redundant Update Tracking page redirect handler
 * Transport Management System (TMS)
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Force login check
check_login();

// Redirect to track.php if trip_id is present
$selected_trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;
if ($selected_trip_id > 0) {
    $trip = null;
    if ($db_connected && $conn) {
        $trip = getTripById($selected_trip_id);
    } else {
        $trip = get_trip_by_id($selected_trip_id);
    }
    
    if ($trip && !empty($trip['trip_code'])) {
        header("Location: track.php?code=" . urlencode($trip['trip_code']));
        exit();
    }
}

// Fallback to Dashboard
header("Location: dashboard.php");
exit();
