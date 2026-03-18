<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$booking_id = intval($_POST['booking_id']);
$user_id = $_SESSION['user_id'];

// Verify booking belongs to user and is upcoming (can be cancelled)
$sql = "SELECT status, check_in, room_id FROM bookings WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

if ($booking['status'] !== 'Confirmed') {
    echo json_encode(['success' => false, 'message' => 'Only confirmed bookings can be cancelled']);
    exit();
}

// Check if check-in is in the future (at least today)
if (strtotime(date('Y-m-d', strtotime($booking['check_in']))) < strtotime(date('Y-m-d'))) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel a past booking']);
    exit();
}

$room_id = $booking['room_id'];

// Use a transaction for atomic update
$conn->begin_transaction();

try {
    // Perform cancellation
    $update_sql = "UPDATE bookings SET status = 'Cancelled' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $booking_id);
    $update_stmt->execute();

    // Make room automatically available
    $room_sql = "UPDATE rooms SET status = 'Available' WHERE id = ?";
    $room_stmt = $conn->prepare($room_sql);
    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking cancelled and room is now available']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
}
?>
