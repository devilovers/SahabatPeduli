<?php
$page_title = "Kalkulator Zakat";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$hargaEmasPerGram = 1100000; 
$nisabTahunan = 85 * $hargaEmasPerGram;
$nisabBulanan = $nisabTahunan / 12;
?>

<div class="bg-slate-50 dark:bg-slate-900 py-12 lg:py-16 min-h-screen transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-brand-600 dark:text-brand-400 font-bold text-sm uppercase tracking-wider">Hitung Zakat Anda</span>
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white mt-1">Kalkulator Zakat Interaktif</h1>
            <p class="text-slate-600 dark:text-slate-300 text-sm sm:text-base mt-2">
                Hitung kewajiban Zakat Penghasilan dan Zakat Maal Anda secara presisi dan transparan sesuai dengan nisab yang berlaku.
            </p>
        </div>

        <div class="flex justify-center mb-8">
            <div class="bg-white dark:bg-slate-800 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex gap-2">
                <button type="button" onclick="switchTab('profesi')" id="tabProfesi" class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all bg-brand-600 text-white shadow-sm">
                    <i class="fa-solid fa-briefcase mr-1.5"></i> Zakat Penghasilan
                </button>
                <button type="button" onclick="switchTab('maal')" id="tabMaal" class="px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">
                    <i class="fa-solid fa-coins mr-1.5"></i> Zakat Maal / Emas
                </button>
            </div>
        </div>

        <div class="max-w-4xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <div class="lg:col-span-7 bg-white dark:bg-slate-800/80 p-6 sm:p-8 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl space-y-6">
                
                <div id="formProfesi" class="space-y-4">
                    <h3 class="font-bold text-slate-900 dark:text-white text-lg border-b border-slate-100 dark:border-slate-700/60 pb-3 flex items-center justify-between">
                        <span>Zakat Penghasilan / Profesi</span>
                        <span class="text-xs font-normal text-slate-400 dark:text-slate-400">Nisab/Bulan: Rp <?= number_format($nisabBulanan, 0, ',', '.'); ?></span>
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pendapatan Utama per Bulan (Rp)</label>
                        <input type="text" id="gajiBulan" onkeyup="formatInputRupiah(this); hitungZakatProfesi();" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="cth. 7.000.000">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pendapatan Tambahan / Bonus per Bulan (Rp)</label>
                        <input type="text" id="bonusBulan" onkeyup="formatInputRupiah(this); hitungZakatProfesi();" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="cth. 1.000.000" value="0">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kebutuhan Pokok / Hutang Jatuh Tempo per Bulan (Rp)</label>
                        <input type="text" id="pengeluaranBulan" onkeyup="formatInputRupiah(this); hitungZakatProfesi();" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="cth. 2.000.000" value="0">
                    </div>
                </div>

                <div id="formMaal" class="hidden space-y-4">
                    <h3 class="font-bold text-slate-900 dark:text-white text-lg border-b border-slate-100 dark:border-slate-700/60 pb-3 flex items-center justify-between">
                        <span>Zakat Maal (Harta / Tabungan)</span>
                        <span class="text-xs font-normal text-slate-400 dark:text-slate-400">Nisab/Tahun: Rp <?= number_format($nisabTahunan, 0, ',', '.'); ?></span>
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Total Uang Tabungan / Deposito / Perhiasan Emas (Rp)</label>
                        <input type="text" id="totalHarta" onkeyup="formatInputRupiah(this); hitungZakatMaal();" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="cth. 100.000.000">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Hutang / Kewajiban yang Harus Dibayar (Rp)</label>
                        <input type="text" id="hutangMaal" onkeyup="formatInputRupiah(this); hitungZakatMaal();" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="cth. 5.000.000" value="0">
                    </div>
                    <p class="text-xs text-slate-400 dark:text-slate-400 italic">*Harta telah mengendap/tersimpan selama 1 tahun (Haul).</p>
                </div>

            </div>

            <div class="lg:col-span-5 bg-white dark:bg-slate-800/80 p-6 sm:p-8 rounded-3xl border border-slate-100 dark:border-slate-700/60 shadow-xl flex flex-col justify-between space-y-6">
                <div>
                    <span class="text-xs font-semibold text-slate-400 dark:text-slate-400 uppercase tracking-wider block mb-1">Hasil Perhitungan</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6" id="titleHasil">Zakat Penghasilan Anda</h3>

                    <div class="space-y-4 bg-slate-50 dark:bg-slate-900/60 p-5 rounded-2xl border border-slate-100 dark:border-slate-700/60">
                        <div class="flex justify-between items-center text-xs text-slate-600 dark:text-slate-300">
                            <span>Status Nisab:</span>
                            <span id="statusNisab" class="font-bold text-amber-600 dark:text-amber-400">Belum Memenuhi</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-slate-600 dark:text-slate-300 border-t border-slate-200/60 dark:border-slate-700/60 pt-3">
                            <span>Persentase Zakat:</span>
                            <span class="font-bold text-slate-900 dark:text-white">2.5%</span>
                        </div>
                        <div class="border-t border-slate-200/60 dark:border-slate-700/60 pt-3">
                            <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">Jumlah Zakat Wajib Dibayar:</span>
                            <span class="text-3xl font-black text-brand-600 dark:text-brand-400" id="textHasilZakat">Rp 0</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <a id="btnBayarZakat" href="/SahabatPeduli/views/donasi.php?type=zakat&amount=0" class="block w-full py-4 text-center rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-lg shadow-brand-600/30 transition-all">
                        Tunaikan Zakat Sekarang
                    </a>
                    <p class="text-[11px] text-slate-400 dark:text-slate-400 text-center">
                        Perhitungan ini mengacu pada nisab emas 85 gram.
                    </p>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
const nisabBulanan = <?= $nisabBulanan; ?>;
const nisabTahunan = <?= $nisabTahunan; ?>;
let activeTab = 'profesi';

function formatInputRupiah(element) {
    let rawValue = element.value.replace(/[^0-9]/g, '');
    if (rawValue) {
        element.value = parseInt(rawValue, 10).toLocaleString('id-ID');
    } else {
        element.value = '';
    }
}

function getCleanNumber(elementId) {
    const val = document.getElementById(elementId).value.replace(/[^0-9]/g, '');
    return parseFloat(val) || 0;
}

function switchTab(tab) {
    activeTab = tab;
    const btnProfesi = document.getElementById('tabProfesi');
    const btnMaal = document.getElementById('tabMaal');
    const formProfesi = document.getElementById('formProfesi');
    const formMaal = document.getElementById('formMaal');
    const titleHasil = document.getElementById('titleHasil');

    if (tab === 'profesi') {
        btnProfesi.className = "px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all bg-brand-600 text-white shadow-sm";
        btnMaal.className = "px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white";
        formProfesi.classList.remove('hidden');
        formMaal.classList.add('hidden');
        titleHasil.innerText = "Zakat Penghasilan Anda";
        hitungZakatProfesi();
    } else {
        btnMaal.className = "px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all bg-brand-600 text-white shadow-sm";
        btnProfesi.className = "px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white";
        formMaal.classList.remove('hidden');
        formProfesi.classList.add('hidden');
        titleHasil.innerText = "Zakat Maal Anda";
        hitungZakatMaal();
    }
}

function hitungZakatProfesi() {
    const gaji = getCleanNumber('gajiBulan');
    const bonus = getCleanNumber('bonusBulan');
    const pengeluaran = getCleanNumber('pengeluaranBulan');

    const bersih = (gaji + bonus) - pengeluaran;
    const statusNisab = document.getElementById('statusNisab');
    const textHasilZakat = document.getElementById('textHasilZakat');
    const btnBayarZakat = document.getElementById('btnBayarZakat');

    if (bersih >= nisabBulanan) {
        const zakat = bersih * 0.025;
        statusNisab.innerText = "Wajib Zakat (Mencapai Nisab)";
        statusNisab.className = "font-bold text-emerald-600 dark:text-emerald-400";
        textHasilZakat.innerText = "Rp " + Math.round(zakat).toLocaleString('id-ID');
        btnBayarZakat.href = "/SahabatPeduli/views/donasi.php?type=zakat&amount=" + Math.round(zakat);
    } else {
        statusNisab.innerText = "Belum Wajib Zakat";
        statusNisab.className = "font-bold text-amber-600 dark:text-amber-400";
        textHasilZakat.innerText = "Rp 0";
        btnBayarZakat.href = "/SahabatPeduli/views/donasi.php?type=zakat&amount=0";
    }
}

function hitungZakatMaal() {
    const totalHarta = getCleanNumber('totalHarta');
    const hutang = getCleanNumber('hutangMaal');

    const bersih = totalHarta - hutang;
    const statusNisab = document.getElementById('statusNisab');
    const textHasilZakat = document.getElementById('textHasilZakat');
    const btnBayarZakat = document.getElementById('btnBayarZakat');

    if (bersih >= nisabTahunan) {
        const zakat = bersih * 0.025;
        statusNisab.innerText = "Wajib Zakat (Mencapai Nisab)";
        statusNisab.className = "font-bold text-emerald-600 dark:text-emerald-400";
        textHasilZakat.innerText = "Rp " + Math.round(zakat).toLocaleString('id-ID');
        btnBayarZakat.href = "/SahabatPeduli/views/donasi.php?type=zakat&amount=" + Math.round(zakat);
    } else {
        statusNisab.innerText = "Belum Wajib Zakat";
        statusNisab.className = "font-bold text-amber-600 dark:text-amber-400";
        textHasilZakat.innerText = "Rp 0";
        btnBayarZakat.href = "/SahabatPeduli/views/donasi.php?type=zakat&amount=0";
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>