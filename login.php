<?php
/**
 * Sign In - FLEET Control Console
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } elseif ($_SESSION['user_role'] === 'driver') {
        header("Location: driver_dashboard.php");
    } elseif ($_SESSION['user_role'] === 'customer') {
        header("Location: customer_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error_msg = '';

// Check if a timeout query parameter is set to show a notice
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error_msg = "Session expired due to inactivity. Please sign in again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF Token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error_msg = "Security token validation failed. Please try again.";
    } else {
        // Sanitize inputs
        $login_identifier = isset($_POST['login_identifier']) ? trim($_POST['login_identifier']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($login_identifier) || empty($password)) {
            $error_msg = 'Please enter both username/email and password.';
        } else {
            if (!$db_connected || !$conn) {
                // Allow fallback mock logins in Simulation Mode
                $user = find_mock_account($login_identifier);
                $mock_password = isset($user['password']) ? $user['password'] : '';
                $mock_password_ok = false;
                if ($user) {
                    // Support both legacy plain-text mock passwords and newer hashed mock passwords.
                    $mock_password_ok = ($password === $mock_password) || password_verify($password, $mock_password);
                }

                if ($user && $mock_password_ok) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['fullname'];
                    $_SESSION['user_name'] = $user['fullname'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['last_activity'] = time();

                    // If driver, store driver_id
                    if ($user['role'] === 'driver') {
                        $_SESSION['driver_id'] = $user['id'];
                    }

                    if ($user['role'] === 'admin') {
                        header("Location: dashboard.php?msg=Welcome+back,+" . urlencode($user['fullname']));
                    } elseif ($user['role'] === 'driver') {
                        header("Location: driver_dashboard.php?msg=Welcome+back,+" . urlencode($user['fullname']));
                    } elseif ($user['role'] === 'customer') {
                        header("Location: customer_dashboard.php?msg=Welcome+back,+" . urlencode($user['fullname']));
                    } else {
                        header("Location: index.php");
                    }
                    exit();
                } else {
                    $error_msg = 'Invalid mock email or password for offline simulation mode.';
                }
            } else {
                // Prepared statements for SQL Injection Protection
                $has_username_column = false;
                $col_check_sql = "SHOW COLUMNS FROM admins LIKE 'username'";
                if ($col_check_res = mysqli_query($conn, $col_check_sql)) {
                    $has_username_column = mysqli_num_rows($col_check_res) > 0;
                    mysqli_free_result($col_check_res);
                }

                if ($has_username_column) {
                    $sql = "SELECT id, fullname, username, email, password, role FROM admins WHERE email = ? OR username = ? LIMIT 1";
                } else {
                    // Backward-compatible query for legacy schemas without `username`.
                    $sql = "SELECT id, fullname, email, password, role FROM admins WHERE email = ? LIMIT 1";
                }

                if ($stmt = mysqli_prepare($conn, $sql)) {
                    if ($has_username_column) {
                        mysqli_stmt_bind_param($stmt, "ss", $login_identifier, $login_identifier);
                    } else {
                        mysqli_stmt_bind_param($stmt, "s", $login_identifier);
                    }

                    if (mysqli_stmt_execute($stmt)) {
                        $result = mysqli_stmt_get_result($stmt);
                        if ($row = mysqli_fetch_assoc($result)) {
                            $stored_password = isset($row['password']) ? $row['password'] : '';
                            $password_ok = ($password === $stored_password) || password_verify($password, $stored_password);
                            if ($password_ok) {
                                // Regenerate Session ID to prevent session fixation attacks
                                session_regenerate_id(true);
                                
                                $_SESSION['user_id'] = $row['id'];
                                $_SESSION['username'] = $row['fullname']; // compatibility with backend-core
                                $_SESSION['user_name'] = $row['fullname']; // compatibility with frontend-trips
                                $_SESSION['login_identifier'] = isset($row['username']) && !empty($row['username']) ? $row['username'] : $row['email'];
                                $_SESSION['user_role'] = $row['role'];
                                $_SESSION['last_activity'] = time(); // Initialize activity timestamp
                                
                                // If driver, fetch dynamic driver_id
                                if ($row['role'] === 'driver') {
                                    // Map email to driver_id
                                    $d_sql = "SELECT id FROM drivers WHERE email = ? LIMIT 1";
                                    if ($d_stmt = mysqli_prepare($conn, $d_sql)) {
                                        mysqli_stmt_bind_param($d_stmt, "s", $row['email']);
                                        if (mysqli_stmt_execute($d_stmt)) {
                                            $d_res = mysqli_stmt_get_result($d_stmt);
                                            if ($d_row = mysqli_fetch_assoc($d_res)) {
                                                $_SESSION['driver_id'] = $d_row['id'];
                                            }
                                        }
                                        mysqli_stmt_close($d_stmt);
                                    }
                                    if (!isset($_SESSION['driver_id'])) {
                                        $_SESSION['driver_id'] = 1; // fallback
                                    }
                                }
                                
                                // Dynamic redirection based on role
                                if ($row['role'] === 'admin') {
                                    header("Location: dashboard.php?msg=Welcome+back,+" . urlencode($row['fullname']));
                                } elseif ($row['role'] === 'driver') {
                                    header("Location: driver_dashboard.php?msg=Welcome+back,+" . urlencode($row['fullname']));
                                } elseif ($row['role'] === 'customer') {
                                    header("Location: customer_dashboard.php?msg=Welcome+back,+" . urlencode($row['fullname']));
                                } else {
                                    header("Location: index.php");
                                }
                                exit();
                            }
                        }
                        mysqli_free_result($result);
                    }
                    mysqli_stmt_close($stmt);
                }
                $error_msg = 'Invalid username/email or password.';
            }
        }
    }
}

// Generate new CSRF token for the form
$token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - FLEET Control Console</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/Logo1.png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="login-body">
    
    <div class="login-card">
        <div class="login-logo" style="margin-bottom: 1.5rem; display: flex; justify-content: center;">
            <img src="assets/images/Logo1.png" alt="FLEET Logo" style="height: 60px; object-fit: contain;">
        </div>

        <div class="login-header">
            <h2>FLEET</h2>
            <p>Transport Dispatch Control Tower</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="login-error">
                <i data-lucide="alert-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- Defaults Hint -->
        <div class="login-hint" style="background-color: rgba(59, 130, 246, 0.05); border: 1px solid rgba(255,255,255,0.08); text-align: left; padding: 0.85rem; border-radius: 8px;">
            <strong style="color: white; display: block; margin-bottom: 0.25rem; font-size: 0.8rem;">System Logins:</strong>
            <span style="font-size: 0.75rem; color: var(--text-secondary);">
                • Admin: <code style="color: #60a5fa; font-weight: 600;">admin@fleet.com</code><br>
                • Driver: <code style="color: #34d399; font-weight: 600;">driver@fleet.com</code><br>
                • Customer: <code style="color: #a78bfa; font-weight: 600;">customer@fleet.com</code><br>
                • Passcode: <code style="color: white;">fleet123</code>
            </span>
        </div>

        <form action="login.php" method="POST" class="login-form">
            <!-- Hidden CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label for="login_identifier" class="form-label">Username or Email</label>
                <input type="text" id="login_identifier" name="login_identifier" required placeholder="your username or email" class="form-control" value="admin@fleet.com">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" class="form-control" value="fleet123">
            </div>

            <button type="submit" class="btn btn-primary login-btn" style="width: 100%; justify-content: center; margin-top: 1rem; border-radius: 8px;">
                <span>Access Console</span>
                <i data-lucide="arrow-right"></i>
            </button>
        </form>

        <div style="margin-top: 1rem; text-align: center; font-size: 0.85rem; color: var(--text-secondary);">
            <span>New customer?</span>
            <a href="register.php" style="color: #60a5fa; font-weight: 700; text-decoration: none; margin-left: 0.25rem;">Create an account</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
