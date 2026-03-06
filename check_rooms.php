<?php
require_once 'config/Database.php';
$db = new Database();
$res = $db->conn->query("SELECT * FROM rooms");
while($row = $res->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | No: " . $row['room_number'] . " | Type: " . $row['room_type'] . " | Status: " . $row['status'] . "\n";
}
?>
