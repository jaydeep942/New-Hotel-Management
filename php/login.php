<?php
// Start session
session_start();

// Include database connection
$conn = require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: ../login.php?error=" . urlencode("Fields cannot be empty"));
        exit();
    }

    // 1. Check if it's a regular user
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Check Account Status
        if (isset($user['status']) && $user['status'] !== 'Active') {
            header("Location: ../login.php?error=" . urlencode("Your account is currently inactive. Please contact management."));
            exit();
        }

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            header("Location: ../customer-dashboard.php");
            exit();
        } else {
            header("Location: ../login.php?error=" . urlencode("Incorrect password"));
            exit();
        }
    } 
    
    // 2. Not found or incorrect
    header("Location: ../login.php?error=" . urlencode("Invalid email or password"));
    exit();
} else {
    header("Location: ../login.php");
}
?>
