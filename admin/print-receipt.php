<?php
require_once '../controllers/AdminController.php';
$adminCtrl = new AdminController();
$adminCtrl->checkAuth();

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid Request");
}

$b = $adminCtrl->getBookingById($id);
if (!$b) {
    die("Residency Record Not Found");
}

// Calculate Nights
$d1 = new DateTime($b['check_in']);
$d2 = new DateTime($b['check_out']);
$nights = $d1->diff($d2)->days ?: 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #LX-<?php echo str_pad($b['id'], 5, '0', STR_PAD_LEFT); ?> | Grand Luxe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap');
        body { font-family: 'Outfit', sans-serif; background: #f9fafb; }
        .receipt-paper { background: white; width: 210mm; min-height: 297mm; margin: 40px auto; padding: 60px; box-shadow: 0 40px 100px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        @media print {
            body { background: white; margin: 0; }
            .receipt-paper { margin: 0; box-shadow: none; width: 100%; border: none; }
            .print-btn { display: none; }
        }
        .maroon-gradient { background: linear-gradient(135deg, #6A1E2D 0%, #832537 100%); }
    </style>
</head>
<body>

    <div class="fixed top-8 right-8 print-btn z-50">
        <button onclick="window.print()" class="bg-maroon text-white px-8 py-4 rounded-2xl font-bold shadow-2xl hover:scale-105 transition-all flex items-center space-x-3" style="background-color: #6A1E2D;">
            <i class="fas fa-print text-sm"></i>
            <span>Execute Print</span>
        </button>
    </div>

    <div class="receipt-paper">
        <!-- Watermark -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 opacity-[0.02] pointer-events-none select-none">
            <h1 class="text-[120px] font-black tracking-tighter" style="font-family: 'Playfair Display', serif;">GRANDLUXE</h1>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-start mb-20 relative z-10">
            <div>
                <h1 class="text-4xl font-bold tracking-tighter text-maroon mb-2" style="font-family: 'Playfair Display', serif; color: #6A1E2D;">
                    GRAND<span style="color: #D4AF37;">LUXE</span>
                </h1>
                <p class="text-[10px] uppercase tracking-[5px] font-black text-gray-400">Excellence Defined</p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-black uppercase tracking-widest text-gray-800">Official Receipt</h2>
                <p class="text-gray-400 font-bold text-xs mt-1">ID: #LX-<?php echo str_pad($b['id'], 5, '0', STR_PAD_LEFT); ?></p>
                <p class="text-gray-400 font-bold text-[10px] uppercase mt-1">Date: <?php echo date('d M Y'); ?></p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-20 mb-20 relative z-10">
            <div>
                <h6 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Resident Protocol</h6>
                <p class="text-lg font-bold text-gray-800"><?php echo $b['guest_name']; ?></p>
                <p class="text-gray-500 text-sm mt-1"><?php echo $b['customer_email']; ?></p>
                <p class="text-gray-500 text-sm"><?php echo $b['customer_phone']; ?></p>
            </div>
            <div>
                <h6 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Residency Intel</h6>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-400">Suite Assignment:</span>
                    <span class="font-bold text-gray-800">Suite <?php echo $b['room_number']; ?></span>
                </div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-400">Type:</span>
                    <span class="font-bold text-gray-800"><?php echo $b['room_type']; ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Duration:</span>
                    <span class="font-bold text-gray-800"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="mb-20 relative z-10">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b-2 border-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-400">
                        <th class="py-5">Description</th>
                        <th class="py-5 px-8">Rate</th>
                        <th class="py-5 px-8">Nights</th>
                        <th class="py-5 text-right">Yield</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr>
                        <td class="py-8">
                            <span class="font-bold text-gray-800">Luxury Stay Residency</span>
                            <p class="text-[10px] text-gray-400 uppercase tracking-tight mt-1">
                                <?php echo date('M d', strtotime($b['check_in'])); ?> — <?php echo date('M d, Y', strtotime($b['check_out'])); ?>
                            </p>
                        </td>
                        <td class="py-8 px-8 font-bold text-gray-700">₹<?php echo number_format($b['price_per_night'], 0); ?></td>
                        <td class="py-8 px-8 font-bold text-gray-700"><?php echo $nights; ?></td>
                        <td class="py-8 text-right font-bold text-gray-800">₹<?php echo number_format($b['total_amount'], 0); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end relative z-10">
            <div class="w-80">
                <div class="flex justify-between py-2 text-sm">
                    <span class="text-gray-400">Portfolio Subtotal:</span>
                    <span class="font-bold text-gray-800">₹<?php echo number_format($b['total_amount'], 0); ?></span>
                </div>
                <div class="flex justify-between py-2 text-sm">
                    <span class="text-gray-400">Luxury Taxes (0%):</span>
                    <span class="font-bold text-gray-800">₹0</span>
                </div>
                <div class="h-px bg-gray-100 my-4"></div>
                <div class="flex justify-between py-4 items-center">
                    <span class="text-[10px] font-black uppercase tracking-widest maroon-text" style="color: #6A1E2D;">Grand Portfolio Total</span>
                    <span class="text-3xl font-black text-gray-900 tracking-tighter">₹<?php echo number_format($b['total_amount'], 0); ?></span>
                </div>
                <div class="mt-6 p-4 rounded-xl bg-emerald-50 text-emerald-600 text-center text-[10px] font-black uppercase tracking-widest">
                    <?php echo $b['payment_status']; ?> IN FULL
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-40 pt-20 border-t border-gray-50 text-center relative z-10">
            <p class="text-gray-400 text-xs font-bold italic">"Excellence is not an act, but a habit."</p>
            <div class="mt-8 flex justify-center space-x-12 grayscale opacity-40">
                <div class="text-[10px] font-black tracking-[4px] uppercase">Grand Luxe Portfolio</div>
                <div class="text-[10px] font-black tracking-[4px] uppercase">Official Archive</div>
            </div>
        </div>
    </div>

    <script>
        // Optional: Auto-print on load if needed, but manual trigger is safer
        // window.onload = () => window.print();
    </script>
</body>
</html>
