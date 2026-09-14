<?php
$page_title = "Artikel";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$slug = $_GET['slug'] ?? null;
$article = null;

if ($slug) {
    $stmtDetail = $pdo->prepare("SELECT news.*, users.name as author_name FROM news LEFT JOIN users ON news.author_id = users.id WHERE news.slug = ? AND news.status = 'published'");
    $stmtDetail->execute([$slug]);
    $article = $stmtDetail->fetch();
}

if (!$article) {
    $search = trim($_GET['q'] ?? '');
    $query = "SELECT news.*, users.name as author_name FROM news LEFT JOIN users ON news.author_id = users.id WHERE news.status = 'published'";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (news.title LIKE ? OR news.content LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }

    $query .= " ORDER BY news.created_at DESC";
    $stmtList = $pdo->prepare($query);
    $stmtList->execute($params);
    $articles = $stmtList->fetchAll();
}
?>

<div class="bg-slate-50 dark:bg-slate-900 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if ($article): ?>
            <div class="max-w-4xl mx-auto space-y-8">
                <a href="/SahabatPeduli/views/artikel.php" class="inline-flex items-center gap-2 text-xs font-bold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Daftar Artikel</span>
                </a>

                <div class="bg-white dark:bg-slate-800 p-6 sm:p-10 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl space-y-6">
                    <div class="space-y-3">
                        <div class="flex items-center gap-4 text-xs font-semibold text-slate-400 dark:text-slate-400">
                            <span><i class="fa-solid fa-user text-brand-600 dark:text-brand-400 mr-1.5"></i> <?= htmlspecialchars($article['author_name'] ?? 'Admin'); ?></span>
                            <span>•</span>
                            <span><i class="fa-regular fa-calendar text-brand-600 dark:text-brand-400 mr-1.5"></i> <?= date('d M Y', strtotime($article['created_at'])); ?></span>
                        </div>
                        <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white leading-tight">
                            <?= htmlspecialchars($article['title']); ?>
                        </h1>
                    </div>

                    <?php if (!empty($article['image'])): ?>
                        <div class="rounded-2xl overflow-hidden h-64 sm:h-96 bg-slate-100 dark:bg-slate-700">
                            <img src="/SahabatPeduli/uploads/news/<?= htmlspecialchars($article['image']); ?>" 
                                 alt="<?= htmlspecialchars($article['title']); ?>" 
                                 class="w-full h-full object-cover"
                                 onerror="this.src='https://images.unsplash.com/photo-1504151932400-72d4384f04b3?q=80&w=600&auto=format&fit=crop';">
                        </div>
                    <?php endif; ?>

                    <div class="prose prose-slate dark:prose-invert max-w-none text-slate-700 dark:text-slate-300 text-sm sm:text-base leading-relaxed space-y-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                        <?= nl2br(htmlspecialchars($article['content'])); ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="space-y-10">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div class="space-y-2 max-w-xl">
                        <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Kabar & Informasi</span>
                        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">Artikel SahabatPeduli</h1>
                        <p class="text-slate-600 dark:text-slate-300 text-sm">
                            Dapatkan pembaruan kabar penyaluran, kisah penerima manfaat, dan informasi kegiatan amil zakat mingguan.
                        </p>
                    </div>

                    <form method="GET" action="" class="relative w-full md:w-80">
                        <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="Cari artikel..." class="w-full pl-10 pr-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none shadow-sm">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                    </form>
                </div>

                <?php if (!empty($articles)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php foreach ($articles as $item): ?>
                            <article class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700/60 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                                <div>
                                    <div class="h-48 bg-slate-100 dark:bg-slate-700 overflow-hidden relative">
                                        <img src="/SahabatPeduli/uploads/news/<?= htmlspecialchars($item['image']); ?>" 
                                             alt="<?= htmlspecialchars($item['title']); ?>" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                             onerror="this.src='https://images.unsplash.com/photo-1504151932400-72d4384f04b3?q=80&w=600&auto=format&fit=crop';">
                                    </div>
                                    <div class="p-6 space-y-3">
                                        <div class="flex items-center gap-2 text-[11px] font-semibold text-slate-400 dark:text-slate-400">
                                            <span><i class="fa-regular fa-calendar text-brand-600 dark:text-brand-400 mr-1"></i> <?= date('d M Y', strtotime($item['created_at'])); ?></span>
                                            <span>•</span>
                                            <span>by <?= htmlspecialchars($item['author_name'] ?? 'Admin'); ?></span>
                                        </div>
                                        <h2 class="font-extrabold text-slate-900 dark:text-white text-lg group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors line-clamp-2">
                                            <a href="/SahabatPeduli/views/artikel.php?slug=<?= $item['slug']; ?>">
                                                <?= htmlspecialchars($item['title']); ?>
                                            </a>
                                        </h2>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs line-clamp-3 leading-relaxed">
                                            <?= htmlspecialchars(strip_tags($item['content'])); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="p-6 pt-0">
                                    <a href="/SahabatPeduli/views/artikel.php?slug=<?= $item['slug']; ?>" class="inline-flex items-center gap-2 text-xs font-bold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors">
                                        <span>Baca Selengkapnya</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white dark:bg-slate-800 p-12 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm text-center max-w-lg mx-auto space-y-3">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded-full flex items-center justify-center mx-auto text-2xl">
                            <i class="fa-solid fa-newspaper"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">Tidak ada artikel ditemukan</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Artikel tidak ditemukan atau belum dipublikasikan oleh admin.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>