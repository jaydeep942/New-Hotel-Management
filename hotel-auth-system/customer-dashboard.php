<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$conn = require_once __DIR__ . '/config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch the latest upcoming booking for this user
$sql = "SELECT b.*, r.room_type, r.room_number 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.user_id = ? AND b.status = 'Confirmed' 
        ORDER BY b.check_in ASC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$booking = $result->fetch_assoc();

if ($booking) {
    $currentBookingID = "#LX-" . str_pad($booking['id'], 4, '0', STR_PAD_LEFT);
    $roomType = $booking['room_type'] . " Suite";
    $suiteNumber = $booking['room_number'];
    $check_in = date('d M Y', strtotime($booking['check_in']));
    $total_price = $booking['total_price'];
    $hasBooking = true;
} else {
    $hasBooking = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Grand Luxe Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background: linear-gradient(135deg, var(--maroon) 0%, #832537 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(106, 30, 45, 0.2);
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

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .gradient-gold { background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%); }
        .gradient-teal { background: linear-gradient(135deg, #2CA6A4 0%, #228B89 100%); }
        .gradient-maroon { background: linear-gradient(135deg, #6A1E2D 0%, #4A1520 100%); }

        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
        }

        /* Animations */
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide { animation: slideIn 0.6s ease-out forwards; }
    </style>
</head>
<body class="bg-[#F8F5F0] min-h-screen">

    <!-- Mobile Sidebar Toggle -->
    <div class="lg:hidden fixed top-6 left-6 z-[60]">
        <button onclick="toggleSidebar()" class="w-12 h-12 bg-white rounded-2xl premium-shadow flex items-center justify-center maroon-text">
            <i class="fas fa-bars-staggered text-xl"></i>
        </button>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-maroon/20 backdrop-blur-sm z-[51] hidden lg:hidden transition-opacity duration-300 opacity-0"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 bg-white fixed h-full border-r border-gray-100 px-6 py-8 z-[55] overflow-y-auto transition-transform duration-300 -translate-x-full lg:translate-x-0">
        <div class="mb-12 px-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tighter maroon-text" style="font-family: 'Playfair Display', serif;">
                    GRAND<span class="gold-text">LUXE</span>
                </h1>
                <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400 mt-1">Excellence Defined</p>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-maroon">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="space-y-2">
            <a href="customer-dashboard.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm">
                <i class="fas fa-th-large w-5"></i>
                <span class="font-semibold">Dashboard</span>
            </a>
            <a href="book-room.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-bed w-5"></i>
                <span class="font-semibold">Book Room</span>
            </a>
            <a href="services.html" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-concierge-bell w-5"></i>
                <span class="font-semibold">Services</span>
            </a>
            <a href="cleaning.html" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-broom w-5"></i>
                <span class="font-semibold">Cleaning Request</span>
            </a>
            <a href="feedback.html" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-star w-5"></i>
                <span class="font-semibold">Feedback</span>
            </a>
            <a href="history.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-history w-5"></i>
                <span class="font-semibold">Booking History</span>
            </a>
            <div class="pt-10">
                <a href="php/logout.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-red-500 hover:bg-red-50 transition text-sm">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span class="font-bold uppercase tracking-wider text-xs">Sign Out</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-72 p-4 md:p-8">
        <!-- Top Navbar -->
        <nav class="glass-nav sticky top-0 flex flex-col md:flex-row justify-between items-center p-4 md:p-6 rounded-3xl mb-8 md:mb-12 z-40 premium-shadow border border-white/20 gap-4 md:gap-0">
            <div class="flex items-center space-x-4 w-full md:w-auto pl-14 lg:pl-0">
                <div class="bg-maroon/5 p-3 rounded-2xl hidden sm:block">
                    <i class="fas fa-key maroon-text"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Current Booking</p>
                    <?php if($hasBooking): ?>
                    <p class="font-bold text-sm"><?php echo $currentBookingID; ?> (Suite <?php echo $suiteNumber; ?>)</p>
                    <?php else: ?>
                    <p class="font-bold text-sm text-gray-400 italic">No active booking</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center justify-between md:justify-end w-full md:w-auto md:space-x-8">
                <div class="relative cursor-pointer group px-4">
                    <i class="far fa-bell text-gray-400 text-xl group-hover:text-gold transition"></i>
                    <span class="absolute top-0 right-3 w-4 h-4 bg-maroon text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white">2</span>
                </div>
                
                <div class="flex items-center space-x-4 pl-4 md:pl-8 border-l border-gray-100">
                    <div class="text-right hidden sm:block">
                        <p class="font-bold text-sm"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                        <p class="text-[10px] uppercase font-bold text-gold tracking-widest">Premium Member</p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl overflow-hidden border-2 border-gold/20 p-1">
                        <div class="w-full h-full bg-maroon rounded-xl flex items-center justify-center text-white font-bold text-lg">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Dashboard Home Content -->
        <div class="animate-slide">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Welcome Back, <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                    <p class="text-gray-500 mt-1 text-sm md:text-base">Here is a summary of your private suite status.</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-sm font-bold text-gray-400"><?php echo date('l, d F Y'); ?></p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div class="gradient-maroon p-8 rounded-[32px] text-white premium-shadow hover:scale-105 transition-transform duration-500 relative overflow-hidden group">
                    <i class="fas fa-bookmark absolute -right-4 -bottom-4 text-8xl text-white/10 group-hover:scale-110 transition-transform"></i>
                    <p class="text-white/70 uppercase tracking-widest text-[10px] font-bold mb-2">Active Booking</p>
                    <h3 class="text-2xl font-bold"><?php echo $hasBooking ? $currentBookingID : 'N/A'; ?></h3>
                    <?php if($hasBooking): ?>
                    <div class="mt-6 flex items-center space-x-2">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        <span class="text-xs font-semibold text-green-100">Live Now</span>
                    </div>
                    <?php else: ?>
                    <a href="book-room.php" class="mt-6 inline-block text-xs font-bold text-gold hover:underline">Book Your Stay →</a>
                    <?php endif; ?>
                </div>

                <div class="bg-white p-8 rounded-[32px] premium-shadow border border-gray-50 hover:scale-105 transition-transform duration-500 group relative">
                     <i class="fas fa-couch absolute -right-4 -bottom-4 text-8xl text-gray-50 group-hover:scale-110 transition-transform"></i>
                    <p class="text-gray-400 uppercase tracking-widest text-[10px] font-bold mb-2">Room Type</p>
                    <h3 class="text-xl font-bold maroon-text"><?php echo $hasBooking ? $roomType : 'No active residency'; ?></h3>
                    <p class="mt-4 text-gold font-bold text-sm"><?php echo $hasBooking ? 'Luxury Suite' : 'Ready for you'; ?></p>
                </div>

                <div class="bg-white p-8 rounded-[32px] premium-shadow border border-gray-50 hover:scale-105 transition-transform duration-500 group relative">
                    <i class="fas fa-calendar-check absolute -right-4 -bottom-4 text-8xl text-gray-50 group-hover:scale-110 transition-transform"></i>
                    <p class="text-gray-400 uppercase tracking-widest text-[10px] font-bold mb-2">Check-In</p>
                    <h3 class="text-2xl font-bold teal-text"><?php echo $hasBooking ? $check_in : '-- -- --'; ?></h3>
                    <p class="mt-4 text-gray-400 text-sm">After 12:00 PM</p>
                </div>

                <div class="gradient-gold p-8 rounded-[32px] text-white premium-shadow hover:scale-105 transition-transform duration-500 group relative">
                    <i class="fas fa-door-open absolute -right-4 -bottom-4 text-8xl text-white/10 group-hover:scale-110 transition-transform"></i>
                    <p class="text-white/70 uppercase tracking-widest text-[10px] font-bold mb-2">Check-Out</p>
                    <h3 class="text-2xl font-bold"><?php echo $hasBooking ? date('d M Y', strtotime($booking['check_out'])) : '-- -- --'; ?></h3>
                    <?php if($hasBooking): ?>
                    <p class="mt-4 text-white/80 text-sm italic"><?php echo (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400; ?> Nights Stay</p>
                    <?php else: ?>
                    <p class="mt-4 text-white/80 text-sm italic">Plan your getaway</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Status & Progress -->
                <div class="lg:col-span-2 bg-white rounded-[40px] p-10 premium-shadow">
                    <div class="flex justify-between items-center mb-8">
                        <h4 class="text-xl font-bold maroon-text">Stay Progress</h4>
                        <span class="px-4 py-1.5 bg-teal/10 text-teal rounded-full text-xs font-bold uppercase tracking-widest">Day 2 of 5</span>
                    </div>
                    <div class="w-full bg-gray-100 h-4 rounded-full overflow-hidden mb-10">
                        <div class="bg-teal h-full rounded-full" style="width: 40%"></div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center p-6 rounded-3xl bg-gray-50">
                            <i class="fas fa-wifi text-gold mb-3 text-xl"></i>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">WiFi Status</p>
                            <p class="font-bold text-sm">Connected</p>
                        </div>
                        <div class="text-center p-6 rounded-3xl bg-gray-50">
                            <i class="fas fa-temperature-high text-teal mb-3 text-xl"></i>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Room Temp</p>
                            <p class="font-bold text-sm">22&deg;C</p>
                        </div>
                        <div class="text-center p-6 rounded-3xl bg-gray-50">
                            <i class="fas fa-bolt text-maroon mb-3 text-xl"></i>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Energy</p>
                            <p class="font-bold text-sm">Smart Mode</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Action -->
                <div class="bg-maroon rounded-[40px] p-10 text-white premium-shadow shimmer-card">
                    <h4 class="text-xl font-bold mb-6">Need Anything?</h4>
                    <p class="text-white/60 text-sm leading-relaxed mb-8">Our award-winning concierge team is available 24/7 to assist with your every wish.</p>
                    <div class="space-y-4">
                        <button class="w-full py-4 rounded-2xl bg-white/10 hover:bg-white text-white hover:maroon-text font-bold transition-all flex items-center justify-center space-x-3 group btn-glow">
                            <i class="fas fa-phone-alt group-hover:scale-110 transition"></i>
                            <span>Call Concierge</span>
                        </button>
                        <button onclick="window.location.href='services.html'" class="w-full py-4 rounded-2xl bg-gold text-white font-bold hover:scale-105 transition-all flex items-center justify-center space-x-3 btn-glow">
                            <i class="fas fa-utensils"></i>
                            <span>Order Dining</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
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
    </script>
</body>
</html>
