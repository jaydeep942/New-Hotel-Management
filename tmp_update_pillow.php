<?php
$conn = require_once __DIR__ . '/config/db.php';

$sql = "UPDATE menu_items SET image_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ68QQBxfzKCBfJl6FT-CxphkttYveW46oR4w&s' WHERE name = 'Extra Plush Feather Pillow'";

if ($conn->query($sql) === TRUE) {
    echo "Image updated successfully for 'Extra Plush Feather Pillow'";
} else {
    echo "Error updating image: " . $conn->error;
}
?>
