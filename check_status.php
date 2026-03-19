<?php
$conn = require 'config/db.php';
$booking_id = 7;
$res = $conn->query("SELECT status, user_id FROM bookings WHERE id = $booking_id");
var_dump($res->fetch_assoc());
?>
