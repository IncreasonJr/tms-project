<?php
/**
 * Sign In - FLEET Control Console
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once __DIR__ . '/includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
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
            if ($db_connected) {
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
                                
                                header("Location: dashboard.php?msg=Welcome+back,+" . urlencode($row['fullname']));
                                exit();
                            }
                        }
                        mysqli_free_result($result);
                    }
                    mysqli_stmt_close($stmt);
                }
                $error_msg = 'Invalid email or password.';
            } else {
                // Simulation Mode Local check
                if (($email === 'dispatcher@fleet.com' || $email === 'admin@fleet.com' || $email === 'yard@fleet.com') && $password === 'fleet123') {
                    session_regenerate_id(true);
                    $_SESSION['last_activity'] = time();
                    
                    if ($email === 'admin@fleet.com') {
                        $_SESSION['user_id'] = 1;
                        $_SESSION['username'] = 'System Administrator';
                        $_SESSION['user_name'] = 'System Administrator';
                        $_SESSION['user_role'] = 'super_admin';
                    } else if ($email === 'dispatcher@fleet.com') {
                        $_SESSION['user_id'] = 2;
                        $_SESSION['username'] = 'Operations Dispatcher';
                        $_SESSION['user_name'] = 'Operations Dispatcher';
                        $_SESSION['user_role'] = 'manager';
                    } else {
                        $_SESSION['user_id'] = 3;
                        $_SESSION['username'] = 'Yard Officer';
                        $_SESSION['user_name'] = 'Yard Officer';
                        $_SESSION['user_role'] = 'staff';
                    }
                    header("Location: dashboard.php?msg=Welcome+to+simulation+mode!");
                    exit();
                } elseif ($email === 'admin@tms.com' && $password === 'admin123') {
                    // Support backend-core's default simulation login as well
                    session_regenerate_id(true);
                    $_SESSION['last_activity'] = time();
                    $_SESSION['user_id'] = 1;
                    $_SESSION['username'] = 'Administrator';
                    $_SESSION['user_name'] = 'Administrator';
                    $_SESSION['user_role'] = 'super_admin';
                    header("Location: dashboard.php?msg=Welcome+to+simulation+mode!");
                    exit();
                } else {
                    $error_msg = 'Invalid credentials. Try dispatcher@fleet.com / fleet123';
                }
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
        <div class="login-logo">
            <img src="assets/images/logo.png" alt="FLEET logo">
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
        <div class="login-hint">
            <strong>Default Dispatcher Credentials:</strong><br>
            Email: <span style="text-decoration: underline;">dispatcher@fleet.com</span><br>
            Password: <span style="text-decoration: underline;">fleet123</span>
        </div>

        <form action="login.php" method="POST" class="login-form">
            <!-- Hidden CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label for="email" class="form-label">Work Email</label>
                <input type="email" id="email" name="email" required placeholder="you@fleet.com" class="form-control" value="dispatcher@fleet.com">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" class="form-control" value="fleet123">
            </div>

            <button type="submit" class="btn btn-primary login-btn">
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
