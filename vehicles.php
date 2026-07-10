<?php
// vehicles.php - Vehicle Fleet Directory
// Conforms to spec.pdf requirements (Module 3: Vehicle Management - View all)

require_once __DIR__ . '/includes/header.php';

// Fetch all vehicles
$vehicles = get_all_vehicles();
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Active Vehicle Fleet</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Manage trucks, vans, and cargo carriers in service</p>
    </div>
</div>

<div class="dashboard-panel">
    <div class="table-container">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vehicle Name</th>
                    <th>License Plate</th>
                    <th>Model</th>
                    <th>Load Capacity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vehicles)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">No vehicles found in fleet registry.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td style="font-weight: 700; color: white;">#<?php echo $vehicle['id']; ?></td>
                            <td style="color: white; font-weight: 600;"><?php echo $vehicle['vehicle_name']; ?></td>
                            <td><span style="font-family: monospace; background-color: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: white;"><?php echo $vehicle['license_plate']; ?></span></td>
                            <td><?php echo $vehicle['model']; ?></td>
                            <td><?php echo number_format($vehicle['capacity']); ?> lbs</td>
                            <td>
                                <span class="badge status-<?php echo $vehicle['status']; ?>">
                                    <?php echo str_replace('_', ' ', $vehicle['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
