<?php
$pageTitle = "Residency Matrix";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Status Change
if (isset($_GET['id']) && isset($_GET['status'])) {
    $adminCtrl->updateBookingStatus($_GET['id'], $_GET['status']);
    header("Location: bookings.php?msg=Status+Updated");
    exit();
}

// Handle Deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $adminCtrl->deleteBooking($_GET['id']);
    header("Location: bookings.php?msg=Protocol+Archived");
    exit();
}

$user_id = $_GET['user_id'] ?? null;
$search = $_GET['search'] ?? '';
$bookings = $adminCtrl->getAllBookings($user_id, $search);

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="flex justify-between items-center mb-10">
    <div>
        <h3 class="text-2xl font-bold text-gray-800">Operational Log</h3>
        <p class="text-sm text-gray-400">Track and manage all stay protocols from entry to exit.</p>
    </div>
    
    <div class="flex items-center space-x-4">
        <form action="" method="GET" class="flex items-center space-x-4">
            <div class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Guest / ID..." class="bg-white border border-gray-100 pl-10 pr-6 py-3 rounded-2xl text-xs outline-none focus:border-primary/30 transition-all font-medium">
            </div>
        </form>
        <a href="add-booking.php" class="bg-gradient-to-r from-primary to-secondary text-white px-6 py-3 rounded-2xl font-bold uppercase tracking-widest text-[9px] shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
            <i class="fas fa-plus mr-2"></i> Manual Entry
        </a>
    </div>
</div>

<!-- Bookings Table -->
<div class="card-soft overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 text-[10px] font-black uppercase tracking-widest text-gray-400">
                    <th class="px-8 py-5">Identity Protocol</th>
                    <th class="px-8 py-5">Assigned Suite</th>
                    <th class="px-8 py-5">Timeline</th>
                    <th class="px-8 py-5">Financial Yield</th>
                    <th class="px-8 py-5">Current Status</th>
                    <th class="px-8 py-5 text-right">Operations</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php foreach($bookings as $b): ?>
                <tr class="hover:bg-gray-50/30 transition-all group">
                    <td class="px-8 py-6">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary/10 to-secondary/10 flex items-center justify-center text-primary font-bold text-xs uppercase">
                                <?php echo substr($b['guest_name'], 0, 1); ?>
                            </div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-800"><?php echo $b['guest_name']; ?></h5>
                                <p class="text-[10px] text-gray-400 font-medium">ID: #LX-<?php echo str_pad($b['id'], 5, '0', STR_PAD_LEFT); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="text-xs font-black text-gray-700">Suite <?php echo $b['room_number']; ?></div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-wider"><?php echo $b['room_type']; ?></div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center space-x-2 text-[10px] font-bold text-gray-500">
                            <span><?php echo date('d M', strtotime($b['check_in'])); ?></span>
                            <i class="fas fa-arrow-right text-[8px] text-gray-300"></i>
                            <span><?php echo date('d M', strtotime($b['check_out'])); ?></span>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <div class="text-sm font-black text-primary">₹<?php echo number_format($b['total_amount'], 0); ?></div>
                        <div class="text-[9px] uppercase font-black <?php echo $b['payment_status'] == 'Paid' ? 'text-emerald-500' : 'text-amber-500'; ?> tracking-tighter">
                            <?php echo $b['payment_status']; ?>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <?php 
                            $statusClass = match($b['status']) {
                                'Booked' => 'text-primary bg-primary/10',
                                'Confirmed' => 'text-primary bg-primary/10 border border-primary/20',
                                'Checked-In' => 'text-indigo-500 bg-indigo-500/10',
                                'Checked-Out' => 'text-emerald-500 bg-emerald-500/10',
                                'Cancelled' => 'text-rose-500 bg-rose-500/10',
                                default => 'text-gray-400 bg-gray-400/10'
                            };
                        ?>
                        <span class="px-4 py-2 rounded-2xl text-[9px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
                            <?php echo $b['status']; ?>
                        </span>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <div class="flex justify-end space-x-2">
                            <?php if($b['status'] == 'Booked' || $b['status'] == 'Confirmed'): ?>
                                <a href="?id=<?php echo $b['id']; ?>&status=Checked-In" class="px-4 py-2 bg-indigo-500 text-white text-[9px] font-black uppercase rounded-xl hover:shadow-lg shadow-indigo-500/20 transition-all">Check In</a>
                            <?php elseif($b['status'] == 'Checked-In'): ?>
                                <a href="?id=<?php echo $b['id']; ?>&status=Checked-Out" class="px-4 py-2 bg-emerald-500 text-white text-[9px] font-black uppercase rounded-xl hover:shadow-lg shadow-emerald-500/20 transition-all">Check Out</a>
                            <?php endif; ?>
                            
                            <div class="relative group/more">
                                <button class="w-8 h-8 flex items-center justify-center bg-gray-50 text-gray-400 hover:text-maroon rounded-lg transition-all">
                                    <i class="fas fa-ellipsis-h text-xs"></i>
                                </button>
                                <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 py-2 opacity-0 invisible group-hover/more:opacity-100 group-hover/more:visible translate-y-2 group-hover/more:translate-y-0 transition-all z-20">
                                    <h6 class="px-6 py-2 text-[8px] font-black uppercase tracking-widest text-gray-300">Residency Actions</h6>
                                    
                                    <?php if($b['status'] == 'Booked' || $b['status'] == 'Confirmed'): ?>
                                        <a href="?id=<?php echo $b['id']; ?>&status=Checked-In" class="flex items-center space-x-3 px-6 py-3 text-[10px] font-black uppercase tracking-widest text-indigo-500 hover:bg-indigo-50 transition-all">
                                            <i class="fas fa-sign-in-alt w-4"></i>
                                            <span>Manual Check-In</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if($b['status'] == 'Checked-In'): ?>
                                        <a href="?id=<?php echo $b['id']; ?>&status=Checked-Out" class="flex items-center space-x-3 px-6 py-3 text-[10px] font-black uppercase tracking-widest text-emerald-500 hover:bg-emerald-50 transition-all">
                                            <i class="fas fa-sign-out-alt w-4"></i>
                                            <span>Final Check-Out</span>
                                        </a>
                                        <a href="?id=<?php echo $b['id']; ?>&status=Confirmed" class="flex items-center space-x-3 px-6 py-3 text-[10px] font-black uppercase tracking-widest text-amber-500 hover:bg-amber-50 transition-all">
                                            <i class="fas fa-undo w-4"></i>
                                            <span>Revert to Confirmed</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if($b['status'] != 'Cancelled' && $b['status'] != 'Checked-Out'): ?>
                                        <a href="?id=<?php echo $b['id']; ?>&status=Cancelled" class="flex items-center space-x-3 px-6 py-3 text-[10px] font-black uppercase tracking-widest text-rose-500 hover:bg-rose-50 transition-all">
                                            <i class="fas fa-ban w-4"></i>
                                            <span>Cancel Stay</span>
                                        </a>
                                    <?php endif; ?>
                                    <a href="print-receipt.php?id=<?php echo $b['id']; ?>" target="_blank" class="flex items-center space-x-3 px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-50 transition-all">
                                        <i class="fas fa-file-invoice-dollar w-4"></i>
                                        <span>Print Receipt</span>
                                    </a>
                                    <div class="h-px bg-gray-50 my-1 mx-4"></div>
                                    <a href="?action=delete&id=<?php echo $b['id']; ?>" onclick="return confirm('Archive this protocol permanently?')" class="flex items-center space-x-3 px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-rose-500 transition-all">
                                        <i class="fas fa-trash-alt w-4"></i>
                                        <span>Archive Log</span>
                                    </a>
                                </div>
                            </div>
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
