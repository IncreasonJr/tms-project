<?php
/**
 * Public Track Delivery Page
 * Transport Management System (TMS)
 */

// 1. Include config file (starts session and provides DB connection)
require_once 'includes/config.php';

// 2. Retrieve code from GET parameters
$code = isset($_GET['code']) ? trim($_GET['code']) : '';

// 3. Process tracking details
$tracking_data = null;
$error = '';

if (!empty($code)) {
    // getTrackingByCode returns the array or false on not found
    $tracking_data = getTrackingByCode($code);
    if ($tracking_data === false) {
        $error = "Tracking code " . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . " not found. Please double-check your code and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Delivery - TMS</title>
    <!-- Google Fonts for Modern Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
            
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --gray-color: #64748b;
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
        }

        .header-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-logo h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(to right, #a78bfa, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .header-logo p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Glassmorphism Card styling */
        .glass-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            backdrop-filter: blur(12px);
            padding: 30px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            font-size: 1rem;
            color: var(--text-primary);
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-family: var(--font-family);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-hover);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: var(--font-family);
        }

        .btn-primary {
            background: var(--primary-color);
            color: #ffffff;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* Result Panel Styling */
        .route-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .route-point h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .route-point p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .route-arrow {
            color: #818cf8;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 16px;
        }

        .info-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 6px;
            font-weight: 700;
        }

        .info-value {
            font-size: 0.95rem;
            font-weight: 600;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            font-weight: 700;
            font-size: 0.85rem;
            border-radius: 8px;
            text-transform: uppercase;
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
            border-left: 2px solid rgba(255, 255, 255, 0.08);
            margin-top: 30px;
            margin-bottom: 10px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-dot {
            position: absolute;
            left: -37px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid #0f172a;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
            transition: all 0.3s ease;
        }

        .timeline-item.active .timeline-dot {
            background: var(--success-color);
            box-shadow: 0 0 10px var(--success-color);
            border-color: #0f172a;
        }

        .timeline-time {
            font-size: 0.82rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
            font-weight: 500;
        }

        .timeline-status {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .timeline-location {
            color: #818cf8;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }

        .timeline-desc {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }

        .footer-text {
            text-align: center;
            margin-top: 40px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- Header Logo -->
        <div class="header-logo">
            <h1>TRANSPORT TRACKING</h1>
            <p>Real-time updates for your cargo & deliveries</p>
        </div>

        <?php if ($tracking_data === null || !empty($error)): ?>
            
            <!-- SEARCH CARD -->
            <div class="glass-card">
                <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 20px; text-align: center;">Enter Tracking Code</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="track.php" method="GET">
                    <div class="form-group">
                        <label for="code" class="form-label">Delivery Code / Trip Code</label>
                        <input 
                            type="text" 
                            id="code" 
                            name="code" 
                            class="form-control" 
                            placeholder="e.g. TRIP-0001" 
                            required 
                            style="text-transform: uppercase; font-family: monospace;"
                            value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">
                        🔍 Track Order
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            
            <!-- RESULTS VIEW -->
            <?php
            $details = $tracking_data['trip_details'];
            $history = $tracking_data['tracking_history'];
            $curr_status = $tracking_data['current_status'];
            $color = $tracking_data['status_color'];
            
            // Badge color mapping
            $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; // default purple
            if ($color === 'blue') { $badge_style = "background: rgba(59, 130, 246, 0.15); color: #60a5fa;"; }
            elseif ($color === 'purple') { $badge_style = "background: rgba(139, 92, 246, 0.15); color: #c084fc;"; }
            elseif ($color === 'orange') { $badge_style = "background: rgba(245, 158, 11, 0.15); color: #fbbf24;"; }
            elseif ($color === 'yellow') { $badge_style = "background: rgba(234, 179, 8, 0.15); color: #fef08a;"; }
            elseif ($color === 'green') { $badge_style = "background: rgba(16, 185, 129, 0.15); color: #34d399;"; }
            elseif ($color === 'darkgreen') { $badge_style = "background: rgba(4, 120, 87, 0.2); color: #059669;"; }
            ?>
            
            <!-- Glassmorphic Details Card -->
            <div class="glass-card">
                
                <!-- Route Header -->
                <div class="route-info">
                    <div class="route-point">
                        <h3><?php echo htmlspecialchars($details['origin'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p>Origin Location</p>
                    </div>
                    <div class="route-arrow">
                        ➔
                    </div>
                    <div class="route-point" style="text-align: right;">
                        <h3><?php echo htmlspecialchars($details['destination'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p>Destination Point</p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-title">Trip Code</div>
                        <div class="info-value" style="font-family: monospace; font-size: 1.1rem; color: #818cf8;"><?php echo htmlspecialchars($details['trip_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-title">Current Status</div>
                        <div class="info-value" style="margin-top: 4px;">
                            <span class="badge" style="<?php echo $badge_style; ?>">
                                <?php echo htmlspecialchars($curr_status ?: 'Order Received', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-title">Shipment Date</div>
                        <div class="info-value"><?php echo htmlspecialchars(date('M d, Y', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <!-- Driver & Vehicle Details -->
                <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 30px;">
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px;">Assigned Delivery Details</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-secondary); display: block; text-transform: uppercase;">Vehicle Name / Plate</span>
                            <span style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($details['vehicle_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($details['license_plate'] ?: '—', ENT_QUOTES, 'UTF-8'); ?>)</span>
                        </div>
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-secondary); display: block; text-transform: uppercase;">Driver Name / Contact</span>
                            <span style="font-weight: 600; font-size: 0.95rem;"><?php echo htmlspecialchars($details['driver_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($details['driver_phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?>)</span>
                        </div>
                    </div>
                </div>

                <!-- Chronological timeline of updates -->
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-top: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Shipment Journey Logs</h3>
                
                <div class="timeline">
                    <?php if (!empty($history)): ?>
                        <?php foreach ($history as $index => $update): ?>
                            <div class="timeline-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-time">
                                    <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="timeline-status">
                                    <?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php if ($update['location']): ?>
                                    <div class="timeline-location">
                                        📍 <?php echo htmlspecialchars($update['location'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($update['description']): ?>
                                    <div class="timeline-desc">
                                        <?php echo htmlspecialchars($update['description'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="timeline-item active">
                            <div class="timeline-dot"></div>
                            <div class="timeline-time">
                                <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="timeline-status">Order Received</div>
                            <div class="timeline-desc">Delivery order created and confirmed. No updates logged yet.</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Back button to search -->
                <div style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <a href="track.php" class="btn btn-secondary" style="width: 100%; text-align: center;">
                        ⬅ Track Another Shipment
                    </a>
                </div>

            </div>

        <?php endif; ?>

        <!-- Footer -->
        <div class="footer-text">
            © <?php echo date('Y'); ?> Transport Management System (TMS). All rights reserved.
        </div>

    </div>

</body>
</html>
