<?php
/**
 * Configuration File
 * Transport Management System (TMS)
 */

// 1. Session Cookie Hardening & Configurations
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Set cookie_secure if HTTPS is active
$is_secure = (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) 
             || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $is_secure ? 1 : 0);

// Start PHP Session if it doesn't already exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Load Database Credentials from root .env file if it exists
$db_server = 'localhost';
$db_username = 'root';
$db_password = '123';
$db_name = 'tms_db';

$env_path = __DIR__ . '/../.env';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Strip quotes around value
            $value = trim($value, "\"'");
            
            switch ($key) {
                case 'DB_SERVER':
                    $db_server = $value;
                    break;
                case 'DB_USERNAME':
                    $db_username = $value;
                    break;
                case 'DB_PASSWORD':
                    $db_password = $value;
                    break;
                case 'DB_NAME':
                    $db_name = $value;
                    break;
            }
        }
    }
}

if (!defined('DB_SERVER')) define('DB_SERVER', $db_server);
if (!defined('DB_USERNAME')) define('DB_USERNAME', $db_username);
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $db_password);
if (!defined('DB_NAME')) define('DB_NAME', $db_name);

// Define aliases for frontend compatibility
if (!defined('DB_HOST')) define('DB_HOST', $db_server);
if (!defined('DB_USER')) define('DB_USER', $db_username);
if (!defined('DB_PASS')) define('DB_PASS', $db_password);

$db_connected = false;
$conn = null;

// Suppress errors and try connecting
@mysqli_report(MYSQLI_REPORT_OFF);
try {
    $conn = @mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn) {
        $db_connected = true;
    }
} catch (Throwable $e) {
    $db_connected = false;
}

// 2. Session Inactivity Timeout (30 minutes)
$timeout_duration = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    // Session expired: clear authentication variables
    $auth_keys = ['user_id', 'username', 'user_name', 'user_role', 'login_identifier', 'driver_id', 'last_activity'];
    foreach ($auth_keys as $key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    // Clear cookies and destroy session ONLY if DB is connected
    if ($db_connected) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        session_destroy();
    }
    header("Location: login.php?timeout=1");
    exit();
}
// Update last activity timestamp
$_SESSION['last_activity'] = time();

// 5. Initialize Simulation Mock Database if DB connection is offline
if (!$db_connected) {
    error_log("Database connection failed. TMS falling back to Simulation Mode.");
    
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

// 6. Define BASE_URL constant for the project
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/tms-project/');
}

// 7. CSRF Token Helper Functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// 8. Tracking Statuses Configuration
$TRACKING_STATUSES = [
    'Order Received' => 'blue',
    'Vehicle Assigned' => 'purple',
    'Departing' => 'orange',
    'In Transit' => 'yellow',
    'Arrived' => 'green',
    'Delivered' => 'darkgreen'
];

/**
 * Insert a new tracking update into the tracking_updates table
 */
function addTrackingUpdate($trip_id, $status, $location, $description) {
    global $conn, $db_connected;
    $trip_id = intval($trip_id);
    
    // Map tracking status to main trip status
    $mapped_trip_status = 'pending';
    if ($status === 'Order Received') {
        $mapped_trip_status = 'pending';
    } else if ($status === 'Vehicle Assigned') {
        $mapped_trip_status = 'approved';
    } else if ($status === 'Delivered') {
        $mapped_trip_status = 'completed';
    } else {
        $mapped_trip_status = 'in_transit'; // Departing, In Transit, Arrived
    }

    if ($db_connected && $conn) {
        $query = "INSERT INTO tracking_updates (trip_id, status, location, description) VALUES (?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "isss", $trip_id, $status, $location, $description);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($result) {
                // Sync main trip status
                $sync_query = "UPDATE trips SET status = ? WHERE id = ?";
                if ($stmt_sync = mysqli_prepare($conn, $sync_query)) {
                    mysqli_stmt_bind_param($stmt_sync, "si", $mapped_trip_status, $trip_id);
                    mysqli_stmt_execute($stmt_sync);
                    mysqli_stmt_close($stmt_sync);
                }
                
                // If Delivered, release vehicle and driver status to available
                if ($status === 'Delivered') {
                    $trip = getTripById($trip_id);
                    if ($trip) {
                        if ($trip['vehicle_id']) {
                            mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = " . intval($trip['vehicle_id']));
                        }
                        if ($trip['driver_id']) {
                            mysqli_query($conn, "UPDATE drivers SET status = 'available' WHERE id = " . intval($trip['driver_id']));
                        }
                    }
                }
            }
            return $result;
        }
        return false;
    } else {
        // Simulation mode logic
        $new_id = empty($_SESSION['mock_tracking_updates']) ? 1 : max(array_keys($_SESSION['mock_tracking_updates'])) + 1;
        $_SESSION['mock_tracking_updates'][$new_id] = [
            'id' => $new_id,
            'trip_id' => $trip_id,
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Sync main trip status in session
        if (isset($_SESSION['mock_trips'][$trip_id])) {
            $_SESSION['mock_trips'][$trip_id]['status'] = $mapped_trip_status;
        }
        
        // Release vehicle and driver if Delivered
        if ($status === 'Delivered') {
            $trip = isset($_SESSION['mock_trips'][$trip_id]) ? $_SESSION['mock_trips'][$trip_id] : null;
            if ($trip) {
                $vehicle_id = $trip['vehicle_id'];
                $driver_id = $trip['driver_id'];
                if ($vehicle_id && isset($_SESSION['mock_vehicles'][$vehicle_id])) {
                    $_SESSION['mock_vehicles'][$vehicle_id]['status'] = 'available';
                }
                if ($driver_id && isset($_SESSION['mock_drivers'][$driver_id])) {
                    $_SESSION['mock_drivers'][$driver_id]['status'] = 'available';
                }
            }
        }
        return true;
    }
}

/**
 * Fetch all tracking updates for a specific trip (newest first)
 */
function getTrackingHistory($trip_id) {
    global $conn, $db_connected;
    $trip_id = intval($trip_id);
    
    if ($db_connected && $conn) {
        $history = [];
        $query = "SELECT *, created_at AS updated_at FROM tracking_updates WHERE trip_id = ? ORDER BY created_at DESC, id DESC";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $trip_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $history[] = $row;
                }
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
        return $history;
    } else {
        $updates = [];
        if (isset($_SESSION['mock_tracking_updates'])) {
            foreach ($_SESSION['mock_tracking_updates'] as $update) {
                if (intval($update['trip_id']) === $trip_id) {
                    $formatted_update = [
                        'id' => $update['id'],
                        'trip_id' => $update['trip_id'],
                        'status' => $update['status'],
                        'location' => $update['location'],
                        'description' => $update['description'],
                        'updated_at' => $update['created_at'] // map created_at to updated_at
                    ];
                    $updates[] = $formatted_update;
                }
            }
        }
        // sort by updated_at descending
        usort($updates, function($a, $b) {
            return strcmp($b['updated_at'], $a['updated_at']);
        });
        return $updates;
    }
}

/**
 * Fetch trip details and all tracking updates for a trip using its trip code
 */
function getTrackingByCode($trip_code) {
    global $conn, $db_connected;
    global $TRACKING_STATUSES;
    
    $trip_code = trim($trip_code);
    
    if ($db_connected && $conn) {
        $query = "SELECT t.id, t.driver_id, t.vehicle_id, t.trip_code, t.origin, t.destination, t.trip_date, 
                         v.vehicle_name, v.license_plate, 
                         d.full_name AS driver_name, d.phone AS driver_phone
                  FROM trips t
                  LEFT JOIN vehicles v ON t.vehicle_id = v.id
                  LEFT JOIN drivers d ON t.driver_id = d.id
                  WHERE t.trip_code = ? LIMIT 1";
                  
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "s", $trip_code);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($trip = mysqli_fetch_assoc($result)) {
                    mysqli_free_result($result);
                    mysqli_stmt_close($stmt);
                    
                    $trip_id = intval($trip['id']);
                    $history = getTrackingHistory($trip_id);
                    $history = getTrackingHistory($trip_id);
                    $latest_status = getLatestTrackingStatus($trip_id);
                    $color = isset($TRACKING_STATUSES[$latest_status]) ? $TRACKING_STATUSES[$latest_status] : 'gray';
                    
                    return [
                        'trip_details' => [
                            'id' => $trip['id'],
                            'driver_id' => $trip['driver_id'],
                            'vehicle_id' => $trip['vehicle_id'],
                            'trip_code' => $trip['trip_code'],
                            'origin' => $trip['origin'],
                            'destination' => $trip['destination'],
                            'trip_date' => $trip['trip_date'],
                            'vehicle_name' => $trip['vehicle_name'],
                            'license_plate' => $trip['license_plate'],
                            'driver_name' => $trip['driver_name'],
                            'driver_phone' => $trip['driver_phone']
                        ],
                        'tracking_history' => $history,
                        'current_status' => $latest_status,
                        'status_color' => $color
                    ];
                }
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    } else {
        // Simulation mode logic
        $found_trip = null;
        foreach ($_SESSION['mock_trips'] as $trip) {
            if (strtolower($trip['trip_code']) === strtolower($trip_code)) {
                $found_trip = $trip;
                break;
            }
        }
        
        if ($found_trip) {
            $trip_id = intval($found_trip['id']);
            $vehicle_id = $found_trip['vehicle_id'];
            $driver_id = $found_trip['driver_id'];
            
            $v_name = 'Not Assigned';
            $v_plate = '—';
            if ($vehicle_id && isset($_SESSION['mock_vehicles'][$vehicle_id])) {
                $v_name = $_SESSION['mock_vehicles'][$vehicle_id]['vehicle_name'];
                $v_plate = $_SESSION['mock_vehicles'][$vehicle_id]['license_plate'];
            }
            
            $d_name = 'Not Assigned';
            $d_phone = '—';
            if ($driver_id && isset($_SESSION['mock_drivers'][$driver_id])) {
                $d_name = $_SESSION['mock_drivers'][$driver_id]['fullname'];
                $d_phone = $_SESSION['mock_drivers'][$driver_id]['phone'];
            }
            
            $history = getTrackingHistory($trip_id);
            $latest_status = getLatestTrackingStatus($trip_id);
            $color = isset($TRACKING_STATUSES[$latest_status]) ? $TRACKING_STATUSES[$latest_status] : 'gray';
            
            return [
                'trip_details' => [
                    'id' => $found_trip['id'],
                    'driver_id' => $found_trip['driver_id'],
                    'vehicle_id' => $found_trip['vehicle_id'],
                    'trip_code' => $found_trip['trip_code'],
                    'origin' => $found_trip['origin'],
                    'destination' => $found_trip['destination'],
                    'trip_date' => $found_trip['trip_date'],
                    'vehicle_name' => $v_name,
                    'license_plate' => $v_plate,
                    'driver_name' => $d_name,
                    'driver_phone' => $d_phone
                ],
                'tracking_history' => $history,
                'current_status' => $latest_status,
                'status_color' => $color
            ];
        }
        return false;
    }
}

/**
 * Fetch trip details by ID
 */
function getTripById($trip_id) {
    global $conn, $db_connected;
    $trip_id = intval($trip_id);
    
    if ($db_connected && $conn) {
        $query = "SELECT * FROM trips WHERE id = ? LIMIT 1";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $trip_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $trip = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
                mysqli_stmt_close($stmt);
                return $trip;
            }
            mysqli_stmt_close($stmt);
        }
        return null;
    } else {
        return isset($_SESSION['mock_trips'][$trip_id]) ? $_SESSION['mock_trips'][$trip_id] : null;
    }
}

/**
 * Fetch all trips with trip_code, origin, destination formatted for dropdowns
 */
function getAllTripsForDropdown() {
    global $conn, $db_connected;
    $trips = [];
    
    if ($db_connected && $conn) {
        $query = "SELECT id, trip_code, origin, destination FROM trips ORDER BY trip_code ASC";
        if ($result = mysqli_query($conn, $query)) {
            while ($row = mysqli_fetch_assoc($result)) {
                $formatted = $row['trip_code'] . " - " . $row['origin'] . " to " . $row['destination'];
                $trips[] = [
                    'id' => $row['id'],
                    'trip_code' => $row['trip_code'],
                    'display_text' => $formatted
                ];
            }
            mysqli_free_result($result);
        }
    } else {
        // Simulation mode
        if (isset($_SESSION['mock_trips'])) {
            foreach ($_SESSION['mock_trips'] as $row) {
                $formatted = $row['trip_code'] . " - " . $row['origin'] . " to " . $row['destination'];
                $trips[] = [
                    'id' => $row['id'],
                    'trip_code' => $row['trip_code'],
                    'display_text' => $formatted
                ];
            }
        }
    }
    return $trips;
}

/**
 * Fetch the most recent tracking status for a trip
 */
function getLatestTrackingStatus($trip_id) {
    global $conn, $db_connected;
    $trip_id = intval($trip_id);
    
    if ($db_connected && $conn) {
        $status = null;
        $query = "SELECT status FROM tracking_updates WHERE trip_id = ? ORDER BY updated_at DESC, id DESC LIMIT 1";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $trip_id);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $status = $row['status'];
                }
                mysqli_free_result($result);
            }
            mysqli_stmt_close($stmt);
        }
        return $status;
    } else {
        $status = null;
        $latest_time = '';
        if (isset($_SESSION['mock_tracking_updates'])) {
            foreach ($_SESSION['mock_tracking_updates'] as $update) {
                if (intval($update['trip_id']) === $trip_id) {
                    if (empty($latest_time) || strcmp($update['created_at'], $latest_time) > 0) {
                        $latest_time = $update['created_at'];
                        $status = $update['status'];
                    }
                }
            }
        }
        return $status;
    }
}
?>
