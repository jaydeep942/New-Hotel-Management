<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification | Grand Luxe Hotel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');

        :root {
            --gold: #D4AF37;
            --glass: rgba(0, 0, 0, 0.75);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&q=80&w=1920');
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
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 40px;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.9);
            width: 100%;
            max-width: 450px;
            padding: 50px;
            text-align: center;
            animation: fadeIn 1s ease;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .gold-text {
            color: var(--gold);
            font-family: 'Playfair Display', serif;
        }

        .otp-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(212, 175, 55, 0.2);
            border-radius: 20px;
            padding: 20px;
            color: white;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.5em;
            text-align: center;
            transition: 0.3s;
        }

        .otp-input:focus {
            outline: none;
            border-color: var(--gold);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.2);
        }

        .action-btn {
            width: 100%;
            background: var(--gold);
            color: #1a1a1a;
            font-weight: 800;
            padding: 18px;
            border-radius: 15px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-size: 14px;
            transition: 0.4s;
            margin-top: 30px;
        }

        .action-btn:hover {
            background: #F1C40F;
            transform: translateY(-2px);
        }

        #messageBox {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 13px;
            display: none;
        }

        .error { background: rgba(255, 59, 48, 0.15); border: 1px solid rgba(255, 59, 48, 0.3); color: #ffbaba; }
        .success { background: rgba(52, 199, 89, 0.15); border: 1px solid rgba(52, 199, 89, 0.3); color: #baffc4; }
    </style>
</head>
<body>
    <div class="premium-card">
        <div class="mb-10">
            <h1 class="text-4xl gold-text mb-3">Verify OTP</h1>
            <p class="text-gray-500 uppercase tracking-[4px] text-[10px] font-bold">Please enter your verification code</p>
        </div>

        <div id="messageBox"></div>

        <form id="verifyForm" action="php/verify-otp.php" method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            
            <div class="space-y-4">
                <label class="text-xs font-bold text-gray-500 uppercase tracking-widest block">Enter 6-Digit Key</label>
                <input type="text" name="otp" maxlength="6" placeholder="000000" required class="otp-input">
            </div>

            <button type="submit" class="action-btn">Verify OTP</button>
        </form>

        <div class="mt-8">
            <p class="text-gray-500 text-xs uppercase tracking-widest">
                Still waiting? 
                <form id="resendForm" action="php/forgot-password.php" method="POST" class="inline">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
                    <button type="submit" class="text-sky-400 font-bold hover:underline bg-transparent border-none cursor-pointer">Request New Key</button>
                </form>
            </p>
        </div>
    </div>

    <script>
        // Redirect to main section if page is refreshed
        if (performance.getEntriesByType('navigation')[0].type === 'reload') {
            window.location.href = 'index.php';
        }

        const urlParams = new URLSearchParams(window.location.search);
        const mBox = document.getElementById('messageBox');
        if (urlParams.has('error')) {
            mBox.textContent = decodeURIComponent(urlParams.get('error'));
            mBox.className = "error block mb-6 p-4 rounded-xl text-center text-sm";
            mBox.style.display = 'block';
        }
        if (urlParams.has('success')) {
            mBox.textContent = decodeURIComponent(urlParams.get('success'));
            mBox.className = "success block mb-6 p-4 rounded-xl text-center text-sm";
            mBox.style.display = 'block';
        }
    </script>
</body>
</html>
