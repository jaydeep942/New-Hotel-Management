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
            'address' => $_POST['address'],
            'logo' => $_POST['logo']
        ]);
        header("Location: settings.php?msg=System+Config+Updated");
        exit();
    } elseif ($_POST['action'] === 'update_profile') {
        $res = $adminCtrl->updateAdminProfile($_SESSION['admin_id'], [
            'name' => $_POST['admin_name'],
            'email' => $_POST['admin_email'],
            'password' => $_POST['admin_password']
        ]);
        if ($res) {
            header("Location: settings.php?msg=Admin+Profile+Updated");
        } else {
            header("Location: settings.php?msg=Update+Failed&type=error");
        }
        exit();
    }
}

$settings = $adminCtrl->getSettings();
// Fetch current admin info using the new protocol-safe method
$admin_data = $adminCtrl->getAdminData($_SESSION['admin_id']);

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
                        <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($settings['hotel_name']); ?>" 
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

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Digital Emblem (Logo URL)</label>
                        <input type="text" name="logo" value="<?php echo htmlspecialchars($settings['logo']); ?>" 
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-primary/50 transition-all font-bold"
                               placeholder="https://example.com/logo.png">
                    </div>
                </div>

                <!-- Contact Intel -->
                <div class="space-y-6">
                    <h4 class="text-[10px] uppercase font-black tracking-[4px] text-secondary">Operational Contact</h4>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Intelligence Email</label>
                        <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" 
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-secondary/50 transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Emergency Relay (Phone)</label>
                        <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>" 
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-secondary/50 transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Station Mapping (Address)</label>
                        <textarea name="address" rows="2" class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-primary/50 transition-all font-medium"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                    </div>
                </div>
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

    <!-- Administrative Identity -->
    <div class="text-center pt-8">
        <h3 class="text-2xl font-bold text-gray-800">Administrative Identity</h3>
        <p class="text-sm text-gray-400">Personalize your high-clearance access credentials.</p>
    </div>

    <div class="card-soft p-12">
        <form action="" method="POST" class="space-y-10">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-6">
                    <h4 class="text-[10px] uppercase font-black tracking-[4px] text-primary">Identity Profile</h4>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Operator Name</label>
                        <input type="text" name="admin_name" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-primary/50 transition-all font-bold">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Access Email</label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-primary/50 transition-all font-bold">
                    </div>
                </div>

                <div class="space-y-6">
                    <h4 class="text-[10px] uppercase font-black tracking-[4px] text-secondary">Security Protocol</h4>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Secure Passcode</label>
                        <input type="password" name="admin_password" placeholder="••••••••"
                               class="w-full bg-gray-50 border border-gray-100 p-5 rounded-[22px] text-gray-800 outline-none focus:border-secondary/50 transition-all font-bold">
                        <p class="text-[8px] text-gray-400 ml-2 uppercase tracking-wider font-bold italic">Leave blank to maintain current encryption</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end pt-6">
                <button type="submit" class="bg-gray-800 text-white px-12 py-5 rounded-[24px] font-bold uppercase tracking-[4px] text-xs shadow-2xl shadow-gray-800/20 hover:scale-[1.05] active:scale-[0.95] transition-all">
                    Update Access Profile
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", "<?php echo isset($_GET['type']) ? $_GET['type'] : 'success'; ?>");
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
