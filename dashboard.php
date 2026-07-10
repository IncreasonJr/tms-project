<?php
/**
 * Dashboard Page
 * Transport Management System (TMS)
 */

// 1. Include the configuration file
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 3. Query the database for statistics
// We initialize variables with 0 and use queries inside checks to ensure safety even if tables don't exist yet
$total_vehicles = 0;
$total_drivers = 0;
$today_trips = 0;
$total_trips = 0;

// Query 1: Total number of vehicles
$query_vehicles = "SELECT COUNT(*) AS total FROM vehicles";
if ($result = mysqli_query($conn, $query_vehicles)) {
    $row = mysqli_fetch_assoc($result);
    $total_vehicles = $row['total'];
    mysqli_free_result($result);
}

// Query 2: Total number of drivers
$query_drivers = "SELECT COUNT(*) AS total FROM drivers";
if ($result = mysqli_query($conn, $query_drivers)) {
    $row = mysqli_fetch_assoc($result);
    $total_drivers = $row['total'];
    mysqli_free_result($result);
}

// Query 3: Total number of trips for today
$query_today = "SELECT COUNT(*) AS total FROM trips WHERE trip_date = CURDATE()";
if ($result = mysqli_query($conn, $query_today)) {
    $row = mysqli_fetch_assoc($result);
    $today_trips = $row['total'];
    mysqli_free_result($result);
}

// Query 4: Total number of trips
$query_trips = "SELECT COUNT(*) AS total FROM trips";
if ($result = mysqli_query($conn, $query_trips)) {
    $row = mysqli_fetch_assoc($result);
    $total_trips = $row['total'];
    mysqli_free_result($result);
}

// Fetch user fullname from session (which was set as $_SESSION['username'])
$fullname = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';

// Fetch recent 5 tracking updates across all trips
$recent_updates = [];
$recent_query = "SELECT tu.*, t.trip_code FROM tracking_updates tu JOIN trips t ON tu.trip_id = t.id ORDER BY tu.updated_at DESC, tu.id DESC LIMIT 5";
if ($r_res = mysqli_query($conn, $recent_query)) {
    while ($row = mysqli_fetch_assoc($r_res)) {
        $recent_updates[] = $row;
    }
    mysqli_free_result($r_res);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Transport Management System</title>
    <!-- Google Fonts for Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Premium Dashboard CSS Styles -->
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #0f172a 50%, #1e1b4b 100%);
            --card-bg: rgba(30, 41, 59, 0.5);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --primary-color: #4f46e5;
            --primary-hover: #6366f1;
            --font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            
            /* Status Card Accent Colors */
            --accent-vehicles: #3b82f6;
            --accent-drivers: #10b981;
            --accent-today: #f59e0b;
            --accent-total: #8b5cf6;
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
            color: var(--text-primary);
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Top Header Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .navbar-brand img {
            height: 36px;
            width: auto;
            onerror: "this.style.display='none';";
        }

        .navbar-brand span {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .btn-logout {
            padding: 8px 16px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.4);
            transform: translateY(-1px);
        }

        /* Main Dashboard Content Layout */
        .dashboard-container {
            max-width: 1200px;
            width: 100%;
            margin: 40px auto;
            padding: 0 24px;
            flex-grow: 1;
            animation: fadeIn 0.6s ease-out;
        }

        /* 4. Welcome Message at the top */
        .welcome-header {
            margin-bottom: 32px;
        }

        .welcome-title {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(to right, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        /* 4. Grid layout for Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        /* Individual Stat Card with unique accent glow */
        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-info {
            display: flex;
            flex-direction: column;
            gap: 6px;
            z-index: 2;
        }

        /* Large stat number and label below */
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Icon wrapper styling */
        .stat-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }

        /* Apply accent colors to specific cards */
        .card-vehicles {
            border-left: 4px solid var(--accent-vehicles);
        }
        .card-vehicles .stat-number { color: var(--accent-vehicles); }
        .card-vehicles .stat-icon-box {
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent-vehicles);
        }

        .card-drivers {
            border-left: 4px solid var(--accent-drivers);
        }
        .card-drivers .stat-number { color: var(--accent-drivers); }
        .card-drivers .stat-icon-box {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-drivers);
        }

        .card-today {
            border-left: 4px solid var(--accent-today);
        }
        .card-today .stat-number { color: var(--accent-today); }
        .card-today .stat-icon-box {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent-today);
        }

        .card-total {
            border-left: 4px solid var(--accent-total);
        }
        .card-total .stat-number { color: var(--accent-total); }
        .card-total .stat-icon-box {
            background: rgba(139, 92, 246, 0.1);
            color: var(--accent-total);
        }

        /* 4. Quick Actions Panel */
        .actions-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 40px;
        }

        .panel-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-title::before {
            content: '';
            width: 4px;
            height: 18px;
            background: var(--primary-color);
            border-radius: 2px;
            display: inline-block;
        }

        .actions-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
        }

        /* Quick Action Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            border: none;
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .btn-action:hover {
            background: linear-gradient(135deg, var(--primary-hover) 0%, #60a5fa 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.35);
        }

        .btn-action:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
        }

        .btn-action svg {
            fill: currentColor;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Breakpoints */
        @media (max-width: 768px) {
            .navbar {
                padding: 16px 20px;
            }
            .dashboard-container {
                margin: 20px auto;
                padding: 0 16px;
            }
            .welcome-title {
                font-size: 1.75rem;
            }
            .actions-grid {
                flex-direction: column;
            }
            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Header -->
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="assets/images/logo.png" alt="TMS Logo" onerror="this.style.display='none';">
            <span>TMS Project</span>
        </div>
        <div class="user-menu">
            <a href="logout.php" class="btn-logout">Sign Out</a>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="dashboard-container">
        
        <!-- Welcome Message Header -->
        <header class="welcome-header">
            <h2 class="welcome-title">Welcome, <?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>!</h2>
            <p class="welcome-subtitle">Here is the snapshot of your fleet operations today.</p>
        </header>

        <!-- Stat Cards Grid -->
        <section class="stats-grid">
            
            <!-- 1. Total Vehicles Card (Truck Icon) -->
            <div class="stat-card card-vehicles">
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($total_vehicles); ?></span>
                    <span class="stat-label">Total Vehicles</span>
                </div>
                <div class="stat-icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M240-160q-33 0-56.5-23.5T160-240q0-33 23.5-56.5T240-320q33 0 56.5 23.5T320-240q0 33-23.5 56.5T240-160Zm360 0q-33 0-56.5-23.5T520-240q0-33 23.5-56.5T600-320q33 0 56.5 23.5T720-240q0 33-23.5 56.5T600-160ZM120-640v320h45l31-62q15-30 46-49t68-19q37 0 68 19.5t45 50.5l4 9h166q14-31 45-50.5t68-19.5q37 0 68 19t45 49l31 62h80v-200L680-640H120Zm0-80h560l160 200v280h-80q0-66-47-113t-113-47q-66 0-113 47t-47 113H360q0-66-47-113t-113-47q-66 0-113 47t-47 113H40v-400l80-80Zm0 80h560-560Z"/>
                    </svg>
                </div>
            </div>

            <!-- 2. Total Drivers Card (User Icon) -->
            <div class="stat-card card-drivers">
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($total_drivers); ?></span>
                    <span class="stat-label">Total Drivers</span>
                </div>
                <div class="stat-icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M480-480q-66 0-113-47t-113-113q0-66 113-113t113-47q66 0 113 47t113 113q0 66-113 113t-113 113ZM160-160v-32q0-34 17.5-62.5T224-296q64-35 135-51.5t121-16.5q50 0 121 16.5t135 51.5q31 15 48.5 43.5T800-192v32H160Zm80-80h480v-16q0-11-5.5-20T700-290q-54-29-109-42.5T480-346q-56 0-111 13.5T260-290q-9 5-14.5 14t-5.5 20v16Zm240-320q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Zm0 400Z"/>
                    </svg>
                </div>
            </div>

            <!-- 3. Today's Trips Card (Calendar Icon) -->
            <div class="stat-card card-today">
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($today_trips); ?></span>
                    <span class="stat-label">Today's Trips</span>
                </div>
                <div class="stat-icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-160 0q-17 0-28.5-11.5T280-440q0-17 11.5-28.5T320-480q17 0 28.5 11.5T360-440q0 17-11.5 28.5T320-400Zm320 0q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400Zm-320 160q-17 0-28.5-11.5T280-280q0-17 11.5-28.5T320-320q17 0 28.5 11.5T360-280q0 17-11.5 28.5T320-240Zm160 0q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm160 0q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z"/>
                    </svg>
                </div>
            </div>

            <!-- 4. Total Trips Card (Road Icon) -->
            <div class="stat-card card-total">
                <div class="stat-info">
                    <span class="stat-number"><?php echo number_format($total_trips); ?></span>
                    <span class="stat-label">Total Trips</span>
                </div>
                <div class="stat-icon-box">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M440-80v-160h80v160h-80Zm0-240v-160h80v160h-80Zm0-240v-160h80v160h-80ZM184-80l144-640h104v-80H314L160-80h24Zm452 0h24L646-800H528v80h104l144 640Z"/>
                    </svg>
                </div>
            </div>

        </section>

        <!-- Recent Tracking Updates Section -->
        <section class="actions-panel" style="margin-bottom: 40px;">
            <h3 class="panel-title">Recent Tracking Updates</h3>
            <?php if (!empty($recent_updates)): ?>
                <div style="overflow-x: auto; margin-top: 20px;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 16px; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary);">Trip Code</th>
                                <th style="padding: 16px; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary);">Status</th>
                                <th style="padding: 16px; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary);">Location</th>
                                <th style="padding: 16px; font-weight: 600; font-size: 0.9rem; color: var(--text-secondary); text-align: right;">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_updates as $update): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='none'">
                                    <td style="padding: 16px;"><strong style="color: #8b5cf6; font-family: monospace; font-size: 0.95rem;"><?php echo htmlspecialchars($update['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td style="padding: 16px;">
                                        <?php
                                        $status = $update['status'];
                                        $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; // fallback purple
                                        if (isset($TRACKING_STATUSES[$status])) {
                                            $badge_color = $TRACKING_STATUSES[$status];
                                            if ($badge_color === 'blue') { $badge_style = "background: rgba(59, 130, 246, 0.15); color: #60a5fa;"; }
                                            elseif ($badge_color === 'purple') { $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; }
                                            elseif ($badge_color === 'orange') { $badge_style = "background: rgba(245, 158, 11, 0.15); color: #fbbf24;"; }
                                            elseif ($badge_color === 'yellow') { $badge_style = "background: rgba(234, 179, 8, 0.15); color: #fef08a;"; }
                                            elseif ($badge_color === 'green') { $badge_style = "background: rgba(16, 185, 129, 0.15); color: #34d399;"; }
                                            elseif ($badge_color === 'darkgreen') { $badge_style = "background: rgba(4, 120, 87, 0.2); color: #059669;"; }
                                        }
                                        ?>
                                        <span style="<?php echo $badge_style; ?> padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; display: inline-block;">
                                            <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 16px; color: var(--text-primary);"><?php echo htmlspecialchars($update['location'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td style="padding: 16px; color: var(--text-secondary); font-size: 0.85rem; text-align: right;"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-top: 15px;">No tracking updates have been recorded yet.</p>
            <?php endif; ?>
        </section>

        <!-- Quick Actions Panel -->
        <section class="actions-panel">
            <h3 class="panel-title">Quick Actions</h3>
            <div class="actions-grid">
                
                <!-- Action: Add Vehicle -->
                <a href="add_vehicle.php" class="btn-action">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
                        <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
                    </svg>
                    Add Vehicle
                </a>

                <!-- Action: Add Driver -->
                <a href="add_driver.php" class="btn-action">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
                        <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
                    </svg>
                    Add Driver
                </a>

                <!-- Action: Add Trip -->
                <a href="add_trip.php" class="btn-action">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20" viewBox="0 -960 960 960" width="20">
                        <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
                    </svg>
                    Add Trip
                </a>

            </div>
        </section>

    </main>

</body>
</html>
