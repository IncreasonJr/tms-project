<?php
// index.php - Welcome Redirector
// Conforms to spec.pdf requirements

require_once __DIR__ . '/includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
?>
