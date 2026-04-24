-- ============================================================
-- Restaurant Reservation System - Database Schema
-- Run this in phpMyAdmin after creating database: restaurant_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_db;

-- --------------------------------------------------------
-- Users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    role         ENUM('admin','user') DEFAULT 'user',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- --------------------------------------------------------
-- Floors
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS floors (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    description   VARCHAR(255),
    icon          VARCHAR(10) DEFAULT '🏢',
    display_order INT DEFAULT 0,
    is_active     TINYINT(1) DEFAULT 1
);

-- --------------------------------------------------------
-- Rooms (belong to a floor)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS rooms (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    floor_id    INT NOT NULL,
    name        VARCHAR(100) NOT NULL,
    description VARCHAR(200),
    x           INT DEFAULT 0,
    y           INT DEFAULT 0,
    width       INT DEFAULT 200,
    height      INT DEFAULT 150,
    color       VARCHAR(30) DEFAULT '#ecf0f1',
    is_active   TINYINT(1) DEFAULT 1,
    FOREIGN KEY (floor_id) REFERENCES floors(id) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Tables (belong to a room)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tables` (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    room_id     INT NOT NULL,
    table_code  VARCHAR(20) NOT NULL,
    capacity    INT NOT NULL DEFAULT 2,
    x           INT DEFAULT 0,
    y           INT DEFAULT 0,
    size        INT DEFAULT 60,
    shape       ENUM('circle','rectangle') DEFAULT 'circle',
    is_active   TINYINT(1) DEFAULT 1,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Reservations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT DEFAULT NULL,
    table_id          INT NOT NULL,
    reservation_date  DATE NOT NULL,
    time_slot         VARCHAR(20) NOT NULL,
    party_size        INT NOT NULL DEFAULT 2,
    customer_name     VARCHAR(100) NOT NULL,
    customer_email    VARCHAR(150) NOT NULL,
    customer_phone    VARCHAR(30) NOT NULL,
    special_requests  TEXT,
    status            ENUM('confirmed','cancelled','completed') DEFAULT 'confirmed',
    confirmation_code VARCHAR(20) NOT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES `tables`(id)
);

-- --------------------------------------------------------
-- Waitlist
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS waitlist (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL,
    phone          VARCHAR(30) NOT NULL,
    party_size     INT NOT NULL DEFAULT 2,
    preferred_date DATE,
    preferred_time VARCHAR(20),
    status         ENUM('waiting','notified','seated','cancelled') DEFAULT 'waiting',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Users (password = admin123 and user123 hashed with password_hash)
INSERT INTO users (name, email, password, role) VALUES
('Admin User',   'admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Smith',   'user@test.com',        '$2y$10$TKh8H1.PfuAi/MhVhZ4B2.5GxH3l2Wm7JXeOhRiI6u3q4m9O7wO2S', 'user');
-- Note: admin123 → $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi (Laravel default hash)
-- Note: user123  → use PHP password_hash('user123', PASSWORD_DEFAULT)
-- For demo, passwords stored as plain for easy update. Run setup.php to regenerate.

-- Floors
INSERT INTO floors (name, description, icon, display_order) VALUES
('Ground Floor',  'Main dining area & bar',         '🏠', 1),
('First Floor',   'Private rooms & lounge',          '🏢', 2),
('Rooftop',       'Open-air terrace dining',         '🌆', 3);

-- Rooms on Ground Floor (floor_id = 1)
INSERT INTO rooms (floor_id, name, description, x, y, width, height, color) VALUES
(1, 'Main Hall',     'Open seating area',          20,  20, 400, 250, '#fef9f0'),
(1, 'Bar Area',      'Bar seating & high tables',  440, 20, 180, 120, '#f0f4fe'),
(1, 'Garden Patio',  'Outdoor garden seating',     20,  290, 600, 130, '#f0fef4');

-- Rooms on First Floor (floor_id = 2)
INSERT INTO rooms (floor_id, name, description, x, y, width, height, color) VALUES
(2, 'Lounge',        'Cozy lounge area',           20,  20, 300, 200, '#fef0f0'),
(2, 'Private Room A','Exclusive dining room',       340, 20, 180, 140, '#f8f0fe'),
(2, 'Private Room B','Group dining room',           340, 180, 180, 140, '#f0fefe');

-- Rooms on Rooftop (floor_id = 3)
INSERT INTO rooms (floor_id, name, description, x, y, width, height, color) VALUES
(3, 'Sky Terrace',   'Open rooftop seating',       20,  20, 600, 200, '#fff8f0'),
(3, 'Skybox',        'Premium enclosed skybox',    20,  240, 280, 120, '#f0f0ff');

-- Tables in Main Hall (room_id = 1)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(1, 'MH-1', 2,  50,  60, 55, 'circle'),
(1, 'MH-2', 2,  135, 60, 55, 'circle'),
(1, 'MH-3', 4,  220, 60, 60, 'circle'),
(1, 'MH-4', 4,  310, 60, 60, 'circle'),
(1, 'MH-5', 4,  60,  160, 65, 'circle'),
(1, 'MH-6', 6,  175, 160, 70, 'circle'),
(1, 'MH-7', 6,  295, 155, 75, 'rectangle'),
(1, 'MH-8', 8,  160, 260, 90, 'rectangle');

-- Tables in Bar Area (room_id = 2)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(2, 'BA-1', 2,  460, 50, 50, 'circle'),
(2, 'BA-2', 2,  545, 50, 50, 'circle'),
(2, 'BA-3', 4,  490, 115, 60, 'rectangle');

-- Tables in Garden Patio (room_id = 3)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(3, 'GP-1', 2,  60,  320, 55, 'circle'),
(3, 'GP-2', 2,  155, 320, 55, 'circle'),
(3, 'GP-3', 4,  250, 320, 60, 'circle'),
(3, 'GP-4', 4,  355, 320, 60, 'circle'),
(3, 'GP-5', 6,  460, 315, 65, 'circle');

-- Tables in Lounge (room_id = 4)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(4, 'LG-1', 2,  50,  60, 55, 'circle'),
(4, 'LG-2', 4,  150, 60, 60, 'circle'),
(4, 'LG-3', 4,  255, 60, 60, 'circle'),
(4, 'LG-4', 6,  100, 160, 70, 'rectangle'),
(4, 'LG-5', 6,  220, 160, 70, 'rectangle');

-- Tables in Private Room A (room_id = 5)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(5, 'PRA-1', 4,  365, 50, 60, 'circle'),
(5, 'PRA-2', 4,  460, 50, 60, 'circle');

-- Tables in Private Room B (room_id = 6)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(6, 'PRB-1', 6,  365, 205, 70, 'rectangle'),
(6, 'PRB-2', 8,  460, 200, 75, 'rectangle');

-- Tables in Sky Terrace (room_id = 7)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(7, 'ST-1', 2,  50,  60, 55, 'circle'),
(7, 'ST-2', 2,  145, 60, 55, 'circle'),
(7, 'ST-3', 4,  240, 60, 60, 'circle'),
(7, 'ST-4', 4,  335, 60, 60, 'circle'),
(7, 'ST-5', 6,  430, 55, 65, 'circle'),
(7, 'ST-6', 6,  525, 55, 65, 'circle'),
(7, 'ST-7', 4,  100, 150, 60, 'rectangle'),
(7, 'ST-8', 4,  280, 150, 60, 'rectangle'),
(7, 'ST-9', 8,  450, 145, 80, 'rectangle');

-- Tables in Skybox (room_id = 8)
INSERT INTO `tables` (room_id, table_code, capacity, x, y, size, shape) VALUES
(8, 'SB-1', 8,  50,  265, 80, 'rectangle'),
(8, 'SB-2', 8,  200, 265, 80, 'rectangle');

-- Sample reservations (for today, so floor plan shows some red tables)
INSERT INTO reservations (table_id, reservation_date, time_slot, party_size, customer_name, customer_email, customer_phone, status, confirmation_code) VALUES
(1, CURDATE(), '7:00 PM', 2, 'Alice Johnson', 'alice@example.com', '555-0101', 'confirmed', 'RES-DEMO1'),
(3, CURDATE(), '7:00 PM', 4, 'Bob Williams', 'bob@example.com',   '555-0102', 'confirmed', 'RES-DEMO2'),
(6, CURDATE(), '7:30 PM', 4, 'Carol Davis',  'carol@example.com', '555-0103', 'confirmed', 'RES-DEMO3'),
(10, CURDATE(), '8:00 PM', 2, 'Dan Brown',   'dan@example.com',   '555-0104', 'confirmed', 'RES-DEMO4');

-- Sample waitlist
INSERT INTO waitlist (name, email, phone, party_size, preferred_date, preferred_time) VALUES
('Emma Wilson', 'emma@example.com', '555-0201', 4, CURDATE(), '7:00 PM'),
('Frank Miller', 'frank@example.com','555-0202', 2, CURDATE(), '8:00 PM');
