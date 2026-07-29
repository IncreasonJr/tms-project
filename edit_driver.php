<?php
/**
 * Edit Existing Driver
 * Transport Management System (TMS)
 */

// 1. Include the configuration file at the top
require_once 'includes/config.php';
require_once 'includes/functions.php';

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

// 3. Get the driver ID from the URL parameter (GET method)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 8. If driver ID is invalid, redirect to drivers.php
if ($id <= 0) {
    header("Location: drivers.php");
    exit();
}

$error = '';
$success = '';

// 7. Process form submission (POST method)
if (isset($_POST['edit_driver'])) {
    
    // Validate CSRF token
    $csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validateCSRFToken($csrf_token)) {
        $error = "Security token validation failed. Please try again.";
    } else {
        // Validate and sanitize inputs
        $full_name = isset($_POST['full_name']) ? sanitize_input(trim($_POST['full_name'])) : '';
        $license_number = isset($_POST['license_number']) ? sanitize_input(trim($_POST['license_number'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_input(trim($_POST['phone'])) : '';
        $email = isset($_POST['email']) ? sanitize_input(trim($_POST['email'])) : '';
        $address = isset($_POST['address']) ? sanitize_input(trim($_POST['address'])) : '';
        $status = isset($_POST['status']) ? sanitize_input(trim($_POST['status'])) : 'available';

        if (empty($full_name) || empty($license_number)) {
            $error = "Full Name and License Number are required fields.";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address format.";
        } else {
            if ($db_connected && $conn) {
                // Update the driver in the drivers table using prepared statements
                $update_query = "UPDATE drivers SET full_name = ?, license_number = ?, phone = ?, email = ?, address = ?, status = ? WHERE id = ?";
                
                if ($stmt = mysqli_prepare($conn, $update_query)) {
                    // Bind parameters
                    mysqli_stmt_bind_param($stmt, "ssssssi", $full_name, $license_number, $phone, $email, $address, $status, $id);
                    
                    // Execute update
                    if (mysqli_stmt_execute($stmt)) {
                        $success = "Driver updated successfully!";
                        header("Refresh: 1; url=drivers.php");
                    } else {
                        $error = "Error updating driver. The license number might already be registered by another driver.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $error = "Database preparation error. Please try again.";
                }
            } else {
                // Simulation Mode update
                $exists = false;
                if (isset($_SESSION['mock_drivers'])) {
                    foreach ($_SESSION['mock_drivers'] as $did => $drv) {
                        if ($drv['license_number'] === $license_number && intval($did) !== $id) {
                            $exists = true;
                            break;
                        }
                    }
                }
                if ($exists) {
                    $error = "Error updating driver. The license number might already be registered by another driver.";
                } else {
                    if (isset($_SESSION['mock_drivers'][$id])) {
                        $_SESSION['mock_drivers'][$id]['fullname'] = $full_name;
                        $_SESSION['mock_drivers'][$id]['license_number'] = $license_number;
                        $_SESSION['mock_drivers'][$id]['phone'] = $phone;
                        $_SESSION['mock_drivers'][$id]['email'] = $email;
                        $_SESSION['mock_drivers'][$id]['address'] = $address;
                        $_SESSION['mock_drivers'][$id]['status'] = $status;
                        
                        // Sync username/email in mock user accounts if driver email changed or exists
                        if (!empty($email)) {
                            // Find the user account associated with this driver's old email or update it
                            foreach ($_SESSION['mock_user_accounts'] as $em => $acc) {
                                if ($acc['role'] === 'driver' && intval($acc['id']) === $id) {
                                    unset($_SESSION['mock_user_accounts'][$em]);
                                }
                            }
                            $_SESSION['mock_user_accounts'][$email] = [
                                'id' => $id,
                                'username' => strtolower(explode('@', $email)[0]),
                                'fullname' => $full_name,
                                'email' => $email,
                                'role' => 'driver',
                                'password' => 'fleet123'
                            ];
                        }
                        
                        $success = "Driver updated successfully (Simulation Mode)!";
                        header("Refresh: 1; url=drivers.php");
                    } else {
                        $error = "Driver not found in simulation mode.";
                    }
                }
            }
        }
    }
}

// 4. Query the database to fetch driver data for the given ID
$driver = null;
if ($db_connected && $conn) {
    $fetch_query = "SELECT * FROM drivers WHERE id = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $fetch_query)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $fetch_result = mysqli_stmt_get_result($stmt);
            $driver = mysqli_fetch_assoc($fetch_result);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Simulation Mode fetch
    if (isset($_SESSION['mock_drivers'][$id])) {
        $m_d = $_SESSION['mock_drivers'][$id];
        $driver = [
            'id' => $m_d['id'],
            'full_name' => $m_d['fullname'],
            'license_number' => $m_d['license_number'],
            'phone' => $m_d['phone'],
            'email' => $m_d['email'],
            'address' => $m_d['address'],
            'status' => $m_d['status']
        ];
    }
}

// 8. If driver not found, redirect to drivers.php
if (!$driver) {
    header("Location: drivers.php");
    exit();
}

$page_title = "Edit Driver";

// 9. Use the dashboard layout for consistency (header includes styling and navbar)
require_once 'includes/header.php';
?>

<!-- Header Layout -->
<div class="page-header">
    <h2 class="page-title">Edit Driver</h2>
    <a href="drivers.php" class="btn btn-secondary">
        Back to List
    </a>
</div>

<!-- Edit Form Card -->
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

    <!-- Form with the same fields as add_driver.php -->
    <form action="edit_driver.php?id=<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" method="POST" autocomplete="off">
        
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Pre-fill the form with existing driver data using htmlspecialchars() -->
        
        <!-- Full Name -->
        <div class="form-group">
            <label for="full_name" class="form-label">Full Name *</label>
            <input 
                type="text" 
                id="full_name" 
                name="full_name" 
                class="form-control" 
                required
                value="<?php echo htmlspecialchars(isset($_POST['full_name']) ? $_POST['full_name'] : $driver['full_name'], ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <!-- License Number -->
        <div class="form-group">
            <label for="license_number" class="form-label">License Number *</label>
            <input 
                type="text" 
                id="license_number" 
                name="license_number" 
                class="form-control" 
                required
                value="<?php echo htmlspecialchars(isset($_POST['license_number']) ? $_POST['license_number'] : $driver['license_number'], ENT_QUOTES, 'UTF-8'); ?>"
            >
        </div>

        <div class="form-row">
            <!-- Phone -->
            <div class="form-group">
                <label for="phone" class="form-label">Phone</label>
                <input 
                    type="text" 
                    id="phone" 
                    name="phone" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : $driver['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : $driver['email'], ENT_QUOTES, 'UTF-8'); ?>"
                >
            </div>
        </div>

        <!-- Address -->
        <div class="form-group">
            <label for="address" class="form-label">Address</label>
            <textarea 
                id="address" 
                name="address" 
                class="form-control" 
                rows="3"
            ><?php echo htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : $driver['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <!-- Status -->
        <div class="form-group">
            <label for="status" class="form-label">Status</label>
            <?php 
            $current_status = isset($_POST['status']) ? $_POST['status'] : $driver['status'];
            ?>
            <select id="status" name="status" class="form-control">
                <option value="available" <?php echo ($current_status === 'available') ? 'selected' : ''; ?>>Available</option>
                <option value="on_trip" <?php echo ($current_status === 'on_trip') ? 'selected' : ''; ?>>On Trip</option>
                <option value="unavailable" <?php echo ($current_status === 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
            </select>
        </div>

        <!-- Submit Button -->
        <button type="submit" name="edit_driver" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
            Update Driver Record
        </button>

    </form>
</div>

<?php
// Include footer layout
require_once 'includes/footer.php';
?>
