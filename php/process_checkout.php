<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Fetch current booking and service orders total
    $sql = "SELECT b.*, r.room_number 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            WHERE b.id = ? AND b.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        throw new Exception("Booking not found.");
    }

    // Calculate Service Orders Total
    $orders_sql = "SELECT SUM(total_price) as service_total FROM service_orders 
                   WHERE user_id = ? AND room_number = ? 
                   AND created_at >= ? AND status != 'Cancelled'";
    $orders_stmt = $conn->prepare($orders_sql);
    $start_date = $booking['check_in'] . " 00:00:00";
    $orders_stmt->bind_param("iss", $user_id, $booking['room_number'], $start_date);
    $orders_stmt->execute();
    $orders_res = $orders_stmt->get_result()->fetch_assoc();
    $service_total = $orders_res['service_total'] ?? 0;

    $final_bill = $booking['total_price'] + $service_total;

    // 2. Update booking status and final bill
    $update_booking = $conn->prepare("UPDATE bookings SET status = 'Checked-Out', actual_checkout = NOW(), final_bill = ? WHERE id = ? AND user_id = ?");
    $update_booking->bind_param("dii", $final_bill, $booking_id, $user_id);
    
    if (!$update_booking->execute()) {
        throw new Exception("Unable to finalize checkout settlement.");
    }

    // 3. Make room available
    $room_id = $booking['room_id'];
    $update_room = $conn->query("UPDATE rooms SET status = 'Available' WHERE id = $room_id");
    if (!$update_room) {
        throw new Exception("Unable to update room availability.");
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Checkout successful']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
