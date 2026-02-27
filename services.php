<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$conn = require_once __DIR__ . '/config/db.php';
$user_id = $_SESSION['user_id'];

// Fetch user details
$user_sql = "SELECT name, profile_photo FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$_SESSION['name'] = $user_data['name'];
$profile_photo = $user_data['profile_photo'];

// Fetch menu items
$menu_sql = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category";
$menu_res = $conn->query($menu_sql);
$menu_items = [];
while($row = $menu_res->fetch_assoc()){
    $menu_items[] = $row;
}
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

        .hidden-service { display: none !important; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 10px; }
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
            <a href="history.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-history w-5"></i><span class="font-semibold">Booking History</span></a>
            <a href="profile.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-user-circle w-5"></i><span class="font-semibold">Manage Profile</span></a>
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
                <div onclick="openService('dining')" class="service-card h-[500px] rounded-[40px] bg-[url('https://images.unsplash.com/photo-1544148103-0773bf10d330?auto=format&fit=crop&q=82&w=800')] bg-cover bg-center flex flex-col justify-end p-10 group" onerror="this.style.backgroundImage='url(https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=82&w=800)'">
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
                <div onclick="openService('water')" class="service-card h-[500px] rounded-[40px] bg-[url('https://images.unsplash.com/photo-1559839914-17aae19cea9e?auto=format&fit=crop&q=82&w=800')] bg-cover bg-center flex flex-col justify-end p-10 group">
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-xl rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas fa-wine-bottle text-white text-2xl"></i>
                        </div>
                        <h3 class="text-3xl font-bold text-white mb-2" style="font-family: 'Playfair Display', serif;">Pure Refreshments</h3>
                        <p class="text-white/80 text-sm mb-6">Premium bottled water and chilled beverages at your door.</p>
                        <span class="px-6 py-3 bg-white text-maroon font-bold rounded-xl text-xs uppercase tracking-widest group-hover:bg-teal group-hover:text-white transition-colors">Request Service</span>
                    </div>
                </div>

                <!-- Type 3: Guest Amenities -->
                <div onclick="openService('amenities')" class="service-card h-[500px] rounded-[40px] bg-[url('https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&q=82&w=800')] bg-cover bg-center flex flex-col justify-end p-10 group">
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
        <div id="diningService" class="hidden-service flex flex-col xl:flex-row gap-8 animate-up">
            <div class="flex-1">
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
                    <?php foreach($menu_items as $item): ?>
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
                                <span class="font-black text-maroon text-lg">$<?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            <p class="text-gray-400 text-xs leading-relaxed mb-6"><?php echo htmlspecialchars($item['description']); ?></p>
                            
                            <div class="flex items-center justify-between border-t border-gray-50 pt-4">
                                <div class="flex items-center space-x-4 bg-gray-50 px-3 py-1.5 rounded-xl">
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, -1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)" class="text-maroon hover:scale-125 transition-transform"><i class="fas fa-minus text-[10px]"></i></button>
                                    <span id="item-qty-<?php echo $item['id']; ?>" class="font-bold text-sm w-4 text-center">0</span>
                                    <button onclick="updateCartItem(<?php echo $item['id']; ?>, 1, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>)" class="text-maroon hover:scale-125 transition-transform"><i class="fas fa-plus text-[10px]"></i></button>
                                </div>
                                <span class="text-[9px] font-black text-gold uppercase tracking-[2px]"><?php echo $item['category']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart Sidebar -->
            <div class="w-full xl:w-96">
                <div class="bg-white rounded-[40px] p-10 premium-shadow sticky top-32">
                    <h3 class="text-2xl font-bold maroon-text mb-8" style="font-family: 'Playfair Display', serif;">Your Gourmet Bag</h3>
                    <div id="cartItems" class="space-y-6 mb-12 min-h-[50px] max-h-[300px] overflow-y-auto">
                        <div class="text-center py-6 text-gray-300"><p class="text-[10px] uppercase tracking-[3px]">Cart is empty</p></div>
                    </div>
                    <div class="border-t-2 border-dashed border-gray-100 pt-8">
                        <div class="flex justify-between items-center mb-8">
                            <span class="text-gray-400 font-bold text-xs uppercase tracking-[3px]">Total</span>
                            <span class="maroon-text font-black text-2xl">$<span id="cartTotal">0.00</span></span>
                        </div>
                        <button id="orderBtn" disabled onclick="placeDiningOrder()" class="w-full py-5 bg-maroon text-white rounded-2xl font-bold hover:bg-gold transition-all duration-300 shadow-xl shadow-maroon/10 disabled:opacity-20">Process Order</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Success Toast -->
    <div id="serviceToast" class="fixed bottom-10 right-10 z-[200] hidden">
        <div class="bg-maroon p-6 rounded-[24px] text-white flex items-center space-x-4 premium-shadow animate-up">
            <div class="bg-white/20 w-12 h-12 rounded-xl flex items-center justify-center"><i class="fas fa-check"></i></div>
            <div><p class="font-bold" id="toastMsg">Order Placed!</p><p class="text-[10px] uppercase opacity-70" id="toastSub">Our concierge is on the way</p></div>
        </div>
    </div>

    <script>
        let activeCuisine = 'all';
        let cart = {};

        function openService(type) {
            document.getElementById('serviceSelection').classList.add('hidden-service');
            if (type === 'dining') {
                document.getElementById('diningService').classList.remove('hidden-service');
            } else {
                showToast('Request Received!', 'Our staff will deliver to your room shortly.');
                document.getElementById('serviceSelection').classList.remove('hidden-service');
            }
        }

        function goBack() {
            document.getElementById('diningService').classList.add('hidden-service');
            document.getElementById('serviceSelection').classList.remove('hidden-service');
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

        function updateCartItem(id, delta, name, price) {
            if (!cart[id]) cart[id] = { name: name, price: price, qty: 0 };
            cart[id].qty += delta;
            if (cart[id].qty < 0) cart[id].qty = 0;
            document.getElementById('item-qty-' + id).innerText = cart[id].qty;
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItems');
            let html = '', total = 0, count = 0;
            for (const id in cart) {
                if (cart[id].qty > 0) {
                    const sub = cart[id].qty * cart[id].price;
                    total += sub; count++;
                    html += `<div class="flex justify-between items-center"><div class="text-xs font-bold maroon-text">${cart[id].name}<br><span class="text-[9px] text-gray-400">${cart[id].qty} x $${cart[id].price}</span></div><span class="text-xs font-black maroon-text">$${sub.toFixed(2)}</span></div>`;
                }
            }
            container.innerHTML = html || '<div class="text-center py-6 text-gray-300"><p class="text-[10px] uppercase tracking-[3px]">Empty Bag</p></div>';
            document.getElementById('cartTotal').innerText = total.toFixed(2);
            document.getElementById('orderBtn').disabled = total === 0;
        }

        function placeDiningOrder() {
            const btn = document.getElementById('orderBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            setTimeout(() => {
                showToast('Order Received!', 'Chef is preparing your meal.');
                cart = {}; renderCart();
                document.querySelectorAll('[id^="item-qty-"]').forEach(el => el.innerText = '0');
                goBack();
                btn.innerText = 'Process Order';
            }, 1500);
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
            document.getElementById('sidebarOverlay').classList.toggle('hidden');
        }

        function showToast(title, sub) {
            const toast = document.getElementById('serviceToast');
            document.getElementById('toastMsg').innerText = title;
            document.getElementById('toastSub').innerText = sub;
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 5000);
        }
    </script>
</body>
</html>