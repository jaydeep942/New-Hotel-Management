<!-- Sidebar -->
<aside id="sidebar" class="glass w-72 h-screen sticky top-0 transition-all duration-500 overflow-hidden flex flex-col z-50">
    <div class="p-6 flex items-center space-x-4">
        <div class="w-10 h-10 bg-gradient-to-tr from-primary to-secondary rounded-xl flex items-center justify-center text-white shadow-lg">
            <i class="fas fa-crown text-xl"></i>
        </div>
        <div class="sidebar-text truncate">
            <h1 class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary" style="font-family: 'Playfair Display', serif;">
                GrandLuxe
            </h1>
            <p class="text-[10px] uppercase tracking-widest font-black text-gray-400">Admin Panel</p>
        </div>
    </div>

    <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
        <a href="dashboard.php" class="sidebar-link flex items-center space-x-4 px-5 py-4 rounded-2xl text-gray-500 hover:text-primary transition-all group">
            <i class="fas fa-chart-line text-lg group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text font-bold text-sm uppercase tracking-wider">Dashboard</span>
        </a>
        
        <div class="pt-4 pb-2 px-5 text-[10px] uppercase tracking-[3px] font-black text-gray-400 sidebar-text">Management</div>
        
        <a href="rooms.php" class="sidebar-link flex items-center space-x-4 px-5 py-4 rounded-2xl text-gray-500 hover:text-primary transition-all group">
            <i class="fas fa-bed text-lg group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text font-bold text-sm uppercase tracking-wider">Rooms</span>
        </a>
        
        <a href="bookings.php" class="sidebar-link flex items-center space-x-4 px-5 py-4 rounded-2xl text-gray-500 hover:text-primary transition-all group">
            <i class="fas fa-calendar-check text-lg group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text font-bold text-sm uppercase tracking-wider">Bookings</span>
        </a>
        
        <a href="customers.php" class="sidebar-link flex items-center space-x-4 px-5 py-4 rounded-2xl text-gray-500 hover:text-primary transition-all group">
            <i class="fas fa-users text-lg group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text font-bold text-sm uppercase tracking-wider">Customers</span>
        </a>
        
        <div class="pt-4 pb-2 px-5 text-[10px] uppercase tracking-[3px] font-black text-gray-400 sidebar-text">Operations</div>
        
        <a href="services.php" class="sidebar-link flex items-center space-x-4 px-5 py-4 rounded-2xl text-gray-500 hover:text-primary transition-all group">
            <i class="fas fa-utensils text-lg group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text font-bold text-sm uppercase tracking-wider">Services</span>
        </a>
        
        <a href="housekeeping.php" class="sidebar-link flex items-center space-x-4 px-5 py-4 rounded-2xl text-gray-500 hover:text-primary transition-all group">
            <i class="fas fa-broom text-lg group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text font-bold text-sm uppercase tracking-wider">Cleaning</span>
        </a>

        <div class="pt-4 pb-2 px-5 text-[10px] uppercase tracking-[3px] font-black text-gray-400 sidebar-text">System</div>
        
        <a href="settings.php" class="sidebar-link flex items-center space-x-4 px-5 py-4 rounded-2xl text-gray-500 hover:text-primary transition-all group">
            <i class="fas fa-cog text-lg group-hover:scale-110 transition-transform"></i>
            <span class="sidebar-text font-bold text-sm uppercase tracking-wider">Settings</span>
        </a>
    </nav>

    <div class="p-4 border-t border-gray-100">
        <button onclick="toggleSidebar()" class="w-full flex items-center justify-center p-3 text-gray-400 hover:text-primary transition-all rounded-xl hover:bg-white">
            <i id="sidebar-icon" class="fas fa-chevron-left"></i>
        </button>
    </div>
</aside>

<!-- Main Wrapper -->
<div class="flex-1 flex flex-col min-w-0">
    <!-- Navbar -->
    <header class="h-20 bg-white/50 backdrop-blur-md border-b border-gray-100 px-8 flex items-center justify-between sticky top-0 z-40">
        <div>
            <h2 class="text-xl font-bold text-gray-800"><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
            <p class="text-xs text-gray-400 font-medium"><?php echo date('l, d F Y'); ?></p>
        </div>

        <div class="flex items-center space-x-6">
            <div class="flex items-center space-x-3 bg-white px-4 py-2 rounded-2xl shadow-sm border border-gray-50">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-primary to-secondary flex items-center justify-center text-white text-xs font-bold shadow-md">
                    <?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="hidden sm:block">
                    <p class="text-xs font-bold text-gray-800 leading-none"><?php echo $_SESSION['name'] ?? 'Admin'; ?></p>
                    <p class="text-[9px] uppercase tracking-wider text-gray-400 mt-1">Super Admin</p>
                </div>
                <div class="ml-2">
                    <a href="logout.php" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 transition-all">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="p-8 animate-fade-in overflow-x-hidden">
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const texts = document.querySelectorAll('.sidebar-text');
        const icon = document.getElementById('sidebar-icon');
        
        if (sidebar.classList.contains('w-72')) {
            sidebar.classList.remove('w-72');
            sidebar.classList.add('w-20');
            texts.forEach(t => t.classList.add('hidden'));
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-72');
            texts.forEach(t => t.classList.remove('hidden'));
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-left');
        }
    }
</script>
