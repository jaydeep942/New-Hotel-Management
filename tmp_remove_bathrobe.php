<?php
$conn = require_once __DIR__ . '/config/db.php';

$sql = "DELETE FROM menu_items WHERE name = 'Spa Bathrobe & Slippers'";

if ($conn->query($sql) === TRUE) {
    if ($conn->affected_rows > 0) {
        echo "Successfully removed 'Spa Bathrobe & Slippers' from amenities.";
    } else {
        echo "Item 'Spa Bathrobe & Slippers' not found or already removed.";
    }
} else {
    echo "Error removing item: " . $conn->error;
}
?>
