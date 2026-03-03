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

    // 2. Check if it's an admin
    $sql_admin = "SELECT * FROM admins WHERE email = '$email'";
    $res_admin = $conn->query($sql_admin);

    if ($res_admin && $res_admin->num_rows == 1) {
        $admin = $res_admin->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['name'] = $admin['name'];
            $_SESSION['role'] = $admin['role'];
            header("Location: ../admin/dashboard.php");
            exit();
        } else {
            header("Location: ../login.php?error=" . urlencode("Incorrect password"));
            exit();
        }
    }

    // 3. Not found in either
    header("Location: ../login.php?error=" . urlencode("No account found with this email"));
    exit();
} else {
    header("Location: ../login.php");
}
?>
