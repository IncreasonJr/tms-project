<?php
// config.php - System Configuration and DB Init
// Conforms to spec.pdf requirements

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection Parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tms_db');

$db_connected = false;
$conn = null;

// Disable mysqli exceptions to prevent crashes in PHP 8.1+ on connection failures
@mysqli_report(MYSQLI_REPORT_OFF);

try {
    // Attempt database connection with warnings suppressed
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn) {
        $db_connected = true;
    }
} catch (Throwable $e) {
    $db_connected = false;
}

if ($db_connected) {
} else {
    // Session fallback initialization for frontend preview without a database
    if (!isset($_SESSION['mock_db'])) {
        $_SESSION['mock_db'] = true;
        
        $_SESSION['mock_vehicles'] = [
            1 => ['id' => 1, 'vehicle_name' => 'DAF XF Heavy Hauler', 'license_plate' => 'GW-2904-22', 'model' => 'DAF XF 105', 'capacity' => 40000, 'status' => 'available'],
            2 => ['id' => 2, 'vehicle_name' => 'Mercedes-Benz Actros', 'license_plate' => 'GT-8412-25', 'model' => 'Actros 2644', 'capacity' => 35000, 'status' => 'assigned'],
            3 => ['id' => 3, 'vehicle_name' => 'Toyota Dyna Box Truck', 'license_plate' => 'AS-1024-23', 'model' => 'Dyna 400', 'capacity' => 8000, 'status' => 'available'],
            4 => ['id' => 4, 'vehicle_name' => 'Hyundai H100 Cargo Van', 'license_plate' => 'ER-4521-24', 'model' => 'H100 Van', 'capacity' => 3500, 'status' => 'under_maintenance'],
            5 => ['id' => 5, 'vehicle_name' => 'MAN TGX Carrier', 'license_plate' => 'GT-1102-23', 'model' => 'TGX 26.440', 'capacity' => 38000, 'status' => 'out_of_service'],
            6 => ['id' => 6, 'vehicle_name' => 'Kia Bongo Delivery Truck', 'license_plate' => 'AS-9921-22', 'model' => 'Bongo III', 'capacity' => 4500, 'status' => 'available']
        ];

        $_SESSION['mock_drivers'] = [
            1 => ['id' => 1, 'fullname' => 'Kwame Mensah', 'license_number' => 'DL-GH9021482', 'phone' => '+233 24 123 4567', 'email' => 'kwame.mensah@fleet.com', 'address' => 'H/No 12, Kanda High Street, Accra', 'status' => 'available'],
            2 => ['id' => 2, 'fullname' => 'Kojo Boateng', 'license_number' => 'DL-GH8410294', 'phone' => '+233 20 234 5678', 'email' => 'kojo.boateng@fleet.com', 'address' => 'Block G, Adum, Kumasi', 'status' => 'on_trip'],
            3 => ['id' => 3, 'fullname' => 'Kofi Hanson', 'license_number' => 'DL-GH1029481', 'phone' => '+233 27 345 6789', 'email' => 'kofi.hanson@fleet.com', 'address' => 'Ashaley Botwe, Accra', 'status' => 'available'],
            4 => ['id' => 4, 'fullname' => 'Yaw Addo', 'license_number' => 'DL-GH4520194', 'phone' => '+233 55 456 7890', 'email' => 'yaw.addo@fleet.com', 'address' => 'Zongo Lane, Koforidua', 'status' => 'unavailable'],
            5 => ['id' => 5, 'fullname' => 'Amma Osei', 'license_number' => 'DL-GH1109283', 'phone' => '+233 24 567 8901', 'email' => 'amma.osei@fleet.com', 'address' => 'P.O. Box 45, Tamale', 'status' => 'available'],
            6 => ['id' => 6, 'fullname' => 'Abena Appiah', 'license_number' => 'DL-GH9920194', 'phone' => '+233 26 678 9012', 'email' => 'abena.appiah@fleet.com', 'address' => 'New Takoradi, Takoradi', 'status' => 'available']
        ];

        $_SESSION['mock_trips'] = [
            1 => ['id' => 1, 'trip_code' => 'TRP-987214', 'trip_date' => '2026-07-02', 'origin' => 'Accra, Greater Accra', 'destination' => 'Tamale, Northern', 'purpose' => 'Industrial machinery delivery to northern terminal', 'vehicle_id' => 2, 'driver_id' => 2, 'status' => 'approved', 'created_by' => 2],
            2 => ['id' => 2, 'trip_code' => 'TRP-112045', 'trip_date' => '2026-06-28', 'origin' => 'Tema, Greater Accra', 'destination' => 'Kumasi, Ashanti', 'purpose' => 'General harbor cargo transfer', 'vehicle_id' => 3, 'driver_id' => 3, 'status' => 'completed', 'created_by' => 2],
            3 => ['id' => 3, 'trip_code' => 'TRP-301149', 'trip_date' => '2026-07-05', 'origin' => 'Kumasi, Ashanti', 'destination' => 'Sunyani, Bono', 'purpose' => 'Seed and agricultural supply transit', 'vehicle_id' => 6, 'driver_id' => 1, 'status' => 'pending', 'created_by' => 3]
        ];

        $_SESSION['mock_maintenance'] = [
            1 => ['id' => 1, 'vehicle_id' => 4, 'maintenance_date' => '2026-06-24', 'description' => 'Radiator flushing and replacement of water pump.', 'cost' => 850.00, 'next_due_date' => '2026-09-24', 'status' => 'completed'],
            2 => ['id' => 2, 'vehicle_id' => 1, 'maintenance_date' => '2026-07-02', 'description' => 'Scheduled engine oil replacement and fuel filter checks.', 'cost' => 1200.00, 'next_due_date' => '2026-10-02', 'status' => 'scheduled'],
            3 => ['id' => 3, 'vehicle_id' => 5, 'maintenance_date' => '2026-06-15', 'description' => 'Full suspension and brake pads overhaul.', 'cost' => 3500.00, 'next_due_date' => null, 'status' => 'in_progress']
        ];

        $_SESSION['mock_tracking_updates'] = [
            1 => ['id' => 1, 'trip_id' => 1, 'status' => 'Order Received', 'location' => 'Accra Depot', 'description' => 'Trip order logged in system, awaiting dispatch.', 'created_at' => '2026-07-02 08:00:00'],
            2 => ['id' => 2, 'trip_id' => 1, 'status' => 'Vehicle Assigned', 'location' => 'Accra Central', 'description' => 'Mercedes-Benz Actros (GT-8412-25) and driver assigned.', 'created_at' => '2026-07-02 10:30:00'],
            3 => ['id' => 3, 'trip_id' => 2, 'status' => 'Order Received', 'location' => 'Tema Harbor Yard', 'description' => 'Port customs clearance obtained.', 'created_at' => '2026-06-28 07:00:00'],
            4 => ['id' => 4, 'trip_id' => 2, 'status' => 'Vehicle Assigned', 'location' => 'Tema Port', 'description' => 'Toyota Dyna Box Truck (AS-1024-23) assigned.', 'created_at' => '2026-06-28 08:30:00'],
            5 => ['id' => 5, 'trip_id' => 2, 'status' => 'Departing', 'location' => 'Tema Highway', 'description' => 'Left Tema Port area, heading towards Kumasi via N6.', 'created_at' => '2026-06-28 09:15:00'],
            6 => ['id' => 6, 'trip_id' => 2, 'status' => 'In Transit', 'location' => 'Koforidua Bypass', 'description' => 'En route. Driver bypassed Eastern corridor checkpoint.', 'created_at' => '2026-06-28 12:00:00'],
            7 => ['id' => 7, 'trip_id' => 2, 'status' => 'Arrived', 'location' => 'Kumasi Depot', 'description' => 'Parked at receiving bay, awaiting offload.', 'created_at' => '2026-06-28 15:30:00'],
            8 => ['id' => 8, 'trip_id' => 2, 'status' => 'Delivered', 'location' => 'Kumasi Yard', 'description' => 'Cargo unloaded, inspected, and signed off by client (K. Osei).', 'created_at' => '2026-06-28 16:00:00'],
            9 => ['id' => 9, 'trip_id' => 3, 'status' => 'Order Received', 'location' => 'Kumasi Depot', 'description' => 'Trip requested, awaiting vehicle loading.', 'created_at' => '2026-07-05 09:00:00']
        ];
    }
}
?>
