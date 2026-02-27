<?php
// Set Global Timezone
date_default_timezone_set('Asia/Kolkata');

// Database configuration
$host = "localhost";
$username = "root";
$password = ""; // Default XAMPP password
$dbname = "hotel_management";

// 1. Connect to MySQL server
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Database created or already exists
} else {
    die("Error creating database: " . $conn->error);
}

// 3. Select the database
$conn->select_db($dbname);

// 4. Create users table if it doesn't exist
$table_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(6) NULL,
    token_expiry DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_users);

// 5. Create rooms table
$table_rooms = "CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type ENUM('Standard', 'Deluxe', 'Executive', 'Presidential') NOT NULL,
    price_per_night DECIMAL(10,2) NOT NULL,
    status ENUM('Available', 'Occupied', 'Cleaning', 'Maintenance') DEFAULT 'Available',
    image VARCHAR(255) NULL
)";
$conn->query($table_rooms);

// 6. Create bookings table
$table_bookings = "CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    guest_name VARCHAR(100) NOT NULL,
    guest_email VARCHAR(100) NULL,
    guest_phone VARCHAR(20) NULL,
    room_id INT,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    id_proof_type VARCHAR(20) NULL,
    id_proof_number VARCHAR(50) NULL,
    permanent_address TEXT NULL,
    status ENUM('Confirmed', 'Checked-In', 'Checked-Out', 'Cancelled') DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
)";
$conn->query($table_bookings);

// 7. Create notifications table
$table_notifs = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('Primary', 'Success', 'Warning', 'Info') DEFAULT 'Info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
$conn->query($table_notifs);

// Migration: Check for newly added registration fields
$fields_to_check = [
    'guest_email' => "VARCHAR(100) NULL AFTER guest_name",
    'guest_phone' => "VARCHAR(20) NULL AFTER guest_email",
    'id_proof_type' => "VARCHAR(20) NULL AFTER total_price",
    'id_proof_number' => "VARCHAR(50) NULL AFTER id_proof_type",
    'permanent_address' => "TEXT NULL AFTER id_proof_number",
    'special_requests' => "TEXT NULL AFTER permanent_address"
];

foreach ($fields_to_check as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `bookings` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN $col $definition");
    }
}

// 7. Seed Rooms if empty (for demo)
$check_rooms = $conn->query("SELECT id FROM rooms");
if ($check_rooms->num_rows == 0) {
    $conn->query("INSERT INTO rooms (room_number, room_type, price_per_night, status) VALUES 
        ('101', 'Standard', 150.00, 'Available'),
        ('102', 'Standard', 150.00, 'Available'),
        ('103', 'Standard', 150.00, 'Available'),
        ('104', 'Standard', 150.00, 'Available'),
        ('201', 'Deluxe', 250.00, 'Available'),
        ('202', 'Deluxe', 250.00, 'Available'),
        ('203', 'Deluxe', 280.00, 'Available'),
        ('204', 'Deluxe', 280.00, 'Available'),
        ('301', 'Executive', 500.00, 'Available'),
        ('302', 'Executive', 550.00, 'Available'),
        ('303', 'Executive', 550.00, 'Available'),
        ('401', 'Presidential', 1200.00, 'Available'),
        ('402', 'Presidential', 1500.00, 'Available')");
}

// 8. Build-in Migration: Check if ALL necessary columns exist for users
$required_columns = [
    'name' => "VARCHAR(100) NOT NULL",
    'email' => "VARCHAR(100) NOT NULL UNIQUE",
    'phone' => "VARCHAR(15) NOT NULL",
    'password' => "VARCHAR(255) NOT NULL",
    'reset_token' => "VARCHAR(6) NULL",
    'token_expiry' => "DATETIME NULL"
];

foreach ($required_columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `users` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN $col $definition");
    }
}

// Return connection
return $conn;
