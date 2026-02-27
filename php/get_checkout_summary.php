<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$booking_id = $_GET['id'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit();
}

// 1. Fetch booking details
$sql = "SELECT b.*, r.room_type, r.room_number, r.price_per_night 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

// 2. Calculate Nights (to show "time" as requested)
$cin = new DateTime($booking['check_in']);
$cout = new DateTime($booking['check_out']);
$nights = $cin->diff($cout)->days;
$nights = $nights > 0 ? $nights : 1;

// 3. Fetch Service Orders for this stay (based on room and date)
$orders_sql = "SELECT * FROM service_orders 
               WHERE user_id = ? AND room_number = ? 
               AND created_at >= ? 
               AND status != 'Cancelled'";
$orders_stmt = $conn->prepare($orders_sql);
$start_date = $booking['check_in'] . " 00:00:00";
$orders_stmt->bind_param("iss", $user_id, $booking['room_number'], $start_date);
$orders_stmt->execute();
$orders_res = $orders_stmt->get_result();
$orders = [];
$orders_total = 0;

while($row = $orders_res->fetch_assoc()) {
    $orders[] = $row;
    $orders_total += $row['total_price'];
}

$grand_total = $booking['total_price'] + $orders_total;

echo json_encode([
    'success' => true,
    'booking' => $booking,
    'nights' => $nights,
    'orders' => $orders,
    'grand_total' => $grand_total
]);
