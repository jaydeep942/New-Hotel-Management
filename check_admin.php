<?php
$conn = require 'config/db.php';
$res = $conn->query('SELECT * FROM admins');
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
