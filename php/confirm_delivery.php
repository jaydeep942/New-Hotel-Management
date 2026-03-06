<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing order identification.']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$order_id = $_POST['order_id'];
$received_status = isset($_POST['received']) ? (int)$_POST['received'] : 1; // 1 = Yes, 2 = Not Yet

// Verify order ownership and status
$check_sql = "SELECT id FROM service_orders WHERE id = ? AND user_id = ? AND status = 'Delivered'";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $order_id, $user_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not valid for confirmation.']);
    exit();
}

// Update acknowledgment status
$update_sql = "UPDATE service_orders SET is_received = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $received_status, $order_id);

if ($update_stmt->execute()) {
    $msg = ($received_status === 1) ? 'Delivery confirmed.' : 'Reported as not received.';
    echo json_encode(['success' => true, 'message' => $msg]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record confirmation.']);
}
?>
