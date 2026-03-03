<?php
header('Content-Type: application/json');
require_once '../../controllers/AdminController.php';
$adminCtrl = new AdminController();

// Basic session check for API
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized component access.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id']) && isset($data['status'])) {
    if ($adminCtrl->updateRoomStatus($data['id'], $data['status'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database operation parity check failed.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters payload.']);
}
?>
