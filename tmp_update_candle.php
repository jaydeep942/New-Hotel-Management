<?php
$conn = require_once __DIR__ . '/config/db.php';

$sql = "UPDATE menu_items SET image_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_tX_DEEp1XSuw1Fc9RYh6K9-yMwA7zPg_HQ&s' WHERE name = 'Scented Candle Set'";

if ($conn->query($sql) === TRUE) {
    echo "Image updated successfully for 'Scented Candle Set'";
} else {
    echo "Error updating image: " . $conn->error;
}
?>
