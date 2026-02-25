<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Key | Grand Luxe Hotel</title>
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
                        url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&q=80&w=1920');
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
            animation: fadeIn 1.2s ease;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .gold-text {
            color: var(--gold);
            font-family: 'Playfair Display', serif;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
            text-align: left;
        }

        .input-group label {
            display: block;
            color: #888;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .input-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px 45px 15px 50px;
            color: white;
            font-size: 16px;
            transition: 0.3s;
        }

        .input-group i {
            position: absolute;
            left: 18px;
            bottom: 18px;
            color: var(--gold);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--gold);
        }

        .toggle-password {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 14px;
            transition: 0.3s;
            z-index: 10;
        }

        .toggle-password:hover {
            color: var(--gold);
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
            margin-top: 20px;
        }

        .action-btn:hover {
            background: #F1C40F;
            transform: translateY(-2px);
        }

        #messageBox {
            background: rgba(255, 59, 48, 0.2);
            color: #ffbaba;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 13px;
            display: none;
            border: 1px solid rgba(255, 59, 48, 0.4);
        }
    </style>
</head>
<body>
    <div class="premium-card">
        <div class="mb-10">
            <h1 class="text-4xl gold-text mb-3">Reset Password</h1>
            <p class="text-gray-500 uppercase tracking-[4px] text-[10px] font-bold">Secure your account with a new password</p>
        </div>

        <div id="messageBox"></div>

        <form action="php/reset-password.php" method="POST" class="space-y-6">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            
            <div class="input-group">
                <label>New Password</label>
                <div class="relative">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <i class="fas fa-eye toggle-password" onclick="toggleVisibility('password', this)"></i>
                </div>
            </div>

            <div class="input-group">
                <label>Confirm Password</label>
                <div class="relative">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                    <i class="fas fa-eye toggle-password" onclick="toggleVisibility('confirm_password', this)"></i>
                </div>
            </div>

            <button type="submit" class="action-btn">Reset Password</button>
        </form>
    </div>
    <script>
        function toggleVisibility(inputId, icon) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html>
