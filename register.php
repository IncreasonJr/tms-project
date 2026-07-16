<?php
/**
 * Customer Registration
 * Transport Management System (TMS)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: dashboard.php");
    } elseif ($_SESSION['user_role'] === 'driver') {
        header("Location: driver_dashboard.php");
    } else {
        header("Location: customer_dashboard.php");
    }
    exit();
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error_msg = 'Security token validation failed. Please try again.';
    } else {
        $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if ($fullname === '' || $username === '' || $email === '' || $password === '') {
            $error_msg = 'Please complete all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error_msg = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_msg = 'Password must be at least 6 characters long.';
        } else {
            if (!$db_connected || !$conn) {
                $mock_account = create_mock_customer_account($fullname, $username, $email, $password);
                if ($mock_account) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $mock_account['id'];
                    $_SESSION['username'] = $mock_account['fullname'];
                    $_SESSION['user_name'] = $mock_account['fullname'];
                    $_SESSION['login_identifier'] = $mock_account['username'];
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['last_activity'] = time();
                    header("Location: customer_dashboard.php?msg=Account+created+successfully+for+" . urlencode($mock_account['fullname']) . "&type=success");
                    exit();
                }
                $error_msg = 'Username or email already exists in simulation mode.';
            } else {
                $sql = "SELECT id FROM admins WHERE email = ? OR username = ? LIMIT 1";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'ss', $email, $username);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    if ($result && mysqli_num_rows($result) > 0) {
                        $error_msg = 'Username or email already exists.';
                    }
                    if ($result) {
                        mysqli_free_result($result);
                    }
                    mysqli_stmt_close($stmt);
                }

                if ($error_msg === '') {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'customer';
                    $insert_sql = "INSERT INTO admins (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)";
                    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
                        mysqli_stmt_bind_param($stmt, 'sssss', $fullname, $username, $email, $password_hash, $role);
                        if (mysqli_stmt_execute($stmt)) {
                            $new_id = mysqli_insert_id($conn);
                            mysqli_stmt_close($stmt);

                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $new_id;
                            $_SESSION['username'] = $fullname;
                            $_SESSION['user_name'] = $fullname;
                            $_SESSION['login_identifier'] = $username;
                            $_SESSION['user_role'] = $role;
                            $_SESSION['last_activity'] = time();

                            header("Location: customer_dashboard.php?msg=Account+created+successfully+for+" . urlencode($fullname) . "&type=success");
                            exit();
                        }
                        $error_msg = 'Failed to create your account.';
                        mysqli_stmt_close($stmt);
                    } else {
                        $error_msg = 'Failed to prepare the account creation request.';
                    }
                }
            }
        }
    }
}

$token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - FLEET Control Console</title>
    <link rel="icon" type="image/png" href="/assets/images/Logo1.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="login-body">
    <div class="login-card">
        <div class="login-logo" style="margin-bottom: 1.5rem; display: flex; justify-content: center;">
            <img src="assets/images/Logo1.png" alt="FLEET Logo" style="height: 60px; object-fit: contain;">
        </div>

        <div class="login-header">
            <h2>FLEET</h2>
            <p>Create your customer account</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="login-error">
                <i data-lucide="alert-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
                <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
                <label for="fullname" class="form-label">Full Name</label>
                <input type="text" id="fullname" name="fullname" required placeholder="Your full name" class="form-control" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" required placeholder="choose a username" class="form-control" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••" class="form-control">
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary login-btn" style="width: 100%; justify-content: center; margin-top: 1rem; border-radius: 8px;">
                <span>Create Account</span>
                <i data-lucide="user-plus"></i>
            </button>
        </form>

        <div style="margin-top: 1rem; text-align: center; font-size: 0.85rem; color: var(--text-secondary);">
            <span>Already have an account?</span>
            <a href="login.php" style="color: #60a5fa; font-weight: 700; text-decoration: none; margin-left: 0.25rem;">Sign in</a>
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