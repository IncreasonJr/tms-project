<?php
// login.php - Secure Authentication Entry Console
// Conforms to spec.pdf requirements

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // do not sanitize raw passwords before checking
    
    if (empty($email) || empty($password)) {
        $error_msg = 'Please enter both email and password.';
    } else {
        if ($db_connected) {
            // Prepared statements for SQL Injection Protection
            $sql = "SELECT id, fullname, email, password, role FROM admins WHERE email = ?";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    if (password_verify($password, $row['password'])) {
                        // Regenerate Session ID to prevent session fixation attacks
                        session_regenerate_id(true);
                        
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['user_name'] = $row['fullname'];
                        $_SESSION['user_role'] = $row['role'];
                        
                        header("Location: dashboard.php?msg=Welcome+back,+" . urlencode($row['fullname']));
                        exit;
                    }
                }
                mysqli_stmt_close($stmt);
            }
            $error_msg = 'Invalid email or password.';
        } else {
            // Simulation Mode Local check
            if (($email === 'dispatcher@fleet.com' || $email === 'admin@fleet.com' || $email === 'yard@fleet.com') && $password === 'fleet123') {
                session_regenerate_id(true);
                if ($email === 'admin@fleet.com') {
                    $_SESSION['user_id'] = 1;
                    $_SESSION['user_name'] = 'System Administrator';
                    $_SESSION['user_role'] = 'super_admin';
                } else if ($email === 'dispatcher@fleet.com') {
                    $_SESSION['user_id'] = 2;
                    $_SESSION['user_name'] = 'Operations Dispatcher';
                    $_SESSION['user_role'] = 'manager';
                } else {
                    $_SESSION['user_id'] = 3;
                    $_SESSION['user_name'] = 'Yard Officer';
                    $_SESSION['user_role'] = 'staff';
                }
                header("Location: dashboard.php?msg=Welcome+to+simulation+mode!");
                exit;
            } else {
                $error_msg = 'Invalid credentials. Try dispatcher@fleet.com / fleet123';
            }
        }
    }
}
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
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Defaults Hint -->
        <div class="login-hint">
            <strong>Default Dispatcher Credentials:</strong><br>
            Email: <span style="text-decoration: underline;">dispatcher@fleet.com</span><br>
            Password: <span style="text-decoration: underline;">fleet123</span>
        </div>

        <form action="login.php" method="POST" class="login-form">
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
