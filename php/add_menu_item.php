<?php
session_start();
$conn = require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $image_url = $_POST['image_url'] ?? '';
    $category = $_POST['category'] ?? 'Refreshments';
    $sub_category = $_POST['sub_category'] ?? 'Cold Drinks';
    $meal_type = $_POST['meal_type'] ?? 'All Day';

    if (empty($name) || empty($price)) {
        echo json_encode(['success' => false, 'message' => 'Name and Price are required.']);
        exit();
    }

    $sql = "INSERT INTO menu_items (name, description, price, image_url, category, sub_category, meal_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssss", $name, $description, $price, $image_url, $category, $sub_category, $meal_type);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding item: ' . $conn->error]);
    }
}
?>
