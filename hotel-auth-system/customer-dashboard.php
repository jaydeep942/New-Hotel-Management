<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Sanctuary | Grand Luxe Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        :root {
            --gold: #D4AF37;
            --glass: rgba(255, 255, 255, 0.05);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: #0a0a0a;
            color: white;
            min-height: 100vh;
        }

        .gold-text {
            color: var(--gold);
            font-family: 'Playfair Display', serif;
        }

        .dashboard-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 30px;
            padding: 40px;
            height: 100%;
            transition: 0.3s;
        }

        .dashboard-card:hover {
            border-color: var(--gold);
            transform: translateY(-5px);
        }

        .sidebar {
            background: rgba(0, 0, 0, 0.5);
            border-right: 1px solid rgba(212, 175, 55, 0.1);
        }
    </style>
</head>
<body class="flex">
    <!-- Sidebar -->
    <div class="sidebar w-80 min-h-screen p-8 hidden lg:block">
        <div class="text-3xl gold-text font-bold mb-16 px-4 uppercase tracking-tighter">Grand Luxe</div>
        
        <nav class="space-y-4">
            <a href="#" class="flex items-center space-x-4 p-4 rounded-2xl bg-gold text-dark font-bold">
                <i class="fas fa-home"></i>
                <span>Residency</span>
            </a>
            <a href="#" class="flex items-center space-x-4 p-4 rounded-2xl hover:bg-white/5 transition">
                <i class="fas fa-concierge-bell"></i>
                <span>Services</span>
            </a>
            <a href="#" class="flex items-center space-x-4 p-4 rounded-2xl hover:bg-white/5 transition">
                <i class="fas fa-utensils"></i>
                <span>Dining</span>
            </a>
            <a href="#" class="flex items-center space-x-4 p-4 rounded-2xl hover:bg-white/5 transition text-red-400">
                <i class="fas fa-sign-out-alt"></i>
                <a href="php/logout.php" class="text-red-400">Sign Out</a>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8 lg:p-12 overflow-y-auto">
        <header class="flex justify-between items-center mb-16">
            <div>
                <h1 class="text-4xl gold-text mb-2">Welcome Back, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
                <p class="text-gray-500 uppercase tracking-widest text-xs font-bold">Your private suite awaits your presence</p>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <p class="text-sm font-bold">Gold Member</p>
                    <p class="text-xs text-gold">Elite Status</p>
                </div>
                <div class="w-12 h-12 bg-gold rounded-full flex items-center justify-center text-dark font-bold text-xl">
                    <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            <div class="dashboard-card col-span-1 md:col-span-2">
                <h2 class="text-2xl gold-text mb-6">Current Reservation</h2>
                <div class="bg-white/5 rounded-2xl p-6 border border-white/10 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Room Type</p>
                        <p class="text-xl font-bold">Presidential Suite - 401</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400 uppercase tracking-widest mb-1">Status</p>
                        <p class="text-gold font-bold">Confirmed</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <h2 class="text-2xl gold-text mb-6">Concierge</h2>
                <p class="text-gray-400 text-sm mb-6 leading-relaxed">Request private dining, spa treatments, or chauffeur services directly from your suite.</p>
                <button class="w-full py-4 rounded-xl border border-gold/30 text-gold font-bold hover:bg-gold hover:text-dark transition-all">Request Service</button>
            </div>
        </div>
    </div>
</body>
</html>
