<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_POST['items'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing data']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$items_json = $_POST['items']; 
$total_price = $_POST['total_price'];

// Fetch room number from active booking (Must be Checked-In)
$room_sql = "SELECT r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id 
            WHERE b.user_id = ? AND b.status IN ('Confirmed', 'Checked-In') 
            AND CURRENT_DATE BETWEEN b.check_in AND b.check_out LIMIT 1";
$room_stmt = $conn->prepare($room_sql);
$room_stmt->bind_param("i", $user_id);
$room_stmt->execute();
$room_res = $room_stmt->get_result()->fetch_assoc();

if (!$room_res) {
    echo json_encode(['success' => false, 'message' => 'You are not curruntly stay in hotel']);
    exit();
}

$room_number = $room_res['room_number'];

$sql = "INSERT INTO service_orders (user_id, room_number, items, total_price) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issd", $user_id, $room_number, $items_json, $total_price);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Your order has been placed! Our staff will arrive in 20-30 minutes.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $conn->error]);
}
?>
