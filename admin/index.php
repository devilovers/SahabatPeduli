<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /PeduliUmat/auth/login.php");
    exit;
}

$page_title = "Dashboard Admin";

$totalDonations = $pdo->query("SELECT SUM(amount) FROM donations WHERE payment_status = 'paid'")->fetchColumn() ?: 0;
$totalDistributions = $pdo->query("SELECT SUM(amount_spent) FROM distributions")->fetchColumn() ?: 0;
$activeCampaigns = $pdo->query("SELECT COUNT(id) FROM campaigns WHERE status = 'active'")->fetchColumn() ?: 0;
$totalDonors = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM donations WHERE payment_status = 'paid'")->fetchColumn() ?: 0;

$stmtRecent = $pdo->query("
    SELECT d.*, u.name as user_name 
    FROM donations d 
    LEFT JOIN users u ON d.user_id = u.id 
    ORDER BY d.created_at DESC LIMIT 5
");
$recentDonations = $stmtRecent->fetchAll();
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
            <a href="/PeduliUmat/admin/index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-600 text-white font-bold">
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
        
        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Selamat Datang, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>!</h1>
                <p class="text-xs text-slate-500 mt-0.5">Ringkasan statistik transaksi dan aktivitas sistem PeduliUmat saat ini.</p>
            </div>
            <span class="text-xs font-bold bg-brand-50 text-brand-700 px-4 py-2 rounded-xl border border-brand-200 self-start sm:self-auto">
                <i class="fa-solid fa-circle-check mr-1.5 text-brand-600"></i> Sistem Online
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Donasi Masuk</span>
                <h2 class="text-2xl font-black text-slate-900">Rp <?= number_format($totalDonations, 0, ',', '.'); ?></h2>
                <p class="text-[11px] text-emerald-600 font-semibold"><i class="fa-solid fa-check-circle"></i> Pembayaran Terverifikasi</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Disalurkan</span>
                <h2 class="text-2xl font-black text-slate-900">Rp <?= number_format($totalDistributions, 0, ',', '.'); ?></h2>
                <p class="text-[11px] text-teal-600 font-semibold"><i class="fa-solid fa-map-marker-alt"></i> Terpeta di Sistem</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Program Aktif</span>
                <h2 class="text-2xl font-black text-slate-900"><?= number_format($activeCampaigns, 0, ',', '.'); ?> Program</h2>
                <p class="text-[11px] text-brand-600 font-semibold"><i class="fa-solid fa-clock"></i> Mengumpulkan Dana</p>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jumlah Donatur</span>
                <h2 class="text-2xl font-black text-slate-900"><?= number_format($totalDonors, 0, ',', '.'); ?> Pengguna</h2>
                <p class="text-[11px] text-indigo-600 font-semibold"><i class="fa-solid fa-users"></i> Donatur Terdaftar</p>
            </div>
        </div>

        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-6">
            <div class="flex justify-between items-center border-b border-slate-100 pb-4">
                <h3 class="font-extrabold text-slate-900 text-lg">Transaksi Donasi Terbaru</h3>
                <a href="/PeduliUmat/admin/donasi.php" class="text-xs font-bold text-brand-600 hover:text-brand-700">Lihat Semua Donasi &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3 px-4">Order ID</th>
                            <th class="py-3 px-4">Donatur</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">Nominal</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php if (!empty($recentDonations)): ?>
                            <?php foreach ($recentDonations as $don): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-4 font-mono font-bold text-slate-900"><?= $don['order_id']; ?></td>
                                    <td class="py-4 px-4 font-semibold text-slate-800">
                                        <?= htmlspecialchars($don['display_name'] ?: ($don['user_name'] ?? 'Hamba Allah')); ?>
                                    </td>
                                    <td class="py-4 px-4 uppercase font-bold text-brand-600"><?= $don['type']; ?></td>
                                    <td class="py-4 px-4 font-black text-slate-900">Rp <?= number_format($don['amount'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-4">
                                        <?php if ($don['payment_status'] === 'paid'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">PAID</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-slate-400"><?= date('d M Y H:i', strtotime($don['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-slate-400">Belum ada data transaksi donasi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

</body>
</html>