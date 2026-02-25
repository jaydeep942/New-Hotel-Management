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
        $room_id = $_POST['room_id'] ?? null;
        $check_in = $_POST['check_in'] ?? null;
        $check_out = $_POST['check_out'] ?? null;
        $guest_name = $_POST['guest_name'] ?? ($_SESSION['name'] ?? 'Guest');
        $guest_email = $_POST['guest_email'] ?? ($_SESSION['email'] ?? null);
        $guest_phone = $_POST['guest_phone'] ?? null;
        $id_proof_type = $_POST['id_proof_type'] ?? null;
        $id_proof_number = $_POST['id_proof'] ?? null;
        $permanent_address = $_POST['guest_address'] ?? null;

        // MANDATORY FIELD VALIDATION
        if (!$room_id || !$check_in || !$check_out || empty($guest_name) || empty($guest_phone) || empty($id_proof_number) || empty($permanent_address)) {
            echo json_encode(['success' => false, 'message' => 'All fields (Name, Phone, ID Proof, Address, Dates) are mandatory.']);
            exit();
        }

        // DATE VALIDATION (No past dates, logical sequence)
        $today = new DateTime('today');
        $cin_date = new DateTime($check_in);
        $cout_date = new DateTime($check_out);

        if ($cin_date < $today) {
            echo json_encode(['success' => false, 'message' => 'Check-in date cannot be in the past.']);
            exit();
        }

        if ($cout_date <= $cin_date) {
            echo json_encode(['success' => false, 'message' => 'Check-out date must be after check-in date.']);
            exit();
        }

        // Calculate total price
        $room_query = $conn->prepare("SELECT price_per_night, room_type FROM rooms WHERE id = ?");
        $room_query->bind_param("i", $room_id);
        $room_query->execute();
        $room_result = $room_query->get_result();
        
        if ($room_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Room not found']);
            exit();
        }

        $room_data = $room_result->fetch_assoc();
        $price_per_night = $room_data['price_per_night'];

        $date1 = new DateTime($check_in);
        $date2 = new DateTime($check_out);
        $interval = $date1->diff($date2);
        $nights = $interval->days;
        if ($nights <= 0) $nights = 1;

        $total_price = $price_per_night * $nights;
        $room_type = $room_data['room_type'];

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO bookings (user_id, guest_name, guest_email, guest_phone, room_id, check_in, check_out, total_price, id_proof_type, id_proof_number, permanent_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
        $stmt->bind_param("isssissdsss", $user_id, $guest_name, $guest_email, $guest_phone, $room_id, $check_in, $check_out, $total_price, $id_proof_type, $id_proof_number, $permanent_address);

        if ($stmt->execute()) {
            $booking_id = $stmt->insert_id;
            // Update room status
            $conn->query("UPDATE rooms SET status = 'Occupied' WHERE id = $room_id");
            
            // --- SEND CONFIRMATION EMAIL START ---
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
                    $mail->Subject = 'Confirmed: Your Luxury Residency at Grand Luxe';

                    $current_time = date('Y-m-d H:i:s');
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
                                                    <h2 style='color: #6A1E2D; font-size: 22px; font-weight: bold; margin: 0 0 10px 0;'>Your Residency is Secured</h2>
                                                    <p style='color: #718096; font-size: 14px; margin: 0; line-height: 1.5;'>Respected $guest_name, your luxury stay at Grand Luxe has been officially archived in our vaults. We are preparing for your arrival with the utmost care.</p>
                                                </div>

                                                <!-- Booking Card -->
                                                <table width='100%' border='0' cellspacing='0' cellpadding='30' style='background-color: #FDFBFA; border: 1px solid #F3EDE7; border-radius: 20px; margin-bottom: 30px;'>
                                                    <tr>
                                                        <td>
                                                            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                <tr>
                                                                    <td style='border-bottom: 1px solid #F3EDE7; padding-bottom: 15px;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Residence ID</span><br>
                                                                        <span style='color: #6A1E2D; font-weight: bold; font-size: 14px;'>#$booking_id</span>
                                                                    </td>
                                                                    <td align='right' style='border-bottom: 1px solid #F3EDE7; padding-bottom: 15px;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Status</span><br>
                                                                        <span style='color: #2CA6A4; font-weight: bold; font-size: 14px;'>CONFIRMED</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan='2' style='padding-top: 20px;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Suite Category</span><br>
                                                                        <span style='color: #6A1E2D; font-size: 18px; font-weight: bold;'>$room_type Suite</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td style='padding-top: 20px; width: 50%;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Arrival</span><br>
                                                                        <span style='color: #333; font-size: 15px; font-weight: 600;'>$check_in</span>
                                                                    </td>
                                                                    <td style='padding-top: 20px; width: 50%;'>
                                                                        <span style='color: #A0AEC0; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: bold;'>Departure</span><br>
                                                                        <span style='color: #333; font-size: 15px; font-weight: 600;'>$check_out</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan='2' style='margin-top: 20px; padding-top: 25px; border-top: 1px dashed #D4AF37;'>
                                                                        <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                            <tr>
                                                                                <td><span style='color: #6A1E2D; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Total Residency Price</span></td>
                                                                                <td align='right'><span style='color: #D4AF37; font-size: 26px; font-weight: bold;'>$$total_price</span></td>
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
                                                    <p style='color: #718096; font-size: 13px; line-height: 1.6; margin-bottom: 25px;'>Our concierge team is at your complete disposal 24/7 for any special arrangements.</p>
                                                    <a href='#' style='display: inline-block; background: #6A1E2D; color: #ffffff; padding: 16px 35px; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 14px;'>Manage Residency</a>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Footer Section -->
                                        <tr>
                                            <td style='background-color: #fcfcfc; padding: 30px; text-align: center; border-top: 1px solid #f5f5f5;'>
                                                <p style='color: #A0AEC0; font-size: 10px; margin: 0; text-transform: uppercase; letter-spacing: 1px;'>123 Luxury Avenue • Grand Luxe Metropolis • +1 (234) 567-890</p>
                                                <p style='color: #A0AEC0; font-size: 10px; margin-top: 5px;'>© 2026 Grand Luxe Hotel Group. All rights reserved.</p>
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
                'message' => 'Booking successful!',
                'booking_id' => $booking_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Booking failed: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}

// Ensure final output is only the JSON
$output = ob_get_clean();
echo $output;
