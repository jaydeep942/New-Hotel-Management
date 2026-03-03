<?php
session_start();
$conn = require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Prevent any stray output (warnings/notices) from breaking JSON
ob_start();

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_SESSION['user_id'];
        $room_ids_str = $_POST['room_ids'] ?? null;
        $room_ids = $room_ids_str ? explode(',', $room_ids_str) : [];
        $check_in = $_POST['check_in'] ?? null;
        $check_out = $_POST['check_out'] ?? null;
        $guest_name = $_POST['guest_name'] ?? ($_SESSION['name'] ?? 'Guest');
        $guest_email = $_POST['guest_email'] ?? ($_SESSION['email'] ?? null);
        $guest_phone = $_POST['guest_phone'] ?? null;
        $id_proof_type = $_POST['id_proof_type'] ?? null;
        $id_proof_number = $_POST['id_proof'] ?? null;
        $permanent_address = $_POST['guest_address'] ?? null;

        // MANDATORY FIELD VALIDATION
        if (empty($room_ids) || !$check_in || !$check_out || empty($guest_name) || empty($guest_phone) || empty($id_proof_number) || empty($permanent_address)) {
            echo json_encode(['success' => false, 'message' => 'Selection and all registration fields are mandatory.']);
            exit();
        }

        // DATE VALIDATION
        $today = new DateTime('today');
        $cin_date = new DateTime($check_in);
        $cout_date = new DateTime($check_out);

        if ($cin_date < $today) {
            echo json_encode(['success' => false, 'message' => 'Check-in date cannot be in the past.']);
            exit();
        }

        if ($cout_date <= $cin_date) {
            echo json_encode(['success' => false, 'message' => 'Departure must be after arrival.']);
            exit();
        }

        $nights = $cin_date->diff($cout_date)->days;
        if ($nights <= 0) $nights = 1;

        $room_details = [];
        $grand_total = 0;

        // Process each room
        foreach ($room_ids as $r_id) {
            $room_query = $conn->prepare("SELECT price_per_night, room_type, room_number FROM rooms WHERE id = ?");
            $room_query->bind_param("i", $r_id);
            $room_query->execute();
            $room_data = $room_query->get_result()->fetch_assoc();
            
            if (!$room_data) continue;

            $total_price = $room_data['price_per_night'] * $nights;
            $grand_total += $total_price;
            
            $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? null;

            // Insert single booking record
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, guest_name, guest_email, guest_phone, room_id, check_in, check_out, total_price, id_proof_type, id_proof_number, permanent_address, status, razorpay_payment_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?)");
            $stmt->bind_param("isssissdssss", $user_id, $guest_name, $guest_email, $guest_phone, $r_id, $check_in, $check_out, $total_price, $id_proof_type, $id_proof_number, $permanent_address, $razorpay_payment_id);
            
            if ($stmt->execute()) {
                $conn->query("UPDATE rooms SET status = 'Occupied' WHERE id = $r_id");
                $room_details[] = $room_data['room_type'] . " (Room " . $room_data['room_number'] . ")";
            }
        }

        if (!empty($room_details)) {
            // --- SEND CONFIRMATION EMAIL ---
            $phpmailer_path = __DIR__ . '/PHPMailer/src/';
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
                    $mail->Subject = 'Confirmed: Your Multi-Suite Residency at Grand Luxe';

                    $room_list_html = implode(', ', $room_details);
                    $mail->Body = "
                    <div style='background-color: #F8F5F0; padding: 40px 10px; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                            <tr>
                                <td align='center'>
                                    <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 30px; overflow: hidden; box-shadow: 0 20px 40px rgba(106, 30, 45, 0.05); border: 1px solid rgba(106, 30, 45, 0.05);'>
                                        <!-- Header Section -->
                                        <tr>
                                            <td align='center' style='background: linear-gradient(135deg, #6A1E2D 0%, #832537 100%); padding: 50px 40px;'>
                                                <div style='color: #D4AF37; font-size: 10px; text-transform: uppercase; letter-spacing: 5px; font-weight: bold; margin-bottom: 15px;'>Confirmed Reservation</div>
                                                <h1 style='color: #ffffff; font-size: 34px; margin: 0; letter-spacing: 2px; text-transform: uppercase; font-family: serif;'>GRAND<span style='color: #D4AF37;'>LUXE</span></h1>
                                                <div style='height: 2px; width: 40px; background-color: #D4AF37; margin: 20px auto;'></div>
                                                <p style='color: #ffffff; opacity: 0.8; font-size: 13px; margin: 0; font-weight: 300;'>Excellence Defined Since 1924</p>
                                            </td>
                                        </tr>

                                        <!-- Body Section -->
                                        <tr>
                                            <td style='padding: 40px;'>
                                                <div style='text-align: center; margin-bottom: 40px;'>
                                                    <h2 style='color: #6A1E2D; font-size: 22px; font-weight: bold; margin: 0 0 10px 0;'>Your Collection of Suites is Secured</h2>
                                                    <p style='color: #718096; font-size: 14px; margin: 0; line-height: 1.5;'>Respected $guest_name, your residency for <strong>$room_list_html</strong> has been successfully archived in our vaults. We are preparing for your arrival with the utmost care.</p>
                                                </div>

                                                <!-- Booking Card -->
                                                <table width='100%' border='0' cellspacing='0' cellpadding='30' style='background-color: #FDFBFA; border: 1px solid #F3EDE7; border-radius: 20px; margin-bottom: 30px;'>
                                                    <tr>
                                                        <td>
                                                            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                <tr>
                                                                    <td style='border-bottom: 1px solid #F3EDE7; padding-bottom: 15px;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Reservation Status</span><br>
                                                                        <span style='color: #2CA6A4; font-weight: bold; font-size: 14px;'>CONFIRMED</span>
                                                                    </td>
                                                                    <td align='right' style='border-bottom: 1px solid #F3EDE7; padding-bottom: 15px;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Stay Duration</span><br>
                                                                        <span style='color: #6A1E2D; font-weight: bold; font-size: 14px;'>$nights Nights</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan='2' style='padding-top: 20px;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Selected Residency</span><br>
                                                                        <span style='color: #6A1E2D; font-size: 16px; font-weight: bold;'>$room_list_html</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td style='padding-top: 20px; width: 50%;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Arrival Date</span><br>
                                                                        <span style='color: #333; font-size: 15px; font-weight: 600;'>$check_in</span>
                                                                    </td>
                                                                    <td style='padding-top: 20px; width: 50%;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Departure Date</span><br>
                                                                        <span style='color: #333; font-size: 15px; font-weight: 600;'>$check_out</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan='2' style='margin-top: 20px; padding-top: 25px; border-top: 1px dashed #D4AF37;'>
                                                                        <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                            <tr>
                                                                                <td><span style='color: #6A1E2D; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Investment Total</span></td>
                                                                                <td align='right'><span style='color: #D4AF37; font-size: 26px; font-weight: bold;'>₹" . number_format($grand_total, 0) . "</span></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>

                                                <!-- Action CTA -->
                                                <div style='text-align: center; padding-bottom: 20px;'>
                                                    <p style='color: #718096; font-size: 13px; line-height: 1.6; margin-bottom: 25px;'>Our concierge team is at your complete disposal 24/7 for any special requirements or portfolio adjustments.</p>
                                                    <a href='#' style='display: inline-block; background: #6A1E2D; color: #ffffff; padding: 16px 35px; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 14px; box-shadow: 0 10px 20px rgba(106, 30, 45, 0.2);'>Access Dashboard</a>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Footer Section -->
                                        <tr>
                                            <td style='background-color: #fcfcfc; padding: 30px; text-align: center; border-top: 1px solid #f5f5f5;'>
                                                <p style='color: #A0AEC0; font-size: 10px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>123 Royalty Avenue • Grand Luxe Metropolis • +1 (234) 567-890</p>
                                                <p style='color: #A0AEC0; font-size: 10px; margin-top: 5px;'>© 2026 Grand Luxe Hotel Group. Excellence Defined.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>";
                    $mail->send();
                } catch (Exception $e) {}
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Multi-room booking successful!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}

// Ensure final output is only the JSON
$output = ob_get_clean();
echo $output;
