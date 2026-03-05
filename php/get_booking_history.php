<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get total count
$count_res = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE user_id = $user_id");
$total_bookings = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $limit);

// Get paginated results
$history_result = $conn->query("SELECT b.*, r.room_type, r.room_number 
                                FROM bookings b 
                                JOIN rooms r ON b.room_id = r.id 
                                WHERE b.user_id = $user_id 
                                ORDER BY b.created_at DESC 
                                LIMIT $limit OFFSET $offset");

$history = [];
while($row = $history_result->fetch_assoc()){
    $row['formatted_check_in'] = date('d/m/Y', strtotime($row['check_in']));
    $row['formatted_check_out'] = date('d/m/Y', strtotime($row['check_out']));
    $row['formatted_actual_checkout'] = $row['actual_checkout'] ? date('d/m/Y, h:i A', strtotime($row['actual_checkout'])) : null;
    
    // Consistent price calculation logic
    $raw_final_bill = isset($row['final_bill']) ? floatval($row['final_bill']) : 0;
    $final_price = ($row['status'] === 'Checked-Out' && $raw_final_bill > 0) ? $raw_final_bill : $row['total_amount'];
    
    $row['formatted_price'] = number_format($final_price, 0);
    $row['formatted_final_bill'] = $raw_final_bill > 0 ? number_format($raw_final_bill, 0) : null;
    $history[] = $row;
}

echo json_encode([
    'success' => true,
    'history' => $history,
    'total_pages' => $total_pages,
    'current_page' => $page,
    'total_items' => $total_bookings,
    'limit' => $limit,
    'offset' => $offset
]);
?>
