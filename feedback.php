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

// AUTO-INITIALIZE FEEDBACK TABLE IF NOT EXISTS
$table_init_sql = "CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(50) NOT NULL,
    rating INT NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($table_init_sql);

// SECURE ACCESS CHECK: Only allow checked-in guests to SUBMIT
$booking_check_sql = "SELECT * FROM bookings WHERE user_id = ? AND status IN ('Confirmed', 'Checked-In') AND CURRENT_DATE BETWEEN check_in AND check_out LIMIT 1";
$check_stmt = $conn->prepare($booking_check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$booking_status = $check_stmt->get_result()->fetch_assoc();
$canUseFeedback = $booking_status ? true : false;

// Fetch initial total pages for pagination
$count_query = "SELECT COUNT(*) as total FROM feedbacks WHERE user_id = ?";
$c_stmt = $conn->prepare($count_query);
$c_stmt->bind_param("i", $user_id);
$c_stmt->execute();
$total_feedbacks = $c_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_feedbacks / 6);

// Fetch initial feedback history (first page)
$history_sql = "SELECT category, rating, message, created_at FROM feedbacks WHERE user_id = ? ORDER BY created_at DESC LIMIT 6";
$h_stmt = $conn->prepare($history_sql);
$h_stmt->bind_param("i", $user_id);
$h_stmt->execute();
$feedback_history = $h_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | Grand Luxe Hotel</title>
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

        .star-btn {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }

        .star-btn:hover {
            transform: scale(1.3);
        }

        .star-btn.active {
            color: var(--gold);
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }

            50% {
                opacity: 1;
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .animate-bounce-in {
            animation: bounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
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

        .category-chip {
            background: white;
            border: 1px solid #eee;
            color: #666;
            padding: 12px 24px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .category-chip.active {
            background: var(--maroon);
            color: white;
            border-color: var(--maroon);
            box-shadow: 0 10px 25px rgba(106, 30, 45, 0.2);
        }

        .feedback-card {
            background: white;
            border-radius: 32px;
            padding: 24px;
            border: 1px solid #f8f8f8;
            transition: all 0.3s;
        }

        .feedback-card:hover {
            transform: translateY(-5px);
            border-color: rgba(212, 175, 55, 0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.03);
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .animate-shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }
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
                class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm"><i
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
                <div class="bg-maroon/5 p-3 rounded-2xl hidden sm:block"><i class="fas fa-comment-alt maroon-text"></i>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Feedback</p>
                    <p class="font-bold text-sm">Your Voice Matters</p>
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

        <div class="max-w-6xl mx-auto py-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Submission Side -->
                <div>
                    <div id="feedbackForm" class="bg-white rounded-[50px] p-12 premium-shadow border border-gray-50 h-full">
                        <div class="mb-10">
                            <h2 class="text-4xl font-bold maroon-text mb-4" style="font-family: 'Playfair Display', serif;">Your Voice</h2>
                            <p class="text-gray-400 text-sm">Help us refine our legacy of absolute luxury.</p>
                        </div>

                        <div class="space-y-10">

                            <!-- Star Rating -->
                        <div id="ratingBox" class="bg-gray-50 rounded-[40px] p-10 text-center transition-all duration-300">
                                <label class="text-[10px] uppercase tracking-[4px] font-extrabold text-gray-400 mb-6 block">Satisfaction Rating</label>
                                <div class="flex items-center justify-center space-x-4">
                                    <i class="fas fa-star text-4xl text-gray-200 star-btn" onclick="rate(1)"></i>
                                    <i class="fas fa-star text-4xl text-gray-200 star-btn" onclick="rate(2)"></i>
                                    <i class="fas fa-star text-4xl text-gray-200 star-btn" onclick="rate(3)"></i>
                                    <i class="fas fa-star text-4xl text-gray-200 star-btn" onclick="rate(4)"></i>
                                    <i class="fas fa-star text-4xl text-gray-200 star-btn" onclick="rate(5)"></i>
                                </div>
                                <p id="ratingLabel" class="text-xs font-bold gold-text uppercase mt-4 opacity-0 transition-opacity">Outstanding Experience</p>
                            </div>

                            <!-- Detailed Feedback -->
                            <div>
                                <label class="text-[10px] uppercase tracking-[4px] font-extrabold text-gray-400 pl-2 mb-4 block">Experience Details</label>
                                <textarea id="feedbackMessage" rows="5"
                                    class="w-full p-8 bg-gray-50 rounded-[32px] border-none focus:ring-2 focus:ring-gold outline-none text-gray-700 resize-none text-sm leading-relaxed"
                                    placeholder="Tell us what made your experience exceptional..."></textarea>
                            </div>

                            <button onclick="submitFeedback()"
                                class="w-full py-6 bg-maroon text-white rounded-[24px] font-bold text-sm tracking-widest uppercase hover:bg-gold transition-all duration-500 shadow-2xl shadow-maroon/20 flex items-center justify-center space-x-3 group">
                                <span>Send Feedback</span>
                                <i class="fas fa-paper-plane text-xs transform group-hover:translate-x-2 group-hover:-translate-y-2 transition-transform"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- History Side -->
                <div class="space-y-8">
                    <div class="flex items-center justify-between px-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-teal"></span>
                                </span>
                                <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400">Contribution Log</p>
                            </div>
                            <h3 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Recent Insights</h3>
                        </div>
                        <button onclick="refreshFeedbackHistory()" class="text-teal font-bold text-[10px] uppercase tracking-widest flex items-center gap-2 hover:underline">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh Log</span>
                        </button>
                    </div>

                    <div id="feedbackHistoryGrid" class="space-y-4">
                        <?php if (empty($feedback_history)): ?>
                            <div class="bg-white/50 border-2 border-dashed border-gray-200 rounded-[40px] p-20 text-center">
                                <i class="fas fa-comment-slash text-gray-200 text-5xl mb-6"></i>
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px]">No Feedback Submitted Yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($feedback_history as $fb): ?>
                                <div class="feedback-card animate-slide">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h6 class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                                <?php echo date('d M, Y • H:i', strtotime($fb['created_at'])); ?>
                                            </h6>
                                        </div>
                                        <div class="flex text-gold text-[10px]">
                                            <?php for($i=0; $i<5; $i++): ?>
                                                <i class="<?php echo $i < $fb['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-sm leading-relaxed italic">"<?php echo htmlspecialchars($fb['message']); ?>"</p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <div id="feedbackPagination" class="mt-10 flex items-center justify-between px-4">
                        <p class="text-[10px] uppercase tracking-widest font-bold text-gray-400">
                             Page <span id="currentPage">1</span> of <?php echo $total_pages; ?>
                        </p>
                        <div class="flex items-center space-x-2">
                            <button onclick="changeFeedbackPage(-1)" id="prevPageBtn" class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-maroon hover:border-maroon transition-all opacity-50 cursor-not-allowed" disabled>
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <div id="pageNumbers" class="flex space-x-2">
                                <?php for($i=1; $i<=min(3, $total_pages); $i++): ?>
                                    <button onclick="refreshFeedbackHistory(<?php echo $i; ?>)" class="page-num w-10 h-10 rounded-xl <?php echo $i===1 ? 'bg-maroon text-white font-bold' : 'bg-white text-gray-400 border border-gray-100'; ?> transition-all text-xs">
                                        <?php echo $i; ?>
                                    </button>
                                <?php endfor; ?>
                                <?php if($total_pages > 3): ?>
                                    <span class="text-gray-300">...</span>
                                <?php endif; ?>
                            </div>
                            <button onclick="changeFeedbackPage(1)" id="nextPageBtn" class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-maroon hover:border-maroon transition-all">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>


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
        <!-- Feedback Success Modal -->
        <div id="feedbackSuccessModal" class="fixed inset-0 z-[300] hidden">
            <div id="successBackdrop" class="absolute inset-0 bg-maroon/10 backdrop-blur-md transition-opacity duration-300 opacity-0" onclick="closeSuccessModal()"></div>
            <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
                <div id="successContent" class="bg-white w-full max-w-sm rounded-[40px] shadow-2xl p-12 text-center transform scale-90 opacity-0 transition-all duration-300 pointer-events-auto border border-white/20">
                    <div class="w-24 h-24 bg-maroon rounded-full flex items-center justify-center mx-auto mb-8 shadow-2xl shadow-maroon/30 relative">
                        <div class="absolute inset-0 rounded-full bg-maroon animate-ping opacity-20"></div>
                        <i class="fas fa-heart text-white text-3xl"></i>
                    </div>
                    <h3 class="text-3xl font-bold maroon-text mb-3" style="font-family: 'Playfair Display', serif;">Review Submitted</h3>
                    <p class="text-gray-400 text-sm leading-relaxed mb-10">Your elite insights have been logged successfully. We treasure your voice.</p>
                    <button onclick="closeSuccessModal()" class="w-full py-5 bg-maroon text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-maroon/20">
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
        let currentRating = 0;
        let selectedCategory = 'General';

        const ratingLabels = {
            1: "Room for Improvement",
            2: "Standard Experience",
            3: "Very Good Stay",
            4: "Exceptional Service",
            5: "Grand Luxury Defined"
        };

        function rate(stars) {
            currentRating = stars;
            const starBtns = document.querySelectorAll('.star-btn');
            const label = document.getElementById('ratingLabel');
            
            starBtns.forEach((btn, index) => {
                if (index < stars) {
                    btn.classList.add('active', 'text-gold');
                    btn.classList.remove('text-gray-200');
                } else {
                    btn.classList.remove('active', 'text-gold');
                    btn.classList.add('text-gray-200');
                }
            });

            if (stars > 0) {
                label.innerText = ratingLabels[stars];
                label.classList.remove('opacity-0');
            } else {
                label.classList.add('opacity-0');
            }
        }



        const canUseFeedback = <?php echo $canUseFeedback ? 'true' : 'false'; ?>;
        function submitFeedback() {
            if (!canUseFeedback) {
                showPremiumAlert("Access Restricted", "Feedback submission is reserved for guests currently staying with us. Please check in to share your experience.", "error");
                return;
            }
            if (currentRating === 0) {
                const ratingBox = document.getElementById('ratingBox');
                ratingBox.classList.add('animate-shake', 'ring-2', 'ring-maroon/20');
                showPremiumAlert("Rating Required", "Please select a star rating to share your experience with us.", "error");
                setTimeout(() => {
                    ratingBox.classList.remove('animate-shake', 'ring-2', 'ring-maroon/20');
                }, 1000);
                return;
            }

            const message = document.getElementById('feedbackMessage').value;
            const formData = new FormData();
            formData.append('category', selectedCategory);
            formData.append('rating', currentRating);
            formData.append('message', message);

            fetch('php/submit_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal();
                    document.getElementById('feedbackMessage').value = '';
                    rate(0);
                    refreshFeedbackHistory();
                } else {
                    showPremiumAlert("Submission Error", data.message, "error");
                }
            })
            .catch(err => {
                console.error(err);
                showPremiumAlert("System Error", "Unable to reach service.", "error");
            });
        }

        function showSuccessModal() {
            const modal = document.getElementById('feedbackSuccessModal');
            const backdrop = document.getElementById('successBackdrop');
            const content = document.getElementById('successContent');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.add('opacity-100');
                content.classList.remove('scale-95', 'opacity-0');
            }, 10);
        }

        function closeSuccessModal() {
            const modal = document.getElementById('feedbackSuccessModal');
            const backdrop = document.getElementById('successBackdrop');
            const content = document.getElementById('successContent');
            
            backdrop.classList.remove('opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }


        let currentPage = 1;
        let totalFeedbackPages = <?php echo $total_pages; ?>;

        function refreshFeedbackHistory(page = 1) {
            currentPage = page;
            const container = document.getElementById('feedbackHistoryGrid');
            const paginationContainer = document.getElementById('feedbackPagination');
            
            fetch(`php/get_feedback_history.php?page=${page}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    totalFeedbackPages = data.total_pages;
                    
                    if (data.history.length > 0) {
                        container.innerHTML = data.history.map(fb => {
                            let stars = '';
                            for(let i=0; i<5; i++) {
                                stars += `<i class="${i < fb.rating ? 'fas' : 'far'} fa-star"></i>`;
                            }

                            const date = new Date(fb.created_at);
                            const dateStr = `${date.getDate()} ${date.toLocaleString('default', { month: 'short' })}, ${date.getFullYear()} • ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

                            return `
                                <div class="feedback-card animate-slide">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h6 class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                                ${dateStr}
                                            </h6>
                                        </div>
                                        <div class="flex text-gold text-[10px]">
                                            ${stars}
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-sm leading-relaxed italic">"${fb.message}"</p>
                                </div>
                            `;
                        }).join('');
                    } else {
                        container.innerHTML = `
                            <div class="bg-white/50 border-2 border-dashed border-gray-200 rounded-[40px] p-20 text-center col-span-2">
                                <i class="fas fa-comment-slash text-gray-200 text-5xl mb-6"></i>
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px]">No Feedback Submitted Yet</p>
                            </div>
                        `;
                    }
                    
                    updatePaginationUI(data);
                }
            })
            .catch(err => console.error("History Refresh Error:", err));
        }

        function updatePaginationUI(data) {
            const pagination = document.getElementById('feedbackPagination');
            if (!pagination) return;

            if (data.total_pages <= 1) {
                pagination.classList.add('hidden');
                return;
            }
            pagination.classList.remove('hidden');

            document.getElementById('currentPage').innerText = data.current_page;
            
            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');
            
            if (data.current_page <= 1) {
                prevBtn.disabled = true;
                prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                prevBtn.disabled = false;
                prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            if (data.current_page >= data.total_pages) {
                nextBtn.disabled = true;
                nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                nextBtn.disabled = false;
                nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            // Update page numbers
            const pageNumbers = document.getElementById('pageNumbers');
            let html = '';
            
            const maxVisible = 3;
            let start = Math.max(1, data.current_page - 1);
            let end = Math.min(data.total_pages, start + maxVisible - 1);
            
            if (end - start < maxVisible - 1) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                html += `
                    <button onclick="refreshFeedbackHistory(${i})" class="page-num w-10 h-10 rounded-xl ${i === data.current_page ? 'bg-maroon text-white font-bold' : 'bg-white text-gray-400 border border-gray-100'} transition-all text-xs">
                        ${i}
                    </button>
                `;
            }
            
            if (end < data.total_pages) {
                html += '<span class="text-gray-300">...</span>';
            }
            
            pageNumbers.innerHTML = html;
        }

        function changeFeedbackPage(dir) {
            const newPage = currentPage + dir;
            if (newPage >= 1 && newPage <= totalFeedbackPages) {
                refreshFeedbackHistory(newPage);
            }
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