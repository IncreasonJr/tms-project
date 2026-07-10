-- Transport Management System (TMS) Database Schema
-- Conforms to spec.pdf requirements

CREATE DATABASE IF NOT EXISTS tms_db;
USE tms_db;

-- --------------------------------------------------------
-- Table: admins
-- --------------------------------------------------------
DROP TABLE IF EXISTS tracking_updates;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS maintenance;
DROP TABLE IF EXISTS drivers;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS admins;

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: vehicles
-- --------------------------------------------------------
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_name VARCHAR(100) NOT NULL,
    license_plate VARCHAR(50) NOT NULL UNIQUE,
    model VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    status ENUM('available', 'assigned', 'under_maintenance', 'out_of_service') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: drivers
-- --------------------------------------------------------
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    address TEXT NULL,
    status ENUM('available', 'on_trip', 'unavailable') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: trips
-- --------------------------------------------------------
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_code VARCHAR(50) NOT NULL UNIQUE,
    trip_date DATE NOT NULL,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    purpose TEXT NULL,
    vehicle_id INT NULL,
    driver_id INT NULL,
    status ENUM('pending', 'approved', 'in_transit', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: maintenance
-- --------------------------------------------------------
CREATE TABLE maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_date DATE NOT NULL,
    description TEXT NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    next_due_date DATE NULL,
    status ENUM('scheduled', 'in_progress', 'completed') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: tracking_updates
-- --------------------------------------------------------
CREATE TABLE tracking_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------
-- Seed Data
-- --------------------------------------------------------

-- Seed admins
-- Password bcrypt hash for 'fleet123'
INSERT INTO admins (id, fullname, email, password, role) VALUES
(1, 'System Administrator', 'admin@fleet.com', '$2y$10$L19H/vD3eJgY.5dD3D2r4evqI4fT2r2HhXG4o1q2B3G4T2eE1dE2e', 'super_admin'),
(2, 'Operations Dispatcher', 'dispatcher@fleet.com', '$2y$10$L19H/vD3eJgY.5dD3D2r4evqI4fT2r2HhXG4o1q2B3G4T2eE1dE2e', 'manager'),
(3, 'Yard Officer', 'yard@fleet.com', '$2y$10$L19H/vD3eJgY.5dD3D2r4evqI4fT2r2HhXG4o1q2B3G4T2eE1dE2e', 'staff');

-- Seed vehicles (Ghana localized)
INSERT INTO vehicles (id, vehicle_name, license_plate, model, capacity, status) VALUES
(1, 'DAF XF Heavy Hauler', 'GW-2904-22', 'DAF XF 105', 40000, 'available'),
(2, 'Mercedes-Benz Actros', 'GT-8412-25', 'Actros 2644', 35000, 'assigned'),
(3, 'Toyota Dyna Box Truck', 'AS-1024-23', 'Dyna 400', 8000, 'available'),
(4, 'Hyundai H100 Cargo Van', 'ER-4521-24', 'H100 Van', 3500, 'under_maintenance'),
(5, 'MAN TGX Carrier', 'GT-1102-23', 'TGX 26.440', 38000, 'out_of_service'),
(6, 'Kia Bongo Delivery Truck', 'AS-9921-22', 'Bongo III', 4500, 'available');

-- Seed drivers (Ghana localized)
INSERT INTO drivers (id, fullname, license_number, phone, email, address, status) VALUES
(1, 'Kwame Mensah', 'DL-GH9021482', '+233 24 123 4567', 'kwame.mensah@fleet.com', 'H/No 12, Kanda High Street, Accra', 'available'),
(2, 'Kojo Boateng', 'DL-GH8410294', '+233 20 234 5678', 'kojo.boateng@fleet.com', 'Block G, Adum, Kumasi', 'on_trip'),
(3, 'Kofi Hanson', 'DL-GH1029481', '+233 27 345 6789', 'kofi.hanson@fleet.com', 'Ashaley Botwe, Accra', 'available'),
(4, 'Yaw Addo', 'DL-GH4520194', '+233 55 456 7890', 'yaw.addo@fleet.com', 'Zongo Lane, Koforidua', 'unavailable'),
(5, 'Amma Osei', 'DL-GH1109283', '+233 24 567 8901', 'amma.osei@fleet.com', 'P.O. Box 45, Tamale', 'available'),
(6, 'Abena Appiah', 'DL-GH9920194', '+233 26 678 9012', 'abena.appiah@fleet.com', 'New Takoradi, Takoradi', 'available');

-- Seed trips
INSERT INTO trips (id, trip_code, trip_date, origin, destination, purpose, vehicle_id, driver_id, status, created_by) VALUES
(1, 'TRP-987214', '2026-07-02', 'Accra, Greater Accra', 'Tamale, Northern', 'Industrial machinery delivery to northern terminal', 2, 2, 'approved', 2),
(2, 'TRP-112045', '2026-06-28', 'Tema, Greater Accra', 'Kumasi, Ashanti', 'General harbor cargo transfer', 3, 3, 'completed', 2),
(3, 'TRP-301149', '2026-07-05', 'Kumasi, Ashanti', 'Sunyani, Bono', 'Seed and agricultural supply transit', 6, 1, 'pending', 3);

-- Seed maintenance logs
INSERT INTO maintenance (id, vehicle_id, maintenance_date, description, cost, next_due_date, status) VALUES
(1, 4, '2026-06-24', 'Radiator flushing and replacement of water pump due to leak.', 850.00, '2026-09-24', 'completed'),
(2, 1, '2026-07-02', 'Scheduled engine oil replacement and fuel filter checks.', 1200.00, '2026-10-02', 'scheduled'),
(3, 5, '2026-06-15', 'Full suspension and brake pads overhaul after long haul.', 3500.00, NULL, 'in_progress');

-- Seed tracking_updates
INSERT INTO tracking_updates (id, trip_id, status, location, description, created_at) VALUES
(1, 1, 'Order Received', 'Accra Depot', 'Trip order logged in system, awaiting dispatch.', '2026-07-02 08:00:00'),
(2, 1, 'Vehicle Assigned', 'Accra Central', 'Mercedes-Benz Actros (GT-8412-25) and driver assigned.', '2026-07-02 10:30:00'),
(3, 2, 'Order Received', 'Tema Harbor Yard', 'Port customs clearance obtained.', '2026-06-28 07:00:00'),
(4, 2, 'Vehicle Assigned', 'Tema Port', 'Toyota Dyna Box Truck (AS-1024-23) assigned.', '2026-06-28 08:30:00'),
(5, 2, 'Departing', 'Tema Highway', 'Left Tema Port area, heading towards Kumasi via N6.', '2026-06-28 09:15:00'),
(6, 2, 'In Transit', 'Koforidua Bypass', 'En route. Driver bypassed Eastern corridor checkpoint.', '2026-06-28 12:00:00'),
(7, 2, 'Arrived', 'Kumasi Depot', 'Parked at receiving bay, awaiting offload.', '2026-06-28 15:30:00'),
(8, 2, 'Delivered', 'Kumasi Yard', 'Cargo unloaded, inspected, and signed off by client (K. Osei).', '2026-06-28 16:00:00'),
(9, 3, 'Order Received', 'Kumasi Depot', 'Trip requested, awaiting vehicle loading.', '2026-07-05 09:00:00');
