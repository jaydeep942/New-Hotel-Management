<?php
session_start();
$conn = require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $room_ids_str = $_GET['room_ids'] ?? null;
    $cin = $_GET['cin'] ?? null;
    $cout = $_GET['cout'] ?? null;

    if (!$room_ids_str || !$cin || !$cout) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
        exit();
    }

    $room_ids = explode(',', $room_ids_str);
    
    // Simple availability check: Check if any of these rooms have a confirmed booking that overlaps with these dates
    // Query overlap: (start1 < end2) AND (end1 > start2)
    foreach ($room_ids as $id) {
        $stmt = $conn->prepare("SELECT id FROM bookings WHERE room_id = ? AND status IN ('Confirmed', 'Checked-In') AND (check_in < ? AND check_out > ?)");
        $stmt->bind_param("iss", $id, $cout, $cin);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // One of the rooms is taken
            $room_query = $conn->prepare("SELECT room_number FROM rooms WHERE id = ?");
            $room_query->bind_param("i", $id);
            $room_query->execute();
            $room_num = $room_query->get_result()->fetch_assoc()['room_number'];
            
            echo json_encode(['success' => false, 'message' => "Suite $room_num is already reserved for these dates."]);
            exit();
        }
    }

    // If we reach here, all rooms are available
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
