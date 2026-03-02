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

// Fetch all service orders
$orders_sql = "SELECT * FROM service_orders WHERE user_id = ? ORDER BY created_at DESC";
$orders_stmt = $conn->prepare($orders_sql);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_res = $orders_stmt->get_result();
$orders = [];
while($row = $orders_res->fetch_assoc()){
    $orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Grand Luxe Hotel</title>
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
        :root { --gold: #D4AF37; --cream: #F8F5F0; --teal: #2CA6A4; --maroon: #6A1E2D; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--cream); color: #333; }
        .maroon-text { color: var(--maroon); }
        .gold-text { color: var(--gold); }
        .sidebar-link { transition: all 0.3s; }
        .sidebar-link.active { background: linear-gradient(135deg, var(--maroon) 0%, #832537 100%); color: white; box-shadow: 0 10px 20px rgba(106, 30, 45, 0.2); }
        .sidebar-link:not(.active):hover { background-color: rgba(106, 30, 45, 0.05); transform: translateX(5px); }
        .premium-shadow { box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05); }
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
        .order-card { transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .order-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(106, 30, 45, 0.05); }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(106, 30, 45, 0.1); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(106, 30, 45, 0.3); }
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
                        <a href="book-room.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
                            <span>Book Room</span>
                        </a>
                        <a href="services.php" class="nav-link active flex items-center px-7 py-4 rounded-2xl text-[15px] font-bold transition-all">
                            <span>Services</span>
                        </a>
                        <a href="cleaning.php" class="nav-link flex items-center px-7 py-4 rounded-2xl text-gray-500 hover:text-maroon text-[15px] font-bold transition-all">
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

                <!-- Right Section: Profile -->
                <div class="flex items-center space-x-8">
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
            <div id="mobileDrawer" class="absolute inset-y-0 right-0 w-80 bg-white shadow-2xl p-8 transform translate-x-full transition-transform duration-300 overflow-y-auto h-full">
                <div class="flex justify-between items-center mb-10">
                    <h2 class="text-xl font-bold maroon-text">Menu</h2>
                    <button onclick="toggleMobileMenu()" class="text-gray-400"><i class="fas fa-times text-xl"></i></button>
                </div>
                <nav class="space-y-3">
                    <a href="customer-dashboard.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-th-large"></i><span>Dashboard</span>
                    </a>
                    <a href="book-room.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 font-bold hover:bg-gray-50">
                        <i class="fas fa-bed"></i><span>Book Room</span>
                    </a>
                    <a href="services.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl font-bold">
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

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            <?php if(empty($orders)): ?>
                <div class="col-span-full py-20 text-center opacity-30">
                    <i class="fas fa-box-open text-6xl mb-6"></i>
                    <h3 class="text-2xl font-bold uppercase tracking-widest">No orders found</h3>
                    <p class="mt-2">Indulge in our culinary offerings today.</p>
                </div>
            <?php else: ?>
                <?php foreach($orders as $order): 
                    $items = json_decode($order['items'], true);
                    $statusColor = 'bg-gray-100 text-gray-500';
                    if($order['status'] == 'Pending') $statusColor = 'bg-gold/10 text-gold';
                    if($order['status'] == 'Preparing') $statusColor = 'bg-teal/10 text-teal';
                    if($order['status'] == 'Delivered') $statusColor = 'bg-green-50 text-green-600';
                    if($order['status'] == 'Cancelled') $statusColor = 'bg-red-50 text-red-600';
                ?>
                <div class="bg-white rounded-[40px] p-8 premium-shadow order-card border border-gray-50 flex flex-col">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[3px]">Order #SO-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></span>
                            <h4 class="text-lg font-bold maroon-text mt-1"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></h4>
                        </div>
                        <span class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest <?php echo $statusColor; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </div>

                    <div class="flex-1 space-y-4 mb-8 max-h-[280px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach($items as $item): ?>
                        <div class="flex justify-between items-center bg-gray-50/50 p-4 rounded-2xl border border-gray-100/50">
                            <div>
                                <p class="font-bold text-sm maroon-text"><?php echo $item['name']; ?></p>
                                <p class="text-[10px] text-gray-400">Quantity: <?php echo $item['qty']; ?></p>
                            </div>
                            <p class="font-bold text-sm gold-text">₹<?php echo number_format($item['price'] * $item['qty'], 2); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="pt-6 border-t border-gray-100 flex justify-between items-center">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Amount</p>
                        <p class="text-2xl font-black maroon-text">₹<?php echo number_format($order['total_price'], 2); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
