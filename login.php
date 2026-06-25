<?php
/**
 * Login Page
 * Transport Management System (TMS)
 */

// 1. Include the configuration file
require_once 'includes/config.php';

// 2. Check if the user is already logged in (if yes, redirect to dashboard.php)
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Initialize error variable
$error = '';

// Check if a timeout query parameter is set to show a notice
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Session expired due to inactivity. Please sign in again.";
}

// 4. Process the form when submitted
if (isset($_POST['login'])) {
    
    // Validate CSRF Token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Validate that email and password are not empty
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            // Query the admins table to find a user with the entered email
            // We use prepared statements to prevent SQL Injection attacks
            $query = "SELECT id, fullname, password FROM admins WHERE email = ? LIMIT 1";
            
            if ($stmt = mysqli_prepare($conn, $query)) {
                // Bind parameters
                mysqli_stmt_bind_param($stmt, "s", $email);
                
                // Execute the query
                if (mysqli_stmt_execute($stmt)) {
                    // Get query result
                    $result = mysqli_stmt_get_result($stmt);
                    
                    // If user found
                    if ($row = mysqli_fetch_assoc($result)) {
                        // Verify the password using password_verify()
                        if (password_verify($password, $row['password'])) {
                            
                            // High Severity Fix: Session ID Regeneration after login
                            session_regenerate_id(true);
                            
                            // Store user info
                            $_SESSION['user_id'] = $row['id'];
                            $_SESSION['username'] = $row['fullname'];
                            $_SESSION['last_activity'] = time(); // Initialize activity timestamp
                            
                            // Redirect to dashboard.php on successful login
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            // Authentication failed (wrong password)
                            $error = "Invalid email or password";
                        }
                    } else {
                        // Authentication failed (user not found)
                        $error = "Invalid email or password";
                    }
                    mysqli_free_result($result);
                } else {
                    $error = "Something went wrong. Please try again.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            } else {
                $error = "Database error. Please try again later.";
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
    <title>Login - Transport Management System</title>
    <!-- Google Fonts for Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Premium CSS Styles -->
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --primary-color: #4f46e5;
            --primary-hover: #6366f1;
            --error-bg: rgba(239, 68, 68, 0.15);
            --error-text: #fca5a5;
            --error-border: rgba(239, 68, 68, 0.4);
            --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            padding: 20px;
            overflow-x: hidden;
        }

        /* Ambient glowing backgrounds for depth */
        .ambient-glow {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%);
            top: 10%;
            left: 10%;
            z-index: 1;
            pointer-events: none;
        }
        .ambient-glow-2 {
            position: absolute;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0) 70%);
            bottom: 10%;
            right: 10%;
            z-index: 1;
            pointer-events: none;
        }

        /* Centered Card Layout with Glassmorphism */
        .login-container {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
            z-index: 10;
            animation: fadeIn 0.6s ease-out;
        }

        /* Logo styling */
        .logo-wrapper {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-wrapper img {
            max-height: 60px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
            animation: float 4s ease-in-out infinite;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 24px;
        }

        /* Form groups and labels */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Modern styled inputs */
        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-primary);
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-hover);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        /* Error message styling (in red) */
        .error-alert {
            background-color: var(--error-bg);
            color: var(--error-text);
            border: 1px solid var(--error-border);
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: shake 0.4s ease;
        }

        .error-alert svg {
            margin-right: 10px;
            flex-shrink: 0;
            fill: currentColor;
        }

        /* Button design */
        .btn-submit {
            width: 100%;
            padding: 14px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, var(--primary-hover) 0%, #60a5fa 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }
    </style>
</head>
<body>
    <!-- Background glows -->
    <div class="ambient-glow"></div>
    <div class="ambient-glow-2"></div>

    <!-- Login card wrapper -->
    <div class="login-container">
        
        <!-- Logo Header -->
        <div class="logo-wrapper">
            <img src="assets/images/logo.png" alt="TMS Logo" onerror="this.style.display='none';">
            <h1 class="login-title">Transport Management</h1>
            <p class="login-subtitle">Sign in to manage fleet and operations</p>
        </div>

        <!-- Error feedback (uses ENT_QUOTES and UTF-8 explicitly) -->
        <?php if (!empty($error)): ?>
            <div class="error-alert" id="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
                    <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                </svg>
                <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <!-- HTML Login Form -->
        <form action="login.php" method="POST" autocomplete="off">
            
            <!-- Hidden CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Email Field -->
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="name@example.com" 
                    required 
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <!-- Submit Button -->
            <button type="submit" name="login" class="btn-submit">
                Sign In
            </button>
            
        </form>
    </div>
</body>
</html>
