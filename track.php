<?php
/**
 * Private Track Delivery Page
 * Transport Management System (TMS)
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Enforce authentication — tracking is private
check_login();

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

$journey_stages = [
    ['key' => 'Order Received',   'icon' => 'clipboard-list', 'label' => 'Order Received',   'color' => '#3b82f6'],
    ['key' => 'Vehicle Assigned', 'icon' => 'truck',          'label' => 'Assigned',          'color' => '#8b5cf6'],
    ['key' => 'Departing',        'icon' => 'log-out',        'label' => 'Departing',         'color' => '#f97316'],
    ['key' => 'In Transit',       'icon' => 'navigation',     'label' => 'In Transit',        'color' => '#fbbf24'],
    ['key' => 'Arrived',          'icon' => 'map-pin',        'label' => 'Arrived',           'color' => '#10b981'],
    ['key' => 'Delivered',        'icon' => 'badge-check',    'label' => 'Delivered',         'color' => '#059669'],
];

function getStageIndex($status, $stages) {
    foreach ($stages as $i => $stage) {
        if (strtolower($stage['key']) === strtolower($status)) return $i;
    }
    return -1;
}
?>

<style>
/* ---- Tracker Page Styles ---- */
.tracker-page { max-width: 980px; margin: 0 auto; padding-top: 1rem; }

/* Route Visual */
.route-visual {
    background: linear-gradient(135deg, rgba(15,23,42,0.9) 0%, rgba(22,30,49,0.85) 100%);
    border: 1px solid rgba(59,130,246,0.2);
    border-radius: 24px;
    padding: 2rem 2.5rem 2.5rem;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(20px);
}
.route-visual::before {
    content: '';
    position: absolute;
    top: -80px; right: -80px;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(59,130,246,0.08) 0%, transparent 70%);
    pointer-events: none;
}

/* Road Track */
.road-track-wrapper {
    position: relative;
    margin: 2.5rem 0 1rem;
}
.road-track {
    position: relative;
    height: 10px;
    background: rgba(255,255,255,0.07);
    border-radius: 10px;
    margin: 0 3.5%;
    overflow: visible;
}
.road-track-fill {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(90deg, #3b82f6, #10b981);
    transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.road-track-fill::after {
    content: '';
    position: absolute;
    right: -1px; top: 50%;
    transform: translateY(-50%);
    width: 18px; height: 18px;
    border-radius: 50%;
    background: white;
    box-shadow: 0 0 12px rgba(59,130,246,0.8);
}

/* Truck Animation */
.truck-container {
    position: absolute;
    top: -28px;
    transition: left 1.2s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateX(-50%);
    z-index: 10;
    filter: drop-shadow(0 4px 12px rgba(59,130,246,0.5));
}
.truck-svg {
    width: 56px;
    height: 56px;
    animation: truckBob 1.8s ease-in-out infinite;
}
@keyframes truckBob {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-3px); }
}

/* Stage nodes */
.stage-nodes {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin-top: 1rem;
}
.stage-node {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    cursor: default;
}
.stage-node-dot {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.3);
    transition: all 0.5s ease;
    position: relative;
}
.stage-node.done .stage-node-dot {
    background: rgba(16,185,129,0.2);
    border-color: #10b981;
    color: #34d399;
}
.stage-node.active .stage-node-dot {
    border-color: var(--node-color, #3b82f6);
    background: rgba(59,130,246,0.15);
    color: var(--node-color, #60a5fa);
    box-shadow: 0 0 0 6px rgba(59,130,246,0.15), 0 0 20px rgba(59,130,246,0.3);
    animation: nodePulse 2s ease-in-out infinite;
}
@keyframes nodePulse {
    0%, 100% { box-shadow: 0 0 0 6px rgba(59,130,246,0.15), 0 0 20px rgba(59,130,246,0.3); }
    50%       { box-shadow: 0 0 0 10px rgba(59,130,246,0.08), 0 0 30px rgba(59,130,246,0.5); }
}
.stage-node-label {
    font-size: 0.68rem;
    font-weight: 600;
    text-align: center;
    color: rgba(255,255,255,0.35);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    line-height: 1.3;
}
.stage-node.done .stage-node-label { color: #34d399; }
.stage-node.active .stage-node-label { color: white; font-weight: 700; }

/* Current status badge */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 1.1rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    background: rgba(59,130,246,0.15);
    border: 1px solid rgba(59,130,246,0.3);
    color: #60a5fa;
}
.status-pill-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #60a5fa;
    animation: blink 1.2s ease-in-out infinite;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.3; }
}

/* Info grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-top: 1.5rem;
}
.info-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    padding: 1rem 1.25rem;
    transition: border-color 0.3s;
}
.info-card:hover { border-color: rgba(59,130,246,0.3); }
.info-card-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-secondary);
    margin-bottom: 0.4rem;
}
.info-card-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-primary);
}
.info-card-value.mono {
    font-family: monospace;
    font-size: 1rem;
    letter-spacing: 1px;
    color: #818cf8;
}

/* Timeline */
.tl-wrapper { position: relative; padding-left: 2.5rem; }
.tl-line {
    position: absolute;
    left: 13px; top: 12px; bottom: 12px;
    width: 2px;
    background: linear-gradient(to bottom, #3b82f6, rgba(255,255,255,0.05));
    border-radius: 2px;
}
.tl-step {
    position: relative;
    margin-bottom: 1.75rem;
    animation: fadeSlideIn 0.4s ease both;
}
.tl-step:nth-child(1) { animation-delay: 0.05s; }
.tl-step:nth-child(2) { animation-delay: 0.12s; }
.tl-step:nth-child(3) { animation-delay: 0.19s; }
.tl-step:nth-child(4) { animation-delay: 0.26s; }
.tl-step:nth-child(5) { animation-delay: 0.33s; }
.tl-step:nth-child(6) { animation-delay: 0.40s; }
@keyframes fadeSlideIn {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
}
.tl-dot {
    position: absolute;
    left: -2rem;
    top: 4px;
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255,255,255,0.1);
    background: rgba(22,30,49,1);
    color: #60a5fa;
}
.tl-dot.latest {
    border-color: #3b82f6;
    background: rgba(59,130,246,0.15);
    box-shadow: 0 0 12px rgba(59,130,246,0.4);
}
.tl-body {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    padding: 1rem 1.25rem;
}
.tl-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.tl-status { font-weight: 800; font-size: 0.95rem; color: var(--text-primary); }
.tl-time   { font-size: 0.72rem; color: var(--text-muted); font-weight: 500; white-space: nowrap; }
.tl-loc    { font-size: 0.8rem; color: var(--text-secondary); display: flex; align-items: center; gap: 0.35rem; margin-bottom: 0.35rem; }
.tl-desc   { font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; }

/* Light mode overrides */
body.light-theme .route-visual {
    background: rgba(255,255,255,0.85);
    border-color: rgba(59,130,246,0.15);
}
body.light-theme .road-track { background: rgba(15,23,42,0.08); }
body.light-theme .stage-node-label { color: #94a3b8; }
body.light-theme .stage-node.active .stage-node-label { color: #0f172a; }
body.light-theme .stage-node.done .stage-node-label { color: #059669; }
body.light-theme .stage-node-dot { background: rgba(15,23,42,0.04); border-color: rgba(15,23,42,0.1); color: #94a3b8; }
body.light-theme .info-card { background: rgba(15,23,42,0.03); border-color: rgba(15,23,42,0.08); }
body.light-theme .info-card-label { color: #64748b; }
body.light-theme .info-card-value { color: #0f172a; }
body.light-theme .tl-body { background: rgba(15,23,42,0.03); border-color: rgba(15,23,42,0.07); }
body.light-theme .tl-dot { background: #f8fafc; border-color: rgba(15,23,42,0.1); }
body.light-theme .tl-status { color: #0f172a; }
body.light-theme .tl-loc { color: #475569; }
body.light-theme .tl-desc { color: #64748b; }
body.light-theme .status-pill { background: rgba(59,130,246,0.08); }
</style>

<div class="tracker-page">

    <!-- Header -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <img src="assets/images/Logo1.png" alt="FLEET Logo" style="height: 46px; margin-bottom: 0.75rem; object-fit: contain;">
        <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px;">Live Shipment Tracker</h2>
        <p style="font-size: 0.875rem; color: var(--text-secondary); max-width: 420px; margin: 0.4rem auto 0; line-height: 1.6;">
            Enter your trip code to monitor real-time route progress, milestones and delivery status.
        </p>
    </div>

    <!-- Search -->
    <div class="glass-container" style="padding: 1.25rem 1.5rem; margin-bottom: 2rem; border-radius: 16px;">
        <form action="track.php" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <input type="hidden" name="search" value="1">
            <div style="flex-grow: 1; min-width: 220px;">
                <label style="font-size: 0.7rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.4rem;">Trip Code</label>
                <input type="text" name="code" class="form-control"
                    placeholder="e.g. TRP-987214"
                    value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                    style="font-size: 1.05rem; text-transform: uppercase; font-family: monospace; font-weight: 700; padding: 0.85rem 1.25rem; letter-spacing: 2px;">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.85rem 2rem; font-size: 0.95rem; height: 52px;">
                <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                <span>Track</span>
            </button>
        </form>
        <?php if (!empty($error_msg)): ?>
            <div style="margin-top: 1rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px; padding: 0.75rem 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i data-lucide="alert-triangle" style="width: 16px; height: 16px; color: #ef4444; flex-shrink: 0;"></i>
                <span style="color: #fca5a5; font-size: 0.875rem;"><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($tracking_data): ?>
    <?php
    $details     = $tracking_data['trip_details'];
    $history     = $tracking_data['tracking_history'];
    $curr_status = $tracking_data['current_status'] ?: 'Order Received';
    $active_idx  = getStageIndex($curr_status, $journey_stages);
    if ($active_idx === -1) $active_idx = 0;
    $total_stages = count($journey_stages) - 1;
    $fill_pct     = $total_stages > 0 ? round(($active_idx / $total_stages) * 100) : 0;
    $truck_left   = $fill_pct; // percentage position on road
    $active_color = $journey_stages[$active_idx]['color'];
    ?>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- ===== ROUTE VISUAL TRACKER ===== -->
        <div class="route-visual">

            <!-- Top Row: Trip Code + Status -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <div style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); font-weight: 700; margin-bottom: 0.25rem;">Trip Code</div>
                    <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: #818cf8; letter-spacing: 2px;"><?php echo htmlspecialchars($details['trip_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="status-pill" style="--pill-color: <?php echo $active_color; ?>; background: <?php echo $active_color; ?>18; border-color: <?php echo $active_color; ?>44; color: <?php echo $active_color; ?>;">
                    <div class="status-pill-dot" style="background: <?php echo $active_color; ?>;"></div>
                    <?php echo htmlspecialchars($curr_status, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <!-- Origin → Destination labels -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <div style="text-align: left;">
                    <div style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 0.2rem;">Origin</div>
                    <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars(explode(',', $details['origin'])[0], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.08); margin: 0 1rem;"></div>
                <div style="text-align: right;">
                    <div style="font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 0.2rem;">Destination</div>
                    <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-primary);"><?php echo htmlspecialchars(explode(',', $details['destination'])[0], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <!-- Road track with animated truck -->
            <div class="road-track-wrapper">
                <!-- Truck icon (SVG) -->
                <div class="truck-container" id="truck-icon" style="left: <?php echo max(4, min(96, $truck_left)); ?>%;">
                    <svg class="truck-svg" viewBox="0 0 64 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Truck body -->
                        <rect x="2" y="10" width="36" height="22" rx="3" fill="#3b82f6" opacity="0.9"/>
                        <!-- Cab -->
                        <path d="M38 14 L52 14 L58 24 L58 32 L38 32 Z" fill="#2563eb"/>
                        <!-- Windshield -->
                        <path d="M40 15 L51 15 L56 24 L40 24 Z" fill="#93c5fd" opacity="0.7"/>
                        <!-- Cargo lines -->
                        <line x1="10" y1="16" x2="10" y2="28" stroke="white" stroke-width="1" opacity="0.3"/>
                        <line x1="18" y1="16" x2="18" y2="28" stroke="white" stroke-width="1" opacity="0.3"/>
                        <line x1="26" y1="16" x2="26" y2="28" stroke="white" stroke-width="1" opacity="0.3"/>
                        <!-- Wheels -->
                        <circle cx="14" cy="33" r="5" fill="#1e293b" stroke="#60a5fa" stroke-width="2"/>
                        <circle cx="14" cy="33" r="2.5" fill="#60a5fa" opacity="0.6"/>
                        <circle cx="48" cy="33" r="5" fill="#1e293b" stroke="#60a5fa" stroke-width="2"/>
                        <circle cx="48" cy="33" r="2.5" fill="#60a5fa" opacity="0.6"/>
                        <!-- Headlight -->
                        <rect x="55" y="25" width="4" height="5" rx="1" fill="#fde68a"/>
                        <!-- Speed lines -->
                        <?php if ($curr_status === 'In Transit' || $curr_status === 'Departing'): ?>
                        <line x1="-4" y1="18" x2="2" y2="18" stroke="#60a5fa" stroke-width="1.5" stroke-dasharray="2 2" opacity="0.5"/>
                        <line x1="-6" y1="22" x2="2" y2="22" stroke="#60a5fa" stroke-width="1" stroke-dasharray="2 2" opacity="0.4"/>
                        <line x1="-3" y1="26" x2="2" y2="26" stroke="#60a5fa" stroke-width="1" stroke-dasharray="2 2" opacity="0.3"/>
                        <?php endif; ?>
                    </svg>
                </div>

                <!-- Road -->
                <div class="road-track">
                    <div class="road-track-fill" id="route-fill" style="width: <?php echo $fill_pct; ?>%;"></div>
                </div>
            </div>

            <!-- Stage Nodes -->
            <div class="stage-nodes" style="margin-top: 0.75rem;">
                <?php foreach ($journey_stages as $i => $stage):
                    $nodeClass = 'stage-node';
                    if ($i < $active_idx)  $nodeClass .= ' done';
                    if ($i === $active_idx) $nodeClass .= ' active';
                ?>
                <div class="<?php echo $nodeClass; ?>" style="--node-color: <?php echo $stage['color']; ?>">
                    <div class="stage-node-dot">
                        <?php if ($i < $active_idx): ?>
                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                        <?php else: ?>
                            <i data-lucide="<?php echo $stage['icon']; ?>" style="width: 14px; height: 14px;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="stage-node-label"><?php echo $stage['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Info Cards -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-card-label">Vehicle</div>
                    <div class="info-card-value"><?php echo htmlspecialchars($details['vehicle_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if ($details['license_plate']): ?>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem; font-family: monospace;"><?php echo htmlspecialchars($details['license_plate'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="info-card">
                    <div class="info-card-label">Driver</div>
                    <div class="info-card-value"><?php echo htmlspecialchars($details['driver_name'] ?: 'Not Assigned', ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if ($details['driver_phone']): ?>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem;"><?php echo htmlspecialchars($details['driver_phone'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                </div>
                <div class="info-card">
                    <div class="info-card-label">Dispatch Date</div>
                    <div class="info-card-value"><?php echo htmlspecialchars(date('d M Y', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-card-label"><?php echo ($curr_status === 'Delivered') ? 'Delivered At' : 'Est. Arrival'; ?></div>
                    <div class="info-card-value" style="color: <?php echo $active_color; ?>;">
                        <?php
                        if ($curr_status === 'Delivered' && !empty($history)) {
                            echo htmlspecialchars(date('h:i A, M d', strtotime($history[0]['updated_at'])), ENT_QUOTES, 'UTF-8');
                        } else {
                            echo htmlspecialchars(date('h:i A, M d', strtotime($details['trip_date'] . ' +1 day')), ENT_QUOTES, 'UTF-8');
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== TRACKING TIMELINE ===== -->
        <div class="dashboard-panel" style="padding: 1.75rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.75rem;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); display: flex; align-items: center; justify-content: center; color: #60a5fa; flex-shrink: 0;">
                    <i data-lucide="history" style="width: 18px; height: 18px;"></i>
                </div>
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0;">Transit Tracking Timeline</h3>
                    <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0;">Live status log for <?php echo htmlspecialchars($details['trip_code'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>

            <?php if (empty($history)): ?>
                <div class="tl-wrapper">
                    <div class="tl-line"></div>
                    <div class="tl-step">
                        <div class="tl-dot latest">
                            <i data-lucide="clipboard-list" style="width: 14px; height: 14px;"></i>
                        </div>
                        <div class="tl-body">
                            <div class="tl-header">
                                <span class="tl-status">Order Received</span>
                                <span class="tl-time"><?php echo htmlspecialchars(date('h:i A, M d, Y', strtotime($details['trip_date'])), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="tl-loc">
                                <i data-lucide="map-pin" style="width: 12px; height: 12px;"></i>
                                <?php echo htmlspecialchars($details['origin'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <div class="tl-desc">Delivery order created and confirmed. Awaiting further status updates.</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="tl-wrapper">
                    <div class="tl-line"></div>
                    <?php foreach ($history as $idx => $update):
                        $icon_map = [
                            'Order Received'   => 'clipboard-list',
                            'Vehicle Assigned' => 'truck',
                            'Departing'        => 'log-out',
                            'In Transit'       => 'navigation',
                            'Arrived'          => 'map-pin',
                            'Delivered'        => 'badge-check',
                        ];
                        $tl_icon = $icon_map[$update['status']] ?? 'circle';
                        $is_latest = ($idx === 0);
                    ?>
                        <div class="tl-step">
                            <div class="tl-dot <?php echo $is_latest ? 'latest' : ''; ?>">
                                <i data-lucide="<?php echo $tl_icon; ?>" style="width: 14px; height: 14px;"></i>
                            </div>
                            <div class="tl-body">
                                <div class="tl-header">
                                    <span class="tl-status"><?php echo htmlspecialchars($update['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="tl-time"><?php echo htmlspecialchars(date('h:i A, M d', strtotime($update['updated_at'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="tl-loc">
                                    <i data-lucide="map-pin" style="width: 12px; height: 12px;"></i>
                                    <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($update['location'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                                <div class="tl-desc"><?php echo htmlspecialchars($update['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php else: ?>
    <!-- Empty state -->
    <div style="text-align: center; padding: 5rem 2rem; border: 1px dashed rgba(59,130,246,0.2); border-radius: 24px; background: rgba(59,130,246,0.02);">
        <div style="width: 90px; height: 90px; border-radius: 50%; background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.15); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
            <svg viewBox="0 0 64 42" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 52px; height: 52px; opacity: 0.6;">
                <rect x="2" y="10" width="36" height="22" rx="3" fill="#3b82f6"/>
                <path d="M38 14 L52 14 L58 24 L58 32 L38 32 Z" fill="#2563eb"/>
                <path d="M40 15 L51 15 L56 24 L40 24 Z" fill="#93c5fd" opacity="0.7"/>
                <circle cx="14" cy="33" r="5" fill="#1e293b" stroke="#60a5fa" stroke-width="2"/>
                <circle cx="48" cy="33" r="5" fill="#1e293b" stroke="#60a5fa" stroke-width="2"/>
            </svg>
        </div>
        <h3 style="color: var(--text-primary); font-weight: 700; font-size: 1.25rem; margin-bottom: 0.5rem;">Ready to Track</h3>
        <p style="color: var(--text-secondary); font-size: 0.875rem; max-width: 340px; margin: 0 auto; line-height: 1.6;">
            Enter your dispatch trip code above to see real-time route progress, vehicle location milestones, and delivery status.
        </p>
    </div>
    <?php endif; ?>

</div>

<script>
// Animate truck and progress bar on load
document.addEventListener('DOMContentLoaded', () => {
    const fill = document.getElementById('route-fill');
    const truck = document.getElementById('truck-icon');
    if (fill && truck) {
        // Brief delay then animate in
        setTimeout(() => {
            fill.style.transition = 'width 1.4s cubic-bezier(0.4,0,0.2,1)';
            truck.style.transition = 'left 1.4s cubic-bezier(0.4,0,0.2,1)';
        }, 200);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
