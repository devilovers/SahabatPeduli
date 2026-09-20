<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /SahabatPeduli/auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'] ?? 'donatur@gmail.com';

$preset_type = $_GET['type'] ?? 'infaq';
$preset_amount = floatval($_GET['amount'] ?? 0);
$preset_campaign = intval($_GET['campaign_id'] ?? 0);

if ($preset_campaign > 0) {
    $preset_type = 'program';
}

$stmtCampaigns = $pdo->query("SELECT id, title, category FROM campaigns WHERE status = 'active' ORDER BY title ASC");
$campaigns = $stmtCampaigns->fetchAll();

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? 'infaq';
    $campaign_id = !empty($_POST['campaign_id']) ? intval($_POST['campaign_id']) : NULL;
    $bank_target = $_POST['bank_target'] ?? 'BCA';
    
    $raw_amount = str_replace('.', '', $_POST['amount'] ?? '0');
    $amount = floatval($raw_amount);
    
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
    $custom_name = trim($_POST['display_name'] ?? '');

    if ($amount < 10000) {
        $error = "Minimal nominal donasi adalah Rp 10.000.";
    } elseif (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Wajib mengunggah bukti transfer pembayaran.";
    } else {
        $fileTmpPath = $_FILES['proof_image']['tmp_name'];
        $fileName = $_FILES['proof_image']['name'];
        $fileSize = $_FILES['proof_image']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $error = "Format file bukti transfer harus berupa JPG, JPEG, PNG, WEBP, atau PDF.";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $error = "Ukuran file maksimal adalah 5MB.";
        } else {
            $display_name = $is_anonymous ? "Hamba Allah" : (!empty($custom_name) ? $custom_name : $user_name);
            $order_id = "SHB-" . time() . "-" . rand(100, 999);

            $uploadDir = __DIR__ . '/../uploads/proofs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFileName = $order_id . '_' . time() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $proof_path = 'uploads/proofs/' . $newFileName;

                try {
                    // Deteksi struktur kolom di tabel 'donations'
                    $columnsQuery = $pdo->query("SHOW COLUMNS FROM donations");
                    $existingColumns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);
                    $columnNames = array_column($existingColumns, 'Field');

                    $pkCol = 'id';
                    $pkIsAutoIncrement = false;

                    foreach ($existingColumns as $col) {
                        if (($col['Key'] ?? '') === 'PRI') {
                            $pkCol = $col['Field'];
                            if (strpos(strtolower($col['Extra'] ?? ''), 'auto_increment') !== false) {
                                $pkIsAutoIncrement = true;
                            }
                            break;
                        }
                    }

                    $insertData = [];

                    // Jika tidak auto increment, hitung ID baru berdasarkan MAX(ID) + 1
                    if (!$pkIsAutoIncrement && in_array($pkCol, $columnNames)) {
                        $maxStmt = $pdo->query("SELECT MAX(CAST({$pkCol} AS UNSIGNED)) FROM donations");
                        $maxId = $maxStmt->fetchColumn();
                        $nextId = ($maxId !== false && $maxId !== null) ? (intval($maxId) + 1) : 1;
                        
                        // Jaga-jaga jika ID terhitung sudah ada, lakukan iterasi hingga menemukan ID kosong
                        while (true) {
                            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE {$pkCol} = ?");
                            $checkStmt->execute([$nextId]);
                            if ($checkStmt->fetchColumn() == 0) {
                                break;
                            }
                            $nextId++;
                        }
                        
                        $insertData[$pkCol] = $nextId;
                    }

                    if (in_array('order_id', $columnNames)) {
                        $insertData['order_id'] = $order_id;
                    }
                    if (in_array('user_id', $columnNames)) {
                        $insertData['user_id'] = $user_id;
                    }
                    if (in_array('type', $columnNames)) {
                        $insertData['type'] = $type;
                    }
                    if (in_array('campaign_id', $columnNames)) {
                        $insertData['campaign_id'] = $campaign_id;
                    }
                    if (in_array('amount', $columnNames)) {
                        $insertData['amount'] = $amount;
                    }
                    if (in_array('is_anonymous', $columnNames)) {
                        $insertData['is_anonymous'] = $is_anonymous;
                    }
                    if (in_array('display_name', $columnNames)) {
                        $insertData['display_name'] = $display_name;
                    }
                    if (in_array('payment_status', $columnNames)) {
                        $insertData['payment_status'] = 'pending';
                    }
                    if (in_array('proof_image', $columnNames)) {
                        $insertData['proof_image'] = $proof_path;
                    }

                    $fields = array_keys($insertData);
                    $placeholders = array_fill(0, count($fields), '?');

                    $sql = "INSERT INTO donations (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_values($insertData));

                    $success = "Terima kasih! Bukti transfer Anda telah berhasil diunggah. Kode Transaksi: <strong>$order_id</strong>. Admin akan melakukan verifikasi pembayaran Anda.";
                } catch (Exception $e) {
                    $error = "Gagal menyimpan data donasi: " . $e->getMessage();
                }
            } else {
                $error = "Terjadi kesalahan saat mengunggah berkas bukti transfer.";
            }
        }
    }
}

$page_title = "Formulir Donasi";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="bg-slate-50 dark:bg-slate-950 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center max-w-xl mx-auto mb-10">
            <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Formulir Donasi</span>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white mt-1">Salurkan Kebaikan Anda</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-2">Pilih kategori penyerahan, kirimkan donasi Anda, dan lampirkan bukti transfer.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-2xl text-xs mb-6 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-sm"></i> <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 px-6 py-4 rounded-2xl text-sm mb-6 flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-base mt-0.5"></i>
                <div><?= $success; ?></div>
            </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-slate-900 p-6 sm:p-10 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xl dark:shadow-slate-900/40 transition-colors">
            <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Pilih Jenis Penyaluran</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php 
                        $types = [
                            'zakat' => ['icon' => 'fa-coins', 'label' => 'Zakat'],
                            'infaq' => ['icon' => 'fa-hand-holding-dollar', 'label' => 'Infaq'],
                            'sedekah' => ['icon' => 'fa-box-open', 'label' => 'Sedekah'],
                            'fidyah' => ['icon' => 'fa-utensils', 'label' => 'Fidyah'],
                            'program' => ['icon' => 'fa-folder-open', 'label' => 'Program Peduli'],
                        ];
                        foreach ($types as $key => $val):
                        ?>
                            <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 cursor-pointer hover:border-brand-500 dark:hover:border-brand-500 has-[:checked]:border-brand-600 dark:has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 dark:has-[:checked]:bg-brand-900/40 transition-all">
                                <input type="radio" name="type" value="<?= $key; ?>" class="sr-only" <?= $preset_type === $key ? 'checked' : ''; ?> onchange="toggleProgramDropdown(this.value)">
                                <div class="w-8 h-8 rounded-xl bg-brand-100 dark:bg-brand-950/80 text-brand-700 dark:text-brand-400 flex items-center justify-center text-sm">
                                    <i class="fa-solid <?= $val['icon']; ?>"></i>
                                </div>
                                <span class="font-bold text-xs text-slate-800 dark:text-slate-200"><?= $val['label']; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="programSelectContainer" class="<?= $preset_type === 'program' ? '' : 'hidden'; ?>">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Program Peduli Spesifik</label>
                    <select name="campaign_id" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none transition-colors">
                        <option value="">-- Pilih Program --</option>
                        <?php foreach ($campaigns as $camp): ?>
                            <option value="<?= $camp['id']; ?>" <?= $preset_campaign == $camp['id'] ? 'selected' : ''; ?>>
                                [<?= strtoupper(str_replace('peduli_', '', $camp['category'])); ?>] <?= htmlspecialchars($camp['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Pilih Rekening Tujuan Transfer</label>
                    <select name="bank_target" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none transition-colors">
                        <option value="BCA - 1234567890 a.n Sahabat Peduli">BCA - 1234567890 a.n Sahabat Peduli</option>
                        <option value="Mandiri - 0987654321 a.n Sahabat Peduli">Mandiri - 0987654321 a.n Sahabat Peduli</option>
                        <option value="BRI - 1122334455 a.n Sahabat Peduli">BRI - 1122334455 a.n Sahabat Peduli</option>
                        <option value="BSI - 5544332211 a.n Sahabat Peduli">BSI - 5544332211 a.n Sahabat Peduli</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">Nominal Donasi (Rp)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
                        <button type="button" onclick="setAmount(50000)" class="py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 focus:bg-brand-600 dark:focus:bg-brand-600 focus:text-white transition-all">Rp 50.000</button>
                        <button type="button" onclick="setAmount(100000)" class="py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 focus:bg-brand-600 dark:focus:bg-brand-600 focus:text-white transition-all">Rp 100.000</button>
                        <button type="button" onclick="setAmount(250000)" class="py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 focus:bg-brand-600 dark:focus:bg-brand-600 focus:text-white transition-all">Rp 250.000</button>
                        <button type="button" onclick="setAmount(500000)" class="py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 focus:bg-brand-600 dark:focus:bg-brand-600 focus:text-white transition-all">Rp 500.000</button>
                    </div>
                    <input type="text" id="inputAmount" name="amount" required value="<?= $preset_amount > 0 ? number_format($preset_amount, 0, ',', '.') : ''; ?>" onkeyup="formatRupiah(this)" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none transition-colors" placeholder="Atau masukkan nominal lainnya (Min. 10.000)">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                        Unggah Bukti Transfer <span class="text-red-500">*</span>
                    </label>
                    <div class="relative border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-center hover:border-brand-500 dark:hover:border-brand-500 transition-colors">
                        <input type="file" name="proof_image" id="proof_image" required accept="image/*,application/pdf" onchange="previewFile()" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="space-y-2" id="uploadPlaceholder">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-brand-600 dark:text-brand-400"></i>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih file atau seret file ke sini</p>
                            <p class="text-[11px] text-slate-400">Format yang didukung: JPG, PNG, WEBP, atau PDF (Maks. 5MB)</p>
                        </div>
                        <div id="filePreview" class="hidden text-xs font-bold text-brand-600 dark:text-brand-400 flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-invoice"></i>
                            <span id="fileNameDisplay"></span>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 border-t border-slate-100 dark:border-slate-800 pt-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Donatur yang Tampil</label>
                        <input type="text" id="displayNameInput" name="display_name" value="<?= htmlspecialchars($user_name); ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none transition-colors" placeholder="Nama Anda">
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_anonymous" id="anonymousCheckbox" onchange="toggleAnonymous(this.checked)" class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500 border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Berdonasi sebagai Hamba Allah (Sembunyikan Nama)</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-4 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-base shadow-lg shadow-brand-500/20 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane text-sm"></i>
                    <span>Kirim & Konfirmasi Bukti Donasi</span>
                </button>
            </form>
        </div>

    </div>
</div>

<script>
function formatRupiah(element) {
    let value = element.value.replace(/[^0-9]/g, '');
    if (value) {
        element.value = parseInt(value, 10).toLocaleString('id-ID');
    } else {
        element.value = '';
    }
}

function setAmount(val) {
    const input = document.getElementById('inputAmount');
    input.value = val.toLocaleString('id-ID');
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
        input.classList.add('bg-slate-100', 'dark:bg-slate-800', 'opacity-60');
    } else {
        input.disabled = false;
        input.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'opacity-60');
    }
}

function previewFile() {
    const fileInput = document.getElementById('proof_image');
    const placeholder = document.getElementById('uploadPlaceholder');
    const preview = document.getElementById('filePreview');
    const fileNameDisplay = document.getElementById('fileNameDisplay');

    if (fileInput.files && fileInput.files[0]) {
        placeholder.classList.add('hidden');
        preview.classList.remove('hidden');
        fileNameDisplay.textContent = fileInput.files[0].name;
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>