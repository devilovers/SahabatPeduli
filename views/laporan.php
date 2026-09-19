<?php
$page_title = "Publikasi Laporan & Audit";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$stmtReports = $pdo->query("SELECT * FROM public_reports ORDER BY report_year DESC, report_month DESC");
$reports = $stmtReports->fetchAll();

$stmtSummary = $pdo->query("
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE payment_status = 'paid') as total_in,
        (SELECT COALESCE(SUM(amount_spent), 0) FROM distributions) as total_out
");
$financialSummary = $stmtSummary->fetch();
$totalIn = $financialSummary['total_in'] ?? 0;
$totalOut = $financialSummary['total_out'] ?? 0;
$saldo = $totalIn - $totalOut;

$stmtIn = $pdo->query("SELECT id, amount, created_at FROM donations WHERE payment_status = 'paid' ORDER BY created_at DESC LIMIT 25");
$donations = $stmtIn->fetchAll();

// Pengecekan kolom dinamis pada tabel distributions untuk mencegah error SQL jika nama kolom berbeda
$columnsDist = $pdo->query("SHOW COLUMNS FROM distributions")->fetchAll(PDO::FETCH_COLUMN);

// Deteksi nama kolom Primary Key / ID
$idColDist = 'id';
if (in_array('id_distribution', $columnsDist)) {
    $idColDist = 'id_distribution';
} elseif (!in_array('id', $columnsDist)) {
    // Fallback jika tidak ada kolom id standar
    $idColDist = $columnsDist[0] ?? 'id';
}

// Deteksi kolom judul/keterangan penyaluran
$titleColDist = 'amount_spent'; // Default aman
if (in_array('title', $columnsDist)) {
    $titleColDist = 'title';
} elseif (in_array('description', $columnsDist)) {
    $titleColDist = 'description';
} elseif (in_array('location_name', $columnsDist)) {
    $titleColDist = 'location_name';
}

// Deteksi kolom tanggal penyaluran
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
    // Jika title_col berisi angka (misal amount_spent), beri teks default agar informatif
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

<div class="bg-slate-50 dark:bg-slate-900 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Akuntabilitas & Audit</span>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">Publikasi Laporan Keuangan</h1>
            <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base">
                Sebagai lembaga penyalur donasi yang amanah, kami menyajikan rincian neraca keuangan dan arus kas secara terbuka, jujur, dan terperinci.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-emerald-600 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-arrow-down-left"></i>
                    <span>Total Penerimaan Donasi</span>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Rp <?= number_format($totalIn, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Total akumulasi dari seluruh donasi masuk.</p>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-rose-500 dark:text-rose-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-arrow-up-right"></i>
                    <span>Total Penyaluran Program</span>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Rp <?= number_format($totalOut, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Telah disalurkan ke berbagai program bantuan.</p>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-brand-600 dark:text-brand-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-vault"></i>
                    <span>Saldo Cadangan Penyaluran</span>
                </div>
                <h3 class="text-2xl font-black text-brand-600 dark:text-brand-400">Rp <?= number_format($saldo, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Siap dialokasikan untuk program mendatang.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-6 sm:p-8 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl space-y-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-700 pb-6">
                <div>
                    <h2 class="font-extrabold text-slate-900 dark:text-white text-xl">Rincian Arus Kas Real-Time</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Transparansi riwayat transaksi masuk dan keluar secara langsung.</p>
                </div>

                <form method="GET" action="" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex gap-2">
                        <a href="?type=all" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $filterType === 'all' ? 'bg-brand-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">Semua</a>
                        <a href="?type=pemasukan" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $filterType === 'pemasukan' ? 'bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">Pemasukan</a>
                        <a href="?type=pengeluaran" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all <?= $filterType === 'pengeluaran' ? 'bg-rose-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">Pengeluaran</a>
                    </div>
                    
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="q" value="<?= htmlspecialchars($searchQuery); ?>" placeholder="Cari transaksi..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($filterType); ?>">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700 text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Jenis Laporan</th>
                            <th class="py-3 px-4">Rincian Transaksi</th>
                            <th class="py-3 px-4 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-xs sm:text-sm">
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $trans): ?>
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
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

        <div class="bg-white dark:bg-slate-800 p-6 sm:p-10 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-slate-100 dark:border-slate-700 pb-4">
                <div>
                    <h2 class="font-extrabold text-slate-900 dark:text-white text-xl">Arsip Dokumen Laporan (PDF)</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Unduh berkas laporan bulanan dan tahunan resmi SahabatPeduli.</p>
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
                        <div class="p-5 rounded-2xl border border-slate-100 dark:border-slate-700/60 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-white dark:hover:bg-slate-800 hover:border-brand-300 dark:hover:border-brand-500 hover:shadow-md transition-all flex flex-col justify-between space-y-4 group">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-xs text-slate-400 dark:text-slate-400 font-semibold">
                                    <span class="bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-brand-300 px-2.5 py-1 rounded-lg font-bold">
                                        <?= strtoupper($item['report_type'] ?? 'Bulanan'); ?>
                                    </span>
                                    <span><?= date("F", mktime(0, 0, 0, $item['report_month'], 10)) . ' ' . $item['report_year']; ?></span>
                                </div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm line-clamp-2">
                                    <?= htmlspecialchars($item['title']); ?>
                                </h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                    <?= htmlspecialchars($item['description']); ?>
                                </p>
                            </div>

                            <a href="/SahabatPeduli/uploads/reports/<?= htmlspecialchars($item['file_path']); ?>" download class="w-full py-2.5 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-brand-600 dark:hover:bg-brand-500 hover:text-white dark:hover:text-white hover:border-brand-600 dark:hover:border-brand-500 font-bold text-xs text-slate-700 dark:text-slate-200 text-center transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-file-pdf text-red-500 group-hover:text-white"></i>
                                <span>Unduh Dokumen Laporan</span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12 space-y-3">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto text-2xl">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Belum ada dokumen laporan publikasi</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Dokumen audit dan neraca bulanan akan diperbarui secara periodik oleh admin.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>