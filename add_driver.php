<?php
/**
 * Add New Driver
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';

// 2. Check if the user is logged in (if not, redirect to login.php)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Enforce admin-only access check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// 4. Process form submission (POST method)
if (isset($_POST['add_driver'])) {
    
    // Validate CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Validate and sanitize input fields
        $full_name = isset($_POST['full_name']) ? sanitize_input(trim($_POST['full_name'])) : '';
        $license_number = isset($_POST['license_number']) ? sanitize_input(trim($_POST['license_number'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_input(trim($_POST['phone'])) : '';
        $email = isset($_POST['email']) ? sanitize_input(trim($_POST['email'])) : '';
        $address = isset($_POST['address']) ? sanitize_input(trim($_POST['address'])) : '';
        $status = isset($_POST['status']) ? sanitize_input(trim($_POST['status'])) : 'available';

        // Validation checks
        if (empty($full_name) || empty($license_number)) {
            $error = "Full Name and License Number are required fields.";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address format.";
        } else {
            if ($db_connected && $conn) {
                // Use mysqli_prepare() prepared statements to prevent SQL injection
                $query = "INSERT INTO drivers (full_name, license_number, phone, email, address, status) VALUES (?, ?, ?, ?, ?, ?)";
                
                if ($stmt = mysqli_prepare($conn, $query)) {
                    // Bind parameters
                    mysqli_stmt_bind_param($stmt, "ssssss", $full_name, $license_number, $phone, $email, $address, $status);
                    
                    // Execute query
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Driver added successfully!";
                        header("Refresh: 1; url=drivers.php");
                    } else {
                        $error = "Error adding driver. The license number may already be registered.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = "Database preparation error. Please try again.";
                }
            } else {
                // Simulation Mode
                $exists = false;
                if (isset($_SESSION['mock_drivers'])) {
                    foreach ($_SESSION['mock_drivers'] as $drv) {
                        if ($drv['license_number'] === $license_number) {
                            $exists = true;
                            break;
                        }
                    }
                }
                if ($exists) {
                    $error = "Error adding driver. The license number may already be registered.";
                } else {
                    $new_id = empty($_SESSION['mock_drivers']) ? 1 : max(array_keys($_SESSION['mock_drivers'])) + 1;
                    $_SESSION['mock_drivers'][$new_id] = [
                        'id' => $new_id,
                        'fullname' => $full_name,
                        'license_number' => $license_number,
                        'phone' => $phone,
                        'email' => $email,
                        'address' => $address,
                        'status' => $status
                    ];
                    
                    // Add driver account so they can log in
                    if (!empty($email)) {
                        create_mock_customer_account($full_name, strtolower(explode('@', $email)[0]), $email, 'fleet123');
                        $_SESSION['mock_user_accounts'][$email]['role'] = 'driver';
                        $_SESSION['mock_user_accounts'][$email]['id'] = $new_id;
                    }
                    
                    $success = "Driver added successfully (Simulation Mode)!";
                    header("Refresh: 1; url=drivers.php");
                }
            }
        }
    }
}

$page_title = "Add Driver";

// 5. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Layout -->
<div class="page-header">
    <h2 class="page-title">Add Driver</h2>
    <a href="drivers.php" class="btn btn-secondary">
        Back to List
    </a>
</div>

<!-- Add Form Card -->
<div class="content-card" style="max-width: 600px; margin: 0 auto;">
    
    <!-- User feedback messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?> Redirecting to driver directory...
        </div>
    <?php endif; ?>

    <!-- Form with required fields -->
    <form action="add_driver.php" method="POST" autocomplete="off">
        
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Full Name (text input, required) -->
        <div class="form-group">
            <label for="full_name" class="form-label">Full Name *</label>
            <input 
                type="text" 
                id="full_name" 
                name="full_name" 
                class="form-control" 
                placeholder="e.g. John Doe" 
                required
                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name'], ENT_QUOTES, 'UTF-8') : ''; ?>"
            >
        </div>

        <!-- License Number (text input, required) -->
        <div class="form-group">
            <label for="license_number" class="form-label">License Number *</label>
            <input 
                type="text" 
                id="license_number" 
                name="license_number" 
                class="form-control" 
                placeholder="e.g. DL-123456789" 
                required
                value="<?php echo isset($_POST['license_number']) ? htmlspecialchars($_POST['license_number'], ENT_QUOTES, 'UTF-8') : ''; ?>"
            >
        </div>

        <div class="form-row">
            <!-- Phone (text input) -->
            <div class="form-group">
                <label for="phone" class="form-label">Phone</label>
                <input 
                    type="text" 
                    id="phone" 
                    name="phone" 
                    class="form-control" 
                    placeholder="e.g. +1 555-0199"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>

            <!-- Email (email input) -->
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="e.g. john.doe@example.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                >
            </div>
        </div>

        <!-- Address (textarea) -->
        <div class="form-group">
            <label for="address" class="form-label">Address</label>
            <textarea 
                id="address" 
                name="address" 
                class="form-control" 
                rows="3" 
                placeholder="e.g. 123 Main St, New York, NY"
            ><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
        </div>

        <!-- Status (dropdown select) -->
        <div class="form-group">
            <label for="status" class="form-label">Initial Status</label>
            <select id="status" name="status" class="form-control">
                <option value="available" <?php echo (isset($_POST['status']) && $_POST['status'] === 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="on_trip" <?php echo (isset($_POST['status']) && $_POST['status'] === 'on_trip') ? 'selected' : ''; ?>>On Trip</option>
                <option value="unavailable" <?php echo (isset($_POST['status']) && $_POST['status'] === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="add_driver" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
            Save Driver Record
        </button>

    </form>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
