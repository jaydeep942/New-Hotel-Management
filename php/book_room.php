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
            
            // Insert single booking record
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, guest_name, guest_email, guest_phone, room_id, check_in, check_out, total_price, id_proof_type, id_proof_number, permanent_address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
            $stmt->bind_param("isssissdsss", $user_id, $guest_name, $guest_email, $guest_phone, $r_id, $check_in, $check_out, $total_price, $id_proof_type, $id_proof_number, $permanent_address);
            
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
                    <!DOCTYPE html>
                    <html lang='en'>
                    <body style='margin: 0; padding: 0; background-color: #F8F9F5; font-family: sans-serif;'>
                        <table width='100%' padding='40px'>
                            <tr>
                                <td align='center'>
                                    <h1 style='color: #1B3022;'>GRAND LUXE</h1>
                                    <p>Your multi-suite residency for <strong>$room_list_html</strong> is confirmed.</p>
                                    <p>Check-in: $check_in | Check-out: $check_out</p>
                                    <h2 style='color: #D4AF37;'>Total: ₹" . number_format($grand_total, 2) . "</h2>
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
