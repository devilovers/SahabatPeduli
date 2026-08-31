<footer class="relative z-10 bg-white/40 dark:bg-slate-900/40 backdrop-blur-2xl backdrop-saturate-150 border-t border-white/60 dark:border-slate-800/80 text-slate-600 dark:text-slate-300 shadow-[0_-10px_25px_-5px_rgba(0,0,0,0.05)] dark:shadow-[0_-10px_25px_-5px_rgba(0,0,0,0.3)] transition-colors duration-300 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-600/20">
                        <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                    </div>
                    <span class="font-extrabold text-2xl text-slate-900 dark:text-white">Peduli<span class="text-brand-600 dark:text-brand-500">Umat</span></span>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                    Lembaga Amil Zakat, Infaq, Sedekah, Wakaf, dan Fidyah yang transparan, amanah, dan profesional.
                </p>
            </div>

            <div>
                <h4 class="text-slate-900 dark:text-white font-semibold mb-4">Program Peduli</h4>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li><a href="#" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Peduli Edukasi</a></li>
                    <li><a href="#" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Peduli Sehat</a></li>
                    <li><a href="#" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Peduli Daya</a></li>
                    <li><a href="#" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Peduli Kemanusiaan</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-slate-900 dark:text-white font-semibold mb-4">Layanan</h4>
                <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <li><a href="/PeduliUmat/views/kalkulator.php" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Kalkulator Zakat</a></li>
                    <li><a href="/PeduliUmat/views/peta.php" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Peta Penyaluran</a></li>
                    <li><a href="/PeduliUmat/views/publikasi.php" class="hover:text-brand-600 dark:hover:text-brand-400 transition-colors">Laporan Keuangan</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-slate-900 dark:text-white font-semibold mb-4">Kontak Kami</h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-location-dot text-brand-600 dark:text-brand-400 w-4"></i> 
                    Jl. Kebajikan No. 1, Jakarta
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-envelope text-brand-600 dark:text-brand-400 w-4"></i> 
                    info@peduliumat.org
                </p>
                <p class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                    <i class="fa-solid fa-phone text-brand-600 dark:text-brand-400 w-4"></i> 
                    (021) 1234-5678
                </p>
            </div>
        </div>

        <div class="border-t border-slate-200/60 dark:border-slate-800/80 mt-12 pt-8 text-center text-sm text-slate-500 dark:text-slate-400">
            &copy; <?= date('Y'); ?> PeduliUmat. Seluruh hak cipta dilindungi.
        </div>
    </div>
</footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>