<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /SahabatPeduli/auth/login.php");
    exit;
}

$page_title = "Kelola Donasi";
$success_msg = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $order_id = $_POST['order_id'] ?? '';
    $new_status = $_POST['payment_status'] ?? 'pending';

    try {
        $pdo->beginTransaction();

        $stmtGet = $pdo->prepare("SELECT * FROM donations WHERE order_id = ?");
        $stmtGet->execute([$order_id]);
        $donation = $stmtGet->fetch();

        if ($donation) {
            $old_status = $donation['payment_status'];

            $stmtUp = $pdo->prepare("UPDATE donations SET payment_status = ? WHERE order_id = ?");
            $stmtUp->execute([$new_status, $order_id]);

            if ($old_status !== 'paid' && $new_status === 'paid' && !empty($donation['campaign_id'])) {
                $stmtCamp = $pdo->prepare("UPDATE campaigns SET collected_amount = collected_amount + ? WHERE id = ?");
                $stmtCamp->execute([$donation['amount'], $donation['campaign_id']]);
            }

            $pdo->commit();
            $success_msg = "Status transaksi $order_id berhasil diperbarui menjadi " . strtoupper($new_status) . ".";
        } else {
            $pdo->rollBack();
            $error_msg = "Transaksi tidak ditemukan.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Gagal memperbarui status: " . $e->getMessage();
    }
}

$filter_status = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$query = "
    SELECT d.*, u.name as user_name, c.title as campaign_title 
    FROM donations d 
    LEFT JOIN users u ON d.user_id = u.id 
    LEFT JOIN campaigns c ON d.campaign_id = c.id 
    WHERE 1=1
";
$params = [];

if ($filter_status !== 'all') {
    $query .= " AND d.payment_status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $query .= " AND (d.order_id LIKE ? OR d.display_name LIKE ? OR u.name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$query .= " ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$donations = $stmt->fetchAll();
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
            <!-- Logo SahabatPeduli -->
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

            <!-- Menus -->
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

    <!-- Main Content Area -->
    <main class="flex-1 bg-white dark:bg-slate-950 py-8 px-4 sm:px-8 lg:px-12 overflow-y-auto space-y-8 sm:space-y-10 transition-colors duration-300">

        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <span class="text-brand-600 dark:text-brand-400 font-bold text-xs uppercase tracking-wider">Manajemen Transaksi</span>
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Kelola Transaksi Donasi</h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Verifikasi dan perbarui status pembayaran donatur SahabatPeduli.</p>
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

        <!-- Filter & Search Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 sm:p-6 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                <?php
                $statuses = [
                    'all' => 'Semua Status',
                    'paid' => 'PAID (Lunas)',
                    'pending' => 'PENDING (Menunggu)',
                ];
                foreach ($statuses as $stKey => $stLabel):
                    $btnClass = ($filter_status === $stKey) 
                        ? 'bg-brand-600 text-white font-bold shadow-md shadow-brand-600/20' 
                        : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-semibold';
                ?>
                    <a href="/SahabatPeduli/admin/donasi.php?status=<?= $stKey; ?>&q=<?= urlencode($search); ?>" class="px-4 py-2.5 rounded-xl text-xs transition-all <?= $btnClass; ?>">
                        <?= $stLabel; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" action="" class="relative w-full sm:w-72">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status); ?>">
                <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Cari Order ID / Donatur..." class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none transition-all">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 dark:text-slate-500 text-xs"></i>
            </form>
        </div>

        <!-- Data Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[600px]">
                    <thead>
                        <tr class="bg-white dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 text-[11px] font-black text-slate-400 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-4 px-6">Order ID</th>
                            <th class="py-4 px-6">Donatur</th>
                            <th class="py-4 px-6">Jenis & Program</th>
                            <th class="py-4 px-6">Nominal</th>
                            <th class="py-4 px-6">Status</th>
                            <th class="py-4 px-6">Tanggal</th>
                            <th class="py-4 px-6 text-center">Aksi Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs text-slate-700 dark:text-slate-300">
                        <?php if (!empty($donations)): ?>
                            <?php foreach ($donations as $don): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-4 px-6 font-mono font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($don['order_id']); ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-900 dark:text-white">
                                        <?= htmlspecialchars($don['display_name'] ?: ($don['user_name'] ?? 'Hamba Allah')); ?>
                                        <?php if ($don['is_anonymous']): ?>
                                            <span class="block text-[10px] text-slate-400 dark:text-slate-500 font-normal">(Anonim)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2.5 py-0.5 rounded-full bg-brand-50 dark:bg-brand-950/50 text-brand-700 dark:text-brand-300 font-bold text-[10px] uppercase tracking-wider border border-brand-200/60 dark:border-brand-800/60 inline-block">
                                            <?= htmlspecialchars($don['type']); ?>
                                        </span>
                                        <?php if (!empty($don['campaign_title'])): ?>
                                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-normal line-clamp-1 mt-1"><?= htmlspecialchars($don['campaign_title']); ?></span>
                                        <?php endif; ?>
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
                                    <td class="py-4 px-6 text-center">
                                        <form method="POST" action="" class="inline-flex items-center gap-1">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($don['order_id']); ?>">
                                            
                                            <?php if ($don['payment_status'] === 'pending'): ?>
                                                <button type="submit" name="payment_status" value="paid" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-[11px] shadow-sm transition-all active:scale-95">
                                                    Set PAID
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="payment_status" value="pending" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-[11px] shadow-sm transition-all active:scale-95">
                                                    Set PENDING
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-12 text-slate-400 dark:text-slate-500 font-medium">Tidak ada transaksi donasi yang cocok.</td>
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