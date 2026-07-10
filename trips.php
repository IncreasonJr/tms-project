<?php
// trips.php - Trips Directory
// Conforms to spec.pdf requirements (Module 5: Trip Management - Read all)

require_once __DIR__ . '/includes/header.php';

// Fetch all trips
$trips = get_all_trips();
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Trips & Dispatch Console</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Monitor route status, scheduling, and carrier assignments</p>
    </div>
    <a href="add_trip.php" class="btn btn-primary">
        <i data-lucide="plus"></i>
        <span>Schedule New Trip</span>
    </a>
</div>

<div class="dashboard-panel">
    <div class="table-container">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>Trip Code</th>
                    <th>Trip Date</th>
                    <th>Route (From -> To)</th>
                    <th>Assigned Vehicle</th>
                    <th>Assigned Driver</th>
                    <th>Status</th>
                    <th>Dispatcher</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trips)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem;">No trips have been scheduled.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td style="font-weight: 700; color: white;"><?php echo $trip['trip_code']; ?></td>
                            <td><?php echo date('d M Y', strtotime($trip['trip_date'])); ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: white; font-weight: 600;"><?php echo explode(',', $trip['origin'])[0]; ?></span>
                                    <i data-lucide="arrow-right" style="width: 12px; height: 12px; color: var(--text-muted);"></i>
                                    <span style="color: white; font-weight: 600;"><?php echo explode(',', $trip['destination'])[0]; ?></span>
                                </div>
                                <?php if (!empty($trip['purpose'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo $trip['purpose']; ?>">
                                        <?php echo $trip['purpose']; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_vehicle_name($trip['vehicle_id']); ?></td>
                            <td><?php echo get_driver_name($trip['driver_id']); ?></td>
                            <td>
                                <span class="badge status-<?php echo $trip['status']; ?>">
                                    <?php echo str_replace('_', ' ', $trip['status']); ?>
                                </span>
                            </td>
                            <td><span style="font-size: 0.8rem;"><?php echo $trip['creator_name']; ?></span></td>
                            <td style="text-align: right;">
                                <div class="action-row" style="justify-content: flex-end;">
                                    <a href="track.php?code=<?php echo $trip['trip_code']; ?>&search=1" class="btn-icon" title="Track Delivery Voyage" style="color: var(--accent-blue);">
                                        <i data-lucide="search"></i>
                                    </a>
                                    <a href="edit_trip.php?id=<?php echo $trip['id']; ?>" class="btn-icon" title="Edit Trip Details / Advance Status">
                                        <i data-lucide="edit"></i>
                                    </a>
                                    <button onclick="confirmCancelTrip(<?php echo $trip['id']; ?>, '<?php echo $trip['trip_code']; ?>')" class="btn-icon delete" title="Cancel/Delete Trip Order">
                                        <i data-lucide="x-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function confirmCancelTrip(id, code) {
        if (confirm(`Are you sure you want to cancel or delete trip order ${code}? This will release the vehicle and driver status.`)) {
            window.location.href = `delete_trip.php?id=${id}`;
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
