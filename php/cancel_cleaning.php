<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Kolkata');
$user_id = $_SESSION['user_id'];
$request_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($request_id > 0) {
    // Cancel specific request
    $sql = "UPDATE housekeeping_requests SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $request_id, $user_id);
} else {
    // Cancel most recent pending (legacy support for the success screen button)
    $sql = "UPDATE housekeeping_requests SET status = 'Cancelled' WHERE user_id = ? AND status = 'Pending' ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Request cancelled successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unable to cancel this request. It may already be in progress or completed.']);
}
?>
