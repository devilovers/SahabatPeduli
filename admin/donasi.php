<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: /PeduliUmat/auth/login.php");
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
            <a href="/PeduliUmat/admin/donasi.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-600 text-white font-bold">
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

    <main class="flex-1 p-6 lg:p-10 space-y-6 overflow-y-auto">

        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Kelola Transaksi Donasi</h1>
                <p class="text-xs text-slate-500 mt-1">Verifikasi dan update status pembayaran donatur.</p>
            </div>
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

        <div class="bg-white p-4 sm:p-6 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                <?php
                $statuses = [
                    'all' => 'Semua Status',
                    'paid' => 'PAID (Lunas)',
                    'pending' => 'PENDING (Menunggu)',
                ];
                foreach ($statuses as $stKey => $stLabel):
                    $btnClass = ($filter_status === $stKey) 
                        ? 'bg-brand-600 text-white font-bold' 
                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200 font-semibold';
                ?>
                    <a href="/PeduliUmat/admin/donasi.php?status=<?= $stKey; ?>&q=<?= urlencode($search); ?>" class="px-4 py-2 rounded-xl text-xs transition-all <?= $btnClass; ?>">
                        <?= $stLabel; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" action="" class="relative w-full sm:w-72">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status); ?>">
                <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Cari Order ID / Donatur..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            </form>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4">Order ID</th>
                            <th class="py-3.5 px-4">Donatur</th>
                            <th class="py-3.5 px-4">Jenis & Program</th>
                            <th class="py-3.5 px-4">Nominal</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4 text-center">Aksi Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php if (!empty($donations)): ?>
                            <?php foreach ($donations as $don): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-4 px-4 font-mono font-bold text-slate-900"><?= $don['order_id']; ?></td>
                                    <td class="py-4 px-4 font-semibold text-slate-800">
                                        <?= htmlspecialchars($don['display_name'] ?: ($don['user_name'] ?? 'Hamba Allah')); ?>
                                        <?php if ($don['is_anonymous']): ?>
                                            <span class="block text-[10px] text-slate-400 font-normal">(Anonim)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="uppercase font-bold text-brand-600 block"><?= $don['type']; ?></span>
                                        <?php if (!empty($don['campaign_title'])): ?>
                                            <span class="text-[11px] text-slate-400 font-normal line-clamp-1"><?= htmlspecialchars($don['campaign_title']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 font-black text-slate-900">Rp <?= number_format($don['amount'], 0, ',', '.'); ?></td>
                                    <td class="py-4 px-4">
                                        <?php if ($don['payment_status'] === 'paid'): ?>
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">PAID</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-4 text-slate-400"><?= date('d M Y H:i', strtotime($don['created_at'])); ?></td>
                                    <td class="py-4 px-4 text-center">
                                        <form method="POST" action="" class="inline-flex items-center gap-1">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?= $don['order_id']; ?>">
                                            
                                            <?php if ($don['payment_status'] === 'pending'): ?>
                                                <button type="submit" name="payment_status" value="paid" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-[11px] transition-all">
                                                    Set PAID
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" name="payment_status" value="pending" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-[11px] transition-all">
                                                    Set PENDING
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-10 text-slate-400">Tidak ada transaksi donasi yang cocok.</td>
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