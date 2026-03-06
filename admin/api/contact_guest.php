<?php
require_once '../../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($email) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }
    
    $success = $adminCtrl->sendThemedEmail($email, $subject, $message, 'Concierge Response');
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Email sent successfully to ' . $email]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
