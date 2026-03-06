<?php
$pageTitle = "Guest Complaints";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Status Updates
if (isset($_GET['id']) && isset($_GET['status'])) {
    $adminCtrl->updateComplaintStatus($_GET['id'], $_GET['status']);
    header("Location: complaints.php?msg=Status+Updated");
    exit();
}

$complaints = $adminCtrl->getAllComplaints();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-6">
    <div class="text-center lg:text-left">
        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Resolution Center</h3>
        <p class="text-sm text-gray-400 mt-1">Manage guest issues and track resolution protocols.</p>
    </div>
    <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-gray-100 shadow-sm">
        <div class="px-4 py-2 text-center">
            <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Active Tickets</p>
            <p class="text-xl font-black text-rose-500">
                <?php echo count(array_filter($complaints, function($c) { return $c['status'] !== 'Closed' && $c['status'] !== 'Resolved'; })); ?>
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if(!empty($complaints)): ?>
        <?php foreach($complaints as $c): ?>
        <div class="card-soft p-8 relative overflow-hidden group hover:-translate-y-1 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="w-16 h-16 rounded-[24px] bg-white border border-gray-100 shadow-sm flex items-center justify-center text-primary font-black text-xl">
                    <?php echo $c['room_number']; ?>
                </div>
                <div>
                    <?php 
                        $statusClass = match($c['status']) {
                            'Open' => 'text-rose-500 bg-rose-50',
                            'In Progress' => 'text-amber-500 bg-amber-50',
                            'Resolved' => 'text-emerald-500 bg-emerald-50',
                            'Closed' => 'text-gray-400 bg-gray-50',
                            default => 'text-gray-400 bg-gray-50'
                        };
                    ?>
                    <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $statusClass; ?>">
                        <?php echo $c['status']; ?>
                    </span>
                </div>
            </div>

            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest opacity-50"><?php echo $c['type']; ?></h4>
                    <span class="text-[8px] text-gray-400 font-bold uppercase tracking-[1px]"><?php echo date('d M H:i', strtotime($c['created_at'])); ?></span>
                </div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1"><?php echo htmlspecialchars($c['guest_name']); ?></p>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 min-h-[80px]">
                    <p class="text-xs text-gray-600 italic">"<?php echo htmlspecialchars($c['description']); ?>"</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="relative group/select">
                    <button class="w-full py-2.5 bg-gray-50 text-[9px] font-black uppercase tracking-widest text-gray-400 rounded-xl border border-gray-100 hover:border-primary hover:text-primary transition-all flex items-center justify-center gap-2">
                        Status <i class="fas fa-chevron-down text-[7px]"></i>
                    </button>
                    <div class="absolute bottom-full left-0 w-full mb-2 bg-white rounded-xl shadow-2xl border border-gray-50 opacity-0 invisible group-hover/select:opacity-100 group-hover/select:visible transition-all z-20 overflow-hidden">
                        <a href="?id=<?php echo $c['id']; ?>&status=Open" class="block px-4 py-2.5 text-[8px] font-bold text-gray-400 hover:bg-rose-50 hover:text-rose-500 uppercase tracking-widest">Open</a>
                        <a href="?id=<?php echo $c['id']; ?>&status=In+Progress" class="block px-4 py-2.5 text-[8px] font-bold text-gray-400 hover:bg-amber-50 hover:text-amber-500 uppercase tracking-widest">In Progress</a>
                        <a href="?id=<?php echo $c['id']; ?>&status=Resolved" class="block px-4 py-2.5 text-[8px] font-bold text-gray-400 hover:bg-emerald-50 hover:text-emerald-500 uppercase tracking-widest">Resolved</a>
                        <a href="?id=<?php echo $c['id']; ?>&status=Closed" class="block px-4 py-2.5 text-[8px] font-bold text-gray-400 hover:bg-gray-50 hover:text-gray-900 uppercase tracking-widest">Closed</a>
                    </div>
                </div>
                <button onclick="openContactModal('<?php echo $c['guest_email']; ?>', '<?php echo $c['type']; ?>')" class="w-full py-2.5 bg-primary/5 text-primary text-[9px] font-black uppercase tracking-widest rounded-xl border border-primary/10 hover:bg-primary hover:text-white transition-all">
                    Contact Guest
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full py-20 bg-white rounded-[40px] border border-gray-100 flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 bg-emerald-500/10 rounded-full flex items-center justify-center text-emerald-500 mb-6">
                <i class="fas fa-check-circle text-3xl"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-800">No Complaints</h4>
            <p class="text-sm text-gray-400 mt-2">The hospitality matrix is operating within optimal parameters.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Contact Guest Modal -->
<div id="contactModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-6 bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-[40px] p-10 relative animate-slide-up">
        <button onclick="closeModal('contactModal')" class="absolute top-8 right-8 text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-times"></i>
        </button>
        
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Concierge Outbound</h3>
        <p class="text-sm text-gray-400 mb-8">Send a formalized resolution message to the guest.</p>
        
        <form id="contactForm" onsubmit="handleContactSubmit(event)" class="space-y-6">
            <input type="hidden" id="contactEmail" name="email">
            
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Subject Header</label>
                <input type="text" id="contactSubject" name="subject" required 
                       class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Resolution Intelligence</label>
                <textarea id="contactMessage" name="message" rows="4" required 
                          class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all"
                          placeholder="Detail the steps taken to resolve the issue..."></textarea>
            </div>

            <button type="submit" id="sendBtn" class="w-full bg-gradient-to-r from-primary to-secondary text-white p-5 rounded-2xl font-bold uppercase tracking-[4px] text-xs shadow-xl shadow-primary/20 mt-4 flex items-center justify-center gap-3">
                <span>Transmit Response</span>
                <i class="fas fa-paper-plane text-[10px]"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function openContactModal(email, type) {
        document.getElementById('contactEmail').value = email;
        document.getElementById('contactSubject').value = "In regards to your quest for: " + type;
        document.getElementById('contactModal').classList.remove('hidden');
        document.getElementById('contactModal').classList.add('flex');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('flex');
        document.getElementById(id).classList.add('hidden');
    }

    function handleContactSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('sendBtn');
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Transmitting...';
        
        const formData = new FormData(e.target);
        
        fetch('api/contact_guest.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showToast(data.message, 'success');
                closeModal('contactModal');
                e.target.reset();
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast("Protocol failure: Unable to transmit intelligence.", 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", 'success');
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
