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

// 2. Calculate Nights
$cin = new DateTime($booking['check_in']);
$cout = new DateTime($booking['check_out']);
$nights = $cin->diff($cout)->days;
$nights = $nights > 0 ? $nights : 1;

// 3. Fetch Pending Service Orders for this stay
// We assume anything NOT cancelled should be settled at checkout
$orders_sql = "SELECT * FROM service_orders 
               WHERE booking_id = ? 
               AND status = 'Delivered'";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $booking_id);
$orders_stmt->execute();
$orders_res = $orders_stmt->get_result();
$orders = [];
$pending_services_total = 0;

while($row = $orders_res->fetch_assoc()) {
    $orders[] = $row;
    $pending_services_total += $row['total_price'];
}

$booking_paid = $booking['total_amount']; // Already paid during booking
$grand_total_stay = $booking_paid + $pending_services_total;
$due_now = $pending_services_total; // Only charge for the extra services

// 4. Feedback check removed as per updated protocol
$feedback_exists = true; 


echo json_encode([
    'success' => true,
    'booking' => $booking,
    'nights' => $nights,
    'orders' => $orders,
    'booking_paid' => $booking_paid,
    'pending_services' => $pending_services_total,
    'grand_total_stay' => $grand_total_stay,
    'due_now' => $due_now,
    'feedback_submitted' => $feedback_exists
]);
