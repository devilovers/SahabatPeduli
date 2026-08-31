<?php
$page_title = "Peta Penyaluran Dana";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$stmtTotal = $pdo->query("SELECT SUM(amount_spent) AS total_spent, COUNT(id) AS total_points FROM distributions");
$summary = $stmtTotal->fetch();
$totalSpent = $summary['total_spent'] ?? 0;
$totalPoints = $summary['total_points'] ?? 0;

$stmtDistributions = $pdo->query("SELECT * FROM distributions ORDER BY distribution_date DESC");
$distributions = $stmtDistributions->fetchAll();
?>

<div class="bg-slate-50 dark:bg-slate-900 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Transparansi Penyaluran</span>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">Peta Sebaran Penyaluran Dana</h1>
            <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base">
                Setiap donasi yang Anda percayakan disalurkan secara terbuka dan akuntabel. Pantau sebaran program bantuan PeduliUmat di berbagai wilayah.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl shadow-inner">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Dana Disalurkan</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5">Rp <?= number_format($totalSpent, 0, ',', '.'); ?></h3>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center text-2xl shadow-inner">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Titik Lokasi Bantuan</span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-0.5"><?= number_format($totalPoints, 0, ',', '.'); ?> Titik</h3>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-sm flex items-center gap-4 sm:col-span-2 lg:col-span-1">
                <div class="w-14 h-14 rounded-2xl bg-cyan-50 dark:bg-cyan-950/50 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-2xl shadow-inner">
                    <i class="fa-solid fa-shield-check"></i>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Penyaluran</span>
                    <h3 class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">Terverifikasi & Terpeta</h3>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-4 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl overflow-hidden space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 p-2">
                <h2 class="font-extrabold text-slate-900 dark:text-white text-lg flex items-center gap-2">
                    <i class="fa-solid fa-globe text-brand-600 dark:text-brand-400"></i> Peta Interaktif Leaflet.js
                </h2>
                <div class="relative w-full sm:w-72">
                    <input type="text" id="mapSearch" onkeyup="searchLocation()" placeholder="Cari lokasi / kegiatan..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div id="fullMap" class="w-full h-[520px] rounded-2xl border border-slate-200 dark:border-slate-700 z-10"></div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-6 sm:p-8 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl space-y-6">
            <h2 class="font-extrabold text-slate-900 dark:text-white text-xl">Rincian Penyaluran Dana</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 dark:border-slate-700 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Nama Kegiatan</th>
                            <th class="py-3 px-4">Lokasi Penyaluran</th>
                            <th class="py-3 px-4 text-right">Dana Disalurkan</th>
                        </tr>
                    </thead>
                    <tbody id="distributionTable" class="divide-y divide-slate-100 dark:divide-slate-700/60 text-sm text-slate-700 dark:text-slate-300">
                        <?php if (!empty($distributions)): ?>
                            <?php foreach ($distributions as $item): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-4 px-4 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                        <?= date('d M Y', strtotime($item['distribution_date'])); ?>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-slate-900 dark:text-white">
                                        <?= htmlspecialchars($item['title']); ?>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 font-normal mt-0.5 line-clamp-1"><?= htmlspecialchars($item['description']); ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                        <i class="fa-solid fa-location-dot text-brand-600 dark:text-brand-400 mr-1"></i> <?= htmlspecialchars($item['location_name']); ?>
                                    </td>
                                    <td class="py-4 px-4 text-right font-black text-brand-600 dark:text-brand-400 whitespace-nowrap">
                                        Rp <?= number_format($item['amount_spent'], 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-8 text-slate-400 dark:text-slate-500">Belum ada data penyaluran dana yang dicatat.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
let map;
let markers = [];
const distributionData = <?= json_encode($distributions); ?>;

document.addEventListener('DOMContentLoaded', function() {
    map = L.map('fullMap').setView([-2.548926, 118.014863], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    renderMarkers(distributionData);
});

function renderMarkers(data) {
    markers.forEach(m => map.removeLayer(m));
    markers = [];

    if (data.length > 0) {
        data.forEach(function(item) {
            const marker = L.marker([item.latitude, item.longitude]).addTo(map);
            const popupContent = `
                <div class="p-2 font-sans">
                    <h4 class="font-bold text-slate-900 text-sm mb-1">${item.title}</h4>
                    <p class="text-xs text-slate-500 mb-1"><i class="fa-solid fa-location-dot"></i> ${item.location_name}</p>
                    <p class="text-xs text-slate-600 mb-2">${item.description}</p>
                    <p class="text-xs font-black text-emerald-600">Disalurkan: Rp ${parseInt(item.amount_spent).toLocaleString('id-ID')}</p>
                </div>
            `;
            marker.bindPopup(popupContent);
            markers.push(marker);
        });
    }
}

function searchLocation() {
    const query = document.getElementById('mapSearch').value.toLowerCase();
    const filtered = distributionData.filter(item => 
        item.title.toLowerCase().includes(query) || 
        item.location_name.toLowerCase().includes(query)
    );
    
    renderMarkers(filtered);

    if (filtered.length > 0) {
        map.setView([filtered[0].latitude, filtered[0].longitude], 8);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>