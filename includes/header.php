<?php
// header.php - Shared HTML Header and Navigation
// Conforms to spec.pdf requirements

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Exclude login.php and index.php from auth redirect (track.php handles its own auth)
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && $current_page !== 'index.php') {
    check_login();
}

$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'System Dispatcher');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'customer';
$role_badge = '';
if ($user_role === 'admin') {
    $role_badge = '<span class="role-badge super-admin">Admin</span>';
} else if ($user_role === 'driver') {
    $role_badge = '<span class="role-badge manager">Driver</span>';
} else {
    $role_badge = '<span class="role-badge staff">Customer</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - FLEET Control" : "FLEET Control - Transport Management System"; ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/Logo1.png">
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
                <div class="brand-icon" style="background: transparent;">
                    <img src="assets/images/Logo1.png" alt="Logo" style="width: 32px; height: 32px; object-fit: contain;">
                </div>
                <div class="brand-text">
                    <h2>FLEET</h2>
                    <span>Control Console</span>
                </div>
                <button id="sidebar-collapse-btn" class="sidebar-collapse-btn" title="Toggle Sidebar Width">
                    <i data-lucide="chevron-left" id="collapse-icon"></i>
                </button>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="sidebar-user">
                <div class="user-avatar">
                    <span class="avatar-initials"><?php echo strtoupper(substr($user_name, 0, 2)); ?></span>
                    <span class="user-status-dot online"></span>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?></h4>
                    <?php echo $role_badge; ?>
                </div>
            </div>
            <?php endif; ?>

            <nav class="sidebar-nav">
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($user_role === 'admin'): ?>
                            <!-- Admin Navigation Links -->
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
                        <?php elseif ($user_role === 'driver'): ?>
                            <!-- Driver Navigation Links -->
                            <li class="<?php echo $current_page === 'driver_dashboard.php' ? 'active' : ''; ?>">
                                <a href="driver_dashboard.php">
                                    <i data-lucide="layout-dashboard"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li class="<?php echo $current_page === 'trips.php' ? 'active' : ''; ?>">
                                <a href="trips.php">
                                    <i data-lucide="navigation"></i>
                                    <span>My Trips</span>
                                </a>
                            </li>
                            <li class="<?php echo $current_page === 'update_tracking.php' ? 'active' : ''; ?>">
                                <a href="update_tracking.php">
                                    <i data-lucide="activity"></i>
                                    <span>Update Status</span>
                                </a>
                            </li>
                        <?php elseif ($user_role === 'customer'): ?>
                            <!-- Customer Navigation Links -->
                            <li class="<?php echo $current_page === 'customer_dashboard.php' ? 'active' : ''; ?>">
                                <a href="customer_dashboard.php">
                                    <i data-lucide="package"></i>
                                    <span>My Orders</span>
                                </a>
                            </li>
                            <li class="<?php echo $current_page === 'track.php' ? 'active' : ''; ?>">
                                <a href="track.php">
                                    <i data-lucide="search"></i>
                                    <span>Track Shipments</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
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
                    <!-- Theme Selector Toggle Widget -->
                    <button id="theme-toggle-btn" class="header-widget theme-toggle-btn" style="cursor: pointer; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.5rem; border-radius: 50px; background-color: rgba(0,0,0,0.2); transition: var(--transition);" title="Toggle Dark/Light Mode">
                        <i data-lucide="moon" id="theme-icon-moon" style="width: 14px; height: 14px; color: var(--accent-blue);"></i>
                        <i data-lucide="sun" id="theme-icon-sun" style="width: 14px; height: 14px; color: #fbbf24; display: none;"></i>
                        <span id="theme-label" style="font-weight: 600; font-size: 0.75rem;">Theme</span>
                    </button>

                    <!-- Regional Time Counter -->
                    <div class="header-widget time-widget">
                        <i data-lucide="globe"></i>
                        <span>Accra, GH: <strong id="accra-time">--:--:--</strong></span>
                    </div>

                    <!-- Connection indicator fallback warning if local database fails -->
                    <?php if (!$db_connected): ?>
                        <div class="header-widget warning-widget" title="Local MySQL server not running. Active database simulation mode enabled.">
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

            <!-- Custom Deletion Modal Dialog Box -->
            <div id="custom-confirm-modal" class="modal-overlay">
                <div class="modal-content glass-container" style="max-width: 400px; padding: 2rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.12); background: rgba(14,21,37,0.75); backdrop-filter: blur(20px); text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
                    <div style="margin-bottom: 1.5rem;">
                        <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); display: inline-flex; align-items: center; justify-content: center; color: #ef4444; margin-bottom: 1rem; margin-left: auto; margin-right: auto;">
                            <i data-lucide="alert-triangle" style="width: 28px; height: 28px;"></i>
                        </div>
                        <h3 style="font-size: 1.2rem; font-weight: 700; color: white; margin-bottom: 0.5rem;" id="confirm-modal-title">Confirm Action</h3>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;" id="confirm-modal-message">Are you sure you want to perform this operation? This action cannot be undone.</p>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button id="confirm-modal-cancel" class="btn btn-secondary" style="flex: 1; justify-content: center; padding: 0.65rem; border-radius: 8px; font-weight: 600;">Cancel</button>
                        <button id="confirm-modal-approve" class="btn btn-danger" style="flex: 1; justify-content: center; padding: 0.65rem; border-radius: 8px; font-weight: 600; background-color: #ef4444;">Delete</button>
                    </div>
                </div>
            </div>
            
            <!-- Content Viewport -->
            <main class="content-viewport">
