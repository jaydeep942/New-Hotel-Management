<?php
// Include database connection
$conn = require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $otp = mysqli_real_escape_string($conn, trim($_POST['otp']));

    // Check OTP and Expiry
    // Using simple query first to see if user exists and token matches
    $sql = "SELECT reset_token, token_expiry FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Debugging / Strict Comparison
        if ($user['reset_token'] === $otp) {
            $expiry = strtotime($user['token_expiry']);
            $now = time();
            
            if ($expiry > $now) {
                // Success!
                header("Location: ../reset-password.php?email=" . urlencode($email) . "&token=" . urlencode($otp));
                exit();
            } else {
                header("Location: ../verify-otp.php?email=" . urlencode($email) . "&error=" . urlencode("OTP has expired. Please request a new one."));
                exit();
            }
        } else {
            header("Location: ../verify-otp.php?email=" . urlencode($email) . "&error=" . urlencode("Invalid OTP code. Please check and try again."));
            exit();
        }
    } else {
        header("Location: ../verify-otp.php?email=" . urlencode($email) . "&error=" . urlencode("System Error: Email not found in verification flow."));
        exit();
    }
}
?>
