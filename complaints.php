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

// SECURE ACCESS CHECK: Only allow checked-in guests to SUBMIT
$booking_check_sql = "SELECT b.*, r.room_number 
                    FROM bookings b 
                    JOIN rooms r ON b.room_id = r.id 
                    WHERE b.user_id = ? AND b.status IN ('Confirmed', 'Checked-In') 
                    AND CURRENT_DATE BETWEEN b.check_in AND b.check_out LIMIT 1";
$check_stmt = $conn->prepare($booking_check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$booking_status = $check_stmt->get_result()->fetch_assoc();
$canUseComplaints = $booking_status ? true : false;
$prefilled_room = $booking_status ? $booking_status['room_number'] : '';

// Fetch existing complaint history
$history_sql = "SELECT id, type, room_number, description, status, created_at FROM complaints WHERE user_id = ? ORDER BY created_at DESC LIMIT 6";
$h_stmt = $conn->prepare($history_sql);
$h_stmt->bind_param("i", $user_id);
$h_stmt->execute();
$complaint_history = $h_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints | Grand Luxe Hotel</title>
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

        .input-group input, .input-group select, .input-group textarea {
            width: 100%;
            padding: 16px 24px;
            background: #F9FAFB;
            border: 2px solid transparent;
            border-radius: 20px;
            transition: all 0.3s;
            outline: none;
            font-size: 14px;
        }
        .input-group input:focus, .input-group select:focus, .input-group textarea:focus {
            background: white;
            border-color: var(--maroon);
            box-shadow: 0 8px 20px rgba(106, 30, 45, 0.05);
        }

        .complaint-card {
            background: white;
            border-radius: 32px;
            padding: 24px;
            border: 1px solid rgba(106, 30, 45, 0.05);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        .complaint-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px rgba(106, 30, 45, 0.08);
            border-color: rgba(106, 30, 45, 0.1);
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
        <div class="mb-12 px-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">GRAND<span class="gold-text">LUXE</span></h1>
                <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400 mt-1">Excellence Defined</p>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-maroon">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="space-y-2">
            <a href="customer-dashboard.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-th-large w-5"></i><span class="font-semibold">Dashboard</span></a>
            <a href="book-room.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-bed w-5"></i><span class="font-semibold">Book Room</span></a>
            <a href="services.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-concierge-bell w-5"></i><span class="font-semibold">Services</span></a>
            <a href="cleaning.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span></a>
            <a href="feedback.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span></a>
            <a href="complaints.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm"><i class="fas fa-exclamation-circle w-5"></i><span class="font-semibold">Complaints</span></a>
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
                <div class="bg-maroon/5 p-3 rounded-2xl hidden sm:block"><i class="fas fa-shield-alt maroon-text"></i></div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Assistance</p>
                    <p class="font-bold text-sm">Complaint Resolution</p>
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
                            <div class="w-full h-full bg-maroon rounded-xl flex items-center justify-center text-white font-bold text-lg"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-6xl mx-auto py-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Submission Side -->
                <div class="space-y-8">
                    <div id="complaintFormPanel" class="bg-white rounded-[50px] p-12 premium-shadow border border-gray-50 h-full relative overflow-hidden">
                        <!-- Abstract Design Elements -->
                        <div class="absolute top-0 right-0 w-64 h-64 bg-maroon/5 rounded-full -mr-32 -mt-32"></div>

                        <div class="relative z-10">
                            <div class="mb-10 text-center md:text-left">
                                <h2 class="text-4xl font-bold maroon-text mb-4" style="font-family: 'Playfair Display', serif;">Registry of Concerns</h2>
                                <p class="text-gray-400 text-sm">We strive for perfection. Our duty managers will resolve any issue within the hour.</p>
                            </div>

                            <form id="complaintForm" onsubmit="handleComplaintSubmit(event)" class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="input-group">
                                        <label class="text-[10px] uppercase tracking-[3px] font-black text-gray-400 mb-3 block pl-2">Nature of Concern</label>
                                        <select id="complaintType" name="type" required>
                                            <option value="" disabled selected>Select Category</option>
                                            <option value="Room Service">Room Service</option>
                                            <option value="Housekeeping">Housekeeping</option>
                                            <option value="Technical Issue">Technical Issue</option>
                                            <option value="Staff Behavior">Staff Behavior</option>
                                            <option value="Billing & Charges">Billing & Charges</option>
                                            <option value="Facility/Amenity">Facility/Amenity</option>
                                            <option value="Other">Other Resolution</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label class="text-[10px] uppercase tracking-[3px] font-black text-gray-400 mb-3 block pl-2">Suite Number</label>
                                        <input type="text" id="suiteNumberInput" name="room_number" value="<?php echo htmlspecialchars($prefilled_room); ?>" placeholder="e.g. 104" required>
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label class="text-[10px] uppercase tracking-[3px] font-black text-gray-400 mb-3 block pl-2">Detailed Narrative</label>
                                    <textarea name="description" rows="5" placeholder="Please describe the situation in detail..." required></textarea>
                                </div>

                                <div class="pt-6">
                                    <button type="submit" class="w-full py-6 bg-maroon text-white rounded-[24px] font-bold text-sm uppercase tracking-widest hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-maroon/20 flex items-center justify-center space-x-4 group">
                                        <span>File Complaint</span>
                                        <i class="fas fa-paper-plane transition-transform group-hover:translate-x-1 group-hover:-translate-y-1"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Success State (Clean Premium Version) -->
                    <div id="successPanel" class="hidden h-full bg-white rounded-[50px] p-16 text-center animate-fade-in premium-shadow border border-gray-50 overflow-hidden relative">
                        <!-- Subtle Background Accent -->
                        <div class="absolute top-0 right-0 w-40 h-40 bg-maroon/5 rounded-full -mr-20 -mt-20"></div>
                        
                        <div class="relative z-10">
                            <div class="w-24 h-24 bg-teal rounded-full flex items-center justify-center mx-auto mb-10 shadow-2xl shadow-teal/20 relative">
                                <div class="absolute inset-0 rounded-full bg-teal animate-ping opacity-20"></div>
                                <i class="fas fa-check text-white text-4xl"></i>
                            </div>

                            <h3 class="text-3xl font-bold maroon-text mb-4" style="font-family: 'Playfair Display', serif;">Concern Logged</h3>
                            <p class="text-gray-400 text-sm mb-12 max-w-xs mx-auto leading-relaxed">
                                Your narrative has been safely received. A dedicated manager has been assigned to your case.
                            </p>

                            <!-- Status Grid (Light Mode) -->
                            <div class="grid grid-cols-2 gap-6 mb-12">
                                <div class="bg-gray-50/80 p-5 rounded-[28px] border border-gray-100">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-2">Tracking ID</p>
                                    <p class="text-sm font-black maroon-text">#COMP-REALTIME</p>
                                </div>
                                <div class="bg-gray-50/80 p-5 rounded-[28px] border border-gray-100">
                                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-2">Expected Call</p>
                                    <p class="text-sm font-black text-teal">< 30 Mins</p>
                                </div>
                            </div>

                            <button onclick="resetComplaintPanel()" class="px-12 py-5 bg-maroon text-white rounded-[24px] font-bold text-xs uppercase tracking-widest hover:bg-gold transition-all duration-300 shadow-xl shadow-maroon/20 hover:shadow-gold/20">
                                File New Report
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
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-maroon opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-maroon"></span>
                                </span>
                                <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400">Resolution Log</p>
                            </div>
                            <h3 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">Registry Monitor</h3>
                        </div>
                        <button onclick="refreshComplaintHistory()" class="text-maroon font-bold text-[10px] uppercase tracking-widest flex items-center gap-2 hover:underline">
                            <i class="fas fa-sync-alt"></i>
                            <span>Real-Time Sync</span>
                        </button>
                    </div>

                    <div id="historyGrid" class="space-y-4">
                        <?php if (empty($complaint_history)): ?>
                            <div class="bg-white/50 border-2 border-dashed border-gray-200 rounded-[40px] p-20 text-center">
                                <i class="fas fa-shield-slash text-gray-200 text-5xl mb-6"></i>
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-[10px]">No Active Filings found</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($complaint_history as $c): ?>
                                <div class="complaint-card animate-slide">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <span class="text-[9px] font-black uppercase tracking-widest text-maroon bg-maroon/5 px-3 py-1 rounded-full mb-2 inline-block">
                                                #COMP-<?php echo str_pad($c['id'], 4, '0', STR_PAD_LEFT); ?>
                                            </span>
                                            <h6 class="text-[11px] font-bold maroon-text uppercase mb-1"><?php echo $c['type']; ?></h6>
                                            <p class="text-[10px] text-gray-400 font-medium tracking-widest uppercase">
                                                <?php echo date('d M, Y • H:i', strtotime($c['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-tighter
                                            <?php 
                                                if($c['status'] == 'Pending') echo 'bg-amber-100 text-amber-600';
                                                elseif($c['status'] == 'Under Investigation') echo 'bg-blue-100 text-blue-600';
                                                else echo 'bg-teal-100 text-teal-600';
                                            ?>">
                                            <?php echo $c['status']; ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-sm leading-relaxed line-clamp-2">"<?php echo htmlspecialchars($c['description']); ?>"</p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Manage Profile Modal (Copied from other pages) -->
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
                                    <div class="w-full h-full bg-maroon flex items-center justify-center text-white text-2xl font-bold"><?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?></div>
                                <?php endif; ?>
                            </div>
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
                        <button onclick="closeProfileModal()" class="w-full py-4 text-xs font-bold uppercase tracking-widest text-gray-400 hover:text-maroon transition-colors">Close Settings</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-8 md:p-12">
                    <div id="tab-profile-info" class="tab-content hidden animate-fade-in">
                        <div class="mb-10">
                            <h4 class="text-2xl font-bold maroon-text">Personal Details</h4>
                            <p class="text-gray-400 text-sm mt-1">Update your identity and contact information.</p>
                        </div>
                        <form id="updateProfileForm" onsubmit="handleUpdateProfile(event)" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="input-group"><label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Full Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required></div>
                                <div class="input-group"><label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Email Address</label><input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required></div>
                            </div>
                            <button type="submit" class="px-10 py-5 bg-maroon text-white rounded-2xl font-bold text-sm hover:scale-105 transition-all shadow-xl shadow-maroon/20 flex items-center space-x-3"><i class="fas fa-save"></i><span>Save Changes</span></button>
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
        <div id="toastInner" class="p-6 rounded-[28px] text-white flex items-center space-x-4 shadow-2xl transition-all duration-300 transform translate-y-10 opacity-0 text-white">
            <div id="toastIconBlockProfile" class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm"><i id="toastIconProfile" class="fas fa-check"></i></div>
            <div><p id="toastTitleTextProfile" class="font-bold text-sm"></p><p id="toastMessageTextProfile" class="text-[10px] uppercase tracking-widest opacity-80 mt-0.5"></p></div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
            setTimeout(() => overlay.classList.toggle('opacity-100'), 10);
        }

        function toggleProfileMenu() {
            const submenu = document.getElementById('profileSubmenu');
            const chevron = document.getElementById('profileChevron');
            submenu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        const canUseComplaints = <?php echo $canUseComplaints ? 'true' : 'false'; ?>;
        const verifiedSuite = "<?php echo $prefilled_room; ?>";

        function handleComplaintSubmit(e) {
            e.preventDefault();
            if (!canUseComplaints) {
                showPremiumAlert("Access Restricted", "Complaint filing is reserved for checked-in guests only.", "error");
                return;
            }

            const formData = new FormData(e.target);
            const enteredRoom = formData.get('room_number');

            if (enteredRoom !== verifiedSuite) {
                showPremiumAlert("Suite Mismatch", "The entered suite number does not match your active residency record (#" + verifiedSuite + "). Please verify your suite number.", "error");
                return;
            }
            fetch('php/submit_complaint.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('complaintFormPanel').classList.add('hidden');
                    document.getElementById('successPanel').classList.remove('hidden');
                    refreshComplaintHistory();
                } else {
                    showPremiumAlert("Submission Error", data.message, "error");
                }
            })
            .catch(() => showPremiumAlert("System Error", "Unable to reach resolution server.", "error"));
        }

        function resetComplaintPanel() {
            document.getElementById('complaintFormPanel').classList.remove('hidden');
            document.getElementById('successPanel').classList.add('hidden');
            document.getElementById('complaintForm').reset();
        }

        async function refreshComplaintHistory() {
            const container = document.getElementById('historyGrid');
            try {
                const res = await fetch('php/get_complaint_history.php');
                const data = await res.json();
                
                if (data.success && data.history.length > 0) {
                    container.innerHTML = data.history.map(c => {
                        let statusColor = 'bg-amber-100 text-amber-600';
                        if(c.status === 'Under Investigation') statusColor = 'bg-blue-100 text-blue-600';
                        else if(c.status === 'Resolved') statusColor = 'bg-teal-100 text-teal-600';

                        const date = new Date(c.created_at);
                        const dateStr = `${date.getDate()} ${date.toLocaleString('default', { month: 'short' })}, ${date.getFullYear()} • ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
                        const compId = String(c.id).padStart(4, '0');

                        return `
                            <div class="complaint-card animate-slide">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <span class="text-[9px] font-black uppercase tracking-widest text-maroon bg-maroon/5 px-3 py-1 rounded-full mb-2 inline-block">
                                            #COMP-${compId}
                                        </span>
                                        <h6 class="text-[11px] font-bold maroon-text uppercase mb-1">${c.type}</h6>
                                        <p class="text-[10px] text-gray-400 font-medium tracking-widest uppercase">
                                            ${dateStr}
                                        </p>
                                    </div>
                                    <div class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-tighter ${statusColor}">
                                        ${c.status}
                                    </div>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed line-clamp-2">"${c.description}"</p>
                            </div>
                        `;
                    }).join('');
                }
            } catch (err) {
                console.error("History Sync Error:", err);
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

        // Modal functions (Placeholder for minimal functionality)
        function openProfileModal(tab) { document.getElementById('profileModal').classList.remove('hidden'); switchTab(tab); }
        function closeProfileModal() { document.getElementById('profileModal').classList.add('hidden'); }
        function switchTab(tab) { document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden')); document.getElementById('tab-'+tab)?.classList.remove('hidden'); }
    </script>
</body>
</html>
