<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /PeduliUmat/auth/login.php");
    exit;
}

$page_title = "Kelola Laporan Keuangan";
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_report') {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'keuangan';
    $period = trim($_POST['period'] ?? '');

    if (empty($title) || empty($period)) {
        $error_msg = "Judul dokumen dan periode laporan wajib diisi.";
    } elseif (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Pilih file dokumen PDF laporan yang akan diunggah.";
    } else {
        $fileTmpPath = $_FILES['pdf_file']['tmp_name'];
        $fileName = $_FILES['pdf_file']['name'];
        $fileSizeByte = $_FILES['pdf_file']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'pdf') {
            $error_msg = "Format file tidak didukung! Hanya file berkas PDF yang diperbolehkan.";
        } else {
            if ($fileSizeByte >= 1048576) {
                $file_size = number_format($fileSizeByte / 1048576, 2) . ' MB';
            } else {
                $file_size = number_format($fileSizeByte / 1024, 2) . ' KB';
            }

            $newFileName = 'Report_' . time() . '_' . preg_replace('/[^A-Za-z0-9]/', '', $period) . '.pdf';
            $uploadFileDir = __DIR__ . '/../uploads/reports/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO reports (title, category, period, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $category, $period, $newFileName, $file_size]);
                    $success_msg = "Dokumen laporan PDF berhasil diunggah.";
                } catch (Exception $e) {
                    $error_msg = "Gagal menyimpan data laporan: " . $e->getMessage();
                }
            } else {
                $error_msg = "Terjadi kesalahan saat mengunggah berkas ke server.";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_report') {
    $report_id = intval($_POST['report_id'] ?? 0);

    try {
        $stmtFile = $pdo->prepare("SELECT file_path FROM reports WHERE id = ?");
        $stmtFile->execute([$report_id]);
        $reportData = $stmtFile->fetch();

        if ($reportData) {
            $filePathOnServer = __DIR__ . '/../uploads/reports/' . $reportData['file_path'];
            if (file_exists($filePathOnServer)) {
                @unlink($filePathOnServer);
            }

            $stmtDelete = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmtDelete->execute([$report_id]);
            $success_msg = "Dokumen laporan berhasil dihapus.";
        }
    } catch (Exception $e) {
        $error_msg = "Gagal menghapus laporan: " . $e->getMessage();
    }
}

$stmtReports = $pdo->query("SELECT * FROM reports ORDER BY created_at DESC");
$reports = $stmtReports->fetchAll();
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
            <a href="/PeduliUmat/admin/penyaluran.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-map-location-dot w-4"></i> Titik Penyaluran
            </a>
            <a href="/PeduliUmat/admin/berita.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                <i class="fa-solid fa-newspaper w-4"></i> Kelola Berita
            </a>
            <a href="/PeduliUmat/admin/laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-600 text-white font-bold">
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
                <h1 class="text-2xl font-black text-slate-900">Kelola Laporan Keuangan</h1>
                <p class="text-xs text-slate-500 mt-1">Unggah berkas PDF audit dan laporan keuangan untuk transparansi akuntabilitas publik.</p>
            </div>
            <button onclick="document.getElementById('modalUploadReport').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-500/20 transition-all flex items-center justify-center gap-2 self-start sm:self-auto">
                <i class="fa-solid fa-file-arrow-up"></i> Unggah Laporan PDF
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
                            <th class="py-3.5 px-4">Nama Dokumen</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Periode</th>
                            <th class="py-3.5 px-4">Ukuran File</th>
                            <th class="py-3.5 px-4">Tanggal Unggah</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $report): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                                                <i class="fa-solid fa-file-pdf text-base"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-900"><?= htmlspecialchars($report['title']); ?></h4>
                                                <span class="text-[11px] text-slate-400 font-mono"><?= htmlspecialchars($report['file_path']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-brand-600 uppercase text-[11px]">
                                        <?= htmlspecialchars($report['category']); ?>
                                    </td>
                                    <td class="py-4 px-4 font-semibold text-slate-700">
                                        <?= htmlspecialchars($report['period']); ?>
                                    </td>
                                    <td class="py-4 px-4 text-slate-500 font-mono">
                                        <?= htmlspecialchars($report['file_size'] ?? '-'); ?>
                                    </td>
                                    <td class="py-4 px-4 text-slate-400">
                                        <?= date('d M Y H:i', strtotime($report['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <div class="inline-flex items-center gap-2">
                                            <a href="/PeduliUmat/uploads/reports/<?= htmlspecialchars($report['file_path']); ?>" target="_blank" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-all" title="Unduh/Lihat PDF">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus dokumen laporan ini?');">
                                                <input type="hidden" name="action" value="delete_report">
                                                <input type="hidden" name="report_id" value="<?= $report['id']; ?>">
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
                                <td colspan="6" class="text-center py-10 text-slate-400">Belum ada dokumen laporan yang diunggah.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<div id="modalUploadReport" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-100 pb-4">
            <h3 class="text-lg font-black text-slate-900">Unggah Dokumen Laporan</h3>
            <button onclick="document.getElementById('modalUploadReport').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold">
            <input type="hidden" name="action" value="upload_report">

            <div>
                <label class="block text-slate-700 mb-1">Judul Dokumen</label>
                <input type="text" name="title" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Contoh: Laporan Audited Keuangan Tahun 2025">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-700 mb-1">Kategori Dokumen</label>
                    <select name="category" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <option value="keuangan">Laporan Keuangan</option>
                        <option value="audit">Laporan Hasil Audit</option>
                        <option value="tahunan">Laporan Tahunan (Annual Report)</option>
                        <option value="penyaluran">Rekap Penyaluran ZISWAF</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 mb-1">Periode / Tahun</label>
                    <input type="text" name="period" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Contoh: Q4 2025 / T.A 2025">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 mb-1">Pilih File Berkas PDF</label>
                <input type="file" name="pdf_file" accept=".pdf" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-slate-500">
                <span class="text-[10px] text-slate-400 mt-1 block">*Hanya menerima ekstensi file .pdf</span>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalUploadReport').classList.add('hidden')" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition-all">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition-all shadow-md shadow-brand-500/20">Unggah Dokumen</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>