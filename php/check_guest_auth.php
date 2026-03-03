<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Check User Status Live from Database
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

if (!$user_data || (isset($user_data['status']) && $user_data['status'] !== 'Active')) {
    session_destroy();
    header("Location: login.php?error=" . urlencode("Your account has been deactivated. Access denied."));
    exit();
}
?>
