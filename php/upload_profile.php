<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];

if (isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array(strtolower($ext), $allowed)) {
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $target = '../uploads/profiles/' . $filename;
        $db_path = 'uploads/profiles/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            // Update database
            $sql = "UPDATE users SET profile_photo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $db_path, $user_id);
            if ($stmt->execute()) {
                $_SESSION['profile_photo'] = $db_path;
                echo json_encode(['success' => true, 'path' => $db_path]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
?>
