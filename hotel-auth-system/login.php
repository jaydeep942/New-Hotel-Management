<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: customer-dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Login | Grand Luxe Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        :root {
            --gold: #D4AF37;
            --gold-light: #F1C40F;
            --glass: rgba(0, 0, 0, 0.6);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=80&w=1920');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .premium-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(212, 175, 55, 0.3);
            border-radius: 40px;
            box-shadow: 0 50px 100px rgba(0, 0, 0, 0.8);
            width: 100%;
            max-width: 450px;
            padding: 50px;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .gold-text {
            color: var(--gold);
            font-family: 'Playfair Display', serif;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group label {
            display: block;
            color: #ccc;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .input-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px 20px 15px 55px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .input-group i {
            position: absolute;
            left: 20px;
            bottom: 18px;
            color: var(--gold);
            font-size: 18px;
        }

        .input-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--gold);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
        }

        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--gold) 0%, #B8860B 100%);
            color: #1a1a1a;
            font-weight: 800;
            padding: 18px;
            border-radius: 15px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 14px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.4);
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
        }

        .login-btn:active { transform: scale(0.98); }

        .spinner {
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-top: 3px solid #1a1a1a;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: none;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .footer-link {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #888;
        }

        .footer-link a {
            color: var(--gold);
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-link a:hover { color: var(--gold-light); text-decoration: underline; }

        #messageBox {
            background: rgba(255, 59, 48, 0.15);
            border: 1px solid rgba(255, 59, 48, 0.3);
            color: #ffbaba;
            padding: 15px;
            border-radius: 15px;
            text-align: center;
            font-size: 14px;
            margin-bottom: 30px;
            display: none;
        }

        #messageBox.success {
            background: rgba(52, 199, 89, 0.15);
            border: 1px solid rgba(52, 199, 89, 0.3);
            color: #baffc4;
        }
    </style>
</head>
<body>
    <div class="premium-card">
        <div class="text-center mb-10">
            <h1 class="text-5xl gold-text mb-4">Login</h1>
            <p class="text-gray-400 uppercase tracking-[5px] text-[10px] font-bold">Welcome to Grand Luxe Hotel</p>
        </div>

        <div id="messageBox"></div>

        <form id="loginForm" action="php/login.php" method="POST">
            <div class="input-group">
                <label>Email Address</label>
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="concierge@grandluxe.com" required>
            </div>

            <div class="input-group">
                <div class="flex justify-between items-center mb-2">
                    <label class="m-0">Password</label>
                    <a href="javascript:void(0)" onclick="goToForgot()" class="text-[10px] text-gray-500 hover:text-gold transition-colors font-bold uppercase tracking-widest">Forgot?</a>
                </div>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="flex items-center mb-8">
                <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-gray-100 accent-gold">
                <label for="remember" class="ml-2 text-xs text-gray-400 cursor-pointer">Remember my presence</label>
            </div>

            <button type="submit" id="submitBtn" class="login-btn">
                <span id="btnText">Login</span>
                <div id="loader" class="spinner"></div>
            </button>
        </form>

        <div class="footer-link">
            Don't have an account? <a href="register.html">Register Now</a>
        </div>
    </div>

    <script>
        // Redirect to main section if page is refreshed
        try {
            const navEntries = performance.getEntriesByType('navigation');
            if (navEntries.length > 0 && navEntries[0].type === 'reload') {
                window.location.href = 'index.php';
            }
        } catch (e) {
            console.log("Navigation timing not supported");
        }

        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const loader = document.getElementById('loader');
        const btnText = document.getElementById('btnText');
        const messageBox = document.getElementById('messageBox');

        // URL Params for status messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('registered') || urlParams.has('success')) {
            messageBox.textContent = decodeURIComponent(urlParams.get('success') || "Welcome! Your suite is ready for login.");
            messageBox.classList.add('success');
            messageBox.style.display = 'block';
        } else if (urlParams.has('error')) {
            messageBox.textContent = decodeURIComponent(urlParams.get('error'));
            messageBox.style.display = 'block';
        }

        loginForm.addEventListener('submit', (e) => {
            // Show loader and update text immediately
            btnText.textContent = "Logging in...";
            loader.style.display = 'block';
            
            // Avoid disabling the button immediately as it can cancel form submission in some browsers
            // instead, we use pointer-events to prevent double submission
            submitBtn.style.pointerEvents = 'none';
        });

        function goToForgot() {
            const email = document.getElementById('email').value;
            window.location.href = 'forgot-password.html?email=' + encodeURIComponent(email);
        }

        // Auto-fill email if passed from Registration
        if (urlParams.has('email')) {
            document.getElementById('email').value = decodeURIComponent(urlParams.get('email'));
        }
    </script>
</body>
</html>
