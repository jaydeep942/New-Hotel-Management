<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting DB test...<br>";

$host = "127.0.0.1"; // Using IP instead of localhost
$username = "root";
$password = "";

echo "Attempting to connect to MySQL...<br>";
$start = microtime(true);
$conn = new mysqli($host, $username, $password);
$end = microtime(true);

if ($conn->connect_error) {
    echo "Connection failed after " . ($end - $start) . " seconds: " . $conn->connect_error . "<br>";
} else {
    echo "Connected successfully in " . ($end - $start) . " seconds.<br>";
    
    $dbname = "hotel_management";
    $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
    if ($result->num_rows > 0) {
        echo "Database $dbname exists.<br>";
        $conn->select_db($dbname);
        
        $tables = ['users', 'rooms', 'bookings'];
        foreach ($tables as $table) {
            $res = $conn->query("SHOW TABLES LIKE '$table'");
            if ($res->num_rows > 0) {
                echo "Table $table exists.<br>";
                $count = $conn->query("SELECT COUNT(*) as cnt FROM $table")->fetch_assoc()['cnt'];
                echo "Table $table has $count rows.<br>";
            } else {
                echo "Table $table does NOT exist.<br>";
            }
        }
    } else {
        echo "Database $dbname does NOT exist.<br>";
    }
}

echo "<br>Testing Session...<br>";
$start = microtime(true);
session_start();
$end = microtime(true);
echo "Session started in " . ($end - $start) . " seconds.<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session save path: " . session_save_path() . "<br>";

?>
