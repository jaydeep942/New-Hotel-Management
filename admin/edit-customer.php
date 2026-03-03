<?php
$pageTitle = "Edit Resident Identity";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

$userId = $_GET['id'] ?? null;
if (!$userId) {
    header("Location: customers.php");
    exit();
}

$user = $adminCtrl->getUserById($userId);
if (!$user) {
    header("Location: customers.php?error=Resident+Not+Found");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset_password'])) {
        $newPass = $adminCtrl->resetUserPassword($userId);
        if ($newPass) {
            $msg = "Credentials Reset. New Access Phrase: " . $newPass;
        } else {
            $error = "Protocol Error: Password reset failed.";
        }
    } else {
        $data = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'status' => $_POST['status']
        ];
        
        if ($adminCtrl->updateUser($userId, $data)) {
            header("Location: customers.php?msg=Identity+Protocol+Synchronized");
            exit();
        } else {
            $error = "System error detected. Data sync failed.";
        }
    }
}

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="mb-10 animate-fade-in">
    <h3 class="text-2xl font-bold text-gray-800">Edit Resident Profile</h3>
    <p class="text-sm text-gray-400">Modify identity parameters for LX-MEMBER-<?php echo str_pad($user['id'], 4, '0', STR_PAD_LEFT); ?>.</p>
</div>

<?php if(isset($error)): ?>
<div class="bg-rose-50 border border-rose-100 text-rose-500 px-6 py-4 rounded-2xl mb-8 text-sm font-bold flex items-center animate-bounce-subtle">
    <i class="fas fa-exclamation-circle mr-3"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<?php if(isset($msg)): ?>
<div class="bg-emerald-50 border border-emerald-100 text-emerald-500 px-6 py-4 rounded-2xl mb-8 text-sm font-bold flex items-center">
    <i class="fas fa-check-circle mr-3"></i> <?php echo $msg; ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-10">
    <!-- Main Form -->
    <div class="md:col-span-2">
        <div class="card-soft p-10 animate-slide-up">
            <form action="" method="POST" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Full Identity Name</label>
                        <input type="text" name="name" required value="<?php echo htmlspecialchars($user['name']); ?>"
                               class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Registered Email</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>"
                               class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Contact Protocol (Phone)</label>
                        <input type="text" name="phone" required value="<?php echo htmlspecialchars($user['phone']); ?>"
                               class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white focus:shadow-xl focus:shadow-primary/5 transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Account Status</label>
                        <select name="status" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 focus:bg-white transition-all font-bold">
                            <option value="Active" <?php echo $user['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $user['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white p-6 rounded-[28px] font-black uppercase tracking-[6px] text-xs shadow-2xl shadow-primary/30 hover:scale-[1.02] active:scale-95 transition-all">
                        Synchronize Identity
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Panel -->
    <div>
        <div class="card-soft p-8 border-dashed border-2 border-primary/20 bg-primary/5 space-y-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-lg flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-primary text-2xl"></i>
                </div>
                <h4 class="text-sm font-black uppercase tracking-widest text-gray-800">Security Override</h4>
                <p class="text-[10px] text-gray-400 leading-relaxed mt-2">Generate a temporary access passcode and transmit it via encrypted email.</p>
            </div>
            
            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to reset this users password? A new one will be sent via email.');">
                <input type="hidden" name="reset_password" value="1">
                <button type="submit" class="w-full bg-white border border-primary/30 text-primary p-4 rounded-2xl font-bold text-xs hover:bg-primary hover:text-white transition-all shadow-sm">
                    Regenerate Credentials
                </button>
            </form>

            <div class="pt-4 border-t border-primary/10">
                <p class="text-[9px] text-gray-400 text-center font-medium italic">Protocol: Security resets are logged in the audit trail.</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>
