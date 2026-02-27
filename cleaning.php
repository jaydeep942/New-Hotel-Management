<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
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

// SECURE ACCESS CHECK: Only allow checked-in guests to REQUEST
$booking_check_sql = "SELECT * FROM bookings WHERE user_id = ? AND status IN ('Confirmed', 'Checked-In') AND CURRENT_DATE BETWEEN check_in AND check_out LIMIT 1";
$check_stmt = $conn->prepare($booking_check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$booking_status = $check_stmt->get_result()->fetch_assoc();
$canUseCleaning = $booking_status ? true : false;

$currentSuite = "Not Checked In";
if ($booking_status) {
    $room_num_sql = "SELECT room_number FROM rooms WHERE id = ?";
    $r_stmt = $conn->prepare($room_num_sql);
    $r_stmt->bind_param("i", $booking_status['room_id']);
    $r_stmt->execute();
    $r_res = $r_stmt->get_result()->fetch_assoc();
    $currentSuite = "Suite " . $r_res['room_number'];
}

// Initialize and Fetch Cleaning History
$table_init_sql = "CREATE TABLE IF NOT EXISTS housekeeping_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_init_sql);

// Alter table if status ENUM needs update (to handle existing tables)
$conn->query("ALTER TABLE housekeeping_requests MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending'");

$history_sql = "SELECT * FROM housekeeping_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$h_stmt = $conn->prepare($history_sql);
$h_stmt->bind_param("i", $user_id);
$h_stmt->execute();
$cleaning_history = $h_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleaning Request | Grand Luxe Hotel</title>
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
        }

        .maroon-text {
            color: var(--maroon);
        }

        .gold-text {
            color: var(--gold);
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

        .clean-btn {
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .clean-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 25px 50px rgba(44, 166, 164, 0.3);
        }

        .clean-btn:active {
            transform: scale(0.95);
        }

        @keyframes successPop {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .success-box {
            animation: successPop 0.5s ease-out forwards;
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

    <!-- Sidebar -->
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
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-th-large w-5"></i><span class="font-semibold">Dashboard</span></a>
            <a href="book-room.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-bed w-5"></i><span class="font-semibold">Book Room</span></a>
            <a href="services.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-concierge-bell w-5"></i><span class="font-semibold">Services</span></a>
            <a href="cleaning.php"
                class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm"><i
                    class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span></a>
            <a href="feedback.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span></a>
            <a href="complaints.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-exclamation-circle w-5"></i><span class="font-semibold">Complaints</span></a>
            <a href="history.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-history w-5"></i><span class="font-semibold">Booking History</span></a>
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
            <div class="pt-10"><a href="php/logout.php"
                    class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-red-500 hover:bg-red-50 text-sm"><i
                        class="fas fa-sign-out-alt w-5"></i><span
                        class="font-bold uppercase tracking-wider text-xs">Sign Out</span></a></div>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-72 p-4 md:p-8">
        <!-- Top Navbar -->
        <nav
            class="glass-nav sticky top-0 flex flex-col md:flex-row justify-between items-center p-4 md:p-6 rounded-3xl mb-8 md:mb-12 z-40 premium-shadow border border-white/20 gap-4 md:gap-0">
            <div class="flex items-center space-x-4 w-full md:w-auto pl-14 lg:pl-0">
                <div class="bg-maroon/5 p-3 rounded-2xl hidden sm:block"><i class="fas fa-spray-can maroon-text"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Housekeeping</p>
                    <p class="font-bold text-sm">Residency Care</p>
                </div>
            </div>
            <div class="flex items-center justify-between md:justify-end w-full md:w-auto md:space-x-8">
                <div class="flex items-center space-x-4 pl-4 md:pl-8 border-l border-gray-100">
                    <div class="text-right hidden sm:block">
                        <p class="font-bold text-sm"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl border-2 border-gold/20 p-1">
                        <?php if ($profile_photo): ?>
                            <img src="<?php echo $profile_photo; ?>" class="w-full h-full object-cover rounded-xl" alt="Profile">
                        <?php else: ?>
                            <div class="w-full h-full bg-maroon rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-6xl mx-auto animate-fade-in">
            <!-- Header Section -->
            <div class="mb-12 text-center">
                <p class="text-[10px] uppercase tracking-[6px] font-extrabold text-gold mb-4">Elite Housekeeping</p>
                <h2 class="text-4xl md:text-5xl font-bold maroon-text mb-4" style="font-family: 'Playfair Display', serif;">Your Sanctuary, Refreshed</h2>
                <div class="flex items-center justify-center space-x-2 text-gray-400 text-sm">
                    <i class="fas fa-key text-[10px] text-gold"></i>
                    <span>Currently Servicing: </span>
                    <span class="font-bold maroon-text"><?php echo $currentSuite; ?></span>
                </div>
            </div>

            <!-- Hero Section & Options -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                <!-- Hero Visual -->
                <div class="lg:col-span-5 h-[400px] lg:h-auto rounded-[60px] overflow-hidden premium-shadow relative group">
                    <img src="assets/housekeeping_hero.png" class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105" alt="Clean Room">
                    <div class="absolute inset-0 bg-gradient-to-t from-maroon/80 via-transparent to-transparent flex flex-col justify-end p-12">
                        <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/20">
                            <p class="text-white font-bold text-lg mb-2">Excellence in every corner.</p>
                            <p class="text-white/70 text-xs leading-relaxed">Our housekeeping professionals are trained to the highest standards of hygiene and luxury.</p>
                        </div>
                    </div>
                </div>

                <!-- Service Selection -->
                <div class="lg:col-span-7 space-y-6">
                    <div id="requestOptions" class="space-y-6">
                        <!-- Standard Clean -->
                        <div class="bg-white p-8 rounded-[40px] premium-shadow border border-gray-50 flex flex-col sm:flex-row items-center gap-8 group hover:border-maroon/20 transition-all cursor-pointer" onclick="<?php echo $canUseCleaning ? "requestCleaning('Full Room Refresh')" : "showPremiumAlert('Access Restricted', 'Check-in required to request service')" ?>">
                            <div class="w-20 h-20 bg-teal/5 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <i class="fas fa-broom text-teal text-3xl"></i>
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                    <h4 class="text-xl font-bold maroon-text">Full Room Refresh</h4>
                                    <span class="bg-teal/10 text-teal text-[9px] font-black uppercase tracking-wider px-3 py-1 rounded-full">Standard</span>
                                </div>
                                <p class="text-gray-400 text-sm leading-relaxed">Comprehensive cleaning, linen change, and restocking of premium amenities.</p>
                            </div>
                            <div class="hidden sm:block">
                                <i class="fas fa-chevron-right text-gray-200 group-hover:text-maroon transition-colors"></i>
                            </div>
                        </div>

                        <!-- Turn Down Service -->
                        <div class="bg-white p-8 rounded-[40px] premium-shadow border border-gray-100 flex flex-col sm:flex-row items-center gap-8 group hover:border-maroon/20 transition-all cursor-pointer" onclick="<?php echo $canUseCleaning ? "requestCleaning('Turn-Down Service')" : "showPremiumAlert('Access Restricted', 'Check-in required to request service')" ?>">
                            <div class="w-20 h-20 bg-maroon/5 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <i class="fas fa-moon text-maroon text-3xl"></i>
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                    <h4 class="text-xl font-bold maroon-text">Turn-Down Service</h4>
                                    <span class="bg-maroon/10 text-maroon text-[9px] font-black uppercase tracking-wider px-3 py-1 rounded-full">Evening Special</span>
                                </div>
                                <p class="text-gray-400 text-sm leading-relaxed">Evening bed preparation, mood lighting, and replenishment of bedside minerals.</p>
                            </div>
                            <div class="hidden sm:block">
                                <i class="fas fa-chevron-right text-gray-200 group-hover:text-maroon transition-colors"></i>
                            </div>
                        </div>

                        <!-- Deep Sanitization -->
                        <div class="bg-white p-8 rounded-[40px] premium-shadow border border-gray-100 flex flex-col sm:flex-row items-center gap-8 group hover:border-maroon/20 transition-all cursor-pointer" onclick="<?php echo $canUseCleaning ? "requestCleaning('Deep Refresh')" : "showPremiumAlert('Access Restricted', 'Check-in required to request service')" ?>">
                            <div class="w-20 h-20 bg-gold/5 rounded-full flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <i class="fas fa-hand-sparkles text-gold text-3xl"></i>
                            </div>
                            <div class="flex-1 text-center sm:text-left">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                                    <h4 class="text-xl font-bold maroon-text">Deep Sanitization</h4>
                                    <span class="bg-gold/10 text-gold text-[9px] font-black uppercase tracking-wider px-3 py-1 rounded-full">Express</span>
                                </div>
                                <p class="text-gray-400 text-sm leading-relaxed">High-touch point sanitization and floor steam treatment for ultimate hygiene.</p>
                            </div>
                            <div class="hidden sm:block">
                                <i class="fas fa-chevron-right text-gray-200 group-hover:text-maroon transition-colors"></i>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-16 text-center text-gray-400 text-xs font-bold uppercase tracking-[3px]">
                Quality Guaranteed &bull; 24/7 Professional Housekeeping
            </div>

            <!-- Request History Section -->
            <div class="mt-20 border-t border-gray-100 pt-20">
                <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-teal"></span>
                            </span>
                            <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400">Live Service Monitor</p>
                        </div>
                        <h3 class="text-3xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Request History</h3>
                    </div>
                    <div class="flex items-center gap-6">
                        <button onclick="refreshCleaningHistory()" class="text-teal font-bold text-xs uppercase tracking-widest flex items-center gap-2 hover:underline">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh</span>
                        </button>
                        <button onclick="openAllHistory()" class="bg-maroon text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-gold transition-all shadow-lg shadow-maroon/20">
                            See All Request
                        </button>
                    </div>
                </div>

                <div id="cleaningHistoryGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if (empty($cleaning_history)): ?>
                        <div class="col-span-full bg-white p-12 rounded-[40px] premium-shadow border border-dashed border-gray-200 text-center">
                            <i class="fas fa-history text-gray-200 text-4xl mb-4"></i>
                            <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No Recent Requests</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cleaning_history as $request): ?>
                            <div class="bg-white p-8 rounded-[40px] premium-shadow border border-gray-50 flex flex-col justify-between hover:border-gold/20 transition-all group animate-fade-in">
                                <div>
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="w-12 h-12 bg-maroon/5 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-broom maroon-text"></i>
                                        </div>
                                        <?php 
                                            $statusClass = 'text-gold bg-gold/5';
                                            $statusIcon = 'fa-clock-rotate-left';
                                            if ($request['status'] === 'In Progress') {
                                                $statusClass = 'text-blue-500 bg-blue-50';
                                                $statusIcon = 'fa-spinner fa-spin';
                                            } elseif ($request['status'] === 'Completed') {
                                                $statusClass = 'text-teal bg-teal/5';
                                                $statusIcon = 'fa-check';
                                            } elseif ($request['status'] === 'Cancelled') {
                                                $statusClass = 'text-maroon bg-maroon/5';
                                                $statusIcon = 'fa-ban';
                                            }
                                        ?>
                                        <span class="flex items-center space-x-2 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
                                            <i class="fas <?php echo $statusIcon; ?>"></i>
                                            <span><?php echo $request['status']; ?></span>
                                        </span>
                                    </div>
                                    <h5 class="font-bold maroon-text text-lg mb-2"><?php echo htmlspecialchars($request['service_type']); ?></h5>
                                    <div class="flex items-center space-x-2 text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-6">
                                        <i class="fas fa-door-closed text-[8px]"></i>
                                        <span><?php echo $request['room_number']; ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                                    <div class="flex items-center gap-2 text-[10px] text-gray-500 font-bold">
                                        <i class="far fa-clock text-gold"></i>
                                        <span><?php echo date('d M, Y • H:i', strtotime($request['created_at'])); ?></span>
                                    </div>
                                    <?php if ($request['status'] === 'Pending'): ?>
                                        <button onclick="cancelCurrentRequest(<?php echo $request['id']; ?>)" class="text-[10px] font-black uppercase tracking-tighter text-maroon hover:text-gold transition-colors flex items-center gap-1">
                                            <span>Cancel</span>
                                            <i class="fas fa-times-circle text-[8px]"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-200 transition-colors"><i class="fas fa-check-double text-[10px]"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

        <!-- Full History Modal -->
    <div id="allHistoryModal" class="fixed inset-0 z-[120] hidden overflow-y-auto">
        <div class="fixed inset-0 bg-maroon/40 backdrop-blur-xl transition-opacity duration-300 opacity-0" id="historyOverlay" onclick="closeAllHistory()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-5xl rounded-[60px] shadow-2xl overflow-hidden transform scale-90 opacity-0 transition-all duration-300" id="historyContent">
                <div class="p-12">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
                        <div class="text-center md:text-left">
                            <h2 class="text-4xl font-bold maroon-text mb-2" style="font-family: 'Playfair Display', serif;">Engagement Archive</h2>
                            <p class="text-gray-400 text-sm font-medium">A comprehensive record of your housekeeping requests.</p>
                        </div>
                        <button onclick="closeAllHistory()" class="w-14 h-14 bg-gray-50 text-gray-400 rounded-2xl flex items-center justify-center hover:bg-maroon hover:text-white transition-all shadow-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div id="fullHistoryList" class="space-y-4 max-h-[60vh] overflow-y-auto pr-4 custom-scrollbar">
                        <!-- Full history items will be injected here -->
                    </div>

                    <div class="mt-12 pt-10 border-t border-gray-50 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="flex items-center gap-4 text-xs font-bold text-gray-400 uppercase tracking-widest">
                            <i class="fas fa-file-contract text-gold text-sm"></i>
                            <span>Official Service Log &bull; Grand Luxe Hotel</span>
                        </div>
                        <button onclick="closeAllHistory()" class="px-10 py-4 border-2 border-maroon text-maroon rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-maroon hover:text-white transition-all">
                            Back to Portal
                        </button>
                    </div>
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

        <!-- Service Success Modal -->
    <div id="successServiceModal" class="fixed inset-0 z-[130] hidden">
        <div id="successOverlay" class="absolute inset-0 bg-maroon/20 backdrop-blur-md transition-opacity duration-300 opacity-0 cursor-pointer" onclick="closeSuccessModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div id="successContent" class="bg-white w-full max-w-lg rounded-[60px] premium-shadow overflow-hidden transform scale-90 opacity-0 transition-all duration-300 pointer-events-auto p-12 text-center">
                <div class="w-24 h-24 bg-teal rounded-full flex items-center justify-center mb-8 shadow-2xl shadow-teal/30 mx-auto animate-bounce-slow">
                    <i class="fas fa-check text-white text-4xl"></i>
                </div>
                <h3 class="text-3xl font-bold maroon-text mb-4" style="font-family: 'Playfair Display', serif;">Request Submitted</h3>
                <p class="text-gray-400 leading-relaxed mb-10 text-sm">
                    Your <span id="popConfirmedService" class="font-bold text-teal">service</span> request has been submitted successfully. Our elite housekeeping team will be with you shortly.
                </p>
                <div class="flex justify-center">
                    <button onclick="closeSuccessModal()" class="w-full max-w-[240px] py-4 bg-teal text-white rounded-2xl font-bold text-[10px] uppercase tracking-widest hover:bg-gold transition-all shadow-xl shadow-teal/20 flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        <span>Understood</span>
                    </button>
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

        async function refreshCleaningHistory() {
            const grid = document.getElementById('cleaningHistoryGrid');
            try {
                const res = await fetch('php/get_cleaning_history.php?limit=6');
                const data = await res.json();
                
                if (data.success) {
                    if (data.history.length === 0) {
                        grid.innerHTML = `
                            <div class="col-span-full bg-white p-12 rounded-[40px] premium-shadow border border-dashed border-gray-200 text-center">
                                <i class="fas fa-history text-gray-200 text-4xl mb-4"></i>
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No Recent Requests</p>
                            </div>`;
                        return;
                    }

                    grid.innerHTML = data.history.map(request => {
                        let statusClass = 'text-gold bg-gold/5';
                        let statusIcon = 'fa-clock-rotate-left';
                        if (request.status === 'In Progress') {
                            statusClass = 'text-blue-500 bg-blue-50';
                            statusIcon = 'fa-spinner fa-spin';
                        } else if (request.status === 'Completed') {
                            statusClass = 'text-teal bg-teal/5';
                            statusIcon = 'fa-check';
                        } else if (request.status === 'Cancelled') {
                            statusClass = 'text-maroon bg-maroon/5';
                            statusIcon = 'fa-ban';
                        }

                        const date = new Date(request.created_at);
                        const formattedDate = `${date.getDate()} ${date.toLocaleString('default', { month: 'short' })}, ${date.getFullYear()} • ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

                        let actionSection = `<span class="text-gray-200 transition-colors"><i class="fas fa-check-double text-[10px]"></i></span>`;
                        if (request.status === 'Pending') {
                            actionSection = `
                                <button onclick="cancelCurrentRequest(${request.id})" class="text-[10px] font-black uppercase tracking-tighter text-maroon hover:text-gold transition-colors flex items-center gap-1">
                                    <span>Cancel</span>
                                    <i class="fas fa-times-circle text-[8px]"></i>
                                </button>`;
                        }

                        return `
                            <div class="bg-white p-8 rounded-[40px] premium-shadow border border-gray-50 flex flex-col justify-between hover:border-gold/20 transition-all group animate-fade-in">
                                <div>
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="w-12 h-12 bg-maroon/5 rounded-2xl flex items-center justify-center">
                                            <i class="fas fa-broom maroon-text"></i>
                                        </div>
                                        <span class="flex items-center space-x-2 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${statusClass}">
                                            <i class="fas ${statusIcon}"></i>
                                            <span>${request.status}</span>
                                        </span>
                                    </div>
                                    <h5 class="font-bold maroon-text text-lg mb-2">${request.service_type}</h5>
                                    <div class="flex items-center space-x-2 text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-6">
                                        <i class="fas fa-door-closed text-[8px]"></i>
                                        <span>${request.room_number}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                                    <div class="flex items-center gap-2 text-[10px] text-gray-500 font-bold">
                                        <i class="far fa-clock text-gold"></i>
                                        <span>${formattedDate}</span>
                                    </div>
                                    ${actionSection}
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            } catch (err) {
                console.error("Failed to refresh history:", err);
            }
        }

        async function openAllHistory() {
            const list = document.getElementById('fullHistoryList');
            const modal = document.getElementById('allHistoryModal');
            const overlay = document.getElementById('historyOverlay');
            const content = document.getElementById('historyContent');
            
            list.innerHTML = '<div class="text-center py-20"><i class="fas fa-circle-notch fa-spin maroon-text text-3xl"></i></div>';
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.add('opacity-100');
                content.classList.remove('scale-90', 'opacity-0');
            }, 10);
            
            try {
                const res = await fetch('php/get_cleaning_history.php?limit=50');
                const data = await res.json();
                
                if (data.success) {
                    if (data.history.length === 0) {
                        list.innerHTML = '<p class="text-center text-gray-400 py-10 font-bold uppercase tracking-widest text-xs">No records found</p>';
                    } else {
                        list.innerHTML = data.history.map(req => {
                            let statusClass = 'text-gold bg-gold/5';
                            if (req.status === 'In Progress') statusClass = 'text-blue-500 bg-blue-50';
                            else if (req.status === 'Completed') statusClass = 'text-teal bg-teal/5';
                            else if (req.status === 'Cancelled') statusClass = 'text-maroon bg-maroon/5';

                            const date = new Date(req.created_at);
                            const dateStr = `${date.getDate()} ${date.toLocaleString('default', { month: 'short' })}, ${date.getFullYear()}`;
                            const timeStr = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

                            let cancelBtn = '';
                            if (req.status === 'Pending') {
                                cancelBtn = `
                                    <button onclick="cancelCurrentRequest(${req.id})" class="text-[9px] font-bold text-maroon hover:text-gold uppercase tracking-widest flex items-center gap-1 border border-maroon/20 px-3 py-1 rounded-lg">
                                        Cancel <i class="fas fa-times text-[7px]"></i>
                                    </button>`;
                            }

                            return `
                                <div class="bg-gray-50/50 p-6 rounded-3xl border border-gray-100 flex items-center justify-between group hover:bg-white hover:shadow-xl transition-all">
                                    <div class="flex items-center gap-6">
                                        <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center shadow-sm">
                                            <i class="fas fa-broom maroon-text"></i>
                                        </div>
                                        <div>
                                            <h6 class="font-bold maroon-text text-sm mb-1">${req.service_type}</h6>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                                <i class="fas fa-door-closed text-[8px] mr-1"></i> Room ${req.room_number} &bull; ${dateStr}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right flex items-center gap-6">
                                        ${cancelBtn}
                                        <div>
                                            <span class="inline-block px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest ${statusClass} mb-2">
                                                ${req.status}
                                            </span>
                                            <p class="text-[14px] text-gray-500 font-bold">${timeStr}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    }
                }
            } catch (err) {
                list.innerHTML = '<p class="text-center text-maroon py-10">Error loading history</p>';
            }
        }

        function closeAllHistory() {
            const modal = document.getElementById('allHistoryModal');
            const overlay = document.getElementById('historyOverlay');
            const content = document.getElementById('historyContent');
            
            overlay.classList.remove('opacity-100');
            content.classList.add('scale-90', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function requestCleaning(serviceName = 'Full Room Refresh') {
            const formData = new FormData();
            formData.append('service_type', serviceName);

            fetch('php/request_cleaning.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('popConfirmedService').innerText = serviceName;
                    openSuccessModal();
                    refreshCleaningHistory(); // Refresh history real-time
                } else {
                    showPremiumAlert('Request Failed', data.message);
                }
            })
            .catch(err => {
                console.error(err);
                showPremiumAlert('System Error', 'Unable to connect to service.');
            });
        }

        function openSuccessModal() {
            const modal = document.getElementById('successServiceModal');
            const overlay = document.getElementById('successOverlay');
            const content = document.getElementById('successContent');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.add('opacity-100');
                content.classList.remove('scale-90', 'opacity-0');
            }, 10);
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successServiceModal');
            const overlay = document.getElementById('successOverlay');
            const content = document.getElementById('successContent');
            
            overlay.classList.remove('opacity-100');
            content.classList.add('scale-90', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function cancelAndClose() {
            cancelCurrentRequest(0); // Cancel latest
            closeSuccessModal();
        }

        function cancelCurrentRequest(id = 0) {
            const formData = new FormData();
            if (id > 0) formData.append('id', id);

            fetch('php/cancel_cleaning.php', { 
                method: 'POST',
                body: formData 
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showPremiumMessage('Action Successful', 'Request has been cancelled.');
                    if (id === 0) resetRequest(); // Only reset view if called from success screen
                    refreshCleaningHistory();
                    
                    // If modal is open, we might want to refresh its content too
                    if (!document.getElementById('allHistoryModal').classList.contains('hidden')) {
                        openAllHistory();
                    }
                } else {
                    showPremiumAlert('Action Failed', data.message);
                }
            });
        }

        function resetRequest() {
            document.getElementById('requestOptions').classList.remove('hidden');
            document.getElementById('successMsg').classList.add('hidden');
        }

        // Auto-refresh history every 10 seconds for "Real-Time" feel
        setInterval(refreshCleaningHistory, 10000);

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