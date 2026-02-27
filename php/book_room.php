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
                    $mail->Subject = 'Confirmed: Your Exclusive Residency at Grand Luxe';

                    $current_time = date('Y-m-d H:i:s');
                    $mail->Body = "
                    <!DOCTYPE html>
                    <html lang='en'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <style>
                            @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Playfair+Display:ital,wght@0,700;1,400&display=swap');
                        </style>
                    </head>
                    <body style='margin: 0; padding: 0; background-color: #F8F9F5; font-family: \"Outfit\", Helvetica, Arial, sans-serif; color: #2D3436;'>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #F8F9F5; padding: 40px 10px;'>
                            <tr>
                                <td align='center'>
                                    <!-- Main Container -->
                                    <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 40px; overflow: hidden; box-shadow: 0 40px 100px rgba(27, 48, 34, 0.08); border: 1px solid rgba(27, 48, 34, 0.05);'>
                                        
                                        <!-- Header Section with Luxury Gradient -->
                                        <tr>
                                            <td align='center' style='background: linear-gradient(135deg, #1B3022 0%, #2C553D 100%); padding: 60px 40px;'>
                                                <div style='color: #D4AF37; font-size: 11px; text-transform: uppercase; letter-spacing: 6px; font-weight: 600; margin-bottom: 20px;'>Private Invitation</div>
                                                <h1 style='color: #ffffff; font-size: 38px; margin: 0; letter-spacing: 4px; text-transform: uppercase; font-family: \"Playfair Display\", serif;'>GRAND<span style='color: #D4AF37;'>LUXE</span></h1>
                                                <div style='height: 1px; width: 60px; background-color: rgba(212, 175, 55, 0.4); margin: 25px auto;'></div>
                                                <p style='color: #E8DEC0; font-size: 14px; margin: 0; font-weight: 300; font-style: italic; font-family: \"Playfair Display\", serif;'>A Sanctuary of Refined Taste</p>
                                            </td>
                                        </tr>

                                        <!-- Welcome Message -->
                                        <tr>
                                            <td style='padding: 50px 50px 30px 50px;'>
                                                <div style='text-align: center;'>
                                                    <h2 style='color: #1B3022; font-size: 28px; font-weight: 600; margin: 0 0 15px 0; font-family: \"Playfair Display\", serif;'>Your Residency is Confirmed</h2>
                                                    <p style='color: #5B6E66; font-size: 15px; margin: 0; line-height: 1.8;'>Respected Guest <strong style='color: #1B3022;'>$guest_name</strong>, it is our distinct pleasure to confirm your upcoming stay. We have prepared everything to ensure your experience transcends the ordinary.</p>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Booking Details Card -->
                                        <tr>
                                            <td style='padding: 0 40px 40px 40px;'>
                                                <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #FAF9F6; border: 1px solid #E8EBE4; border-radius: 30px; overflow: hidden;'>
                                                    <!-- Top Bar -->
                                                    <tr>
                                                        <td style='padding: 25px 30px; border-bottom: 1px solid #E8EBE4;'>
                                                            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                <tr>
                                                                    <td>
                                                                        <span style='color: #A0AC9F; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;'>Residency ID</span><br>
                                                                        <span style='color: #1B3022; font-weight: 600; font-size: 16px;'>#$booking_id</span>
                                                                    </td>
                                                                    <td align='right'>
                                                                        <span style='display: inline-block; background-color: rgba(13, 148, 136, 0.1); color: #0D9488; padding: 6px 15px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;'>Status: Confirmed</span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Stay Info -->
                                                    <tr>
                                                        <td style='padding: 30px;'>
                                                            <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                <tr>
                                                                    <td colspan='2' style='padding-bottom: 25px;'>
                                                                        <span style='color: #A0AC9F; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;'>Selected Sanctuary</span><br>
                                                                        <span style='color: #1B3022; font-size: 20px; font-weight: 600; font-family: \"Playfair Display\", serif;'>$room_type Suite</span>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td style='width: 50%; padding-bottom: 25px;'>
                                                                        <span style='color: #A0AC9F; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;'>Arrival</span><br>
                                                                        <span style='color: #2D3436; font-size: 16px; font-weight: 500;'>$check_in</span>
                                                                    </td>
                                                                    <td style='width: 50%; padding-bottom: 25px;'>
                                                                        <span style='color: #A0AC9F; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;'>Departure</span><br>
                                                                        <span style='color: #2D3436; font-size: 16px; font-weight: 500;'>$check_out</span>
                                                                    </td>
                                                                </tr>
                                                            </table>

                                                            <!-- Total Price Banner -->
                                                            <div style='background: #1B3022; border-radius: 20px; padding: 25px; margin-top: 10px;'>
                                                                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                    <tr>
                                                                        <td>
                                                                            <span style='color: rgba(255,255,255,0.6); font-size: 10px; text-transform: uppercase; letter-spacing: 1px;'>Total Value of Stay</span><br>
                                                                            <span style='color: #ffffff; font-size: 14px; font-weight: 300;'>Inclusive of all luxury services</span>
                                                                        </td>
                                                                        <td align='right'>
                                                                            <span style='color: #D4AF37; font-size: 28px; font-weight: 600;'>₹$total_price</span>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <!-- Personal Concierge Note -->
                                        <tr>
                                            <td style='padding: 0 50px 50px 50px; text-align: center;'>
                                                <p style='color: #5B6E66; font-size: 14px; line-height: 1.6; margin-bottom: 30px;'>Our 24/7 elite concierge team has been notified of your arrival. For any bespoke requirements prior to check-in, please reach out to us.</p>
                                                <a href='#' style='display: inline-block; background: #D4AF37; color: #ffffff; padding: 18px 45px; border-radius: 100px; text-decoration: none; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 2px; box-shadow: 0 10px 25px rgba(212, 175, 55, 0.2);'>Manage Your Stay</a>
                                            </td>
                                        </tr>

                                        <!-- Footer Signature -->
                                        <tr>
                                            <td style='background-color: #FBFBFA; padding: 40px; text-align: center; border-top: 1px solid #F3F4F1;'>
                                                <div style='color: #1B3022; font-family: \"Playfair Display\", serif; font-size: 20px; margin-bottom: 15px;'>Grand Luxe</div>
                                                <p style='color: #A0AC9F; font-size: 10px; margin: 0; text-transform: uppercase; letter-spacing: 2px;'>Marine Drive • Nariman Point • Mumbai</p>
                                                <p style='color: #B2BEC3; font-size: 10px; margin-top: 10px;'>&copy; 2026 Grand Luxe Hotel Group. Defined by Excellence.</p>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- Social Links / Extra Footer -->
                                    <div style='padding-top: 30px; text-align: center;'>
                                        <p style='color: #B2BEC3; font-size: 10px; text-transform: uppercase; letter-spacing: 4px;'>Privacy • Safety • Excellence</p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </body>
                    </html>";
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
