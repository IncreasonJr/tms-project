<?php
/**
 * Logout Page
 * Transport Management System (TMS)
 */

// 1. Start the session to gain access to current session data
session_start();

// 2. Unset all session variables to clear the $_SESSION array
$_SESSION = array();

// 3. Clear the session cookies (good practice to completely reset the session)
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

// 4. Destroy the session completely on the server
session_destroy();

// 5. Redirect the user to login.php
header("Location: login.php");
exit();
?>
