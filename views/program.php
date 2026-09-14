<?php
$page_title = "Program Peduli";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$selected_category = $_GET['category'] ?? 'all';

$query = "SELECT * FROM campaigns WHERE status = 'active'";
$params = [];

if ($selected_category !== 'all') {
    $query .= " AND category = ?";
    $params[] = $selected_category;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$campaigns = $stmt->fetchAll();
?>

<div class="bg-slate-50 dark:bg-slate-900 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">

        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Program Kebaikan</span>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">Program SahabatPeduli</h1>
            <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base">
                Pilih program donasi yang ingin Anda dukung. Penyaluran dana dikelola secara rinci, akuntabel, dan transparan untuk mereka yang membutuhkan.
            </p>
        </div>

        <div class="flex flex-wrap justify-center gap-2 sm:gap-3">
            <?php
            $categories = [
                'all' => 'Semua Program',
                'peduli_pendidikan' => 'Peduli Pendidikan',
                'peduli_kesehatan' => 'Peduli Kesehatan',
                'peduli_bencana' => 'Peduli Bencana',
                'peduli_ekonomi' => 'Peduli Ekonomi & Sosial',
            ];
            foreach ($categories as $key => $label):
                $active = ($selected_category === $key) 
                    ? 'bg-brand-600 dark:bg-brand-500 text-white shadow-md shadow-brand-500/20' 
                    : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:border-brand-500 dark:hover:border-brand-400 hover:text-brand-600 dark:hover:text-brand-400';
            ?>
                <a href="/SahabatPeduli/views/program.php?category=<?= $key; ?>" class="px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all <?= $active; ?>">
                    <?= $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($campaigns)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($campaigns as $camp): 
                    $target = floatval($camp['target_amount']);
                    $collected = floatval($camp['collected_amount']);
                    $percent = ($target > 0) ? min(100, round(($collected / $target) * 100)) : 0;
                    
                    $endDate = new DateTime($camp['end_date']);
                    $today = new DateTime();
                    $daysLeft = $today->diff($endDate)->days;
                    if ($today > $endDate) {
                        $daysLeft = 0;
                    }
                ?>
                    <div class="bg-white dark:bg-slate-800/60 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm hover:shadow-xl transition-all flex flex-col justify-between overflow-hidden group">
                        
                        <div>
                            <div class="relative h-52 bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                <img src="/SahabatPeduli/uploads/campaigns/<?= htmlspecialchars($camp['image']); ?>" 
                                     alt="<?= htmlspecialchars($camp['title']); ?>" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     onerror="this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=600&auto=format&fit=crop';">
                                
                                <span class="absolute top-4 left-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md text-brand-700 dark:text-brand-400 text-[10px] font-black uppercase px-3 py-1.5 rounded-full shadow-sm">
                                    <?= str_replace('peduli_', 'Peduli ', $camp['category']); ?>
                                </span>
                            </div>

                            <div class="p-6 space-y-4">
                                <h2 class="font-extrabold text-slate-900 dark:text-white text-lg group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-2">
                                    <?= htmlspecialchars($camp['title']); ?>
                                </h2>
                                
                                <p class="text-slate-500 dark:text-slate-400 text-xs line-clamp-2 leading-relaxed">
                                    <?= htmlspecialchars($camp['description']); ?>
                                </p>

                                <div class="space-y-2 pt-2">
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-brand-600 dark:text-brand-400">Rp <?= number_format($collected, 0, ',', '.'); ?></span>
                                        <span class="text-slate-400 dark:text-slate-500">Terkumpul <?= $percent; ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden">
                                        <div class="bg-brand-600 dark:bg-brand-500 h-full rounded-full transition-all duration-500" style="width: <?= $percent; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-[11px] text-slate-400 dark:text-slate-500 font-medium">
                                        <span>Target: Rp <?= number_format($target, 0, ',', '.'); ?></span>
                                        <span><i class="fa-regular fa-clock mr-1"></i><?= $daysLeft; ?> hari lagi</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 pt-0">
                            <a href="/SahabatPeduli/views/donasi.php?campaign_id=<?= $camp['id']; ?>" class="w-full py-3.5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-brand-500/20 transition-all text-center block">
                                Donasi Sekarang
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white dark:bg-slate-800 p-12 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-sm text-center max-w-lg mx-auto space-y-3">
                <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">Tidak ada program ditemukan</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Belum ada program aktif pada kategori yang Anda pilih saat ini.</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>