<?php
/**
 * Private Track Delivery Page
 * Transport Management System (TMS)
 */

// 1. Include config file
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 2. Enforce authentication — tracking is now private
check_login();

// 3. Fix header.php auth bypass: manually call after check
require_once 'includes/header.php';

$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$error_msg = '';
$tracking_data = null;

if (!empty($code) || isset($_GET['search'])) {
    if (empty($code)) {
        $error_msg = 'Please enter a tracking code.';
    } else {
        $tracking_data = getTrackingByCode($code);
        if ($tracking_data === false) {
            $error_msg = 'Delivery not found. Please check your tracking code.';
        }
    }
}

// Helper: Map statuses to journey stages (ordered)
$journey_stages = [
    ['key' => 'Order Received',   'icon' => 'clipboard-list', 'label' => 'Order Received'],
    ['key' => 'Vehicle Assigned', 'icon' => 'truck',          'label' => 'Assigned'],
    ['key' => 'Departing',        'icon' => 'log-out',        'label' => 'Departing'],
    ['key' => 'In Transit',       'icon' => 'navigation',     'label' => 'In Transit'],
    ['key' => 'Arrived',          'icon' => 'map-pin',        'label' => 'Arrived'],
    ['key' => 'Delivered',        'icon' => 'badge-check',    'label' => 'Delivered'],
];

function getStageIndex($status, $stages) {
    foreach ($stages as $i => $stage) {
        if (strtolower($stage['key']) === strtolower($status)) return $i;
    }
    return -1;
}
?>

<div class="dashboard-viewport" style="max-width: 960px; margin: 0 auto; padding-top: 1rem;">
    
    <!-- Title Section -->
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <img src="assets/images/Logo1.png" alt="FLEET Logo" style="height: 52px; margin-bottom: 1rem; object-fit: contain;">
        <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px;">
            Live Shipment Tracker
        </h2>
        <p style="font-size: 0.875rem; color: var(--text-secondary); max-width: 450px; margin: 0.5rem auto 0 auto; line-height: 1.6;">
            Enter your dispatch trip code to monitor route statuses, vehicle allocations, and live journey coordinates.
        </p>
    </div>

    <!-- Search Box -->
    <div class="glass-container" style="padding: 1.5rem; margin-bottom: 2rem; border-radius: 16px;">
        <form action="track.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="search" value="1">
            <div style="flex-grow: 1; min-width: 250px;">
                <label style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 0.5rem;">Trip Code</label>
                <input type="text" name="code" class="form-control" placeholder="e.g. TRP-987214"
                    value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                    style="font-size: 1.1rem; text-transform: uppercase; font-family: monospace; font-weight: 700; padding: 0.9rem 1.25rem; letter-spacing: 2px;">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.9rem 2rem; font-size: 1rem; height: 54px;">
                <i data-lucide="search"></i>
                <span>Track Shipment</span>
            </button>
        </form>
        
        <?php if (!empty($error_msg)): ?>
            <div class="login-error" style="margin-top: 1.25rem; margin-bottom: 0; text-align: left; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 0.75rem 1rem;">
                <i data-lucide="alert-triangle" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-right: 6px; color: #ef4444;"></i>
                <span style="color: #fca5a5;"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($tracking_data): ?>
        <?php 
        $details = $tracking_data['trip_details'];
        $history = $tracking_data['tracking_history'];
        $curr_status = $tracking_data['current_status'] ?: 'Order Received';
        
        $active_index = getStageIndex($curr_status, $journey_stages);
        if ($active_index === -1) $active_index = 0;

        $badge_class = 'status-' . strtolower(str_replace(' ', '_', $curr_status));
        
        // Determine fill percentage for map line
        $total_stages = count($journey_stages) - 1;
        $fill_pct = $total_stages > 0 ? round(($active_index / $total_stages) * 100) : 0;
        ?>

        <div style="display: flex; flex-direction: column; gap: 2rem;">

            <!-- Visual Journey Map -->
            <div class="dashboard-panel" style="overflow: visible;">
                <div class="panel-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 2.5rem;">
                    <h3 class="panel-title">
                        <i data-lucide="route" style="vertical-align: middle; margin-right: 6px; color: var(--accent-blue);"></i>
                        <span>Journey Map</span>
                    </h3>
                    <span class="badge <?php echo $badge_class; ?>" style="font-size: 0.8rem; padding: 0.35rem 0.85rem;">
                        <?php echo htmlspecialchars($curr_status, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <!-- Horizontal Map Timeline -->
                <div class="tracking-map-container" id="tracking-map">
                    <!-- Background Track Line -->
                    <div class="tracking-map-line">
                        <div class="tracking-map-line-fill" id="journey-progress" style="width: <?php echo $fill_pct; ?>%;"></div>
                    </div>

                    <?php foreach ($journey_stages as $i => $stage):
                        $nodeClass = 'tracking-map-node';
                        if ($i < $active_index) $nodeClass .= ' completed';
                        elseif ($i === $active_index) $nodeClass .= ' active';
                    ?>
                        <div class="<?php echo $nodeClass; ?>">
                            <div class="tracking-map-node-icon">
                                <?php if ($i < $active_index): ?>
                                    <i data-lucide="check" style="width: 28px; height: 28px;"></i>
                                <?php else: ?>
                                    <i data-lucide="<?php echo $stage['icon']; ?>" style="width: 28px; height: 28px;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="tracking-map-node-label">
                                <?php echo htmlspecialchars($stage['label'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($i === $active_index): ?>
                                    <div style="font-size: 0.7rem; font-weight: 400; color: var(--accent-blue); margin-top: 0.25rem;">● CURRENT</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Delivery Details Grid -->
            <div class="dashboard-panel">
                <div class="panel-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem;">
                    <h3 class="panel-title">
                        <i data-lucide="box" style="vertical-align: middle; margin-right: 6px; color: var(--accent-blue);"></i>
                        <span>Delivery Details</span>
                    </h3>
                </div>

                <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 0; gap: 1.5rem;">
                    <div class="form-group">
                        <span class="form-label">Trip Code</span>
                        <strong style="color: var(--text-primary); font-size: 1.1rem; font-family: monospace; letter-spacing: 1px;"><?php echo htmlspecialchars($details['trip_code'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Route</span>
                        <div style="color: var(--text-primary); font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                            <span><?php echo htmlspecialchars(explode(',', $details['origin'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                            <i data-lucide="arrow-right" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                            <span><?php echo htmlspecialchars(explode(',', $details['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Assigned Vehicle</span>
                        <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($details['vehicle_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($details['license_plate'] ?: '—', ENT_QUOTES, 'UTF-8'); ?>)</strong>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Driver Operator</span>
                        <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($details['driver_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($details['driver_phone'] ?: '—', ENT_QUOTES, 'UTF-8'); ?>)</strong>
                    </div>

                    <div class="form-group">
                        <span class="form-label">Dispatch Date</span>
                        <strong style="color: var(--text-primary);"><?php echo htmlspecialchars(date('d M Y', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>

                    <div class="form-group">
                        <span class="form-label"><?php echo ($curr_status === 'Delivered') ? 'Delivered At' : 'Est. Arrival (ETA)'; ?></span>
                        <strong style="color: var(--accent-blue);">
                            <?php 
                            if ($curr_status === 'Delivered' && !empty($history)) {
                                echo htmlspecialchars(date('h:i A, M d', strtotime($history[0]['updated_at'])), ENT_QUOTES, 'UTF-8');
                            } else {
                                echo htmlspecialchars(date('h:i A, M d', strtotime($details['trip_date'] . ' +1 day')), ENT_QUOTES, 'UTF-8');
                            }
                            ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Tracking History Timeline -->
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
                                    <h4 style="font-weight: 800; font-size: 1rem;">Order Received</h4>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
                                        <?php echo htmlspecialchars(date('h:i A, M d, Y', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <div class="timeline-meta">
                                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                                        <i data-lucide="map-pin" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                        <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($details['origin'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </div>
                                </div>
                                <p class="timeline-desc">Delivery order created and confirmed. No updates logged yet.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="timeline-flow">
                        <?php foreach ($history as $update):
                            $step_class = 'status-' . strtolower(str_replace(' ', '_', $update['status']));
                            $timeline_icon = 'circle';
                            if ($update['status'] === 'Order Received') $timeline_icon = 'clipboard-list';
                            elseif ($update['status'] === 'Vehicle Assigned') $timeline_icon = 'truck';
                            elseif ($update['status'] === 'Departing') $timeline_icon = 'log-out';
                            elseif ($update['status'] === 'In Transit') $timeline_icon = 'navigation';
                            elseif ($update['status'] === 'Arrived') $timeline_icon = 'map-pin';
                            elseif ($update['status'] === 'Delivered') $timeline_icon = 'badge-check';
                        ?>
                            <div class="timeline-step <?php echo $step_class; ?>">
                                <div class="timeline-dot" title="<?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <i data-lucide="<?php echo $timeline_icon; ?>"></i>
                                </div>
                                <div class="timeline-info">
                                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                        <h4 style="font-weight: 800; font-size: 1rem;"><?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">
                                            <?php echo htmlspecialchars(date('h:i A, M d, Y', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>
                                    <div class="timeline-meta">
                                        <div style="display: flex; align-items: center; gap: 0.25rem;">
                                            <i data-lucide="map-pin" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                            <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($update['location'], ENT_QUOTES, 'UTF-8'); ?></strong>
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
    <?php else: ?>
        <!-- Welcome empty state -->
        <div style="text-align: center; padding: 4rem 2rem; border: 1px dashed var(--border-color); border-radius: var(--radius-lg); background-color: rgba(255,255,255,0.01);">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
                <i data-lucide="scan-line" style="width: 36px; height: 36px; color: var(--accent-blue);"></i>
            </div>
            <h3 style="color: var(--text-primary); font-weight: 700; margin-bottom: 0.5rem;">Ready to Track</h3>
            <p style="color: var(--text-secondary); font-size: 0.875rem; max-width: 320px; margin: 0 auto; line-height: 1.6;">
                Input your dispatch trip code above to monitor route logistics updates in real-time.
            </p>
        </div>
    <?php endif; ?>

</div>

<?php 
require_once 'includes/footer.php'; 
?>
