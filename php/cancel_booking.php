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

require_once __DIR__ . '/../controllers/AdminController.php';
$adminCtrl = new AdminController();

$booking_id = intval($_POST['booking_id']);
$user_id = $_SESSION['user_id'];

// Verify booking belongs to user
$sql = "SELECT status, check_in, check_out, room_id, guest_email, guest_name FROM bookings WHERE id = ? AND user_id = ?";
$booking = $adminCtrl->db->fetchOne($sql, [$booking_id, $user_id]);

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

// User's specific condition: "if it not check in and mid stay"
// We allow cancellation if status is Booked or Confirmed (not Checked-In).
$allowed_statuses = ['Booked', 'Confirmed'];
if (!in_array($booking['status'], $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'This booking status (' . $booking['status'] . ') cannot be cancelled.']);
    exit();
}

// Check if it's past the stay period (completely over)
$today = date('Y-m-d');
if ($booking['check_out'] < $today) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel a stay that has already reached its conclusion date.']);
    exit();
}

$room_id = $booking['room_id'];

// Use a transaction for atomic update
$conn = $adminCtrl->db->conn;
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

    // Send the specific email requested by the user
    $email = $booking['guest_email'] ?: ($_SESSION['email'] ?? '');
    if ($email) {
        $subject = "Residency Cancellation & Refund Protocol";
        $message = "Respected " . htmlspecialchars($booking['guest_name']) . ",<br><br>
                    We have processed your request to cancel residency <strong>#LX-" . str_pad($booking_id, 4, '0', STR_PAD_LEFT) . "</strong>.<br><br>
                    As per our protocol, your refund will reflect in your bank account in the <strong>7 working days</strong>.<br><br>
                    Thank you for choosing Grand Luxe. We hope to host you again soon.";
        
        $adminCtrl->sendThemedEmail($email, $subject, $message, 'Refund');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking cancelled. Your refund will reflect in 7 working days.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel: ' . $e->getMessage()]);
}
?>
