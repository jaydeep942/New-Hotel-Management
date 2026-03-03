<?php
$pageTitle = "Room Matrix";
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $adminCtrl->addRoom([
            'room_number' => $_POST['room_number'],
            'room_type' => $_POST['room_type'],
            'price' => $_POST['price'],
            'status' => $_POST['status'],
            'image' => $_POST['image'] ?? '',
            'description' => $_POST['description'] ?? ''
        ]);
        header("Location: rooms.php?msg=Room+Added");
        exit();
    } elseif ($_POST['action'] === 'edit') {
        $adminCtrl->updateRoom($_POST['room_id'], [
            'room_number' => $_POST['room_number'],
            'room_type' => $_POST['room_type'],
            'price' => $_POST['price'],
            'status' => $_POST['status'],
            'image' => $_POST['image'] ?? '',
            'description' => $_POST['description'] ?? ''
        ]);
        header("Location: rooms.php?msg=Suite+Updated");
        exit();
    }
}

// Handle Room Deletion
if (isset($_GET['delete_id'])) {
    $adminCtrl->deleteRoom($_GET['delete_id']);
    header("Location: rooms.php?msg=Suite+Decommissioned");
    exit();
}

$rooms = $adminCtrl->getAllRooms();

include '../includes/admin_header.php';
include '../includes/admin_sidebar.php';
?>

<div class="flex justify-between items-center mb-10">
    <div>
        <h3 class="text-2xl font-bold text-gray-800">Suite Inventory</h3>
        <p class="text-sm text-gray-400">Manage and monitor all Grand Luxe residencies.</p>
    </div>
    <button onclick="openModal('addRoomModal')" class="bg-gradient-to-r from-primary to-secondary text-white px-8 py-4 rounded-2xl font-bold uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all">
        <i class="fas fa-plus mr-2"></i> Deploy New Suite
    </button>
</div>

<!-- Room Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">
    <?php foreach($rooms as $r): ?>
    <div class="card-soft overflow-hidden group">
        <div class="h-48 bg-gray-100 relative overflow-hidden">
            <img src="<?php echo !empty($r['image']) ? $r['image'] : 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&q=80&w=800'; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
            <div class="absolute top-4 right-4">
                <?php 
                    $statusClass = match($r['status']) {
                        'Available' => 'bg-emerald-500',
                        'Booked' => 'bg-primary',
                        'Maintenance' => 'bg-amber-500',
                        'Needs Cleaning' => 'bg-rose-500',
                        default => 'bg-gray-500'
                    };
                ?>
                <span class="<?php echo $statusClass; ?> text-white text-[8px] font-black px-3 py-1.5 rounded-full uppercase tracking-widest shadow-lg">
                    <?php echo $r['status']; ?>
                </span>
            </div>
        </div>
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h4 class="text-lg font-bold text-gray-800">Suite <?php echo $r['room_number']; ?></h4>
                    <p class="text-[10px] uppercase tracking-widest font-black text-gray-400"><?php echo $r['room_type']; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Nightly Yield</p>
                    <p class="text-lg font-black text-primary">₹<?php echo number_format($r['price_per_night'], 0); ?></p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2 pt-4 border-t border-gray-50">
                <button onclick="toggleStatus(<?php echo $r['id']; ?>, 'Available')" class="flex-1 p-2 bg-gray-50 text-gray-400 hover:text-emerald-500 hover:bg-emerald-50 rounded-xl transition-all" title="Available"><i class="fas fa-check text-xs"></i></button>
                <button onclick="toggleStatus(<?php echo $r['id']; ?>, 'Maintenance')" class="flex-1 p-2 bg-gray-50 text-gray-400 hover:text-amber-500 hover:bg-amber-50 rounded-xl transition-all" title="Maintenance"><i class="fas fa-tools text-xs"></i></button>
                <button onclick='openEditModal(<?php echo json_encode($r); ?>)' class="flex-1 p-2 bg-gray-50 text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-xl transition-all" title="Edit"><i class="fas fa-edit text-xs"></i></button>
                <a href="?delete_id=<?php echo $r['id']; ?>" onclick="return confirm('Decommission this suite permanently?')" class="flex-1 p-2 bg-gray-50 text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl text-center transition-all" title="Delete"><i class="fas fa-trash text-xs"></i></a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Room Modal -->
<div id="addRoomModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-6 bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-[40px] p-10 animate-slide-up relative">
        <button onclick="closeModal('addRoomModal')" class="absolute top-8 right-8 text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-times"></i>
        </button>
        
        <h3 class="text-2xl font-bold text-gray-800 mb-2">New Suite Protocol</h3>
        <p class="text-sm text-gray-400 mb-8">Deploy a new high-end residency to the inventory.</p>
        
        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Suite Number</label>
                    <input type="text" name="room_number" required placeholder="101" 
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Classification</label>
                    <select name="room_type" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Luxe Suite</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Nightly Yield (₹)</label>
                    <input type="number" name="price" required placeholder="2500" 
                           class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Initial Status</label>
                    <select name="status" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                        <option value="Available">Available</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Visual Mapping (Image URL)</label>
                <input type="url" name="image" placeholder="https://..." 
                       class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white p-5 rounded-2xl font-bold uppercase tracking-[4px] text-xs shadow-xl shadow-primary/20 mt-4">
                Deploy Suite
            </button>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<div id="editRoomModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-6 bg-gray-900/60 backdrop-blur-sm">
    <div class="bg-white w-full max-w-lg rounded-[40px] p-10 animate-slide-up relative">
        <button onclick="closeModal('editRoomModal')" class="absolute top-8 right-8 text-gray-400 hover:text-gray-600 transition-colors">
            <i class="fas fa-times"></i>
        </button>
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Refine Suite Specs</h3>
        <p class="text-sm text-gray-400 mb-8">Update the operational parameters for this residency.</p>
        <form action="" method="POST" class="space-y-6">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="edit_room_id">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Suite Number</label>
                    <input type="text" name="room_number" id="edit_room_number" required class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Classification</label>
                    <select name="room_type" id="edit_room_type" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                        <option value="Standard">Standard</option>
                        <option value="Deluxe">Deluxe</option>
                        <option value="Suite">Luxe Suite</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Nightly Yield (₹)</label>
                    <input type="number" name="price" id="edit_room_price" required class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Deployment Status</label>
                    <select name="status" id="edit_room_status" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all">
                        <option value="Available">Available</option>
                        <option value="Booked">Booked</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Needs Cleaning">Needs Cleaning</option>
                    </select>
                </div>
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-2">Description Intel</label>
                <textarea name="description" id="edit_room_desc" rows="2" class="w-full bg-gray-50 border border-gray-100 p-4 rounded-2xl text-gray-800 outline-none focus:border-primary/50 transition-all"></textarea>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white p-5 rounded-2xl font-bold uppercase tracking-[4px] text-xs shadow-xl shadow-primary/20 mt-4">Commit Changes</button>
        </form>
    </div>
</div>

<script>
    function openEditModal(room) {
        document.getElementById('edit_room_id').value = room.id;
        document.getElementById('edit_room_number').value = room.room_number;
        document.getElementById('edit_room_type').value = room.room_type;
        document.getElementById('edit_room_price').value = room.price_per_night;
        document.getElementById('edit_room_status').value = room.status;
        document.getElementById('edit_room_desc').value = room.description || '';
        openModal('editRoomModal');
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    // AJAX for Status Update
    function toggleStatus(roomId, status) {
        fetch('api/update_room_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: roomId, status: status })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showToast(`Suite ${roomId} status updated to ${status}.`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || 'System error detected.', 'error');
            }
        });
    }

    <?php if(isset($_GET['msg'])): ?>
        showToast("<?php echo $_GET['msg']; ?>", 'success');
    <?php endif; ?>
</script>

<?php include '../includes/admin_footer.php'; ?>
