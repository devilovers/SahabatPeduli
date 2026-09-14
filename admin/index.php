<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /SahabatPeduli/auth/login.php");
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
    <title><?= $page_title; ?> - SahabatPeduli</title>
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
<body class="bg-white dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-100 antialiased selection:bg-brand-500 selection:text-white min-h-screen flex flex-col transition-colors duration-300">

<!-- Header Mobile Top Bar -->
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

<div class="min-h-screen flex flex-col md:flex-row relative bg-white dark:bg-slate-950">

    <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 hidden md:hidden transition-opacity"></div>

    <!-- Sidebar Admin -->
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
                <a href="/SahabatPeduli/admin/index.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/30">
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
                <a href="/SahabatPeduli/admin/laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white transition-all">
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

    <main class="flex-1 bg-white dark:bg-slate-950 py-8 px-4 sm:px-8 lg:px-12 overflow-y-auto space-y-8 sm:space-y-10 transition-colors duration-300">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center">
            
            <div class="lg:col-span-8 space-y-4 sm:space-y-6">
                <span class="text-brand-600 dark:text-brand-400 font-bold text-xs uppercase tracking-wider">Dashboard Control Center</span>
                <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                    Selamat Datang, <br />
                    <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-600 via-emerald-600 to-teal-500 dark:from-brand-400 dark:via-emerald-400 dark:to-teal-300">
                        <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>!
                    </span>
                </h1>
                
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 max-w-2xl font-normal leading-relaxed">
                    Kelola penyaluran Zakat, Infaq, Sedekah, dan DSKL secara akuntabel, terpeta real-time, serta pantau seluruh perkembangan program SahabatPeduli.
                </p>

                <div class="flex flex-wrap items-center gap-3 sm:gap-4 pt-2">
                    <a href="/SahabatPeduli/admin/donasi.php" class="w-full sm:w-auto px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-lg shadow-brand-600/30 hover:shadow-xl transition-all flex items-center justify-center gap-3 group">
                        <span>Kelola Donasi</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="/SahabatPeduli/admin/program.php" class="w-full sm:w-auto px-6 sm:px-8 py-3.5 sm:py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-brand-500 text-slate-700 dark:text-slate-200 hover:text-brand-600 dark:hover:text-brand-400 font-bold text-sm shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-folder-open text-brand-600 dark:text-brand-400"></i>
                        <span>Tambah Program</span>
                    </a>
                </div>
            </div>

            <div class="lg:col-span-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 sm:p-6 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none space-y-4">
                    <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-tr from-brand-600 via-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                            <i class="fa-solid fa-hand-holding-heart text-xl sm:text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white text-sm sm:text-base">Ringkasan Sistem</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Status Operasional Live</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 text-xs font-semibold">
                        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Database Connection</span>
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Terhubung
                            </span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Role Akses</span>
                            <span class="text-brand-600 dark:text-brand-400 font-bold uppercase tracking-wider">
                                <?= htmlspecialchars($_SESSION['user_role'] ?? 'admin'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            
            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all space-y-2">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Dana Terkumpul</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white block">Rp <?= number_format($totalDonations, 0, ',', '.'); ?></span>
                <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-circle-check"></i> Terverifikasi Pembayaran
                </span>
            </div>

            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all space-y-2">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Total Penyaluran</span>
                <span class="text-xl sm:text-2xl font-black text-brand-600 dark:text-brand-400 block">Rp <?= number_format($totalDistributions, 0, ',', '.'); ?></span>
                <span class="text-[11px] text-brand-600 dark:text-brand-400 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-map-location-dot"></i> Terpeta Secara Real-Time
                </span>
            </div>

            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all space-y-2">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Program Aktif</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white block"><?= number_format($activeCampaigns, 0, ',', '.'); ?> <span class="text-xs text-slate-400 font-normal">Program</span></span>
                <span class="text-[11px] text-teal-600 dark:text-teal-400 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-clock"></i> Penggalangan Berjalan
                </span>
            </div>

            <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-xl transition-all space-y-2">
                <span class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Jumlah Donatur</span>
                <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white block"><?= number_format($totalDonors, 0, ',', '.'); ?> <span class="text-xs text-slate-400 font-normal">Orang</span></span>
                <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold flex items-center gap-1">
                    <i class="fa-solid fa-users"></i> Donatur Terdaftar
                </span>
            </div>

        </div>

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
            <div class="p-5 sm:p-8 flex flex-col sm:flex-row sm:items-end justify-between border-b border-slate-100 dark:border-slate-800 gap-4">
                <div>
                    <span class="text-brand-600 dark:text-brand-400 font-bold text-xs uppercase tracking-wider">Transparansi Real-Time</span>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white mt-1">Transaksi Donasi Terbaru</h2>
                </div>
                <a href="/SahabatPeduli/admin/donasi.php" class="inline-flex items-center gap-2 font-bold text-brand-600 dark:text-brand-400 hover:text-brand-700 text-xs sm:text-sm">
                    <span>Lihat Semua Donasi</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-white dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-[11px] font-black text-slate-400 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-4 px-6">Order ID</th>
                            <th class="py-4 px-6">Donatur</th>
                            <th class="py-4 px-6">Kategori</th>
                            <th class="py-4 px-6">Nominal</th>
                            <th class="py-4 px-6">Status Pembayaran</th>
                            <th class="py-4 px-6">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs text-slate-700 dark:text-slate-300">
                        <?php if (!empty($recentDonations)): ?>
                            <?php foreach ($recentDonations as $don): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-4 px-6 font-mono font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($don['order_id']); ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-900 dark:text-white">
                                        <?= htmlspecialchars($don['display_name'] ?: ($don['user_name'] ?? 'Hamba Allah')); ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 rounded-full bg-brand-50 dark:bg-brand-950/50 text-brand-700 dark:text-brand-300 font-bold text-[10px] uppercase tracking-wider border border-brand-200/60 dark:border-brand-800/60">
                                            <?= htmlspecialchars($don['type']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 font-black text-brand-600 dark:text-brand-400 text-sm">Rp <?= number_format($don['amount'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-6">
                                        <?php if ($don['payment_status'] === 'paid'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-bold text-[10px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> PAID
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-bold text-[10px]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> PENDING
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-slate-400 dark:text-slate-500 font-medium"><?= date('d M Y H:i', strtotime($don['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-12 text-slate-400 dark:text-slate-500 font-medium">Belum ada transaksi donasi yang tercatat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
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