<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? $_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /SahabatPeduli/auth/login.php");
    exit;
}

$page_title = "Verifikasi Donasi";
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $user_token)) {
        $error_msg = "Sesi tidak valid. Silakan coba lagi.";
    } else {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $action = $_POST['action'];

        try {
            $stmtDonation = $pdo->prepare("SELECT * FROM donations WHERE id = ?");
            $stmtDonation->execute([$donation_id]);
            $donation = $stmtDonation->fetch();

            if ($donation) {
                if ($action === 'approve') {
                    $pdo->beginTransaction();

                    $stmtUpdate = $pdo->prepare("UPDATE donations SET payment_status = 'success', status = 'success' WHERE id = ?");
                    $stmtUpdate->execute([$donation_id]);

                    if (!empty($donation['campaign_id'])) {
                        $raw_amount = $donation['amount'];
                        if (is_numeric($raw_amount)) {
                            $amount = floatval($raw_amount);
                        } else {
                            $amount = floatval(preg_replace('/[^0-9.]/', '', (string)$raw_amount));
                        }

                        if ($amount > 0) {
                            $stmtCampaign = $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
                            $stmtCampaign->execute([$amount, $donation['campaign_id']]);
                        }
                    }

                    $pdo->commit();
                    $success_msg = "Donasi kode " . htmlspecialchars($donation['order_id']) . " berhasil disetujui!";
                } elseif ($action === 'reject') {
                    $stmtUpdate = $pdo->prepare("UPDATE donations SET payment_status = 'failed', status = 'failed' WHERE id = ?");
                    $stmtUpdate->execute([$donation_id]);

                    $success_msg = "Donasi kode " . htmlspecialchars($donation['order_id']) . " telah ditolak.";
                }
            } else {
                $error_msg = "Data donasi tidak ditemukan.";
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Gagal memproses donasi: " . $e->getMessage();
        }
    }
}

$stmtDonations = $pdo->query("
    SELECT d.*, c.title AS campaign_title 
    FROM donations d 
    LEFT JOIN campaigns c ON d.campaign_id = c.id 
    ORDER BY d.created_at DESC
");
$all_donations = $stmtDonations->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?> - Admin SahabatPeduli</title>
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
                <a href="/SahabatPeduli/admin/donasi.php" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/30">
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

        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <span class="text-brand-600 dark:text-brand-400 font-bold text-xs uppercase tracking-wider">Verifikasi & Keuangan</span>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Kelola Transaksi Donasi</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Periksa bukti transfer dan konfirmasi status pembayaran dari donatur.</p>
            </div>
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

        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-white dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-[11px] font-black text-slate-400 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-4 px-6">Kode & Tanggal</th>
                            <th class="py-4 px-6">Donatur</th>
                            <th class="py-4 px-6">Penyaluran</th>
                            <th class="py-4 px-6">Nominal</th>
                            <th class="py-4 px-6">Bukti Transfer</th>
                            <th class="py-4 px-6 text-center">Status</th>
                            <th class="py-4 px-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs text-slate-700 dark:text-slate-300">
                        <?php if (!empty($all_donations)): ?>
                            <?php foreach ($all_donations as $donation): 
                                $raw_status = $donation['payment_status'] ?? $donation['status'] ?? '';
                                $status = strtolower($raw_status);
                            ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?= htmlspecialchars($donation['order_id']); ?></span>
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500"><?= date('d M Y H:i', strtotime($donation['created_at'])); ?></span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?= htmlspecialchars($donation['display_name']); ?></span>
                                        <?php if ($donation['is_anonymous']): ?>
                                            <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-semibold mt-0.5">Anonim</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-brand-600 dark:text-brand-400 uppercase text-[11px] block"><?= htmlspecialchars($donation['type']); ?></span>
                                        <?php if (!empty($donation['campaign_title'])): ?>
                                            <span class="text-[11px] text-slate-400 dark:text-slate-500 line-clamp-1 max-w-[180px]" title="<?= htmlspecialchars($donation['campaign_title']); ?>">
                                                <?= htmlspecialchars($donation['campaign_title']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 font-black text-slate-900 dark:text-white">
                                        Rp <?= number_format(floatval(preg_replace('/[^0-9.]/', '', (string)$donation['amount'])), 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php if (!empty($donation['proof_image'])): ?>
                                            <a href="/SahabatPeduli/<?= htmlspecialchars($donation['proof_image']); ?>" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-bold text-[11px] transition-all">
                                                <i class="fa-solid fa-file-invoice"></i> Lihat Bukti
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-slate-500 italic">Tidak ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($status === 'success' || $status === 'disetujui'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-bold text-[10px]">DISETUJUI</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 font-bold text-[10px]">DITOLAK</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <div class="inline-flex items-center gap-2">
                                            <form method="POST" action="" onsubmit="return confirm('Setujui donasi ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="donation_id" value="<?= $donation['id']; ?>">
                                                <button type="submit" class="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-all active:scale-95 shadow-md shadow-emerald-600/20 flex items-center gap-1 px-3">
                                                    <i class="fa-solid fa-check"></i> Terima
                                                </button>
                                            </form>

                                            <form method="POST" action="" onsubmit="return confirm('Tolak donasi ini?');">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="donation_id" value="<?= $donation['id']; ?>">
                                                <button type="submit" class="p-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-all active:scale-95 shadow-md shadow-rose-600/20 flex items-center gap-1 px-3">
                                                    <i class="fa-solid fa-xmark"></i> Tolak
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-12 text-slate-400 dark:text-slate-500 font-medium">Belum ada transaksi donasi yang masuk.</td>
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