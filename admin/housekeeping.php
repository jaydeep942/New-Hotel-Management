<?php
$pageTitle = "Cleaning Protocols";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Protocol Update
if (isset($_GET['room_id']) && isset($_GET['status'])) {
    $adminCtrl->updateCleaningProtocol($_GET['room_id'], $_GET['status']);
    header("Location: housekeeping.php?msg=Protocol+Updated");
    exit();
}

$tasks = $adminCtrl->getHousekeepingGrid();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="mb-10 text-center lg:text-left">
    <h3 class="text-2xl font-bold text-gray-800">Room Clearance System</h3>
    <p class="text-sm text-gray-400">Manage suite sanitization and availability protocols.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if($tasks): ?>
        <?php foreach($tasks as $t): ?>
        <div class="card-soft p-8 relative overflow-hidden group">
            <!-- Background Indicator -->
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full group-hover:scale-125 transition-transform duration-700"></div>
            
            <div class="flex justify-between items-start mb-6">
                <div class="w-16 h-16 rounded-[24px] bg-white border border-gray-100 shadow-sm flex items-center justify-center text-primary font-black text-xl">
                    <?php echo $t['room_number']; ?>
                </div>
                <div class="text-right">
                    <span class="text-[9px] font-black uppercase tracking-widest text-rose-500 bg-rose-500/10 px-3 py-1.5 rounded-full">
                        <?php echo $t['room_status']; ?>
                    </span>
                    <p class="text-[8px] text-gray-400 mt-2 font-black uppercase tracking-[2px]">Last Seen: <?php echo date('H:i', strtotime($t['last_updated'])); ?></p>
                </div>
            </div>

            <h4 class="text-sm font-bold text-gray-800 mb-6 uppercase tracking-wider">Sanitization Protocol</h4>
            
            <div class="grid grid-cols-3 gap-3">
                <a href="?room_id=<?php echo $t['room_id']; ?>&status=Dirty" 
                   class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 <?php echo ($t['clean_status'] ?? 'Dirty') === 'Dirty' ? 'border-rose-500 bg-rose-500/5 text-rose-500' : 'border-gray-50 bg-gray-50 text-gray-300 hover:border-rose-200 transition-all'; ?>">
                    <i class="fas fa-biohazard text-sm mb-2"></i>
                    <span class="text-[8px] font-black uppercase tracking-widest">Dirty</span>
                </a>
                <a href="?room_id=<?php echo $t['room_id']; ?>&status=Cleaning" 
                   class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 <?php echo ($t['clean_status'] ?? '') === 'Cleaning' ? 'border-amber-500 bg-amber-500/5 text-amber-500' : 'border-gray-50 bg-gray-50 text-gray-300 hover:border-amber-200 transition-all'; ?>">
                    <i class="fas fa-broom text-sm mb-2"></i>
                    <span class="text-[8px] font-black uppercase tracking-widest">Active</span>
                </a>
                <a href="?room_id=<?php echo $t['room_id']; ?>&status=Cleaned" 
                   class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 <?php echo ($t['clean_status'] ?? '') === 'Cleaned' ? 'border-emerald-500 bg-emerald-500/5 text-emerald-500' : 'border-gray-50 bg-gray-50 text-gray-300 hover:border-emerald-200 transition-all'; ?>">
                    <i class="fas fa-check-double text-sm mb-2"></i>
                    <span class="text-[8px] font-black uppercase tracking-widest">Clear</span>
                </a>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-50 flex items-center justify-between">
                <div class="flex -space-x-2">
                    <div class="w-6 h-6 rounded-full bg-indigo-500 border-2 border-white flex items-center justify-center text-white text-[8px] font-bold">H</div>
                </div>
                <p class="text-[9px] font-bold text-gray-400">Housekeeping Queue</p>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full py-20 bg-white rounded-[40px] border border-gray-100 flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-full flex items-center justify-center text-emerald-500 mb-6">
                <i class="fas fa-shield-virus text-3xl"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-800">All Grids Sanitized</h4>
            <p class="text-sm text-gray-400 mt-2">No pending cleaning protocols detected at this timestamp.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", 'success');
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
