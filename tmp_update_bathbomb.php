<?php
$conn = require_once __DIR__ . '/config/db.php';

$sql = "UPDATE menu_items SET image_url = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQNMbVuBtP1_smDBEI4njgZmh-rER6Vz9XYTg&s' WHERE name = 'Artisan Bath Bomb Trio'";

if ($conn->query($sql) === TRUE) {
    echo "Image updated successfully for 'Artisan Bath Bomb Trio'";
} else {
    echo "Error updating image: " . $conn->error;
}
?>
