<?php
// header.php - Shared HTML Header and Navigation
// Conforms to spec.pdf requirements

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Exclude login.php, index.php, and track.php from auth redirect
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && $current_page !== 'index.php' && $current_page !== 'track.php') {
    check_login();
}

$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'System Dispatcher';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'staff';
$role_badge = '';
if ($user_role === 'super_admin') {
    $role_badge = '<span class="role-badge super-admin">Super Admin</span>';
} else if ($user_role === 'manager') {
    $role_badge = '<span class="role-badge manager">Manager</span>';
} else {
    $role_badge = '<span class="role-badge staff">Staff</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FLEET Control - Transport Management System</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="dark-theme">
    
    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar" id="app-sidebar">
            <div class="sidebar-brand">
                <div class="brand-icon">
                    <i data-lucide="shield-check" class="logo-icon"></i>
                </div>
                <div class="brand-text">
                    <h2>FLEET</h2>
                    <span>Control Console</span>
                </div>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="sidebar-user">
                <div class="user-avatar">
                    <span class="avatar-initials"><?php echo strtoupper(substr($user_name, 0, 2)); ?></span>
                    <span class="user-status-dot online"></span>
                </div>
                <div class="user-info">
                    <h4><?php echo $user_name; ?></h4>
                    <?php echo $role_badge; ?>
                </div>
            </div>
            <?php endif; ?>

            <nav class="sidebar-nav">
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <a href="dashboard.php">
                            <i data-lucide="layout-dashboard"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'vehicles.php' ? 'active' : ''; ?>">
                        <a href="vehicles.php">
                            <i data-lucide="truck"></i>
                            <span>Vehicles</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'drivers.php' ? 'active' : ''; ?>">
                        <a href="drivers.php">
                            <i data-lucide="users"></i>
                            <span>Drivers</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'trips.php' || $current_page === 'add_trip.php' || $current_page === 'edit_trip.php' ? 'active' : ''; ?>">
                        <a href="trips.php">
                            <i data-lucide="navigation"></i>
                            <span>Trips & Dispatch</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'update_tracking.php' ? 'active' : ''; ?>">
                        <a href="update_tracking.php">
                            <i data-lucide="activity"></i>
                            <span>Update Tracking</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'maintenance.php' ? 'active' : ''; ?>">
                        <a href="maintenance.php">
                            <i data-lucide="wrench"></i>
                            <span>Maintenance</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                        <a href="reports.php">
                            <i data-lucide="trending-up"></i>
                            <span>Reports & Logs</span>
                        </a>
                    </li>
                    <li class="<?php echo $current_page === 'track.php' ? 'active' : ''; ?>">
                        <a href="track.php">
                            <i data-lucide="search"></i>
                            <span>Track Delivery</span>
                        </a>
                    </li>
                    <li class="nav-divider"></li>
                    <li>
                        <a href="logout.php" class="logout-link">
                            <i data-lucide="log-out"></i>
                            <span>Sign Out</span>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="<?php echo $current_page === 'track.php' ? 'active' : ''; ?>">
                        <a href="track.php">
                            <i data-lucide="search"></i>
                            <span>Track Delivery</span>
                        </a>
                    </li>
                    <li class="nav-divider"></li>
                    <li>
                        <a href="login.php">
                            <i data-lucide="log-in"></i>
                            <span>Sign In</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Window Container -->
        <div class="main-window">
            <!-- Topbar Header -->
            <header class="main-header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebar-toggle" title="Toggle Sidebar">
                        <i data-lucide="menu"></i>
                    </button>
                    <h1 class="header-title">
                        <?php 
                        switch ($current_page) {
                            case 'dashboard.php': echo 'Dashboard Operations'; break;
                            case 'vehicles.php': echo 'Vehicle Fleet Directory'; break;
                            case 'drivers.php': echo 'Active Driver Registry'; break;
                            case 'trips.php': echo 'Trips & Dispatch Controller'; break;
                            case 'add_trip.php': echo 'Schedule New Trip'; break;
                            case 'edit_trip.php': echo 'Modify Dispatch Order'; break;
                            case 'maintenance.php': echo 'Fleet Maintenance Log'; break;
                            case 'reports.php': echo 'System Reports & Usage'; break;
                            case 'track.php': echo 'Public Tracking Portal'; break;
                            case 'update_tracking.php': echo 'Update Shipment Tracking'; break;
                            default: echo 'FLEET Operations';
                        }
                        ?>
                    </h1>
                </div>
                
                <div class="header-right">
                    <!-- Regional Time Counter -->
                    <div class="header-widget time-widget">
                        <i data-lucide="globe"></i>
                        <span>Accra, GH: <strong id="accra-time">--:--:--</strong></span>
                    </div>

                    <!-- Connection indicator fallback warning if local database fails -->
                    <?php if (!$db_connected): ?>
                        <div class="header-widget warning-widget" title="Local MySQL server not running. Active database session simulation enabled.">
                            <i data-lucide="database-backup"></i>
                            <span>Simulation Mode</span>
                        </div>
                    <?php else: ?>
                        <div class="header-widget success-widget" title="MySQL Database connected successfully.">
                            <i data-lucide="database"></i>
                            <span>DB Connected</span>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Content Viewport -->
            <main class="content-viewport">
