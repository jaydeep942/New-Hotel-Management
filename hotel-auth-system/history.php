<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$conn = require_once __DIR__ . '/config/db.php';
$user_id = $_SESSION['user_id'];
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

        .teal-text {
            color: var(--teal);
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
            <a href="services.html"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-concierge-bell w-5"></i><span class="font-semibold">Services</span></a>
            <a href="cleaning.html"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span></a>
            <a href="feedback.html"
                class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i
                    class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span></a>
            <a href="history.php"
                class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm"><i
                    class="fas fa-history w-5"></i><span class="font-semibold">Booking History</span></a>
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
                <div class="relative cursor-pointer group px-4">
                    <i class="far fa-bell text-gray-400 text-xl group-hover:text-gold transition"></i>
                    <span
                        class="absolute top-0 right-3 w-4 h-4 bg-maroon text-white text-[10px] flex items-center justify-center rounded-full border-2 border-white">2</span>
                </div>
                <div class="flex items-center space-x-4 pl-4 md:pl-8 border-l border-gray-100">
                    <div class="text-right hidden sm:block">
                        <p class="font-bold text-sm">Respected Guest</p>
                        <p class="text-[10px] uppercase font-bold text-gold tracking-widest">Premium Member</p>
                    </div>
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl border-2 border-gold/20 p-1">
                        <img src="https://ui-avatars.com/api/?name=Guest&background=6A1E2D&color=fff"
                            class="w-full h-full rounded-xl object-cover" alt="Avatar">
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
                                <td><span class="status-badge <?php echo strtolower($row['status']) == 'confirmed' ? 'status-confirmed' : 'status-completed'; ?>"><?php echo $row['status']; ?></span></td>
                                <td class="pr-10 text-right">
                                    <button class="p-2 text-gray-300 hover:text-gold transition"><i class="fas fa-eye"></i></button>
                                    <button class="p-2 text-gray-300 hover:text-maroon transition"><i class="fas fa-print"></i></button>
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