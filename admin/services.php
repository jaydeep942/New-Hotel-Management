<?php
$pageTitle = "Service Intel";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_service') {
        $adminCtrl->addService([
            'service_name' => $_POST['service_name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category' => $_POST['category']
        ]);
        header("Location: services.php?msg=Service+Deployed");
        exit();
    }
}

// Handle Order Updates
if (isset($_GET['order_id']) && isset($_GET['status'])) {
    $adminCtrl->updateServiceOrderStatus($_GET['order_id'], $_GET['status']);
    header("Location: services.php?msg=Order+Status+Updated");
    exit();
}

$services = $adminCtrl->getAllServices();
$orders = $adminCtrl->getAllServiceOrders();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
    <!-- Active Orders Matrix -->
    <div class="xl:col-span-2 space-y-10">
        <div>
            <h3 class="text-2xl font-bold text-gray-800">Operational Requests</h3>
            <p class="text-sm text-gray-400">Real-time tracking of guest orders and housekeeping requirements.</p>
        </div>

        <div class="card-soft overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                            <th class="px-8 py-5">Intel Source</th>
                            <th class="px-8 py-5">Request Content</th>
                            <th class="px-8 py-5">Protocol Status</th>
                            <th class="px-8 py-5 text-right">Ops</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($orders as $o): ?>
                        <tr class="hover:bg-gray-50/30 transition-all">
                            <td class="px-8 py-6">
                                <div class="text-sm font-bold text-gray-800">Suite <?php echo $o['room_number']; ?></div>
                                <div class="text-[10px] text-gray-400 font-medium uppercase tracking-widest"><?php echo $o['guest_name']; ?></div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="text-xs font-black text-gray-700 uppercase"><?php echo $o['service_name']; ?></div>
                                <div class="text-[9px] text-gray-400 font-bold"><?php echo $o['category']; ?> • x<?php echo $o['quantity']; ?></div>
                            </td>
                            <td class="px-8 py-6">
                                <?php 
                                    $statusClass = match($o['status']) {
                                        'Pending' => 'text-amber-500 bg-amber-500/10',
                                        'Preparing' => 'text-indigo-500 bg-indigo-500/10',
                                        'Delivered' => 'text-emerald-500 bg-emerald-500/10',
                                        'Cancelled' => 'text-rose-500 bg-rose-500/10',
                                        default => 'text-gray-400 bg-gray-400/10'
                                    };
                                ?>
                                <span class="px-4 py-1.5 rounded-2xl text-[9px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
                                    <?php echo $o['status']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <?php if($o['status'] == 'Pending'): ?>
                                    <a href="?order_id=<?php echo $o['id']; ?>&status=Preparing" class="p-2 bg-indigo-50 text-indigo-500 rounded-xl hover:bg-indigo-500 hover:text-white transition-all text-xs" title="Start Preparation"><i class="fas fa-fire"></i></a>
                                <?php elseif($o['status'] == 'Preparing'): ?>
                                    <a href="?order_id=<?php echo $o['id']; ?>&status=Delivered" class="p-2 bg-emerald-50 text-emerald-500 rounded-xl hover:bg-emerald-500 hover:text-white transition-all text-xs" title="Mark Delivered"><i class="fas fa-truck"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Catalog Management -->
    <div class="space-y-10">
        <div>
            <h3 class="text-xl font-bold text-gray-800">Master Catalog</h3>
            <p class="text-xs text-gray-400">Available services and pricing deployments.</p>
        </div>

        <button onclick="openModal('addServiceModal')" class="w-full bg-gradient-to-r from-primary to-secondary text-white p-5 rounded-3xl font-bold uppercase tracking-[3px] text-xs shadow-xl shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all">
            <i class="fas fa-plus mr-3"></i> Add Service
        </button>

        <div class="space-y-4">
            <?php foreach($services as $s): ?>
            <div class="card-soft p-5 flex items-center justify-between group">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-primary/10 group-hover:text-primary transition-all">
                        <i class="fas <?php echo $s['category'] == 'Food' ? 'fa-hamburger' : 'fa-bell'; ?>"></i>
                    </div>
                    <div>
                        <h5 class="text-sm font-bold text-gray-800"><?php echo $s['service_name']; ?></h5>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black"><?php echo $s['category']; ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-black text-primary">₹<?php echo $s['price']; ?></p>
                    <button class="text-[10px] text-gray-300 hover:text-rose-500 transition-colors"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div id="addServiceModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-6 bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-[40px] p-10 animate-slide-up relative">
        <button onclick="closeModal('addServiceModal')" class="absolute top-8 right-8 text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-times"></i>
        </button>
        
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Service Deployment</h3>
        <p class="text-sm text-gray-400 mb-8">Deploy a new operational service for guests.</p>
        
        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add_service">
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Service Designation</label>
                <input type="text" name="service_name" required placeholder="Luxury Spa Session" 
                       class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Classification</label>
                    <select name="category" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                        <option value="Food">Culinary</option>
                        <option value="Cleaning">Housekeeping</option>
                        <option value="Facility">Facilities</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Yield Price (₹)</label>
                    <input type="number" name="price" required placeholder="500" 
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Description Intel</label>
                <textarea name="description" rows="3" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all"></textarea>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white p-5 rounded-2xl font-bold uppercase tracking-[4px] text-xs shadow-xl shadow-primary/20 mt-4">
                Deploy Service
            </button>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('flex');
        document.getElementById(id).classList.add('hidden');
    }

    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", 'success');
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
