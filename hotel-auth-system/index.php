<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Luxe Hotel | Experience Elegance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        :root {
            --gold: #D4AF37;
            --gold-light: #F1C40F;
            --dark: #0a0a0a;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--dark);
            color: white;
            overflow-x: hidden;
        }

        .hero {
            height: 100vh;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), 
                        url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&q=80&w=1920');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .gold-text {
            color: var(--gold);
            font-family: 'Playfair Display', serif;
        }

        .btn-premium {
            background: linear-gradient(135deg, var(--gold) 0%, #B8860B 100%);
            color: #1a1a1a;
            padding: 15px 40px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 800;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-premium:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.4);
        }

        nav {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <nav class="fixed w-full z-50 px-10 py-5 flex justify-between items-center">
        <div class="text-2xl gold-text font-bold uppercase tracking-widest">Grand Luxe</div>
        <div class="space-x-8">
            <a href="#" class="hover:text-gold transition">Living</a>
            <a href="#" class="hover:text-gold transition">Dining</a>
            <a href="#" class="hover:text-gold transition">Spa</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="customer-dashboard.php" class="btn-premium py-2 px-6">Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn-premium py-2 px-6">Sign In</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hero">
        <div class="max-w-4xl px-4">
            <h1 class="text-7xl md:text-9xl gold-text mb-6">Exquisite Living</h1>
            <p class="text-xl md:text-2xl text-gray-300 mb-10 tracking-[10px] uppercase font-light">The Masterpiece of Hospitality</p>
            <div class="flex flex-col md:flex-row gap-6 justify-center">
                <a href="register.html" class="btn-premium text-lg">Reserve Your Suite</a>
                <a href="login.php" class="border border-gold text-gold hover:bg-gold hover:text-dark transition-all px-10 py-4 rounded-full text-lg font-bold uppercase tracking-widest">Member Login</a>
            </div>
        </div>
    </div>
</body>
</html>
