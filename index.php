<?php
/**
 * Welcome & Landing Page - FLEET Logistics Platform
 * Transport Management System (TMS)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in to change CTA button text/links
$is_logged_in = isset($_SESSION['user_id']);
$dashboard_url = 'login.php';
if ($is_logged_in) {
    if ($_SESSION['user_role'] === 'admin') {
        $dashboard_url = 'dashboard.php';
    } elseif ($_SESSION['user_role'] === 'driver') {
        $dashboard_url = 'driver_dashboard.php';
    } else {
        $dashboard_url = 'customer_dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FLEET - Next-Gen Transport & Logistics Management</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <!-- Style Sheet -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background-color: var(--lp-primary);
        }
    </style>
</head>
<body class="dark-theme">

    <div class="landing-hero">
        <!-- Landing Navbar -->
        <nav class="landing-nav anim-fade-in">
            <div class="logo">
                <div class="brand-icon" style="width: 36px; height: 36px; border-radius: 8px;">
                    <i data-lucide="shield-check" style="width: 20px; height: 20px; color: white;"></i>
                </div>
                <h2 style="font-size: 1.15rem; font-weight: 900; letter-spacing: 0.05em; color: white;">FLEET</h2>
            </div>
            
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <div class="header-widget time-widget" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05);">
                    <i data-lucide="globe"></i>
                    <span>Accra Time: <strong id="lp-clock">--:--:--</strong></span>
                </div>
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                        <i data-lucide="layout-dashboard" style="width: 14px; height: 14px;"></i>
                        <span>Console Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                        <i data-lucide="log-in" style="width: 14px; height: 14px;"></i>
                        <span>Sign In</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Main Hero Intro Section -->
        <main class="landing-main">
            <div class="anim-slide-up" style="max-width: 800px; margin-bottom: 2rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.25em; font-weight: 700; color: var(--lp-highlight); display: inline-block; margin-bottom: 1rem; background: rgba(233, 69, 96, 0.1); padding: 4px 12px; border-radius: 50px;">
                    Smart Logistics Management
                </span>
                <h1 style="font-size: clamp(2.5rem, 6vw, 4.25rem); font-weight: 900; color: white; line-height: 1.1; letter-spacing: -0.04em; margin-bottom: 1.5rem;">
                    FLEET Logistics <br><span style="background: linear-gradient(135deg, #60a5fa, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Platform Control</span>
                </h1>
                <p style="font-size: clamp(1rem, 2vw, 1.2rem); color: var(--text-secondary); line-height: 1.6; max-width: 600px; margin: 0 auto 2.5rem auto;">
                    An enterprise solution for real-time shipment dispatch tracking, vehicle maintenance analytics, and active carrier scheduling.
                </p>
                
                <div class="cta-btn-group">
                    <a href="track.php" class="btn btn-highlight" style="padding: 0.85rem 1.75rem; font-size: 0.95rem;">
                        <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                        <span>Track Cargo Shipment</span>
                    </a>
                    
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-secondary" style="padding: 0.85rem 1.75rem; font-size: 0.95rem; background: rgba(255,255,255,0.03); border: 1px solid var(--lp-glass-border);">
                        <?php if ($is_logged_in): ?>
                            <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                            <span>Go to Dashboard</span>
                        <?php else: ?>
                            <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                            <span>Employee Portal</span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Features Info Grid -->
            <div class="landing-grid reveal delay-1">
                <!-- Stat 1 -->
                <div class="glass-container feature-card">
                    <div class="feature-icon-wrapper">
                        <i data-lucide="truck"></i>
                    </div>
                    <h3>Active Fleet Control</h3>
                    <p>Track load capacity, schedules, and carrier maintenance records across the regional transport network.</p>
                </div>
                
                <!-- Stat 2 -->
                <div class="glass-container feature-card">
                    <div class="feature-icon-wrapper" style="background: rgba(96, 165, 250, 0.1); color: #60a5fa; border-color: rgba(96, 165, 250, 0.2);">
                        <i data-lucide="map-pin"></i>
                    </div>
                    <h3>100% Tracking Rate</h3>
                    <p>Drivers post live milestones and location logs, giving customers direct visibility into transit times.</p>
                </div>

                <!-- Stat 3 -->
                <div class="glass-container feature-card">
                    <div class="feature-icon-wrapper" style="background: rgba(52, 211, 153, 0.1); color: #34d399; border-color: rgba(52, 211, 153, 0.2);">
                        <i data-lucide="shield-check"></i>
                    </div>
                    <h3>Secure Operations</h3>
                    <p>Enforced role-based access controls for dispatchers, active drivers, and cargo booking clients.</p>
                </div>
            </div>
        </main>

        <!-- Footer Section -->
        <footer class="landing-footer anim-fade-in">
            <p>&copy; <?php echo date('Y'); ?> FLEET TMS System. All rights reserved. Regional Headquarters, Accra, Ghana.</p>
        </footer>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Lucide Icons
            lucide.createIcons();

            // Scroll reveal animation handler
            const reveals = document.querySelectorAll(".reveal");
            const revealOnScroll = () => {
                for (let i = 0; i < reveals.length; i++) {
                    const windowHeight = window.innerHeight;
                    const elementTop = reveals[i].getBoundingClientRect().top;
                    const elementVisible = 100;
                    if (elementTop < windowHeight - elementVisible) {
                        reveals[i].classList.add("active");
                    }
                }
            };
            window.addEventListener("scroll", revealOnScroll);
            revealOnScroll(); // trigger check on initial render
            
            // Accra Digital Live Clock
            const updateTime = () => {
                const now = new Date();
                const options = { 
                    timeZone: 'Africa/Accra', 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit', 
                    hour12: false 
                };
                const timeStr = now.toLocaleTimeString('en-US', options);
                const clockEl = document.getElementById("lp-clock");
                if (clockEl) {
                    clockEl.textContent = timeStr;
                }
            };
            setInterval(updateTime, 1000);
            updateTime();
        });
    </script>
</body>
</html>
