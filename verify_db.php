<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Database Connectivity & Admin Verification</h3>";

$db = include 'config/Database.php';

if (!$db) {
    die("CRITICAL: Database object could not be initialized.");
}

$email = "admin@grandluxe.com";
$password = "admin123";

$admin = $db->fetchOne("SELECT * FROM admins WHERE email = ?", [$email]);

if (!$admin) {
    echo "❌ Admin account NOT found in database.<br>";
    echo "Attempting to create it now...<br>";
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->conn->query("INSERT INTO admins (name, email, password, role) VALUES ('Super Admin', '$email', '$hash', 'Super Admin')");
    echo "✓ Admin created.<br>";
} else {
    echo "✓ Admin account found.<br>";
    echo "Email: " . $admin['email'] . "<br>";
    echo "Hashed Password in DB: " . $admin['password'] . "<br>";
    
    if (password_verify($password, $admin['password'])) {
        echo "✅ Password verification SUCCESSFUL.<br>";
    } else {
        echo "❌ Password verification FAILED.<br>";
        echo "Updating password to 'admin123'...<br>";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $db->conn->query("UPDATE admins SET password = '$hash' WHERE email = '$email'");
        echo "✓ Password updated.<br>";
    }
}
?>
