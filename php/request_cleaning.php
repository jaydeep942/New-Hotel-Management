<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['service_type'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing data']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$service_type = $_POST['service_type'];

// Fetch room number from active booking (Must be Checked-In)
$room_sql = "SELECT r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id 
            WHERE b.user_id = ? AND b.status IN ('Confirmed', 'Checked-In') 
            AND CURRENT_DATE BETWEEN b.check_in AND b.check_out LIMIT 1";
$room_stmt = $conn->prepare($room_sql);
$room_stmt->bind_param("i", $user_id);
$room_stmt->execute();
$room_res = $room_stmt->get_result()->fetch_assoc();

if (!$room_res) {
    echo json_encode(['success' => false, 'message' => 'Active check-in required to request service.']);
    exit();
}

$room_number = $room_res['room_number'];

// Check if a cleaning table exists, if not we can use service_orders or create a new one.
// For now, let's log it into a new 'housekeeping_requests' table.
// First, let's create the table if it doesn't exist.
$table_sql = "CREATE TABLE IF NOT EXISTS housekeeping_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_sql);

$sql = "INSERT INTO housekeeping_requests (user_id, room_number, service_type) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $room_number, $service_type);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Housekeeping request received.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit request: ' . $conn->error]);
}
?>
