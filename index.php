<?php
/**
 * Welcome & Landing Page - FLEET Logistics Platform
 * Transport Management System (TMS)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in to change CTA button
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
    <title>FLEET - Transport & Logistics Management System</title>
    <meta name="description" content="FLEET is Ghana's premier enterprise transport management system. Track shipments, manage drivers, dispatch vehicles, and monitor fleet operations in real-time.">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/Logo1.png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <!-- Style Sheet -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background-color: var(--bg-primary); }

        /* Landing page card hover lift effect */
        .feature-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                        box-shadow 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                        border-color 0.35s ease;
            animation: cardFloat linear infinite;
        }
        .feature-card:nth-child(1) { animation-duration: 5s; animation-delay: 0s; }
        .feature-card:nth-child(2) { animation-duration: 6s; animation-delay: 1s; }
        .feature-card:nth-child(3) { animation-duration: 5.5s; animation-delay: 0.5s; }

        @keyframes cardFloat {
            0%   { transform: translateY(0px); }
            50%  { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }

        .feature-card:hover {
            animation-play-state: paused;
            transform: translateY(-12px) scale(1.02);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }

        body.light-theme .feature-card:hover {
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
        }

        .landing-stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border-radius: 50px;
            padding: 0.35rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1.5rem;
        }

        body.light-theme .landing-stat-badge {
            background: rgba(37, 99, 235, 0.08);
            color: #1d4ed8;
            border-color: rgba(37, 99, 235, 0.2);
        }

        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4.25rem);
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1.1;
            letter-spacing: -0.04em;
            margin-bottom: 1.5rem;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.15rem);
            color: var(--text-secondary);
            line-height: 1.7;
            max-width: 580px;
            margin: 0 auto 2.5rem auto;
        }

        .stat-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
            margin-top: 3rem;
            padding-top: 3rem;
            border-top: 1px solid var(--border-color);
        }

        .stat-row-item {
            text-align: center;
        }

        .stat-row-item strong {
            display: block;
            font-size: 2rem;
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1;
        }

        .stat-row-item span {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .feature-card h3 { color: var(--text-primary); }
        .feature-card p { color: var(--text-secondary); }

        .landing-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
    </style>
</head>
<body class="dark-theme">

    <div class="landing-hero">
        <!-- Landing Navbar -->
        <nav class="landing-nav anim-fade-in">
            <div class="logo">
                <img src="assets/images/Logo1.png" alt="FLEET Logo" style="height: 36px; object-fit: contain;">
                <h2 style="font-size: 1.15rem; font-weight: 900; letter-spacing: 0.05em; color: var(--text-primary);">FLEET</h2>
            </div>
            
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="header-widget time-widget" style="background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.05);">
                    <i data-lucide="globe"></i>
                    <span>Accra: <strong id="lp-clock">--:--:--</strong></span>
                </div>
                <?php if ($is_logged_in): ?>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary" style="padding: 0.55rem 1.1rem; font-size: 0.82rem; border-radius: 8px;">
                        <i data-lucide="layout-dashboard" style="width: 14px; height: 14px;"></i>
                        <span>Dashboard</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary" style="padding: 0.55rem 1.25rem; font-size: 0.82rem; border-radius: 8px;">
                        <i data-lucide="log-in" style="width: 14px; height: 14px;"></i>
                        <span>Sign In</span>
                    </a>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Main Hero Section -->
        <main class="landing-main">
            <div class="anim-slide-up" style="max-width: 820px; margin-bottom: 1rem; width: 100%;">

                <div class="landing-stat-badge">
                    <i data-lucide="zap" style="width: 13px; height: 13px;"></i>
                    <span>Ghana's Smart Logistics Platform</span>
                </div>

                <h1 class="hero-title">
                    FLEET Dispatch<br>
                    <span style="background: linear-gradient(135deg, #60a5fa, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        Control Tower
                    </span>
                </h1>
                <p class="hero-subtitle">
                    An enterprise solution for real-time shipment dispatch tracking, driver & vehicle management, and intelligent scheduling — built for Ghana's transport sector.
                </p>
                
                <!-- Primary CTA Button to Login -->
                <div class="cta-btn-group" style="margin-top: 0;">
                    <a href="login.php" class="btn btn-primary" style="padding: 1rem 2.25rem; font-size: 1rem; border-radius: 12px; font-weight: 700; box-shadow: 0 8px 25px rgba(59, 130, 246, 0.35);">
                        <i data-lucide="log-in" style="width: 20px; height: 20px;"></i>
                        <span><?php echo $is_logged_in ? 'Go to Dashboard' : 'Access Console'; ?></span>
                    </a>
                </div>

                <!-- Animated Stats Row -->
                <div class="stat-row reveal delay-1">
                    <div class="stat-row-item">
                        <strong class="lp-count" data-target="6" data-suffix="+">6+</strong>
                        <span>Fleet Vehicles</span>
                    </div>
                    <div class="stat-row-item">
                        <strong class="lp-count" data-target="6" data-suffix="+">6+</strong>
                        <span>Registered Drivers</span>
                    </div>
                    <div class="stat-row-item">
                        <strong style="background: linear-gradient(135deg, #60a5fa, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">100%</strong>
                        <span>Live Tracking</span>
                    </div>
                    <div class="stat-row-item">
                        <strong class="lp-count" data-target="3" data-suffix="">3</strong>
                        <span>Active Routes</span>
                    </div>
                </div>
            </div>

            <!-- Features Grid (Animated Floating Cards) -->
            <div class="landing-grid reveal delay-2" style="margin-top: 3rem;">
                <!-- Feature Card 1 -->
                <div class="glass-container feature-card">
                    <div class="feature-icon-wrapper" style="background: rgba(59, 130, 246, 0.1); color: #60a5fa; border-color: rgba(59, 130, 246, 0.2);">
                        <i data-lucide="truck"></i>
                    </div>
                    <h3>Active Fleet Control</h3>
                    <p>Manage load capacity, schedules, and vehicle status across the regional transport network in real-time.</p>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.75rem; color: #60a5fa; font-weight: 600;">
                        <i data-lucide="arrow-right" style="width: 12px; height: 12px;"></i>
                        <span>Manage Vehicles</span>
                    </div>
                </div>
                
                <!-- Feature Card 2 -->
                <div class="glass-container feature-card">
                    <div class="feature-icon-wrapper" style="background: rgba(52, 211, 153, 0.1); color: #34d399; border-color: rgba(52, 211, 153, 0.2);">
                        <i data-lucide="map-pin"></i>
                    </div>
                    <h3>Live Shipment Tracking</h3>
                    <p>Drivers log live location milestones, giving stakeholders direct visibility into transit timelines and delivery status.</p>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.75rem; color: #34d399; font-weight: 600;">
                        <i data-lucide="arrow-right" style="width: 12px; height: 12px;"></i>
                        <span>Track Shipments</span>
                    </div>
                </div>

                <!-- Feature Card 3 -->
                <div class="glass-container feature-card">
                    <div class="feature-icon-wrapper" style="background: rgba(167, 139, 250, 0.1); color: #a78bfa; border-color: rgba(167, 139, 250, 0.2);">
                        <i data-lucide="shield-check"></i>
                    </div>
                    <h3>Secure Role-Based Access</h3>
                    <p>Enforced role-based access controls for Admins, active Drivers, and cargo booking Customers with audit trails.</p>
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.75rem; color: #a78bfa; font-weight: 600;">
                        <i data-lucide="arrow-right" style="width: 12px; height: 12px;"></i>
                        <span>3 Role Types</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="landing-footer anim-fade-in">
            <p>&copy; <?php echo date('Y'); ?> FLEET Transport Management System. All rights reserved. &nbsp;|&nbsp; Regional HQ: Accra, Ghana</p>
        </footer>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();

            // Apply dark theme
            document.body.classList.add('dark-theme');

            // Scroll reveal
            const reveals = document.querySelectorAll(".reveal");
            const revealOnScroll = () => {
                for (let el of reveals) {
                    const top = el.getBoundingClientRect().top;
                    if (top < window.innerHeight - 80) {
                        el.classList.add("active");
                    }
                }
            };
            window.addEventListener("scroll", revealOnScroll);
            revealOnScroll();

            // Accra Live Clock
            const updateTime = () => {
                const timeStr = new Date().toLocaleTimeString('en-US', {
                    timeZone: 'Africa/Accra',
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
                });
                const clockEl = document.getElementById("lp-clock");
                if (clockEl) clockEl.textContent = timeStr;
            };
            setInterval(updateTime, 1000);
            updateTime();

            // Count-up animation for landing stats
            const animateCounter = (el) => {
                const target = parseInt(el.getAttribute('data-target'), 10);
                const suffix = el.getAttribute('data-suffix') || '';
                if (isNaN(target)) return;
                let start = 0;
                const duration = 1200;
                const step = Math.max(1, Math.ceil(target / (duration / 16)));
                const timer = setInterval(() => {
                    start += step;
                    if (start >= target) {
                        start = target;
                        clearInterval(timer);
                    }
                    el.textContent = start + suffix;
                }, 16);
            };

            // Trigger count-up when stat row becomes visible
            const statRow = document.querySelector('.stat-row');
            if (statRow) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            document.querySelectorAll('.lp-count').forEach(animateCounter);
                            observer.disconnect();
                        }
                    });
                }, { threshold: 0.3 });
                observer.observe(statRow);
            } else {
                // Trigger immediately on small pages
                setTimeout(() => document.querySelectorAll('.lp-count').forEach(animateCounter), 400);
            }
        });
    </script>
</body>
</html>
