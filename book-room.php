<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$conn = require_once __DIR__ . '/config/db.php';

// Ensure phone number and other data are in session (for users already logged in)
// Fetch full user details
$user_id = $_SESSION['user_id'];
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

// Logic: A room is unavailable if it has a confirmed booking that overlaps with the searched dates
if (!empty($cin) && !empty($cout)) {
    $query .= " AND id NOT IN (
        SELECT room_id FROM bookings 
        WHERE status != 'Cancelled' 
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
                    <div class="hidden xl:flex items-center space-x-1">
                        <a href="customer-dashboard.php" class="nav-link flex items-center px-6 py-3.5 rounded-2xl text-gray-500 hover:text-maroon text-[13px] font-bold transition-all">
                            <span>Dashboard</span>
                        </a>
                        <a href="book-room.php" class="nav-link active flex items-center px-6 py-3.5 rounded-2xl text-[13px] font-bold transition-all">
                            <span>Book Room</span>
                        </a>
                        <a href="services.php" class="nav-link flex items-center px-6 py-3.5 rounded-2xl text-gray-500 hover:text-maroon text-[13px] font-bold transition-all">
                            <span>Services</span>
                        </a>
                        <a href="cleaning.php" class="nav-link flex items-center px-6 py-3.5 rounded-2xl text-gray-500 hover:text-maroon text-[13px] font-bold transition-all">
                            <span>Cleaning</span>
                        </a>
                        <a href="history.php" class="nav-link flex items-center px-6 py-3.5 rounded-2xl text-gray-500 hover:text-maroon text-[13px] font-bold transition-all">
                            <span>History</span>
                        </a>
                        <a href="feedback.php" class="nav-link flex items-center px-6 py-3.5 rounded-2xl text-gray-500 hover:text-maroon text-[13px] font-bold transition-all">
                            <span>Feedback</span>
                        </a>
                        <a href="complaints.php" class="nav-link flex items-center px-6 py-3.5 rounded-2xl text-gray-500 hover:text-maroon text-[13px] font-bold transition-all">
                            <span>Complaints</span>
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

                    <!-- Profile & Manage -->
                    <div class="flex items-center space-x-4">
                        <div class="hidden sm:block text-right">
                            <p class="font-bold text-sm maroon-text"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                            <button onclick="openProfileModal()" class="text-[9px] uppercase font-black text-gold tracking-widest hover:text-maroon transition-colors shadow-sm bg-white px-2 py-0.5 rounded border border-gold/20">Manage Profile</button>
                        </div>
                        <div class="relative group cursor-pointer" onclick="openProfileModal()">
                            <div class="w-10 h-10 rounded-xl overflow-hidden border-2 border-gold/20 p-1 transition-transform group-hover:scale-105">
                                <?php if ($profile_photo): ?>
                                    <img src="<?php echo $profile_photo; ?>" class="w-full h-full object-cover rounded-lg" alt="Profile">
                                <?php else: ?>
                                    <div class="w-full h-full bg-maroon rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="php/logout.php" class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm" title="Sign Out">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                        <!-- Mobile Menu Trigger -->
                        <div class="xl:hidden">
                            <button onclick="toggleMobileMenu()" class="w-10 h-10 bg-white rounded-xl shadow-sm flex items-center justify-center maroon-text">
                                <i class="fas fa-bars-staggered"></i>
                            </button>
                        </div>
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
                <div class="mb-12">
                    <h2 class="text-3xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Find Your Perfect Room</h2>
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
                    <span id="modalTotal" class="text-xl font-bold maroon-text">₹0</span>
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
        <div class="bg-teal p-6 rounded-[24px] text-white flex items-center space-x-4 premium-shadow animate-up">
            <div class="bg-white/20 p-2 rounded-full"><i class="fas fa-check"></i></div>
            <div>
                <p id="toastTitle" class="font-bold">Reservation Successful!</p>
                <p id="toastDesc" class="text-[10px] uppercase tracking-widest opacity-80">Welcome to Grand Luxe</p>
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

        function proceedToBooking() {
            const cin = document.getElementById('cin').value;
            const cout = document.getElementById('cout').value;

            if (!cin || !cout) {
                showPremiumMessage('Dates Missing', 'Please select Check-In and Check-Out dates first.', 'error');
                document.getElementById('cin').focus();
                return;
            }

            const modal = document.getElementById('bookingModal');
            const roomListModal = document.getElementById('modalRoomName');
            
            // Format rooms for modal
            roomListModal.innerHTML = roomCart.map(r => `<div class="bg-maroon/5 p-3 rounded-xl mb-2 flex justify-between items-center"><span class="font-bold text-xs">${r.name}</span><span class="text-gold font-bold text-xs">₹${parseFloat(r.price).toLocaleString()}</span></div>`).join('');
            
            document.getElementById('modalCin').innerText = cin;
            document.getElementById('modalCout').innerText = cout;

            // Simple night calculation
            const d1 = new Date(cin);
            const d2 = new Date(cout);
            const timeDiff = d2.getTime() - d1.getTime();
            const diff = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
            const nights = (isNaN(diff) || diff <= 0) ? 1 : diff;
            
            const subtotal = roomCart.reduce((sum, r) => sum + parseFloat(r.price), 0);
            document.getElementById('modalTotal').innerText = '₹' + (subtotal * nights).toLocaleString();
            
            modal.classList.remove('hidden');
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
                showPremiumMessage('Fields Required', 'All guest details are mandatory.', 'error');
                return;
            }

            const btn = document.getElementById('confirmBtn');
            const loader = document.getElementById('btnLoader');
            const text = document.getElementById('confirmText');

            text.innerText = "Finalizing Stay...";
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

            fetch('php/book_room.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('bookingModal').classList.add('hidden');
                    showToast('Residencies Confirmed!', 'Your multi-suite stay has been archived.');
                    setTimeout(() => window.location.href = 'customer-dashboard.php', 2000);
                } else {
                    showPremiumMessage('Booking Error', data.message, 'error');
                    text.innerText = "Complete Residency";
                    loader.classList.add('hidden');
                    btn.style.pointerEvents = 'auto';
                }
            })
            .catch(err => {
                console.error(err);
                showPremiumMessage('System Error', 'Unable to complete reservation.', 'error');
                text.innerText = "Complete Residency";
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
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 4000);
        }

    </script>
</body>
</html>