<?php

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'floodwatch';

date_default_timezone_set('Asia/Manila');

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Cannot connect to MySQL. Start MySQL in the XAMPP Control Panel, then refresh this page.');
}

$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4');
$pdo->exec('USE `' . DB_NAME . '`');

$pdo->exec('CREATE TABLE IF NOT EXISTS barangays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
)');
$pdo->exec('CREATE TABLE IF NOT EXISTS readings (
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
)');
$pdo->exec('CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    barangay_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
)');
$pdo->exec('CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barangay_id INT NOT NULL,
    level VARCHAR(10) NOT NULL,
    message VARCHAR(255) NOT NULL,
    via_sms TINYINT(1) NOT NULL DEFAULT 0,
    via_app TINYINT(1) NOT NULL DEFAULT 0,
    via_siren TINYINT(1) NOT NULL DEFAULT 0,
    recipients INT NOT NULL DEFAULT 0,
    source VARCHAR(10) NOT NULL DEFAULT "auto",
    created_at DATETIME NOT NULL,
    FOREIGN KEY (barangay_id) REFERENCES barangays(id) ON DELETE CASCADE
)');

if ((int)$pdo->query('SELECT COUNT(*) FROM barangays')->fetchColumn() === 0) {
    $ins = $pdo->prepare('INSERT INTO barangays (name) VALUES (?)');
    foreach (['Baybay', 'Buenavista', 'Cogon', 'Guirang', 'Loog', 'Mabini', 'Sugca', 'Tinaogan'] as $n) {
        $ins->execute([$n]);
    }
}
