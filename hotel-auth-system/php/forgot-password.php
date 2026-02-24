<?php
// Include database connection
$conn = require_once __DIR__ . '/../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// -------------------------------------------------------------------------
// IMPORTANT: DOWNLOAD PHPMAILER FILES
// 1. Go to: https://github.com/PHPMailer/PHPMailer
// 2. Download 'PHPMailer.php', 'SMTP.php', and 'Exception.php' from the 'src' folder.
// 3. Place them in: hotel-auth-system/php/PHPMailer/src/
// -------------------------------------------------------------------------

$phpmailer_path = __DIR__ . '/PHPMailer/src/';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if email exists
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Generate 6-digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // Save OTP to database
        $update_sql = "UPDATE users SET reset_token = '$otp', token_expiry = '$expiry' WHERE email = '$email'";
        $conn->query($update_sql);

        // Check if PHPMailer files exist
        if (file_exists($phpmailer_path . 'PHPMailer.php')) {
            require $phpmailer_path . 'Exception.php';
            require $phpmailer_path . 'PHPMailer.php';
            require $phpmailer_path . 'SMTP.php';

            $mail = new PHPMailer(true);

            try {
                // SERVER SETTINGS - CONFIGURED FOR YOU
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';             // Gmail SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'jaydipramoliya942@gmail.com'; // YOUR CONFIGURED EMAIL
                $mail->Password   = 'xkax chyj ccud sskl';          // GOOGLE APP PASSWORD NEEDED HERE
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('jaydipramoliya942@gmail.com', 'Grand Luxe Hotel');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Security Key: ' . $otp . ' - Grand Luxe Hotel';
                $mail->Body    = "
                <div style='background-color: #0c0a09; padding: 40px 10px; font-family: \"Outfit\", sans-serif; color: #ffffff;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #1c1917; border: 1px solid #d4af3733; border-radius: 32px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.5);'>
                        
                        <!-- Premium Header -->
                        <div style='background: linear-gradient(135deg, #1c1917 0%, #0c0a09 100%); padding: 50px 40px; text-align: center; border-bottom: 1px solid #d4af3722;'>
                            <div style='display: inline-block; padding: 15px; background: rgba(212, 175, 55, 0.1); border-radius: 20px; margin-bottom: 20px;'>
                                <span style='font-size: 40px; color: #d4af37;'>✨</span>
                            </div>
                            <h1 style='margin: 0; font-size: 36px; color: #d4af37; font-family: \"Playfair Display\", serif; letter-spacing: 4px; text-transform: uppercase;'>Grand Luxe</h1>
                            <p style='margin: 10px 0 0 0; font-size: 10px; color: #a8a29e; letter-spacing: 5px; text-transform: uppercase; font-weight: 700;'>Excellence Defined</p>
                        </div>
                        
                        <!-- Content Body -->
                        <div style='padding: 60px 50px; text-align: center;'>
                            <h2 style='color: #ffffff; font-size: 24px; font-weight: 500; margin-bottom: 15px;'>Security Verification</h2>
                            <p style='color: #a8a29e; font-size: 15px; line-height: 1.8; margin-bottom: 40px;'>Someone (hopefully you) requested a guest access key for your account. Please use the private code below to proceed.</p>
                            
                            <!-- Master OTP Card -->
                            <div style='background: linear-gradient(135deg, #292524 0%, #1c1917 100%); border: 1px solid #d4af3744; border-radius: 24px; padding: 40px; margin-bottom: 40px;'>
                                <p style='color: #d4af37; font-size: 11px; text-transform: uppercase; letter-spacing: 3px; font-weight: 800; margin-bottom: 20px;'>Private Security Key</p>
                                <div style='font-size: 54px; font-weight: 700; color: #ffffff; letter-spacing: 12px; font-family: monospace;'>$otp</div>
                            </div>
                            
                            <p style='color: #78716c; font-size: 13px;'>This key will expire in 15 minutes for your protection. If this wasn't you, our security team suggests ignoring this message.</p>
                        </div>
                        
                        <!-- Concierge Footer -->
                        <div style='background: #171717; padding: 40px; text-align: center; border-top: 1px solid #d4af3711;'>
                            <p style='color: #a8a29e; font-size: 12px; margin-bottom: 10px;'>24/7 Concierge Support: concierge@grandluxe.com</p>
                            <p style='color: #57534e; font-size: 10px; text-transform: uppercase; letter-spacing: 2px;'>&copy; 2026 Grand Luxe Luxury Group &bull; All Rights Reserved</p>
                        </div>
                    </div>
                    <div style='text-align: center; padding-top: 30px;'>
                        <p style='color: #44403c; font-size: 10px; text-transform: uppercase; letter-spacing: 3px;'>A Sanctuary of Privacy</p>
                    </div>
                </div>";

                $mail->send();
                header("Location: ../verify-otp.php?email=" . urlencode($email) . "&success=" . urlencode("Luxury OTP has been sent to your registered email!"));
            } catch (Exception $e) {
                header("Location: ../forgot-password.html?email=" . urlencode($email) . "&error=" . urlencode("Failed to send email. Error: " . $mail->ErrorInfo));
            }
        } else {
            // FALLBACK FOR LOCAL TESTING - If PHPMailer files are missing
            header("Location: ../verify-otp.php?email=" . urlencode($email) . "&success=" . urlencode("PHPMailer files missing. FOR TESTING USE OTP: $otp"));
        }
    } else {
        header("Location: ../forgot-password.html?error=" . urlencode("No account found with this email address."));
    }
}
?>
