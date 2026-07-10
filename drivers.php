<?php
// drivers.php - Active Driver Registry
// Conforms to spec.pdf requirements (Module 4: Driver Management - View all)

require_once __DIR__ . '/includes/header.php';

// Fetch all drivers
$drivers = get_all_drivers();
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Active Driver Registry</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Manage driver availability and contact channels</p>
    </div>
</div>

<div class="dashboard-panel">
    <div class="table-container">
        <table class="tms-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Driver Name</th>
                    <th>License Number</th>
                    <th>Phone Contact</th>
                    <th>Email Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drivers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem;">No drivers found in driver directory.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($drivers as $driver): ?>
                        <tr>
                            <td style="font-weight: 700; color: white;">#<?php echo $driver['id']; ?></td>
                            <td style="color: white; font-weight: 600;"><?php echo $driver['fullname']; ?></td>
                            <td><span style="font-family: monospace; background-color: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color); color: white;"><?php echo $driver['license_number']; ?></span></td>
                            <td>
                                <a href="tel:<?php echo $driver['phone']; ?>" style="color: var(--accent-blue); text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <i data-lucide="phone" style="width: 12px; height: 12px;"></i>
                                    <?php echo $driver['phone']; ?>
                                </a>
                            </td>
                            <td><?php echo $driver['email'] ? $driver['email'] : '<span class="text-muted">N/A</span>'; ?></td>
                            <td>
                                <span class="badge status-<?php echo $driver['status']; ?>">
                                    <?php echo str_replace('_', ' ', $driver['status']); ?>
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
