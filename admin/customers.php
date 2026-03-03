<?php
$pageTitle = "Resident Registry";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Status Toggle
if (isset($_GET['id']) && isset($_GET['status'])) {
    $adminCtrl->toggleUserStatus($_GET['id'], $_GET['status']);
    header("Location: customers.php?msg=Resident+Status+Updated");
    exit();
}

$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';
$users = $adminCtrl->getAllUsers($filter, $search);

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="flex justify-between items-center mb-10">
    <div>
        <h3 class="text-2xl font-bold text-gray-800">Resident Directory</h3>
        <p class="text-sm text-gray-400">Comprehensive database of all verified Grand Luxe members.</p>
    </div>
    
    <form action="" method="GET" class="relative">
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Identity / Email..." class="bg-white border border-gray-100 pl-10 pr-6 py-3 rounded-2xl text-xs outline-none focus:border-primary/30 transition-all font-medium">
    </form>
</div>

<div class="card-soft overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                    <th class="px-8 py-5">Identity Profile</th>
                    <th class="px-8 py-5">Contact Protocols</th>
                    <th class="px-8 py-5">Access Level</th>
                    <th class="px-8 py-5">Registration Intel</th>
                    <th class="px-8 py-5 text-right">Operations</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach($users as $u): ?>
                <tr class="hover:bg-gray-50/30 transition-all group">
                    <td class="px-8 py-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-primary to-secondary p-[2px] shadow-lg shadow-primary/10">
                                <div class="w-full h-full rounded-2xl bg-white flex items-center justify-center text-primary font-black text-sm uppercase">
                                    <?php echo substr($u['name'], 0, 1); ?>
                                </div>
                            </div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-800"><?php echo $u['name']; ?></h5>
                                <p class="text-[9px] uppercase tracking-wider font-black text-gray-400">LX-MEMBER-<?php echo str_pad($u['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="text-xs font-bold text-gray-700"><?php echo $u['email']; ?></div>
                        <div class="text-[10px] text-gray-400 font-medium"><?php echo $u['phone']; ?></div>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $u['status'] == 'Active' ? 'text-emerald-500 bg-emerald-500/10' : 'text-rose-500 bg-rose-500/10'; ?>">
                            <?php echo $u['status']; ?>
                        </span>
                    </td>
                    <td class="px-8 py-6">
                        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-tighter">
                            <?php echo date('d M Y', strtotime($u['created_at'])); ?>
                        </div>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <div class="flex justify-end space-x-2">
                            <?php if($u['status'] == 'Active'): ?>
                                <a href="?id=<?php echo $u['id']; ?>&status=Inactive" class="p-2 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-all text-xs" title="Deactivate"><i class="fas fa-user-slash"></i></a>
                            <?php else: ?>
                                <a href="?id=<?php echo $u['id']; ?>&status=Active" class="p-2 bg-emerald-50 text-emerald-500 rounded-xl hover:bg-emerald-500 hover:text-white transition-all text-xs" title="Activate"><i class="fas fa-user-check"></i></a>
                            <?php endif; ?>
                            <a href="bookings.php?user_id=<?php echo $u['id']; ?>" class="p-2 <?php echo $u['status'] == 'Active' ? 'bg-indigo-50 text-indigo-500 hover:bg-indigo-100' : 'bg-gray-50 text-gray-400'; ?> rounded-xl transition-all text-xs" title="History Protocols">
                                <i class="fas fa-history"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", 'success');
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
