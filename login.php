<?php
/**
 * Sign In - FLEET Control Console
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once __DIR__ . '/includes/config.php';

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
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($password)) {
            $error_msg = 'Please enter both email and password.';
        } else {
            if (!$db_connected) {
                // Allow fallback mock logins in Simulation Mode
                $mock_users = [
                    'admin@fleet.com' => [
                        'id' => 999,
                        'fullname' => 'System Admin',
                        'role' => 'admin',
                        'password' => 'admin123'
                    ],
                    'dispatcher@fleet.com' => [
                        'id' => 998,
                        'fullname' => 'System Dispatcher',
                        'role' => 'admin',
                        'password' => 'admin123'
                    ],
                    'yard@fleet.com' => [
                        'id' => 1, // Kwame Mensah driver id
                        'fullname' => 'Kwame Mensah',
                        'role' => 'driver',
                        'password' => 'driver123'
                    ],
                    'customer@fleet.com' => [
                        'id' => 1, // customer id
                        'fullname' => 'Customer User',
                        'role' => 'customer',
                        'password' => 'customer123'
                    ]
                ];

                if (isset($mock_users[$email]) && $password === $mock_users[$email]['password']) {
                    $user = $mock_users[$email];
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
                $sql = "SELECT id, fullname, email, password, role FROM admins WHERE email = ? LIMIT 1";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    if (mysqli_stmt_execute($stmt)) {
                        $result = mysqli_stmt_get_result($stmt);
                        if ($row = mysqli_fetch_assoc($result)) {
                            if (password_verify($password, $row['password'])) {
                                // Regenerate Session ID to prevent session fixation attacks
                                session_regenerate_id(true);
                                
                                $_SESSION['user_id'] = $row['id'];
                                $_SESSION['username'] = $row['fullname']; // compatibility with backend-core
                                $_SESSION['user_name'] = $row['fullname']; // compatibility with frontend-trips
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
                $error_msg = 'Invalid email or password.';
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
                <label for="email" class="form-label">Work Email</label>
                <input type="email" id="email" name="email" required placeholder="you@fleet.com" class="form-control" value="admin@fleet.com">
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
