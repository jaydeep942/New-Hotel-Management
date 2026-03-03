<?php
$pageTitle = "System Configuration";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        $adminCtrl->updateSettings([
            'hotel_name' => $_POST['hotel_name'],
            'contact_email' => $_POST['contact_email'],
            'contact_phone' => $_POST['contact_phone'],
            'currency' => $_POST['currency'],
            'address' => $_POST['address']
        ]);
        header("Location: settings.php?msg=System+Config+Updated");
        exit();
    }
}

$settings = $adminCtrl->getSettings();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="max-w-4xl mx-auto space-y-10">
    <div class="text-center">
        <h3 class="text-2xl font-bold text-gray-800">Master Configuration</h3>
        <p class="text-sm text-gray-400">Global parameters for the Grand Luxe ecosystem.</p>
    </div>

    <div class="card-soft p-12">
        <form action="" method="POST" class="space-y-10">
            <input type="hidden" name="action" value="update_settings">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- Hotel Branding -->
                <div class="space-y-6">
                    <h4 class="text-[10px] uppercase font-black tracking-[4px] text-primary">Identity & Branding</h4>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Proprietary Designation</label>
                        <input type="text" name="hotel_name" value="<?php echo $settings['hotel_name']; ?>" 
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-primary/50 transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Yield Units (Currency)</label>
                        <select name="currency" class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-primary/50 transition-all font-bold">
                            <option value="₹" <?php echo $settings['currency'] == '₹' ? 'selected' : ''; ?>>INR (₹)</option>
                            <option value="$" <?php echo $settings['currency'] == '$' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="€" <?php echo $settings['currency'] == '€' ? 'selected' : ''; ?>>EUR (€)</option>
                        </select>
                    </div>
                </div>

                <!-- Contact Intel -->
                <div class="space-y-6">
                    <h4 class="text-[10px] uppercase font-black tracking-[4px] text-secondary">Operational Contact</h4>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Intelligence Email</label>
                        <input type="email" name="contact_email" value="<?php echo $settings['contact_email']; ?>" 
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-secondary/50 transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Emergency Relay (Phone)</label>
                        <input type="text" name="contact_phone" value="<?php echo $settings['contact_phone']; ?>" 
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-secondary/50 transition-all font-bold">
                    </div>
                </div>
            </div>

            <!-- Detailed Address -->
            <div class="space-y-2 pt-6 border-t border-gray-50">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Station Mapping (Address)</label>
                <textarea name="address" rows="3" class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-primary/50 transition-all font-medium"><?php echo $settings['address']; ?></textarea>
            </div>

            <div class="flex items-center justify-between pt-6">
                <div class="flex items-center -space-x-2">
                    <div class="w-10 h-10 rounded-full bg-primary/10 border-2 border-white flex items-center justify-center text-primary text-xs"><i class="fas fa-lock text-[10px]"></i></div>
                    <div class="w-10 h-10 rounded-full bg-secondary/10 border-2 border-white flex items-center justify-center text-secondary text-xs"><i class="fas fa-shield-alt text-[10px]"></i></div>
                </div>
                <button type="submit" class="bg-gradient-to-r from-primary to-secondary text-white px-12 py-5 rounded-[24px] font-bold uppercase tracking-[4px] text-xs shadow-2xl shadow-primary/20 hover:scale-[1.05] active:scale-[0.95] transition-all">
                    Commit Protocol Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Security Note -->
    <div class="p-8 bg-amber-50 rounded-[32px] border border-amber-100 flex items-start space-x-6">
        <div class="w-12 h-12 bg-amber-500 rounded-2xl flex items-center justify-center text-white shrink-0 shadow-lg shadow-amber-500/10">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <h5 class="text-sm font-black text-amber-700 uppercase tracking-widest">Protocol Warning</h5>
            <p class="text-[11px] text-amber-600 font-medium leading-relaxed mt-1">Changes to the system configuration affect the entire operational grid instantly. Ensure all parameters are verified before committing protocol changes to the master record.</p>
        </div>
    </div>
</div>

<script>
    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", 'success');
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
