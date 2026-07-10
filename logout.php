<?php
// logout.php - Session Termination Handler
// Conforms to spec.pdf requirements

require_once __DIR__ . '/includes/config.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

header("Location: login.php?msg=You+have+been+signed+out.&type=info");
exit;
?>
