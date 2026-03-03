<?php
$pageTitle = "Manual Booking Protocol";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

$rooms = $adminCtrl->getAvailableRooms();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'guest_name' => $_POST['guest_name'],
        'guest_email' => $_POST['guest_email'],
        'guest_phone' => $_POST['guest_phone'],
        'room_id' => $_POST['room_id'],
        'check_in' => $_POST['check_in'],
        'check_out' => $_POST['check_out'],
        'total_amount' => $_POST['total_amount'],
        'status' => $_POST['status'],
        'payment_status' => $_POST['payment_status'],
        'id_proof_type' => $_POST['id_proof_type'],
        'id_proof_number' => $_POST['id_proof_number'],
        'address' => $_POST['address']
    ];
    
    if($adminCtrl->manualBooking($data)) {
        header("Location: bookings.php?msg=Manual+Booking+Deployed");
        exit();
    } else {
        $error = "System error detected. Protocol failed.";
    }
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="mb-10 animate-fade-in">
    <h3 class="text-2xl font-bold text-gray-800">New Booking Protocol</h3>
    <p class="text-sm text-gray-400">Initialize a manual residency entry for offline guests or walk-ins.</p>
</div>

<?php if(isset($error)): ?>
<div class="bg-rose-50 border border-rose-100 text-rose-500 px-6 py-4 rounded-2xl mb-8 text-sm font-bold flex items-center animate-bounce-subtle">
    <i class="fas fa-exclamation-circle mr-3"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="card-soft p-10 max-w-4xl mx-auto md:mx-0 animate-slide-up">
    <form action="" method="POST" class="space-y-10">
        <!-- Guest Identification -->
        <div class="group">
            <h4 class="text-[10px] font-black uppercase tracking-[4px] text-gray-400 mb-8 flex items-center group-hover:text-primary transition-colors">
                <span class="w-8 h-px bg-gray-200 mr-4 group-hover:bg-primary transition-all group-hover:w-12"></span>
                Guest Identification
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Full Name</label>
                    <input type="text" name="guest_name" required placeholder="John Doe" 
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Email Address</label>
                    <input type="email" name="guest_email" required placeholder="john@example.com" 
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Phone Number</label>
                    <input type="text" name="guest_phone" required placeholder="+91 00000 00000" 
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">ID Proof Type</label>
                        <select name="id_proof_type" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white transition-all font-medium">
                            <option value="Aadhar">Aadhar Card</option>
                            <option value="Passport">Passport</option>
                            <option value="Voter ID">Voter ID</option>
                            <option value="Pan Card">PAN Card</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">ID Number</label>
                        <input type="text" name="id_proof_number" placeholder="XXXX-XXXX-XXXX" 
                               class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium">
                    </div>
                </div>
                <div class="md:col-span-2 space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Permanent Address</label>
                    <textarea name="address" rows="2" placeholder="Street, City, Zip Code..." 
                              class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium"></textarea>
                </div>
            </div>
        </div>

        <!-- Residency Parameters -->
        <div class="group">
            <h4 class="text-[10px] font-black uppercase tracking-[4px] text-gray-400 mb-8 flex items-center group-hover:text-secondary transition-colors">
                <span class="w-8 h-px bg-gray-200 mr-4 group-hover:bg-secondary transition-all group-hover:w-12"></span>
                Residency Parameters
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Suite Assignment</label>
                    <select name="room_id" id="room_select" required class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-secondary/50 focus:bg-white focus:shadow-xl focus:shadow-secondary/5 transition-all font-bold">
                        <option value="">Select Suite</option>
                        <?php foreach($rooms as $r): ?>
                        <option value="<?php echo $r['id']; ?>" data-price="<?php echo $r['price_per_night']; ?>">
                            Suite <?php echo $r['room_number']; ?> (<?php echo $r['room_type']; ?>) - ₹<?php echo $r['price_per_night']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Arrival Date</label>
                    <input type="date" name="check_in" id="cin_date" required min="<?php echo date('Y-m-d'); ?>"
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-secondary/50 focus:bg-white focus:shadow-xl focus:shadow-secondary/5 transition-all font-medium">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Departure Date</label>
                    <input type="date" name="check_out" id="cout_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-secondary/50 focus:bg-white focus:shadow-xl focus:shadow-secondary/5 transition-all font-medium">
                </div>
            </div>
        </div>

        <!-- Financial yield & Status -->
        <div class="group">
            <h4 class="text-[10px] font-black uppercase tracking-[4px] text-gray-400 mb-8 flex items-center group-hover:text-emerald-500 transition-colors">
                <span class="w-8 h-px bg-gray-200 mr-4 group-hover:bg-emerald-500 transition-all group-hover:w-12"></span>
                Financial yield & Status
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Calculated Total (₹)</label>
                    <input type="number" name="total_amount" id="total_amount" required placeholder="0" 
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-emerald-500/50 focus:bg-white focus:shadow-xl focus:shadow-emerald-500/5 transition-all font-black text-2xl">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Booking Status</label>
                    <select name="status" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-emerald-500/50 focus:bg-white transition-all font-bold">
                        <option value="Confirmed">Confirmed</option>
                        <option value="Checked-In">Checked-In</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Payment Status</label>
                    <select name="payment_status" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-emerald-500/50 focus:bg-white transition-all font-bold">
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="pt-6">
            <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white p-6 rounded-[28px] font-black uppercase tracking-[6px] text-xs shadow-2xl shadow-primary/30 hover:scale-[1.02] active:scale-95 transition-all">
                Initialize Residency Matrix
            </button>
        </div>
    </form>
</div>

<script>
    const roomSelect = document.getElementById('room_select');
    const cinInput = document.getElementById('cin_date');
    const coutInput = document.getElementById('cout_date');
    const amountInput = document.getElementById('total_amount');

    function calculateYield() {
        const option = roomSelect.options[roomSelect.selectedIndex];
        const price = option ? parseFloat(option.getAttribute('data-price')) : 0;
        const cin = cinInput.value ? new Date(cinInput.value) : null;
        const cout = coutInput.value ? new Date(coutInput.value) : null;

        if (price && cin && cout && cout > cin) {
            const diffTime = Math.abs(cout - cin);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            amountInput.value = price * diffDays;
        }
    }

    roomSelect.addEventListener('change', calculateYield);
    cinInput.addEventListener('change', calculateYield);
    coutInput.addEventListener('change', calculateYield);
</script>

<?php include '../includes/admin_footer.php'; ?>
