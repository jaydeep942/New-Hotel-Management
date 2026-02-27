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

// Pagination Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get total count
$count_res = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE user_id = $user_id");
$total_bookings = $count_res->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $limit);

$history_result = $conn->query("SELECT b.*, r.room_type, r.room_number 
                                FROM bookings b 
                                JOIN rooms r ON b.room_id = r.id 
                                WHERE b.user_id = $user_id 
                                ORDER BY b.created_at DESC 
                                LIMIT $limit OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History | Grand Luxe Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: '#D4AF37',
                        cream: '#F8F5F0',
                        maroon: '#6A1E2D',
                        teal: '#2CA6A4',
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
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
        .teal-text { color: var(--teal); }

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

        .premium-table thead th {
            border-bottom: 2px solid #f8f8f8;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 2px;
            color: #999;
            padding: 25px;
        }

        .premium-table tbody tr {
            transition: all 0.3s;
            border-bottom: 1px solid #f9f9f9;
        }

        .premium-table tbody tr:hover {
            background-color: #fcfcfc;
            transform: scale(1.005);
        }

        .premium-table tbody td {
            padding: 25px;
            vertical-align: middle;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-confirmed {
            background-color: rgba(44, 166, 164, 0.1);
            color: var(--teal);
        }

        .status-completed {
            background-color: rgba(106, 30, 45, 0.1);
            color: var(--maroon);
        }

        .status-cancelled {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-slide { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

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
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span></a>
            <a href="feedback.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span></a>
            <a href="complaints.php"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-exclamation-circle w-5"></i><span class="font-semibold">Complaints</span></a>
            <a href="history.php"
                class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm"><i
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
                <div class="bg-maroon/5 p-3 rounded-2xl hidden sm:block"><i class="fas fa-book-open maroon-text"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Chronicles</p>
                    <p class="font-bold text-sm">Residency Archive</p>
                </div>
            </div>
            <div class="flex items-center justify-between md:justify-end w-full md:w-auto md:space-x-8">
                
                <div class="flex items-center space-x-4 pl-4 md:pl-8 border-l border-gray-100">
                    <div class="text-right hidden sm:block">
                        <p class="font-bold text-sm"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl overflow-hidden border-2 border-gold/20 p-1">
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
        <div class="animate-slide">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-10 gap-6">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold maroon-text"
                        style="font-family: 'Playfair Display', serif;">The Grand Archive</h2>
                    <p class="text-gray-500 mt-1 text-sm">Review your past and current residencies at our hotel group.
                    </p>
                </div>
                <div class="w-full sm:w-auto">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                        <input type="text" placeholder="Search archives..."
                            class="pl-12 pr-6 py-4 bg-white border border-gray-100 rounded-2xl focus:ring-2 focus:ring-gold outline-none text-sm w-full sm:w-80 premium-shadow">
                    </div>
                </div>
            </div>

            <!-- Booking Table -->
            <div class="bg-white rounded-[40px] overflow-hidden premium-shadow border border-gray-50">
                <div class="overflow-x-auto">
                    <table class="w-full premium-table text-left min-w-[800px]">
                        <thead>
                            <tr>
                                <th class="pl-10">Booking ID</th>
                                <th>Room Details</th>
                                 <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="pr-10 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody" class="text-sm">
                            <!-- Populated via JS -->
                            <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr class="animate-fade-in">
                                <td class="pl-10 font-bold maroon-text">#LX-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <p class="font-bold"><?php echo $row['room_type']; ?> Suite</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">Room <?php echo $row['room_number']; ?></p>
                                </td>
                                 <td><?php echo date('d M Y', strtotime($row['check_in'])); ?></td>
                                 <td>
                                    <?php if($row['status'] === 'Checked-Out' && $row['actual_checkout']): ?>
                                        <p class="font-bold text-teal-600"><?php echo date('d M Y, h:i A', strtotime($row['actual_checkout'])); ?></p>
                                        <p class="text-[9px] uppercase tracking-tighter opacity-50">Actual Departure</p>
                                    <?php else: ?>
                                        <?php echo date('d M Y', strtotime($row['check_out'])); ?>
                                    <?php endif; ?>
                                 </td>
                                 <td class="font-bold maroon-text">
                                    ₹<?php 
                                        $display_price = ($row['status'] === 'Checked-Out' && $row['final_bill']) ? $row['final_bill'] : $row['total_price'];
                                        echo number_format($display_price, 0); 
                                    ?>
                                 </td>
                                 <td><span class="status-badge <?php 
                                    $status = strtolower($row['status']);
                                    if ($status == 'confirmed' || $status == 'checked-in') echo 'status-confirmed';
                                    elseif ($status == 'checked-out' || $status == 'completed') echo 'status-completed';
                                    elseif ($status == 'cancelled') echo 'status-cancelled';
                                ?>"><?php echo $row['status']; ?></span></td>
                                <td class="pr-10 text-right">
                                    <div class="flex items-center justify-end gap-1 sm:gap-3">
                                        <?php if ($row['status'] == 'Confirmed' && strtotime($row['check_in']) > time()): ?>
                                            <button onclick="cancelBooking(<?php echo $row['id']; ?>)" class="p-2 text-red-400 hover:text-red-600 transition-all duration-300 hover:scale-125" title="Cancel Booking">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                         <button onclick='viewBooking(<?php echo json_encode($row); ?>)' class="p-2 text-gray-300 hover:text-gold transition-all duration-300 hover:scale-125" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                         <button onclick='printBooking(<?php echo json_encode($row); ?>)' class="p-2 text-gray-300 hover:text-maroon transition-all duration-300 hover:scale-125" title="Print Receipt">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($history_result->num_rows == 0): ?>
                            <tr>
                                <td colspan="7" class="p-10 text-center text-gray-400 italic">No residency archives found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination UI (AJAX version) -->
                <div id="paginationContainer" class="p-6 md:p-10 border-t border-gray-50 flex flex-col md:flex-row items-center justify-between gap-6 <?php echo $total_pages <= 1 ? 'hidden' : ''; ?>">
                    <p id="showingStats" class="text-xs text-gray-400 uppercase tracking-widest font-bold text-center">
                        Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_bookings); ?> of <?php echo $total_bookings; ?> Archives
                    </p>
                    <div class="flex items-center space-x-3">
                        <button onclick="changeHistoryPage(-1)" id="prevBtn" class="w-10 h-10 rounded-xl border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-maroon hover:text-white transition <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                            <i class="fas fa-chevron-left text-xs"></i>
                        </button>

                        <div id="pageNumbers" class="flex items-center space-x-3">
                            <!-- Numbers populated via JS -->
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)): ?>
                                    <button onclick="fetchHistory(<?php echo $i; ?>)"
                                        class="page-num-btn w-10 h-10 rounded-xl <?php echo $i == $page ? 'bg-maroon text-white active-page' : 'border border-gray-100 text-gray-400 hover:bg-gray-50'; ?> flex items-center justify-center text-xs font-bold transition-all duration-300">
                                        <?php echo $i; ?>
                                    </button>
                                <?php elseif (($i == $page - 2 && $start > 1) || ($i == $page + 2 && $end < $total_pages)): ?>
                                    <span class="text-gray-300">...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>

                        <button onclick="changeHistoryPage(1)" id="nextBtn" class="w-10 h-10 rounded-xl border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-maroon hover:text-white transition <?php echo $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                            <i class="fas fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- View Details Modal -->
    <div id="viewModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-maroon/60 backdrop-blur-xl" onclick="closeViewModal()"></div>
        <div class="bg-white rounded-[40px] w-full max-w-2xl relative z-[101] premium-shadow border border-white/20 animate-fade-in overflow-hidden">
            <!-- Modal Header -->
            <div class="gradient-maroon p-8 text-white relative">
                <button onclick="closeViewModal()" class="absolute top-6 right-8 text-white/50 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md">
                        <i class="fas fa-file-invoice text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold" style="font-family: 'Playfair Display', serif;">Residency Dossier</h3>
                        <p id="modalBookingID" class="text-[10px] uppercase tracking-[4px] font-bold text-gold/80 mt-1">#LX-0123</p>
                    </div>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-8 md:p-10 space-y-10 max-h-[70vh] overflow-y-auto custom-scrollbar">
                
                <!-- Section: Guest Identity -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <span class="w-8 h-8 rounded-lg bg-maroon/5 flex items-center justify-center text-maroon text-xs">
                            <i class="fas fa-user"></i>
                        </span>
                        <h4 class="text-xs font-bold uppercase tracking-widest text-maroon/60">Guest Identity</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/50 p-6 rounded-3xl border border-gray-100">
                        <div class="space-y-4">
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-tighter text-gray-400 block">Full Name</span>
                                <p id="modalGuestName" class="font-bold maroon-text">--</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-tighter text-gray-400 block">Contact</span>
                                <p id="modalGuestEmail" class="text-xs font-medium text-gray-600">--</p>
                                <p id="modalGuestPhone" class="text-xs font-medium text-gray-600 mt-0.5">--</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-tighter text-gray-400 block">Identification</span>
                                <p id="modalGuestID" class="text-xs font-bold text-gray-700 italic">--</p>
                            </div>
                            <div>
                                <span class="text-[9px] font-bold uppercase tracking-tighter text-gray-400 block">Residential Address</span>
                                <p id="modalGuestAddress" class="text-[11px] leading-relaxed text-gray-500 mt-1">--</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Stay Chronology -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <span class="w-8 h-8 rounded-lg bg-teal/5 flex items-center justify-center text-teal text-xs">
                            <i class="fas fa-calendar-alt"></i>
                        </span>
                        <h4 class="text-xs font-bold uppercase tracking-widest text-teal/60">Stay Chronology</h4>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                            <span class="text-[9px] font-bold uppercase tracking-tighter text-gray-400 block mb-1">Arrival</span>
                            <p id="modalCheckIn" class="font-bold maroon-text text-sm">--</p>
                        </div>
                        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100">
                            <span class="text-[9px] font-bold uppercase tracking-tighter text-gray-400 block mb-1">Departure</span>
                            <p id="modalCheckOut" class="font-bold maroon-text text-sm">--</p>
                        </div>
                        <div class="bg-gold/5 p-5 rounded-2xl border border-gold/10">
                            <span class="text-[9px] font-bold uppercase tracking-tighter text-gold block mb-1">Suite Assigned</span>
                            <p id="modalRoomType" class="font-bold maroon-text text-sm">--</p>
                        </div>
                    </div>
                </div>

                <!-- Section: Financial Audit -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center text-gold text-xs">
                            <i class="fas fa-receipt"></i>
                        </span>
                        <h4 class="text-xs font-bold uppercase tracking-widest text-gold">Financial Audit</h4>
                    </div>
                    <div class="bg-maroon/5 p-8 rounded-[32px] border border-maroon/10 flex flex-col md:flex-row justify-between items-center gap-6 text-center md:text-left">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-maroon/40 mb-1">Final Settlement Amount</p>
                            <h2 id="modalTotalPrice" class="text-4xl font-black maroon-text tracking-tighter">--</h2>
                            <p class="text-[9px] text-gray-400 mt-2 uppercase font-bold tracking-widest">Inclusive of all services & taxes</p>
                        </div>
                        <button id="modalPrintBtn" class="flex items-center space-x-3 px-8 py-4 bg-maroon text-white text-xs font-bold uppercase tracking-widest rounded-2xl shadow-xl shadow-maroon/20 hover:scale-105 transition-transform">
                            <i class="fas fa-print"></i>
                            <span>Download Receipt</span>
                        </button>
                    </div>
                </div>

                <!-- Section: Provisions -->
                <div id="modalRequestsContainer" class="hidden pt-4">
                    <div class="bg-gold/5 border border-gold/10 p-6 rounded-2xl italic text-[11px] leading-relaxed text-maroon/70 relative">
                        <i class="fas fa-quote-left absolute -top-3 -left-1 text-2xl text-gold/20"></i>
                        <span id="modalSpecialRequests">--</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-6 bg-gray-50 border-t border-gray-100 text-center">
                <button onclick="closeViewModal()" class="text-[10px] font-bold uppercase tracking-[3px] text-gray-400 hover:text-maroon transition-colors py-2">
                    Dismiss Archives
                </button>
            </div>
        </div>
    </div>
    
    <!-- Premium Cancellation Modal -->
    <div id="cancelConfirmModal" class="fixed inset-0 z-[200] hidden items-center justify-center p-6">
        <div class="absolute inset-0 bg-maroon/40 backdrop-blur-md" onclick="closeCancelModal()"></div>
        <div class="bg-white rounded-[40px] p-10 max-w-sm w-full relative z-[201] premium-shadow border border-white/20 animate-slide text-center">
            <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
            </div>
            <h3 class="text-2xl font-bold maroon-text mb-4" style="font-family: 'Playfair Display', serif;">Cancel Residency?</h3>
            <p class="text-gray-400 text-sm mb-8">This action will release your suite and archive this booking as cancelled. Are you absolutely certain?</p>
            
            <div class="flex flex-col space-y-3">
                <button id="confirmCancelBtn" class="w-full py-4 bg-red-500 text-white rounded-2xl font-bold shadow-xl shadow-red-200 hover:bg-red-600 transition-all transform active:scale-95">
                    Yes, Cancel Stay
                </button>
                <button onclick="closeCancelModal()" class="w-full py-4 bg-gray-50 text-gray-400 rounded-2xl font-bold hover:bg-gray-100 transition-all tracking-widest text-[10px] uppercase">
                    Keep Reservations
                </button>
            </div>
        </div>
    </div>

    <style>
        /* Modern Print Architecture */
        @media print {
            body { background: #fff !important; }
            body > *:not(#printableReceipt) { display: none !important; }
            
            #printableReceipt { 
                display: block !important; 
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important;
                margin: 0 !important;
                padding: 40px !important;
                border: none !important;
                visibility: visible !important;
                background: #fff !important;
            }
            
            #printableReceipt * {
                visibility: visible !important;
            }

            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            section { break-inside: avoid; page-break-inside: avoid; }
        }
        
        /* Hidden on Screen by Default */
        @media screen {
            #printableReceipt { display: none !important; }
        }
    </style>
    <div id="printableReceipt" style="font-family: 'Outfit', sans-serif; background: #fff; width: 210mm; min-height: 297mm; box-sizing: border-box;">
        <!-- Header Section -->
        <section style="border-bottom: 3px solid #6A1E2D; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 1;">
                <h1 style="font-family: 'Playfair Display', serif; color: #6A1E2D; margin: 0; font-size: 30px; letter-spacing: -1px; font-weight: 900;">GRAND LUXE</h1>
                <p style="text-transform: uppercase; letter-spacing: 5px; font-size: 9px; font-weight: 800; color: #D4AF37; margin: 4px 0 0 0;">Excellence Defined Since 1924</p>
            </div>
            <div style="text-align: right; flex: 1;">
                <div style="display: inline-block; padding: 5px 12px; border: 1px solid #D4AF37; color: #D4AF37; font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; border-radius: 4px; margin-bottom: 10px;">Official Receipt</div>
                <p style="margin: 0; font-weight: 800; color: #6A1E2D; font-size: 14px;" id="printBookingID_H">#LX-0000</p>
                <p style="margin: 2px 0 0 0; font-size: 10px; color: #999;" id="printDate">Generated: --</p>
            </div>
        </section>

        <!-- Information Columnar Grid -->
        <section style="display: flex; gap: 30px; margin-bottom: 30px;">
            <div style="flex: 1; padding: 20px; background: #fbfbfc; border-radius: 20px; border: 1px solid #f0f0f2;">
                <h4 style="text-transform: uppercase; font-size: 8px; letter-spacing: 2px; color: #D4AF37; margin: 0 0 12px 0; font-weight: 800;">Principal Guest</h4>
                <p style="margin: 0; font-weight: 800; font-size: 16px; color: #6A1E2D;" id="printGuestName"></p>
                <p style="margin: 4px 0; font-size: 12px; color: #555;" id="printGuestEmail"></p>
                <p style="margin: 0; font-size: 12px; color: #555;" id="printGuestPhone"></p>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #ddd;">
                    <p style="margin: 0; font-size: 10px; color: #888; line-height: 1.4; font-style: italic;" id="printGuestAddress"></p>
                </div>
            </div>
            <div style="flex: 1; padding: 20px; border: 1px solid #eee; border-radius: 20px; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="text-transform: uppercase; font-size: 8px; letter-spacing: 2px; color: #999; margin: 0; font-weight: 800;">Stay Information</h4>
                    <span id="printStatus" style="font-size: 7px; font-weight: 900; color: #D4AF37; border: 1px solid #D4AF37; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; letter-spacing: 1px;"></span>
                </div>
                    <p style="margin: 0; font-weight: 800; font-size: 16px; color: #6A1E2D;" id="printRoomType"></p>
                    <p style="margin: 4px 0; font-size: 12px; color: #666;">Room Number: <span id="printRoomNumber" style="font-weight: 800; color: #6A1E2D;"></span></p>
                </div>
                <div style="background: #1a1a1a; padding: 15px; border-radius: 12px; color: #fff;">
                    <div style="display: flex; justify-content: space-between; gap: 20px;">
                        <div style="flex: 1;">
                            <p style="font-size: 7px; opacity: 0.6; text-transform: uppercase;">Arrival</p>
                            <p style="font-size: 10px; font-weight: 700;" id="printCheckIn"></p>
                        </div>
                        <div style="flex: 1;">
                            <p style="font-size: 7px; opacity: 0.6; text-transform: uppercase;">Departure</p>
                            <p style="font-size: 10px; font-weight: 700;" id="printCheckOut"></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Finalized Ledger -->
        <section style="margin-bottom: 30px; overflow: hidden; border: 1px solid #e5e7eb; border-radius: 16px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 12px 20px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280;">Item Description</th>
                        <th style="padding: 12px 20px; text-align: right; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 15px 20px; border-bottom: 1px solid #f3f4f6;">
                            <strong style="color: #6A1E2D; font-size: 13px; display: block;">Residency Suite Stay</strong>
                            <span style="font-size: 10px; color: #9ca3af;">Base allocation and luxury provisions.</span>
                        </td>
                        <td style="padding: 15px 20px; border-bottom: 1px solid #f3f4f6; text-align: right; font-weight: 800; font-size: 14px; color: #1f2937;" id="printBasePrice">--</td>
                    </tr>
                    <tr id="printOrdersRow" style="display: none;">
                        <td style="padding: 15px 20px; border-bottom: 1px solid #f3f4f6;">
                            <strong style="color: #6A1E2D; font-size: 13px; display: block;">Auxiliary Orders & Dining</strong>
                            <span style="font-size: 10px; color: #9ca3af;">Aggregate of room service and refreshments.</span>
                        </td>
                        <td style="padding: 15px 20px; border-bottom: 1px solid #f3f4f6; text-align: right; font-weight: 800; font-size: 14px; color: #1f2937;" id="printServicePrice">--</td>
                    </tr>
                </tbody>
            </table>
            <!-- Final Settlement Footer -->
            <div style="background: #6A1E2D; padding: 25px; display: flex; justify-content: space-between; align-items: center; color: #fff;">
                <div>
                    <h2 style="font-family: 'Playfair Display', serif; font-size: 16px; margin: 0; font-style: italic; color: #D4AF37;">Final Audit Settlement</h2>
                    <p style="margin: 2px 0 0 0; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">Document Verified</p>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 9px; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; font-weight: 800;">Balance Due (Paid)</span>
                    <h2 style="margin: 0; font-size: 28px; font-weight: 900; letter-spacing: -1px;" id="printTotalPrice">--</h2>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <section style="text-align: center; color: #9ca3af; padding-top: 20px; border-top: 1px solid #f3f4f6;">
            <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 15px; opacity: 0.5;">
                <span style="font-size: 7px; font-weight: 900; letter-spacing: 1px;">S.E.C.U.R.E. P.A.Y.</span>
                <span style="font-size: 7px; font-weight: 900; letter-spacing: 1px;">FIVE-STAR ACCREDITATION</span>
            </div>
            <p style="font-size: 8px; line-height: 1.6; margin: 0; color: #6b7280;">
                <strong style="color: #6A1E2D;">GRAND LUXE HOTEL & RESIDENCES</strong><br>
                Marine Drive, Nariman Point, Mumbai • concierge@grandluxe.com • +91 22 1234 5678
            </p>
        </section>
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
                <div id="toastIconBlock" class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <i id="toastIcon" class="fas fa-check"></i>
                </div>
                <div>
                    <p id="toastTitleText" class="font-bold text-sm"></p>
                    <p id="toastMessageText" class="text-[10px] uppercase tracking-widest opacity-80 mt-0.5"></p>
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
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Upload failed');
                });
            }
        }

        function viewBooking(booking) {
            document.getElementById('modalBookingID').innerText = '#LX-' + booking.id.toString().padStart(4, '0');
            document.getElementById('modalGuestName').innerText = booking.guest_name;
            document.getElementById('modalGuestEmail').innerText = booking.guest_email || 'Not provided';
            document.getElementById('modalGuestPhone').innerText = booking.guest_phone || 'Not provided';
            document.getElementById('modalGuestID').innerText = `${booking.id_proof_type || 'ID'}: ${booking.id_proof_number || '---'}`;
            document.getElementById('modalGuestAddress').innerText = booking.permanent_address || 'No address recorded.';
            
            document.getElementById('modalRoomType').innerText = booking.room_type + ' Suite (#' + booking.room_number + ')';
            document.getElementById('modalCheckIn').innerText = booking.formatted_check_in || booking.check_in;
            document.getElementById('modalCheckOut').innerText = (booking.status === 'Checked-Out' && booking.formatted_actual_checkout) ? 
                booking.formatted_actual_checkout : (booking.formatted_check_out || booking.check_out);
            
            const isSettled = booking.status === 'Checked-Out' && booking.final_bill;
            const finalPrice = isSettled ? booking.final_bill : booking.total_price;
            document.getElementById('modalTotalPrice').innerText = '₹' + parseFloat(finalPrice).toLocaleString();
            
            const reqCont = document.getElementById('modalRequestsContainer');
            if (booking.special_requests && booking.special_requests.trim() !== '') {
                reqCont.classList.remove('hidden');
                document.getElementById('modalSpecialRequests').innerText = booking.special_requests;
            } else {
                reqCont.classList.add('hidden');
            }

            // Sync print button
            document.getElementById('modalPrintBtn').onclick = () => printBooking(booking);
            
            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewModal').classList.add('flex');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        function printBooking(booking) {
            document.getElementById('printBookingID_H').innerText = '#LX-' + booking.id.toString().padStart(4, '0');
            document.getElementById('printDate').innerText = 'Date: ' + new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            
            document.getElementById('printGuestName').innerText = booking.guest_name;
            document.getElementById('printGuestEmail').innerText = booking.guest_email || '';
            document.getElementById('printGuestPhone').innerText = booking.guest_phone || '';
            document.getElementById('printGuestAddress').innerText = booking.permanent_address || '';

            document.getElementById('printRoomType').innerText = booking.room_type + ' Suite';
            document.getElementById('printRoomNumber').innerText = booking.room_number;
            document.getElementById('printCheckIn').innerText = booking.formatted_check_in || booking.check_in;
            document.getElementById('printCheckOut').innerText = (booking.status === 'Checked-Out' && booking.formatted_actual_checkout) ? 
                booking.formatted_actual_checkout : (booking.formatted_check_out || booking.check_out);
            
            document.getElementById('printStatus').innerText = 'Status: ' + booking.status;
            
            const isSettled = booking.status === 'Checked-Out' && booking.final_bill;
            const finalTotal = isSettled ? booking.final_bill : booking.total_price;
            const basePrice = parseFloat(booking.total_price);
            const servicePrice = isSettled ? (parseFloat(booking.final_bill) - basePrice) : 0;

            document.getElementById('printBasePrice').innerText = '₹' + basePrice.toLocaleString();
            
            const ordersRow = document.getElementById('printOrdersRow');
            if (servicePrice > 0) {
                ordersRow.style.display = 'table-row';
                document.getElementById('printServicePrice').innerText = '₹' + servicePrice.toLocaleString();
            } else {
                ordersRow.style.display = 'none';
            }

            document.getElementById('printTotalPrice').innerText = '₹' + parseFloat(finalTotal).toLocaleString();
            
            setTimeout(() => {
                window.print();
            }, 500);
        }
        
        let currentHistoryPage = <?php echo $page; ?>;
        let totalHistoryPages = <?php echo $total_pages; ?>;

        function fetchHistory(page = 1) {
            currentHistoryPage = page;
            const tbody = document.getElementById('historyTableBody');
            tbody.classList.add('opacity-30'); // Visual feedback for loading

            fetch(`php/get_booking_history.php?page=${page}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    totalHistoryPages = data.total_pages;
                    renderHistoryTable(data.history);
                    updateHistoryPaginationUI(data);
                }
            })
            .finally(() => tbody.classList.remove('opacity-30'));
        }

        function renderHistoryTable(history) {
            const tbody = document.getElementById('historyTableBody');
            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="p-10 text-center text-gray-400 italic">No residency archives found.</td></tr>';
                return;
            }

            tbody.innerHTML = history.map(row => {
                const s = row.status.toLowerCase();
                let sClass = '';
                if (s === 'confirmed') sClass = 'status-confirmed';
                else if (s === 'completed') sClass = 'status-completed';
                else if (s === 'cancelled') sClass = 'status-cancelled';

                // Check if cancel button should show
                const canCancel = (row.status === 'Confirmed' && new Date(row.check_in) > new Date());
                const cancelBtn = canCancel ? `
                    <button onclick="cancelBooking(${row.id})" class="p-2 text-red-400 hover:text-red-600 transition-all duration-300 hover:scale-125" title="Cancel Booking">
                        <i class="fas fa-times-circle"></i>
                    </button>
                ` : '';

                return `
                    <tr class="animate-fade-in">
                        <td class="pl-10 font-bold maroon-text">#LX-${row.id.toString().padStart(4, '0')}</td>
                        <td>
                            <p class="font-bold">${row.room_type} Suite</p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Room ${row.room_number}</p>
                        </td>
                        <td>${row.formatted_check_in}</td>
                        <td>
                            ${row.status === 'Checked-Out' && row.formatted_actual_checkout ? 
                                `<p class="font-bold text-teal-600">${row.formatted_actual_checkout}</p><p class="text-[9px] uppercase tracking-tighter opacity-50">Actual Departure</p>` : 
                                row.formatted_check_out}
                        </td>
                        <td class="font-bold maroon-text">
                            ₹${row.status === 'Checked-Out' && row.formatted_final_bill ? row.formatted_final_bill : row.formatted_price}
                        </td>
                        <td><span class="status-badge ${sClass}">${row.status}</span></td>
                        <td class="pr-10 text-right">
                            <div class="flex items-center justify-end gap-1 sm:gap-3">
                                ${cancelBtn}
                                <button onclick='viewBooking(${JSON.stringify(row)})' class="p-2 text-gray-300 hover:text-gold transition-all duration-300 hover:scale-125" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick='printBooking(${JSON.stringify(row)})' class="p-2 text-gray-300 hover:text-maroon transition-all duration-300 hover:scale-125" title="Print Receipt">
                                    <i class="fas fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updateHistoryPaginationUI(data) {
            const container = document.getElementById('paginationContainer');
            if (data.total_pages <= 1) {
                container.classList.add('hidden');
                return;
            }
            container.classList.remove('hidden');

            document.getElementById('showingStats').innerText = `Showing ${data.offset + 1}-${Math.min(data.offset + data.limit, data.total_items)} of ${data.total_items} Archives`;

            const prev = document.getElementById('prevBtn');
            const next = document.getElementById('nextBtn');
            
            prev.disabled = (data.current_page <= 1);
            prev.classList.toggle('opacity-50', data.current_page <= 1);
            prev.classList.toggle('cursor-not-allowed', data.current_page <= 1);

            next.disabled = (data.current_page >= data.total_pages);
            next.classList.toggle('opacity-50', data.current_page >= data.total_pages);
            next.classList.toggle('cursor-not-allowed', data.current_page >= data.total_pages);

            // Redraw numbers
            const pageNumbers = document.getElementById('pageNumbers');
            let html = '';
            for (let i = 1; i <= data.total_pages; i++) {
                if (i === 1 || i === data.total_pages || (i >= data.current_page - 1 && i <= data.current_page + 1)) {
                    html += `
                        <button onclick="fetchHistory(${i})"
                            class="page-num-btn w-10 h-10 rounded-xl ${i === data.current_page ? 'bg-maroon text-white active-page' : 'border border-gray-100 text-gray-400 hover:bg-gray-50'} flex items-center justify-center text-xs font-bold transition-all duration-300">
                            ${i}
                        </button>
                    `;
                } else if ((i === data.current_page - 2 && i > 1) || (i === data.current_page + 2 && i < data.total_pages)) {
                    html += '<span class="text-gray-300">...</span>';
                }
            }
            pageNumbers.innerHTML = html;
        }

        function changeHistoryPage(dir) {
            const nextP = currentHistoryPage + dir;
            if (nextP >= 1 && nextP <= totalHistoryPages) {
                fetchHistory(nextP);
            }
        }

        let bookingToCancel = null;

        function cancelBooking(id) {
            bookingToCancel = id;
            const modal = document.getElementById('cancelConfirmModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Set up the confirmation button click handler
            document.getElementById('confirmCancelBtn').onclick = executeCancellation;
        }

        function closeCancelModal() {
            const modal = document.getElementById('cancelConfirmModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            bookingToCancel = null;
        }

        function executeCancellation() {
            if (!bookingToCancel) return;
            
            const btn = document.getElementById('confirmCancelBtn');
            btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i>Processing...';
            btn.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('booking_id', bookingToCancel);

            fetch('php/cancel_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeCancelModal();
                    showPremiumMessage('Stay Cancelled', 'Your residency has been released.', 'error');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showPremiumMessage('System Error', data.message, 'error');
                    btn.innerHTML = 'Yes, Cancel Stay';
                    btn.style.pointerEvents = 'auto';
                }
            })
            .catch(() => {
                showPremiumMessage('System error', 'Unable to cancel stay', 'error');
                btn.innerHTML = 'Yes, Cancel Stay';
                btn.style.pointerEvents = 'auto';
            });
        }
    </script>
</body>

</html>