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
$payment_id = $_POST['payment_id'] ?? null;

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
                   WHERE booking_id = ? AND status = 'Delivered'";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("i", $booking_id);
    $orders_stmt->execute();
    $orders_res = $orders_stmt->get_result()->fetch_assoc();
    $service_total = $orders_res['service_total'] ?? 0;

    $final_bill = $booking['total_amount'] + $service_total;

    // 2. Update booking status and final bill
    $update_booking = $conn->prepare("UPDATE bookings SET status = 'Checked-Out', payment_status = 'Paid', actual_checkout = NOW(), final_bill = ?, razorpay_payment_id = ? WHERE id = ? AND user_id = ?");
    $update_booking->bind_param("dsii", $final_bill, $payment_id, $booking_id, $user_id);
    
    if (!$update_booking->execute()) {
        throw new Exception("Unable to finalize checkout settlement.");
    }

    // 3. Make room available for cleaning protocol
    $room_id = $booking['room_id'];
    $update_room = $conn->query("UPDATE rooms SET status = 'Needs Cleaning' WHERE id = $room_id");
    if (!$update_room) {
        throw new Exception("Unable to update room cleaning status.");
    }

    $conn->commit();
    
    // 4. Send "Return Money" Mail if Early Checkout
    try {
        require_once __DIR__ . '/../controllers/AdminController.php';
        $adminCtrl = new AdminController();
        
        $scheduled_out = strtotime($booking['check_out']);
        $today = strtotime(date('Y-m-d'));
        
        if ($today < $scheduled_out && $booking['guest_email']) {
            $subject = "Mid-Stay Departure & Refund Protocol - #LX-" . str_pad($booking_id, 4, '0', STR_PAD_LEFT);
            $message = "Respected " . htmlspecialchars($booking['guest_name']) . ",<br><br>
                        We have recorded your mid-stay departure from Grand Luxe via your private dashboard.<br><br>
                        <strong>Scheduled Departure:</strong> " . date('d M Y', $scheduled_out) . "<br>
                        <strong>Actual Departure:</strong> " . date('d M Y') . "<br><br>
                        As per our protocol for early departures, a <strong>refund for the remaining nights</strong> of your residency has been initiated. This amount will reflect in your bank account within the next <strong>7 working days</strong>.<br><br>
                        Your final residency protocol has been closed. We hope your stay was exceptional despite the change in plans.";
            $adminCtrl->sendThemedEmail($booking['guest_email'], $subject, $message, 'Refund');
        }
    } catch (Exception $e) {
        // Silently fail email if concierge server is down, don't break the checkout
        error_log("Early checkout email failed: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'message' => 'Checkout successful']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
