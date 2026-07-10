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

// 2. Session Inactivity Timeout (30 minutes)
$timeout_duration = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
    // Session expired: unset, delete cookies, and destroy
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
    header("Location: login.php?timeout=1");
    exit();
}
// Update last activity timestamp
$_SESSION['last_activity'] = time();

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

define('DB_SERVER', $db_server);
define('DB_USERNAME', $db_username);
define('DB_PASSWORD', $db_password);
define('DB_NAME', $db_name);

// 4. Create a MySQL Database Connection using mysqli_connect
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 5. Check Connection (Generic message to user, detailed error logged)
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database Connection failed. Please try again later.");
}

// 6. Define BASE_URL constant for the project
define('BASE_URL', 'http://localhost/tms-project/');

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
    global $conn;
    $query = "INSERT INTO tracking_updates (trip_id, status, location, description) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "isss", $trip_id, $status, $location, $description);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    return false;
}

/**
 * Fetch all tracking updates for a specific trip (newest first)
 */
function getTrackingHistory($trip_id) {
    global $conn;
    $history = [];
    $query = "SELECT * FROM tracking_updates WHERE trip_id = ? ORDER BY updated_at DESC, id DESC";
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
}

/**
 * Fetch trip details and all tracking updates for a trip using its trip code
 */
function getTrackingByCode($trip_code) {
    global $conn;
    global $TRACKING_STATUSES;
    
    $query = "SELECT t.id, t.trip_code, t.origin, t.destination, t.trip_date, 
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
                $latest_status = getLatestTrackingStatus($trip_id);
                $color = isset($TRACKING_STATUSES[$latest_status]) ? $TRACKING_STATUSES[$latest_status] : 'gray';
                
                return [
                    'trip_details' => [
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
}

/**
 * Fetch trip details by ID
 */
function getTripById($trip_id) {
    global $conn;
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
}

/**
 * Fetch all trips with trip_code, origin, destination formatted for dropdowns
 */
function getAllTripsForDropdown() {
    global $conn;
    $trips = [];
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
    return $trips;
}

/**
 * Fetch the most recent tracking status for a trip
 */
function getLatestTrackingStatus($trip_id) {
    global $conn;
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
}
?>
