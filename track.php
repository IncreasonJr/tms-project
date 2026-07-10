<?php
/**
 * Public Track Delivery Page
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Include header layout (does not enforce redirect for track.php)
require_once 'includes/header.php';

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$error_msg = '';
$tracking_data = null;

if (!empty($code) || isset($_GET['search'])) {
    if (empty($code)) {
        $error_msg = 'Please enter a tracking code.';
    } else {
        // getTrackingByCode returns the array or false on not found
        $tracking_data = getTrackingByCode($code);
        if ($tracking_data === false) {
            $error_msg = 'Delivery not found. Please check your tracking code.';
        }
    }
}
?>

<div class="dashboard-viewport" style="max-width: 900px; margin: 0 auto; padding-top: 1rem;">
    
    <!-- Title Section -->
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <img src="assets/images/logo.png" alt="FLEET Logo" style="height: 60px; margin-bottom: 1rem; object-fit: contain;">
        <h2 style="font-size: 1.75rem; font-weight: 800; color: white;">Global Shipment Tracker</h2>
        <p style="font-size: 0.875rem; color: var(--text-secondary); max-width: 450px; margin: 0.5rem auto 0 auto;">
            Enter your unique dispatch trip code below to monitor route statuses, vehicle allocations, and live timeline coordinates.
        </p>
    </div>

    <!-- Search Box -->
    <div class="tracking-search-box">
        <form action="track.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <input type="hidden" name="search" value="1">
            <div style="flex-grow: 1; min-width: 250px;">
                <input type="text" name="code" class="form-control" placeholder="e.g. TRIP-0001" value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 1.1rem; text-transform: uppercase; font-family: monospace; font-weight: 700; padding: 0.9rem 1.25rem;">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.9rem 2rem; font-size: 1rem;">
                <i data-lucide="search"></i>
                <span>Track Package</span>
            </button>
        </form>
        
        <?php if (!empty($error_msg)): ?>
            <div class="login-error" style="margin-top: 1.25rem; margin-bottom: 0; text-align: left;">
                <i data-lucide="alert-triangle" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                <span><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($tracking_data): ?>
        <?php 
        $details = $tracking_data['trip_details'];
        $history = $tracking_data['tracking_history'];
        $curr_status = $tracking_data['current_status'];
        $color = $tracking_data['status_color'];
        
        $badge_class = 'status-' . strtolower(str_replace(' ', '_', $curr_status ?: 'order_received'));
        ?>

        <div style="display: flex; flex-direction: column; gap: 2rem;">
            
            <!-- Section 1: Delivery Summary Grid -->
            <div class="dashboard-panel">
                <div class="panel-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                    <h3 class="panel-title">
                        <i data-lucide="box" style="vertical-align: middle; margin-right: 6px; color: var(--accent-blue);"></i>
                        <span>Delivery Details</span>
                    </h3>
                    <span class="badge <?php echo $badge_class; ?>" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">
                        <?php echo htmlspecialchars($curr_status ?: 'Order Received', ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 0; gap: 1.5rem;">
                    <div class="form-group">
                        <span class="form-label">Trip Code</span>
                        <strong style="color: white; font-size: 1.1rem; font-family: monospace;"><?php echo htmlspecialchars($details['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Route</span>
                        <div style="color: white; font-weight: 600; font-size: 0.9rem;">
                            <span><?php echo htmlspecialchars(explode(',', $details['origin'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                            <i data-lucide="arrow-right" style="width: 12px; height: 12px; display: inline-block; margin: 0 0.25rem; vertical-align: middle; color: var(--text-muted);"></i>
                            <span><?php echo htmlspecialchars(explode(',', $details['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Assigned Vehicle</span>
                        <strong style="color: white;"><?php echo htmlspecialchars($details['vehicle_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($details['license_plate'] ?: '—', ENT_QUOTES, 'UTF-8'); ?>)</strong>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Driver Operator</span>
                        <strong style="color: white;"><?php echo htmlspecialchars($details['driver_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($details['driver_phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?>)</strong>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Dispatch Date</span>
                        <strong style="color: white;"><?php echo htmlspecialchars(date('d M Y', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Section 2: Estimated Delivery Time Banner -->
            <div class="dashboard-panel" style="background-image: linear-gradient(to right, rgba(59, 130, 246, 0.05), transparent); border-left: 4px solid var(--accent-blue);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h4 style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 0.25rem;">
                            <?php echo ($curr_status === 'Delivered') ? 'Delivery Completion Time' : 'Estimated Time of Arrival (ETA)'; ?>
                        </h4>
                        <div style="font-size: 1.25rem; font-weight: 800; color: white;">
                            <?php 
                            if ($curr_status === 'Delivered' && !empty($history)) {
                                $delivered_time = $history[0]['updated_at'];
                                echo htmlspecialchars(date('H:i A, l, F d, Y', strtotime($delivered_time)), ENT_QUOTES, 'UTF-8');
                            } else {
                                // Mock ETA: Trip date + 1 day
                                $eta = date('H:i A, l, F d, Y', strtotime($details['trip_date'] . ' + 1 day'));
                                echo htmlspecialchars($eta, ENT_QUOTES, 'UTF-8');
                            }
                            ?>
                        </div>
                    </div>
                    <div style="color: var(--accent-blue);">
                        <i data-lucide="<?php echo ($curr_status === 'Delivered') ? 'badge-check' : 'calendar-clock'; ?>" style="width: 32px; height: 32px;"></i>
                    </div>
                </div>
            </div>

            <!-- Section 3: Tracking Timeline -->
            <div class="dashboard-panel">
                <h3 class="panel-title" style="margin-bottom: 2rem;">
                    <i data-lucide="history" style="vertical-align: middle; margin-right: 6px; color: var(--accent-blue);"></i>
                    <span>Transit Tracking Timeline</span>
                </h3>

                <?php if (empty($history)): ?>
                    <div class="timeline-flow">
                        <div class="timeline-step status-order_received">
                            <div class="timeline-dot" title="Order Received">
                                <i data-lucide="clipboard-list"></i>
                            </div>
                            <div class="timeline-info">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                    <h4 style="color: var(--status-color); font-weight: 800; font-size: 1rem;">Order Received</h4>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
                                        <?php echo htmlspecialchars(date('h:i A, M d, Y', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <div class="timeline-meta">
                                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                                        <i data-lucide="map-pin" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                        <strong style="color: white;"><?php echo htmlspecialchars($details['origin'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                                <p class="timeline-desc">Delivery order created and confirmed. No updates logged yet.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="timeline-flow">
                        <?php foreach ($history as $update): ?>
                            <?php 
                            $step_class = 'status-' . strtolower(str_replace(' ', '_', $update['status']));
                            
                            // Map icon names to statuses
                            $timeline_icon = 'circle';
                            if ($update['status'] === 'Order Received') $timeline_icon = 'clipboard-list';
                            elseif ($update['status'] === 'Vehicle Assigned') $timeline_icon = 'truck';
                            elseif ($update['status'] === 'Departing') $timeline_icon = 'log-out';
                            elseif ($update['status'] === 'In Transit') $timeline_icon = 'navigation';
                            elseif ($update['status'] === 'Arrived') $timeline_icon = 'map-pin';
                            elseif ($update['status'] === 'Delivered') $timeline_icon = 'check-circle2';
                            ?>
                            <div class="timeline-step <?php echo $step_class; ?>">
                                <div class="timeline-dot" title="<?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <i data-lucide="<?php echo $timeline_icon; ?>"></i>
                                </div>
                                <div class="timeline-info">
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                        <h4 style="color: var(--status-color); font-weight: 800; font-size: 1rem;"><?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
                                            <?php echo htmlspecialchars(date('h:i A, M d, Y', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                    <div class="timeline-meta">
                                        <div style="display: flex; align-items: center; gap: 0.25rem;">
                                            <i data-lucide="map-pin" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                            <strong style="color: white;"><?php echo htmlspecialchars($update['location'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        </div>
                                    </div>
                                    <p class="timeline-desc"><?php echo htmlspecialchars($update['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    <?php elseif (isset($_GET['search'])): ?>
        <!-- Searched but not found empty placeholder -->
    <?php else: ?>
        <!-- Welcome empty state -->
        <div style="text-align: center; padding: 4rem 2rem; border: 1px dashed var(--border-color); border-radius: var(--radius-lg); background-color: rgba(255,255,255,0.01);">
            <i data-lucide="scan-line" style="width: 48px; height: 48px; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: white; font-weight: 700; margin-bottom: 0.5rem;">Ready to Track</h3>
            <p style="color: var(--text-secondary); font-size: 0.875rem; max-width: 320px; margin: 0 auto;">Input your dispatch trip code above to monitor route logistics updates.</p>
        </div>
    <?php endif; ?>

</div>

<?php 
// Include footer layout
require_once 'includes/footer.php'; 
?>
