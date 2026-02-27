<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$history_sql = "SELECT id, room_number, service_type, status, created_at FROM housekeeping_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("ii", $user_id, $limit);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'history' => $history]);
?>
