<?php
// maintenance.php - Vehicle Maintenance Logging Module
// Conforms to spec.pdf requirements (Module 6: Maintenance Management)

require_once __DIR__ . '/includes/header.php';

// Fetch all vehicles for dropdown
$vehicles = get_all_vehicles();

// Fetch all maintenance history records
$maintenance_logs = get_all_maintenance();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = intval($_POST['vehicle_id']);
    $maintenance_date = sanitize_input($_POST['maintenance_date']);
    $description = sanitize_input($_POST['description']);
    $cost = floatval($_POST['cost']);
    $next_due_date = !empty($_POST['next_due_date']) ? sanitize_input($_POST['next_due_date']) : null;
    $status = sanitize_input($_POST['status']);
    
    if (empty($vehicle_id) || empty($maintenance_date) || empty($description) || $cost < 0 || empty($status)) {
        $error = 'Please fill out all required fields. Cost must be positive.';
    } else {
        $ok = insert_maintenance($vehicle_id, $maintenance_date, $description, $cost, $next_due_date, $status);
        if ($ok) {
            header("Location: maintenance.php?msg=Maintenance+activity+logged+successfully!&type=success");
            exit;
        } else {
            $error = 'Failed to save maintenance record.';
        }
    }
}
?>

<div class="panel-header" style="margin-bottom: 2rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 700; color: white; margin-bottom: 0.25rem;">Fleet Maintenance Control Desk</h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">Register vehicle servicing entries, track maintenance history, and adjust fleet availability</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="login-error" style="text-align: left; max-width: 100%; margin-bottom: 1.5rem;">
        <i data-lucide="alert-circle" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="dashboard-row" style="grid-template-cols: 1fr 2fr;">
    
    <!-- Left Column: Log Maintenance Form -->
    <div class="dashboard-panel">
        <h3 class="panel-title" style="margin-bottom: 1.5rem;">Log Maintenance Activity</h3>
        
        <form action="maintenance.php" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="form-group">
                <label for="vehicle_id" class="form-label">Select Vehicle *</label>
                <select id="vehicle_id" name="vehicle_id" required class="form-control">
                    <option value="">-- Choose vehicle --</option>
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo $v['vehicle_name'] . ' (' . $v['license_plate'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="maintenance_date" class="form-label">Maintenance Date *</label>
                <input type="date" id="maintenance_date" name="maintenance_date" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="cost" class="form-label">Service Cost (GHS) *</label>
                <input type="number" id="cost" name="cost" required step="0.01" min="0" placeholder="0.00" class="form-control">
            </div>

            <div class="form-group">
                <label for="next_due_date" class="form-label">Next Service Due Date</label>
                <input type="date" id="next_due_date" name="next_due_date" class="form-control">
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Service Status *</label>
                <select id="status" name="status" required class="form-control">
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress (Hold Vehicle)</option>
                    <option value="completed">Completed (Release Vehicle)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Service Description *</label>
                <textarea id="description" name="description" required rows="3" placeholder="Specify oil checks, brake inspections, suspension adjustments..." class="form-control"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="justify-content: center; padding: 0.8rem; margin-top: 0.5rem;">
                <i data-lucide="plus-circle"></i>
                <span>Log Service Activity</span>
            </button>
        </form>
    </div>

    <!-- Right Column: Maintenance History Logs -->
    <div class="dashboard-panel">
        <h3 class="panel-title" style="margin-bottom: 1.5rem;">Fleet Service History Logs</h3>
        
        <div class="table-container">
            <table class="tms-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vehicle Details</th>
                        <th>Service Date</th>
                        <th>Description</th>
                        <th>Cost (GHS)</th>
                        <th>Next Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($maintenance_logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">No maintenance logs registered in history.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($maintenance_logs as $log): ?>
                            <tr>
                                <td style="font-weight: 700; color: white;">#<?php echo $log['id']; ?></td>
                                <td>
                                    <div style="color: white; font-weight: 600;"><?php echo $log['vehicle_name']; ?></div>
                                    <div style="font-size: 0.75rem; font-family: monospace; color: var(--text-muted);"><?php echo $log['license_plate']; ?></div>
                                </td>
                                <td><?php echo date('d M Y', strtotime($log['maintenance_date'])); ?></td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary); max-width: 200px; line-height: 1.3;" title="<?php echo $log['description']; ?>">
                                        <?php echo $log['description']; ?>
                                    </div>
                                </td>
                                <td style="font-weight: 600; color: white;"><?php echo format_currency($log['cost']); ?></td>
                                <td>
                                    <?php echo $log['next_due_date'] ? date('d M Y', strtotime($log['next_due_date'])) : '<span class="text-muted">None Set</span>'; ?>
                                </td>
                                <td>
                                    <span class="badge status-<?php echo $log['status']; ?>">
                                        <?php echo str_replace('_', ' ', $log['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
