<?php
$host = "localhost";
$username = "root";
$password = "";
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    echo "Connection failed: " . $conn->connect_error;
} else {
    echo "Connected successfully. ";
    $result = $conn->query("SHOW DATABASES LIKE 'hotel_management'");
    if ($result->num_rows > 0) {
        echo "Database hotel_management exists.";
    } else {
        echo "Database hotel_management does NOT exist.";
    }
}
?>
