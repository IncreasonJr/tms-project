<?php
// functions.php - Helper Functions for database queries and session fallbacks
// Conforms to spec.pdf requirements

require_once __DIR__ . '/config.php';

// Sanitize user inputs
function sanitize_input($data) {
    global $conn, $db_connected;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    if ($db_connected && $conn) {
        $data = mysqli_real_escape_string($conn, $data);
    }
    return $data;
}

// Check if user is logged in
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Generate unique trip code
function generate_trip_code() {
    return 'TRP-' . strtoupper(bin2hex(random_bytes(3)));
}

// Format currency
function format_currency($val) {
    return 'GH₵ ' . number_format($val, 2);
}

// Get all vehicles
function get_all_vehicles() {
    global $conn, $db_connected;
    if ($db_connected) {
        $sql = "SELECT * FROM vehicles ORDER BY vehicle_name ASC";
        $result = mysqli_query($conn, $sql);
        $vehicles = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $vehicles[] = $row;
            }
        }
        return $vehicles;
    } else {
        return array_values($_SESSION['mock_vehicles']);
    }
}

// Get vehicle name by ID
function get_vehicle_name($vehicle_id) {
    global $conn, $db_connected;
    if (!$vehicle_id) return 'Not Assigned';
    
    if ($db_connected) {
        $sql = "SELECT vehicle_name, license_plate FROM vehicles WHERE id = " . intval($vehicle_id);
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['vehicle_name'] . ' (' . $row['license_plate'] . ')';
        }
    } else {
        if (isset($_SESSION['mock_vehicles'][$vehicle_id])) {
            $v = $_SESSION['mock_vehicles'][$vehicle_id];
            return $v['vehicle_name'] . ' (' . $v['license_plate'] . ')';
        }
    }
    return 'Unknown Vehicle';
}

// Get all drivers
function get_all_drivers() {
    global $conn, $db_connected;
    if ($db_connected) {
        $sql = "SELECT * FROM drivers ORDER BY fullname ASC";
        $result = mysqli_query($conn, $sql);
        $drivers = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $drivers[] = $row;
            }
        }
        return $drivers;
    } else {
        return array_values($_SESSION['mock_drivers']);
    }
}

// Get driver name by ID
function get_driver_name($driver_id) {
    global $conn, $db_connected;
    if (!$driver_id) return 'Not Assigned';
    
    if ($db_connected) {
        $sql = "SELECT fullname FROM drivers WHERE id = " . intval($driver_id);
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['fullname'];
        }
    } else {
        if (isset($_SESSION['mock_drivers'][$driver_id])) {
            return $_SESSION['mock_drivers'][$driver_id]['fullname'];
        }
    }
    return 'Unknown Driver';
}

// Get all trips
function get_all_trips() {
    global $conn, $db_connected;
    if ($db_connected) {
        $sql = "SELECT t.*, a.fullname as creator_name FROM trips t 
                JOIN admins a ON t.created_by = a.id 
                ORDER BY t.trip_date DESC, t.id DESC";
        $result = mysqli_query($conn, $sql);
        $trips = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $trips[] = $row;
            }
        }
        return $trips;
    } else {
        $trips = [];
        foreach ($_SESSION['mock_trips'] as $trip) {
            $trip['creator_name'] = isset($_SESSION['mock_admin_name']) ? $_SESSION['mock_admin_name'] : 'System Dispatcher';
            $trips[] = $trip;
        }
        // sort by date descending
        usort($trips, function($a, $b) {
            return strcmp($b['trip_date'], $a['trip_date']);
        });
        return $trips;
    }
}

// Get trip by ID
function get_trip_by_id($id) {
    global $conn, $db_connected;
    $id = intval($id);
    if ($db_connected) {
        $sql = "SELECT * FROM trips WHERE id = $id";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    } else {
        return isset($_SESSION['mock_trips'][$id]) ? $_SESSION['mock_trips'][$id] : null;
    }
}

// Insert trip
function insert_trip($trip_code, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $created_by) {
    global $conn, $db_connected;
    
    $trip_date = sanitize_input($trip_date);
    $origin = sanitize_input($origin);
    $destination = sanitize_input($destination);
    $purpose = sanitize_input($purpose);
    $vehicle_val = $vehicle_id ? intval($vehicle_id) : 'NULL';
    $driver_val = $driver_id ? intval($driver_id) : 'NULL';
    $created_by = intval($created_by);
    
    if ($db_connected) {
        $sql = "INSERT INTO trips (trip_code, trip_date, origin, destination, purpose, vehicle_id, driver_id, status, created_by) 
                VALUES ('$trip_code', '$trip_date', '$origin', '$destination', '$purpose', $vehicle_val, $driver_val, 'pending', $created_by)";
        
        $success = mysqli_query($conn, $sql);
        if ($success) {
            // Update vehicle and driver status if assigned
            if ($vehicle_id) {
                mysqli_query($conn, "UPDATE vehicles SET status = 'assigned' WHERE id = $vehicle_id");
            }
            if ($driver_id) {
                mysqli_query($conn, "UPDATE drivers SET status = 'on_trip' WHERE id = $driver_id");
            }
            return true;
        }
        return false;
    } else {
        $new_id = empty($_SESSION['mock_trips']) ? 1 : max(array_keys($_SESSION['mock_trips'])) + 1;
        $_SESSION['mock_trips'][$new_id] = [
            'id' => $new_id,
            'trip_code' => $trip_code,
            'trip_date' => $trip_date,
            'origin' => $origin,
            'destination' => $destination,
            'purpose' => $purpose,
            'vehicle_id' => $vehicle_id ? intval($vehicle_id) : null,
            'driver_id' => $driver_id ? intval($driver_id) : null,
            'status' => 'pending',
            'created_by' => $created_by
        ];
        
        // Update vehicle and driver status
        if ($vehicle_id) {
            $_SESSION['mock_vehicles'][$vehicle_id]['status'] = 'assigned';
        }
        if ($driver_id) {
            $_SESSION['mock_drivers'][$driver_id]['status'] = 'on_trip';
        }
        return true;
    }
}

// Update trip status and details
function update_trip($id, $trip_date, $origin, $destination, $purpose, $vehicle_id, $driver_id, $status) {
    global $conn, $db_connected;
    $id = intval($id);
    $trip_date = sanitize_input($trip_date);
    $origin = sanitize_input($origin);
    $destination = sanitize_input($destination);
    $purpose = sanitize_input($purpose);
    $status = sanitize_input($status);
    $vehicle_val = $vehicle_id ? intval($vehicle_id) : 'NULL';
    $driver_val = $driver_id ? intval($driver_id) : 'NULL';
    
    // Get old trip for status updates logic
    $old_trip = get_trip_by_id($id);
    if (!$old_trip) return false;
    
    if ($db_connected) {
        $sql = "UPDATE trips SET 
                trip_date = '$trip_date', 
                origin = '$origin', 
                destination = '$destination', 
                purpose = '$purpose', 
                vehicle_id = $vehicle_val, 
                driver_id = $driver_val, 
                status = '$status' 
                WHERE id = $id";
        
        $success = mysqli_query($conn, $sql);
        if ($success) {
            // Revert old vehicle/driver status if changed or completed/cancelled
            if ($old_trip['vehicle_id'] && $old_trip['vehicle_id'] != $vehicle_id) {
                mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = " . $old_trip['vehicle_id']);
            }
            if ($old_trip['driver_id'] && $old_trip['driver_id'] != $driver_id) {
                mysqli_query($conn, "UPDATE drivers SET status = 'available' WHERE id = " . $old_trip['driver_id']);
            }
            
            // Set new statuses based on trip status
            if ($status == 'completed' || $status == 'cancelled') {
                if ($vehicle_id) mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = $vehicle_id");
                if ($driver_id) mysqli_query($conn, "UPDATE drivers SET status = 'available' WHERE id = $driver_id");
            } else {
                if ($vehicle_id) mysqli_query($conn, "UPDATE vehicles SET status = 'assigned' WHERE id = $vehicle_id");
                if ($driver_id) mysqli_query($conn, "UPDATE drivers SET status = 'on_trip' WHERE id = $driver_id");
            }
            return true;
        }
        return false;
    } else {
        // Revert old statuses
        $old_v = $old_trip['vehicle_id'];
        $old_d = $old_trip['driver_id'];
        if ($old_v && $old_v != $vehicle_id) {
            $_SESSION['mock_vehicles'][$old_v]['status'] = 'available';
        }
        if ($old_d && $old_d != $driver_id) {
            $_SESSION['mock_drivers'][$old_d]['status'] = 'available';
        }
        
        $_SESSION['mock_trips'][$id] = [
            'id' => $id,
            'trip_code' => $old_trip['trip_code'],
            'trip_date' => $trip_date,
            'origin' => $origin,
            'destination' => $destination,
            'purpose' => $purpose,
            'vehicle_id' => $vehicle_id ? intval($vehicle_id) : null,
            'driver_id' => $driver_id ? intval($driver_id) : null,
            'status' => $status,
            'created_by' => $old_trip['created_by']
        ];
        
        // Update vehicle and driver status based on new trip status
        if ($status == 'completed' || $status == 'cancelled') {
            if ($vehicle_id) $_SESSION['mock_vehicles'][$vehicle_id]['status'] = 'available';
            if ($driver_id) $_SESSION['mock_drivers'][$driver_id]['status'] = 'available';
        } else {
            if ($vehicle_id) $_SESSION['mock_vehicles'][$vehicle_id]['status'] = 'assigned';
            if ($driver_id) $_SESSION['mock_drivers'][$driver_id]['status'] = 'on_trip';
        }
        return true;
    }
}

// Delete / Cancel trip
function delete_trip($id) {
    global $conn, $db_connected;
    $id = intval($id);
    $trip = get_trip_by_id($id);
    if (!$trip) return false;
    
    if ($db_connected) {
        // Free vehicle and driver status
        if ($trip['vehicle_id']) {
            mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = " . $trip['vehicle_id']);
        }
        if ($trip['driver_id']) {
            mysqli_query($conn, "UPDATE drivers SET status = 'available' WHERE id = " . $trip['driver_id']);
        }
        
        $sql = "DELETE FROM trips WHERE id = $id";
        return mysqli_query($conn, $sql);
    } else {
        // Free vehicle and driver status
        $v_id = $trip['vehicle_id'];
        $d_id = $trip['driver_id'];
        if ($v_id) $_SESSION['mock_vehicles'][$v_id]['status'] = 'available';
        if ($d_id) $_SESSION['mock_drivers'][$d_id]['status'] = 'available';
        
        unset($_SESSION['mock_trips'][$id]);
        return true;
    }
}

// Get all maintenance records
function get_all_maintenance() {
    global $conn, $db_connected;
    if ($db_connected) {
        $sql = "SELECT m.*, v.vehicle_name, v.license_plate FROM maintenance m 
                JOIN vehicles v ON m.vehicle_id = v.id 
                ORDER BY m.maintenance_date DESC, m.id DESC";
        $result = mysqli_query($conn, $sql);
        $maintenance = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $maintenance[] = $row;
            }
        }
        return $maintenance;
    } else {
        $logs = [];
        foreach ($_SESSION['mock_maintenance'] as $log) {
            $v_id = $log['vehicle_id'];
            $log['vehicle_name'] = isset($_SESSION['mock_vehicles'][$v_id]) ? $_SESSION['mock_vehicles'][$v_id]['vehicle_name'] : 'Unknown Vehicle';
            $log['license_plate'] = isset($_SESSION['mock_vehicles'][$v_id]) ? $_SESSION['mock_vehicles'][$v_id]['license_plate'] : 'Unknown';
            $logs[] = $log;
        }
        // sort by date descending
        usort($logs, function($a, $b) {
            return strcmp($b['maintenance_date'], $a['maintenance_date']);
        });
        return $logs;
    }
}

// Insert maintenance record
function insert_maintenance($vehicle_id, $maintenance_date, $description, $cost, $next_due_date, $status) {
    global $conn, $db_connected;
    $vehicle_id = intval($vehicle_id);
    $maintenance_date = sanitize_input($maintenance_date);
    $description = sanitize_input($description);
    $cost = floatval($cost);
    $next_due_date_val = $next_due_date ? "'" . sanitize_input($next_due_date) . "'" : "NULL";
    $status = sanitize_input($status);
    
    if ($db_connected) {
        $sql = "INSERT INTO maintenance (vehicle_id, maintenance_date, description, cost, next_due_date, status) 
                VALUES ($vehicle_id, '$maintenance_date', '$description', $cost, $next_due_date_val, '$status')";
        $success = mysqli_query($conn, $sql);
        if ($success) {
            // Update vehicle status based on maintenance status
            if ($status == 'in_progress') {
                mysqli_query($conn, "UPDATE vehicles SET status = 'under_maintenance' WHERE id = $vehicle_id");
            } else if ($status == 'completed') {
                mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = $vehicle_id");
            }
            return true;
        }
        return false;
    } else {
        $new_id = empty($_SESSION['mock_maintenance']) ? 1 : max(array_keys($_SESSION['mock_maintenance'])) + 1;
        $_SESSION['mock_maintenance'][$new_id] = [
            'id' => $new_id,
            'vehicle_id' => $vehicle_id,
            'maintenance_date' => $maintenance_date,
            'description' => $description,
            'cost' => $cost,
            'next_due_date' => $next_due_date ? $next_due_date : null,
            'status' => $status
        ];
        
        // Update vehicle status
        if ($status == 'in_progress') {
            $_SESSION['mock_vehicles'][$vehicle_id]['status'] = 'under_maintenance';
        } else if ($status == 'completed') {
            $_SESSION['mock_vehicles'][$vehicle_id]['status'] = 'available';
        }
        return true;
    }
}

// Get trip by Code (case-insensitive)
function get_trip_by_code($code) {
    global $conn, $db_connected;
    $code = sanitize_input(trim($code));
    if ($db_connected) {
        $sql = "SELECT * FROM trips WHERE LOWER(trip_code) = LOWER('$code')";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    } else {
        foreach ($_SESSION['mock_trips'] as $trip) {
            if (strtolower($trip['trip_code']) === strtolower($code)) {
                return $trip;
            }
        }
        return null;
    }
}

// Get all tracking updates for a trip (newest first)
function get_tracking_updates($trip_id) {
    global $conn, $db_connected;
    $trip_id = intval($trip_id);
    if ($db_connected) {
        $sql = "SELECT * FROM tracking_updates WHERE trip_id = $trip_id ORDER BY created_at DESC, id DESC";
        $result = mysqli_query($conn, $sql);
        $updates = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $updates[] = $row;
            }
        }
        return $updates;
    } else {
        $updates = [];
        if (isset($_SESSION['mock_tracking_updates'])) {
            foreach ($_SESSION['mock_tracking_updates'] as $update) {
                if (intval($update['trip_id']) === $trip_id) {
                    $updates[] = $update;
                }
            }
        }
        // sort by created_at descending
        usort($updates, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });
        return $updates;
    }
}

// Insert new tracking update and synchronize main trip status
function insert_tracking_update($trip_id, $status, $location, $description) {
    global $conn, $db_connected;
    $trip_id = intval($trip_id);
    $status = sanitize_input($status);
    $location = sanitize_input($location);
    $description = sanitize_input($description);
    
    // Map tracking status to main trip status
    $mapped_trip_status = 'pending';
    if ($status === 'Order Received') {
        $mapped_trip_status = 'pending';
    } else if ($status === 'Vehicle Assigned') {
        $mapped_trip_status = 'approved';
    } else if ($status === 'Delivered') {
        $mapped_trip_status = 'completed';
    } else {
        $mapped_trip_status = 'in_transit'; // Departing, In Transit, Arrived
    }
    
    if ($db_connected) {
        $sql = "INSERT INTO tracking_updates (trip_id, status, location, description) 
                VALUES ($trip_id, '$status', '$location', '$description')";
        $success = mysqli_query($conn, $sql);
        if ($success) {
            // Sync main trip status
            mysqli_query($conn, "UPDATE trips SET status = '$mapped_trip_status' WHERE id = $trip_id");
            
            // If Delivered, release vehicle and driver status to available
            if ($status === 'Delivered') {
                $trip = get_trip_by_id($trip_id);
                if ($trip) {
                    if ($trip['vehicle_id']) {
                        mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = " . intval($trip['vehicle_id']));
                    }
                    if ($trip['driver_id']) {
                        mysqli_query($conn, "UPDATE drivers SET status = 'available' WHERE id = " . intval($trip['driver_id']));
                    }
                }
            }
            return true;
        }
        return false;
    } else {
        $new_id = empty($_SESSION['mock_tracking_updates']) ? 1 : max(array_keys($_SESSION['mock_tracking_updates'])) + 1;
        $_SESSION['mock_tracking_updates'][$new_id] = [
            'id' => $new_id,
            'trip_id' => $trip_id,
            'status' => $status,
            'location' => $location,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Sync main trip status
        if (isset($_SESSION['mock_trips'][$trip_id])) {
            $_SESSION['mock_trips'][$trip_id]['status'] = $mapped_trip_status;
        }
        
        // If Delivered, release vehicle and driver status
        if ($status === 'Delivered') {
            $trip = isset($_SESSION['mock_trips'][$trip_id]) ? $_SESSION['mock_trips'][$trip_id] : null;
            if ($trip) {
                $vehicle_id = $trip['vehicle_id'];
                $driver_id = $trip['driver_id'];
                if ($vehicle_id && isset($_SESSION['mock_vehicles'][$vehicle_id])) {
                    $_SESSION['mock_vehicles'][$vehicle_id]['status'] = 'available';
                }
                if ($driver_id && isset($_SESSION['mock_drivers'][$driver_id])) {
                    $_SESSION['mock_drivers'][$driver_id]['status'] = 'available';
                }
            }
        }
        return true;
    }
}
?>
