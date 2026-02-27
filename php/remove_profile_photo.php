<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// Get current photo path
$sql = "SELECT profile_photo FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result && !empty($result['profile_photo'])) {
    $photoPath = __DIR__ . '/../' . $result['profile_photo'];
    if (file_exists($photoPath) && strpos($result['profile_photo'], 'uploads/profiles/') !== false) {
        unlink($photoPath);
    }
}

// Update DB
$update_sql = "UPDATE users SET profile_photo = NULL WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $user_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove photo from database.']);
}
?>
