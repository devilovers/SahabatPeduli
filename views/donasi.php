<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /PeduliUmat/auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$preset_type = $_GET['type'] ?? 'infaq';
$preset_amount = floatval($_GET['amount'] ?? 0);
$preset_campaign = intval($_GET['campaign_id'] ?? 0);

if ($preset_campaign > 0) {
    $preset_type = 'program';
}

$stmtCampaigns = $pdo->query("SELECT id, title, category FROM campaigns WHERE status = 'active' ORDER BY title ASC");
$campaigns = $stmtCampaigns->fetchAll();

$error = "";
$success_donation = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'infaq';
    $campaign_id = !empty($_POST['campaign_id']) ? intval($_POST['campaign_id']) : NULL;
    $amount = floatval($_POST['amount'] ?? 0);
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $custom_name = trim($_POST['display_name'] ?? '');

    if ($amount < 10000) {
        $error = "Minimal nominal donasi adalah Rp 10.000.";
    } else {
        $display_name = $is_anonymous ? "Hamba Allah" : (!empty($custom_name) ? $custom_name : $user_name);
        $order_id = "PDU-" . time() . "-" . rand(100, 999);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO donations (order_id, user_id, type, campaign_id, amount, is_anonymous, display_name, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$order_id, $user_id, $type, $campaign_id, $amount, $is_anonymous, $display_name]);

            if ($campaign_id) {
                $stmtUpdate = $pdo->prepare("UPDATE campaigns SET collected_amount = collected_amount + ? WHERE id = ?");
                $stmtUpdate->execute([$amount, $campaign_id]);
            }

            $stmtPaid = $pdo->prepare("UPDATE donations SET payment_status = 'paid' WHERE order_id = ?");
            $stmtPaid->execute([$order_id]);

            $pdo->commit();
            $success_donation = [
                'order_id' => $order_id,
                'amount' => $amount,
                'display_name' => $display_name,
                'type' => $type
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal memproses donasi: " . $e->getMessage();
        }
    }
}

$page_title = "Formulir Donasi";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="bg-slate-50 py-12 lg:py-16 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <?php if ($success_donation): ?>
            <div class="bg-white p-8 sm:p-12 rounded-3xl border border-slate-100 shadow-xl text-center space-y-6">
                <div class="w-20 h-20 bg-emerald-100 text-brand-600 rounded-full flex items-center justify-center mx-auto text-4xl shadow-inner animate-bounce">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="space-y-2">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Niat Baik Anda telah Terkirim!</h2>
                    <p class="text-slate-500 text-sm">Terima kasih telah menitipkan amanah donasi melalui PeduliUmat.</p>
                </div>

                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200/60 max-w-md mx-auto text-left space-y-3 text-sm">
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Order ID:</span>
                        <span class="font-mono font-bold text-slate-900"><?= $success_donation['order_id']; ?></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Atas Nama:</span>
                        <span class="font-bold text-slate-900"><?= htmlspecialchars($success_donation['display_name']); ?></span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 pb-2">
                        <span class="text-slate-500">Kategori:</span>
                        <span class="font-bold text-brand-600 uppercase"><?= $success_donation['type']; ?></span>
                    </div>
                    <div class="flex justify-between pt-1 text-base">
                        <span class="font-bold text-slate-900">Total Dibayar:</span>
                        <span class="font-black text-brand-600">Rp <?= number_format($success_donation['amount'], 0, ',', '.'); ?></span>
                    </div>
                </div>

                <div class="pt-4 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/PeduliUmat/index.php" class="px-8 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-500/20 transition-all">
                        Kembali ke Beranda
                    </a>
                    <a href="/PeduliUmat/views/penyaluran.php" class="px-8 py-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition-all">
                        Lihat Peta Penyaluran
                    </a>
                </div>
            </div>
        <?php else: ?>

            <div class="text-center max-w-xl mx-auto mb-10">
                <span class="text-brand-600 font-bold text-sm uppercase tracking-wider">Formulir Donasi</span>
                <h1 class="text-3xl font-black text-slate-900 mt-1">Salurkan Kebaikan Anda</h1>
                <p class="text-slate-500 text-sm mt-2">Pilih kategori penyerahan dan nominal yang ingin Anda salurkan.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-xs mb-6">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 sm:p-10 rounded-3xl border border-slate-100 shadow-xl">
                <form method="POST" action="" class="space-y-6">

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Pilih Jenis Penyaluran</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <?php 
                            $types = [
                                'zakat' => ['icon' => 'fa-coins', 'label' => 'Zakat'],
                                'infaq' => ['icon' => 'fa-hand-holding-dollar', 'label' => 'Infaq'],
                                'sedekah' => ['icon' => 'fa-box-open', 'label' => 'Sedekah'],
                                'wakaf' => ['icon' => 'fa-building-columns', 'label' => 'Wakaf'],
                                'fidyah' => ['icon' => 'fa-utensils', 'label' => 'Fidyah'],
                                'program' => ['icon' => 'fa-folder-open', 'label' => 'Program Peduli'],
                            ];
                            foreach ($types as $key => $val):
                            ?>
                                <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-slate-200 cursor-pointer hover:border-brand-500 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50 transition-all">
                                    <input type="radio" name="type" value="<?= $key; ?>" class="sr-only" <?= $preset_type === $key ? 'checked' : ''; ?> onchange="toggleProgramDropdown(this.value)">
                                    <div class="w-8 h-8 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-sm">
                                        <i class="fa-solid <?= $val['icon']; ?>"></i>
                                    </div>
                                    <span class="font-bold text-xs text-slate-800"><?= $val['label']; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="programSelectContainer" class="<?= $preset_type === 'program' ? '' : 'hidden'; ?>">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Program Peduli Spesifik</label>
                        <select name="campaign_id" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none">
                            <option value="">-- Pilih Program --</option>
                            <?php foreach ($campaigns as $camp): ?>
                                <option value="<?= $camp['id']; ?>" <?= $preset_campaign == $camp['id'] ? 'selected' : ''; ?>>
                                    [<?= strtoupper(str_replace('peduli_', '', $camp['category'])); ?>] <?= htmlspecialchars($camp['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Nominal Donasi (Rp)</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                            <button type="button" onclick="setAmount(50000)" class="py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 focus:bg-brand-600 focus:text-white transition-all">Rp 50.000</button>
                            <button type="button" onclick="setAmount(100000)" class="py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 focus:bg-brand-600 focus:text-white transition-all">Rp 100.000</button>
                            <button type="button" onclick="setAmount(250000)" class="py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 focus:bg-brand-600 focus:text-white transition-all">Rp 250.000</button>
                            <button type="button" onclick="setAmount(500000)" class="py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 focus:bg-brand-600 focus:text-white transition-all">Rp 500.000</button>
                        </div>
                        <input type="number" id="inputAmount" name="amount" required min="10000" value="<?= $preset_amount > 0 ? $preset_amount : ''; ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Atau masukkan nominal lainnya">
                    </div>

                    <div class="space-y-4 border-t border-slate-100 pt-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nama Donatur yang Tampil</label>
                            <input type="text" id="displayNameInput" name="display_name" value="<?= htmlspecialchars($user_name); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Nama Anda">
                        </div>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_anonymous" id="anonymousCheckbox" onchange="toggleAnonymous(this.checked)" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300">
                            <span class="text-xs font-semibold text-slate-700">Berdonasi sebagai Hamba Allah (Sembunyikan Nama)</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full py-4 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-base shadow-lg shadow-brand-500/20 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock text-sm"></i>
                        <span>Lanjutkan ke Pembayaran (VA & E-Wallet)</span>
                    </button>
                </form>
            </div>

        <?php endif; ?>

    </div>
</div>

<script>
function setAmount(val) {
    document.getElementById('inputAmount').value = val;
}

function toggleProgramDropdown(type) {
    const container = document.getElementById('programSelectContainer');
    if (type === 'program') {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
}

function toggleAnonymous(checked) {
    const input = document.getElementById('displayNameInput');
    if (checked) {
        input.disabled = true;
        input.classList.add('bg-slate-100');
    } else {
        input.disabled = false;
        input.classList.remove('bg-slate-100');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>