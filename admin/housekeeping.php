<?php
$pageTitle = "Cleaning Protocols";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Guest Request Status Update (from guests)
if (isset($_GET['request_id']) && isset($_GET['new_status'])) {
    $adminCtrl->updateHousekeepingStatus($_GET['request_id'], $_GET['new_status']);
    header("Location: housekeeping.php?msg=Request+Status+Updated");
    exit();
}

// Handle Room Sanitization Status Update (internal management)
if (isset($_GET['room_id']) && isset($_GET['status'])) {
    $adminCtrl->updateCleaningProtocol($_GET['room_id'], $_GET['status']);
    header("Location: housekeeping.php?msg=Protocol+Updated");
    exit();
}

$tasks = $adminCtrl->getHousekeepingGrid();
$guestRequests = $adminCtrl->getPendingHousekeepingRequests();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-6">
    <div class="text-center lg:text-left">
        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Housekeeping Command Center</h3>
        <p class="text-sm text-gray-400 mt-1">Manage suite sanitization and real-time guest service requests.</p>
    </div>
    <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-gray-100 shadow-sm">
        <div class="px-4 py-2 text-center">
            <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Active Requests</p>
            <p class="text-xl font-black text-primary"><?php echo count($guestRequests); ?></p>
        </div>
        <div class="w-px h-8 bg-gray-100"></div>
        <div class="px-4 py-2 text-center">
            <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Rooms to Clean</p>
            <p class="text-xl font-black text-rose-500"><?php echo count($tasks); ?></p>
        </div>
    </div>
</div>

<!-- SECTION 1: ACTIVE GUEST REQUESTS -->
<?php if(!empty($guestRequests)): ?>
<div class="mb-12">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></div>
        <h4 class="text-xs font-black uppercase tracking-[4px] text-amber-600">Active Guest Calls</h4>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($guestRequests as $req): ?>
        <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-primary/5 border border-primary/5 relative overflow-hidden group hover:-translate-y-1 transition-all duration-300">
            <div class="absolute top-0 right-0 p-6">
                <?php 
                    $badgeClass = 'bg-amber-50 text-amber-600 border border-amber-100';
                    $badgeText = $req['status'];
                    if($req['status'] === 'Completed') {
                        if($req['is_received'] == 1) {
                            $badgeClass = 'bg-green-50 text-green-600 border border-green-100';
                            $badgeText = 'Satisfied';
                        } elseif($req['is_received'] == 2) {
                            $badgeClass = 'bg-rose-50 text-rose-600 border border-rose-100 animate-pulse';
                            $badgeText = 'Not Satisfied';
                        } else {
                            $badgeClass = 'bg-teal-50 text-teal-600 border border-teal-100';
                            $badgeText = 'Refreshed';
                        }
                    }
                ?>
                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $badgeClass; ?>">
                    <?php echo $badgeText; ?>
                </span>
            </div>
            
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-primary/5 flex items-center justify-center text-primary font-black text-lg">
                    <?php echo $req['room_number']; ?>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($req['guest_name']); ?></h5>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">Checked-In Resident</p>
                </div>
            </div>
            
            <div class="bg-gray-50/50 rounded-2xl p-6 mb-8 border border-gray-100">
                <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest mb-2">Requested Service</p>
                <h6 class="text-sm font-extrabold text-primary flex items-center gap-2">
                    <i class="fas fa-broom"></i>
                    <?php echo htmlspecialchars($req['service_type']); ?>
                </h6>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if($req['status'] === 'Pending'): ?>
                    <a href="?request_id=<?php echo $req['id']; ?>&new_status=In+Progress" 
                       class="flex-1 py-3 bg-primary text-white rounded-xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-primary-dark transition-all shadow-lg shadow-primary/20">
                        Accept Task
                    </a>
                <?php elseif($req['status'] === 'In Progress'): ?>
                    <a href="?request_id=<?php echo $req['id']; ?>&new_status=Completed" 
                       class="flex-1 py-3 bg-emerald-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest text-center hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-500/20">
                        Mark Done
                    </a>
                <?php endif; ?>
                
                <?php if($req['status'] !== 'Completed' && $req['status'] !== 'Cancelled'): ?>
                    <a href="?request_id=<?php echo $req['id']; ?>&new_status=Cancelled" 
                       class="w-12 h-12 flex items-center justify-center rounded-xl bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-all">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- SECTION 2: ROOM SANITIZATION GRID -->
<div class="flex items-center gap-3 mb-6">
    <div class="w-2 h-2 rounded-full bg-primary/40"></div>
    <h4 class="text-xs font-black uppercase tracking-[4px] text-gray-400">Post-Departure & Maintenance Clearance</h4>
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
                    <span class="text-[9px] font-black uppercase tracking-widest <?php echo $t['room_status'] === 'Needs Cleaning' ? 'text-rose-500 bg-rose-500/10' : 'text-primary bg-primary/10'; ?> px-3 py-1.5 rounded-full">
                        <?php echo $t['room_status']; ?>
                    </span>
                    <p class="text-[8px] text-gray-400 mt-2 font-black uppercase tracking-[2px]">Timestamp: <?php echo $t['last_updated'] ? date('H:i', strtotime($t['last_updated'])) : 'Never'; ?></p>
                </div>
            </div>

            <div class="mb-6">
                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest opacity-50 mb-2">Protocol Engine</h4>
                <p class="text-sm font-bold text-gray-800">
                    Standard Post-Stay Sanitization
                </p>
            </div>
            
            <div class="grid grid-cols-3 gap-3">
                <a href="?room_id=<?php echo $t['room_id']; ?>&status=Dirty" 
                   class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 <?php echo ($t['clean_status'] ?? 'Dirty') === 'Dirty' ? 'border-rose-500 bg-rose-500/5 text-rose-500' : 'border-gray-50 bg-gray-50 text-gray-300 hover:border-rose-200 transition-all'; ?>">
                    <i class="fas fa-biohazard text-sm mb-2"></i>
                    <span class="text-[8px] font-black uppercase tracking-widest text-center">Dirty</span>
                </a>
                <a href="?room_id=<?php echo $t['room_id']; ?>&status=Cleaning" 
                   class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 <?php echo ($t['clean_status'] ?? '') === 'Cleaning' ? 'border-amber-500 bg-amber-500/5 text-amber-500' : 'border-gray-50 bg-gray-50 text-gray-300 hover:border-amber-200 transition-all'; ?>">
                    <i class="fas fa-broom text-sm mb-2"></i>
                    <span class="text-[8px] font-black uppercase tracking-widest text-center">Active</span>
                </a>
                <a href="?room_id=<?php echo $t['room_id']; ?>&status=Cleaned" 
                   class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 <?php echo ($t['clean_status'] ?? '') === 'Cleaned' ? 'border-emerald-500 bg-emerald-500/5 text-emerald-500' : 'border-gray-50 bg-gray-50 text-gray-300 hover:border-emerald-200 transition-all'; ?>">
                    <i class="fas fa-check-double text-sm mb-2"></i>
                    <span class="text-[8px] font-black uppercase tracking-widest text-center">Clear</span>
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
