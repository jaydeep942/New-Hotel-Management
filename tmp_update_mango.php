<?php
$conn = require 'c:/xampp/htdocs/hotel-auth-system/config/db.php';
$new_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQrKP4AzLkdeOdjRGqV-cM9AoQgzbjAxpTgcw&s';
$sql = "UPDATE menu_items SET image_url = ? WHERE name = 'Golden Mango Lassi' AND category = 'Refreshments'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $new_url);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Successfully updated Golden Mango Lassi image URL\n";
    } else {
        echo "No changes made. Item might already have this URL or doesn't exist.\n";
    }
} else {
    echo "Error updating: " . $conn->error . "\n";
}
?>
