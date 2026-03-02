<?php
session_start();
$conn = require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'] ?? null;
$new_cin = $_POST['check_in'] ?? null;
$new_cout = $_POST['check_out'] ?? null;

if (!$booking_id || !$new_cin || !$new_cout) {
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
    exit();
}

try {
    // 1. Fetch current booking to get room_id
    $sql = "SELECT room_id FROM bookings WHERE id = ? AND user_id = ? AND status = 'Confirmed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        throw new Exception("Confirmed booking not found or already active.");
    }

    $room_id = $booking['room_id'];

    // 2. Check availability for the new dates, EXCLUDING the current booking
    $avail_sql = "SELECT id FROM bookings 
                  WHERE room_id = ? 
                  AND id != ? 
                  AND status != 'Cancelled' 
                  AND (check_in < ? AND check_out > ?)";
    $avail_stmt = $conn->prepare($avail_sql);
    $avail_stmt->bind_param("iiss", $room_id, $booking_id, $new_cout, $new_cin);
    $avail_stmt->execute();
    
    if ($avail_stmt->get_result()->num_rows > 0) {
        throw new Exception("The room is busy on these new dates. Please try another schedule.");
    }

    // 3. Recalculate price if dates changed (simplified: assuming same per-night price)
    $room_price_query = $conn->query("SELECT price_per_night FROM rooms WHERE id = $room_id");
    $room_price = $room_price_query->fetch_assoc()['price_per_night'];

    $d1 = new DateTime($new_cin);
    $d2 = new DateTime($new_cout);
    $nights = $d1->diff($d2)->days;
    $new_total = $room_price * ($nights > 0 ? $nights : 1);

    // 4. Update the booking
    $update_sql = "UPDATE bookings SET check_in = ?, check_out = ?, total_price = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssdi", $new_cin, $new_cout, $new_total, $booking_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Stay updated successfully!']);
    } else {
        throw new Exception("Unable to update database record.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
