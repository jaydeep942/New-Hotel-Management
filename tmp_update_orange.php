<?php
$conn = require 'c:/xampp/htdocs/hotel-auth-system/config/db.php';
$new_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ21YQPKZoVosTGZedcLPHnxBopE2r7M9IVzQ&s';
$sql = "UPDATE menu_items SET image_url = ? WHERE name = 'Fresh Orange Nectar' AND category = 'Refreshments'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $new_url);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Successfully updated Fresh Orange Nectar image URL\n";
    } else {
        echo "No changes made. Item might already have this URL or doesn't exist.\n";
    }
} else {
    echo "Error updating: " . $conn->error . "\n";
}
?>
