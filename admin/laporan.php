<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /SahabatPeduli/auth/login.php");
    exit;
}

$page_title = "Kelola Laporan Keuangan";
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_report') {
    $title        = trim($_POST['title'] ?? '');
    $category     = trim($_POST['category'] ?? 'Laporan Keuangan');
    $report_type  = trim($_POST['report_type'] ?? 'bulanan');
    $report_month = !empty($_POST['report_month']) ? (int)$_POST['report_month'] : (int)date('n');
    $report_year  = !empty($_POST['report_year']) ? (int)$_POST['report_year'] : (int)date('Y');
    $description  = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $error_msg = "Judul dokumen laporan wajib diisi.";
    } elseif (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Pilih file dokumen PDF laporan yang akan diunggah.";
    } else {
        $fileTmpPath   = $_FILES['pdf_file']['tmp_name'];
        $fileName      = $_FILES['pdf_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'pdf') {
            $error_msg = "Format file tidak didukung! Hanya berkas PDF yang diperbolehkan.";
        } else {
            $newFileName   = 'Report_' . time() . '_' . rand(1000, 9999) . '.pdf';
            $uploadFileDir = __DIR__ . '/../uploads/reports/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO public_reports 
                        (title, description, report_type, report_month, report_year, file_path) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt.execute([
                        $title,
                        $description,
                        $report_type,
                        $report_month,
                        $report_year,
                        $newFileName
                    ]);
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
        $stmtFile = $pdo->prepare("SELECT file_path FROM public_reports WHERE id = ?");
        $stmtFile->execute([$report_id]);
        $reportData = $stmtFile->fetch();

        if ($reportData) {
            $filePathOnServer = __DIR__ . '/../uploads/reports/' . $reportData['file_path'];
            if (file_exists($filePathOnServer)) {
                @unlink($filePathOnServer);
            }

            $stmtDelete = $pdo->prepare("DELETE FROM public_reports WHERE id = ?");
            $stmtDelete->execute([$report_id]);
            $success_msg = "Dokumen laporan berhasil dihapus.";
        }
    } catch (Exception $e) {
        $error_msg = "Gagal menghapus laporan: " . $e->getMessage();
    }
}

try {
    $stmtReports = $pdo->query("SELECT * FROM public_reports ORDER BY report_year DESC, report_month DESC");
    $reports = $stmtReports->fetchAll();
} catch (Exception $e) {
    $reports = [];
    $error_msg = "Gagal memuat data laporan: " . $e->getMessage();
}

$stmtSummary = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE payment_status = 'paid') as total_in,
        (SELECT COALESCE(SUM(amount_spent), 0) FROM distributions) as total_out
");
$financialSummary = $stmtSummary->fetch();
$totalIn = $financialSummary['total_in'] ?? 0;
$totalOut = $financialSummary['total_out'] ?? 0;
$saldo = $totalIn - $totalOut;

$columnsDonations = $pdo->query("SHOW COLUMNS FROM donations")->fetchAll(PDO::FETCH_COLUMN);
$idColDonations = 'id';
if (in_array('id_donation', $columnsDonations)) {
    $idColDonations = 'id_donation';
} elseif (in_array('donation_id', $columnsDonations)) {
    $idColDonations = 'donation_id';
} elseif (!in_array('id', $columnsDonations)) {
    $idColDonations = $columnsDonations[0] ?? 'id';
}

$stmtIn = $pdo->query("SELECT {$idColDonations} AS id, amount, created_at FROM donations WHERE payment_status = 'paid' ORDER BY created_at DESC LIMIT 25");
$donations = $stmtIn->fetchAll();

$columnsDist = $pdo->query("SHOW COLUMNS FROM distributions")->fetchAll(PDO::FETCH_COLUMN);

$idColDist = 'id';
if (in_array('id_distribution', $columnsDist)) {
    $idColDist = 'id_distribution';
} elseif (in_array('distribution_id', $columnsDist)) {
    $idColDist = 'distribution_id';
} elseif (!in_array('id', $columnsDist)) {
    $idColDist = $columnsDist[0] ?? 'id';
}

$titleColDist = 'amount_spent';
if (in_array('title', $columnsDist)) {
    $titleColDist = 'title';
} elseif (in_array('description', $columnsDist)) {
    $titleColDist = 'description';
} elseif (in_array('location_name', $columnsDist)) {
    $titleColDist = 'location_name';
}

$dateColDist = 'NOW()';
if (in_array('distributed_at', $columnsDist)) {
    $dateColDist = 'distributed_at';
} elseif (in_array('created_at', $columnsDist)) {
    $dateColDist = 'created_at';
} elseif (in_array('date', $columnsDist)) {
    $dateColDist = 'date';
} elseif (in_array('created_date', $columnsDist)) {
    $dateColDist = 'created_date';
}

$stmtOut = $pdo->query("SELECT {$idColDist} AS id, {$titleColDist} AS title_col, amount_spent, {$dateColDist} AS date_created FROM distributions ORDER BY {$dateColDist} DESC LIMIT 25");
$distributions = $stmtOut->fetchAll();

$transactions = [];

foreach ($donations as $d) {
    $transactions[] = [
        'transaction_type' => 'pemasukan',
        'transaction_date' => $d['created_at'],
        'details'          => 'Penerimaan Donasi Online',
        'amount'           => $d['amount']
    ];
}

foreach ($distributions as $dis) {
    $detailText = $dis['title_col'];
    if (is_numeric($detailText)) {
        $detailText = 'Penyaluran Dana Program Sosial';
    }

    $transactions[] = [
        'transaction_type' => 'pengeluaran',
        'transaction_date' => $dis['date_created'] ?? date('Y-m-d H:i:s'),
        'details'          => $detailText,
        'amount'           => $dis['amount_spent']
    ];
}

usort($transactions, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

$filterType = $_GET['type'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

if ($filterType !== 'all') {
    $transactions = array_filter($transactions, function($item) use ($filterType) {
        return $item['transaction_type'] === $filterType;
    });
}

if (!empty($searchQuery)) {
    $transactions = array_filter($transactions, function($item) use ($searchQuery) {
        return stripos($item['details'], $searchQuery) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?> - Admin SahabatPeduli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857'
                        }
                    }
                }
            }
        }
    </script>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-100 antialiased selection:bg-brand-500 selection:text-white min-h-screen flex flex-col transition-colors duration-300">

<header class="md:hidden sticky top-0 z-50 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 via-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md">
            <i class="fa-solid fa-hand-holding-heart text-lg"></i>
        </div>
        <span class="font-black text-slate-900 dark:text-white text-base">Sahabat<span class="text-brand-600 dark:text-brand-400">Peduli</span></span>
    </div>
    <div class="flex items-center gap-2">
        <button onclick="toggleTheme()" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
            <i class="mobileThemeIcon fa-solid fa-moon"></i>
        </button>
        <button id="mobileMenuBtn" onclick="toggleSidebar()" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</header>

<div class="min-h-screen flex flex-col md:flex-row relative bg-slate-50 dark:bg-slate-950">

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

    <aside id="sidebarNav" class="fixed md:sticky top-0 left-0 z-50 w-72 md:w-64 h-screen bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 flex-shrink-0 flex flex-col justify-between border-r border-slate-200 dark:border-slate-800/80 -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
        <div>
            <div class="p-6 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 via-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                        <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                    </div>
                    <div>
                        <h1 class="font-black text-slate-900 dark:text-white text-lg tracking-tight leading-none">Sahabat<span class="text-brand-600 dark:text-brand-400">Peduli</span></h1>
                        <span class="text-[10px] text-brand-600 dark:text-brand-400 font-bold tracking-wider uppercase flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse"></span> Admin Panel
                        </span>
                    </div>
                </div>
                <button onclick="toggleSidebar()" class="md:hidden text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <nav class="p-4 space-y-1.5 text-xs font-bold">
                <a href="/SahabatPeduli/admin/index.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
                    <i class="fa-solid fa-chart-line w-4"></i> Dashboard
                </a>
                <a href="/SahabatPeduli/admin/donasi.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
                    <i class="fa-solid fa-hand-holding-dollar w-4"></i> Kelola Donasi
                </a>
                <a href="/SahabatPeduli/admin/program.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
                    <i class="fa-solid fa-folder-open w-4"></i> Kelola Program
                </a>
                <a href="/SahabatPeduli/admin/penyaluran.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
                    <i class="fa-solid fa-map-location-dot w-4"></i> Titik Penyaluran
                </a>
                <a href="/SahabatPeduli/admin/berita.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
                    <i class="fa-solid fa-newspaper w-4"></i> Kelola Berita
                </a>
                <a href="/SahabatPeduli/admin/laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/30">
                    <i class="fa-solid fa-file-invoice w-4"></i> Kelola Laporan
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-100 dark:border-slate-800/80 space-y-1 text-xs font-semibold">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="w-full flex items-center justify-between px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
                <span class="flex items-center gap-3">
                    <i id="themeIcon" class="fa-solid fa-moon w-4"></i>
                    <span id="themeText">Mode Gelap</span>
                </span>
            </button>

            <a href="/SahabatPeduli/index.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
                <i class="fa-solid fa-globe w-4"></i> Lihat Situs Utama
            </a>
            <a href="/SahabatPeduli/auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-rose-500 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-all">
                <i class="fa-solid fa-right-from-bracket w-4"></i> Keluar
            </a>
        </div>
    </aside>

    <main class="flex-1 bg-slate-50 dark:bg-slate-950 py-8 px-4 sm:px-8 lg:px-12 overflow-y-auto space-y-8 sm:space-y-10 transition-colors duration-300">

        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <span class="text-brand-600 dark:text-brand-400 font-bold text-xs uppercase tracking-wider">Transparansi Publik</span>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Kelola Laporan Keuangan</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Unggah berkas PDF audit dan kelola neraca arus kas transparansi SahabatPeduli.</p>
            </div>
            <button onclick="document.getElementById('modalUploadReport').classList.remove('hidden')" class="px-5 py-3 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-lg shadow-brand-600/30 transition-all flex items-center justify-center gap-2 self-start sm:self-auto active:scale-95">
                <i class="fa-solid fa-file-arrow-up"></i> Unggah Laporan PDF
            </button>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-2xl text-xs flex items-center gap-2 font-medium">
                <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400"></i> <?= htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="bg-red-50 dark:bg-rose-950/40 border border-red-200 dark:border-rose-800 text-red-800 dark:text-rose-300 px-4 py-3 rounded-2xl text-xs flex items-center gap-2 font-medium">
                <i class="fa-solid fa-circle-exclamation text-red-600 dark:text-rose-400"></i> <?= htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-emerald-600 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-arrow-down-left"></i>
                    <span>Total Penerimaan Donasi</span>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Rp <?= number_format($totalIn, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Total akumulasi dari seluruh donasi masuk.</p>
            </div>

            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-rose-500 dark:text-rose-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-arrow-up-right"></i>
                    <span>Total Penyaluran Program</span>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Rp <?= number_format($totalOut, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Telah disalurkan ke berbagai program bantuan.</p>
            </div>

            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-brand-600 dark:text-brand-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-vault"></i>
                    <span>Saldo Cadangan Penyaluran</span>
                </div>
                <h3 class="text-2xl font-black text-brand-600 dark:text-brand-400">Rp <?= number_format($saldo, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Siap dialokasikan untuk program mendatang.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 sm:p-8 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-6">
                <div>
                    <h2 class="font-extrabold text-slate-900 dark:text-white text-xl">Rincian Arus Kas Real-Time</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Transparansi riwayat transaksi masuk dan keluar secara langsung.</p>
                </div>

                <form method="GET" action="" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex gap-2">
                        <a href="?type=all" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $filterType === 'all' ? 'bg-brand-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">Semua</a>
                        <a href="?type=pemasukan" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $filterType === 'pemasukan' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">Pemasukan</a>
                        <a href="?type=pengeluaran" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $filterType === 'pengeluaran' ? 'bg-rose-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' ?>">Pengeluaran</a>
                    </div>
                    
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Cari transaksi..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($filterType); ?>">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Jenis Laporan</th>
                            <th class="py-3 px-4">Rincian Transaksi</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs sm:text-sm">
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $trans): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-3.5 px-4 font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        <?= date('d M Y, H:i', strtotime($trans['transaction_date'])); ?>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <?php if ($trans['transaction_type'] === 'pemasukan'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 text-[11px] font-bold">
                                                <i class="fa-solid fa-arrow-down-left"></i> Pemasukan
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 text-[11px] font-bold">
                                                <i class="fa-solid fa-arrow-up-right"></i> Pengeluaran
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                        <?= htmlspecialchars($trans['details']); ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-black whitespace-nowrap <?= $trans['transaction_type'] === 'pemasukan' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'; ?>">
                                        <?= $trans['transaction_type'] === 'pemasukan' ? '+' : '-'; ?> Rp <?= number_format($trans['amount'], 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-xs text-slate-400 dark:text-slate-500">
                                    Tidak ada data transaksi yang ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 sm:p-10 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                <div>
                    <h2 class="font-extrabold text-slate-900 dark:text-white text-xl">Arsip Dokumen Laporan (PDF)</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Kelola dan unduh berkas laporan bulanan serta tahunan resmi SahabatPeduli.</p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 text-xs font-bold">
                        <i class="fa-solid fa-shield-check"></i> Audit WTP
                    </span>
                </div>
            </div>

            <?php if (!empty($reports)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($reports as $item): ?>
                        <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 hover:bg-white dark:hover:bg-slate-800 hover:border-brand-300 dark:hover:border-brand-500 hover:shadow-md transition-all flex flex-col justify-between space-y-4 group">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs text-slate-400 dark:text-slate-400 font-semibold">
                                    <span class="bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-brand-300 px-2.5 py-1 rounded-lg font-bold">
                                        <?= strtoupper($item['report_type'] ?? 'Bulanan'); ?>
                                    </span>
                                    <span><?= date("F", mktime(0, 0, 0, $item['report_month'] ?? 1, 10)) . ' ' . ($item['report_year'] ?? date('Y')); ?></span>
                                </div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm line-clamp-2">
                                    <?= htmlspecialchars($item['title']); ?>
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                    <?= htmlspecialchars($item['description']); ?>
                                </p>
                            </div>

                            <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <a href="/SahabatPeduli/uploads/reports/<?= htmlspecialchars($item['file_path']); ?>" download class="flex-1 py-2 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-brand-600 dark:hover:bg-brand-500 hover:text-white dark:hover:text-white hover:border-brand-600 dark:hover:border-brand-500 font-bold text-xs text-slate-700 dark:text-slate-200 text-center transition-all flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-file-pdf text-red-500 group-hover:text-white"></i>
                                    <span>Unduh</span>
                                </a>
                                <form method="POST" action="" onsubmit="return confirm('Yakin ingin menghapus dokumen laporan ini?');" class="inline">
                                    <input type="hidden" name="action" value="delete_report">
                                    <input type="hidden" name="report_id" value="<?= $item['id']; ?>">
                                    <button type="submit" class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-rose-600 dark:text-rose-400 text-xs font-bold transition-all active:scale-95" title="Hapus Laporan">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 space-y-3">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum ada dokumen laporan publikasi</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Gunakan tombol unggah di atas untuk menambahkan dokumen laporan baru.</p>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<div id="modalUploadReport" class="fixed inset-0 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-100 dark:border-slate-800">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-4">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Unggah Dokumen Laporan</h3>
                <p class="text-xs text-slate-400 dark:text-slate-500">Berkas akan dipublikasikan ke halaman transparansi.</p>
            </div>
            <button onclick="document.getElementById('modalUploadReport').classList.add('hidden')" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-4 text-xs font-semibold">
            <input type="hidden" name="action" value="upload_report">

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1">Judul Dokumen</label>
                <input type="text" name="title" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition-all" placeholder="Contoh: Laporan Audited Keuangan Tahun 2026">
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1">Tipe Laporan</label>
                <select name="report_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition-all">
                    <option value="bulanan">Bulanan</option>
                    <option value="tahunan">Tahunan</option>
                    <option value="audit">Audit</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Bulan Periode (1 - 12)</label>
                    <select name="report_month" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition-all">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m; ?>" <?= $m == date('n') ? 'selected' : ''; ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 10)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-700 dark:text-slate-300 mb-1">Tahun Periode</label>
                    <input type="number" name="report_year" value="<?= date('Y'); ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition-all">
                </div>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1">Keterangan / Deskripsi Singkat</label>
                <textarea name="description" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 focus:outline-none transition-all" placeholder="Ringkasan singkat mengenai laporan..."></textarea>
            </div>

            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1">Pilih File Berkas PDF</label>
                <input type="file" name="pdf_file" accept=".pdf" required class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 transition-all">
                <span class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 block">*Hanya menerima ekstensi file .pdf</span>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('modalUploadReport').classList.add('hidden')" class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold transition-all">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold transition-all shadow-md shadow-brand-500/20 active:scale-95">Unggah Dokumen</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarNav');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

function updateThemeUI() {
    const isDark = document.documentElement.classList.contains('dark');
    const icon = document.getElementById('themeIcon');
    const text = document.getElementById('themeText');
    const mobileIcons = document.querySelectorAll('.mobileThemeIcon');
    
    if (isDark) {
        if (icon) icon.className = 'fa-solid fa-sun w-4 text-amber-400';
        if (text) text.innerText = 'Mode Terang';
        mobileIcons.forEach(i => i.className = 'mobileThemeIcon fa-solid fa-sun text-amber-400');
    } else {
        if (icon) icon.className = 'fa-solid fa-moon w-4 text-slate-400';
        if (text) text.innerText = 'Mode Gelap';
        mobileIcons.forEach(i => i.className = 'mobileThemeIcon fa-solid fa-moon');
    }
}

function toggleTheme() {
    if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    }
    updateThemeUI();
}

document.addEventListener('DOMContentLoaded', updateThemeUI);
</script>

</body>
</html>