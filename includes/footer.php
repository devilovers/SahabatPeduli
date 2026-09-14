<footer class="relative z-10 bg-white/40 dark:bg-slate-900/40 backdrop-blur-2xl backdrop-saturate-150 border-t border-white/60 dark:border-slate-800/80 text-slate-600 dark:text-slate-300 shadow-[0_-10px_25px_-5px_rgba(0,0,0,0.05)] dark:shadow-[0_-10px_25px_-5px_rgba(0,0,0,0.3)] transition-colors duration-300 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-600/20">
                        <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                    </div>
                    <span class="font-extrabold text-2xl text-slate-900 dark:text-white">Sahabat<span class="text-brand-600 dark:text-brand-500">Peduli</span></span>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Lembaga Pengelola ZIS - DSKL yang transparan, amanah, dan profesional.
                </p>
            </div>

            <div>
                <h4 class="text-slate-900 dark:text-white font-semibold mb-4">Program Peduli</h4>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400 cursor-default select-none">
                    <li>Peduli Edukasi</li>
                    <li>Peduli Sehat</li>
                    <li>Peduli Daya</li>
                    <li>Peduli Kemanusiaan</li>
                </ul>
            </div>

            <div>
                <h4 class="text-slate-900 dark:text-white font-semibold mb-4">Layanan</h4>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400 cursor-default select-none">
                    <li>Kalkulator Zakat</li>
                    <li>Peta Penyaluran</li>
                    <li>Program</li>
                    <li>Laporan Keuangan</li>
                </ul>
            </div>

            <div>
                <h4 class="text-slate-900 dark:text-white font-semibold mb-4">Kontak Kami</h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2.5 flex items-start gap-2">
                    <i class="fa-solid fa-location-dot text-brand-600 dark:text-brand-400 w-4 mt-1 flex-shrink-0"></i> 
                    <span>Jl. Ahmad Yani KM 30.5, Guntung Manggis, Landasan Ulin, Banjarbaru, Kalimantan Selatan, 70712</span>
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-envelope text-brand-600 dark:text-brand-400 w-4 flex-shrink-0"></i> 
                    <span>spinpeduli@gmail.com</span>
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                    <i class="fa-solid fa-phone text-brand-600 dark:text-brand-400 w-4 flex-shrink-0"></i> 
                    <span>081545726000</span>
                </p>
            </div>
        </div>

        <div class="mt-12 mb-6 flex flex-col items-center justify-center gap-3">
            <span class="text-xs font-semibold text-slate-900 dark:text-white">Ikuti Media Sosial Kami</span>
            <div class="flex items-center gap-4">
                <a href="https://www.instagram.com/spinpeduli?igsi=NmtzeWZ0aXNhb2Rz" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-brand-600 hover:text-white dark:hover:bg-brand-600 transition-all flex items-center justify-center text-base shadow-sm" title="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a href="https://www.tiktok.com/@sahabat.peduli.in?_r=1&_t=ZS-993g1842pr3" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-brand-600 hover:text-white dark:hover:bg-brand-600 transition-all flex items-center justify-center text-base shadow-sm" title="TikTok">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
                <a href="https://www.facebook.com/share/1C5bjSokVc/" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-brand-600 hover:text-white dark:hover:bg-brand-600 transition-all flex items-center justify-center text-base shadow-sm" title="Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
            </div>
        </div>

        <div class="border-t border-slate-200/60 dark:border-slate-800/80 pt-8 text-center text-sm text-slate-500 dark:text-slate-400">
            &copy; <?= date('Y'); ?> SahabatPeduli. Seluruh hak cipta dilindungi.
        </div>
    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>