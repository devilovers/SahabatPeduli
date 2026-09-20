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

$pkCol = 'id';
$dateCol = 'created_at';
$statusCol = 'payment_status';

try {
    $colCheck = $pdo->query("SHOW COLUMNS FROM donations");
    $cols = $colCheck->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('donation_id', $cols)) {
        $pkCol = 'donation_id';
    } elseif (in_array('id_donation', $cols)) {
        $pkCol = 'id_donation';
    } elseif (in_array('id_donasi', $cols)) {
        $pkCol = 'id_donasi';
    } elseif (!in_array('id', $cols) && !empty($cols)) {
        $pkCol = $cols[0];
    }

    if (in_array('created_at', $cols)) {
        $dateCol = 'created_at';
    } elseif (in_array('tanggal', $cols)) {
        $dateCol = 'tanggal';
    } elseif (in_array('date', $cols)) {
        $dateCol = 'date';
    } elseif (in_array('created_date', $cols)) {
        $dateCol = 'created_date';
    } elseif (in_array('transaction_date', $cols)) {
        $dateCol = 'transaction_date';
    } else {
        $dateCol = $pkCol;
    }

    if (in_array('payment_status', $cols)) {
        $statusCol = 'payment_status';
    } elseif (in_array('status', $cols)) {
        $statusCol = 'status';
    } elseif (in_array('donation_status', $cols)) {
        $statusCol = 'donation_status';
    } elseif (in_array('status_pembayaran', $cols)) {
        $statusCol = 'status_pembayaran';
    }
} catch (Exception $e) {
    $pkCol = 'id';
    $dateCol = 'id';
    $statusCol = 'payment_status';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $user_token)) {
        $error_msg = "Sesi tidak valid. Silakan coba lagi.";
    } else {
        $donation_id = intval($_POST['donation_id'] ?? 0);
        $action = $_POST['action'];

        try {
            $stmtDonation = $pdo->prepare("SELECT * FROM donations WHERE {$pkCol} = ?");
            $stmtDonation->execute([$donation_id]);
            $donation = $stmtDonation->fetch();

            if ($donation) {
                $currStatus = strtolower(trim((string)($donation[$statusCol] ?? 'pending')));
                $isAlreadyDone = in_array($currStatus, ['1', '2', 'success', 'verified', 'paid', 'approved', 'disetujui', 'failed', 'rejected', 'ditolak', 'cancel']);

                if ($isAlreadyDone) {
                    $error_msg = "Donasi ini sudah diproses sebelumnya dan tidak dapat diubah lagi.";
                } else {
                    if ($action === 'approve') {
                        $pdo->beginTransaction();

                        $stmtUpdate = $pdo->prepare("UPDATE donations SET {$statusCol} = 'success' WHERE {$pkCol} = ?");
                        $stmtUpdate->execute([$donation_id]);

                        if (!empty($donation['campaign_id'])) {
                            $raw_amount = $donation['amount'] ?? 0;
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
                        $orderCode = $donation['order_id'] ?? $donation['transaction_id'] ?? $donation_id;
                        $success_msg = "Donasi kode " . htmlspecialchars($orderCode) . " berhasil disetujui!";
                    } elseif ($action === 'reject') {
                        $stmtUpdate = $pdo->prepare("UPDATE donations SET {$statusCol} = 'failed' WHERE {$pkCol} = ?");
                        $stmtUpdate->execute([$donation_id]);

                        $orderCode = $donation['order_id'] ?? $donation['transaction_id'] ?? $donation_id;
                        $success_msg = "Donasi kode " . htmlspecialchars($orderCode) . " telah ditolak.";
                    }
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

try {
    $query = "SELECT d.*, c.title AS campaign_title 
              FROM donations d 
              LEFT JOIN campaigns c ON d.campaign_id = c.id 
              ORDER BY d.{$dateCol} DESC";
    $stmtDonations = $pdo->query($query);
    $all_donations = $stmtDonations->fetchAll();
} catch (Exception $e) {
    try {
        $stmtDonations = $pdo->query("SELECT * FROM donations ORDER BY {$dateCol} DESC");
        $all_donations = $stmtDonations->fetchAll();
    } catch (Exception $ex) {
        $all_donations = [];
        $error_msg = "Gagal memuat data donasi: " . $ex->getMessage();
    }
}
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
                            <th class="py-4 px-6">Penyaluran / Program</th>
                            <th class="py-4 px-6">Nominal</th>
                            <th class="py-4 px-6">Bukti Transfer</th>
                            <th class="py-4 px-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs text-slate-700 dark:text-slate-300">
                        <?php if (!empty($all_donations)): ?>
                            <?php foreach ($all_donations as $donation): 
                                $status = strtolower(trim((string)($donation[$statusCol] ?? $donation['payment_status'] ?? $donation['status'] ?? 'pending')));
                                $item_id = $donation[$pkCol] ?? $donation['id'] ?? $donation['donation_id'] ?? 0;
                                
                                $isApproved = in_array($status, ['1', 'success', 'verified', 'paid', 'approved', 'disetujui']);
                                $isRejected = in_array($status, ['2', 'failed', 'rejected', 'ditolak', 'cancel', 'cancelled']);

                                $orderCode = $donation['order_id'] ?? $donation['transaction_id'] ?? $donation['kode_donasi'] ?? ('TRX-' . $item_id);
                                $rawDate = $donation[$dateCol] ?? $donation['created_at'] ?? $donation['tanggal'] ?? $donation['date'] ?? null;
                                $formattedDate = ($rawDate && $rawDate !== $item_id) ? date('d M Y H:i', strtotime($rawDate)) : '-';

                                $isAnon = !empty($donation['is_anonymous']) || strtolower((string)($donation['is_anonymous'] ?? '')) === '1' || strtolower((string)($donation['is_anonymous'] ?? '')) === 'true';
                                
                                if ($isAnon) {
                                    $donorName = "Hamba Allah";
                                } else {
                                    $donorName = $donation['donor_name'] ?? $donation['display_name'] ?? $donation['nama_donatur'] ?? $donation['name'] ?? $donation['full_name'] ?? 'Donatur';
                                }

                                $programTitle = $donation['campaign_title'] ?? $donation['program_title'] ?? $donation['type'] ?? $donation['jenis_donasi'] ?? 'Umum / Infaq';

                                $proofPath = $donation['proof_image'] ?? $donation['payment_proof'] ?? $donation['bukti_transfer'] ?? $donation['proof'] ?? $donation['bukti'] ?? '';
                                $proofPathsToTry = [];
                                if (!empty($proofPath)) {
                                    $cleanPath = ltrim($proofPath, '/');
                                    if (strpos($proofPath, 'http') === 0) {
                                        $proofPathsToTry[] = $proofPath;
                                    } else {
                                        $proofPathsToTry[] = "/SahabatPeduli/" . $cleanPath;
                                        if (strpos($cleanPath, 'uploads/') !== 0) {
                                            $proofPathsToTry[] = "/SahabatPeduli/uploads/" . $cleanPath;
                                            $proofPathsToTry[] = "/SahabatPeduli/uploads/proofs/" . $cleanPath;
                                            $proofPathsToTry[] = "/SahabatPeduli/assets/images/" . $cleanPath;
                                        }
                                    }
                                }
                            ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?= htmlspecialchars($orderCode); ?></span>
                                        <span class="text-[11px] text-slate-400 dark:text-slate-500"><?= htmlspecialchars($formattedDate); ?></span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-slate-900 dark:text-white block"><?= htmlspecialchars($donorName); ?></span>
                                        <?php if ($isAnon): ?>
                                            <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-semibold mt-0.5">Anonim</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="font-bold text-brand-600 dark:text-brand-400 text-[11px] block"><?= htmlspecialchars($programTitle); ?></span>
                                    </td>
                                    <td class="py-4 px-6 font-black text-slate-900 dark:text-white">
                                        Rp <?= number_format(floatval(preg_replace('/[^0-9.]/', '', (string)($donation['amount'] ?? 0))), 0, ',', '.'); ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <?php if (!empty($proofPathsToTry)): ?>
                                            <button type="button" 
                                                    onclick="openProofModal(<?= htmlspecialchars(json_encode($proofPathsToTry)); ?>, '<?= htmlspecialchars($orderCode); ?>')" 
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-bold text-[11px] transition-all">
                                                <i class="fa-solid fa-eye"></i> Lihat Bukti
                                            </button>
                                        <?php else: ?>
                                            <span class="text-slate-400 dark:text-slate-500 italic">Tidak ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <?php if ($isApproved): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-bold text-[11px]">
                                                <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400"></i> Disetujui
                                            </span>
                                        <?php elseif ($isRejected): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 font-bold text-[11px]">
                                                <i class="fa-solid fa-circle-xmark text-rose-600 dark:text-rose-400"></i> Ditolak
                                            </span>
                                        <?php else: ?>
                                            <div class="inline-flex items-center gap-2">
                                                <form method="POST" action="" onsubmit="return confirm('Setujui donasi ini?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="donation_id" value="<?= $item_id; ?>">
                                                    <button type="submit" class="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition-all active:scale-95 shadow-md shadow-emerald-600/20 flex items-center gap-1 px-3">
                                                        <i class="fa-solid fa-check"></i> Terima
                                                    </button>
                                                </form>

                                                <form method="POST" action="" onsubmit="return confirm('Tolak donasi ini?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="donation_id" value="<?= $item_id; ?>">
                                                    <button type="submit" class="p-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-all active:scale-95 shadow-md shadow-rose-600/20 flex items-center gap-1 px-3">
                                                        <i class="fa-solid fa-xmark"></i> Tolak
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-12 text-slate-400 dark:text-slate-500 font-medium">Belum ada transaksi donasi yang masuk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<div id="proofModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md transition-all duration-300">
    <div class="relative bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 class="font-black text-slate-900 dark:text-white text-base">Bukti Transfer</h3>
                <p id="proofModalCode" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"></p>
            </div>
            <button onclick="closeProofModal()" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-all">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="flex-1 overflow-auto my-4 flex items-center justify-center min-h-[250px] bg-slate-50 dark:bg-slate-950/50 rounded-2xl p-2 border border-dashed border-slate-200 dark:border-slate-800">
            <img id="proofModalImage" src="" alt="Bukti Transfer" class="max-w-full max-h-[60vh] object-contain rounded-xl shadow-md transition-all duration-300">
            <p id="proofErrorText" class="hidden text-xs text-rose-500 font-semibold flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> Gambar bukti transfer tidak ditemukan di server.
            </p>
        </div>

        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
            <a id="proofModalDownload" href="" target="_blank" download class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs transition-all">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Gambar Asli
            </a>
            <button onclick="closeProofModal()" class="px-5 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs transition-all shadow-md shadow-brand-600/20">
                Tutup
            </button>
        </div>
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

function openProofModal(paths, orderCode) {
    const modal = document.getElementById('proofModal');
    const img = document.getElementById('proofModalImage');
    const errorText = document.getElementById('proofErrorText');
    const modalCode = document.getElementById('proofModalCode');
    const downloadBtn = document.getElementById('proofModalDownload');

    modalCode.innerText = 'Transaksi: ' + orderCode;
    img.classList.remove('hidden');
    errorText.classList.add('hidden');

    let currentPathIndex = 0;

    function tryLoadImage() {
        if (currentPathIndex < paths.length) {
            img.src = paths[currentPathIndex];
            downloadBtn.href = paths[currentPathIndex];
        } else {
            img.classList.add('hidden');
            errorText.classList.remove('hidden');
        }
    }

    img.onerror = function() {
        currentPathIndex++;
        tryLoadImage();
    };

    tryLoadImage();
    modal.classList.remove('hidden');
}

function closeProofModal() {
    const modal = document.getElementById('proofModal');
    modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', updateThemeUI);
</script>

</body>
</html>