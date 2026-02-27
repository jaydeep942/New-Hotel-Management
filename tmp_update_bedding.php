<?php
$conn = require_once __DIR__ . '/config/db.php';

$sql = "UPDATE menu_items SET image_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSFLVVZ689rbPqWSePYdR0u4U8oPrTym4y24g&s' WHERE name = 'Premium Silk Bedding'";

if ($conn->query($sql) === TRUE) {
    echo "Image updated successfully for 'Premium Silk Bedding'";
} else {
    echo "Error updating image: " . $conn->error;
}
?>
