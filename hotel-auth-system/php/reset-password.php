<?php
// Include database connection
$conn = require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $token = mysqli_real_escape_string($conn, $_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        header("Location: ../reset-password.php?email=" . urlencode($email) . "&token=" . urlencode($token) . "&error=" . urlencode("Passwords do not match"));
        exit();
    }

    // Verify token one last time before updating
    $sql = "SELECT id, token_expiry FROM users WHERE email = '$email' AND reset_token = '$token'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $expiry = strtotime($user['token_expiry']);
        
        if ($expiry > time()) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear token
            $update_sql = "UPDATE users SET password = '$hashed_password', reset_token = NULL, token_expiry = NULL WHERE email = '$email'";
            
            if ($conn->query($update_sql) === TRUE) {
                header("Location: ../login.php?registered=true&success=" . urlencode("Password reset successful. Please login."));
                exit();
            } else {
                header("Location: ../reset-password.php?email=" . urlencode($email) . "&token=" . urlencode($token) . "&error=" . urlencode("Update failed: " . $conn->error));
                exit();
            }
        } else {
            header("Location: ../login.php?error=" . urlencode("Token has expired. Please request a new OTP."));
            exit();
        }
    } else {
        header("Location: ../login.php?error=" . urlencode("Security Error: Invalid reset attempt."));
        exit();
    }
}
?>
