<?php
/**
 * Logout Page
 * Transport Management System (TMS)
 */

// 1. Include the configuration file
require_once __DIR__ . '/includes/config.php';

// 2. Clear authentication session variables
$auth_keys = ['user_id', 'username', 'user_name', 'user_role', 'login_identifier', 'driver_id', 'last_activity'];
foreach ($auth_keys as $key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

// 3. Wiping session cookies and destroying session completely ONLY if database is connected
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

// 4. Redirect the user to login.php
header("Location: login.php?msg=You+have+been+signed+out.&type=info");
exit();
?>
