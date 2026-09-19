<?php
$page_title = "Peta Penyaluran Dana";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']);

$stmtTotal =$pdo->query("SELECT SUM(amount_spent) AS total_spent, COUNT(id) AS total_points FROM distributions");
$summary =$stmtTotal->fetch();
$totalSpent =$summary['total_spent'] ?? 0;
$totalPoints =$summary['total_points'] ?? 0;

$stmtDistributions =$pdo->query("SELECT * FROM distributions ORDER BY distribution_date DESC");
$distributions =$stmtDistributions->fetchAll();
?>

<div class="bg-slate-50 dark:bg-slate-900 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        
        <div class="text-center max-w-3xl mx-auto space-y-3">
            <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Transparansi Penyaluran</span>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white">Peta Sebaran Penyaluran Dana</h1>
            <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base">
                Setiap donasi yang Anda percayakan disalurkan secara terbuka dan akuntabel. Pantau sebaran program bantuan SahabatPeduli di berbagai wilayah.
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
                    <input type="text" id="mapSearch" onkeyup="searchLocation()" placeholder="Cari lokasi / tempat..." class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-xs focus:ring-2 focus:ring-brand-500 focus:outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
                </div>
            </div>

            <div id="fullMap" class="w-full h-[520px] rounded-2xl border border-slate-200 dark:border-slate-700 z-10"></div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-6 sm:p-8 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-700 pb-4">
                <div>
                    <h2 class="font-extrabold text-slate-900 dark:text-white text-xl">Rincian Penyaluran Dana</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Laporan rinci foto, penerima, dan anggaran bantuan.</p>
                </div>
                <?php if (!$isLoggedIn): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 text-xs font-semibold border border-amber-200 dark:border-amber-800/50">
                        <i class="fa-solid fa-lock text-[10px]"></i> Akses Terbatas (Belum Login)
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Foto</th>
                                <th class="py-3 px-4">Tanggal & Tempat</th>
                                <th class="py-3 px-4">Disalurkan Kepada</th>
                                <th class="py-3 px-4">Jenis Bantuan</th>
                                <th class="py-3 px-4 text-right">Anggaran</th>
                            </tr>
                        </thead>
                        <tbody id="distributionTable" class="divide-y divide-slate-100 dark:divide-slate-700/60 text-sm text-slate-700 dark:text-slate-300">
                            <?php if (!empty($distributions)): ?>
                                <?php foreach ($distributions as $item):$imgSrc = !empty($item['photo']) ? '/SahabatPeduli/' .$item['photo'] : 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=200&auto=format&fit=crop';
                                ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <button type="button" onclick="openPhotoModal('<?= htmlspecialchars($imgSrc); ?>', '<?= htmlspecialchars(addslashes($item['location_name'])); ?>')" class="group relative block focus:outline-none">
                                                <img src="<?= htmlspecialchars($imgSrc); ?>" 
                                                     alt="Foto Penyaluran" 
                                                     class="w-16 h-12 object-cover rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm group-hover:opacity-80 transition-all duration-200"
                                                     onerror="this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=200&auto=format&fit=crop';">
                                                <span class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 rounded-xl transition-opacity text-white text-xs">
                                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                                </span>
                                            </button>
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap">
                                            <span class="block font-bold text-slate-900 dark:text-white">
                                                <i class="fa-solid fa-location-dot text-brand-600 dark:text-brand-400 mr-1"></i> <?= htmlspecialchars($item['location_name']); ?>
                                            </span>
                                            <span class="text-xs text-slate-400 dark:text-slate-500">
                                                <i class="fa-regular fa-calendar mr-1"></i> <?= !empty($item['distribution_date']) ? date('d M Y', strtotime($item['distribution_date'])) : '-'; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                            <?= htmlspecialchars($item['recipient_name'] ?? 'Masyarakat Penerima'); ?>
                                            <span class="block text-xs text-slate-400 font-normal"><?= number_format($item['beneficiaries_count'] ?? 0, 0, ',', '.'); ?> Orang</span>
                                        </td>
                                        <td class="py-4 px-4">
                                            <span class="inline-block px-2.5 py-1 rounded-lg bg-brand-50 dark:bg-brand-950/50 text-brand-700 dark:text-brand-300 text-xs font-bold border border-brand-100 dark:border-brand-800/40">
                                                <?= htmlspecialchars($item['assistance_type'] ?? 'Bantuan Sosial'); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-4 text-right font-black text-brand-600 dark:text-brand-400 whitespace-nowrap">
                                            Rp <?= number_format($item['amount_spent'], 0, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-slate-400 dark:text-slate-500">Belum ada data penyaluran dana yang dicatat.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12 px-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-dashed border-slate-200 dark:border-slate-700/80 space-y-4">
                    <div class="w-16 h-16 mx-auto rounded-full bg-brand-50 dark:bg-slate-800 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl shadow-inner">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                    <div class="max-w-md mx-auto space-y-1">
                        <h3 class="font-bold text-slate-900 dark:text-white text-lg">Rincian Penyaluran Dibatasi</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed">
                            Silakan masuk atau buat akun terlebih dahulu untuk melihat foto kegiatan, rincian penerima bantuan, jenis bantuan, serta anggaran lengkap.
                        </p>
                    </div>
                    <div class="pt-2">
                        <a href="../auth/login.php" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition-all">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>Login Sekarang</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>

<!-- Modal Pratinjau Foto Penyaluran -->
<div id="photoPreviewModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-2xl w-full p-4 sm:p-6 space-y-4 shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 id="photoModalTitle" class="font-bold text-slate-900 dark:text-white text-sm sm:text-base flex items-center gap-2">
                <i class="fa-solid fa-image text-brand-600 dark:text-brand-400"></i> Dokumentasi Penyaluran
            </h3>
            <button onclick="closePhotoModal()" class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center transition-all">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="flex justify-center bg-slate-950 rounded-2xl overflow-hidden">
            <img id="photoModalImg" src="" alt="Foto Penyaluran" class="max-h-[70vh] w-auto object-contain">
        </div>
    </div>
</div>

<script>
let map;
let markers = [];
const distributionData = <?= json_encode($distributions); ?>;
const isLoggedIn = <?= json_encode($isLoggedIn); ?>;

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
            let popupContent = '';

            if (isLoggedIn) {
                const imgSrc = item.photo ? '/SahabatPeduli/' + item.photo : 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=200&auto=format&fit=crop';
                popupContent = `
                    <div class="p-2 font-sans max-w-[220px]">
                        <img src="${imgSrc}" class="w-full h-24 object-cover rounded-lg mb-2 border border-slate-100 cursor-pointer" onclick="openPhotoModal('${imgSrc}', '${item.location_name}')" onerror="this.src='https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=200&auto=format&fit=crop';">
                        <h4 class="font-bold text-slate-900 text-sm mb-1">${item.location_name}</h4>
                        <p class="text-[11px] text-slate-500 mb-1"><i class="fa-regular fa-calendar"></i> ${item.distribution_date || '-'}</p>
                        <p class="text-[11px] text-slate-700 mb-1"><strong>Penerima:</strong> ${item.recipient_name || '-'}</p>
                        <p class="text-[11px] text-slate-700 mb-1"><strong>Bantuan:</strong> ${item.assistance_type || '-'}</p>
                        <p class="text-xs font-black text-emerald-600 mt-2">Anggaran: Rp ${parseInt(item.amount_spent).toLocaleString('id-ID')}</p>
                    </div>
                `;
            } else {
                popupContent = `
                    <div class="p-2 font-sans text-center">
                        <h4 class="font-bold text-slate-900 text-sm mb-1"><i class="fa-solid fa-location-dot text-emerald-600"></i> ${item.location_name}</h4>
                        <p class="text-[11px] text-slate-500 mb-2">Titik Lokasi Penyaluran</p>
                        <a href="../auth/login.php" class="inline-block text-[10px] bg-emerald-600 text-white font-bold px-2 py-1 rounded">Login untuk Rincian</a>
                    </div>
                `;
            }

            marker.bindPopup(popupContent);
            markers.push(marker);
        });
    }
}

function searchLocation() {
    const query = document.getElementById('mapSearch').value.toLowerCase();
    const filtered = distributionData.filter(item => 
        (item.location_name && item.location_name.toLowerCase().includes(query)) ||
        (item.assistance_type && item.assistance_type.toLowerCase().includes(query))
    );
    
    renderMarkers(filtered);

    if (filtered.length > 0) {
        map.setView([filtered[0].latitude, filtered[0].longitude], 8);
    }
}

function openPhotoModal(src, location) {
    const modal = document.getElementById('photoPreviewModal');
    const img = document.getElementById('photoModalImg');
    const title = document.getElementById('photoModalTitle');
    
    img.src = src;
    title.innerHTML = `<i class="fa-solid fa-image text-brand-600 dark:text-brand-400"></i> Dokumentasi Penyaluran - ${location}`;
    modal.classList.remove('hidden');
}

function closePhotoModal() {
    document.getElementById('photoPreviewModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>