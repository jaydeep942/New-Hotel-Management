<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$user_id = $_SESSION['user_id'];
$booking_id = $_POST['booking_id'] ?? null;
$payment_id = $_POST['payment_id'] ?? null;

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Fetch current booking and service orders total
    $sql = "SELECT b.*, r.room_number 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            WHERE b.id = ? AND b.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();

    if (!$booking) {
        throw new Exception("Booking not found.");
    }

    // Calculate Service Orders Total
    $orders_sql = "SELECT SUM(total_price) as service_total FROM service_orders 
                   WHERE booking_id = ? AND status = 'Delivered'";
    $orders_stmt = $conn->prepare($orders_sql);
    $orders_stmt->bind_param("i", $booking_id);
    $orders_stmt->execute();
    $orders_res = $orders_stmt->get_result()->fetch_assoc();
    $service_total = $orders_res['service_total'] ?? 0;

    $final_bill = $booking['total_amount'] + $service_total;

    // 2. Update booking status and final bill
    $update_booking = $conn->prepare("UPDATE bookings SET status = 'Checked-Out', payment_status = 'Paid', actual_checkout = NOW(), final_bill = ?, razorpay_payment_id = ? WHERE id = ? AND user_id = ?");
    $update_booking->bind_param("dsii", $final_bill, $payment_id, $booking_id, $user_id);
    
    if (!$update_booking->execute()) {
        throw new Exception("Unable to finalize checkout settlement.");
    }

    // 3. Make room available for cleaning protocol
    $room_id = $booking['room_id'];
    $update_room = $conn->query("UPDATE rooms SET status = 'Needs Cleaning' WHERE id = $room_id");
    if (!$update_room) {
        throw new Exception("Unable to update room cleaning status.");
    }

    // 4. Send Email Notification
    $phpmailer_path = __DIR__ . '/PHPMailer/src/';
    $guest_email = $booking['guest_email'] ?? $_SESSION['email'];
    $guest_name = $booking['guest_name'] ?? $_SESSION['name'];

    if (file_exists($phpmailer_path . 'PHPMailer.php') && !empty($guest_email)) {
        require_once $phpmailer_path . 'Exception.php';
        require_once $phpmailer_path . 'PHPMailer.php';
        require_once $phpmailer_path . 'SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'grandluxe.luxury@gmail.com';
            $mail->Password   = 'hzpe obze lbbi anuu';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

            $mail->setFrom('jaydipramoliya942@gmail.com', 'Grand Luxe Hotel');
            $mail->addAddress($guest_email, $guest_name);
            $mail->isHTML(true);
            
            // Check for mid-stay refund condition
            $amount_paid = $booking['amount_paid'] ?? $booking['total_amount'];
            $due_now = ($final_bill - $amount_paid);

            $mail->Subject = 'Departure Receipt & Settlement: Grand Luxe';

            $content_title = "Checkout Settlement Successful";
            $content_desc = "Respected $guest_name, we have recorded your departure via your private dashboard.";
            $action_text = "";

            if ($due_now < 0) {
                // It's a refund scenario (Mid-stay departure usually)
                $refund_str = number_format(abs($due_now), 2);
                $content_title = "Mid-Stay Departure & Refund Protocol";
                $action_text = "
                <span class='text-sm italic block text-teal-600 mb-2'>Refund Allocation Detected</span>
                <div style='background: #fdfaf5; border: 1px solid #eee; padding: 20px; border-radius: 12px; margin: 30px 0;'>
                    <p style='color: #6A1E2D; font-weight: bold; font-size: 18px; margin: 0;'>Expected Refund Reflection</p>
                    <p style='color: #777; font-size: 14px; margin-top: 10px;'>A refund of <strong>₹$refund_str</strong> has been successfully processed. Your refund will reflect after 7 working days.</p>
                </div>";
            }

            $booking_id_str = "#LX-" . str_pad($booking_id, 4, '0', STR_PAD_LEFT);
            $final_bill_str = number_format($final_bill, 2);

            $mail->Body = "
            <div style='background-color: #F8F5F0; padding: 40px 10px; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                    <tr>
                        <td align='center'>
                            <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 30px; overflow: hidden; box-shadow: 0 20px 40px rgba(106, 30, 45, 0.05); border: 1px solid rgba(106, 30, 45, 0.05);'>
                                <tr>
                                    <td align='center' style='background: linear-gradient(135deg, #6A1E2D 0%, #832537 100%); padding: 50px 40px;'>
                                        <div style='color: #D4AF37; font-size: 10px; text-transform: uppercase; letter-spacing: 5px; font-weight: bold; margin-bottom: 15px;'>Official Receipt</div>
                                        <h1 style='color: #ffffff; font-size: 34px; margin: 0; letter-spacing: 2px; text-transform: uppercase; font-family: serif;'>GRAND<span style='color: #D4AF37;'>LUXE</span></h1>
                                        <div style='height: 2px; width: 40px; background-color: #D4AF37; margin: 20px auto;'></div>
                                        <p style='color: #ffffff; opacity: 0.8; font-size: 13px; margin: 0; font-weight: 300;'>Excellence Defined Since 1924</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 40px;'>
                                        <div style='text-align: center; margin-bottom: 40px;'>
                                            <h2 style='color: #6A1E2D; font-size: 22px; font-weight: bold; margin: 0 0 10px 0;'>$content_title</h2>
                                            <p style='color: #718096; font-size: 14px; margin: 0; line-height: 1.5;'>$content_desc</p>
                                        </div>

                                        <table width='100%' border='0' cellspacing='0' cellpadding='30' style='background-color: #FDFBFA; border: 1px solid #F3EDE7; border-radius: 20px; margin-bottom: 30px;'>
                                            <tr>
                                                <td>
                                                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                        <tr>
                                                            <td style='border-bottom: 1px solid #F3EDE7; padding-bottom: 15px;'>
                                                                <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Reservation Status</span><br>
                                                                <span style='color: #2CA6A4; font-weight: bold; font-size: 14px;'>CHECKED-OUT</span>
                                                            </td>
                                                            <td align='right' style='border-bottom: 1px solid #F3EDE7; padding-bottom: 15px;'>
                                                                <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Booking Reference</span><br>
                                                                <span style='color: #6A1E2D; font-weight: bold; font-size: 14px;'>$booking_id_str</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan='2' style='margin-top: 20px; padding-top: 25px;'>
                                                                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                    <tr>
                                                                        <td><span style='color: #6A1E2D; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Investment Total</span></td>
                                                                        <td align='right'><span style='color: #D4AF37; font-size: 26px; font-weight: bold;'>₹$final_bill_str</span></td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <div style='text-align: center; padding-bottom: 20px;'>
                                            $action_text
                                            <p style='color: #718096; font-size: 13px; line-height: 1.6; margin-bottom: 25px;'>If you have any questions regarding your final settlement bill or wish to speak to our dispute team, contact us 24/7.</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='background-color: #fcfcfc; padding: 30px; text-align: center; border-top: 1px solid #f5f5f5;'>
                                        <p style='color: #A0AEC0; font-size: 10px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>123 Royalty Avenue • Grand Luxe Metropolis • +1 (234) 567-890</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>";
            
            $mail->send();
        } catch (Exception $e) {
            error_log("Checkout Mailer Error: " . $e->getMessage());
        } catch (\Error $e) {
            error_log("Checkout Mailer Fatal Error: " . $e->getMessage());
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Checkout successful']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
