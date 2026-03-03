<?php
require_once __DIR__ . '/php/check_guest_auth.php';

// User data and $conn are now available from check_guest_auth.php
$_SESSION['name'] = $user_data['name'];
$_SESSION['email'] = $user_data['email'];
$profile_photo = $user_data['profile_photo'];
$phone = $user_data['phone'] ?? '';
$nationality = $user_data['nationality'] ?? '';
$dob = $user_data['dob'] ?? '';
$created_at = $user_data['created_at'];

// ONE-TIME SEED REFRESH (Add more rooms if only 6 exist)
$check_count = $conn->query("SELECT COUNT(*) as total FROM rooms");
$row_count = $check_count->fetch_assoc();
if ($row_count['total'] <= 6) {
    $conn->query("INSERT IGNORE INTO rooms (room_number, room_type, price_per_night, status) VALUES 
        ('103', 'Standard', 1000.00, 'Available'),
        ('104', 'Standard', 1000.00, 'Available'),
        ('203', 'Deluxe', 1500.00, 'Available'),
        ('204', 'Deluxe', 1500.00, 'Available'),
        ('302', 'Executive', 1700.00, 'Available'),
        ('303', 'Executive', 1800.00, 'Available'),
        ('402', 'Presidential', 2300.00, 'Available'),
        ('501', 'Presidential', 2300.00, 'Available')");
}

// Processing Search Filters
$cin = isset($_GET['cin']) ? $_GET['cin'] : '';
$cout = isset($_GET['cout']) ? $_GET['cout'] : '';
$type = isset($_GET['room_type']) ? $_GET['room_type'] : 'All Room Types';

$query = "SELECT * FROM rooms WHERE status = 'Available'";

if ($type != 'All Room Types') {
    $query .= " AND room_type = '" . $conn->real_escape_string($type) . "'";
}

// Logic: A room is unavailable if it has a confirmed booking that overlaps with the searched dates
if (!empty($cin) && !empty($cout)) {
    $query .= " AND id NOT IN (
        SELECT room_id FROM bookings 
        WHERE status IN ('Confirmed', 'Checked-In') 
        AND (check_in < '$cout' AND check_out > '$cin')
    )";
}

$rooms_result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room | Grand Luxe Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#D4AF37',
                        cream: '#F8F5F0',
                        maroon: '#6A1E2D',
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
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--cream);
            color: #333;
        }

        .maroon-text { color: var(--maroon); }
        .gold-text { color: var(--gold); }

        .gradient-maroon {
            background: linear-gradient(135deg, #6A1E2D 0%, #832537 100%);
        }

        .sidebar-link { transition: all 0.3s; }
        .sidebar-link.active {
            background: linear-gradient(135deg, var(--maroon) 0%, #832537 100%);
            color: white;
            box-shadow: 0 10px 20px rgba(106, 30, 45, 0.2);
        }

        .sidebar-link:not(.active):hover {
            background-color: rgba(106, 30, 45, 0.05);
            transform: translateX(5px);
        }


        .premium-shadow {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
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

        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.95);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide { animation: slideIn 0.5s ease-out forwards; }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-up { animation: slideInUp 0.6s ease-out forwards; }

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
            box-shadow: 0 8px 10x rgba(106, 30, 45, 0.05);
        }
        /* Custom Flatpickr Theme */
        .flatpickr-calendar {
            background: #fff;
            box-shadow: 0 20px 50px rgba(106, 30, 45, 0.15);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 24px;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            padding: 10px;
        }
        .flatpickr-day.selected {
            background: var(--maroon) !important;
            border-color: var(--maroon) !important;
        }
        .flatpickr-day:hover {
            background: rgba(212, 175, 55, 0.1);
        }
        .flatpickr-months .flatpickr-month {
            background: var(--maroon);
            color: #fff;
            fill: #fff;
        }
        .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: var(--maroon);
        }
        .flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after {
            border-bottom-color: var(--maroon);
        }
        .flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month {
            color: #fff;
            fill: #fff;
        }
        .flatpickr-calendar .flatpickr-innerContainer {
            padding-top: 10px;
        }
        .flatpickr-weekday {
            color: var(--maroon);
            font-weight: 700;
        }
        .flatpickr-input-custom {
            cursor: pointer !important;
            background-color: #f9fafb !important;
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
                        <a href="customer-dashboard.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Dashboard</span>
                        </a>
                        <a href="book-room.php" class="nav-link active flex items-center px-7 py-4 rounded-2xl text-[15px] font-bold transition-all">
                            <span>Book Room</span>
                        </a>
                        <a href="services.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Services</span>
                        </a>
                        <a href="cleaning.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Cleaning</span>
                        </a>
                        <a href="feedback.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Feedback</span>
                        </a>
                        <a href="complaints.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Complaints</span>
                        </a>
                        <a href="history.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>History</span>
                        </a>
                    </div>
                </div>

                <!-- Right Section: Cart & Profile -->
                <div class="flex items-center space-x-8">
                    <!-- Room Cart Button -->
                    <div id="roomCartBtn" onclick="toggleCart()" class="relative border-r border-gray-100 pr-8 cursor-pointer group">
                        <div class="bg-maroon/5 p-3 rounded-2xl group-hover:bg-maroon group-hover:text-white transition-all text-maroon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <span id="cartCounter" class="absolute top-0 right-6 bg-gold text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-white shadow-sm">0</span>
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
            <div id="mobileDrawer" class="absolute inset-y-0 right-0 w-80 bg-white h-full shadow-2xl p-8 transform translate-x-full transition-transform duration-300 overflow-y-auto">
                <div class="flex justify-between items-center mb-10">
                    <h2 class="text-xl font-bold maroon-text">Menu</h2>
                    <button onclick="toggleMobileMenu()" class="text-gray-400"><i class="fas fa-times text-xl"></i></button>
                </div>
                <nav class="space-y-3">
                    <a href="customer-dashboard.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-th-large"></i><span>Dashboard</span>
                    </a>
                    <a href="book-room.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl font-bold">
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
            <div class="animate-slide">

            <!-- Simplified Header -->
            <div class="flex flex-col md:flex-row justify-between items-end mb-12 animate-slide">
                <div>
                    <h2 class="text-3xl font-black maroon-text" style="font-family: 'Playfair Display', serif;">Explore Our Portfolio</h2>
                    <p class="text-gold text-[10px] uppercase tracking-[3px] font-black mt-2">Discover premium suites for your next residency</p>
                </div>
                <div class="hidden md:block">
                     <p class="text-gray-400 text-[10px] uppercase tracking-widest font-bold">Residencies available: <span class="text-maroon"><?php echo $rooms_result->num_rows; ?></span></p>
                </div>
            </div>

            <!-- Room Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                <?php if($rooms_result->num_rows > 0): ?>
                    <?php while($room = $rooms_result->fetch_assoc()): ?>
                    <div class="bg-white rounded-[32px] overflow-hidden premium-shadow group border border-gray-50 flex flex-col hover:border-gold/30 transition-all duration-500 hover:-translate-y-2">
                        <div class="h-64 relative overflow-hidden">
                            <?php 
                            // Highly Diverse & Clean Image Pool (Expanded for deep variety)
                            $imagePool = [
                                'Standard' => [
                                    "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/164595/pexels-photo-164595.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/279746/pexels-photo-279746.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/1743231/pexels-photo-1743231.jpeg?auto=compress&cs=tinysrgb&w=800"
                                ],
                                'Deluxe' => [
                                    "https://images.pexels.com/photos/262048/pexels-photo-262048.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/271619/pexels-photo-271619.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/1457842/pexels-photo-1457842.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/2102656/pexels-photo-2102656.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/271643/pexels-photo-271643.jpeg?auto=compress&cs=tinysrgb&w=800"
                                ],
                                'Executive' => [
                                    "https://images.pexels.com/photos/271647/pexels-photo-271647.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/276671/pexels-photo-276671.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/2029722/pexels-photo-2029722.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/261169/pexels-photo-261169.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/2034330/pexels-photo-2034330.jpeg?auto=compress&cs=tinysrgb&w=800"
                                ],
                                'Presidential' => [
                                    "https://images.pexels.com/photos/323311/pexels-photo-323311.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/2506990/pexels-photo-2506990.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/2507010/pexels-photo-2507010.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/237371/pexels-photo-237371.jpeg?auto=compress&cs=tinysrgb&w=800",
                                    "https://images.pexels.com/photos/1838554/pexels-photo-1838554.jpeg?auto=compress&cs=tinysrgb&w=800"
                                ]
                            ];

                            // Unique Suite Names (Expanded Pool)
                            $namePool = [
                                'Standard' => ['Urban', 'Cosy', 'Classic', 'Serenity', 'Heritage', 'Avenue', 'Noble', 'Boutique'],
                                'Deluxe' => ['Royal', 'Emerald', 'Sapphire', 'Grand', 'Vista', 'Crystal', 'Ambassador', 'Golden'],
                                'Executive' => ['Elite', 'Summit', 'Majestic', 'Plaza', 'Metropolitan', 'Horizon', 'Victory', 'Skyline'],
                                'Presidential' => ['Imperial', 'Regent', 'Palace', 'Crown', 'Sovereign', 'Pinnacle', 'Dynasty', 'Zenith']
                            ];
                            
                            $type = $room['room_type'];
                            $pool = $imagePool[$type] ?? $imagePool['Standard'];
                            $names = $namePool[$type] ?? $namePool['Standard'];
                            
                            // Use room number as a smarter seed to ensure every room is unique
                            $seed = (int)$room['room_number'];
                            $img = $pool[$seed % count($pool)];
                            $suiteName = $names[$seed % count($names)] . " " . $type . " Suite";
                            ?>
                            <img src="<?php echo $img; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="Residency Image" onerror="this.src='https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=800'">
                            <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-sm p-2 px-4 rounded-full font-bold maroon-text text-xs uppercase tracking-widest shadow-sm">
                                <?php echo $room['room_type']; ?>
                            </div>
                        </div>
                        <div class="p-8 flex-1 flex flex-col">
                            <div class="mb-4">
                                <h4 class="text-xl font-bold maroon-text"><?php echo $suiteName; ?></h4>
                                <p class="text-[10px] uppercase tracking-[3px] font-bold text-gold mt-1">Luxury Residency</p>
                            </div>
                            <p class="text-gray-400 text-sm mb-6 flex items-center">
                                <i class="fas fa-expand-arrows-alt mr-2 text-gold"></i> 
                                <?php 
                                $sizes = ['Standard' => '45', 'Deluxe' => '65', 'Executive' => '85', 'Presidential' => '120', 'Family' => '150', 'Penthouse' => '180'];
                                echo $sizes[$room['room_type']] ?? '45'; 
                                ?> m² • King Bed • <?php echo ($room['room_type'] == 'Penthouse' ? '360° View' : 'Panoramic View'); ?>
                            </p>
                            <div class="flex items-center justify-between mt-auto pt-6 border-t border-gray-50">
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-gray-300 tracking-widest mb-1">Starting From</p>
                                    <span class="text-2xl font-bold maroon-text">₹<?php echo number_format($room['price_per_night'], 0); ?></span>
                                    <span class="text-xs text-gray-400 font-medium">/Night</span>
                                </div>
                                <button id="room-btn-<?php echo $room['id']; ?>" 
                                        onclick="toggleRoomSelection(<?php echo htmlspecialchars(json_encode([
                                            'id' => $room['id'],
                                            'name' => $suiteName,
                                            'type' => $room['room_type'],
                                            'price' => $room['price_per_night'],
                                            'img' => $img
                                        ])); ?>)" 
                                        class="bg-maroon text-white px-6 py-3 rounded-2xl font-bold hover:bg-gold hover:shadow-xl transition-all duration-500 transform active:scale-95 flex items-center space-x-2 whitespace-nowrap group shrink-0">
                                    <i class="fas fa-plus text-sm transition-transform duration-500 group-hover:rotate-90"></i>
                                    <span>Add Stay</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full py-20 text-center">
                        <div class="w-24 h-24 bg-maroon/5 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-search text-maroon text-3xl opacity-20"></i>
                        </div>
                        <h3 class="text-2xl font-bold maroon-text">No Suites Available</h3>
                        <p class="text-gray-400 mt-2">Try adjusting your dates or room type for more options.</p>
                        <a href="book-room.php" class="inline-block mt-8 text-gold font-bold hover:underline">Clear all filters</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </main>

    <!-- Floating Room Cart Panel -->
    <div id="cartPanel" class="fixed right-6 bottom-6 z-[100] w-[380px] hidden transform transition-all duration-500 translate-y-full opacity-0">
        <div class="bg-white rounded-[40px] premium-shadow border border-maroon/5 overflow-hidden flex flex-col max-h-[600px]">
            <div class="p-8 bg-maroon text-white flex justify-between items-center">
                <div>
                    <h4 class="font-bold text-lg">Residency Selection</h4>
                    <p id="cartItemsCount" class="text-[10px] uppercase tracking-widest text-white/60">0 Suites Chosen</p>
                </div>
                <button onclick="toggleCart()" class="text-white/60 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="cartItemsList" class="p-6 flex-1 overflow-y-auto space-y-4 min-h-[100px] max-h-[300px]">
                <!-- Cart items will be injected here -->
            </div>
            <div class="p-8 border-t border-gray-50 bg-gray-50/50">
                <div class="flex justify-between items-center mb-6">
                    <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">Est. Total (Per Night)</span>
                    <span id="cartTotalPrice" class="text-2xl font-black maroon-text">₹0</span>
                </div>
                <button onclick="proceedToBooking()" class="w-full py-4 gradient-maroon text-white rounded-2xl font-bold btn-glow transition-all shadow-xl shadow-maroon/20">
                    Book Selected Suites
                </button>
            </div>
        </div>
    </div>

    <!-- Booking Confirmation Modal (Step-Based) -->
    <div id="bookingModal" class="modal hidden fixed inset-0 z-[150] flex items-center justify-center p-6 overflow-y-auto">
        <div class="absolute inset-0 bg-maroon/40 backdrop-blur-md" onclick="hideBookingModal()"></div>
        <div class="bg-white rounded-[40px] p-8 md:p-10 relative w-full family-sans max-w-xl premium-shadow z-[101] my-8 overflow-y-auto max-h-[95vh] animate-fade-in shadow-2xl border border-white/20">
            <button onclick="hideBookingModal()" class="absolute top-8 right-8 text-gray-400 hover:text-maroon transition-all transform hover:rotate-90">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <!-- STEP 1: DATE SELECTION -->
            <div id="bookingStep1">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-gold/10 rounded-[28px] flex items-center justify-center mx-auto mb-4 border border-gold/20">
                        <i class="fas fa-calendar-alt text-gold text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-black maroon-text" style="font-family: 'Playfair Display', serif;">Schedule Residency</h3>
                    <p class="text-gray-400 text-[10px] uppercase tracking-[2px] mt-2 font-bold">When will you be staying with us?</p>
                </div>

                <div class="space-y-6 mb-8">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">Arrival Date</label>
                            <input type="text" id="book_cin" placeholder="Select Arrival" value="<?php echo $cin; ?>"
                                class="flatpickr w-full p-5 rounded-[24px] bg-gray-50 border-none focus:ring-2 focus:ring-gold outline-none text-xs font-bold flatpickr-input-custom">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">Departure</label>
                            <input type="text" id="book_cout" placeholder="Select Departure" value="<?php echo $cout; ?>"
                                class="flatpickr w-full p-5 rounded-[24px] bg-gray-50 border-none focus:ring-2 focus:ring-gold outline-none text-xs font-bold flatpickr-input-custom">
                        </div>
                    </div>

                    <div id="modalRoomListStep1" class="space-y-3">
                        <!-- Preview selected rooms -->
                    </div>
                </div>

                <button onclick="verifyAvailability()" id="verifyBtn" class="w-full py-5 gradient-maroon text-white rounded-[24px] font-black uppercase tracking-[3px] text-[11px] btn-glow transition-all shadow-xl flex items-center justify-center space-x-3">
                    <span id="verifyText">Verify Availability</span>
                    <div id="verifyLoader" class="hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                </button>
            </div>

            <!-- STEP 2: PERSONAL DETAILS -->
            <div id="bookingStep2" class="hidden">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-gold/10 rounded-[28px] flex items-center justify-center mx-auto mb-4 border border-gold/20 shadow-inner">
                        <i class="fas fa-id-card text-gold text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-black maroon-text" style="font-family: 'Playfair Display', serif;">Finalize Your Portfolio</h3>
                    <p class="text-gray-400 text-[10px] uppercase tracking-[2px] mt-2 font-bold">Suites verified. Complete your identification.</p>
                </div>
                
                <div class="space-y-6 mb-8">
                    <div class="bg-gray-50/50 p-8 rounded-[32px] border border-gray-100 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">Identity Name</label>
                                <input type="text" id="guest_name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>"
                                    class="w-full p-4 rounded-2xl bg-white border border-gray-200 focus:border-maroon focus:ring-0 outline-none text-xs font-bold transition-all">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">Archive Email</label>
                                <input type="email" id="guest_email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>"
                                    class="w-full p-4 rounded-2xl bg-white border border-gray-200 focus:border-maroon focus:ring-0 outline-none text-xs font-bold transition-all">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">Contact Number</label>
                                <input type="text" id="guest_phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>"
                                    class="w-full p-4 rounded-2xl bg-white border border-gray-200 focus:border-maroon focus:ring-0 outline-none text-xs font-bold transition-all">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">ID Document Proof</label>
                                <div class="relative">
                                    <select id="id_proof_type" 
                                        class="w-full p-4 rounded-2xl bg-white border border-gray-200 focus:border-maroon focus:ring-0 outline-none text-xs font-bold appearance-none transition-all">
                                        <option value="Aadhar">Indian Aadhar Card</option>
                                        <option value="Passport">International Passport</option>
                                        <option value="PAN">PAN Document</option>
                                        <option value="VoterID">Voter Identification</option>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-gold pointer-events-none"></i>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">Archive Identity ID</label>
                                <input type="text" id="id_proof" placeholder="Identity Document Number"
                                    class="w-full p-4 rounded-2xl bg-white border border-gray-200 focus:border-maroon focus:ring-0 outline-none text-xs font-bold transition-all">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 block">Permanent Residence</label>
                                <input type="text" id="guest_address" placeholder="Residential City/State/Country"
                                    class="w-full p-4 rounded-2xl bg-white border border-gray-200 focus:border-maroon focus:ring-0 outline-none text-xs font-bold transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center p-6 bg-maroon/5 rounded-[28px] border border-maroon/10 shadow-inner">
                        <div class="flex flex-col">
                            <span class="font-black text-gray-400 text-[10px] uppercase tracking-widest">Grand Portfolio Total</span>
                            <span class="text-[10px] italic text-gold mt-1" id="nightsText">Total for 1 Night</span>
                        </div>
                        <span id="modalTotal" class="text-3xl font-black maroon-text tracking-tighter">₹0</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button onclick="backToStep1()" class="py-5 bg-gray-50 text-gray-400 rounded-[24px] font-black uppercase tracking-[3px] text-[11px] hover:text-maroon transition-all">Back</button>
                    <button onclick="completeBooking()" id="confirmBtn" class="py-5 gradient-maroon text-white rounded-[24px] font-black uppercase tracking-[3px] text-[11px] btn-glow transition-all shadow-xl flex items-center justify-center space-x-3 hover:scale-[1.02]">
                        <span id="confirmText">Confirm Booking</span>
                        <div id="btnLoader" class="hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Confirmation Modal -->
    <div id="successModal" class="modal hidden fixed inset-0 z-[150] flex items-center justify-center p-6">
        <div class="absolute inset-0 bg-maroon/60 backdrop-blur-xl"></div>
        <div class="bg-white rounded-[50px] w-full max-w-lg relative z-[151] overflow-hidden premium-shadow animate-up">
            <!-- Pattern Header -->
            <div class="h-32 gradient-maroon relative flex items-center justify-center">
                <div class="absolute inset-0 opacity-10" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-2xl relative z-10">
                    <i class="fas fa-check text-teal text-3xl"></i>
                </div>
            </div>

            <div class="p-10 text-center">
                <h3 class="text-3xl font-bold maroon-text mb-2">Residency Secured</h3>
                <p class="text-gray-400 text-sm mb-8">Your luxury collection has been officially archived.</p>

                <!-- Receipt Detail -->
                <div class="bg-gray-50 rounded-[32px] p-8 mb-8 border border-gray-100 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gold text-white text-[8px] font-black px-4 py-1 rounded-full uppercase tracking-widest">Official Receipt</div>
                    
                    <div class="space-y-4">
                        <div class="flex justify-between items-center border-bottom border-gray-100 pb-4">
                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-widest text-left">Suites Selection</span>
                            <span id="successRooms" class="font-bold text-xs maroon-text text-right truncate ml-4">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="text-left">
                                <span class="text-[8px] uppercase font-bold text-gray-300 tracking-widest block">Arrival</span>
                                <span id="successCin" class="font-bold text-xs maroon-text">--</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[8px] uppercase font-bold text-gray-300 tracking-widest block">Departure</span>
                                <span id="successCout" class="font-bold text-xs maroon-text">--</span>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-dashed border-gold/30 flex justify-between items-center">
                            <span class="text-xs font-bold maroon-text uppercase tracking-tighter">Total Investment</span>
                            <span id="successTotal" class="text-2xl font-black maroon-text">₹0</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col space-y-3">
                    <a href="customer-dashboard.php" class="w-full py-5 gradient-maroon text-white rounded-2xl font-bold btn-glow transition shadow-xl text-center">
                        Access Your Dashboard
                    </a>
                    <button onclick="window.location.reload()" class="text-[10px] uppercase font-bold text-gray-400 tracking-widest hover:text-gold transition">
                        Reserve Another Stay
                    </button>
                </div>
            </div>
        </div>
    </div>

        <!-- Manage Profile Modal -->
        <div id="profileModal" class="fixed inset-0 z-[110] hidden">
            <div id="modalOverlayProfile" class="absolute inset-0 bg-maroon/20 backdrop-blur-md transition-opacity duration-300 opacity-0 cursor-pointer" onclick="closeProfileModal()"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
                <div id="modalContentProfile" class="bg-white w-full max-w-4xl rounded-[40px] shadow-2xl overflow-hidden transform scale-95 opacity-0 transition-all duration-300 pointer-events-auto flex flex-col md:flex-row h-full max-h-[650px]">
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

        <!-- Premium Toast -->
        <div id="premiumToast" class="fixed bottom-10 right-10 z-[200] hidden">
            <div id="toastInner" class="p-6 rounded-[28px] text-white flex items-center space-x-4 shadow-2xl transition-all duration-300 transform translate-y-10 opacity-0">
                <div id="toastIconBlockProfile" class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i id="toastIconProfile" class="fas fa-check"></i>
                </div>
                <div>
                    <p id="toastTitleTextProfile" class="font-bold text-sm"></p>
                    <p id="toastMessageTextProfile" class="text-[10px] uppercase tracking-widest opacity-80 mt-0.5"></p>
                </div>
            </div>
        </div>

    <script>
        let selectedRoomId = null;


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
            const overlay = document.getElementById('modalOverlayProfile');
            const content = document.getElementById('modalContentProfile');
            
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
            const overlay = document.getElementById('modalOverlayProfile');
            const content = document.getElementById('modalContentProfile');
            
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

        function showPremiumMessage(title, msg, type = 'success') {
            const toast = document.getElementById('premiumToast');
            const inner = document.getElementById('toastInner');
            const icon = document.getElementById('toastIconProfile');
            const titleEl = document.getElementById('toastTitleTextProfile');
            const msgEl = document.getElementById('toastMessageTextProfile');

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
                    showPremiumMessage('Settings Updated', 'Your profile details have been saved.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showPremiumMessage('Error occurred', data.message, 'error');
                }
            })
            .catch(() => showPremiumMessage('System error', 'Unable to connect to server', 'error'));
        }

        function handleUpdatePassword(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            if (formData.get('new_password') !== formData.get('confirm_password')) {
                showPremiumMessage('Match Error', 'New passwords do not match.', 'error');
                return;
            }

            fetch('php/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showPremiumMessage('Security Updated', 'Your password has been changed.');
                    e.target.reset();
                } else {
                    showPremiumMessage('Update Failed', data.message, 'error');
                }
            })
            .catch(() => showPremiumMessage('System error', 'Unable to connect to server', 'error'));
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
                        showPremiumMessage('Upload Failed', data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showPremiumMessage('System error', 'Upload failed', 'error');
                });
            }
        }

        let roomCart = [];

        function toggleRoomSelection(room) {
            const index = roomCart.findIndex(r => r.id === room.id);
            const btn = document.getElementById('room-btn-' + room.id);
            
            if (index === -1) {
                // Add to cart
                roomCart.push(room);
                btn.innerHTML = '<i class="fas fa-check text-sm"></i><span>Added</span>';
                btn.classList.replace('bg-maroon', 'bg-gold');
            } else {
                // Remove from cart
                roomCart.splice(index, 1);
                btn.innerHTML = '<i class="fas fa-plus text-sm"></i><span>Add Stay</span>';
                btn.classList.replace('bg-gold', 'bg-maroon');
            }
            updateCartUI();
        }

        function updateCartUI() {
            const counter = document.getElementById('cartCounter');
            const list = document.getElementById('cartItemsList');
            const totalEl = document.getElementById('cartTotalPrice');
            const countEl = document.getElementById('cartItemsCount');
            const panel = document.getElementById('cartPanel');

            counter.innerText = roomCart.length;
            countEl.innerText = `${roomCart.length} ${roomCart.length === 1 ? 'Suite' : 'Suites'} Chosen`;

            if (roomCart.length > 0) {
                panel.classList.remove('hidden');
                setTimeout(() => {
                    panel.classList.remove('translate-y-full', 'opacity-0');
                }, 10);
            } else {
                panel.classList.add('translate-y-full', 'opacity-0');
                setTimeout(() => panel.classList.add('hidden'), 500);
            }

            let total = 0;
            list.innerHTML = roomCart.map(room => {
                total += parseFloat(room.price);
                return `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100 group">
                        <div class="flex items-center space-x-4">
                            <img src="${room.img}" class="w-12 h-12 rounded-xl object-cover shadow-sm">
                            <div>
                                <p class="font-bold text-xs maroon-text">${room.name}</p>
                                <p class="text-[9px] uppercase tracking-widest text-gold font-bold">${room.type}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="font-bold text-xs text-maroon">₹${parseFloat(room.price).toLocaleString()}</span>
                            <button onclick="toggleRoomSelection(${JSON.stringify(room).replace(/"/g, '&quot;')})" class="text-gray-300 hover:text-red-500 transition-colors">
                                <i class="fas fa-trash-alt text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            totalEl.innerText = '₹' + total.toLocaleString();
        }

        function toggleCart() {
            const panel = document.getElementById('cartPanel');
            if (panel.classList.contains('translate-y-full')) {
                updateCartUI();
            } else {
                panel.classList.add('translate-y-full', 'opacity-0');
                setTimeout(() => panel.classList.add('hidden'), 500);
            }
        }

        function toggleRoomSelection(room) {
            const index = roomCart.findIndex(item => item.id === room.id);
            const btn = document.getElementById(`room-btn-${room.id}`);
            
            if (index > -1) {
                roomCart.splice(index, 1);
                if(btn) {
                    btn.classList.remove('bg-gold');
                    btn.classList.add('bg-maroon');
                    btn.querySelector('span').innerText = 'Add Stay';
                    btn.querySelector('i').className = 'fas fa-plus text-sm group-hover:rotate-90 transition-transform';
                }
            } else {
                roomCart.push(room);
                if(btn) {
                    btn.classList.remove('bg-maroon');
                    btn.classList.add('bg-gold');
                    btn.querySelector('span').innerText = 'Selected';
                    btn.querySelector('i').className = 'fas fa-check text-sm';
                }
            }
            updateCartUI();
        }

        function updateCartUI() {
            const panel = document.getElementById('cartPanel');
            const list = document.getElementById('cartItemsList');
            const totalEl = document.getElementById('cartTotalPrice');
            const countText = document.getElementById('cartItemsCount');
            const bubble = document.getElementById('cartCounter');

            if(bubble) bubble.innerText = roomCart.length;
            if(countText) countText.innerText = `${roomCart.length} Suite${roomCart.length === 1 ? '' : 's'} Chosen`;

            if (roomCart.length > 0) {
                panel.classList.remove('hidden');
                setTimeout(() => {
                    panel.classList.remove('translate-y-full', 'opacity-0');
                }, 10);
            } else {
                panel.classList.add('translate-y-full', 'opacity-0');
                setTimeout(() => panel.classList.add('hidden'), 500);
            }

            let total = 0;
            list.innerHTML = roomCart.map(room => {
                total += parseFloat(room.price);
                return `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100 group hover:border-gold/30 transition-all">
                        <div class="flex items-center space-x-3">
                            <img src="${room.img}" class="w-12 h-12 rounded-xl object-cover shadow-sm">
                            <div>
                                <p class="font-bold text-xs maroon-text">${room.name}</p>
                                <p class="text-[9px] uppercase tracking-widest text-gold font-bold">₹${parseFloat(room.price).toLocaleString()}</p>
                            </div>
                        </div>
                        <button onclick='toggleRoomSelection(${JSON.stringify(room).replace(/"/g, '&quot;')})' class="text-gray-300 hover:text-red-500 transition-colors">
                            <i class="fas fa-trash-alt text-[10px]"></i>
                        </button>
                    </div>
                `;
            }).join('');

            totalEl.innerText = '₹' + total.toLocaleString();
        }

        function proceedToBooking() {
            const modal = document.getElementById('bookingModal');
            const roomListModal = document.getElementById('modalRoomListStep1');
            
            document.getElementById('bookingStep1').classList.remove('hidden');
            document.getElementById('bookingStep2').classList.add('hidden');

            roomListModal.innerHTML = roomCart.map(r => `
                <div class="p-4 bg-maroon/5 rounded-2xl flex justify-between items-center border border-maroon/5">
                    <span class="font-black text-xs maroon-text uppercase tracking-tight">${r.name}</span>
                    <span class="text-gold font-bold text-xs">₹${parseFloat(r.price).toLocaleString()} <span class="text-[9px] text-gray-400">/ Night</span></span>
                </div>`).join('');
            
            modal.classList.remove('hidden');
        }

        async function verifyAvailability() {
            const cin = document.getElementById('book_cin').value;
            const cout = document.getElementById('book_cout').value;

            if (!cin || !cout) {
                showPremiumMessage('Dates Required', 'Please select arrival and departure dates.', 'error');
                return;
            }

            const loader = document.getElementById('verifyLoader');
            const text = document.getElementById('verifyText');
            loader.classList.remove('hidden');
            text.innerText = "Checking Portals...";

            const roomIds = roomCart.map(r => r.id).join(',');
            
            try {
                // We use the same booking script with a check-only dry run logic if added, 
                // but since we want it "Live", we can check if rooms are available in one go.
                const response = await fetch(`php/check_availability_batch.php?room_ids=${roomIds}&cin=${cin}&cout=${cout}`);
                const data = await response.json();

                if (data.success) {
                    // Calculate totals
                    const d1 = new Date(cin);
                    const d2 = new Date(cout);
                    const nights = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) || 1;
                    const subtotal = roomCart.reduce((sum, r) => sum + parseFloat(r.price), 0);
                    
                    document.getElementById('modalTotal').innerText = '₹' + (subtotal * nights).toLocaleString();
                    document.getElementById('nightsText').innerText = `Total for ${nights} Night${nights > 1 ? 's' : ''}`;
                    
                    document.getElementById('bookingStep1').classList.add('hidden');
                    document.getElementById('bookingStep2').classList.remove('hidden');
                } else {
                    showPremiumMessage('Suites Occupied', data.message, 'error');
                }
            } catch (err) {
                showPremiumMessage('Sync Error', 'Unable to verify availability.', 'error');
            } finally {
                loader.classList.add('hidden');
                text.innerText = "Verify Availability";
            }
        }

        function backToStep1() {
            document.getElementById('bookingStep1').classList.remove('hidden');
            document.getElementById('bookingStep2').classList.add('hidden');
        }

        function completeBooking() {
            const cin = document.getElementById('book_cin').value;
            const cout = document.getElementById('book_cout').value;
            const name = document.getElementById('guest_name').value.trim();
            const email = document.getElementById('guest_email').value.trim();
            const phone = document.getElementById('guest_phone').value.trim();
            const id_proof_type = document.getElementById('id_proof_type').value;
            const id_proof = document.getElementById('id_proof').value.trim();
            const address = document.getElementById('guest_address').value.trim();

            if (!name || !email || !phone || !id_proof || !address) {
                showPremiumMessage('Verification Required', 'Please complete all identification fields.', 'error');
                return;
            }

            // --- RAZORPAY PAYMENT INITIATION ---
            const d1 = new Date(cin);
            const d2 = new Date(cout);
            const nights = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) || 1;
            const subtotal = roomCart.reduce((sum, r) => sum + parseFloat(r.price), 0);
            const grandTotal = subtotal * nights;

            const options = {
                key: "rzp_test_GdsfXdgH2WnNY1",
                amount: grandTotal * 100, // Amount in paise
                currency: "INR",
                name: "Grand Luxe Hotel",
                description: "Luxury Regency Booking",
                image: "https://images.pexels.com/photos/271618/pexels-photo-271618.jpeg?auto=compress&cs=tinysrgb&w=100",
                handler: function (response) {
                    finalizeBooking(response.razorpay_payment_id);
                },
                prefill: {
                    name: name,
                    email: email,
                    contact: phone
                },
                theme: {
                    color: "#6A1E2D"
                }
            };
            const rzp = new Razorpay(options);
            
            rzp.on('payment.failed', function (response){
                showPremiumMessage('Payment Failed', response.error.description, 'error');
            });

            rzp.open();
        }

        function finalizeBooking(paymentId) {
            const cin = document.getElementById('book_cin').value;
            const cout = document.getElementById('book_cout').value;
            const name = document.getElementById('guest_name').value.trim();
            const email = document.getElementById('guest_email').value.trim();
            const phone = document.getElementById('guest_phone').value.trim();
            const id_proof_type = document.getElementById('id_proof_type').value;
            const id_proof = document.getElementById('id_proof').value.trim();
            const address = document.getElementById('guest_address').value.trim();

            const btn = document.getElementById('confirmBtn');
            const loader = document.getElementById('btnLoader');
            const text = document.getElementById('confirmText');

            text.innerText = "Finalizing Archive...";
            loader.classList.remove('hidden');
            btn.style.pointerEvents = 'none';

            const roomIds = roomCart.map(r => r.id).join(',');
            const formData = new FormData();
            formData.append('room_ids', roomIds);
            formData.append('check_in', cin);
            formData.append('check_out', cout);
            formData.append('guest_name', name);
            formData.append('guest_email', email);
            formData.append('guest_phone', phone);
            formData.append('id_proof_type', id_proof_type);
            formData.append('id_proof', id_proof);
            formData.append('guest_address', address);
            formData.append('razorpay_payment_id', paymentId);

            fetch('php/book_room.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    hideBookingModal();
                    
                    // Show Success Modal instead of Toast
                    document.getElementById('successRooms').innerText = roomCart.map(r => r.name).join(', ');
                    document.getElementById('successCin').innerText = cin;
                    document.getElementById('successCout').innerText = cout;
                    
                    const d1 = new Date(cin);
                    const d2 = new Date(cout);
                    const nights = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24)) || 1;
                    const subtotal = roomCart.reduce((sum, r) => sum + parseFloat(r.price), 0);
                    document.getElementById('successTotal').innerText = '₹' + (subtotal * nights).toLocaleString();
                    
                    document.getElementById('successModal').classList.remove('hidden');
                } else {
                    showPremiumMessage('Archive Failed', data.message, 'error');
                    text.innerText = "Confirm Booking";
                    loader.classList.add('hidden');
                    btn.style.pointerEvents = 'auto';
                }
            })
            .catch(err => {
                showPremiumMessage('System Error', 'Unable to complete reservation.', 'error');
                text.innerText = "Confirm Booking";
                loader.classList.add('hidden');
                btn.style.pointerEvents = 'auto';
            });
        }

        function hideBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        function showToast(title, desc) {
            const toast = document.getElementById('successToast');
            document.getElementById('toastTitle').innerText = title;
            document.getElementById('toastDesc').innerText = desc;
git             toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 4000);
        }

        // Initialize Flatpickr
        document.addEventListener('DOMContentLoaded', function() {
            // Specific link for Booking Pickers
            const bookCout = flatpickr("#book_cout", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                minDate: "today",
                animate: true,
                disableMobile: "true"
            });

            flatpickr("#book_cin", {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                minDate: "today",
                animate: true,
                disableMobile: "true",
                onChange: function(selectedDates, dateStr, instance) {
                    bookCout.set('minDate', dateStr);
                },
                onReady: function(selectedDates, dateStr, instance) {
                    if (dateStr) {
                        bookCout.set('minDate', dateStr);
                    }
                }
            });
        });
    </script>
</body>
</html>