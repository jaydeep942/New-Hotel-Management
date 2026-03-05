<?php
$pageTitle = "Intelligence Dashboard";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

$stats = $adminCtrl->getDashboardStats();
$recentBookings = $adminCtrl->getRecentBookings();
$recentOrders = $adminCtrl->getRecentOrders();
$analytics = $adminCtrl->getAnalyticsData();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<!-- Statistics Overview -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-10">
    <!-- Total Rooms -->
    <a href="rooms.php" class="card-soft p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-primary/5 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary shadow-inner">
                <i class="fas fa-bed text-xl"></i>
            </div>
            <div class="flex items-center space-x-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[8px] font-black text-emerald-500 uppercase tracking-widest">Live</span>
            </div>
        </div>
        <h4 class="text-3xl font-black text-gray-800 counter" data-target="<?php echo $stats['total_rooms']; ?>">0</h4>
        <p class="text-[9px] uppercase tracking-[2px] font-black text-gray-400 mt-2">Total Inventory</p>
    </a>

    <!-- Available -->
    <a href="rooms.php" class="card-soft p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-emerald-500/5 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 shadow-inner">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
        </div>
        <h4 class="text-3xl font-black text-gray-800 counter" data-target="<?php echo $stats['available_rooms']; ?>">0</h4>
        <p class="text-[9px] uppercase tracking-[2px] font-black text-gray-400 mt-2">Available Suites</p>
    </a>

    <!-- Total Customers -->
    <a href="customers.php" class="card-soft p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-violet-500/5 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-12 h-12 rounded-xl bg-violet-500/10 flex items-center justify-center text-violet-500 shadow-inner">
                <i class="fas fa-users text-xl"></i>
            </div>
        </div>
        <h4 class="text-3xl font-black text-gray-800 counter" data-target="<?php echo $stats['total_customers']; ?>">0</h4>
        <p class="text-[9px] uppercase tracking-[2px] font-black text-gray-400 mt-2">Total Residents</p>
    </a>

    <!-- New Residents Today -->
    <a href="customers.php?filter=today" class="card-soft p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-amber-500/5 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 shadow-inner">
                <i class="fas fa-user-plus text-xl"></i>
            </div>
            <span class="text-[8px] font-black text-amber-500 bg-amber-500/10 px-2 py-1 rounded-lg uppercase">Today</span>
        </div>
        <h4 class="text-3xl font-black text-gray-800 counter" data-target="<?php echo $stats['new_residents_today']; ?>">0</h4>
        <p class="text-[9px] uppercase tracking-[2px] font-black text-gray-400 mt-2">New Registrations</p>
    </a>

    <!-- Revenue -->
    <a href="bookings.php" class="card-soft p-6 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
        <div class="absolute -right-6 -top-6 w-32 h-32 bg-rose-500/5 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
        <div class="flex justify-between items-start mb-6">
            <div class="w-12 h-12 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-500 shadow-inner">
                <i class="fas fa-hand-holding-dollar text-xl"></i>
            </div>
        </div>
        <h4 class="text-3xl font-black text-gray-800">
            ₹<span class="counter" data-target="<?php echo $stats['total_revenue']; ?>">0</span>
        </h4>
        <p class="text-[9px] uppercase tracking-[2px] font-black text-gray-400 mt-2">Yield Balance</p>
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
    <!-- Chart Area -->
    <div class="xl:col-span-2 card-soft p-8">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Performance Analytics</h3>
                <p class="text-xs text-gray-400">Monthly revenue and occupancy trends.</p>
            </div>
            <div class="flex p-1 bg-gray-50 rounded-2xl" id="analyticsToggle">
                <button onclick="updateChart('daily')" data-period="daily" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 rounded-xl hover:text-primary transition-all">Day</button>
                <button onclick="updateChart('monthly')" data-period="monthly" class="px-4 py-2 bg-white shadow-sm text-[10px] font-black uppercase tracking-widest text-primary rounded-xl transition-all">Month</button>
                <button onclick="updateChart('yearly')" data-period="yearly" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 rounded-xl hover:text-primary transition-all">Year</button>
            </div>
        </div>
        <div class="h-80 w-full relative">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card-soft p-8">
        <h3 class="text-lg font-bold text-gray-800 mb-8">Rapid Protocols</h3>
        <div class="space-y-4">
            <a href="rooms.php" class="w-full flex items-center justify-between p-5 bg-gray-50 rounded-[28px] hover:bg-white hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1 transition-all group">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="text-left">
                        <h5 class="text-sm font-bold text-gray-800">Add Room</h5>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black">Expand Grid</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:text-primary transition-colors text-xs"></i>
            </a>
            <a href="add-booking.php" class="w-full flex items-center justify-between p-5 bg-gray-50 rounded-[28px] hover:bg-white hover:shadow-xl hover:shadow-secondary/5 hover:-translate-y-1 transition-all group">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-secondary/10 rounded-2xl flex items-center justify-center text-secondary">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="text-left">
                        <h5 class="text-sm font-bold text-gray-800">New Booking</h5>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black">Manual Entry</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:text-secondary transition-colors text-xs"></i>
            </a>
            <a href="bookings.php" class="w-full flex items-center justify-between p-5 bg-gray-50 rounded-[28px] hover:bg-white hover:shadow-xl hover:shadow-emerald-500/5 hover:-translate-y-1 transition-all group">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-500">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="text-left">
                        <h5 class="text-sm font-bold text-gray-800">Export Yield</h5>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black">CSV Statement</p>
                    </div>
                </div>
                <i class="fas fa-chevron-right text-gray-300 group-hover:text-emerald-500 transition-colors text-xs"></i>
            </a>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="xl:col-span-2 card-soft overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Residency Matrix</h3>
            <button class="text-[10px] font-black uppercase tracking-widest text-primary hover:underline">Full Feed</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                        <th class="px-8 py-4">Guest</th>
                        <th class="px-8 py-4">Suite</th>
                        <th class="px-8 py-4">Timeline</th>
                        <th class="px-8 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if($recentBookings): ?>
                        <?php foreach($recentBookings as $b): ?>
                        <tr class="hover:bg-gray-50/30 transition-all cursor-pointer">
                            <td class="px-8 py-6">
                                <div class="text-sm font-bold text-gray-800"><?php echo $b['guest_name']; ?></div>
                                <div class="text-[10px] text-gray-400 font-medium">#LX-<?php echo $b['id']; ?></div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="bg-primary/5 text-primary text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                                    <?php echo $b['room_number']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="text-xs font-bold text-gray-500"><?php echo date('d M', strtotime($b['check_in'])); ?> - <?php echo date('d M', strtotime($b['check_out'])); ?></div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-[9px] font-black uppercase tracking-widest text-emerald-500 bg-emerald-500/10 px-3 py-1.5 rounded-2xl">
                                    <?php echo $b['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-400 text-xs italic">No active residencies detected.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card-soft overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Operational Feed</h3>
        </div>
        <div class="p-6 space-y-4">
            <?php if($recentOrders): ?>
                <?php foreach($recentOrders as $o): ?>
                <div class="flex items-start space-x-4 p-4 hover:bg-gray-50 rounded-2xl transition-all">
                    <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center text-indigo-500">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h5 class="text-xs font-bold text-gray-800"><?php echo $o['service_name']; ?></h5>
                        <p class="text-[10px] text-gray-400 mt-1">Suite <?php echo $o['room_number']; ?> • <?php echo $o['guest_name']; ?></p>
                        <span class="inline-block mt-2 text-[8px] font-black uppercase tracking-widest text-indigo-500">Pending</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-10">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mx-auto mb-4">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <p class="text-xs text-gray-400 italic">No operational requests.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Animated Counters
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.counter');
        const speed = 200;

        counters.forEach(counter => {
            const updateCount = () => {
                const target = +counter.getAttribute('data-target');
                const count = +counter.innerText;
                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target;
                }
            };
            updateCount();
        });

        // Initialize Performance Chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const allAnalytics = <?php echo json_encode($analytics); ?>;
        let currentChart;

        window.updateChart = function(period) {
            const dataSet = allAnalytics[period];
            const labels = dataSet.map(item => item.label);
            const values = dataSet.map(item => item.revenue);

            // Update UI Buttons
            document.querySelectorAll('#analyticsToggle button').forEach(btn => {
                if (btn.getAttribute('data-period') === period) {
                    btn.classList.add('bg-white', 'shadow-sm', 'text-primary');
                    btn.classList.remove('text-gray-400');
                } else {
                    btn.classList.remove('bg-white', 'shadow-sm', 'text-primary');
                    btn.classList.add('text-gray-400');
                }
            });

            if (currentChart) {
                currentChart.data.labels = labels;
                currentChart.data.datasets[0].data = values;
                currentChart.update();
            } else {
                currentChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue (₹)',
                            data: values,
                            backgroundColor: function(context) {
                                const chart = context.chart;
                                const {ctx, chartArea} = chart;
                                if (!chartArea) return null;
                                const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                                gradient.addColorStop(0, '#059669');
                                gradient.addColorStop(1, '#10b981');
                                return gradient;
                            },
                            borderRadius: 15,
                            borderSkipped: false,
                            barThickness: period === 'daily' ? 30 : 45,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                                titleColor: '#1f2937',
                                bodyColor: '#1f2937',
                                cornerRadius: 15,
                                displayColors: false,
                                padding: 15,
                                borderColor: '#f3f4f6',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return '₹' + context.raw.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { display: true, drawBorder: false, color: '#f3f4f6' },
                                ticks: {
                                    color: '#9ca3af',
                                    font: { family: 'Outfit', size: 10, weight: 'bold' },
                                    callback: function(value) {
                                        if (value >= 1000) return '₹' + (value/1000) + 'k';
                                        return '₹' + value;
                                    }
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: {
                                    color: '#9ca3af',
                                    font: { family: 'Outfit', size: 10, weight: 'bold' }
                                }
                            }
                        },
                        animation: {
                            duration: 1500,
                            easing: 'easeOutQuart'
                        }
                    }
                });
            }
        };

        // Default Load: Monthly
        updateChart('monthly');
    });
</script>

<?php include '../includes/admin_footer.php'; ?>
