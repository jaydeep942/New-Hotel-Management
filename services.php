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

// Fetch menu items
$menu_sql = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category";
$menu_res = $conn->query($menu_sql);
$food_items = [];
$drink_items = [];
$amenity_items = [];
while($row = $menu_res->fetch_assoc()){
    if ($row['category'] === 'Refreshments') {
        $drink_items[] = $row;
    } elseif ($row['category'] === 'Amenities') {
        $amenity_items[] = $row;
    } else {
        $food_items[] = $row;
    }
}

// SECURE ACCESS CHECK: Only allow checked-in guests to ORDER
$booking_check_sql = "SELECT b.*, r.room_number FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.user_id = ? AND b.status IN ('Confirmed', 'Checked-In') AND CURRENT_DATE BETWEEN b.check_in AND b.check_out LIMIT 1";
$check_stmt = $conn->prepare($booking_check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$booking_status = $check_stmt->get_result()->fetch_assoc();
$canUseServices = $booking_status ? true : false;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Services | Grand Luxe Hotel</title>
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
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--cream);
            color: #333;
            overflow-x: hidden;
        }

        .maroon-text { color: var(--maroon); }
        .gold-text { color: var(--gold); }
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

        .premium-shadow { box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05); }
        .glass-nav { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }

        .service-card {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .service-card:hover {
            transform: scale(1.02);
            box-shadow: 0 40px 80px rgba(106, 30, 45, 0.1);
        }
        .service-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom, transparent 40%, rgba(106, 30, 45, 0.8));
            opacity: 0.6;
            transition: opacity 0.5s;
        }
        .service-card:hover::after { opacity: 0.9; }

        .menu-item-card {
            transition: all 0.4s ease;
            border: 1px solid rgba(212, 175, 55, 0.05);
        }
        .menu-item-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(106, 30, 45, 0.05);
            border-color: var(--gold);
        }

        .category-pill.active {
            background-color: var(--maroon);
            color: white;
            box-shadow: 0 4px 12px rgba(106, 30, 45, 0.2);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-up { animation: slideUp 0.6s ease-out forwards; }

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

        .hidden-service { display: none !important; }

        /* Modern Cart Styles */
        .cart-container {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(106, 30, 45, 0.05);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }
        
        .cart-item-image {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
        }

        .qty-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--gold);
            color: white;
            font-size: 10px;
            font-weight: 800;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(212, 175, 55, 0.3);
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e2e2; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--maroon); }

        /* Food Order Style Bottom Bar */
        .bottom-checkout-bar {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            width: calc(100% - 48px);
            max-width: 600px;
            background: var(--maroon);
            padding: 16px 24px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 100;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 20px 40px rgba(106, 30, 45, 0.3);
        }

        .bottom-checkout-bar.active {
            transform: translateX(-50%) translateY(0);
        }

        .view-cart-btn {
            background: white;
            color: var(--maroon);
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
        }
        
        .view-cart-btn:hover {
            background: var(--gold);
            color: white;
            transform: scale(1.05);
        }

        #mobileCartDrawer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: auto;
            max-height: 85vh;
            background: white;
            z-index: 200;
            transition: transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
            border-radius: 40px 40px 0 0;
            overflow: hidden;
            box-shadow: 0 -20px 50px rgba(0,0,0,0.15);
            transform: translateY(100%);
            display: flex;
            flex-direction: column;
        }

        #mobileCartDrawer.open {
            transform: translateY(0);
        }

        .bottom-checkout-bar.drawer-open {
            opacity: 0;
            transform: translateX(-50%) translateY(100px);
            pointer-events: none;
        }
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
        <div class="mb-12 px-4">
            <h1 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">GRAND<span class="gold-text">LUXE</span></h1>
            <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400 mt-1">Excellence Defined</p>
        </div>
        <nav class="space-y-2">
            <a href="customer-dashboard.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-th-large w-5"></i><span class="font-semibold">Dashboard</span></a>
            <a href="book-room.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-bed w-5"></i><span class="font-semibold">Book Room</span></a>
            <a href="services.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm"><i class="fas fa-concierge-bell w-5"></i><span class="font-semibold">Services</span></a>
            <a href="cleaning.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span></a>
            <a href="feedback.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span></a>
            <a href="complaints.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-exclamation-circle w-5"></i><span class="font-semibold">Complaints</span></a>
            <a href="history.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-history w-5"></i><span class="font-semibold">Booking History</span></a>
            <!-- Manage Profile with Dropdown -->
            <div class="space-y-1">
                <button onclick="toggleProfileMenu()" class="w-full sidebar-link flex items-center justify-between p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm transition-all">
                    <div class="flex items-center space-x-4">
                        <i class="fas fa-user-circle w-5"></i>
                        <span class="font-semibold">Manage Profile</span>
                    </div>
                    <i id="profileChevron" class="fas fa-chevron-down text-[10px] transition-transform duration-300"></i>
                </button>
                <div id="profileSubmenu" class="hidden pl-12 space-y-3 py-2 animate-slide">
                    <button onclick="openProfileModal('profile-info')" class="flex items-center space-x-3 text-xs font-bold text-gray-400 hover:text-maroon transition-colors w-full text-left">
                        <div class="w-1.5 h-1.5 rounded-full bg-current opacity-20"></div>
                        <span>Personal Details</span>
                    </button>
                    <button onclick="openProfileModal('security-settings')" class="flex items-center space-x-3 text-xs font-bold text-gray-400 hover:text-maroon transition-colors w-full text-left">
                        <div class="w-1.5 h-1.5 rounded-full bg-current opacity-20"></div>
                        <span>Security & Password</span>
                    </button>
                </div>
            </div>
            <div class="pt-10"><a href="php/logout.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-red-500 hover:bg-red-50 text-sm"><i class="fas fa-sign-out-alt w-5"></i><span class="font-bold uppercase tracking-wider text-xs">Sign Out</span></a></div>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-72 p-4 md:p-8">
        <!-- Top Navbar -->
        <nav class="glass-nav sticky top-0 flex flex-col md:flex-row justify-between items-center p-4 md:p-6 rounded-3xl mb-8 md:mb-12 z-40 premium-shadow border border-white/20 gap-4 md:gap-0">
            <div class="flex items-center space-x-4 w-full md:w-auto pl-14 lg:pl-0">
                <div class="bg-maroon/5 p-3 rounded-2xl hidden sm:block"><i class="fas fa-concierge-bell maroon-text"></i></div>
                <div id="navTitle">
                    <h2 class="text-xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Resident Services</h2>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Curated Excellence</p>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl border-2 border-gold/20 p-1">
                    <?php if ($profile_photo): ?>
                        <img src="<?php echo $profile_photo; ?>" class="w-full h-full object-cover rounded-xl" alt="Profile">
                    <?php else: ?>
                        <div class="w-full h-full bg-maroon rounded-xl flex items-center justify-center text-white font-bold text-lg"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- STEP 1: SERVICE TYPES SELECTION -->
        <div id="serviceSelection" class="animate-up">
            <div class="mb-12 text-center md:text-left">
                <h2 class="text-4xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">How may we assist you?</h2>
                <p class="text-gray-500 mt-2">Select a premium service to enhance your residency.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <!-- Type 1: In-Suite Dining -->
                <div onclick="openService('dining')" class="service-card h-[500px] rounded-[40px] bg-[url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&q=82&w=800')] bg-cover bg-center flex flex-col justify-end p-10 group">
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-utensils text-white text-2xl"></i>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2" style="font-family: 'Playfair Display', serif;">Gourmet Dining</h3>
                        <p class="text-white/80 text-sm mb-6">Explore our Michelin-starred in-suite vegetarian selections.</p>
                        <span class="px-6 py-3 bg-white text-maroon font-bold rounded-xl text-xs uppercase tracking-widest group-hover:bg-gold group-hover:text-white transition-colors">Order Now</span>
                    </div>
                </div>

                <!-- Type 2: Beverage Service -->
                <div onclick="openService('water')" class="service-card h-[500px] rounded-[40px] bg-[url('https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&q=82&w=800')] bg-cover bg-center flex flex-col justify-end p-10 group">
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-wine-bottle text-white text-2xl"></i>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2" style="font-family: 'Playfair Display', serif;">Pure Refreshments</h3>
                        <p class="text-white/80 text-sm mb-6">Premium bottled water and chilled beverages at your door.</p>
                        <span class="px-6 py-3 bg-white text-maroon font-bold rounded-xl text-xs uppercase tracking-widest group-hover:bg-teal group-hover:text-white transition-colors">Order Drinks</span>
                    </div>
                </div>

                <!-- Type 3: Guest Amenities -->
                <div onclick="openService('amenities')" class="service-card h-[500px] rounded-[40px] bg-[url('https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&q=82&w=800')] bg-cover bg-center flex flex-col justify-end p-10 group">
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-bed text-white text-2xl"></i>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2" style="font-family: 'Playfair Display', serif;">Luxury Amenities</h3>
                        <p class="text-white/80 text-sm mb-6">Extra bedding, pillows, or toiletries for ultimate comfort.</p>
                        <span class="px-6 py-3 bg-white text-maroon font-bold rounded-xl text-xs uppercase tracking-widest group-hover:bg-gold group-hover:text-white transition-colors">Add to Room</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: FOOD SERVICE VIEW (Hidden initially) -->
        <div id="diningService" class="hidden-service animate-up">
            <div class="w-full">
                <button onclick="goBack()" class="mb-8 flex items-center space-x-2 text-gray-400 hover:text-maroon font-bold text-xs uppercase tracking-widest transition-all">
                    <i class="fas fa-arrow-left"></i> <span>Back to Services</span>
                </button>

                <div class="mb-10 flex flex-col gap-6">
                    <div>
                        <p class="text-[10px] uppercase tracking-[3px] text-gray-400 font-bold mb-4">Pure Vegetarian Cuisine</p>
                        <div class="flex flex-wrap gap-3">
                            <button onclick="updateFilter('cuisine', 'all')" class="cuisine-btn active px-6 py-2.5 rounded-xl text-xs font-bold border border-gray-100 category-pill" data-cuisine="all">All Cuisines</button>
                            <?php 
                            $cuisines = ['Gujarati', 'Punjabi', 'Chinese', 'South Indian'];
                            foreach($cuisines as $c): ?>
                            <button onclick="updateFilter('cuisine', '<?php echo $c; ?>')" class="cuisine-btn px-6 py-2.5 rounded-xl text-xs font-bold border border-gray-100 category-pill" data-cuisine="<?php echo $c; ?>"><?php echo $c; ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Menu Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-8" id="menuGrid">
                    <?php foreach($food_items as $item): ?>
                    <div class="menu-item bg-white rounded-[40px] overflow-hidden menu-item-card group" data-category="<?php echo $item['category']; ?>">
                        <div class="h-56 relative overflow-hidden">
                            <img src="<?php echo $item['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="<?php echo $item['name']; ?>" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=82&w=800'">
                            <div class="absolute bottom-4 right-4 bg-maroon/90 backdrop-blur-md px-3 py-1.5 rounded-lg text-white font-bold text-[9px] uppercase tracking-[2px]">
                                <?php echo $item['meal_type']; ?>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="text-lg font-bold maroon-text"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <span class="font-black text-maroon text-lg">₹<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <p class="text-gray-400 text-xs leading-relaxed mb-6"><?php echo htmlspecialchars($item['description']); ?></p>
                            
                            <div class="flex items-center justify-between border-t border-gray-50 pt-4">
                                <div class="flex items-center space-x-4 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, -1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $item['image_url']; ?>')" class="text-maroon hover:scale-125 transition-transform"><i class="fas fa-minus text-[10px]"></i></button>
                                    <span id="item-qty-<?php echo $item['id']; ?>" class="font-bold text-sm min-w-[20px] text-center">0</span>
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, 1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $item['image_url']; ?>')" class="text-maroon hover:scale-125 transition-transform"><i class="fas fa-plus text-[10px]"></i></button>
                                </div>
                                <span class="text-[9px] font-black text-gold uppercase tracking-[2px] bg-gold/5 px-2 py-1 rounded-md"><?php echo $item['category']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            </div>
        </div>

        <!-- STEP 3: REFRESHMENT SERVICE VIEW -->
        <div id="refreshmentService" class="hidden-service animate-up">
            <div class="w-full">
                <button onclick="goBack()" class="mb-8 flex items-center space-x-2 text-gray-400 hover:text-teal font-bold text-xs uppercase tracking-widest transition-all">
                    <i class="fas fa-arrow-left"></i> <span>Back to Services</span>
                </button>

                </div>

                <!-- Beverages Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-8">
                    <?php foreach($drink_items as $item): ?>
                    <div class="bg-white rounded-[40px] overflow-hidden menu-item-card group border border-gray-50">
                        <div class="h-56 relative overflow-hidden">
                            <img src="<?php echo $item['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="<?php echo $item['name']; ?>">
                            <div class="absolute top-4 right-4 bg-teal/90 backdrop-blur-md px-3 py-1.5 rounded-lg text-white font-bold text-[9px] uppercase tracking-[2px]">
                                <?php echo $item['sub_category']; ?>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="text-lg font-bold maroon-text"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <span class="font-black text-teal text-lg">₹<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <p class="text-gray-400 text-xs leading-relaxed mb-6"><?php echo htmlspecialchars($item['description']); ?></p>
                            
                            <div class="flex items-center justify-between border-t border-gray-50 pt-4">
                                <div class="flex items-center space-x-4 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, -1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $item['image_url']; ?>')" class="text-teal hover:scale-125 transition-transform"><i class="fas fa-minus text-[10px]"></i></button>
                                    <span id="item-qty-<?php echo $item['id']; ?>" class="font-bold text-sm min-w-[20px] text-center">0</span>
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, 1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $item['image_url']; ?>')" class="text-teal hover:scale-125 transition-transform"><i class="fas fa-plus text-[10px]"></i></button>
                                </div>
                                <span class="text-[9px] font-black text-teal/40 uppercase tracking-[2px] bg-teal/5 px-2 py-1 rounded-md">Premium</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <!-- STEP 4: AMENITIES SERVICE VIEW -->
        <div id="amenitiesService" class="hidden-service animate-up">
            <div class="w-full">
                <button onclick="goBack()" class="mb-8 flex items-center space-x-2 text-gray-400 hover:text-gold font-bold text-xs uppercase tracking-widest transition-all">
                    <i class="fas fa-arrow-left"></i> <span>Back to Services</span>
                </button>

                <!-- Amenities Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-8">
                    <?php foreach($amenity_items as $item): ?>
                    <div class="bg-white rounded-[40px] overflow-hidden menu-item-card group border border-gray-50">
                        <div class="h-56 relative overflow-hidden">
                            <img src="<?php echo $item['image_url']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="<?php echo $item['name']; ?>">
                            <div class="absolute top-4 right-4 bg-gold/90 backdrop-blur-md px-3 py-1.5 rounded-lg text-white font-bold text-[9px] uppercase tracking-[2px]">
                                <?php echo $item['sub_category']; ?>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="text-lg font-bold maroon-text"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <span class="font-black text-gold text-lg">₹<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <p class="text-gray-400 text-xs leading-relaxed mb-6"><?php echo htmlspecialchars($item['description']); ?></p>
                            
                            <div class="flex items-center justify-between border-t border-gray-50 pt-4">
                                <div class="flex items-center space-x-4 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, -1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $item['image_url']; ?>')" class="text-gold hover:scale-125 transition-transform"><i class="fas fa-minus text-[10px]"></i></button>
                                    <span id="item-qty-<?php echo $item['id']; ?>" class="font-bold text-sm min-w-[20px] text-center">0</span>
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, 1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $item['image_url']; ?>')" class="text-gold hover:scale-125 transition-transform"><i class="fas fa-plus text-[10px]"></i></button>
                                </div>
                                <span class="text-[9px] font-black text-gold/40 uppercase tracking-[2px] bg-gold/5 px-2 py-1 rounded-md">Luxury</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Mobile Sticky Checkout Bar -->
    <div id="stickyCheckout" class="bottom-checkout-bar">
        <div class="flex items-center gap-4 text-white">
            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center">
                <i class="fas fa-shopping-basket text-lg"></i>
            </div>
            <div>
                <p class="text-[10px] uppercase font-bold opacity-60 leading-none mb-1">Items Added</p>
                <p class="font-black text-lg">₹<span class="cartTotalValue">0.00</span></p>
            </div>
        </div>
        <button onclick="toggleMobileCart()" class="view-cart-btn">View My Basket</button>
    </div>

    <!-- Mobile Cart Drawer -->
    <div id="mobileCartDrawer">
        <div class="px-8 pt-10 pb-8 flex-1 overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Your Selection</h3>
                    <p class="text-[9px] uppercase tracking-[2px] text-gray-400 font-bold mt-1">Review Items</p>
                </div>
                <button onclick="toggleMobileCart()" class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 hover:bg-maroon hover:text-white transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="mobileCartItemsList" class="space-y-6 mb-8">
                <!-- Populated by JS -->
            </div>

            <div class="border-t border-dashed border-gray-100 pt-8 mt-4 space-y-3">
                <div class="flex justify-between items-center text-xs font-bold text-gray-400 uppercase tracking-widest">
                    <span>Subtotal</span>
                    <span class="text-gray-600">₹<span class="subtotalValue">0.00</span></span>
                </div>
                <div class="flex justify-between items-center text-sm font-bold maroon-text pt-2">
                    <span class="uppercase tracking-widest">Total Amount</span>
                    <span class="text-xl">₹<span class="cartTotalValue">0.00</span></span>
                </div>
            </div>
        </div>
        <div class="px-8 pb-10">
            <button onclick="placeDiningOrder('mobile')" class="w-full py-5 bg-maroon text-white rounded-[24px] font-bold shadow-xl shadow-maroon/20 flex items-center justify-center gap-3 hover:bg-gold transition-all">
                <span>Place Order Now</span>
                <i class="fas fa-arrow-right text-[10px]"></i>
            </button>
        </div>
    </div>

    <div id="drawerOverlay" onclick="toggleMobileCart()" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[190] hidden opacity-0 transition-opacity duration-300"></div>

    <!-- Success Toast -->


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

        <!-- Premium Alert Modal -->
        <div id="premiumAlertModal" class="fixed inset-0 z-[300] hidden">
            <div id="alertBackdrop" class="absolute inset-0 bg-black/20 backdrop-blur-md transition-opacity duration-300 opacity-0" onclick="closePremiumAlert()"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
                <div id="alertContent" class="bg-white w-full max-w-sm rounded-[40px] shadow-2xl p-10 text-center transform scale-90 opacity-0 transition-all duration-300 pointer-events-auto border border-gray-100">
                    <div id="alertIconContainer" class="w-20 h-20 bg-maroon/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i id="alertIcon" class="fas fa-shield-alt text-4xl text-maroon"></i>
                    </div>
                    <h3 id="alertTitle" class="text-2xl font-bold maroon-text mb-2" style="font-family: 'Playfair Display', serif;">Security Protocol</h3>
                    <p id="alertMessage" class="text-gray-400 text-sm leading-relaxed mb-8">Access restricted for verified guests only.</p>
                    <button onclick="closePremiumAlert()" class="w-full py-4 bg-maroon text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-maroon/20">
                        Acknowledge
                    </button>
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
        let activeCuisine = 'all';
        let cart = {};
        const canUseServices = <?php echo $canUseServices ? 'true' : 'false'; ?>;

        // Handle direct links from Dashboard
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const service = urlParams.get('service');
            if (service) {
                openService(service);
            }
        };

        function openService(type) {
            document.getElementById('serviceSelection').classList.add('hidden');
            document.getElementById('diningService').classList.add('hidden-service');
            document.getElementById('refreshmentService').classList.add('hidden-service');
            document.getElementById('amenitiesService').classList.add('hidden-service');

            if (type === 'dining') {
                document.getElementById('diningService').classList.remove('hidden-service');
                updateFilter('cuisine', 'all');
            } else if (type === 'water') {
                document.getElementById('refreshmentService').classList.remove('hidden-service');
            } else if (type === 'amenities') {
                document.getElementById('amenitiesService').classList.remove('hidden-service');
            } else {
                if (!canUseServices) {
                    showPremiumAlert("Access Restricted", "You are not curruntly stay in hotel");
                    return;
                }
                showToast('Request Received!', 'Our staff will deliver to your room shortly.');
                document.getElementById('serviceSelection').classList.remove('hidden');
            }
        }

        function goBack() {
            document.getElementById('diningService').classList.add('hidden-service');
            document.getElementById('refreshmentService').classList.add('hidden-service');
            document.getElementById('amenitiesService').classList.add('hidden-service');
            document.getElementById('serviceSelection').classList.remove('hidden');
        }

        function updateFilter(type, value) {
            activeCuisine = value;
            document.querySelectorAll('.cuisine-btn').forEach(btn => {
                if(btn.getAttribute('data-cuisine') === activeCuisine) btn.classList.add('active');
                else btn.classList.remove('active');
            });
            document.querySelectorAll('.menu-item').forEach(item => {
                item.style.display = (activeCuisine === 'all' || item.getAttribute('data-category') === activeCuisine) ? 'block' : 'none';
            });
        }

        function updateCartItem(id, delta, name, price, image) {
            if (!cart[id]) {
                if(delta < 0) return;
                cart[id] = { name: name, price: price, qty: 0, image: image };
            }
            cart[id].qty += delta;
            if (cart[id].qty < 0) cart[id].qty = 0;
            
            // Update UI count in all possible grids
            document.querySelectorAll('[id^="item-qty-' + id + '"]').forEach(el => el.innerText = cart[id].qty);
            
            renderCart();
        }

        function toggleMobileCart() {
            const drawer = document.getElementById('mobileCartDrawer');
            const overlay = document.getElementById('drawerOverlay');
            const stickyBar = document.getElementById('stickyCheckout');
            
            if (drawer.classList.contains('open')) {
                drawer.classList.remove('open');
                stickyBar.classList.remove('drawer-open');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
                document.body.classList.remove('overflow-hidden');
            } else {
                drawer.classList.add('open');
                stickyBar.classList.add('drawer-open');
                overlay.classList.remove('hidden');
                setTimeout(() => overlay.classList.remove('opacity-0'), 10);
                document.body.classList.add('overflow-hidden');
            }
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            const containerRef = document.getElementById('cartItemsRefresh');
            const containerAme = document.getElementById('cartItemsAmenities');
            const mobileContainer = document.getElementById('mobileCartItemsList');
            let html = '', subtotal = 0;
            
            for (const id in cart) {
                if (cart[id].qty > 0) {
                    const itemSub = cart[id].qty * cart[id].price;
                    subtotal += itemSub;
                    html += `
                        <div class="flex items-center gap-4 group animate-fade-in">
                            <div class="relative flex-shrink-0">
                                <img src="${cart[id].image}" class="cart-item-image" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=82&w=800'">
                                <div class="qty-badge">${cart[id].qty}</div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold maroon-text leading-tight truncate mb-1">${cart[id].name}</h4>
                                <div class="flex items-center gap-2">
                                    <button onclick="updateCartItem(${id}, -1)" class="w-6 h-6 rounded-lg bg-gray-50 flex items-center justify-center text-[10px] text-gray-400 hover:bg-maroon hover:text-white transition-all"><i class="fas fa-minus"></i></button>
                                    <button onclick="updateCartItem(${id}, 1)" class="w-6 h-6 rounded-lg bg-gray-50 flex items-center justify-center text-[10px] text-gray-400 hover:bg-teal hover:text-white transition-all"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] text-gray-400 font-bold mb-1">₹${cart[id].price}</p>
                                <span class="text-xs font-black maroon-text">₹${itemSub.toFixed(2)}</span>
                            </div>
                        </div>`;
                }
            }
            
            const emptyDiningHtml = `
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                        <i class="fas fa-utensils text-3xl"></i>
                    </div>
                    <p class="text-[10px] uppercase tracking-[3px] text-gray-300 font-bold">Your basket is empty</p>
                </div>`;
            
            const emptyRefreshHtml = `
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                        <i class="fas fa-glass-whiskey text-3xl"></i>
                    </div>
                    <p class="text-[10px] uppercase tracking-[3px] text-gray-300 font-bold">Tray is Empty</p>
                </div>`;

            const emptyAmenityHtml = `
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                        <i class="fas fa-bed text-3xl"></i>
                    </div>
                    <p class="text-[10px] uppercase tracking-[3px] text-gray-300 font-bold">Selection Empty</p>
                </div>`;
            
            if(container) container.innerHTML = html || emptyDiningHtml;
            if(containerRef) containerRef.innerHTML = html || emptyRefreshHtml;
            if(containerAme) containerAme.innerHTML = html || emptyAmenityHtml;
            if(mobileContainer) mobileContainer.innerHTML = html || `<div class='text-center py-10 text-gray-300 text-xs font-bold uppercase tracking-widest'>No items selected</div>`;
            
            const total = subtotal; // Removing service charge for simplicity as it was confusing
            
            document.querySelectorAll('.subtotalValue').forEach(el => el.innerText = subtotal.toFixed(2));
            document.querySelectorAll('.cartTotalValue, #cartTotal').forEach(el => el.innerText = total.toFixed(2));
            
            const orderBtn = document.getElementById('orderBtn');
            const orderBtnRef = document.getElementById('orderBtnRefresh');
            const orderBtnAme = document.getElementById('orderBtnAmenities');
            if(orderBtn) orderBtn.disabled = subtotal === 0;
            if(orderBtnRef) orderBtnRef.disabled = subtotal === 0;
            if(orderBtnAme) orderBtnAme.disabled = subtotal === 0;

            // Handle Sticky Checkout Bar visibility
            const stickyBar = document.getElementById('stickyCheckout');
            if (subtotal > 0) {
                stickyBar.classList.add('active');
            } else {
                stickyBar.classList.remove('active');
                if (document.getElementById('mobileCartDrawer').classList.contains('open')) {
                    toggleMobileCart();
                }
            }
        }

        function placeDiningOrder(serviceType = 'dining') {
            if (!canUseServices) {
                showPremiumAlert("Access Restricted", "You are not curruntly stay in hotel");
                return;
            }
            let btn;
            if (serviceType === 'dining') btn = document.getElementById('orderBtn');
            else if (serviceType === 'refresh') btn = document.getElementById('orderBtnRefresh');
            else if (serviceType === 'amenities') btn = document.getElementById('orderBtnAmenities');
            else btn = document.querySelector('#mobileCartDrawer button');
            
            let totalStr = "0.00";
            const totalEl = document.querySelector('.cartTotalValue') || document.getElementById('cartTotal');
            if(totalEl) totalStr = totalEl.innerText;
            const totalVal = parseFloat(totalStr);
            
            const cartItems = [];
            for (const id in cart) {
                if (cart[id].qty > 0) {
                    cartItems.push({
                        name: cart[id].name,
                        price: cart[id].price,
                        qty: cart[id].qty
                    });
                }
            }

            if (cartItems.length === 0) return;

            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('items', JSON.stringify(cartItems));
            formData.append('total_price', totalVal);

            fetch('php/place_order.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text()) // Get raw text first to handle non-json errors
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        let successTitle = 'Order Received!';
                        let successMsg = 'Chef is preparing your meal.';
                        
                        if (serviceType === 'refresh') {
                            successTitle = 'Beverage Request!';
                            successMsg = 'Our barista is preparing your drinks.';
                        } else if (serviceType === 'amenities' || serviceType === 'mobile' || serviceType === 'room') {
                            successTitle = 'Request Received!';
                            successMsg = 'Our staff will deliver to your room shortly.';
                        }

                        showPremiumMessage(successTitle, successMsg);
                        cart = {}; 
                        renderCart();
                        document.querySelectorAll('[id^="item-qty-"]').forEach(el => el.innerText = '0');
                        setTimeout(() => goBack(), 2000);
                    } else {
                        showPremiumAlert("Order Issue", data.message);
                    }
                } catch (e) {
                    console.error("Non-JSON Response:", text);
                    showPremiumAlert("System Error", "The server returned an invalid response. Please try again.");
                }
                btn.innerHTML = originalText;
                btn.disabled = false;
            })
            .catch(err => {
                console.error("Fetch Error:", err);
                showPremiumAlert("System Error", "An error occurred while connecting to the order service.");
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay?.classList.remove('hidden');
                setTimeout(() => overlay?.classList.add('opacity-100'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay?.classList.remove('opacity-100');
                setTimeout(() => overlay?.classList.add('hidden'), 300);
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

        function showToast(title, sub) {
            showPremiumMessage(title, sub, 'success');
        }

        function showPremiumAlert(title, message, type = 'error') {
            const modal = document.getElementById('premiumAlertModal');
            const content = document.getElementById('alertContent');
            const titleEl = document.getElementById('alertTitle');
            const msgEl = document.getElementById('alertMessage');
            const iconEl = document.getElementById('alertIcon');
            const iconContainer = document.getElementById('alertIconContainer');

            titleEl.innerText = title;
            msgEl.innerText = message;
            
            if (type === 'error') {
                iconEl.className = 'fas fa-shield-alt text-4xl text-maroon';
                iconContainer.className = 'w-20 h-20 bg-maroon/10 rounded-full flex items-center justify-center mx-auto mb-6';
            } else {
                iconEl.className = 'fas fa-check-circle text-4xl text-teal';
                iconContainer.className = 'w-20 h-20 bg-teal/10 rounded-full flex items-center justify-center mx-auto mb-6';
            }

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => {
                document.getElementById('alertBackdrop').classList.add('opacity-100');
                content.classList.remove('scale-90', 'opacity-0');
            }, 10);
        }

        function closePremiumAlert() {
            const modal = document.getElementById('premiumAlertModal');
            const content = document.getElementById('alertContent');
            
            document.getElementById('alertBackdrop').classList.remove('opacity-100');
            content.classList.add('scale-90', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }

    </script>
</body>
</html>