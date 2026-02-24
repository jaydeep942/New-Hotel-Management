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

    // Check if user exists
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Success! Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];

            // Redirect to Customer Dashboard
            header("Location: ../customer-dashboard.php");
            exit();
        } else {
            header("Location: ../login.php?error=" . urlencode("Incorrect password"));
            exit();
        }
    } else {
        header("Location: ../login.php?error=" . urlencode("No account found with this email"));
        exit();
    }
} else {
    header("Location: ../login.php");
}
?>
