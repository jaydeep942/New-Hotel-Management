<?php
$pageTitle = "Guest Feedback";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Operations
if (isset($_GET['action']) && isset($_GET['id'])) {
    if ($_GET['action'] === 'delete') {
        $adminCtrl->deleteFeedback($_GET['id']);
        header("Location: feedback.php?msg=Feedback+Removed");
        exit();
    }
}

$feedback = $adminCtrl->getAllFeedback();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-6">
    <div class="text-center lg:text-left">
        <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Experience Analytics</h3>
        <p class="text-sm text-gray-400 mt-1">Review guest testimonials and hospitality ratings.</p>
    </div>
    <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-gray-100 shadow-sm">
        <div class="px-4 py-2 text-center">
            <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Avg Rating</p>
            <p class="text-xl font-black text-primary">
                <?php 
                    if(count($feedback) > 0) {
                        $avg = array_sum(array_column($feedback, 'rating')) / count($feedback);
                        echo number_format($avg, 1);
                    } else {
                        echo "0.0";
                    }
                ?>
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php if(!empty($feedback)): ?>
        <?php foreach($feedback as $f): ?>
        <div class="card-soft p-8 relative overflow-hidden group hover:-translate-y-1 transition-all">
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary font-black text-sm">
                        <?php echo strtoupper(substr($f['guest_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <h5 class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($f['guest_name']); ?></h5>
                        <p class="text-[8px] text-gray-400 font-bold uppercase tracking-widest mt-0.5"><?php echo $f['category']; ?></p>
                    </div>
                </div>
                <div class="flex text-amber-400 text-[10px]">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $f['rating'] ? '' : 'text-gray-100'; ?>"></i>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="mb-6">
                <div class="bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100 relative min-h-[120px]">
                    <i class="fas fa-quote-left absolute top-4 left-4 text-primary/5 text-2xl"></i>
                    <p class="text-xs text-gray-600 italic relative z-10">"<?php echo htmlspecialchars($f['message']); ?>"</p>
                </div>
            </div>

            <div class="flex items-center justify-between mt-auto pt-6 border-t border-gray-50">
                <p class="text-[8px] text-gray-300 font-black uppercase tracking-widest"><?php echo date('d M Y', strtotime($f['created_at'])); ?></p>
                <div class="flex gap-2">
                    <button onclick="showToast('Feedback marked as internally reviewed.', 'success')" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all text-[10px]">
                        <i class="fas fa-check"></i>
                    </button>
                    <a href="?action=delete&id=<?php echo $f['id']; ?>" 
                       onclick="return confirm('Archive this feedback protocol? This cannot be reversed.')"
                       class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all text-[10px]">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full py-20 bg-white rounded-[40px] border border-gray-100 flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 bg-indigo-50/10 rounded-full flex items-center justify-center text-indigo-500 mb-6">
                <i class="fas fa-comment-slash text-3xl"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-800">Silence is Golden</h4>
            <p class="text-sm text-gray-400 mt-2">No guest testimonials found in the repository.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", 'success');
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
