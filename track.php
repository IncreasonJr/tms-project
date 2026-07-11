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
    $active_color = $journey_stages[$active_idx]['color'];
    ?>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- ===== ROUTE VISUAL TRACKER ===== -->
        <div class="route-visual">

            <!-- Top Row: Trip Code + Status -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <div style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-secondary); font-weight: 700; margin-bottom: 0.25rem;">Trip Code</div>
                    <div style="font-family: monospace; font-size: 1.5rem; font-weight: 900; color: #818cf8; letter-spacing: 2px;"><?php echo htmlspecialchars($details['trip_code'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <div class="status-pill" style="--pill-color: <?php echo $active_color; ?>; background: <?php echo $active_color; ?>18; border-color: <?php echo $active_color; ?>44; color: <?php echo $active_color; ?>;">
                    <div class="status-pill-dot" style="background: <?php echo $active_color; ?>;"></div>
                    <?php echo htmlspecialchars($curr_status, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <!-- Dynamic GPS Highway canvas & Telemetry Grid -->
            <div style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 1.5rem; margin: 1.5rem 0; min-height: 250px; flex-wrap: wrap;" id="map-dashboard-grid">
                
                <!-- Map Canvas -->
                <div style="position: relative; border-radius: 16px; border: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.3); overflow: hidden; padding: 0.5rem; min-height: 250px;" id="canvas-container">
                    <canvas id="gps-route-canvas" style="display: block; width: 100%; height: 100%; min-height: 250px;"></canvas>
                    
                    <div style="position: absolute; top: 12px; left: 12px; display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.65rem; background: rgba(15,23,42,0.85); border: 1px solid rgba(59,130,246,0.3); border-radius: 6px; backdrop-filter: blur(10px); font-size: 0.68rem; font-family: monospace; font-weight: 700; color: #60a5fa;">
                        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: #3b82f6; box-shadow: 0 0 8px #3b82f6; animation: blink 1s infinite;"></span>
                        <span>GPS LIVE FEED: CONNECTED</span>
                    </div>
                </div>

                <!-- Telemetry Metrics Card -->
                <div style="display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; padding: 1.25rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px;" id="telemetry-panel">
                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 0.65rem; margin-bottom: 0.75rem;">
                            <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-secondary); font-weight: 700;">Voyage Telemetry</span>
                            <span id="tel-signal" style="font-size: 0.68rem; font-family: monospace; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 4px;">
                                <i data-lucide="signal" style="width: 12px; height: 12px; vertical-align: middle;"></i> SIGNAL: 100%
                            </span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">GPS Coordinates</span>
                                <strong id="tel-coords" style="font-size: 0.82rem; font-family: monospace; color: var(--text-primary);">--° N, --° W</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Current Landmark</span>
                                <strong id="tel-location" style="font-size: 0.82rem; color: #60a5fa; text-align: right; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($latest_update ? $latest_update['location'] : $details['origin'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Cruising Speed</span>
                                <strong id="tel-speed" style="font-size: 0.82rem; font-family: monospace; color: #10b981;">72 km/h</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 500;">Route Distance</span>
                                <strong id="tel-distance" style="font-size: 0.82rem; font-family: monospace; color: #fbbf24;">-- km remaining</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Countdown clock card -->
                    <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.15); border-radius: 12px; padding: 0.75rem 1rem; text-align: center;">
                        <span style="font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.1em; color: #93c5fd; font-weight: 700; display: block; margin-bottom: 0.25rem;">Est. Time to Destination</span>
                        <div id="tel-eta" style="font-size: 1.4rem; font-family: monospace; font-weight: 900; color: #60a5fa; letter-spacing: 1px;">00:00:00</div>
                        <span style="font-size: 0.65rem; color: var(--text-muted); display: block; margin-top: 0.15rem;">Live ETA Countdown</span>
                    </div>
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
// Dynamic Route Canvas & GPS Telemetry Simulator
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($tracking_data): ?>
    
    // GPS Coordinates Database of major towns in Ghana
    const coordinates = {
        "accra": {lat: 5.6037, lng: -0.1870},
        "kumasi": {lat: 6.6961, lng: -1.6149},
        "tamale": {lat: 9.4075, lng: -0.8393},
        "takoradi": {lat: 4.9016, lng: -1.7831},
        "cape coast": {lat: 5.1053, lng: -1.2466},
        "koforidua": {lat: 6.0945, lng: -0.2591},
        "ho": {lat: 6.1084, lng: 0.4738},
        "sunyani": {lat: 7.3349, lng: -2.3124},
        "wa": {lat: 9.7126, lng: -2.5089},
        "bolgatanga": {lat: 10.7856, lng: -0.8514}
    };

    const originCity = "<?php echo htmlspecialchars(explode(',', $details['origin'])[0], ENT_QUOTES, 'UTF-8'); ?>";
    const destCity = "<?php echo htmlspecialchars(explode(',', $details['destination'])[0], ENT_QUOTES, 'UTF-8'); ?>";
    const fillPct = <?php echo intval($fill_pct); ?>;
    const currentStatus = "<?php echo htmlspecialchars($curr_status, ENT_QUOTES, 'UTF-8'); ?>";

    // Setup Canvas
    const canvas = document.getElementById('gps-route-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    function resizeCanvas() {
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Resolve Lat/Lng base points
    const startCoord = coordinates[originCity.toLowerCase()] || coordinates["accra"];
    const endCoord = coordinates[destCity.toLowerCase()] || coordinates["kumasi"];

    // Current position along the line
    let currentLat = startCoord.lat + (endCoord.lat - startCoord.lat) * (fillPct / 100);
    let currentLng = startCoord.lng + (endCoord.lng - startCoord.lng) * (fillPct / 100);

    // Speedometer & Distance base values
    let baseSpeed = (currentStatus === 'In Transit') ? 78 : ((currentStatus === 'Departing') ? 35 : 0);
    let totalDistance = 240; // Simulated km
    let distanceRemaining = Math.max(0, Math.round(totalDistance * (1 - (fillPct / 100))));
    let distanceCovered = totalDistance - distanceRemaining;
    
    // ETA countdown duration in seconds
    let etaSeconds = distanceRemaining * 65; // Approx 65 seconds per km
    if (currentStatus === 'Delivered') etaSeconds = 0;
    if (currentStatus === 'Arrived') etaSeconds = 120; // 2 mins to park

    const formatTime = (secs) => {
        const h = Math.floor(secs / 3600).toString().padStart(2, '0');
        const m = Math.floor((secs % 3600) / 60).toString().padStart(2, '0');
        const s = (secs % 60).toString().padStart(2, '0');
        return `${h}:${m}:${s}`;
    };

    // Telemetry updates
    const etaEl = document.getElementById('tel-eta');
    const coordsEl = document.getElementById('tel-coords');
    const speedEl = document.getElementById('tel-speed');
    const distanceEl = document.getElementById('tel-distance');

    // Winding highway path helper (bezier control points calculation)
    function getBezierPoint(p0, p1, p2, p3, t) {
        const cx = 3 * (p1.x - p0.x);
        const bx = 3 * (p2.x - p1.x) - cx;
        const ax = p3.x - p0.x - cx - bx;
        
        const cy = 3 * (p1.y - p0.y);
        const by = 3 * (p2.y - p1.y) - cy;
        const ay = p3.y - p0.y - cy - by;
        
        const xt = ax*(t*t*t) + bx*(t*t) + cx*t + p0.x;
        const yt = ay*(t*t*t) + by*(t*t) + cy*t + p0.y;
        
        return {x: xt, y: yt};
    }

    // Render loop
    function drawMap() {
        if (!canvas.width || !canvas.height) return;
        
        const w = canvas.getBoundingClientRect().width;
        const h = canvas.getBoundingClientRect().height;
        const padding = 45;
        
        ctx.clearRect(0, 0, w, h);
        
        // Define coordinates relative to size
        const p0 = { x: padding, y: h - padding };
        const p1 = { x: w * 0.35, y: h * 0.85 };
        const p2 = { x: w * 0.65, y: h * 0.15 };
        const p3 = { x: w - padding, y: padding };
        
        // Draw gridlines (futuristic radar style)
        ctx.strokeStyle = document.body.classList.contains('light-theme') ? 'rgba(0, 0, 0, 0.04)' : 'rgba(59, 130, 246, 0.04)';
        ctx.lineWidth = 1;
        const gridSpacing = 20;
        for (let x = 0; x < w; x += gridSpacing) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, h);
            ctx.stroke();
        }
        for (let y = 0; y < h; y += gridSpacing) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(w, y);
            ctx.stroke();
        }
        
        // Draw highway road layout
        ctx.strokeStyle = document.body.classList.contains('light-theme') ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.06)';
        ctx.lineWidth = 8;
        ctx.lineCap = 'round';
        ctx.beginPath();
        ctx.moveTo(p0.x, p0.y);
        ctx.bezierCurveTo(p1.x, p1.y, p2.x, p2.y, p3.x, p3.y);
        ctx.stroke();
        
        // Draw middle divider dashed line
        ctx.strokeStyle = document.body.classList.contains('light-theme') ? 'rgba(0,0,0,0.15)' : 'rgba(59,130,246,0.2)';
        ctx.lineWidth = 2;
        ctx.setLineDash([6, 8]);
        ctx.beginPath();
        ctx.moveTo(p0.x, p0.y);
        ctx.bezierCurveTo(p1.x, p1.y, p2.x, p2.y, p3.x, p3.y);
        ctx.stroke();
        ctx.setLineDash([]); // Reset
        
        // Draw filled progress route (green/blue)
        ctx.strokeStyle = '#10b981';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.moveTo(p0.x, p0.y);
        
        const currentProgressT = fillPct / 100;
        for (let t = 0; t <= currentProgressT; t += 0.01) {
            const pt = getBezierPoint(p0, p1, p2, p3, t);
            ctx.lineTo(pt.x, pt.y);
        }
        ctx.stroke();
        
        // Draw Origin City Node
        ctx.fillStyle = '#3b82f6';
        ctx.beginPath();
        ctx.arc(p0.x, p0.y, 6, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = 'rgba(59, 130, 246, 0.2)';
        ctx.beginPath();
        ctx.arc(p0.x, p0.y, 14, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = document.body.classList.contains('light-theme') ? '#0f172a' : '#94a3b8';
        ctx.font = 'bold 10px monospace';
        ctx.fillText(originCity, p0.x - 20, p0.y + 24);
        
        // Draw Destination City Node
        ctx.fillStyle = '#10b981';
        ctx.beginPath();
        ctx.arc(p3.x, p3.y, 6, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = 'rgba(16, 185, 129, 0.2)';
        ctx.beginPath();
        ctx.arc(p3.x, p3.y, 14, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = document.body.classList.contains('light-theme') ? '#0f172a' : '#94a3b8';
        ctx.font = 'bold 10px monospace';
        ctx.fillText(destCity, p3.x - 25, p3.y - 14);
        
        // Intermediate Checkpoint Landmarks along highway
        const landmarkT = 0.5;
        const landmarkPt = getBezierPoint(p0, p1, p2, p3, landmarkT);
        ctx.fillStyle = fillPct >= 50 ? '#10b981' : 'rgba(255,255,255,0.2)';
        ctx.beginPath();
        ctx.arc(landmarkPt.x, landmarkPt.y, 4, 0, Math.PI * 2);
        ctx.fill();
        ctx.font = '8px sans-serif';
        ctx.fillStyle = document.body.classList.contains('light-theme') ? '#64748b' : 'rgba(255,255,255,0.4)';
        ctx.fillText("Transit Landmark", landmarkPt.x + 8, landmarkPt.y + 3);

        // Draw animated vehicle (Truck) indicator
        const truckPt = getBezierPoint(p0, p1, p2, p3, currentProgressT);
        
        let bounce = 0;
        if (currentStatus === 'In Transit' || currentStatus === 'Departing') {
            bounce = Math.sin(Date.now() / 150) * 1.5;
        }
        
        // Pulse ring around truck
        ctx.strokeStyle = 'rgba(59, 130, 246, 0.6)';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.arc(truckPt.x, truckPt.y + bounce, 12 + Math.abs(bounce*2), 0, Math.PI * 2);
        ctx.stroke();
        
        // Truck Dot
        ctx.fillStyle = '#2563eb';
        ctx.beginPath();
        ctx.arc(truckPt.x, truckPt.y + bounce, 7, 0, Math.PI * 2);
        ctx.fill();
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        // Label vehicle marker
        ctx.fillStyle = document.body.classList.contains('light-theme') ? '#0f172a' : '#ffffff';
        ctx.font = 'bold 9px monospace';
        ctx.fillText("VEHICLE", truckPt.x - 20, truckPt.y - 12 + bounce);
        
        requestAnimationFrame(drawMap);
    }
    
    // Start canvas loop
    setTimeout(() => {
        requestAnimationFrame(drawMap);
    }, 100);

    // Live Telemetry Loop
    setInterval(() => {
        // GPS Coordinate signal drift simulation
        const driftLat = (Math.random() * 0.0004) - 0.0002;
        const driftLng = (Math.random() * 0.0004) - 0.0002;
        const displayLat = (currentLat + driftLat).toFixed(4);
        const displayLng = (currentLng + driftLng).toFixed(4);
        if (coordsEl) coordsEl.textContent = `${displayLat}° N, ${displayLng}° W`;

        // Cruise speed updates
        if (currentStatus === 'In Transit') {
            const speed = baseSpeed + Math.floor(Math.random() * 7) - 3;
            if (speedEl) speedEl.textContent = `${speed} km/h`;
        } else if (currentStatus === 'Departing') {
            const speed = baseSpeed + Math.floor(Math.random() * 5) - 2;
            if (speedEl) speedEl.textContent = `${speed} km/h`;
        } else if (currentStatus === 'Delivered') {
            if (speedEl) speedEl.textContent = `0 km/h (Parked)`;
        } else {
            if (speedEl) speedEl.textContent = `0 km/h`;
        }

        // ETA countdown decrementer
        if (etaSeconds > 0) {
            etaSeconds--;
            if (etaEl) etaEl.textContent = formatTime(etaSeconds);
        } else {
            if (etaEl) etaEl.textContent = "00:00:00";
        }

        // Remaining distance updates
        if (distanceEl) {
            distanceEl.textContent = `${distanceRemaining} km / ${totalDistance} km`;
        }

    }, 1000);

    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
