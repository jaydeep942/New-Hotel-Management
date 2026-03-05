<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

$orders_sql = "SELECT * FROM service_orders WHERE user_id = ? ORDER BY ordered_at DESC LIMIT 5";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_res = $orders_stmt->get_result();
$orders = [];
while($row = $orders_res->fetch_assoc()){
    $summary = "Service Request";
    if (!empty($row['item_name'])) {
        $summary = $row['item_name'];
        if ($row['quantity'] > 1) $summary .= " (x" . $row['quantity'] . ")";
    } else if (!empty($row['items'])) {
        $items = json_decode($row['items'], true);
        if (is_array($items)) {
            $itemNames = array_map(function($i) { return $i['name']; }, $items);
            $summary = implode(', ', $itemNames);
        }
    }
    $row['summary'] = $summary;
    $row['display_date'] = date('d/m/Y, h:i A', strtotime($row['ordered_at']));
    $orders[] = $row;
}

echo json_encode(['success' => true, 'orders' => $orders]);
?>
