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

<div class="flex items-center justify-between mb-8 animate-fade-in px-4">
    <div>
        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Manual Booking Protocol</h3>
        <p class="text-sm text-gray-500 mt-1">Initialize a premium residency entry for offline guests or walk-ins.</p>
    </div>
    <a href="bookings.php" class="flex items-center gap-2 px-5 py-2.5 bg-white/50 backdrop-blur-md border border-gray-100 rounded-xl text-gray-600 hover:text-primary transition-all hover:shadow-lg hover:-translate-y-0.5 group">
        <i class="fas fa-arrow-left text-xs group-hover:-translate-x-1 transition-transform"></i>
        <span class="text-sm font-bold">Back to Bookings</span>
    </a>
</div>

<?php if(isset($error)): ?>
<div class="bg-rose-50/80 backdrop-blur-md border border-rose-100 text-rose-600 px-6 py-4 rounded-2xl mb-8 text-sm font-bold flex items-center animate-bounce-subtle">
    <i class="fas fa-exclamation-circle mr-3"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="glass p-1 rounded-[2.5rem] shadow-2xl shadow-primary/5 animate-slide-up w-full">
    <div class="bg-white/40 backdrop-blur-xl rounded-[2.3rem] p-8 md:p-12">
        <form action="" method="POST" class="space-y-12">
            <!-- Guest Identification -->
            <div class="relative">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary">
                        <i class="fas fa-user-tie text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-[3px] text-gray-400">Guest Identification</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-0.5">Primary identity markers</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-8">
                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-primary transition-all z-10">Full Name</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-primary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-primary/5 transition-all">
                            <i class="fas fa-signature text-gray-300 mr-4 text-sm group-focus-within:text-primary transition-colors"></i>
                            <input type="text" name="guest_name" required placeholder="John Doe" 
                                   class="w-full py-4 bg-transparent text-gray-800 outline-none font-medium placeholder:text-gray-300">
                        </div>
                    </div>

                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-primary transition-all z-10">Email Address</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-primary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-primary/5 transition-all">
                            <i class="fas fa-envelope text-gray-300 mr-4 text-sm group-focus-within:text-primary transition-colors"></i>
                            <input type="email" name="guest_email" required placeholder="john@example.com" 
                                   class="w-full py-4 bg-transparent text-gray-800 outline-none font-medium placeholder:text-gray-300">
                        </div>
                    </div>

                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-primary transition-all z-10">Phone Number</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-primary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-primary/5 transition-all">
                            <i class="fas fa-phone text-gray-300 mr-4 text-sm group-focus-within:text-primary transition-colors"></i>
                            <input type="text" name="guest_phone" required placeholder="+91 00000 00000" 
                                   class="w-full py-4 bg-transparent text-gray-800 outline-none font-medium placeholder:text-gray-300">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="relative group">
                            <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-primary transition-all z-10">ID Type</label>
                            <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-primary/40 focus-within:bg-white transition-all">
                                <select name="id_proof_type" class="w-full py-4 bg-transparent text-gray-800 outline-none font-medium appearance-none">
                                    <option value="Aadhar">Aadhar Card</option>
                                    <option value="Passport">Passport</option>
                                    <option value="Voter ID">Voter ID</option>
                                    <option value="Pan Card">PAN Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="relative group">
                            <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-primary transition-all z-10">ID Number</label>
                            <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-primary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-primary/5 transition-all">
                                <input type="text" name="id_proof_number" placeholder="XXXX-XXXX" 
                                       class="w-full py-4 bg-transparent text-gray-800 outline-none font-medium placeholder:text-gray-300 text-center">
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-primary transition-all z-10">Permanent Address</label>
                        <div class="flex items-start bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-4 focus-within:border-primary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-primary/5 transition-all">
                            <i class="fas fa-map-marker-alt text-gray-300 mr-4 mt-1 text-sm group-focus-within:text-primary transition-colors"></i>
                            <textarea name="address" rows="1" placeholder="Street, City, Zip Code..." 
                                      class="w-full bg-transparent text-gray-800 outline-none font-medium placeholder:text-gray-300 resize-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Residency Parameters -->
            <div class="relative">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-secondary/10 flex items-center justify-center text-secondary">
                        <i class="fas fa-key text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-[3px] text-gray-400">Residency Parameters</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-0.5">Space & duration allocation</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-secondary transition-all z-10">Suite Assignment</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-secondary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-secondary/5 transition-all">
                            <i class="fas fa-door-open text-gray-300 mr-4 text-sm group-focus-within:text-secondary transition-colors"></i>
                            <select name="room_id" id="room_select" required class="w-full py-4 bg-transparent text-gray-800 outline-none font-bold appearance-none">
                                <option value="">Select Suite</option>
                                <?php foreach($rooms as $r): ?>
                                <option value="<?php echo $r['id']; ?>" data-price="<?php echo $r['price_per_night']; ?>">
                                    Suite <?php echo $r['room_number']; ?> - ₹<?php echo number_format($r['price_per_night']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-secondary transition-all z-10">Arrival Date</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-secondary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-secondary/5 transition-all">
                            <i class="fas fa-calendar-check text-gray-300 mr-4 text-sm group-focus-within:text-secondary transition-colors"></i>
                            <input type="text" name="check_in" id="cin_date" required placeholder="Select Arrival"
                                   class="w-full py-4 bg-transparent text-gray-800 outline-none font-medium cursor-pointer">
                        </div>
                    </div>

                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-secondary transition-all z-10">Departure Date</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-secondary/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-secondary/5 transition-all">
                            <i class="fas fa-calendar-day text-gray-300 mr-4 text-sm group-focus-within:text-secondary transition-colors"></i>
                            <input type="text" name="check_out" id="cout_date" required placeholder="Select Departure"
                                   class="w-full py-4 bg-transparent text-gray-800 outline-none font-medium cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Analysis & Status -->
            <div class="relative">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-[3px] text-gray-400">Financial Analysis & Status</h4>
                        <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-0.5">Revenue yield & operational state</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end">
                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-emerald-500 transition-all z-10">Calculated Yield (₹)</label>
                        <div class="flex items-center bg-emerald-50/30 border border-emerald-100/50 rounded-2xl px-5 py-1 focus-within:border-emerald-500/40 focus-within:bg-white focus-within:shadow-xl focus-within:shadow-emerald-500/5 transition-all">
                            <span class="text-xl font-black text-emerald-500 mr-3">₹</span>
                            <input type="number" name="total_amount" id="total_amount" required placeholder="0" 
                                   class="w-full py-4 bg-transparent text-gray-900 outline-none font-black text-2xl placeholder:text-emerald-200">
                        </div>
                    </div>

                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-emerald-500 transition-all z-10">Booking Status</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-emerald-500/40 focus-within:bg-white transition-all">
                            <select name="status" class="w-full py-4 bg-transparent text-gray-800 outline-none font-bold appearance-none">
                                <option value="Confirmed">Confirmed</option>
                                <option value="Checked-In">Checked-In</option>
                            </select>
                        </div>
                    </div>

                    <div class="relative group">
                        <label class="absolute -top-2.5 left-4 px-2 bg-white/0 group-focus-within:bg-white text-[10px] font-black uppercase tracking-widest text-gray-400 group-focus-within:text-emerald-500 transition-all z-10">Payment Status</label>
                        <div class="flex items-center bg-gray-50/50 border border-gray-100 rounded-2xl px-5 py-1 focus-within:border-emerald-500/40 focus-within:bg-white transition-all">
                            <select name="payment_status" class="w-full py-4 bg-transparent text-gray-800 outline-none font-bold appearance-none">
                                <option value="Pending" class="text-rose-500">Pending</option>
                                <option value="Paid" class="text-emerald-500 font-bold">Paid</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-8">
                <button type="submit" class="group relative w-full overflow-hidden rounded-[2rem] p-6 text-white transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-primary via-secondary to-primary bg-[length:200%_100%] animate-gradient-slow"></div>
                    <div class="relative flex items-center justify-center gap-4">
                        <span class="text-xs font-black uppercase tracking-[8px]">Deploy Residency Protocol</span>
                        <i class="fas fa-bolt text-xs group-hover:animate-pulse"></i>
                    </div>
                </button>
                <p class="text-[10px] text-center text-gray-400 uppercase tracking-[4px] mt-6">Secure Transaction & Identity Verification Protocol Active</p>
            </div>
        </form>
    </div>
</div>

<style>
    .animate-gradient-slow {
        animation: gradient 6s ease infinite;
    }

    /* Premium Flatpickr Integration */
    .flatpickr-calendar {
        background: #fff;
        box-shadow: 0 40px 100px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 32px;
        font-family: 'Outfit', sans-serif;
        overflow: hidden !important;
        width: 320px !important;
        padding: 0;
    }
    .flatpickr-months {
        background: #6A1E2D; /* Maroon */
        padding: 15px 10px;
        color: #fff;
    }
    .flatpickr-months .flatpickr-month {
        height: 40px;
        color: #fff;
        fill: #fff;
    }
    .flatpickr-current-month {
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: inherit;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months {
        background: transparent !important;
        appearance: none;
        font-weight: 800;
        color: #fff;
    }
    .cur-year {
        font-weight: 800 !important;
        color: rgba(255,255,255,0.8) !important;
    }
    .flatpickr-innerContainer {
        padding: 15px;
    }
    .flatpickr-weekday {
        color: #6A1E2D;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 1px;
    }
    .flatpickr-day {
        border-radius: 12px;
        font-weight: 500;
        height: 40px;
        line-height: 40px;
    }
    .flatpickr-day.selected {
        background: #6A1E2D !important;
        border-color: #6A1E2D !important;
        box-shadow: 0 10px 20px rgba(106, 30, 45, 0.3);
    }
    .flatpickr-day:hover {
        background: rgba(106, 30, 45, 0.05);
    }
    .flatpickr-calendar.arrowTop:before, .flatpickr-calendar.arrowTop:after {
        border-bottom-color: #6A1E2D;
    }
</style>

<!-- Flatpickr Assets -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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

    // Initialize Flatpickr for Admin Manual Booking
    const coutPicker = flatpickr("#cout_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        minDate: "today",
        onChange: calculateYield
    });

    const cinPicker = flatpickr("#cin_date", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d/m/Y",
        minDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            coutPicker.set('minDate', dateStr);
            calculateYield();
        }
    });

    // Initial calculation on page load to ensure pre-filled data is captured
    window.addEventListener('DOMContentLoaded', calculateYield);
</script>

<?php include '../includes/admin_footer.php'; ?>
