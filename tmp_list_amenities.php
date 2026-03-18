<?php
$conn = require 'c:/xampp/htdocs/hotel-auth-system/config/db.php';
$res = $conn->query("SELECT name, image_url FROM menu_items WHERE category = 'Amenities'");
while($row = $res->fetch_assoc()) {
    echo $row['name'] . ' -> ' . $row['image_url'] . PHP_EOL;
}
?>
