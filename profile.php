<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$conn = require_once __DIR__ . '/config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch user details
$user_sql = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$_SESSION['name'] = $user_data['name'];
$profile_photo = $user_data['profile_photo'];
$email = $user_data['email'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile | Grand Luxe Hotel</title>
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

        .maroon-text { color: var(--maroon); }
        .gold-text { color: var(--gold); }

        .sidebar-link { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
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
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .profile-card {
            background: white;
            border-radius: 40px;
            padding: 40px;
            border: 1px solid rgba(0,0,0,0.02);
        }

        .input-group input {
            width: 100%;
            padding: 16px 24px;
            background: #F9FAFB;
            border: 2px solid transparent;
            border-radius: 20px;
            transition: all 0.3s;
            outline: none;
        }

        .input-group input:focus {
            background: white;
            border-color: var(--gold);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade { animation: fadeIn 0.6s ease-out forwards; }
    </style>
</head>
<body class="bg-[#F8F5F0] min-h-screen">

    <!-- Sidebar Trigger (Mobile) -->
    <div class="lg:hidden fixed top-6 left-6 z-[60]">
        <button onclick="toggleSidebar()" class="w-12 h-12 bg-white rounded-2xl premium-shadow flex items-center justify-center maroon-text">
            <i class="fas fa-bars-staggered text-xl"></i>
        </button>
    </div>

    <aside id="sidebar" class="w-72 bg-white fixed h-full border-r border-gray-100 px-6 py-8 z-[55] overflow-y-auto transition-transform duration-300 -translate-x-full lg:translate-x-0">
        <div class="mb-12 px-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-tighter maroon-text" style="font-family: 'Playfair Display', serif;">
                GRAND<span class="gold-text">LUXE</span>
            </h1>
            <button onclick="toggleSidebar()" class="lg:hidden text-gray-400 hover:text-maroon"><i class="fas fa-times"></i></button>
        </div>

        <nav class="space-y-2">
            <a href="customer-dashboard.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-th-large w-5"></i><span class="font-semibold">Dashboard</span>
            </a>
            <a href="book-room.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-bed w-5"></i><span class="font-semibold">Book Room</span>
            </a>
            <a href="services.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-concierge-bell w-5"></i><span class="font-semibold">Services</span>
            </a>
            <a href="cleaning.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span>
            </a>
            <a href="feedback.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span>
            </a>
            <a href="complaints.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-exclamation-circle w-5"></i><span class="font-semibold">Complaints</span>
            </a>
            <a href="history.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm">
                <i class="fas fa-history w-5"></i><span class="font-semibold">Booking History</span>
            </a>
            <a href="profile.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm">
                <i class="fas fa-user-circle w-5"></i><span class="font-semibold">Manage Profile</span>
            </a>
            <div class="pt-10">
                <a href="php/logout.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-red-500 hover:bg-red-50 text-sm">
                    <i class="fas fa-sign-out-alt w-5"></i><span class="font-bold uppercase tracking-wider text-xs">Sign Out</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-72 p-4 md:p-8">
        <nav class="glass-nav sticky top-0 flex justify-between items-center p-6 rounded-3xl mb-12 z-40 premium-shadow border border-white/20">
            <div class="flex items-center space-x-4 pl-14 lg:pl-0">
                <!-- Branding or subtle indicator -->
                <div class="bg-gold/5 p-3 rounded-2xl hidden md:block animate-pulse"><i class="fas fa-crown gold-text"></i></div>
                <div>
                    <h2 class="text-xl font-bold maroon-text tracking-tight uppercase" style="font-family: 'Playfair Display', serif;">Elite <span class="gold-text">Circle</span></h2>
                    <p class="text-[9px] uppercase tracking-[3px] text-gray-400 font-extrabold -mt-1 opacity-60">Verified Member Lounge</p>
                </div>
            </div>
             <div class="flex items-center space-x-4">
                <div class="text-right hidden sm:block">
                    <p class="font-bold text-sm"><?php echo htmlspecialchars($user_data['name']); ?></p>
                </div>
                <div class="w-12 h-12 rounded-2xl border-2 border-gold/20 p-1">
                    <?php if ($profile_photo): ?>
                        <img src="<?php echo $profile_photo; ?>" class="w-full h-full object-cover rounded-xl" alt="Profile">
                    <?php else: ?>
                        <div class="w-full h-full bg-maroon rounded-xl flex items-center justify-center text-white font-bold text-lg">
                            <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto animate-fade">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Profile Overview -->
                <div class="lg:col-span-1 space-y-8 lg:sticky lg:top-32 h-fit">
                    <div class="profile-card text-center relative overflow-hidden group">
                        <!-- Decorative background element -->
                        <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-maroon to-darkMaroon opacity-5"></div>
                        
                        <div class="relative w-40 h-40 mx-auto mb-8 mt-4 group">
                            <div class="w-full h-full rounded-[45px] overflow-hidden border-4 border-gold/20 p-1.5 shadow-2xl shadow-gold/10 transition-transform duration-500 group-hover:scale-105">
                                <?php if ($profile_photo): ?>
                                    <img src="<?php echo $profile_photo; ?>" id="profilePreview" class="w-full h-full object-cover rounded-[38px]" alt="Profile">
                                <?php else: ?>
                                    <div id="profilePlaceholder" class="w-full h-full bg-gradient-to-br from-maroon to-darkMaroon rounded-[38px] flex items-center justify-center text-white font-bold text-5xl">
                                        <?php echo strtoupper(substr($user_data['name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="absolute -bottom-2 right-2 flex space-x-2">
                                <button onclick="document.getElementById('profileUpload').click()" class="w-10 h-10 bg-white text-gold rounded-xl flex items-center justify-center shadow-xl border border-gray-50 hover:bg-gold hover:text-white transition-all transform hover:scale-110 active:scale-90 z-10">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <?php if ($profile_photo): ?>
                                    <button onclick="removeProfilePhoto()" class="w-10 h-10 bg-white text-red-500 rounded-xl flex items-center justify-center shadow-xl border border-gray-50 hover:bg-red-500 hover:text-white transition-all transform hover:scale-110 active:scale-90 z-10">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <input type="file" id="profileUpload" class="hidden" accept="image/*" onchange="uploadProfile(this)">
                        </div>
                        
                        <div class="relative z-10">
                            <h3 class="text-2xl font-bold maroon-text tracking-tight"><?php echo htmlspecialchars($user_data['name']); ?></h3>
                            
                            <div class="grid grid-cols-1 gap-4 mt-6 pt-6 border-t border-gray-50">
                                <div class="flex items-center justify-between group/item">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 group-hover/item:bg-maroon/5 group-hover/item:text-maroon transition-colors">
                                            <i class="far fa-envelope text-xs"></i>
                                        </div>
                                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest text-[9px]">Email</span>
                                    </div>
                                    <span class="text-xs font-semibold maroon-text truncate max-w-[150px]"><?php echo htmlspecialchars($user_data['email']); ?></span>
                                </div>
                                <div class="flex items-center justify-between group/item">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 group-hover/item:bg-gold/5 group-hover/item:text-gold transition-colors">
                                            <i class="far fa-calendar-alt text-xs"></i>
                                        </div>
                                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest text-[9px]">Since</span>
                                    </div>
                                    <span class="text-xs font-semibold maroon-text"><?php echo date('M Y', strtotime($user_data['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card bg-gradient-to-br from-maroon to-darkMaroon text-white p-8 relative overflow-hidden group">
                        <i class="fas fa-shield-halved absolute -right-6 -bottom-6 text-8xl text-white/5 transform -rotate-12 group-hover:scale-110 transition-transform"></i>
                        <h4 class="font-bold mb-3 flex items-center gap-2">
                            <i class="fas fa-lock text-gold"></i>
                            Security Status
                        </h4>
                        <p class="text-white/60 text-xs leading-relaxed">Your account is protected with enterprise-grade encryption. Change your password regularly to maintain high security standards.</p>
                    </div>
                </div>

                <!-- Right: Settings Forms -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Personal Info -->
                    <div class="profile-card">
                        <div class="flex items-center space-x-4 mb-10">
                            <div class="w-12 h-12 bg-gold/10 rounded-2xl flex items-center justify-center text-gold"><i class="fas fa-user-edit"></i></div>
                            <h3 class="text-xl font-bold maroon-text">Personal Details</h3>
                        </div>
                        <form id="updateInfoForm" onsubmit="updateInfo(event)" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6">
                                <div class="input-group">
                                    <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Full Name</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" required>
                                </div>
                                <div class="input-group">
                                    <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Email Address</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-t border-gray-50 pt-8">
                                <div class="input-group">
                                    <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" placeholder="+1 (555) 000-0000">
                                </div>
                                <div class="input-group">
                                    <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Nationality</label>
                                    <input type="text" name="nationality" value="<?php echo htmlspecialchars($user_data['nationality'] ?? ''); ?>" placeholder="e.g. American">
                                </div>
                                <div class="input-group md:col-span-2">
                                    <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Date of Birth</label>
                                    <input type="date" name="dob" value="<?php echo htmlspecialchars($user_data['dob'] ?? ''); ?>" style="color-scheme: light;">
                                </div>
                            </div>
                            <button type="submit" class="px-8 py-4 bg-maroon text-white rounded-2xl font-bold text-sm hover:scale-105 transition-all shadow-lg shadow-maroon/20">Save Changes</button>
                        </form>
                    </div>


                    <!-- Change Password -->
                    <div class="profile-card">
                        <div class="flex items-center space-x-4 mb-10">
                            <div class="w-12 h-12 bg-teal/10 rounded-2xl flex items-center justify-center text-teal"><i class="fas fa-lock"></i></div>
                            <h3 class="text-xl font-bold maroon-text">Security</h3>
                        </div>
                        <form id="changePasswordForm" onsubmit="changePassword(event)" class="space-y-6">
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
                                    <label class="text-[10px] uppercase tracking-widest font-extrabold text-gray-400 pl-4 mb-2 block">Confirm New Password</label>
                                    <input type="password" name="confirm_password" required placeholder="••••••••">
                                </div>
                            </div>
                            <button type="submit" class="px-8 py-4 bg-teal text-white rounded-2xl font-bold text-sm hover:scale-105 transition-all shadow-lg shadow-teal/20">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Success/Error Toast -->
    <div id="toast" class="fixed bottom-10 right-10 z-[100] hidden">
        <div id="toastContent" class="p-6 rounded-[24px] text-white flex items-center space-x-4 premium-shadow animate-fade">
            <div id="toastIcon" class="bg-white/20 p-2 rounded-full"></div>
            <div>
                <p class="font-bold" id="toastTitle"></p>
                <p class="text-[10px] uppercase tracking-widest opacity-80" id="toastMsg"></p>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay?.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay?.classList.add('hidden');
            }
        }

        function showToast(title, msg, type = 'success') {
            const toast = document.getElementById('toast');
            const content = document.getElementById('toastContent');
            const icon = document.getElementById('toastIcon');
            
            content.className = `p-6 rounded-[24px] text-white flex items-center space-x-4 premium-shadow animate-fade ${type === 'success' ? 'bg-teal' : 'bg-maroon'}`;
            icon.innerHTML = type === 'success' ? '<i class="fas fa-check"></i>' : '<i class="fas fa-exclamation-triangle"></i>';
            document.getElementById('toastTitle').innerText = title;
            document.getElementById('toastMsg').innerText = msg;
            
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 4000);
        }

        function uploadProfile(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('profile_photo', input.files[0]);

                const preview = document.getElementById('profilePreview');
                const placeholder = document.getElementById('profilePlaceholder');
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
                        showToast('Error', data.message, 'error');
                    }
                });
            }
        }

        function updateInfo(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            fetch('php/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Updated', 'Personal details saved successfully.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast('Error', data.message, 'error');
                }
            });
        }

        function changePassword(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            if (formData.get('new_password') !== formData.get('confirm_password')) {
                showToast('Error', 'Passwords do not match.', 'error');
                return;
            }

            fetch('php/change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Success', 'Password updated successfully.');
                    e.target.reset();
                } else {
                    showToast('Error', data.message, 'error');
                }
            });
        }

        function removeProfilePhoto() {
            if (confirm('Are you sure you want to remove your profile picture?')) {
                fetch('php/remove_profile_photo.php', {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                });
            }
        }
    </script>
</body>
</html>
