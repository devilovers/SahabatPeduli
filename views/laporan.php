<?php
$page_title = "Publikasi Laporan & Audit";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$stmtReports = $pdo->query("SELECT * FROM public_reports ORDER BY report_year DESC, report_month DESC");
$reports = $stmtReports->fetchAll();

$stmtSummary = $pdo->query("
    SELECT 
        (SELECT SUM(amount) FROM donations WHERE payment_status = 'paid') as total_in,
        (SELECT SUM(amount_spent) FROM distributions) as total_out
");
$financialSummary = $stmtSummary->fetch();
$totalIn = $financialSummary['total_in'] ?? 0;
$totalOut = $financialSummary['total_out'] ?? 0;
$saldo = $totalIn - $totalOut;
?>

<div class="bg-slate-50 dark:bg-slate-900 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Akuntabilitas & Audit</span>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">Publikasi Laporan Keuangan</h1>
            <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base">
                Sebagai lembaga penyalur donasi yang amanah, kami menyajikan rincian neraca keuangan dan laporan hasil audit publikasi secara berkala.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-emerald-600 dark:text-emerald-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-arrow-down-left"></i>
                    <span>Total Penerimaan Donasi</span>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Rp <?= number_format($totalIn, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Total akumulasi dari seluruh kategori donasi.</p>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm space-y-2">
                <div class="flex items-center gap-3 text-rose-500 dark:text-rose-400 text-xs font-bold uppercase tracking-wider">
                    <i class="fa-solid fa-arrow-up-right"></i>
                    <span>Total Penyaluran Program</span>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white">Rp <?= number_format($totalOut, 0, ',', '.'); ?></h3>
                <p class="text-[11px] text-slate-400 dark:text-slate-500">Telah disalurkan ke berbagai titik bantuan.</p>
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

        <div class="bg-white dark:bg-slate-800 p-6 sm:p-10 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-slate-100 dark:border-slate-700 pb-4">
                <div>
                    <h2 class="font-extrabold text-slate-900 dark:text-white text-xl">Arsip Dokumen Laporan (PDF)</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Unduh berkas laporan bulanan dan tahunan resmi PeduliUmat.</p>
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

                            <a href="/PeduliUmat/uploads/reports/<?= htmlspecialchars($item['file_path']); ?>" download class="w-full py-2.5 rounded-xl bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 hover:bg-brand-600 dark:hover:bg-brand-500 hover:text-white dark:hover:text-white hover:border-brand-600 dark:hover:border-brand-500 font-bold text-xs text-slate-700 dark:text-slate-200 text-center transition-all flex items-center justify-center gap-2">
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