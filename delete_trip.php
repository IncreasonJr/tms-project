<?php
// delete_trip.php - Delete/Cancel Trip Handler
// Conforms to spec.pdf requirements (Module 5: Trip Management - Delete)

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check authorization
check_login();

$trip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$trip = get_trip_by_id($trip_id);

if ($trip) {
    $code = $trip['trip_code'];
    $success = delete_trip($trip_id);
    if ($success) {
        header("Location: trips.php?msg=Trip+" . urlencode($code) . "+cancelled+and+removed+successfully.&type=warning");
        exit;
    }
}

header("Location: trips.php?msg=Failed+to+remove+trip+order.&type=error");
exit;
?>
