<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

$type = $_POST['type'] ?? '';
$room_number = $_POST['room_number'] ?? '';
$description = $_POST['description'] ?? '';

if (empty($type) || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit();
}

$insert_sql = "INSERT INTO complaints (user_id, type, room_number, description) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("isss", $user_id, $type, $room_number, $description);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Your concern has been registered. Our duty manager will reach out shortly.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
}
?>
