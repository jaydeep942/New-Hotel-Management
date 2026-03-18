<?php
$conn = require_once __DIR__ . '/config/db.php';

$sql = "UPDATE menu_items SET image_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSl2iSIn3fWtpulEFep501jCuam11valy1cAg&s' WHERE name = 'Luxury Grooming Kit'";

if ($conn->query($sql) === TRUE) {
    echo "Image updated successfully for 'Luxury Grooming Kit'";
} else {
    echo "Error updating image: " . $conn->error;
}
?>
