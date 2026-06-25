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
?>
