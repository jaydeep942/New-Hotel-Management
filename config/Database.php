<?php
if (!class_exists('Database')) {
    class Database {
        private $host = "localhost";
        private $user = "root";
        private $pass = "";
        private $dbname = "hotel_management";
        public $conn;

        public function __construct() {
            $this->conn = new mysqli($this->host, $this->user, $this->pass);
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }

            $this->createDatabase();
            $this->conn->select_db($this->dbname);
            $this->createTables();
        }

        private function createDatabase() {
            $sql = "CREATE DATABASE IF NOT EXISTS " . $this->dbname;
            $this->conn->query($sql);
        }

        private function createTables() {
            // Admins Table
            $this->conn->query("CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('Super Admin', 'Staff') DEFAULT 'Staff',
                last_login DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Users (Customers) Table
            $this->conn->query("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                phone VARCHAR(15) NOT NULL,
                password VARCHAR(255) NOT NULL,
                profile_photo VARCHAR(255) NULL,
                status ENUM('Active', 'Inactive') DEFAULT 'Active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            // Rooms Table
            $this->conn->query("CREATE TABLE IF NOT EXISTS rooms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                room_number VARCHAR(10) NOT NULL UNIQUE,
                room_type ENUM('Standard', 'Deluxe', 'Suite', 'Executive', 'Presidential') NOT NULL,
                price_per_night DECIMAL(10,2) NOT NULL,
                status ENUM('Available', 'Booked', 'Maintenance', 'Needs Cleaning') DEFAULT 'Available',
                image VARCHAR(255) NULL,
                description TEXT NULL
            )");

            // Bookings Table
            $this->conn->query("CREATE TABLE IF NOT EXISTS bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                room_id INT,
                guest_name VARCHAR(100) NOT NULL,
                check_in DATE NOT NULL,
                check_out DATE NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                status ENUM('Booked', 'Checked-In', 'Checked-Out', 'Cancelled') DEFAULT 'Booked',
                payment_status ENUM('Pending', 'Paid', 'Refunded') DEFAULT 'Pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
            )");

            // Services Table
            $this->conn->query("CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                service_name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                price DECIMAL(10,2) NOT NULL,
                category VARCHAR(50) DEFAULT 'Food',
                status ENUM('Active', 'Inactive') DEFAULT 'Active'
            )");

            // Service Orders
            $this->conn->query("CREATE TABLE IF NOT EXISTS service_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT,
                service_id INT,
                quantity INT DEFAULT 1,
                total_price DECIMAL(10,2) NOT NULL,
                status ENUM('Pending', 'Preparing', 'Delivered', 'Cancelled') DEFAULT 'Pending',
                ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
            )");

            // Migration: Column Renaming & Checks
            $so_migrations = [
                'booking_id' => "INT NULL AFTER id",
                'service_id' => "INT NULL AFTER booking_id",
                'quantity' => "INT DEFAULT 1 AFTER service_id",
                'ordered_at' => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status"
            ];
            foreach ($so_migrations as $col => $def) {
                $check = $this->conn->query("SHOW COLUMNS FROM `service_orders` LIKE '$col'");
                if ($check && $check->num_rows == 0) {
                    $this->conn->query("ALTER TABLE service_orders ADD COLUMN $col $def");
                }
            }

            // Sync rooms table ENUMs
            $room_migrations = [
                'status' => "ENUM('Available', 'Booked', 'Maintenance', 'Needs Cleaning') DEFAULT 'Available'",
                'room_type' => "ENUM('Standard', 'Deluxe', 'Suite', 'Executive', 'Presidential') NOT NULL"
            ];
            foreach ($room_migrations as $col => $def) {
                $this->conn->query("ALTER TABLE rooms MODIFY COLUMN $col $def");
            }
            
            // Housekeeping
            $this->conn->query("CREATE TABLE IF NOT EXISTS housekeeping (
                id INT AUTO_INCREMENT PRIMARY KEY,
                room_id INT,
                status ENUM('Dirty', 'Cleaning', 'Cleaned') DEFAULT 'Dirty',
                assigned_staff_id INT NULL,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
            )");

            // Settings
            $this->conn->query("CREATE TABLE IF NOT EXISTS settings (
                id INT PRIMARY KEY DEFAULT 1,
                hotel_name VARCHAR(255),
                contact_email VARCHAR(100),
                contact_phone VARCHAR(20),
                currency VARCHAR(10) DEFAULT '₹',
                logo VARCHAR(255),
                address TEXT
            )");

            // Users status migration
            $check_status = $this->conn->query("SHOW COLUMNS FROM `users` LIKE 'status'");
            if ($check_status->num_rows == 0) {
                $this->conn->query("ALTER TABLE users ADD COLUMN status ENUM('Active', 'Inactive') DEFAULT 'Active' AFTER password");
            }

            // Seed Super Admin if none exists
            $check = $this->conn->query("SELECT id FROM admins LIMIT 1");
            if ($check->num_rows == 0) {
                $pass = password_hash('admin123', PASSWORD_DEFAULT);
                $this->conn->query("INSERT INTO admins (name, email, password, role) VALUES ('Super Admin', 'admin@grandluxe.com', '$pass', 'Super Admin')");
            }
        }

        public function fetchAll($query, $params = []) {
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return [];
            if ($params) {
                $types = str_repeat('s', count($params)); 
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        public function fetchOne($query, $params = []) {
            $stmt = $this->conn->prepare($query);
            if (!$stmt) return null;
            if ($params) {
                $types = str_repeat('s', count($params)); 
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }
    }
}

return new Database();
?>
