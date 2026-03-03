<?php
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if ($adminCtrl->login($email, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid clearance credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gateway | Grand Luxe Intelligence</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .bg-mesh {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, rgba(139, 92, 246, 0.2) 0, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(244, 63, 94, 0.2) 0, transparent 50%);
        }
        .login-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-mesh min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full animate-fade-in">
        <div class="text-center mb-10">
            <div class="w-16 h-16 bg-gradient-to-tr from-violet-500 to-rose-500 rounded-2xl flex items-center justify-center text-white text-3xl shadow-2xl mx-auto mb-6">
                <i class="fas fa-fingerprint"></i>
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-white mb-2" style="font-family: 'Playfair Display', serif;">
                GrandLuxe <span class="text-rose-500">Core</span>
            </h1>
            <p class="text-xs uppercase tracking-[5px] font-black text-violet-400">Security Gateway</p>
        </div>

        <div class="login-card p-10 rounded-[40px] shadow-2xl">
            <?php if($error): ?>
                <div class="mb-8 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-500 text-xs font-bold uppercase tracking-widest text-center rounded-2xl">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Clearance Email</label>
                    <div class="relative group">
                        <i class="fas fa-envelope absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-violet-500 transition-colors"></i>
                        <input type="email" name="email" required placeholder="admin@grandluxe.com" 
                               class="w-full bg-white/5 border border-white/10 p-5 pl-14 rounded-2xl text-white outline-none focus:border-violet-500 transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Access Pattern</label>
                    <div class="relative group">
                        <i class="fas fa-lock absolute left-5 top-1/2 -translate-y-1/2 text-gray-600 group-focus-within:text-violet-500 transition-colors"></i>
                        <input type="password" name="password" required placeholder="••••••••" 
                               class="w-full bg-white/5 border border-white/10 p-5 pl-14 rounded-2xl text-white outline-none focus:border-violet-500 transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-violet-600 to-rose-600 hover:scale-[1.02] active:scale-[0.98] transition-all p-5 rounded-2xl text-white font-bold uppercase tracking-[4px] text-xs shadow-xl shadow-violet-900/20 mt-4">
                    Authenticate
                </button>
            </form>
        </div>

        <div class="mt-12 text-center text-gray-500 text-[10px] uppercase tracking-widest font-black">
            &copy; 2026 Grand Luxe Intelligence Systems
        </div>
    </div>

</body>
</html>
