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

$history_result = $conn->query("SELECT b.*, r.room_type, r.room_number 
                                FROM bookings b 
                                JOIN rooms r ON b.room_id = r.id 
                                WHERE b.user_id = $user_id 
                                ORDER BY b.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History | Grand Luxe Hotel</title>
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
                        <tbody class="text-sm">
                            <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td class="pl-10 font-bold maroon-text">#LX-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <p class="font-bold"><?php echo $row['room_type']; ?> Suite</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">Room <?php echo $row['room_number']; ?></p>
                                </td>
                                 <td><?php echo date('d M Y', strtotime($row['check_in'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['check_out'])); ?></td>
                                <td class="font-bold maroon-text">₹<?php echo number_format($row['total_price'], 0); ?></td>
                                 <td><span class="status-badge <?php 
                                    $status = strtolower($row['status']);
                                    if ($status == 'confirmed') echo 'status-confirmed';
                                    elseif ($status == 'completed') echo 'status-completed';
                                    elseif ($status == 'cancelled') echo 'status-cancelled';
                                ?>"><?php echo $row['status']; ?></span></td>
                                <td class="pr-10 text-right">
                                    <div class="flex items-center justify-end gap-1 sm:gap-3">
                                        <?php if ($row['status'] == 'Confirmed' && strtotime($row['check_in']) > time()): ?>
                                            <button onclick="cancelBooking(<?php echo $row['id']; ?>)" class="p-2 text-red-400 hover:text-red-600 transition-all duration-300 hover:scale-125" title="Cancel Booking">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                         <button onclick="viewBooking(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="p-2 text-gray-300 hover:text-gold transition-all duration-300 hover:scale-125" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                         <button onclick="printBooking(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="p-2 text-gray-300 hover:text-maroon transition-all duration-300 hover:scale-125" title="Print Receipt">
                                            <i class="fas fa-print"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($history_result->num_rows == 0): ?>
                            <tr>
                                <td colspan="6" class="p-10 text-center text-gray-400 italic">No residency archives found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination UI -->
                <div
                    class="p-6 md:p-10 border-t border-gray-50 flex flex-col md:flex-row items-center justify-between gap-6">
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-bold text-center">Showing 1-3 of 12
                        Archives</p>
                    <div class="flex items-center space-x-3">
                        <button
                            class="w-10 h-10 rounded-xl border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-maroon hover:text-white transition"><i
                                class="fas fa-chevron-left text-xs"></i></button>
                        <button
                            class="w-10 h-10 rounded-xl bg-maroon text-white flex items-center justify-center text-xs font-bold">1</button>
                        <button
                            class="w-10 h-10 rounded-xl border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-gray-50 transition text-xs font-bold">2</button>
                        <button
                            class="w-10 h-10 rounded-xl border border-gray-100 flex items-center justify-center text-gray-400 hover:bg-maroon hover:text-white transition"><i
                                class="fas fa-chevron-right text-xs"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- View Details Modal -->
    <div id="viewModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-6">
        <div class="absolute inset-0 bg-maroon/40 backdrop-blur-md" onclick="closeViewModal()"></div>
        <div class="bg-white rounded-[40px] p-10 max-w-lg w-full relative z-[101] premium-shadow border border-white/20 animate-slide">
            <button onclick="closeViewModal()" class="absolute top-6 right-8 text-gray-400 hover:text-maroon">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-maroon/5 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-invoice maroon-text text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Residency Details</h3>
                <p id="modalBookingID" class="text-[10px] uppercase tracking-[4px] font-bold text-gold mt-2">#LX-0000</p>
            </div>
            
            <div class="space-y-6">
                <div class="flex justify-between border-b border-gray-50 pb-4">
                    <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">Guest Name</span>
                    <span id="modalGuestName" class="font-bold text-sm maroon-text">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-50 pb-4">
                    <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">Room Type</span>
                    <span id="modalRoomType" class="font-bold text-sm maroon-text">-</span>
                </div>
                <div class="grid grid-cols-2 gap-8 py-4 bg-gray-50 rounded-3xl px-6">
                    <div>
                        <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Arrival</span>
                        <p id="modalCheckIn" class="font-bold text-sm maroon-text">-</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Departure</span>
                        <p id="modalCheckOut" class="font-bold text-sm maroon-text">-</p>
                    </div>
                </div>
                <div class="flex justify-between pt-2">
                    <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">Total Amount Paid</span>
                    <span id="modalTotalPrice" class="text-2xl font-bold maroon-text">-</span>
                </div>
            </div>
            
            <button onclick="closeViewModal()" class="w-full mt-10 py-4 bg-maroon text-white rounded-2xl font-bold shadow-xl shadow-maroon/20 hover:scale-[1.02] transition-transform">
                Close Archive
            </button>
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
        #printableReceipt { display: none; }
        @media print {
            body > *:not(#printableReceipt) { display: none !important; }
            #printableReceipt { 
                display: block !important; 
                position: absolute; 
                top: 0; 
                left: 0; 
                width: 100%; 
                padding: 40px; 
                color: #333;
                background: white !important;
            }
            #printableReceipt .print-header, 
            #printableReceipt .print-details, 
            #printableReceipt .print-footer,
            #printableReceipt p,
            #printableReceipt span,
            #printableReceipt h1 { 
                display: block !important; 
                visibility: visible !important; 
            }
            #printableReceipt .label { display: inline-block !important; font-weight: bold; color: #6A1E2D; margin-right: 10px; }
            #printableReceipt p { margin-bottom: 15px; }
            .print-header { border-bottom: 2px solid #6A1E2D; padding-bottom: 20px; margin-bottom: 30px; text-align: center; }
            .print-details { margin-bottom: 30px; line-height: 1.6; }
            .print-footer { border-top: 1px solid #eee; padding-top: 20px; text-align: center; font-size: 12px; color: #999; }
        }
    </style>
    <!-- Hidden Printable Receipt -->
    <div id="printableReceipt">
        <div class="print-header">
            <h1 style="font-family: serif; color: #6A1E2D;">GRAND LUXE HOTEL</h1>
            <p>Official Residency Receipt</p>
        </div>
        <div class="print-details">
            <p><span class="label">Residency ID:</span> <span id="printBookingID"></span></p>
            <p><span class="label">Guest Name:</span> <span id="printGuestName"></span></p>
            <p><span class="label">Suite:</span> <span id="printRoomType"></span> (Room <span id="printRoomNumber"></span>)</p>
            <p><span class="label">Check In:</span> <span id="printCheckIn"></span></p>
            <p><span class="label">Check Out:</span> <span id="printCheckOut"></span></p>
            <p style="font-size: 20px;"><span class="label">Total Paid:</span> <span id="printTotalPrice"></span></p>
        </div>
        <div class="print-footer">
            <p>Thank you for choosing Grand Luxe. We look forward to your next visit.</p>
            <p>Mumbai • Nariman Point • Excellence Defined</p>
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
            document.getElementById('modalRoomType').innerText = booking.room_type + ' Suite';
            document.getElementById('modalCheckIn').innerText = booking.check_in;
            document.getElementById('modalCheckOut').innerText = booking.check_out;
            document.getElementById('modalTotalPrice').innerText = '₹' + parseInt(booking.total_price).toLocaleString();
            
            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewModal').classList.add('flex');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('viewModal').classList.remove('flex');
        }

        function printBooking(booking) {
            document.getElementById('printBookingID').innerText = '#LX-' + booking.id.toString().padStart(4, '0');
            document.getElementById('printGuestName').innerText = booking.guest_name;
            document.getElementById('printRoomType').innerText = booking.room_type + ' Suite';
            document.getElementById('printRoomNumber').innerText = booking.room_number;
            document.getElementById('printCheckIn').innerText = booking.check_in;
            document.getElementById('printCheckOut').innerText = booking.check_out;
            document.getElementById('printTotalPrice').innerText = '₹' + parseInt(booking.total_price).toLocaleString();
            
            window.print();
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