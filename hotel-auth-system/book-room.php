<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$conn = require_once __DIR__ . '/config/db.php';

// Ensure phone number and other data are in session (for users already logged in)
if (!isset($_SESSION['phone'])) {
    $uid = $_SESSION['user_id'];
    $user_check = $conn->query("SELECT phone FROM users WHERE id = $uid");
    if ($user_check->num_rows > 0) {
        $u_data = $user_check->fetch_assoc();
        $_SESSION['phone'] = $u_data['phone'];
    }
}

// ONE-TIME SEED REFRESH (Add more rooms if only 6 exist)
$check_count = $conn->query("SELECT COUNT(*) as total FROM rooms");
$row_count = $check_count->fetch_assoc();
if ($row_count['total'] <= 6) {
    $conn->query("INSERT IGNORE INTO rooms (room_number, room_type, price_per_night, status) VALUES 
        ('103', 'Standard', 150.00, 'Available'),
        ('104', 'Standard', 150.00, 'Available'),
        ('203', 'Deluxe', 280.00, 'Available'),
        ('204', 'Deluxe', 280.00, 'Available'),
        ('302', 'Executive', 550.00, 'Available'),
        ('303', 'Executive', 550.00, 'Available'),
        ('402', 'Presidential', 1500.00, 'Available'),
        ('501', 'Presidential', 2000.00, 'Available')");
}

// Processing Search Filters
$cin = isset($_GET['cin']) ? $_GET['cin'] : '';
$cout = isset($_GET['cout']) ? $_GET['cout'] : '';
$type = isset($_GET['room_type']) ? $_GET['room_type'] : 'All Room Types';

$query = "SELECT * FROM rooms WHERE status = 'Available'";

if ($type != 'All Room Types') {
    $query .= " AND room_type = '" . $conn->real_escape_string($type) . "'";
}

// Optional: Filter out already booked rooms for these dates (Basic overlap check)
if (!empty($cin) && !empty($cout)) {
    $query .= " AND id NOT IN (SELECT room_id FROM bookings WHERE (check_in <= '$cout' AND check_out >= '$cin'))";
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

        .maroon-text {
            color: var(--maroon);
        }

        .gold-text {
            color: var(--gold);
        }

        .gradient-maroon {
            background: linear-gradient(135deg, #6A1E2D 0%, #832537 100%);
        }

        .sidebar-link {
            transition: all 0.3s;
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

        .premium-shadow {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05);
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .gradient-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #B8860B 100%);
        }

        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.4);
        }

        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .modal.hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(0.95);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide {
            animation: slideIn 0.6s ease-out forwards;
        }
    </style>
</head>

<body class="bg-[#F8F5F0] min-h-screen">
    <!-- Mobile Sidebar Toggle -->
    <div class="lg:hidden fixed top-6 left-6 z-[60]">
        <button onclick="toggleSidebar()"
            class="w-12 h-12 bg-white rounded-2xl premium-shadow flex items-center justify-center maroon-text">
            <i class="fas fa-bars-staggered text-xl"></i>
        </button>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebarOverlay" onclick="toggleSidebar()"
        class="fixed inset-0 bg-maroon/20 backdrop-blur-sm z-[51] hidden lg:hidden transition-opacity duration-300 opacity-0">
    </div>

    <!-- Sidebar (Same as Dashboard) -->
    <aside id="sidebar"
        class="w-72 bg-white fixed h-full border-r border-gray-100 px-6 py-8 z-[55] overflow-y-auto transition-transform duration-300 -translate-x-full lg:translate-x-0">
        <div class="mb-12 px-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">GRAND<span
                        class="gold-text">LUXE</span></h1>
                <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400 mt-1">Excellence Defined</p>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-maroon">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="space-y-2">
            <a href="customer-dashboard.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-th-large w-5"></i><span class="font-semibold">Dashboard</span>
            </a>
            <a href="book-room.php"
                class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm">
                <i class="fas fa-bed w-5"></i><span class="font-semibold">Book Room</span>
            </a>
            <a href="services.html"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-concierge-bell w-5"></i><span class="font-semibold">Services</span>
            </a>
            <a href="cleaning.html"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span>
            </a>
            <a href="feedback.html"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span>
            </a>
            <a href="history.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-history w-5"></i><span class="font-semibold">Booking History</span>
            </a>
            <div class="pt-10">
                <a href="php/logout.php"
                    class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-red-500 hover:bg-red-50 text-sm">
                    <i class="fas fa-sign-out-alt w-5"></i><span class="font-bold uppercase tracking-wider text-xs">Sign
                        Out</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-72 p-4 md:p-8">
        <!-- Top Navbar -->
        <nav
            class="glass-nav sticky top-0 flex flex-col md:flex-row justify-between items-center p-4 md:p-6 rounded-3xl mb-8 md:mb-12 z-40 premium-shadow border border-white/20 gap-4 md:gap-0">
            <div class="flex items-center space-x-4 w-full md:w-auto pl-14 lg:pl-0">
                <div class="bg-maroon/5 p-3 rounded-2xl hidden sm:block"><i class="fas fa-calendar-alt maroon-text"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Reservation</p>
                    <p class="font-bold text-sm">Plan Your Stay</p>
                </div>
            </div>
            <div class="flex items-center justify-between md:justify-end w-full md:w-auto md:space-x-8">
                <div class="relative cursor-pointer group px-4">
                    <i class="far fa-bell text-gray-400 text-xl group-hover:text-gold transition"></i>
                    <span
                        class="absolute top-0 right-3 w-4 h-4 bg-maroon text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white">2</span>
                </div>
                <div class="flex items-center space-x-4 pl-4 md:pl-8 border-l border-gray-100">
                    <div class="text-right hidden sm:block">
                        <p class="font-bold text-sm"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                        <p class="text-[10px] uppercase font-bold text-gold tracking-widest">Premium Member</p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl border-2 border-gold/20 p-1">
                        <div class="w-full h-full bg-maroon rounded-xl flex items-center justify-center text-white font-bold text-lg">
                            <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="animate-slide">
            <div class="mb-12">
                <h2 class="text-3xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Find Your
                    Perfect Room</h2>
                <p class="text-gray-500 mt-1">Select your dates and preferred room type to view our availability.</p>
            </div>

            <!-- Booking Filter -->
            <div class="bg-white rounded-[40px] p-10 premium-shadow mb-12">
                <?php $today = date('Y-m-d'); ?>
                <form action="book-room.php" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-8 items-end">
                    <div class="space-y-3">
                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">Check-In
                            Date</label>
                        <input type="date" id="cin" name="cin" value="<?php echo $cin; ?>" min="<?php echo $today; ?>" onchange="document.getElementById('cout').min = this.value"
                            class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-gold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">Check-Out
                            Date</label>
                        <input type="date" id="cout" name="cout" value="<?php echo $cout; ?>" min="<?php echo $today; ?>"
                            class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-gold outline-none">
                    </div>
                    <div class="space-y-3">
                        <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">Room
                            Type</label>
                        <select name="room_type"
                            class="w-full p-4 rounded-2xl bg-gray-50 border-none focus:ring-2 focus:ring-gold outline-none appearance-none">
                            <option <?php echo $type == 'All Room Types' ? 'selected' : ''; ?>>All Room Types</option>
                            <option <?php echo $type == 'Standard' ? 'selected' : ''; ?>>Standard</option>
                            <option <?php echo $type == 'Deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                            <option <?php echo $type == 'Executive' ? 'selected' : ''; ?>>Executive</option>
                            <option <?php echo $type == 'Presidential' ? 'selected' : ''; ?>>Presidential</option>
                        </select>
                    </div>
                    <button type="submit"
                        class="w-full gradient-maroon text-white p-5 rounded-2xl font-bold btn-glow transition shadow-lg flex items-center justify-center space-x-2">
                        <i class="fas fa-search"></i>
                        <span>Check Availability</span>
                    </button>
                </form>
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
                                <?php echo ($room['room_type'] == 'Presidential' ? '120' : ($room['room_type'] == 'Executive' ? '85' : '45')); ?> m² • King Bed • City View
                            </p>
                            <div class="flex items-center justify-between mt-auto pt-6 border-t border-gray-50">
                                <div>
                                    <p class="text-[10px] uppercase font-bold text-gray-300 tracking-widest mb-1">Starting From</p>
                                    <span class="text-2xl font-bold maroon-text">$<?php echo number_format($room['price_per_night'], 0); ?></span>
                                    <span class="text-xs text-gray-400 font-medium">/Night</span>
                                </div>
                                <button onclick="showBookingModal('<?php echo $room['room_type']; ?> Suite', <?php echo $room['price_per_night']; ?>, <?php echo $room['id']; ?>)" 
                                        class="bg-[#6A1E2D] text-white px-7 py-4 rounded-2xl font-bold hover:bg-[#D4AF37] hover:shadow-xl hover:shadow-gold/20 transition-all duration-300 transform active:scale-95 flex items-center space-x-2">
                                    <i class="fas fa-bed text-sm"></i>
                                    <span>Book Stay</span>
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
    </main>

    <!-- Booking Confirmation Modal -->
    <div id="bookingModal" class="modal hidden fixed inset-0 z-[100] flex items-center justify-center p-6 overflow-y-auto">
        <div class="absolute inset-0 bg-maroon/40 backdrop-blur-md"></div>
        <div class="bg-white rounded-[40px] p-8 md:p-10 max-width-xl relative w-full family-sans max-w-lg premium-shadow z-[101] my-8 overflow-y-auto max-h-[90vh]">
            <button onclick="hideBookingModal()" class="absolute top-6 right-6 text-gray-400 hover:text-maroon transition"><i class="fas fa-times text-xl"></i></button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gold/10 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-check-circle text-gold text-3xl"></i></div>
                <h3 class="text-2xl font-bold maroon-text">Finalize Registration</h3>
                <p class="text-gray-400 text-sm mt-1">Complete the residency archives below.</p>
            </div>
            
            <div class="space-y-4 mb-6">
                <div class="bg-gray-50 rounded-2xl p-4 flex justify-between items-center">
                    <span class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Suite Selected</span>
                    <span id="modalRoomName" class="font-bold text-sm maroon-text text-right">--</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 rounded-2xl p-3">
                        <p class="text-gray-400 text-[9px] uppercase font-bold tracking-widest mb-1 text-center">Arrival</p>
                        <p id="modalCin" class="font-bold text-xs text-center maroon-text">--</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-3">
                        <p class="text-gray-400 text-[9px] uppercase font-bold tracking-widest mb-1 text-center">Departure</p>
                        <p id="modalCout" class="font-bold text-xs text-center maroon-text">--</p>
                    </div>
                </div>

                <!-- DETAILED REGISTRATION FIELDS -->
                <div class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">Full Name</label>
                            <input type="text" id="guest_name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>"
                                class="w-full p-3 rounded-xl bg-gray-50 border border-gray-100 focus:ring-2 focus:ring-gold outline-none text-xs">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">Email Address</label>
                            <input type="email" id="guest_email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>"
                                class="w-full p-3 rounded-xl bg-gray-50 border border-gray-100 focus:ring-2 focus:ring-gold outline-none text-xs">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">Phone Number</label>
                            <input type="text" id="guest_phone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>"
                                class="w-full p-3 rounded-xl bg-gray-50 border border-gray-100 focus:ring-2 focus:ring-gold outline-none text-xs">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">ID Proof Type</label>
                            <select id="id_proof_type" 
                                class="w-full p-3 rounded-xl bg-gray-50 border border-gray-100 focus:ring-2 focus:ring-gold outline-none text-xs appearance-none">
                                <option value="Aadhar">Aadhar Card</option>
                                <option value="Passport">Passport</option>
                                <option value="PAN">PAN Card</option>
                                <option value="VoterID">Voter ID</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">ID Proof Number</label>
                            <input type="text" id="id_proof" placeholder="Enter ID Number"
                                class="w-full p-3 rounded-xl bg-gray-50 border border-gray-100 focus:ring-2 focus:ring-gold outline-none text-xs">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] uppercase tracking-widest font-extrabold text-gray-400 pl-2">Permanent Address</label>
                            <input type="text" id="guest_address" placeholder="Residential City/State"
                                class="w-full p-3 rounded-xl bg-gray-50 border border-gray-100 focus:ring-2 focus:ring-gold outline-none text-xs">
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center p-4 bg-maroon/5 rounded-2xl border border-maroon/10">
                    <span class="font-bold text-gray-500 text-sm">Residency Calculation</span>
                    <span id="modalTotal" class="text-xl font-bold maroon-text">$0</span>
                </div>
            </div>

            <button onclick="completeBooking()" id="confirmBtn" class="w-full py-4 gradient-maroon text-white rounded-2xl font-bold btn-glow transition shadow-xl flex items-center justify-center space-x-3">
                <span id="confirmText">Complete Residency</span>
                <div id="btnLoader" class="hidden w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
            </button>
        </div>
    </div>

    <!-- Success Toast -->
    <div id="successToast" class="fixed bottom-10 right-10 z-[200] hidden">
        <div class="bg-teal p-6 rounded-[24px] text-white flex items-center space-x-4 premium-shadow animate-slide">
            <div class="bg-white/20 p-2 rounded-full"><i class="fas fa-check"></i></div>
            <div>
                <p id="toastTitle" class="font-bold">Reservation Successful!</p>
                <p id="toastDesc" class="text-[10px] uppercase tracking-widest opacity-80">Welcome to Grand Luxe</p>
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

        function showBookingModal(name, price, id) {
            const cin = document.getElementById('cin').value;
            const cout = document.getElementById('cout').value;

            if (!cin || !cout) {
                alert('Please select Check-In and Check-Out dates first.');
                document.getElementById('cin').focus();
                return;
            }

            selectedRoomId = id;
            document.getElementById('modalRoomName').innerText = name;
            document.getElementById('modalCin').innerText = cin;
            document.getElementById('modalCout').innerText = cout;

            // Simple night calculation
            const d1 = new Date(cin);
            const d2 = new Date(cout);
            const diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24));
            const nights = diff > 0 ? diff : 1;
            
            document.getElementById('modalTotal').innerText = '$' + (price * nights);
            document.getElementById('bookingModal').classList.remove('hidden');
        }

        function hideBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }

        function completeBooking() {
            const cin = document.getElementById('cin').value;
            const cout = document.getElementById('cout').value;
            const name = document.getElementById('guest_name').value.trim();
            const email = document.getElementById('guest_email').value.trim();
            const phone = document.getElementById('guest_phone').value.trim();
            const id_proof_type = document.getElementById('id_proof_type').value;
            const id_proof = document.getElementById('id_proof').value.trim();
            const address = document.getElementById('guest_address').value.trim();

            if (!name || !email || !phone || !id_proof || !address) {
                alert('All registration fields are mandatory. Please provide Name, Email, Phone, ID Proof, and Address.');
                return;
            }

            const btn = document.getElementById('confirmBtn');
            const loader = document.getElementById('btnLoader');
            const text = document.getElementById('confirmText');

            text.innerText = "Finalizing...";
            loader.classList.remove('hidden');
            btn.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('room_id', selectedRoomId);
            formData.append('check_in', cin);
            formData.append('check_out', cout);
            formData.append('guest_name', name);
            formData.append('guest_email', email);
            formData.append('guest_phone', phone);
            formData.append('id_proof_type', id_proof_type);
            formData.append('id_proof', id_proof);
            formData.append('guest_address', address);

            fetch('php/book_room.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    hideBookingModal();
                    showToast('Confirmed!', 'Your residency has been archived.');
                    setTimeout(() => window.location.href = 'customer-dashboard.php', 2000);
                } else {
                    alert(data.message);
                    text.innerText = "Complete Residency";
                    loader.classList.add('hidden');
                    btn.style.pointerEvents = 'auto';
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred during booking.');
                text.innerText = "Complete Residency";
                loader.classList.add('hidden');
                btn.style.pointerEvents = 'auto';
            });
        }

        function showToast(title, desc) {
            const toast = document.getElementById('successToast');
            document.getElementById('toastTitle').innerText = title;
            document.getElementById('toastDesc').innerText = desc;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 4000);
        }
    </script>
</body>
</html>