<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$conn = require_once __DIR__ . '/config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch full user details
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

$_SESSION['name'] = $user_data['name'];
$_SESSION['email'] = $user_data['email'];
$profile_photo = $user_data['profile_photo'];
$phone = $user_data['phone'] ?? '';
$nationality = $user_data['nationality'] ?? '';
$dob = $user_data['dob'] ?? '';
$created_at = $user_data['created_at'];

// SECURE ACCESS CHECK: Only allow checked-in guests to ORDER
$booking_check_sql = "SELECT * FROM bookings WHERE user_id = ? AND status IN ('Confirmed', 'Checked-In') AND CURRENT_DATE BETWEEN check_in AND check_out LIMIT 1";
$check_stmt = $conn->prepare($booking_check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$booking_status = $check_stmt->get_result()->fetch_assoc();
$canUseServices = $booking_status ? true : false;

// Fetch the latest upcoming booking for this user
$sql = "SELECT b.*, r.room_type, r.room_number 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.user_id = ? AND b.status IN ('Confirmed', 'Checked-In') 
        ORDER BY b.check_in ASC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$booking = $result->fetch_assoc();

// Initialize variables with defaults for users without bookings
$hasBooking = false;
$bookingLabel = "No active booking";
$isLive = false;
$roomType = "No active residency";
$currentBookingID = "N/A";
$suiteNumber = "N/A";
$check_in = "-- -- --";
$check_out = "-- -- --";
$total_price = 0;
$progressPercent = 0;
$currentDay = 1;
$totalNights = 1;

if ($booking) {
    $hasBooking = true;
    $currentBookingID = "#LX-" . str_pad($booking['id'], 4, '0', STR_PAD_LEFT);
    $roomType = $booking['room_type'] . " Suite";
    $suiteNumber = $booking['room_number'];
    $check_in = date('d M Y', strtotime($booking['check_in']));
    $check_out = date('d M Y', strtotime($booking['check_out']));
    $total_price = $booking['total_price'];
    
    // Check if booking is active (today is within stay) or upcoming
    $today = new DateTime('today');
    $cin_date = new DateTime($booking['check_in']);
    $cout_date = new DateTime($booking['check_out']);
    
    if ($today >= $cin_date && $today <= $cout_date) {
        $bookingLabel = "Active Booking";
        $isLive = true;
    } else {
        $bookingLabel = "Upcoming Booking";
        $isLive = false;
    }

    // Calculate progress
    $totalNights = $cin_date->diff($cout_date)->days;
    $totalNights = $totalNights > 0 ? $totalNights : 1;
    $currentDay = $cin_date->diff($today)->days + 1;
    
    if ($currentDay > $totalNights) $currentDay = $totalNights;
    if ($currentDay < 1) $currentDay = 1;
    
    $progressPercent = ($isLive) ? ($currentDay / $totalNights) * 100 : 0;
}

// Fetch recent service orders
$orders_sql = "SELECT * FROM service_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_res = $orders_stmt->get_result();
$orders = [];
while($row = $orders_res->fetch_assoc()){
    $orders[] = $row;
}
// Fetch service total for active booking to show running ledger
$running_service_total = 0;
if($hasBooking) {
    try {
        $orders_total_sql = "SELECT SUM(total_price) as service_total FROM service_orders 
                             WHERE user_id = ? AND room_number = ? 
                             AND created_at >= ? AND status != 'Cancelled'";
        $orders_total_stmt = $conn->prepare($orders_total_sql);
        $start_date = $booking['check_in'] . " 00:00:00";
        $orders_total_stmt->bind_param("iss", $user_id, $booking['room_number'], $start_date);
        $orders_total_stmt->execute();
        $orders_total_res = $orders_total_stmt->get_result()->fetch_assoc();
        $running_service_total = $orders_total_res['service_total'] ?? 0;
    } catch (Exception $e) {
        $running_service_total = 0;
    }
}
$cumulative_ledger = $total_price + $running_service_total;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Grand Luxe Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#D4AF37',
                        cream: '#F8F5F0',
                        maroon: '#6A1E2D',
                        darkMaroon: '#4A1520',
                        teal: '#2CA6A4',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        :root {
            --gold: #D4AF37;
            --cream: #F8F5F0;
            --teal: #2CA6A4;
            --maroon: #6A1E2D;
            --soft-white: #FFFFFF;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--cream);
            color: #333;
        }

        .maroon-text { color: var(--maroon); }
        .gold-text { color: var(--gold); }
        .teal-text { color: var(--teal); }

        .sidebar-link {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-link.active {
            background: var(--maroon);
            color: white;
            box-shadow: 0 10px 20px rgba(106, 30, 45, 0.15);
        }
        .sidebar-link:not(.active):hover {
            background-color: rgba(106, 30, 45, 0.05);
            transform: translateX(5px);
        }

        .shimmer-card {
            position: relative;
            overflow: hidden;
        }
        .shimmer-card::after {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shimmer 3s infinite;
        }
        @keyframes shimmer {
            0% { transform: translate(-100%, -100%) rotate(45deg); }
            100% { transform: translate(100%, 100%) rotate(45deg); }
        }

        .premium-shadow {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
        }

        .gradient-maroon {
            background: linear-gradient(135deg, #6A1E2D 0%, #4A141E 100%) !important;
            color: white !important;
        }

        .gradient-teal {
            background: linear-gradient(135deg, #1D6A68 0%, #144A48 100%) !important;
            color: white !important;
        }

        .gradient-pearl {
            background: linear-gradient(135deg, #FFFFFF 0%, #F5F0E6 100%) !important;
            border: 1px solid rgba(212, 175, 55, 0.2) !important;
        }

        .gradient-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #B8962E 100%) !important;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* Simple Premium Navbar Styles */
        .nav-link {
            position: relative;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .nav-link span {
            position: relative;
            z-index: 20;
        }

        /* Simple Centered Pill Background */
        .nav-link::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            background: var(--maroon);
            border-radius: 12px;
            transform: translate(-50%, -50%) scale(0.6, 0.4);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 5;
        }

        .nav-link:hover::before {
            transform: translate(-50%, -50%) scale(0.8, 0.6);
            opacity: 0.05;
        }

        .nav-link.active::before {
            transform: translate(-50%, -50%) scale(0.85, 0.65);
            opacity: 1;
            background: var(--maroon);
            box-shadow: 0 8px 20px rgba(106, 30, 45, 0.15);
        }

        .nav-link.active {
            color: white !important;
        }

        .nav-link:not(.active):hover {
            color: var(--maroon) !important;
        }

        /* Animations */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide { animation: slideIn 0.6s ease-out forwards; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* Premium Modal & Inputs */
        .modal-active { overflow: hidden; }
        .input-group input, .input-group select {
            width: 100%;
            padding: 14px 20px;
            background: #F9FAFB;
            border: 2px solid transparent;
            border-radius: 16px;
            transition: all 0.3s;
            outline: none;
            font-size: 14px;
        }
        .input-group input:focus, .input-group select:focus {
            background: white;
            border-color: var(--maroon);
            box-shadow: 0 8px 20px rgba(106, 30, 45, 0.05);
        }
    </style>
</head>
<body class="bg-[#F8F5F0] min-h-screen">

    <!-- Main Content (Full Width now) -->
    <main class="min-h-screen">
        <!-- New Primary Navbar (Replaces Sidebar) -->
        <nav class="glass-nav sticky top-0 z-[60] premium-shadow border-b border-white/20">
            <div class="max-w-[1600px] mx-auto px-6 py-4 flex items-center justify-between">
                <!-- Brand Section -->
                <div class="flex items-center space-x-12">
                    <div class="flex flex-col">
                        <h1 class="text-2xl font-bold tracking-tighter maroon-text" style="font-family: 'Playfair Display', serif;">
                            GRAND<span class="gold-text">LUXE</span>
                        </h1>
                        <p class="text-[9px] uppercase tracking-[3px] font-bold text-gray-400 mt-1">EXCELLENCE DEFINED</p>
                    </div>

                    <!-- Navigation Links (Desktop) -->
                    <div class="hidden xl:flex items-center space-x-2">
                        <a href="customer-dashboard.php" class="nav-link active flex items-center px-7 py-4 rounded-2xl text-[15px] font-bold transition-all">
                            <span>Dashboard</span>
                        </a>
                        <a href="book-room.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Book Room</span>
                        </a>
                        <a href="services.php" class="nav-link flex items-center px-7 py-4 rounded-2xl <?php echo $canUseServices ? 'text-gray-500 hover:text-maroon' : 'text-gray-400'; ?> text-[15px] font-bold transition-all">
                            <span>Services</span>
                        </a>
                        <a href="cleaning.php" class="nav-link flex items-center px-7 py-4 rounded-2xl <?php echo $canUseServices ? 'text-gray-500 hover:text-maroon' : 'text-gray-400'; ?> text-[15px] font-bold transition-all">
                            <span>Cleaning</span>
                        </a>
                        <a href="history.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>History</span>
                        </a>
                        <a href="feedback.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Feedback</span>
                        </a>
                        <a href="complaints.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Complaints</span>
                        </a>
                    </div>
                </div>

                <!-- Right Section: Booking & Profile -->
                <div class="flex items-center space-x-8">
                    <!-- Current Booking Summary (Compact) -->
                    <div class="hidden lg:flex items-center space-x-3 bg-maroon/5 py-2 px-4 rounded-xl border border-maroon/10">
                        <i class="fas fa-key maroon-text text-xs opacity-50"></i>
                        <div class="text-right">
                            <p class="text-[8px] uppercase font-black text-gray-400 tracking-tighter">Current Stay</p>
                            <p class="font-bold text-[10px] maroon-text"><?php echo $hasBooking ? $currentBookingID : 'No active residency'; ?></p>
                        </div>
                    </div>

                    <!-- Profile & Dropdown -->
                    <div class="relative group">
                        <div class="flex items-center space-x-4 cursor-pointer py-1">
                            <div class="hidden sm:block text-right">
                                <p class="font-bold text-sm maroon-text leading-none"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                                <p class="text-[9px] uppercase font-black text-gold tracking-widest mt-1 opacity-70">Premium Member</p>
                            </div>
                            <div class="w-12 h-12 rounded-2xl overflow-hidden border-2 border-gold/20 p-1 transition-all duration-500 group-hover:border-gold/50 group-hover:rotate-6 shadow-sm bg-white">
                                <?php if ($profile_photo): ?>
                                    <img src="<?php echo $profile_photo; ?>" class="w-full h-full object-cover rounded-xl" alt="Profile">
                                <?php else: ?>
                                    <div class="w-full h-full bg-maroon rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Premium Dropdown Menu -->
                        <div class="absolute right-0 top-full pt-4 opacity-0 invisible translate-y-4 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 transition-all duration-300 z-[100] w-64">
                            <div class="bg-white rounded-[32px] shadow-2xl border border-gray-100 overflow-hidden premium-shadow p-3">
                                <div class="space-y-1">
                                    <button onclick="openProfileModal('profile-info')" class="w-full flex items-center space-x-3 p-4 rounded-2xl text-gray-600 hover:bg-maroon/5 hover:text-maroon transition-all group/item text-left">
                                        <div class="w-8 h-8 rounded-xl bg-maroon/5 flex items-center justify-center text-maroon group-hover/item:scale-110 transition-transform">
                                            <i class="fas fa-id-card text-xs"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold">Personal Details</span>
                                            <span class="text-[9px] uppercase tracking-wider text-gray-400">Identity & Contact</span>
                                        </div>
                                    </button>

                                    <button onclick="openProfileModal('security-settings')" class="w-full flex items-center space-x-3 p-4 rounded-2xl text-gray-600 hover:bg-maroon/5 hover:text-maroon transition-all group/item text-left">
                                        <div class="w-8 h-8 rounded-xl bg-maroon/5 flex items-center justify-center text-maroon group-hover/item:scale-110 transition-transform">
                                            <i class="fas fa-shield-halved text-xs"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold">Security & Password</span>
                                            <span class="text-[9px] uppercase tracking-wider text-gray-400">Lock & Key Access</span>
                                        </div>
                                    </button>

                                    <div class="h-px bg-gray-100 mx-4 my-2"></div>

                                    <a href="php/logout.php" class="w-full flex items-center space-x-3 p-4 rounded-2xl text-red-500 hover:bg-red-50 transition-all group/item">
                                        <div class="w-8 h-8 rounded-xl bg-red-100/50 flex items-center justify-center text-red-500 group-hover/item:scale-110 transition-transform">
                                            <i class="fas fa-sign-out-alt text-xs"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold">Sign Out</span>
                                            <span class="text-[9px] uppercase tracking-wider text-red-400 opacity-70">End Current Session</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Menu Trigger -->
                    <div class="xl:hidden">
                        <button onclick="toggleMobileMenu()" class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center maroon-text">
                            <i class="fas fa-bars-staggered"></i>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Navigation Drawer -->
        <div id="mobileMenu" class="fixed inset-0 z-[100] hidden">
            <div id="mobileOverlay" onclick="toggleMobileMenu()" class="absolute inset-0 bg-maroon/20 backdrop-blur-sm transition-opacity duration-300 opacity-0"></div>
            <div id="mobileDrawer" class="absolute inset-y-0 right-0 w-80 bg-white shadow-2xl p-8 transform translate-x-full transition-transform duration-300 overflow-y-auto">
                <div class="flex justify-between items-center mb-10">
                    <h2 class="text-xl font-bold maroon-text">Menu</h2>
                    <button onclick="toggleMobileMenu()" class="text-gray-400"><i class="fas fa-times text-xl"></i></button>
                </div>
                <nav class="space-y-3">
                    <a href="customer-dashboard.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl font-bold">
                        <i class="fas fa-th-large"></i><span>Dashboard</span>
                    </a>
                    <a href="book-room.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-bed"></i><span>Book Room</span>
                    </a>
                    <a href="services.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-concierge-bell"></i><span>Services</span>
                    </a>
                    <a href="cleaning.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-broom"></i><span>Cleaning Request</span>
                    </a>
                    <a href="feedback.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-star"></i><span>Feedback</span>
                    </a>
                    <a href="complaints.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-exclamation-circle"></i><span>Complaints</span>
                    </a>
                    <a href="history.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-history"></i><span>Booking History</span>
                    </a>
                </nav>
            </div>
        </div>

        <script>
            function toggleMobileMenu() {
                const drawer = document.getElementById('mobileDrawer');
                const overlay = document.getElementById('mobileOverlay');
                const menu = document.getElementById('mobileMenu');
                
                if(menu.classList.contains('hidden')) {
                    menu.classList.remove('hidden');
                    setTimeout(() => {
                        overlay.classList.add('opacity-100');
                        drawer.classList.remove('translate-x-full');
                    }, 10);
                } else {
                    overlay.classList.remove('opacity-100');
                    drawer.classList.add('translate-x-full');
                    setTimeout(() => menu.classList.add('hidden'), 300);
                }
            }
        </script>

        <div class="max-w-[1600px] mx-auto p-4 md:p-8">
            <!-- Dashboard Home Content -->
            <div class="animate-slide">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Welcome Back, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                    <p class="text-gray-500 mt-1 text-sm md:text-base">Here is a summary of your private suite status.</p>
                </div>
                <div class="text-left sm:text-right">
                    <p id="live-clock" class="text-lg font-black maroon-text tracking-tight animate-fade"></p>
                    <p id="live-date" class="text-[10px] uppercase tracking-[3px] font-bold text-gray-400 mt-1"></p>
                </div>
                </div>
            </div>

            <script>
                function updateTime() {
                    const now = new Date();
                    const optionsTime = { 
                        hour: '2-digit', 
                        minute: '2-digit', 
                        second: '2-digit',
                        hour12: true 
                    };
                    const optionsDate = { 
                        weekday: 'long', 
                        day: 'numeric', 
                        month: 'long', 
                        year: 'numeric' 
                    };
                    
                    document.getElementById('live-clock').innerText = now.toLocaleTimeString('en-US', optionsTime);
                    document.getElementById('live-date').innerText = now.toLocaleDateString('en-US', optionsDate);
                }
                setInterval(updateTime, 1000);
                updateTime();
            </script>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div class="gradient-maroon p-8 rounded-[32px] text-white premium-shadow hover:scale-110 transition-transform duration-500 relative overflow-hidden group shimmer-card">
                    <i class="fas fa-bookmark absolute -right-4 -bottom-4 text-8xl text-white/5 opacity-40 group-hover:scale-110 transition-transform"></i>
                    <p class="text-white/60 uppercase tracking-widest text-[9px] font-black mb-2"><?php echo $bookingLabel; ?></p>
                    <h3 class="text-3xl font-black italic tracking-tighter text-[#F8F5F0]" style="font-family: 'Playfair Display', serif;"><?php echo $currentBookingID; ?></h3>
                    <?php if($hasBooking): ?>
                    <div class="mt-8 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <?php if($isLive): ?>
                            <span class="w-2.5 h-2.5 bg-green-400 rounded-full animate-pulse shadow-[0_0_15px_rgba(74,222,128,0.8)]"></span>
                            <span class="text-[9px] font-black uppercase tracking-widest text-white/90">Resident Active</span>
                            <?php else: ?>
                            <span class="w-2.5 h-2.5 bg-gold rounded-full opacity-60"></span>
                            <span class="text-[9px] font-black uppercase tracking-widest text-white/60">Upcoming Stay</span>
                            <?php endif; ?>
                        </div>
                        <?php if($isLive): ?>
                        <button onclick="initiateCheckout(<?php echo $booking['id']; ?>)" class="px-6 py-2.5 bg-gold text-maroon hover:bg-white hover:text-maroon rounded-full text-[9px] font-black tracking-[2px] uppercase shadow-lg transition-all duration-300 transform group-hover:scale-105">CHECK-OUT</button>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <a href="book-room.php" class="mt-8 inline-block text-[10px] font-black uppercase tracking-widest text-gold hover:text-white transition-colors">Start Your Journey →</a>
                    <?php endif; ?>
                </div>

                <div class="bg-white p-8 rounded-[32px] premium-shadow border border-gray-50 hover:scale-105 transition-transform duration-500 group relative">
                     <i class="fas fa-couch absolute -right-4 -bottom-4 text-8xl text-gray-50 group-hover:scale-110 transition-transform"></i>
                    <p class="text-gray-400 uppercase tracking-widest text-[10px] font-bold mb-2">Room Type</p>
                    <h3 class="text-xl font-bold maroon-text leading-tight"><?php echo $roomType; ?></h3>
                    <p class="mt-4 text-gold font-bold text-[10px] uppercase tracking-widest opacity-70"><?php echo $hasBooking ? 'Confirmed Resident' : 'Ready for you'; ?></p>
                </div>

                <div class="bg-white p-8 rounded-[32px] premium-shadow border border-gray-50 hover:scale-105 transition-transform duration-500 group relative">
                    <i class="fas fa-calendar-check absolute -right-4 -bottom-4 text-8xl text-gray-50 group-hover:scale-110 transition-transform"></i>
                    <p class="text-gray-400 uppercase tracking-widest text-[10px] font-bold mb-2">Check-In</p>
                    <h3 class="text-2xl font-black maroon-text leading-tight"><?php echo $check_in; ?></h3>
                    <p class="mt-4 text-gold font-bold text-[10px] uppercase tracking-widest opacity-60">After 12:00 PM</p>
                </div>

                <div class="gradient-gold p-8 rounded-[32px] text-maroon premium-shadow hover:scale-110 transition-transform duration-500 group relative overflow-hidden">
                    <i class="fas fa-door-open absolute -right-4 -bottom-4 text-8xl text-maroon/5 group-hover:scale-110 transition-transform"></i>
                    <p class="text-maroon/60 uppercase tracking-widest text-[10px] font-black mb-2">Check-Out</p>
                    <h3 class="text-2xl font-black maroon-text leading-tight drop-shadow-sm"><?php echo $check_out; ?></h3>
                    <?php if($hasBooking): ?>
                    <p class="mt-4 text-maroon font-black text-[10px] uppercase tracking-widest opacity-60 flex items-center">
                        <i class="fas fa-moon mr-2 text-[8px]"></i><?php echo $totalNights; ?> Nights Duration
                    </p>
                    <?php else: ?>
                    <p class="mt-4 text-maroon font-black text-[10px] uppercase tracking-widest opacity-40 italic">Plan your getaway</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-12 mb-12">
                <!-- Status & Progress -->
                <div class="lg:col-span-2 bg-white rounded-[40px] p-10 premium-shadow">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h4 class="text-2xl font-black maroon-text leading-tight" style="font-family: 'Playfair Display', serif;">
                                <?php echo ($isLive) ? 'Residency Progress' : 'Suite Preparation'; ?>
                            </h4>
                            <p class="text-[10px] font-black uppercase tracking-[2px] text-gold mt-1">
                                <?php echo ($isLive) ? 'Live Status Tracking' : 'Excellence in progress'; ?>
                            </p>
                        </div>
                        <?php if($hasBooking && $isLive): ?>
                        <div class="text-right">
                            <span class="px-4 py-1.5 bg-maroon text-white rounded-full text-[9px] font-black uppercase tracking-widest">Day <?php echo $currentDay; ?> of <?php echo $totalNights; ?></span>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 bg-gold rounded-full animate-ping"></span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-maroon">V.I.P Selection</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($isLive): ?>
                    <div class="relative w-full bg-gray-100 h-3 rounded-full overflow-hidden mb-10 p-[2px]">
                        <div class="bg-gradient-to-r from-maroon via-gold to-teal h-full rounded-full transition-all duration-[2000ms] relative" style="width: <?php echo $progressPercent; ?>%">
                            <div class="absolute inset-x-0 top-0 h-1/2 bg-white/20"></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-2 gap-4 mb-10">
                        <div class="p-4 rounded-2xl bg-cream border border-gold/10 flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-gold shadow-sm">
                                <i class="fas fa-sparkles"></i>
                            </div>
                            <div>
                                <p class="text-[8px] font-black uppercase text-gray-400">Sanitization</p>
                                <p class="text-[10px] font-bold maroon-text">Certified & Secured</p>
                            </div>
                        </div>
                        <div class="p-4 rounded-2xl bg-cream border border-gold/10 flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-teal shadow-sm">
                                <i class="fas fa-gift"></i>
                            </div>
                            <div>
                                <p class="text-[8px] font-black uppercase text-gray-400">Welcome Gift</p>
                                <p class="text-[10px] font-bold maroon-text">Suite Ready</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Residency Ledger -->
                        <div class="group p-6 rounded-[32px] bg-maroon/5 border border-maroon/10 hover:bg-maroon hover:border-maroon transition-all duration-500 cursor-pointer">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center text-maroon shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <span class="text-[8px] font-black uppercase tracking-widest text-maroon/50 group-hover:text-white/50">Running Total</span>
                            </div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest group-hover:text-white/70">Residency Ledger</p>
                            <h5 class="text-xl font-black maroon-text mt-1 group-hover:text-white transition-colors">₹<?php echo number_format($cumulative_ledger, 0); ?></h5>
                            <div class="mt-3 overflow-hidden h-1 bg-maroon/10 rounded-full">
                                <div class="bg-maroon h-full group-hover:bg-gold w-3/4 transition-all"></div>
                            </div>
                        </div>

                        <!-- Concierge Desk -->
                        <div class="group p-6 rounded-[32px] bg-gold/5 border border-gold/10 hover:bg-gold hover:border-gold transition-all duration-500 cursor-pointer" onclick="window.location.href='services.php'">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center text-gold shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    <span class="text-[8px] font-black uppercase tracking-widest text-gold group-hover:text-white">Live</span>
                                </div>
                            </div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest group-hover:text-white/70">Concierge Desk</p>
                            <h5 class="text-xl font-black gold-text mt-1 group-hover:text-white transition-colors">On-Call Ready</h5>
                            <p class="text-[9px] text-gold/60 group-hover:text-white/80 mt-1">Direct suite assistance active</p>
                        </div>

                        <!-- Smart Climate -->
                        <div class="group p-6 rounded-[32px] bg-teal/5 border border-teal/10 hover:bg-teal hover:border-teal transition-all duration-500 relative overflow-hidden">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center text-teal shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-wind"></i>
                                </div>
                                <span class="text-[8px] font-black uppercase tracking-widest text-teal group-hover:text-white">Automated</span>
                            </div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest group-hover:text-white/70">Suite Climate</p>
                            <div class="flex items-baseline space-x-1 mt-1">
                                <h5 class="text-2xl font-black text-teal group-hover:text-white transition-colors">22</h5>
                                <span class="text-xs font-bold text-teal/50 group-hover:text-white/50">&deg;C</span>
                            </div>
                            <p class="text-[9px] text-teal/60 group-hover:text-white/80 mt-1 italic">Optimized for rest</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Service Orders -->
                <div class="bg-white rounded-[40px] p-10 premium-shadow overflow-hidden relative">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h4 class="text-xl font-bold maroon-text">Recent Orders</h4>
                            <a href="orders.php" class="text-[9px] font-black text-gold uppercase tracking-[2px] hover:underline mt-1 block">View Order Archive</a>
                        </div>
                        <?php if($canUseServices): ?>
                            <a href="services.php?service=dining" class="px-4 py-2 bg-maroon/5 text-maroon hover:bg-maroon hover:text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Order Now</a>
                        <?php else: ?>
                            <button onclick="showPremiumMessage('Access Restricted', 'Service features activate upon your arrival and check-in.', 'error')" class="px-4 py-2 bg-gray-100 text-gray-400 cursor-not-allowed rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Order Now</button>
                        <?php endif; ?>
                    </div>
                    
                    <div id="recentOrdersContainer" class="space-y-6">
                        <?php if(empty($orders)): ?>
                            <div class="flex flex-col items-center justify-center py-10 opacity-30">
                                <i class="fas fa-utensils text-5xl mb-4"></i>
                                <p class="text-xs font-bold uppercase tracking-widest">No recent orders</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($orders as $order): 
                                $statusColor = 'bg-gray-100 text-gray-500';
                                if($order['status'] == 'Pending') $statusColor = 'bg-gold/10 text-gold';
                                if($order['status'] == 'Preparing') $statusColor = 'bg-teal/10 text-teal';
                                if($order['status'] == 'Delivered') $statusColor = 'bg-green-50 text-green-600';
                                if($order['status'] == 'Cancelled') $statusColor = 'bg-red-50 text-red-600';
                                
                                $items = json_decode($order['items'], true);
                                $itemNames = array_map(function($i) { return $i['name']; }, $items);
                                $summary = implode(', ', $itemNames);
                            ?>
                                <div class="group flex justify-between items-start border-b border-gray-50 pb-6 last:border-0 last:pb-0 animate-fade-in hover:bg-gray-50/50 p-2 rounded-2xl transition-colors">
                                    <div class="flex items-start space-x-4">
                                        <div class="w-10 h-10 rounded-xl bg-maroon/5 flex items-center justify-center text-maroon group-hover:scale-110 transition-transform">
                                            <i class="fas <?php echo (stripos($summary, 'Coffee') !== false || stripos($summary, 'Soda') !== false) ? 'fa-coffee' : 'fa-utensils'; ?> text-xs"></i>
                                        </div>
                                        <div class="max-w-[140px]">
                                            <p class="text-xs font-black maroon-text truncate"><?php echo $summary; ?></p>
                                            <p class="text-[9px] font-bold text-gray-400 mt-1 uppercase tracking-tighter"><?php echo date('d M, h:i A', strtotime($order['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[11px] font-black maroon-text mb-2 tracking-tight">₹<?php echo number_format($order['total_price'], 0); ?></p>
                                        <span class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest <?php echo $statusColor; ?> shadow-sm">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Manage Profile Modal -->
        <div id="profileModal" class="fixed inset-0 z-[100] hidden">
            <div id="modalOverlay" class="absolute inset-0 bg-maroon/20 backdrop-blur-md transition-opacity duration-300 opacity-0 cursor-pointer" onclick="closeProfileModal()"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
                <div id="modalContent" class="bg-white w-full max-w-4xl rounded-[40px] shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300 pointer-events-auto flex flex-col md:flex-row h-full max-h-[650px]">
                    <!-- Sidebar in Modal -->
                    <div class="w-full md:w-80 bg-gray-50/50 border-r border-gray-100 p-8 flex flex-col">
                        <div class="mb-10 text-center md:text-left">
                            <div class="relative w-20 h-20 mx-auto md:mx-0 mb-4 group">
                                <div class="w-full h-full rounded-[28px] overflow-hidden border-4 border-white premium-shadow">
                                    <?php if($profile_photo): ?>
                                        <img src="<?php echo $profile_photo; ?>" class="w-full h-full object-cover" alt="Profile">
                                    <?php else: ?>
                                        <div class="w-full h-full bg-maroon flex items-center justify-center text-white text-2xl font-bold">
                                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button onclick="document.getElementById('modalPhotoUpload').click()" class="absolute -bottom-1 -right-1 w-8 h-8 bg-white text-gold rounded-xl flex items-center justify-center shadow-xl border border-gray-50 hover:bg-gold hover:text-white transition-all transform hover:scale-110">
                                    <i class="fas fa-camera text-[10px]"></i>
                                </button>
                                <input type="file" id="modalPhotoUpload" class="hidden" accept="image/*" onchange="uploadPhoto(this)">
                            </div>
                            <h3 class="font-bold maroon-text text-lg line-clamp-1"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mt-1">Premium Member</p>
                        </div>

                        <nav class="space-y-3 flex-1">
                            <button onclick="switchTab('profile-info')" id="btn-profile-info" class="tab-btn w-full flex items-center space-x-4 p-4 rounded-2xl text-sm font-bold transition-all text-gray-400 hover:text-maroon">
                                <i class="fas fa-id-card w-5 text-center"></i>
                                <span>Personal Details</span>
                            </button>
                            <button onclick="switchTab('security-settings')" id="btn-security-settings" class="tab-btn w-full flex items-center space-x-4 p-4 rounded-2xl text-sm font-bold transition-all text-gray-400 hover:text-maroon">
                                <i class="fas fa-shield-halved w-5 text-center"></i>
                                <span>Security & Password</span>
                            </button>
                        </nav>

                        <div class="pt-8 border-t border-gray-100">
                            <button onclick="closeProfileModal()" class="w-full py-4 text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-maroon transition-colors">
                                Close Settings
                            </button>
                        </div>
                    </div>

                    <!-- Content Area -->
                    <div class="flex-1 overflow-y-auto p-8 md:p-12">
                        <!-- Personal Info Tab -->
                        <div id="tab-profile-info" class="tab-content hidden animate-fade-in">
                            <div class="mb-10">
                                <h4 class="text-2xl font-bold maroon-text">Personal Details</h4>
                                <p class="text-gray-400 text-sm mt-1">Update your identity and contact information.</p>
                            </div>
                            <form id="updateProfileForm" onsubmit="handleUpdateProfile(event)" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="input-group">
                                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Full Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                                    </div>
                                    <div class="input-group">
                                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Email Address</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                    </div>
                                    <div class="input-group">
                                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Phone Number</label>
                                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+1 (555) 000-0000">
                                    </div>
                                    <div class="input-group">
                                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Nationality</label>
                                        <input type="text" name="nationality" value="<?php echo htmlspecialchars($nationality); ?>" placeholder="e.g. Indian">
                                    </div>
                                    <div class="input-group md:col-span-2">
                                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Date of Birth</label>
                                        <input type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>" style="color-scheme: light;">
                                    </div>
                                </div>
                                <button type="submit" class="px-10 py-5 bg-maroon text-white rounded-2xl font-bold text-sm hover:scale-105 transition-all shadow-xl shadow-maroon/20 flex items-center space-x-3">
                                    <i class="fas fa-save"></i>
                                    <span>Save Changes</span>
                                </button>
                            </form>
                        </div>

                        <!-- Security Tab -->
                        <div id="tab-security-settings" class="tab-content hidden animate-fade-in">
                            <div class="mb-10">
                                <h4 class="text-2xl font-bold maroon-text">Security Settings</h4>
                                <p class="text-gray-400 text-sm mt-1">Manage your account protection and password.</p>
                            </div>
                            <form id="updateSecurityForm" onsubmit="handleUpdatePassword(event)" class="space-y-8">
                                <div class="bg-gray-50 rounded-[32px] p-8 space-y-6 border border-gray-100">
                                    <div class="input-group">
                                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Current Password</label>
                                        <input type="password" name="current_password" required placeholder="••••••••">
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="input-group">
                                            <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">New Password</label>
                                            <input type="password" name="new_password" required placeholder="••••••••">
                                        </div>
                                        <div class="input-group">
                                            <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Confirm Password</label>
                                            <input type="password" name="confirm_password" required placeholder="••••••••">
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <button type="submit" class="px-10 py-5 bg-teal text-white rounded-2xl font-bold text-sm hover:scale-105 transition-all shadow-xl shadow-teal/20 flex items-center space-x-3">
                                        <i class="fas fa-key"></i>
                                        <span>Update Security</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Premium Checkout Modal -->
        <div id="checkoutModal" class="fixed inset-0 z-[120] hidden items-center justify-center p-6">
            <div class="absolute inset-0 bg-maroon/40 backdrop-blur-md" onclick="closeCheckoutModal()"></div>
            <div class="bg-white rounded-[40px] p-10 max-w-xl w-full relative z-[121] premium-shadow border border-white/20 animate-fade-in overflow-y-auto max-h-[90vh]">
                <button onclick="closeCheckoutModal()" class="absolute top-6 right-8 text-gray-400 hover:text-maroon">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <div class="text-center mb-10">
                    <div class="w-20 h-20 bg-gold/5 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-receipt gold-text text-3xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Final Settlement</h3>
                    <p class="text-gray-400 text-sm mt-2">Review your residency summary and final payment.</p>
                </div>

                <div class="space-y-8">
                    <!-- Residency Info -->
                    <div class="bg-gray-50 rounded-3xl p-6 border border-gray-100">
                        <div class="flex justify-between mb-4">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Residency Suite</span>
                            <span id="checkoutSuite" class="font-bold maroon-text">--</span>
                        </div>
                        <div class="grid grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Stay Period</p>
                                <p id="checkoutNights" class="font-bold maroon-text mt-1 text-sm">--</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Base Amount</p>
                                <p id="checkoutBasePrice" class="font-bold maroon-text mt-1 text-sm">--</p>
                            </div>
                        </div>
                    </div>

                    <!-- Service Orders Section -->
                    <div id="checkoutOrdersSection" class="hidden">
                        <h4 class="text-[10px] font-bold uppercase tracking-[3px] text-gray-400 mb-4 pl-2">Service Orders</h4>
                        <div id="checkoutOrdersList" class="space-y-4 max-h-[200px] overflow-y-auto pr-2 custom-scrollbar">
                            <!-- Orders dynamic -->
                        </div>
                    </div>

                    <!-- Total Calculation -->
                    <div class="pt-6 border-t-2 border-dashed border-gray-100">
                        <div class="flex justify-between items-center px-2">
                            <div>
                                <h4 class="text-xl font-bold maroon-text">Grand Total</h4>
                                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">Incl. all taxes & services</p>
                            </div>
                            <span id="checkoutGrandTotal" class="text-3xl font-black maroon-text">--</span>
                        </div>
                    </div>

                    <button id="confirmCheckoutBtn" onclick="processCheckout()" class="w-full py-5 gradient-maroon text-white rounded-[24px] font-bold shadow-xl shadow-maroon/20 hover:scale-[1.02] transition-transform flex items-center justify-center space-x-3">
                        <i class="fas fa-credit-card"></i>
                        <span>Settle & Check-Out</span>
                    </button>
                    <p class="text-center text-[10px] text-gray-400 font-bold uppercase tracking-widest">Safe & Secure Transaction</p>
                </div>
            </div>
        </div>

        <!-- Premium Toast -->
        <div id="premiumToast" class="fixed bottom-10 right-10 z-[200] hidden">
            <div id="toastInner" class="p-6 rounded-[28px] text-white flex items-center space-x-4 shadow-2xl transition-all duration-300 transform translate-y-10 opacity-0">
                <div id="toastIconBlock" class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i id="toastIcon" class="fas fa-check"></i>
                </div>
                <div>
                    <p id="toastTitleText" class="font-bold text-sm"></p>
                    <p id="toastMessageText" class="text-[10px] uppercase tracking-widest opacity-80 mt-0.5"></p>
                </div>
            </div>
        </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.add('opacity-100'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-100');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            }
        }

        function toggleProfileMenu() {
            const submenu = document.getElementById('profileSubmenu');
            const chevron = document.getElementById('profileChevron');
            submenu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        function openProfileModal(tab = 'profile-info') {
            const modal = document.getElementById('profileModal');
            const overlay = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            document.body.classList.add('modal-active');
            switchTab(tab);
            
            setTimeout(() => {
                overlay.classList.add('opacity-100');
                content.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeProfileModal() {
            const modal = document.getElementById('profileModal');
            const overlay = document.getElementById('modalOverlay');
            const content = document.getElementById('modalContent');
            
            overlay.classList.remove('opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.classList.remove('modal-active');
            }, 300);
        }

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.remove('text-maroon', 'bg-white', 'shadow-sm', 'border', 'border-gray-100');
                b.classList.add('text-gray-400');
            });

            document.getElementById('tab-' + tabId).classList.remove('hidden');
            const btn = document.getElementById('btn-' + tabId);
            btn.classList.add('text-maroon', 'bg-white', 'shadow-sm', 'border', 'border-gray-100');
            btn.classList.remove('text-gray-400');
        }

        function showMessage(title, msg, type = 'success') {
            const toast = document.getElementById('premiumToast');
            const inner = document.getElementById('toastInner');
            const icon = document.getElementById('toastIcon');
            const titleEl = document.getElementById('toastTitleText');
            const msgEl = document.getElementById('toastMessageText');

            inner.className = `p-6 rounded-[28px] text-white flex items-center space-x-4 shadow-2xl transition-all duration-300 transform ${type === 'success' ? 'bg-teal' : 'bg-maroon'}`;
            icon.className = `fas ${type === 'success' ? 'fa-check' : 'fa-exclamation-triangle'}`;
            titleEl.innerText = title;
            msgEl.innerText = msg;

            toast.classList.remove('hidden');
            setTimeout(() => {
                inner.classList.remove('translate-y-10', 'opacity-0');
            }, 10);

            setTimeout(() => {
                inner.classList.add('translate-y-10', 'opacity-0');
                setTimeout(() => toast.classList.add('hidden'), 300);
            }, 4000);
        }

        function handleUpdateProfile(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            fetch('php/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage('Settings Updated', 'Your profile details have been saved.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showMessage('Error occurred', data.message, 'error');
                }
            })
            .catch(() => showMessage('System error', 'Unable to connect to server', 'error'));
        }

        function handleUpdatePassword(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            if (formData.get('new_password') !== formData.get('confirm_password')) {
                showMessage('Match Error', 'New passwords do not match.', 'error');
                return;
            }

            fetch('php/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage('Security Updated', 'Your password has been changed.');
                    e.target.reset();
                } else {
                    showMessage('Update Failed', data.message, 'error');
                }
            })
            .catch(() => showMessage('System error', 'Unable to connect to server', 'error'));
        }

        function uploadPhoto(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_photo', input.files[0]);

                const placeholder = document.getElementById('avatarPlaceholder');
                if (placeholder) placeholder.innerHTML = '<i class="fas fa-spinner animate-spin"></i>';

                fetch('php/upload_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Upload failed');
                });
            }
        }

        function cancelBooking(id) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                const formData = new FormData();
                formData.append('booking_id', id);

                fetch('php/cancel_booking.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Booking Cancelled', 'The room is now available.');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showMessage('Error', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showMessage('System Error', 'Unable to cancel booking', 'error');
                });
            }
        }

        // Real-time Order Polling
        function pollOrders() {
            fetch('php/get_recent_orders.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.orders.length > 0) {
                        const container = document.getElementById('recentOrdersContainer');
                        let html = '';
                        data.orders.forEach(order => {
                            let statusColor = 'bg-gray-100 text-gray-500';
                            if(order.status === 'Pending') statusColor = 'bg-gold/10 text-gold';
                            if(order.status === 'Preparing') statusColor = 'bg-teal/10 text-teal';
                            if(order.status === 'Delivered') statusColor = 'bg-green-50 text-green-600';
                            if(order.status === 'Cancelled') statusColor = 'bg-red-50 text-red-600';

                            html += `
                                <div class="flex justify-between items-start border-b border-gray-50 pb-6 last:border-0 last:pb-0 animate-fade-in">
                                    <div class="max-w-[180px]">
                                        <p class="text-sm font-bold maroon-text truncate">${order.summary}</p>
                                        <p class="text-[10px] text-gray-400 mt-1">${order.display_date}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-black maroon-text mb-2">₹${parseFloat(order.total_price).toFixed(2)}</p>
                                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${statusColor}">
                                            ${order.status}
                                        </span>
                                    </div>
                                </div>`;
                        });
                        container.innerHTML = html;
                    } else if (data.success && data.orders.length === 0) {
                        document.getElementById('recentOrdersContainer').innerHTML = `
                            <div class="flex flex-col items-center justify-center py-10 opacity-30">
                                <i class="fas fa-utensils text-5xl mb-4"></i>
                                <p class="text-xs font-bold uppercase tracking-widest">No recent orders</p>
                            </div>`;
                    }
                });
        }

        // Poll every 10 seconds
        setInterval(pollOrders, 10000);
        function initiateCheckout(bookingId) {
            const modal = document.getElementById('checkoutModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            fetch(`php/get_checkout_summary.php?id=${bookingId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('checkoutSuite').innerText = `${data.booking.room_type} (Room ${data.booking.room_number})`;
                    document.getElementById('checkoutNights').innerText = `${data.nights} Nights Stay`;
                    document.getElementById('checkoutBasePrice').innerText = `₹${parseFloat(data.booking.total_price).toLocaleString()}`;
                    
                    const ordersContainer = document.getElementById('checkoutOrdersSection');
                    const ordersList = document.getElementById('checkoutOrdersList');
                    ordersList.innerHTML = '';
                    
                    if (data.orders && data.orders.length > 0) {
                        ordersContainer.classList.remove('hidden');
                        data.orders.forEach(order => {
                            const items = JSON.parse(order.items);
                            const names = items.map(i => i.name).join(', ');
                            ordersList.innerHTML += `
                                <div class="flex justify-between items-center text-xs p-3 bg-gray-50 rounded-xl">
                                    <div class="max-w-[150px]">
                                        <p class="font-bold maroon-text truncate">${names}</p>
                                        <p class="text-[9px] text-gray-400 mt-1">${new Date(order.created_at).toLocaleDateString()}</p>
                                    </div>
                                    <span class="font-bold maroon-text">₹${parseFloat(order.total_price).toLocaleString()}</span>
                                </div>
                            `;
                        });
                    } else {
                        ordersContainer.classList.add('hidden');
                    }
                    
                    document.getElementById('checkoutGrandTotal').innerText = `₹${parseFloat(data.grand_total).toLocaleString()}`;
                    document.getElementById('confirmCheckoutBtn').onclick = () => processCheckout(bookingId);
                } else {
                    showMessage('Checkout Error', data.message, 'error');
                    closeCheckoutModal();
                }
            })
            .catch(() => {
                showMessage('System Error', 'Unable to fetch checkout summary.', 'error');
                closeCheckoutModal();
            });
        }

        function closeCheckoutModal() {
            const modal = document.getElementById('checkoutModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function processCheckout(bookingId) {
            const btn = document.getElementById('confirmCheckoutBtn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Processing...</span>';
            btn.disabled = true;

            fetch('php/process_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `booking_id=${bookingId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage('Departure Finalized', 'Your stay has been successfully completed. Farewell!');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showMessage('Checkout Error', data.message, 'error');
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            })
            .catch(() => {
                showMessage('System Error', 'An unexpected error occurred.', 'error');
                btn.innerHTML = originalContent;
                btn.disabled = false;
            });
        }

        window.addEventListener('load', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('error') === 'checkin_required' || urlParams.get('checkin_required')) {
                showMessage('Access Restricted', 'Service features activate upon your arrival and check-in.', 'error');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>
</html>
