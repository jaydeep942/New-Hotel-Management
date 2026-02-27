<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

// SECURE ACCESS CHECK: Only allow checked-in guests to SUBMIT
$booking_check_sql = "SELECT * FROM bookings WHERE user_id = ? AND status IN ('Confirmed', 'Checked-In') AND CURRENT_DATE BETWEEN check_in AND check_out LIMIT 1";
$check_stmt = $conn->prepare($booking_check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$booking = $check_stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Feedback submission is reserved for guests currently staying with us. Please check in to share your experience.']);
    exit();
}

$category = $_POST['category'] ?? 'Overall';
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$message = $_POST['message'] ?? '';

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid rating.']);
    exit();
}

$insert_sql = "INSERT INTO feedbacks (user_id, category, rating, message) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("isis", $user_id, $category, $rating, $message);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thank you for your valuable feedback!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
}
?>
