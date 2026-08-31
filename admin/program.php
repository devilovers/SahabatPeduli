<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /PeduliUmat/auth/login.php");
    exit;
}

$page_title = "Kelola Program Peduli";
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_campaign') {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'peduli_ekonomi';
    $target_amount = floatval($_POST['target_amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('+30 days'));
    
    $image_name = 'default-campaign.jpg';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = __DIR__ . '/../uploads/campaigns/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $image_name = $newFileName;
            }
        }
    }

    if (empty($title) || $target_amount <= 0) {
        $error_msg = "Judul program dan target nominal wajib diisi dengan benar.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO campaigns (title, category, target_amount, collected_amount, description, image, end_date, status) VALUES (?, ?, ?, 0, ?, ?, ?, 'active')");
            $stmt->execute([$title, $category, $target_amount, $description, $image_name, $end_date]);
            $success_msg = "Program donasi baru berhasil ditambahkan.";
        } catch (Exception $e) {
            $error_msg = "Gagal menambah program: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $campaign_id = intval($_POST['campaign_id'] ?? 0);
    $new_status = $_POST['status'] ?? 'active';

    try {
        $stmt = $pdo->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $campaign_id]);
        $success_msg = "Status program berhasil diperbarui.";
    } catch (Exception $e) {
        $error_msg = "Gagal memperbarui status: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_campaign') {
    $campaign_id = intval($_POST['campaign_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $success_msg = "Program berhasil dihapus.";
    } catch (Exception $e) {
        $error_msg = "Gagal menghapus program: " . $e->getMessage();
    }
}

$stmtCampaigns = $pdo->query("SELECT * FROM campaigns ORDER BY created_at DESC");
$campaigns = $stmtCampaigns->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?> - Admin PeduliUmat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5', 100: '#d1fae5', 500: '#10b981', 600: '#059669', 700: '#047857'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 font-sans text-slate-800 antialiased">

<div class="min-h-screen flex flex-col md:flex-row">

    <aside class="w-full md:w-64 bg-slate-900 text-slate-300 flex-shrink-0">
        <div class="p-6 border-b border-slate-800 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center font-black">P</div>
            <div>
                <h1 class="font-extrabold text-white text-base">PeduliUmat</h1>
                <span class="text-[10px] text-brand-500 font-bold uppercase tracking-wider">Panel Admin</span>
            </div>
        </div>

        <nav class="p-4 space-y-1 text-xs font-semibold">
            <a href="/PeduliUmat/admin/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-chart-line w-4"></i> Dashboard
            </a>
            <a href="/PeduliUmat/admin/donasi.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-hand-holding-dollar w-4"></i> Kelola Donasi
            </a>
            <a href="/PeduliUmat/admin/program.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-600 text-white font-bold">
                <i class="fa-solid fa-folder-open w-4"></i> Kelola Program
            </a>
            <a href="/PeduliUmat/admin/penyaluran.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-map-location-dot w-4"></i> Titik Penyaluran
            </a>
            <a href="/PeduliUmat/admin/berita.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-newspaper w-4"></i> Kelola Berita
            </a>
            <a href="/PeduliUmat/admin/laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-file-invoice w-4"></i> Kelola Laporan
            </a>

            <div class="pt-6 mt-6 border-t border-slate-800 space-y-1">
                <a href="/PeduliUmat/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                    <i class="fa-solid fa-globe w-4"></i> Lihat Situs Utama
                </a>
                <a href="/PeduliUmat/auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-rose-400 hover:bg-rose-900/30 hover:text-rose-300 transition-all">
                    <i class="fa-solid fa-right-from-bracket w-4"></i> Keluar
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 p-6 lg:p-10 space-y-8 overflow-y-auto">

        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Kelola Program Peduli</h1>
                <p class="text-xs text-slate-500 mt-1">Buat program kebaikan baru dan pantau target donasi yang terkumpul.</p>
            </div>
            <button onclick="document.getElementById('modalCreate').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-500/20 transition-all flex items-center justify-center gap-2 self-start sm:self-auto">
                <i class="fa-solid fa-plus"></i> Tambah Program Baru
            </button>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-2xl text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Program</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Terkumpul / Target</th>
                            <th class="py-3.5 px-4">Batas Waktu</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php if (!empty($campaigns)): ?>
                            <?php foreach ($campaigns as $camp): 
                                $target = floatval($camp['target_amount']);
                                $collected = floatval($camp['collected_amount']);
                                $percent = ($target > 0) ? min(100, round(($collected / $target) * 100)) : 0;
                            ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <img src="/PeduliUmat/uploads/campaigns/<?= htmlspecialchars($camp['image']); ?>" 
                                                 alt="" 
                                                 class="w-12 h-12 rounded-xl object-cover border border-slate-200"
                                                 onerror="this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=150&auto=format&fit=crop';">
                                            <div>
                                                <h4 class="font-bold text-slate-900 line-clamp-1"><?= htmlspecialchars($camp['title']); ?></h4>
                                                <p class="text-[11px] text-slate-400 line-clamp-1 mt-0.5"><?= htmlspecialchars($camp['description']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-brand-600 uppercase text-[11px]">
                                        <?= str_replace('peduli_', '', $camp['category']); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="font-bold text-slate-900">Rp <?= number_format($collected, 0, ',', '.'); ?></span>
                                        <span class="text-slate-400 text-[11px] block">dari Rp <?= number_format($target, 0, ',', '.'); ?> (<?= $percent; ?>%)</span>
                                    </td>
                                    <td class="py-4 px-4 text-slate-500 font-semibold"><?= date('d M Y', strtotime($camp['end_date'])); ?></td>
                                    <td class="py-4 px-4">
                                        <?php if ($camp['status'] === 'active'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">AKTIF</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 font-bold text-[10px]">SELESAI</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="inline-flex items-center gap-2">
                                            <form method="POST" action="">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="campaign_id" value="<?= $camp['id']; ?>">
                                                <input type="hidden" name="status" value="<?= $camp['status'] === 'active' ? 'completed' : 'active'; ?>">
                                                <button type="submit" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-all" title="Ubah Status">
                                                    <i class="fa-solid fa-rotate"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus program ini?');">
                                                <input type="hidden" name="action" value="delete_campaign">
                                                <input type="hidden" name="campaign_id" value="<?= $camp['id']; ?>">
                                                <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold transition-all" title="Hapus">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-slate-400">Belum ada program peduli yang dibuat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<div id="modalCreate" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
            <h3 class="text-lg font-black text-slate-900">Buat Program Peduli Baru</h3>
            <button onclick="document.getElementById('modalCreate').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold">
            <input type="hidden" name="action" value="create_campaign">

            <div>
                <label class="block text-slate-700 mb-1">Judul Program</label>
                <input type="text" name="title" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Contoh: Bantuan Sembako Yatim Banjarmasin">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Kategori Program</label>
                <select name="category" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="peduli_pendidikan">Peduli Pendidikan</option>
                    <option value="peduli_kesehatan">Peduli Kesehatan</option>
                    <option value="peduli_bencana">Peduli Bencana</option>
                    <option value="peduli_ekonomi">Peduli Ekonomi & Sosial</option>
                </select>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Target Nominal (Rp)</label>
                <input type="number" name="target_amount" required min="100000" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="10000000">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Batas Waktu Donasi</label>
                <input type="date" name="end_date" required value="<?= date('Y-m-d', strtotime('+30 days')); ?>" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Gambar Sampul/Thumbnail</label>
                <input type="file" name="image" accept="image/*" class="w-full px-4 py-2 rounded-xl border border-slate-200 text-slate-500">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Deskripsi Singkat Program</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Penjelasan mengenai target dan tujuan penggalangan dana..."></textarea>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalCreate').classList.add('hidden')" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition-all">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition-all shadow-md shadow-brand-500/20">Simpan Program</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>