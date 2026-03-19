<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit();
}

$conn = require_once __DIR__ . '/../config/db.php';
$booking_id = intval($_POST['booking_id']);
$user_id = $_SESSION['user_id'];

// Verify booking belongs to user and is upcoming (can be cancelled)
$sql = "SELECT status, check_in, room_id FROM bookings WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

if ($booking['status'] !== 'Confirmed') {
    echo json_encode(['success' => false, 'message' => 'Only confirmed bookings can be cancelled']);
    exit();
}

// Check if check-in is in the future (at least today)
if (strtotime(date('Y-m-d', strtotime($booking['check_in']))) < strtotime(date('Y-m-d'))) {
    echo json_encode(['success' => false, 'message' => 'Cannot cancel a past booking']);
    exit();
}

$room_id = $booking['room_id'];

// Use a transaction for atomic update
$conn->begin_transaction();

try {
    // Perform cancellation
    $update_sql = "UPDATE bookings SET status = 'Cancelled' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $booking_id);
    $update_stmt->execute();

    // Make room automatically available
    $room_sql = "UPDATE rooms SET status = 'Available' WHERE id = ?";
    $room_stmt = $conn->prepare($room_sql);
    $room_stmt->bind_param("i", $room_id);
    $room_stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Booking cancelled and room is now available']);
}
catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
}
?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan='2' style='margin-top: 20px; padding-top: 25px;'>
                                                                <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                                                    <tr>
                                                                        <td><span style='color: #6A1E2D; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;'>Refund Total</span></td>
                                                                        <td align='right'><span style='color: #D4AF37; font-size: 26px; font-weight: bold;'>₹$refund_str</span></td>
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
                                            <p style='color: #718096; font-size: 13px; line-height: 1.6; margin-bottom: 25px;'><strong>Your refund will after 7 working days.</strong></p>
                                            <p style='color: #718096; font-size: 13px; line-height: 1.6; margin-bottom: 25px;'>If you have any questions regarding these charges or wish to speak to our dispute team, contact us 24/7.</p>
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
        } catch (Exception $e) {
            error_log("Cancellation Mailer Error: " . $mail->ErrorInfo . " Exception: " . $e->getMessage());
        } catch (\Error $e) {
            error_log("Cancellation Mailer Fatal Error: " . $e->getMessage());
        }
    } else {
        error_log("Cancellation Mailer Skipped. file_exists: " . file_exists($phpmailer_path . 'PHPMailer.php') . ", guest_email: $guest_email");
    }

    echo json_encode(['success' => true, 'message' => 'Booking cancelled. Any applicable refund has been initiated.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
}
?>
