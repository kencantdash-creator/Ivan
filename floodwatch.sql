-- FloodWatch Basey database
-- Import in phpMyAdmin: Import tab > choose this file > Import
-- (The app also creates the empty tables by itself, so this file is optional.)

CREATE DATABASE IF NOT EXISTS `floodwatch` CHARACTER SET utf8mb4;
USE `floodwatch`;

CREATE TABLE IF NOT EXISTS barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_id INT NOT NULL,
    rainfall_mm DECIMAL(6,1) NOT NULL,
    river_level_m DECIMAL(4,2) NOT NULL,
    soil_saturation TINYINT UNSIGNED NOT NULL,
    tide_level_m DECIMAL(3,2) NOT NULL,
    probability DECIMAL(5,4) NOT NULL,
    risk_level VARCHAR(10) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    barangay_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_id INT NOT NULL,
    level VARCHAR(10) NOT NULL,
    message VARCHAR(255) NOT NULL,
    via_sms TINYINT(1) NOT NULL DEFAULT 0,
    via_app TINYINT(1) NOT NULL DEFAULT 0,
    via_siren TINYINT(1) NOT NULL DEFAULT 0,
    recipients INT NOT NULL DEFAULT 0,
    source VARCHAR(10) NOT NULL DEFAULT 'auto',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
);

-- Barangays (sample list, edit to the real flood-prone barangays)
INSERT IGNORE INTO barangays (id, name) VALUES
(1,'Baybay'),(2,'Buenavista'),(3,'Cogon'),(4,'Guirang'),
(5,'Loog'),(6,'Mabini'),(7,'Sugca'),(8,'Tinaogan');

-- Sample data for the demo (delete these rows if you want a clean start)
INSERT INTO readings (barangay_id, rainfall_mm, river_level_m, soil_saturation, tide_level_m, probability, risk_level, created_at) VALUES
(1, 50.0, 1.50, 40, 0.80, 0.0427, 'Low',      NOW()),
(2, 90.0, 2.20, 60, 1.00, 0.2612, 'Moderate', NOW()),
(3,120.0, 3.00, 70, 1.20, 0.6682, 'High',     NOW()),
(7,200.0, 4.00, 90, 1.80, 0.9858, 'Critical', NOW());

INSERT INTO subscribers (name, phone, barangay_id, created_at) VALUES
('Juan Dela Cruz', '09171234567', 3, NOW()),
('Maria Santos',   '09181234567', 7, NOW());

INSERT INTO alerts (barangay_id, level, message, via_sms, via_app, via_siren, recipients, source, created_at) VALUES
(3, 'High',     'HIGH flood risk in Brgy. Cogon. Prepare go-bags and move valuables and livestock to higher ground.', 1, 1, 0, 1, 'auto', NOW()),
(7, 'Critical', 'CRITICAL flood warning for Brgy. Sugca. Evacuate to the nearest evacuation center now.',             1, 1, 1, 1, 'auto', NOW());
