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
    total_amount DECIMAL(10,2) NOT NULL,
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

// 7. Create services table
$table_services = "CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) DEFAULT 'Food',
    status ENUM('Active', 'Inactive') DEFAULT 'Active'
)";
$conn->query($table_services);

// 8. Create service_orders table
$table_service_orders = "CREATE TABLE IF NOT EXISTS service_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NULL,
    service_id INT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Preparing', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
)";
$conn->query($table_service_orders);

// Migrate service_orders table columns if they exist in old format
$so_migrations = [
    'booking_id' => "INT NULL AFTER id",
    'user_id' => "INT NULL AFTER booking_id",
    'room_number' => "VARCHAR(10) NULL AFTER user_id",
    'service_id' => "INT NULL AFTER room_number",
    'item_name' => "VARCHAR(255) NULL AFTER service_id",
    'quantity' => "INT DEFAULT 1 AFTER item_name",
    'ordered_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status"
];

foreach ($so_migrations as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM `service_orders` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE service_orders ADD COLUMN $col $def");
    }
}

// Rename created_at to ordered_at if necessary
$check_ca = $conn->query("SHOW COLUMNS FROM `service_orders` LIKE 'created_at'");
if ($check_ca->num_rows > 0) {
    try {
        $conn->query("UPDATE service_orders SET ordered_at = created_at WHERE ordered_at IS NULL");
        $conn->query("ALTER TABLE service_orders DROP COLUMN created_at");
    } catch(Exception $e) {}
}

// Migration: Check for newly added registration fields
$fields_to_check = [
    'guest_email' => "VARCHAR(100) NULL AFTER guest_name",
    'guest_phone' => "VARCHAR(20) NULL AFTER guest_email",
    'id_proof_type' => "VARCHAR(20) NULL AFTER total_amount",
    'id_proof_number' => "VARCHAR(50) NULL AFTER id_proof_type",
    'permanent_address' => "TEXT NULL AFTER id_proof_number",
    'special_requests' => "TEXT NULL AFTER permanent_address"
];

// Migration: Column Renaming & Checks
$check_tp = $conn->query("SHOW COLUMNS FROM `bookings` LIKE 'total_price'");
if ($check_tp->num_rows > 0) {
    $conn->query("ALTER TABLE bookings CHANGE total_price total_amount DECIMAL(10,2) NOT NULL");
}

$admin_fields = [
    'payment_status' => "ENUM('Pending', 'Paid', 'Refunded') DEFAULT 'Pending'",
    'guest_name' => "VARCHAR(100) NOT NULL"
];
foreach ($admin_fields as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM `bookings` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN $col $def");
    }
}

foreach ($fields_to_check as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `bookings` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE bookings ADD COLUMN $col $definition");
    }
}

// Fix service_orders ordered_at if it was named order_date or created_at previously
$check_order_date = $conn->query("SHOW COLUMNS FROM `service_orders` LIKE 'order_date'");
if ($check_order_date->num_rows > 0) {
    try {
        $conn->query("ALTER TABLE service_orders CHANGE order_date ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    } catch(Exception $e) {}
}

// 7. Seed Rooms if empty (for demo)
$check_rooms = $conn->query("SELECT id FROM rooms");
if ($check_rooms->num_rows == 0) {
    $conn->query("INSERT INTO rooms (room_number, room_type, price_per_night, status) VALUES
        ('101', 'Standard', 700.00, 'Available'),
        ('102', 'Standard', 900.00, 'Available'),
        ('103', 'Standard', 1000.00, 'Available'),
        ('104', 'Standard', 1000.00, 'Available'),
        ('201', 'Deluxe', 1100.00, 'Available'),
        ('202', 'Deluxe', 1200.00, 'Available'),
        ('203', 'Deluxe', 1500.00, 'Available'),
        ('204', 'Deluxe', 1500.00, 'Available'),
        ('301', 'Executive', 1650.00, 'Available'),
        ('302', 'Executive', 1700.00, 'Available'),
        ('303', 'Executive', 1800.00, 'Available'),
        ('401', 'Presidential', 2000.00, 'Available'),
        ('402', 'Presidential', 2300.00, 'Available')");
}

// 9. Create admins table
$table_admins = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Super Admin', 'Staff') DEFAULT 'Staff',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_admins);

// Seed Super Admin if none exists
$check_admin = $conn->query("SELECT id FROM admins LIMIT 1");
if ($check_admin->num_rows == 0) {
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admins (name, email, password, role) VALUES ('Super Admin', 'admin@grandluxe.com', '$pass', 'Super Admin')");
}

// 8. Build-in Migration: Check if ALL necessary columns exist for users
$required_columns = [
    'name' => "VARCHAR(100) NOT NULL",
    'email' => "VARCHAR(100) NOT NULL UNIQUE",
    'phone' => "VARCHAR(15) NOT NULL",
    'password' => "VARCHAR(255) NOT NULL",
    'status' => "ENUM('Active', 'Inactive') DEFAULT 'Active'",
    'reset_token' => "VARCHAR(6) NULL",
    'token_expiry' => "DATETIME NULL"
];

foreach ($required_columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `users` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN $col $definition");
    }
}

// 10. Reconcile Rooms Table for Admin Statuses
$room_migrations = [
    'status' => "ENUM('Available', 'Booked', 'Maintenance', 'Needs Cleaning') DEFAULT 'Available'",
    'room_type' => "ENUM('Standard', 'Deluxe', 'Suite', 'Executive', 'Presidential') NOT NULL"
];
foreach ($room_migrations as $col => $def) {
    $conn->query("ALTER TABLE rooms MODIFY COLUMN $col $def");
}

// 11. Ensure housekeeping and other tables exist
$conn->query("CREATE TABLE IF NOT EXISTS housekeeping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT,
    status ENUM('Dirty', 'Cleaning', 'Cleaned') DEFAULT 'Dirty',
    assigned_staff_id INT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY DEFAULT 1,
    hotel_name VARCHAR(255),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    currency VARCHAR(10) DEFAULT '₹',
    logo VARCHAR(255),
    address TEXT
)");

// Return connection
return $conn;
?>
