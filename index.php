<?php
$page_title = "Beranda";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$stmtTotal =$pdo->query("SELECT SUM(amount) AS total FROM donations WHERE payment_status = 'paid'");
$totalDonations =$stmtTotal->fetch()['total'] ?? 0;

$stmtSpent =$pdo->query("SELECT SUM(amount_spent) AS total_spent FROM distributions");
$totalSpent =$stmtSpent->fetch()['total_spent'] ?? 0;

$stmtCampaigns =$pdo->query("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC LIMIT 6");
$campaigns =$stmtCampaigns->fetchAll();

$stmtArticles =$pdo->query("SELECT * FROM news WHERE status = 'published' ORDER BY created_at DESC LIMIT 6");
$articles =$stmtArticles->fetchAll();

$stmtDistributions =$pdo->query("SELECT id, location_name, amount_spent, latitude, longitude, created_at FROM distributions ORDER BY created_at DESC");
$distributions =$stmtDistributions->fetchAll();
?>

<main class="flex-grow">
    <section class="relative bg-gradient-to-b from-emerald-50/50 via-white to-slate-50 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 py-16 lg:py-24 overflow-hidden transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
                        Penyaluran ZIS - DSKL <br class="hidden sm:inline" />
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-600 via-emerald-600 to-teal-500 dark:from-brand-400 dark:via-emerald-400 dark:to-teal-300">
                            Tepat Sasaran & Berdampak
                        </span>
                    </h1>
                    
                    <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 max-w-2xl mx-auto lg:mx-0 font-normal leading-relaxed">
                        SahabatPeduli membantu menyalurkan Zakat, Infaq, Sedekah, dan DSKL Anda langsung kepada Mustahik secara akuntabel dan terpeta secara real-time.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                        <a href="/SahabatPeduli/views/donasi.php" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-base shadow-lg shadow-brand-600/30 hover:shadow-xl transition-all flex items-center justify-center gap-3 group">
                            <span>Tunaikan Donasi</span>
                            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="/SahabatPeduli/views/kalkulator.php" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-brand-500 text-slate-700 dark:text-slate-200 hover:text-brand-600 dark:hover:text-brand-400 font-bold text-base shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-calculator text-brand-600 dark:text-brand-400"></i>
                            <span>Hitung Zakat</span>
                        </a>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-4 pt-8 border-t border-slate-200/60 dark:border-slate-800">
                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700/60 shadow-sm">
                            <span class="block text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Total Dana Terkumpul</span>
                            <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Rp <?= number_format($totalDonations, 0, ',', '.'); ?></span>
                        </div>
                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-100 dark:border-slate-700/60 shadow-sm">
                            <span class="block text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wider">Total Penyaluran</span>
                            <span class="text-xl sm:text-2xl font-black text-brand-600 dark:text-brand-400">Rp <?= number_format($totalSpent, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-5 relative">
                    <div class="relative mx-auto w-full max-w-md bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-white dark:border-slate-700/50 p-6 sm:p-8 rounded-3xl shadow-2xl shadow-emerald-900/10 dark:shadow-black/40 space-y-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-brand-50 dark:bg-slate-700 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl shadow-inner">
                                    <i class="fa-solid fa-layer-group"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 dark:text-white">Kategori Program</h3>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Pilih opsi donasi cepat</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="/SahabatPeduli/views/donasi.php?type=zakat" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 hover:bg-brand-50 dark:hover:bg-slate-700/80 border border-slate-100 dark:border-slate-700 hover:border-brand-200 dark:hover:border-slate-600 transition-all text-center group">
                                <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-coins"></i>
                                </div>
                                <span class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-brand-700 dark:group-hover:text-brand-400 block">Zakat</span>
                                <span class="text-[10px] text-brand-600 dark:text-brand-400 font-medium block italic">(زَكَاة) Suci & Tumbuh</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-tight">Kewajiban menyucikan harta untuk golongan berhak.</p>
                            </a>

                            <a href="/SahabatPeduli/views/donasi.php?type=infaq" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 hover:bg-brand-50 dark:hover:bg-slate-700/80 border border-slate-100 dark:border-slate-700 hover:border-brand-200 dark:hover:border-slate-600 transition-all text-center group">
                                <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-teal-500 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-hand-holding-dollar"></i>
                                </div>
                                <span class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-brand-700 dark:group-hover:text-brand-400 block">Infaq</span>
                                <span class="text-[10px] text-brand-600 dark:text-brand-400 font-medium block italic">(إِنْفَاق) Mengeluarkan Harta</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-tight">Menafkahkan harta di jalan Allah tanpa nisab tertentu.</p>
                            </a>

                            <a href="/SahabatPeduli/views/donasi.php?type=sedekah" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 hover:bg-brand-50 dark:hover:bg-slate-700/80 border border-slate-100 dark:border-slate-700 hover:border-brand-200 dark:hover:border-slate-600 transition-all text-center group">
                                <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-cyan-500 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <span class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-brand-700 dark:group-hover:text-brand-400 block">Sedekah</span>
                                <span class="text-[10px] text-brand-600 dark:text-brand-400 font-medium block italic">(صَدَقَة) Kebenaran Iman</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-tight">Pemberian sukarela bukti kejujuran iman seseorang.</p>
                            </a>

                            <a href="/SahabatPeduli/views/donasi.php?type=dskl" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/60 hover:bg-brand-50 dark:hover:bg-slate-700/80 border border-slate-100 dark:border-slate-700 hover:border-brand-200 dark:hover:border-slate-600 transition-all text-center group">
                                <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg shadow-md group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-globe"></i>
                                </div>
                                <span class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover:text-brand-700 dark:group-hover:text-brand-400 block">DSKL</span>
                                <span class="text-[10px] text-brand-600 dark:text-brand-400 font-medium block italic">Dana Keagamaan Lain</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 line-clamp-2 leading-tight">Penyaluran dana sosial keagamaan non-zakat/infaq.</p>
                            </a>
                        </div>

                        <a href="/SahabatPeduli/views/donasi.php" class="block w-full text-center py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-md shadow-brand-600/20 transition-colors">
                            Lihat Semua Opsi
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="py-20 bg-white dark:bg-slate-900 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12">
                <div>
                    <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Program Unggulan</span>
                    <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1">Program SahabatPeduli</h2>
                </div>
                <a href="/SahabatPeduli/views/program.php" class="mt-4 md:mt-0 inline-flex items-center gap-2 font-bold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300">
                    <span>Lihat Semua Program</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (!empty($campaigns)): ?>
                    <?php foreach ($campaigns as$campaign): 
                        $percent =$campaign['target_amount'] > 0 ? min(100, round(($campaign['collected_amount'] /$campaign['target_amount']) * 100)) : 0;
                        
                        if (!empty($campaign['image'])) {
                            if (preg_match('~^(https?://|/)~', $campaign['image'])) {
                                $imgSrc =$campaign['image'];
                            } else {
                                $imgSrc = '/SahabatPeduli/uploads/campaigns/' .$campaign['image'];
                            }
                        } else {
                            $imgSrc = 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=600&auto=format&fit=crop';
                        }
                    ?>
                        <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col group">
                            <div class="relative h-48 bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                <img src="<?= htmlspecialchars($imgSrc); ?>" 
                                     alt="<?= htmlspecialchars($campaign['title']); ?>" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     onerror="this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=600&auto=format&fit=crop';">
                                <span class="absolute top-4 left-4 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold text-brand-700 dark:text-brand-400 shadow-sm capitalize">
                                    <?= str_replace('_', ' ', $campaign['category']); ?>
                                </span>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                                <div>
                                    <h3 class="font-bold text-lg text-slate-900 dark:text-white line-clamp-2 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition-colors">
                                        <?= htmlspecialchars($campaign['title']); ?>
                                    </h3>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-2 line-clamp-2">
                                        <?= htmlspecialchars($campaign['description']); ?>
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs font-bold">
                                        <span class="text-slate-500 dark:text-slate-400">Terkumpul</span>
                                        <span class="text-brand-600 dark:text-brand-400"><?= $percent; ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 h-2.5 rounded-full overflow-hidden">
                                        <div class="bg-brand-600 dark:bg-brand-500 h-full rounded-full" style="width: <?= $percent; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-sm font-extrabold pt-1">
                                        <span class="text-slate-900 dark:text-white">Rp <?= number_format($campaign['collected_amount'], 0, ',', '.'); ?></span>
                                        <span class="text-slate-400 dark:text-slate-500 font-normal text-xs">Target: Rp <?= number_format($campaign['target_amount'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>

                                <a href="/SahabatPeduli/views/donasi.php?campaign_id=<?= $campaign['id']; ?>" class="block w-full py-3 text-center rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm transition-colors shadow-md shadow-brand-500/10">
                                    Donasi Sekarang
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-12 text-slate-400 dark:text-slate-500 font-medium">
                        Belum ada program aktif saat ini.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="py-20 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Transparansi Real-Time</span>
                <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white mt-1">Peta Lokasi Penyaluran Dana</h2>
                <p class="text-slate-600 dark:text-slate-400 text-sm mt-2">
                    Setiap rupiah dana yang terkumpul disalurkan secara terbuka dan dapat dipantau sebarannya di seluruh wilayah.
                </p>
            </div>

            <div class="rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-xl relative z-10">
                <div id="distributionMap" class="w-full h-[450px] bg-slate-100 dark:bg-slate-800"></div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white dark:bg-slate-900 transition-colors duration-300 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex items-center justify-between pb-6 mb-8">
                <div>
                    <span class="text-xs font-black uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400 block mb-1">
                        Pers & Publikasi
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                        Berita & Artikel
                    </h2>
                </div>
                <a href="/SahabatPeduli/views/berita.php" class="text-xs font-bold uppercase tracking-widest text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors flex items-center gap-2">
                    <span>Lihat Semua</span>
                    <i class="fa-solid fa-arrow-right-long"></i>
                </a>
            </div>

            <?php if (!empty($articles)): ?>
                <div class="relative group/slider px-2">
                    <button id="slideLeftBtn" class="absolute left-2 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 dark:bg-slate-800/90 text-slate-800 dark:text-white shadow-xl hover:bg-brand-600 hover:text-white dark:hover:bg-brand-500 transition-all flex items-center justify-center opacity-90 group-hover/slider:opacity-100 border border-slate-200 dark:border-slate-700">
                        <i class="fa-solid fa-chevron-left text-lg"></i>
                    </button>
                    <button id="slideRightBtn" class="absolute right-2 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 dark:bg-slate-800/90 text-slate-800 dark:text-white shadow-xl hover:bg-brand-600 hover:text-white dark:hover:bg-brand-500 transition-all flex items-center justify-center opacity-90 group-hover/slider:opacity-100 border border-slate-200 dark:border-slate-700">
                        <i class="fa-solid fa-chevron-right text-lg"></i>
                    </button>

                    <div id="articlesSlider" class="flex overflow-x-auto scrollbar-none snap-x snap-mandatory rounded-3xl">
                        <?php foreach ($articles as$article): ?>
                            <article class="flex-none w-full snap-start flex flex-col group">
                                <div class="space-y-4">
                                    <a href="/SahabatPeduli/views/berita.php?slug=<?= $article['slug']; ?>" class="block overflow-hidden rounded-3xl">
                                        <img src="/SahabatPeduli/uploads/news/<?= htmlspecialchars($article['image']); ?>" 
                                             alt="<?= htmlspecialchars($article['title']); ?>" 
                                             class="w-full h-[400px] sm:h-[500px] object-cover filter grayscale contrast-125 group-hover:grayscale-0 group-hover:scale-105 transition-all duration-500 ease-out"
                                             onerror="this.src='https://images.unsplash.com/photo-1497633762265-9d179a990aa6?q=80&w=1000&auto=format&fit=crop';">
                                    </a>

                                    <div class="flex items-center gap-3 text-xs font-mono text-slate-400 dark:text-slate-500 uppercase tracking-wider pt-2">
                                        <span class="text-brand-600 dark:text-brand-400 font-bold">Hamba Allah</span>
                                        <span>/</span>
                                        <time><?= date('d M Y', strtotime($article['created_at'])); ?></time>
                                    </div>

                                    <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white leading-tight">
                                        <a href="/SahabatPeduli/views/berita.php?slug=<?= $article['slug']; ?>" class="hover:underline decoration-brand-500 underline-offset-4">
                                            <?= htmlspecialchars($article['title']); ?>
                                        </a>
                                    </h3>

                                    <p class="text-slate-600 dark:text-slate-300 text-base line-clamp-3 leading-relaxed font-sans">
                                        <?= htmlspecialchars(strip_tags($article['content'])); ?>
                                    </p>
                                </div>

                                <div class="pt-4">
                                    <a href="/SahabatPeduli/views/berita.php?slug=<?= $article['slug']; ?>" class="text-xs font-black uppercase tracking-widest text-brand-600 dark:text-brand-400 hover:text-slate-900 dark:hover:text-white transition-colors inline-flex items-center gap-1">
                                        <span>Baca Laporan Selengkapnya</span>
                                        <i class="fa-solid fa-angle-right text-[10px]"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-12 text-slate-400 dark:text-slate-500 font-medium">
                    Belum ada berita dipublikasikan.
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapContainer = document.getElementById('distributionMap');
    if (mapContainer && typeof L !== 'undefined') {
        var map = L.map('distributionMap').setView([-2.548926, 118.014863], 5);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var distributionData = <?= json_encode($distributions); ?>;

        if (distributionData && distributionData.length > 0) {
            distributionData.forEach(function(item) {
                if (item.latitude && item.longitude) {
                    var marker = L.marker([parseFloat(item.latitude), parseFloat(item.longitude)]).addTo(map);
                    var popupContent = `
                        <div class="p-2 font-sans">
                            <h4 class="font-bold text-slate-900 text-sm mb-1">${item.location_name}</h4>
                            <p class="text-xs text-slate-500 mb-1"><i class="fa-solid fa-location-dot"></i> ${item.location_name}</p>
                            <p class="text-xs font-bold text-emerald-600">Penyaluran: Rp ${parseInt(item.amount_spent).toLocaleString('id-ID')}</p>
                        </div>
                    `;
                    marker.bindPopup(popupContent);
                }
            });
        }
    }

    var slider = document.getElementById('articlesSlider');
    var btnLeft = document.getElementById('slideLeftBtn');
    var btnRight = document.getElementById('slideRightBtn');

    if (slider && btnLeft && btnRight) {
        btnLeft.addEventListener('click', function() {
            slider.scrollBy({ left: -slider.clientWidth, behavior: 'smooth' });
        });
        btnRight.addEventListener('click', function() {
            slider.scrollBy({ left: slider.clientWidth, behavior: 'smooth' });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>