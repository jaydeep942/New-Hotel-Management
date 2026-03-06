<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AdminController {
    protected $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $db_path = __DIR__ . '/../config/Database.php';
        
        // Ensure class is defined first
        require_once $db_path; 
        
        // Then instantiate or get the return value if it's an object
        $res = include $db_path; 
        if ($res instanceof Database) {
            $this->db = $res;
        } else {
            $this->db = new Database();
        }
    }

    public function checkAuth() {
        if (!isset($_SESSION['admin_id'])) {
            header("Location: login.php");
            exit();
        }
    }

    public function login($email, $password) {
        $admin = $this->db->fetchOne("SELECT * FROM admins WHERE email = ?", [$email]);
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['name'] = $admin['name'];
            $_SESSION['role'] = $admin['role'];
            
            $this->db->conn->query("UPDATE admins SET last_login = NOW() WHERE id = " . $admin['id']);
            return true;
        }
        return false;
    }

    public function getDashboardStats() {
        $stats = [];
        $stats['total_rooms'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM rooms")['count'];
        $stats['available_rooms'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'Available'")['count'];
        $stats['booked_rooms'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM rooms WHERE status = 'Booked'")['count'];
        $stats['total_customers'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        $stats['new_residents_today'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")['count'];
        $stats['active_bookings'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM bookings WHERE status = 'Checked-In'")['count'];
        
        $revenue = $this->db->fetchOne("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'Paid'");
        $stats['total_revenue'] = $revenue['total'] ?? 0;
        
        $stats['pending_services'] = $this->db->fetchOne("SELECT COUNT(*) as count FROM service_orders WHERE status = 'Pending'")['count'];
        
        return $stats;
    }

    public function getAnalyticsData() {
        $analytics = [
            'daily' => [],
            'monthly' => [],
            'yearly' => []
        ];

        // 1. Daily Analytics (Last 7 Days)
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayName = date('D', strtotime("-$i days"));
            $res = $this->db->fetchOne("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'Paid' AND DATE(created_at) = ?", [$date]);
            $analytics['daily'][] = [
                'label' => strtoupper($dayName),
                'revenue' => (float)($res['total'] ?? 0)
            ];
        }

        // 2. Monthly Analytics (Last 6 Months)
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i month"));
            $monthName = date('M', strtotime("-$i month"));
            $res = $this->db->fetchOne("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'Paid' AND DATE_FORMAT(created_at, '%Y-%m') = ?", [$month]);
            $analytics['monthly'][] = [
                'label' => strtoupper($monthName),
                'revenue' => (float)($res['total'] ?? 0)
            ];
        }

        // 3. Yearly Analytics (Last 5 Years)
        for ($i = 4; $i >= 0; $i--) {
            $year = date('Y', strtotime("-$i years"));
            $res = $this->db->fetchOne("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'Paid' AND YEAR(created_at) = ?", [$year]);
            $analytics['yearly'][] = [
                'label' => $year,
                'revenue' => (float)($res['total'] ?? 0)
            ];
        }

        return $analytics;
    }

    public function getRecentBookings($limit = 5) {
        return $this->db->fetchAll("SELECT b.*, r.room_number FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id ORDER BY b.created_at DESC LIMIT $limit");
    }

    public function getRecentOrders($limit = 5) {
        $sql = "SELECT o.*, 
                       COALESCE(NULLIF(o.item_name, '0'), NULLIF(o.item_name, ''), s.service_name, 'Concierge Request') as service_name,
                       b.guest_name, r.room_number 
                FROM service_orders o 
                LEFT JOIN services s ON o.service_id = s.id 
                JOIN bookings b ON o.booking_id = b.id
                JOIN rooms r ON b.room_id = r.id
                ORDER BY o.ordered_at DESC LIMIT $limit";
        return $this->db->fetchAll($sql);
    }

    // Room Management
    public function getAllRooms() {
        return $this->db->fetchAll("SELECT * FROM rooms ORDER BY room_number ASC");
    }

    public function addRoom($data) {
        $query = "INSERT INTO rooms (room_number, room_type, price_per_night, status, image, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("ssdsss", $data['room_number'], $data['room_type'], $data['price'], $data['status'], $data['image'], $data['description']);
        return $stmt->execute();
    }

    public function updateRoom($id, $data) {
        $query = "UPDATE rooms SET room_number=?, room_type=?, price_per_night=?, status=?, description=? WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("ssdssi", $data['room_number'], $data['room_type'], $data['price'], $data['status'], $data['description'], $id);
        return $stmt->execute();
    }

    public function deleteRoom($id) {
        $query = "DELETE FROM rooms WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function updateRoomStatus($id, $status) {
        $query = "UPDATE rooms SET status=? WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    // Booking Management
    public function getAllBookings($userId = null, $search = '') {
        $query = "SELECT b.*, r.room_number, r.room_type, u.email as customer_email 
                  FROM bookings b 
                  LEFT JOIN rooms r ON b.room_id = r.id 
                  LEFT JOIN users u ON b.user_id = u.id 
                  WHERE 1=1";
        $params = [];
        
        if ($userId) {
            $query .= " AND b.user_id = ?";
            $params[] = $userId;
        }
        
        if ($search) {
            $query .= " AND (b.guest_name LIKE ? OR r.room_number LIKE ? OR u.email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY b.created_at DESC";
        return $this->db->fetchAll($query, $params);
    }

    public function getBookingById($id) {
        return $this->db->fetchOne("SELECT b.*, r.room_number, r.room_type, r.price_per_night, u.email as customer_email, u.phone as customer_phone
                                   FROM bookings b 
                                   LEFT JOIN rooms r ON b.room_id = r.id 
                                   LEFT JOIN users u ON b.user_id = u.id 
                                   WHERE b.id = ?", [$id]);
    }

    public function updateBookingStatus($id, $status) {
        $paymentUpdate = "";
        if ($status === 'Checked-Out') {
            $paymentUpdate = ", payment_status='Paid', actual_checkout=NOW()";
        }
        
        $query = "UPDATE bookings SET status=? $paymentUpdate WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        
        // Logical Room Triggers
        $booking = $this->db->fetchOne("SELECT room_id FROM bookings WHERE id = ?", [$id]);
        if ($booking && $booking['room_id']) {
            if ($status === 'Checked-In') {
                $this->updateRoomStatus($booking['room_id'], 'Booked');
            } elseif ($status === 'Checked-Out') {
                $this->updateRoomStatus($booking['room_id'], 'Needs Cleaning');
            } elseif ($status === 'Cancelled') {
                $this->updateRoomStatus($booking['room_id'], 'Available');
            }
        }
        
        return $stmt->execute();
    }

    public function deleteBooking($id) {
        // First get room_id to reset status if it was active
        $booking = $this->db->fetchOne("SELECT room_id, status FROM bookings WHERE id = ?", [$id]);
        if ($booking && $booking['room_id'] && ($booking['status'] == 'Booked' || $booking['status'] == 'Checked-In')) {
            $this->updateRoomStatus($booking['room_id'], 'Available');
        }
        
        $query = "DELETE FROM bookings WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getAvailableRooms() {
        return $this->db->fetchAll("SELECT * FROM rooms WHERE status = 'Available' ORDER BY room_number ASC");
    }

    public function manualBooking($data) {
        // 1. Check if user already exists based on email
        $user = $this->db->fetchOne("SELECT id FROM users WHERE email = ?", [$data['guest_email']]);
        $user_id = null;
        $is_new_user = false;
        $generated_password = '';

        if ($user) {
            $user_id = $user['id'];
            
            // 1b. Update existing user details to match latest manual entry
            $u_update = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
            $u_stmt = $this->db->conn->prepare($u_update);
            $u_stmt->bind_param("ssi", $data['guest_name'], $data['guest_phone'], $user_id);
            $u_stmt->execute();
            
            // Send Booking Confirmation for existing user
            $subject = "Residency Confirmation - " . $data['guest_name'];
            $message = "Your luxury residency at Grand Luxe has been manually initialized by our concierge.<br><br>
                        <strong>Suite:</strong> Suite " . $data['room_id'] . "<br>
                        <strong>Arrival:</strong> " . $data['check_in'] . "<br>
                        <strong>Departure:</strong> " . $data['check_out'] . "<br>
                        <strong>Total Investment:</strong> ₹" . $data['total_amount'] . "<br><br>
                        You can access your private dashboard using your existing credentials to manage this residency.";
            
            $this->sendThemedEmail($data['guest_email'], $subject, $message, 'Confirmation');
        } else {
            // 2. Create new user account
            $is_new_user = true;
            $generated_password = bin2hex(random_bytes(4)); // Generate 8-character random password
            $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
            
            $u_query = "INSERT INTO users (name, email, phone, password, status) VALUES (?, ?, ?, ?, 'Active')";
            $u_stmt = $this->db->conn->prepare($u_query);
            $u_stmt->bind_param("ssss", $data['guest_name'], $data['guest_email'], $data['guest_phone'], $hashed_password);
            
            if ($u_stmt->execute()) {
                $user_id = $this->db->conn->insert_id;
                
                // 3. Send Credentials via Themed Email
                $subject = "Welcome to Grand Luxe - Your Residency Credentials";
                $message = "Your luxury account has been initialized. You can now access your private dashboard using the following credentials:<br><br>
                            <strong>User ID (Email):</strong> " . $data['guest_email'] . "<br>
                            <strong>Access Password:</strong> " . $generated_password . "<br><br>
                            <strong>Residency Details:</strong><br>
                            <strong>Suite:</strong> Suite " . $data['room_id'] . "<br>
                            <strong>Dates:</strong> " . $data['check_in'] . " to " . $data['check_out'] . "<br><br>
                            Please change your password after your first login for protocol security.";
                
                $this->sendThemedEmail($data['guest_email'], $subject, $message, 'Activation');
            } else {
                return false; // User creation failed
            }
        }

        // 4. Insert Booking linked to user_id
        $query = "INSERT INTO bookings (user_id, guest_name, guest_email, guest_phone, room_id, check_in, check_out, total_amount, status, payment_status, id_proof_type, id_proof_number, permanent_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->conn->prepare($query);
        
        $status = $data['status'] ?? 'Confirmed';
        $payment_status = $data['payment_status'] ?? 'Pending';
        
        $stmt->bind_param("isssissdsssss", 
            $user_id,
            $data['guest_name'], 
            $data['guest_email'], 
            $data['guest_phone'], 
            $data['room_id'], 
            $data['check_in'], 
            $data['check_out'], 
            $data['total_amount'],
            $status,
            $payment_status,
            $data['id_proof_type'],
            $data['id_proof_number'],
            $data['address']
        );
        
        if ($stmt->execute()) {
            $this->updateRoomStatus($data['room_id'], 'Booked');
            return true;
        }
        return false;
    }

    // Service Management
    public function getAllServices() {
        return $this->db->fetchAll("SELECT * FROM services ORDER BY category, service_name");
    }

    public function getAllServiceOrders() {
        // Fetch Service Orders joined with rich metadata
        $sql = "SELECT 'Order' as type, o.id, 
                       COALESCE(NULLIF(o.item_name, '0'), NULLIF(o.item_name, ''), s.service_name, 'Concierge Request') as service_name, 
                       COALESCE(s.category, 'Culinary') as category,
                       COALESCE(b.guest_name, u.name, 'Premium Resident') as guest_name, 
                       COALESCE(r.room_number, o.room_number, 'N/A') as room_number,
                       o.quantity, o.total_price, o.status, o.items, o.ordered_at
                FROM service_orders o 
                LEFT JOIN services s ON o.service_id = s.id 
                LEFT JOIN bookings b ON o.booking_id = b.id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN rooms r ON b.room_id = r.id
                ORDER BY ordered_at DESC";
                
        return $this->db->fetchAll($sql);
    }

    public function updateServiceOrderStatus($id, $status) {
        $query = "UPDATE service_orders SET status=? WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function addService($data) {
        $query = "INSERT INTO services (service_name, description, price, category) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("ssds", $data['service_name'], $data['description'], $data['price'], $data['category']);
        return $stmt->execute();
    }

    // Customer Management
    public function getAllUsers($filter = '', $search = '') {
        $query = "SELECT * FROM users WHERE 1=1";
        $params = [];
        
        if ($filter === 'today') {
            $query .= " AND DATE(created_at) = CURDATE()";
        }
        
        if ($search) {
            $query .= " AND (name LIKE ? OR email LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $query .= " ORDER BY created_at DESC";
        return $this->db->fetchAll($query, $params);
    }

    public function getUserHistory($userId) {
        return $this->db->fetchAll("SELECT b.*, r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.user_id = ? ORDER BY b.created_at DESC", [$userId]);
    }

    public function toggleUserStatus($userId, $status) {
        $query = "UPDATE users SET status=? WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("si", $status, $userId);
        return $stmt->execute();
    }

    public function updateUser($userId, $data) {
        $query = "UPDATE users SET name=?, email=?, phone=?, status=? WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("ssssi", $data['name'], $data['email'], $data['phone'], $data['status'], $userId);
        return $stmt->execute();
    }

    public function resetUserPassword($userId) {
        $user = $this->db->fetchOne("SELECT email, name FROM users WHERE id = ?", [$userId]);
        if (!$user) return false;

        $generated_password = bin2hex(random_bytes(4));
        $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE users SET password=? WHERE id=?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("si", $hashed_password, $userId);
        
        if ($stmt->execute()) {
            $subject = "Security Update - Your New Access Code";
            $message = "Respected Guest, <br><br>As per your request or administrative protocol, your access credentials have been reset. <br><br>
                        <strong>New Password:</strong> " . $generated_password . "<br><br>
                        Please use this to login and update your password immediately.";
            
            $this->sendThemedEmail($user['email'], $subject, $message, 'Security');
            return $generated_password;
        }
        return false;
    }

    public function getUserById($id) {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }

    // Housekeeping Management
    public function getHousekeepingGrid() {
        // This grid shows rooms that need attention (dirty after checkout) 
        // OR rooms that have active guest requests
        return $this->db->fetchAll("SELECT r.id as room_id, r.room_number, r.status as room_status, 
                                          h.status as clean_status, h.last_updated,
                                          (SELECT service_type FROM housekeeping_requests WHERE room_number = r.room_number AND status = 'Pending' LIMIT 1) as guest_request
                                   FROM rooms r 
                                   LEFT JOIN housekeeping h ON r.id = h.room_id 
                                   WHERE r.status = 'Needs Cleaning' OR r.status = 'Maintenance' 
                                      OR r.room_number IN (SELECT room_number FROM housekeeping_requests WHERE status = 'Pending')
                                   ORDER BY r.room_number ASC");
    }

    public function getPendingHousekeepingRequests() {
        return $this->db->fetchAll("SELECT h.*, u.name as guest_name 
                                   FROM housekeeping_requests h 
                                   JOIN users u ON h.user_id = u.id 
                                   WHERE h.status = 'Pending' OR h.status = 'In Progress'
                                   ORDER BY h.created_at DESC");
    }

    public function updateHousekeepingStatus($id, $status) {
        $query = "UPDATE housekeeping_requests SET status = ? WHERE id = ?";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }

    public function updateCleaningProtocol($roomId, $status) {
        // Update Housekeeping Table
        $check = $this->db->fetchOne("SELECT id FROM housekeeping WHERE room_id = ?", [$roomId]);
        if($check) {
            $this->db->conn->query("UPDATE housekeeping SET status = '$status', last_updated = NOW() WHERE room_id = $roomId");
        } else {
            $this->db->conn->query("INSERT INTO housekeeping (room_id, status) VALUES ($roomId, '$status')");
        }

        // Synchronize with Room Status
        if($status === 'Cleaned') {
            $this->updateRoomStatus($roomId, 'Available');
            
            // Also mark any guest requests for this room as Completed if we just cleaned it
            $room = $this->db->fetchOne("SELECT room_number FROM rooms WHERE id = ?", [$roomId]);
            if ($room) {
                $this->db->conn->query("UPDATE housekeeping_requests SET status = 'Completed' WHERE room_number = '{$room['room_number']}' AND status != 'Cancelled'");
            }
        } elseif($status === 'Cleaning') {
            $this->updateRoomStatus($roomId, 'Maintenance');
        }
        return true;
    }

    // Settings Management
    public function getSettings() {
        $settings = $this->db->fetchOne("SELECT * FROM settings WHERE id = 1");
        if(!$settings) {
            $this->db->conn->query("INSERT INTO settings (id, hotel_name) VALUES (1, 'Grand Luxe')");
            return $this->db->fetchOne("SELECT * FROM settings WHERE id = 1");
        }
        return $settings;
    }

    public function updateSettings($data) {
        $query = "UPDATE settings SET hotel_name=?, contact_email=?, contact_phone=?, currency=?, address=? WHERE id=1";
        $stmt = $this->db->conn->prepare($query);
        $stmt->bind_param("sssss", $data['hotel_name'], $data['contact_email'], $data['contact_phone'], $data['currency'], $data['address']);
        return $stmt->execute();
    }

    // Email Template Engine - Using PHPMailer for Real Delivery
    public function sendThemedEmail($to, $subject, $message, $type = 'confirmation') {
        $settings = $this->getSettings();
        $hotelName = $settings['hotel_name'];
        
        $phpmailer_path = __DIR__ . '/../php/PHPMailer/src/';
        
        if (file_exists($phpmailer_path . 'PHPMailer.php')) {
            require_once $phpmailer_path . 'Exception.php';
            require_once $phpmailer_path . 'PHPMailer.php';
            require_once $phpmailer_path . 'SMTP.php';

            $mail = new PHPMailer(true);

            try {
                // Server Settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'grandluxe.luxury@gmail.com';
                $mail->Password   = 'hzpe obze lbbi anuu'; // Verified App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Recipients
                $mail->setFrom('jaydipramoliya942@gmail.com', $hotelName);
                $mail->addAddress($to);

                // Content
                $mail->isHTML(true);
                $mail->Subject = "$hotelName - $subject";

                $template = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);'>
                    <div style='background: linear-gradient(to right, #8b5cf6, #f43f5e); padding: 40px; text-align: center; color: white;'>
                        <h1 style='margin: 0; font-size: 28px; letter-spacing: 2px; text-transform: uppercase;'>$hotelName</h1>
                        <p style='text-transform: uppercase; font-size: 10px; opacity: 0.8; letter-spacing: 4px; font-weight: bold; margin-top: 10px;'>$type Protocol Activated</p>
                    </div>
                    <div style='padding: 40px; color: #333; background: #fff;'>
                        <h2 style='color: #8b5cf6; font-size: 20px; margin-bottom: 20px;'>$subject</h2>
                        <div style='line-height: 1.8; font-size: 15px; color: #444;'>
                            $message
                        </div>
                        <div style='margin-top: 40px; padding: 25px; background: #f9fafb; border-radius: 15px; border: 1px dashed #e5e7eb;'>
                            <p style='margin: 0; font-size: 13px; color: #6b7280;'>
                                <strong>System Note:</strong> This is an automated security protocol. Please do not reply to this email. For assistance, contact our 24/7 concierge.
                            </p>
                        </div>
                        <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #9ca3af; text-align: center;'>
                            <p>&copy; 2026 $hotelName. The Pinnacle of Luxury. All rights reserved.</p>
                        </div>
                    </div>
                </div>";
                
                $mail->Body = $template;
                return $mail->send();
            } catch (Exception $e) {
                error_log("Email sending failed: " . $mail->ErrorInfo);
                return false;
            }
        }
        return false; 
    }
}
?>
