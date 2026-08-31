<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /PeduliUmat/auth/login.php");
    exit;
}

$page_title = "Kelola Titik Penyaluran";
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_distribution') {
    $location_name = trim($_POST['location_name'] ?? '');
    $category = $_POST['category'] ?? 'peduli_ekonomi';
    $amount_spent = floatval($_POST['amount_spent'] ?? 0);
    $beneficiaries_count = intval($_POST['beneficiaries_count'] ?? 0);
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($location_name) || empty($latitude) || empty($longitude)) {
        $error_msg = "Nama lokasi, latitude, dan longitude wajib diisi.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO distributions (location_name, category, amount_spent, beneficiaries_count, latitude, longitude, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$location_name, $category, $amount_spent, $beneficiaries_count, $latitude, $longitude, $description]);
            $success_msg = "Titik penyaluran baru berhasil ditambahkan ke sistem peta.";
        } catch (Exception $e) {
            $error_msg = "Gagal menambah data penyaluran: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_distribution') {
    $dist_id = intval($_POST['dist_id'] ?? 0);

    try {
        $stmt = $pdo->prepare("DELETE FROM distributions WHERE id = ?");
        $stmt->execute([$dist_id]);
        $success_msg = "Titik penyaluran berhasil dihapus.";
    } catch (Exception $e) {
        $error_msg = "Gagal menghapus data penyaluran: " . $e->getMessage();
    }
}

$stmtDist = $pdo->query("SELECT * FROM distributions ORDER BY created_at DESC");
$distributions = $stmtDist->fetchAll();
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
            <a href="/PeduliUmat/admin/program.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-folder-open w-4"></i> Kelola Program
            </a>
            <a href="/PeduliUmat/admin/penyaluran.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-600 text-white font-bold">
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
                <h1 class="text-2xl font-black text-slate-900">Kelola Titik Penyaluran</h1>
                <p class="text-xs text-slate-500 mt-1">Input lokasi geografis dan detail laporan penyaluran dana donasi ke masyarakat.</p>
            </div>
            <button onclick="document.getElementById('modalCreateDist').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-500/20 transition-all flex items-center justify-center gap-2 self-start sm:self-auto">
                <i class="fa-solid fa-plus"></i> Tambah Titik Penyaluran
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
                            <th class="py-3.5 px-4">Nama Lokasi</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Dana Disalurkan</th>
                            <th class="py-3.5 px-4">Penerima Manfaat</th>
                            <th class="py-3.5 px-4">Koordinat (Lat, Long)</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php if (!empty($distributions)): ?>
                            <?php foreach ($distributions as $dist): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-4 font-bold text-slate-900">
                                        <?= htmlspecialchars($dist['location_name']); ?>
                                        <span class="block text-[11px] text-slate-400 font-normal line-clamp-1 mt-0.5"><?= htmlspecialchars($dist['description']); ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-brand-600 uppercase text-[11px]">
                                        <?= str_replace('peduli_', '', $dist['category']); ?>
                                    </td>
                                    <td class="py-4 px-4 font-black text-slate-900">
                                        Rp <?= number_format($dist['amount_spent'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-4 px-4 font-semibold text-slate-700">
                                        <i class="fa-solid fa-users text-slate-400 mr-1"></i> <?= number_format($dist['beneficiaries_count'], 0, ',', '.'); ?> Orang
                                    </td>
                                    <td class="py-4 px-4 font-mono text-slate-500 text-[11px]">
                                        <?= htmlspecialchars($dist['latitude']); ?>, <?= htmlspecialchars($dist['longitude']); ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus titik penyaluran ini?');" class="inline-block">
                                            <input type="hidden" name="action" value="delete_distribution">
                                            <input type="hidden" name="dist_id" value="<?= $dist['id']; ?>">
                                            <button type="submit" class="p-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold transition-all" title="Hapus">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-10 text-slate-400">Belum ada titik penyaluran yang didaftarkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<div id="modalCreateDist" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
            <h3 class="text-lg font-black text-slate-900">Tambah Titik Penyaluran Peta</h3>
            <button onclick="document.getElementById('modalCreateDist').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="" class="space-y-4 text-xs font-semibold">
            <input type="hidden" name="action" value="create_distribution">

            <div>
                <label class="block text-slate-700 mb-1">Nama Lokasi / Penerima</label>
                <input type="text" name="location_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Contoh: Panti Asuhan Harapan, Banjarmasin Barat">
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Kategori Bantuan</label>
                <select name="category" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <option value="peduli_pendidikan">Peduli Pendidikan</option>
                    <option value="peduli_kesehatan">Peduli Kesehatan</option>
                    <option value="peduli_bencana">Peduli Bencana</option>
                    <option value="peduli_ekonomi">Peduli Ekonomi & Sosial</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-700 mb-1">Nominal Disalurkan (Rp)</label>
                    <input type="number" name="amount_spent" required min="0" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="5000000">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">Jumlah Penerima (Jiwa)</label>
                    <input type="number" name="beneficiaries_count" required min="1" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="50">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-700 mb-1">Latitude</label>
                    <input type="text" name="latitude" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="-3.3194">
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">Longitude</label>
                    <input type="text" name="longitude" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="114.5908">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Deskripsi Kegiatan Penyaluran</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Penyerahan bantuan sembako dan perbaikan sarana belajar..."></textarea>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalCreateDist').classList.add('hidden')" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition-all">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition-all shadow-md shadow-brand-500/20">Simpan Titik</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>