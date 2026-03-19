<?php
// Emergency Admin Repair Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hotel_management";

echo "<h2>Admin System Diagnostic</h2>";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Master Connection Failed: " . $conn->connect_error);
}

// 1. Ensure DB exists
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);
echo "✓ Database '$dbname' verified.<br>";

// 2. Drop and Recreate Admins Table to be absolutely sure of schema
$conn->query("DROP TABLE IF EXISTS admins");
$sql = "CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Super Admin', 'Staff') DEFAULT 'Staff',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "✓ Admins table rebuilt successfully.<br>";
} else {
    die("Critical Error creating table: " . $conn->error);
}

// 3. Insert fresh admin with known password
$email = "admin@grandluxe.com";
$password = "admin123";
$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admins (name, email, password, role) VALUES ('Super Admin', ?, ?, 'Super Admin')");
$stmt->bind_param("ss", $email, $hashed);

if ($stmt->execute()) {
    echo "<div style='color: green; font-weight: bold; background: #e6fffa; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
    echo "SUCCESS: Admin account has been hard-reset!<br><br>";
    echo "Email: <code>$email</code><br>";
    echo "Password: <code>$password</code><br>";
    echo "</div>";
    echo "<p>Please delete this script (fix_admin.php) after use for security.</p>";
    echo "<a href='admin/login.php' style='display: inline-block; padding: 10px 20px; background: #4a5568; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
} else {
    echo "Error inserting admin: " . $stmt->error;
}

$conn->close();
?>
