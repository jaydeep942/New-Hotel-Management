<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing data']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'];
$confirmed_status = isset($_POST['confirmed']) ? (int)$_POST['confirmed'] : 1; // 1 = Yes, 2 = Not Yet

// Verify request ownership and status
$check_sql = "SELECT id FROM housekeeping_requests WHERE id = ? AND user_id = ? AND status = 'Completed'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $request_id, $user_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Request not valid for confirmation.']);
    exit();
}

// Update acknowledgment status
$update_sql = "UPDATE housekeeping_requests SET is_received = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $confirmed_status, $request_id);

if ($update_stmt->execute()) {
    $msg = ($confirmed_status === 1) ? 'Service confirmed.' : 'Reported as incomplete.';
    echo json_encode(['success' => true, 'message' => $msg]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record confirmation.']);
}
?>
