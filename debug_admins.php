<?php
$db = require_once 'config/Database.php';
$admins = $db->fetchAll("SELECT email FROM admins");
print_r($admins);
?>
