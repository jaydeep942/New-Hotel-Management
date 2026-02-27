<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

$orders_sql = "SELECT * FROM service_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_res = $orders_stmt->get_result();
$orders = [];
while($row = $orders_res->fetch_assoc()){
    $items = json_decode($row['items'], true);
    $itemNames = array_map(function($i) { return $i['name']; }, $items);
    $row['summary'] = implode(', ', $itemNames);
    $row['display_date'] = date('d M, h:i A', strtotime($row['created_at']));
    $orders[] = $row;
}

echo json_encode(['success' => true, 'orders' => $orders]);
?>
