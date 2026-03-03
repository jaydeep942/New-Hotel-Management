<?php
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

    public function getRecentBookings($limit = 5) {
        return $this->db->fetchAll("SELECT b.*, r.room_number FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id ORDER BY b.created_at DESC LIMIT $limit");
    }

    public function getRecentOrders($limit = 5) {
        return $this->db->fetchAll("SELECT o.*, s.service_name, b.guest_name, r.room_number 
                                   FROM service_orders o 
                                   JOIN services s ON o.service_id = s.id 
                                   JOIN bookings b ON o.booking_id = b.id
                                   JOIN rooms r ON b.room_id = r.id
                                   ORDER BY o.ordered_at DESC LIMIT $limit");
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

    // Service Management
    public function getAllServices() {
        return $this->db->fetchAll("SELECT * FROM services ORDER BY category, service_name");
    }

    public function getAllServiceOrders() {
        return $this->db->fetchAll("SELECT o.*, s.service_name, s.category, b.guest_name, r.room_number 
                                   FROM service_orders o 
                                   JOIN services s ON o.service_id = s.id 
                                   JOIN bookings b ON o.booking_id = b.id
                                   JOIN rooms r ON b.room_id = r.id
                                   ORDER BY o.ordered_at DESC");
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

    // Housekeeping Management
    public function getHousekeepingGrid() {
        return $this->db->fetchAll("SELECT r.id as room_id, r.room_number, r.status as room_status, h.status as clean_status, h.last_updated 
                                   FROM rooms r 
                                   LEFT JOIN housekeeping h ON r.id = h.room_id 
                                   WHERE r.status = 'Needs Cleaning' OR r.status = 'Maintenance'
                                   ORDER BY r.room_number ASC");
    }

    public function updateCleaningProtocol($roomId, $status) {
        // Update Housekeeping Table
        $check = $this->db->fetchOne("SELECT id FROM housekeeping WHERE room_id = ?", [$roomId]);
        if($check) {
            $this->db->conn->query("UPDATE housekeeping SET status = '$status' WHERE room_id = $roomId");
        } else {
            $this->db->conn->query("INSERT INTO housekeeping (room_id, status) VALUES ($roomId, '$status')");
        }

        // Synchronize with Room Status
        if($status === 'Cleaned') {
            $this->updateRoomStatus($roomId, 'Available');
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

    // Email Template Engine (Simulated)
    public function sendThemedEmail($to, $subject, $message, $type = 'confirmation') {
        $settings = $this->getSettings();
        $hotelName = $settings['hotel_name'];
        
        $template = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; border-radius: 20px; overflow: hidden;'>
            <div style='background: linear-gradient(to right, #8b5cf6, #f43f5e); padding: 40px; text-align: center; color: white;'>
                <h1 style='margin: 0; font-size: 28px; letter-spacing: 2px;'>$hotelName</h1>
                <p style='text-transform: uppercase; font-size: 10px; opacity: 0.8; letter-spacing: 4px; font-weight: bold;'>$type Protocol</p>
            </div>
            <div style='padding: 40px; color: #333;'>
                <h2 style='color: #8b5cf6;'>$subject</h2>
                <p style='line-height: 1.6;'>$message</p>
                <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999;'>
                    <p>&copy; 2026 $hotelName. All rights reserved.</p>
                </div>
            </div>
        </div>";
        
        return true; 
    }
}
?>
