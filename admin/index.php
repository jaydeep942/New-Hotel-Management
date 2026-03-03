<?php
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
