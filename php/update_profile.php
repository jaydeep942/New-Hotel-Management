<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Self-healing: Check and add columns if they don't exist
$columns_to_check = ['phone' => 'VARCHAR(20)', 'nationality' => 'VARCHAR(100)', 'dob' => 'DATE'];
foreach($columns_to_check as $col => $type) {
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$col'");
    if($check->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD $col $type");
    }
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$nationality = trim($_POST['nationality'] ?? '');
$dob = $_POST['dob'] ?? null;

if (empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Name and Email are required.']);
    exit();
}

// Check if email is already taken (excluding self)
$check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("si", $email, $user_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This email is already associated with another account.']);
    exit();
}

// Update including new fields
$update_sql = "UPDATE users SET name = ?, email = ?, phone = ?, nationality = ?, dob = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("sssssi", $name, $email, $phone, $nationality, $dob, $user_id);

if ($update_stmt->execute()) {
    $_SESSION['name'] = $name; // Update session
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
}
?>
