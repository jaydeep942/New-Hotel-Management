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
        $expiry = date("Y-m-d H:i:s", strtotime("+2 minutes"));

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
                // SERVER SETTINGS - OPTIMIZED FOR COMPATIBILITY
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'grandluxe.luxury@gmail.com';
                $mail->Password   = 'hzpe obze lbbi anuu';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Changed to STARTTLS for better compatibility
                $mail->Port       = 587; // Standard port for STARTTLS
                $mail->Timeout    = 20; // Increased timeout slightly
                
                // FIXED: Bypass SSL verification issues common in local development (XAMPP/WAMP)
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // PERFORMANCE: Disable X-Mailer header
                $mail->XMailer    = ' ';

                // Recipients
                $mail->setFrom('jaydipramoliya942@gmail.com', 'Grand Luxe Hotel');
                $mail->addAddress($email);

                // EMBED LOGO - Looking for assets/logo.png or assets/logo.jpg
                $logoPath = __DIR__ . '/../assets/logo.png';
                $hasLogo = false;
                if (file_exists($logoPath)) {
                    $mail->addEmbeddedImage($logoPath, 'hotel_logo');
                    $hasLogo = true;
                } elseif (file_exists(__DIR__ . '/../assets/logo.jpg')) {
                    $mail->addEmbeddedImage(__DIR__ . '/../assets/logo.jpg', 'hotel_logo');
                    $hasLogo = true;
                }

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Exclusive Passcode: ' . $otp;
                
                // Content for Logo or Icon
                $headerIcon = $hasLogo ? '<img src="cid:hotel_logo" alt="Grand Luxe" style="max-width: 200px; height: auto; margin-bottom: 25px;">' : 
                          '<div style="width: 80px; height: 80px; line-height: 80px; background: rgba(212, 175, 55, 0.1); border: 1px solid #d4af3777; border-radius: 25px; margin-bottom: 25px;">
                                <span style="font-size: 35px; color: #d4af37;">🔱</span>
                           </div>';

                // ULTRA PREMIUM EMAIL TEMPLATE
                $mail->Body    = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&family=Playfair+Display:wght@700&display=swap');
                    </style>
                </head>
                <body style='margin: 0; padding: 0; background-color: #0c0a10; font-family: \"Outfit\", sans-serif; color: #ffffff;'>
                    <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #0c0a10; padding: 40px 0;'>
                        <tr>
                            <td align='center'>
                                <table width='600' border='0' cellspacing='0' cellpadding='0' style='background: #15131a; border: 1px solid #d4af3733; border-radius: 40px; overflow: hidden; box-shadow: 0 40px 100px rgba(0,0,0,0.6);'>
                                    <!-- Header Image / Logo Section -->
                                    <tr>
                                        <td align='center' style='padding: 60px 40px 40px 40px; background: linear-gradient(180deg, #1d1b22 0%, #15131a 100%);'>
                                            $headerIcon
                                            <h1 style='margin: 0; font-family: \"Playfair Display\", serif; font-size: 38px; color: #d4af37; letter-spacing: 6px; text-transform: uppercase;'>Grand Luxe</h1>
                                            <p style='margin: 15px 0 0 0; font-size: 11px; color: #94a3b8; letter-spacing: 4px; text-transform: uppercase; font-weight: 600;'>The Pinnacle of Luxury</p>
                                        </td>
                                    </tr>
                                    
                                    <!-- Main Body -->
                                    <tr>
                                        <td style='padding: 20px 60px 60px 60px;'>
                                            <div style='text-align: center;'>
                                                <h2 style='font-size: 26px; font-weight: 500; margin-bottom: 20px; color: #ffffff;'>Security Authentication</h2>
                                                <p style='font-size: 16px; color: #94a3b8; line-height: 1.8; margin-bottom: 40px;'>Respected Guest, <br>We received a request to access your private suite. Please use the following digital key to verify your identity.</p>
                                                
                                                <!-- OTP Container -->
                                                <div style='background: rgba(212, 175, 55, 0.03); border: 1px dashed #d4af3788; border-radius: 30px; padding: 50px 20px; margin-bottom: 40px; position: relative;'>
                                                    <p style='font-size: 12px; color: #d4af37; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 25px; font-weight: 700;'>Your Secure Entry Code</p>
                                                    <div style='font-size: 64px; font-weight: 700; color: #ffffff; letter-spacing: 15px; font-family: sans-serif;'>$otp</div>
                                                </div>
                                                
                                                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                    <tr>
                                                        <td style='padding: 20px; background: rgba(255, 255, 255, 0.03); border-radius: 20px; text-align: left;'>
                                                            <p style='margin: 0; font-size: 13px; color: #64748b; line-height: 1.6;'>
                                                                <strong style='color: #94a3b8;'>Security Note:</strong> This key is valid for exactly 2 minutes. Our concierge recommends never sharing this code with anyone. If this request was not initiated by you, please secure your account immediately.
                                                            </p>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Footer -->
                                    <tr>
                                        <td style='padding: 40px; background: #0f0d14; text-align: center; border-top: 1px solid #ffffff08;'>
                                            <p style='margin-bottom: 15px; font-size: 13px; color: #94a3b8;'>Need assistance? Your 24/7 Concierge is here.</p>
                                            <a href='#' style='color: #d4af37; text-decoration: none; font-size: 14px; font-weight: 600;'>Contact Support</a>
                                            <div style='margin-top: 30px; padding-top: 30px; border-top: 1px solid #ffffff05;'>
                                                <p style='font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: 2px;'>&copy; 2026 Grand Luxe Hotel Group. All rights reserved.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style='margin-top: 30px; font-size: 10px; color: #334155; text-transform: uppercase; letter-spacing: 5px;'>Privacy. Safety. Excellence.</p>
                            </td>
                        </tr>
                    </table>
                </body>
                </html>";

                $mail->send();
                header("Location: ../verify-otp.php?email=" . urlencode($email) . "&success=" . urlencode("A secure OTP has been dispatched to your email. Please check your inbox."));
            } catch (Exception $e) {
                header("Location: ../forgot-password.html?email=" . urlencode($email) . "&error=" . urlencode("Email delivery failed. Protocol Error: " . $mail->ErrorInfo));
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
