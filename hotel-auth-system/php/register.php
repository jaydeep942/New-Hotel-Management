<?php
// Include database connection
$conn = require_once __DIR__ . '/../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize inputs
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Basic Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        header("Location: ../register.html?error=" . urlencode("All fields are required"));
        exit();
    }

    // 2. Email Format Check
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.html?error=" . urlencode("Invalid email format"));
        exit();
    }

    // 3. Password Match Check
    if ($password !== $confirm_password) {
        header("Location: ../register.html?error=" . urlencode("Passwords do not match"));
        exit();
    }

    // 4. Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);
    if ($result->num_rows > 0) {
        header("Location: ../register.html?error=" . urlencode("Email already registered"));
        exit();
    }

    // 5. Hash Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 6. Insert into database
    $insert_sql = "INSERT INTO users (name, email, phone, password) VALUES ('$name', '$email', '$phone', '$hashed_password')";

    if ($conn->query($insert_sql) === TRUE) {
        // Redirect to login with success message
        header("Location: ../login.php?registered=true");
    } else {
        header("Location: ../register.html?error=" . urlencode("Registration failed: " . $conn->error));
    }
} else {
    header("Location: ../register.html");
}
?>
