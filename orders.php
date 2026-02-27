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
        .glass-nav { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
        .order-card { transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
        .order-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(106, 30, 45, 0.05); }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(106, 30, 45, 0.1); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(106, 30, 45, 0.3); }
    </style>
</head>
<body class="bg-[#F8F5F0] min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 bg-white fixed h-full border-r border-gray-100 px-6 py-8 z-[55] overflow-y-auto transition-transform duration-300 -translate-x-full lg:translate-x-0">
        <div class="mb-12 px-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold maroon-text" style="font-family: 'Playfair Display', serif;">GRAND<span class="gold-text">LUXE</span></h1>
                <p class="text-[10px] uppercase tracking-[4px] font-bold text-gray-400 mt-1">Excellence Defined</p>
            </div>
        </div>
        <nav class="space-y-2">
            <a href="customer-dashboard.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-th-large w-5"></i><span class="font-semibold">Dashboard</span></a>
            <a href="book-room.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-bed w-5"></i><span class="font-semibold">Book Room</span></a>
            <a href="services.php" class="sidebar-link active flex items-center space-x-4 p-4 rounded-2xl group text-sm"><i class="fas fa-concierge-bell w-5"></i><span class="font-semibold">Services</span></a>
            <a href="cleaning.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-broom w-5"></i><span class="font-semibold">Cleaning Request</span></a>
            <a href="feedback.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-star w-5"></i><span class="font-semibold">Feedback</span></a>
            <a href="complaints.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-exclamation-circle w-5"></i><span class="font-semibold">Complaints</span></a>
            <a href="history.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-gray-500 hover:text-maroon group text-sm"><i class="fas fa-history w-5"></i><span class="font-semibold">Booking History</span></a>
            <div class="pt-10"><a href="php/logout.php" class="sidebar-link flex items-center space-x-4 p-4 rounded-2xl text-red-500 hover:bg-red-50 text-sm"><i class="fas fa-sign-out-alt w-5"></i><span class="font-bold uppercase tracking-wider text-xs">Sign Out</span></a></div>
        </nav>
    </aside>

    <main class="flex-1 lg:ml-72 p-4 md:p-8">
        <nav class="glass-nav sticky top-0 flex justify-between items-center p-6 rounded-3xl mb-12 z-40 premium-shadow border border-white/20">
            <div class="flex items-center space-x-4">
                <div class="bg-maroon/5 p-3 rounded-2xl"><i class="fas fa-utensils maroon-text"></i></div>
                <div>
                    <h2 class="text-xl font-bold maroon-text">Order Archive</h2>
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Your Dining Journey</p>
                </div>
            </div>
            <a href="services.php?service=dining" class="px-6 py-3 bg-maroon text-white rounded-2xl font-bold text-sm hover:scale-105 transition-all shadow-xl shadow-maroon/20">Place New Order</a>
        </nav>

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
