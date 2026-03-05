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

// Fetch room number and booking ID from active booking (Must be Checked-In)
$room_sql = "SELECT b.id as booking_id, r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id 
            WHERE b.user_id = ? AND b.status IN ('Confirmed', 'Checked-In') 
            AND CURRENT_DATE BETWEEN b.check_in AND b.check_out LIMIT 1";
$room_stmt = $conn->prepare($room_sql);
$room_stmt->bind_param("i", $user_id);
$room_stmt->execute();
$room_res = $room_stmt->get_result()->fetch_assoc();

if (!$room_res) {
    echo json_encode(['success' => false, 'message' => 'Active check-in required to place order.']);
    exit();
}

$room_number = $room_res['room_number'];
$booking_id = $room_res['booking_id'];
$items = json_decode($items_json, true);

if (!is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data.']);
    exit();
}

$total_qty = 0;
foreach ($items as $item) {
    $total_qty += (int)$item['qty'];
}

// Generate an identification label for the consolidated order
$main_item_name = (count($items) > 0) ? $items[0]['name'] : "Concierge Request";
if (count($items) > 1) {
    $main_item_name .= " (+" . (count($items) - 1) . " items)";
}

// Storing the entire basket in a single row for a unique Order ID
$sql = "INSERT INTO service_orders (booking_id, user_id, room_number, item_name, items, quantity, total_price, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisssid", $booking_id, $user_id, $room_number, $main_item_name, $items_json, $total_qty, $total_price);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Your consolidated order has been received by our concierge.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process order details.']);
}
?>
