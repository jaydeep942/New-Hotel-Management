<?php
ob_start();
session_start();

$_SESSION['user_id'] = 1;
$_SESSION['email'] = 'test@example.com';
$_SESSION['name'] = 'Test User';

$_POST['booking_id'] = 7;
$_POST['payment_id'] = 'PAID_IN_FULL';

// require process_checkout which will include config/db.php intrinsically!
include 'c:/xampp/htdocs/New-Hotel-Management/php/process_checkout.php';

$out = ob_get_clean();
echo "OUTPUT: $out\n";
?>
