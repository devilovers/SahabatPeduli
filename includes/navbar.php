<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$userName   = $_SESSION['user_name'] ?? '';
$userRole   = $_SESSION['user_role'] ?? 'muzakki';

// Deteksi halaman aktif berdasarkan URL saat ini
$currentUri = $_SERVER['REQUEST_URI'];
function isActiveNav($path, $currentUri) {
    $parsedPath = parse_url($path, PHP_URL_PATH);
    $parsedUri  = parse_url($currentUri, PHP_URL_PATH);
    if ($parsedPath === '/PeduliUmat/index.php' && ($parsedUri === '/PeduliUmat/' || $parsedUri === '/PeduliUmat/index.php')) {
        return true;
    }
    return $parsedPath === $parsedUri;
}
?>

<header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-100 dark:border-slate-800 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            
            <a href="/PeduliUmat/index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 via-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:scale-105 transition-transform">
                    <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                </div>
                <span class="font-extrabold text-xl text-slate-900 dark:text-white tracking-tight">
                    Peduli<span class="text-brand-600">Umat</span>
                </span>
            </a>

            <nav class="hidden md:flex items-center gap-8 font-semibold text-sm text-slate-600 dark:text-slate-300 h-full">
                <?php
                $navItems = [
                    ['url' => '/PeduliUmat/index.php', 'label' => 'Beranda'],
                    ['url' => '/PeduliUmat/views/program.php', 'label' => 'Program Donasi'],
                    ['url' => '/PeduliUmat/views/kalkulator.php', 'label' => 'Kalkulator Zakat'],
                    ['url' => '/PeduliUmat/views/penyaluran.php', 'label' => 'Peta Penyaluran'],
                    ['url' => '/PeduliUmat/views/berita.php', 'label' => 'Berita'],
                    ['url' => '/PeduliUmat/views/laporan.php', 'label' => 'Laporan Transparansi'],
                ];

                foreach ($navItems as $item):
                    $active = isActiveNav($item['url'], $currentUri);
                ?>
                    <a href="<?= $item['url']; ?>" class="relative flex items-center h-full transition-colors <?= $active ? 'text-brand-600 dark:text-brand-400 font-bold' : 'hover:text-brand-600 dark:hover:text-brand-400' ?>">
                        <span><?= $item['label']; ?></span>
                        <?php if ($active): ?>
                            <span class="absolute bottom-0 left-0 right-0 h-1 bg-brand-600 dark:bg-brand-400 rounded-t-full shadow-sm"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                <button type="button" class="theme-toggle-btn w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center justify-center active:scale-95" aria-label="Ubah Mode Tema">
                    <i class="fa-solid fa-moon theme-toggle-dark-icon hidden text-sm"></i>
                    <i class="fa-solid fa-sun theme-toggle-light-icon hidden text-sm"></i>
                </button>

                <?php if ($isLoggedIn): ?>
                    <?php if ($userRole === 'admin'): ?>
                        <div class="relative group">
                            <a href="/PeduliUmat/admin/index.php" class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-slate-800 to-slate-900 text-amber-400 flex items-center justify-center shadow-md shadow-slate-900/10 hover:scale-105 active:scale-95 transition-all duration-200" aria-label="Panel Admin">
                                <i class="fa-solid fa-chart-line text-sm"></i>
                            </a>
                            <div class="absolute right-0 top-12 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                                Panel Admin
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="relative group">
                        <a href="/PeduliUmat/auth/logout.php" class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-500 text-white flex items-center justify-center shadow-md shadow-rose-500/20 hover:scale-105 active:scale-95 transition-all duration-200" aria-label="Keluar">
                            <i class="fa-solid fa-right-from-bracket text-sm"></i>
                        </a>
                        <div class="absolute right-0 top-12 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                            Keluar
                        </div>
                    </div>
                <?php else: ?>
                    <div class="relative group">
                        <a href="/PeduliUmat/auth/login.php" class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-sky-500 to-blue-600 text-white flex items-center justify-center shadow-md shadow-sky-500/20 hover:scale-105 active:scale-95 transition-all duration-200" aria-label="Masuk">
                            <i class="fa-solid fa-right-to-bracket text-sm"></i>
                        </a>
                        <div class="absolute right-0 top-12 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                            Masuk
                        </div>
                    </div>

                    <div class="relative group">
                        <a href="/PeduliUmat/auth/register.php" class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-brand-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20 hover:scale-105 active:scale-95 transition-all duration-200" aria-label="Daftar">
                            <i class="fa-solid fa-user-plus text-sm"></i>
                        </a>
                        <div class="absolute right-0 top-12 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                            Daftar Akun
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2 md:hidden">
                <button type="button" class="theme-toggle-btn p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-amber-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95" aria-label="Ubah Mode Tema">
                    <i class="fa-solid fa-moon theme-toggle-dark-icon hidden text-lg"></i>
                    <i class="fa-solid fa-sun theme-toggle-light-icon hidden text-lg"></i>
                </button>

                <button id="mobile-menu-btn" type="button" class="p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-brand-600 hover:bg-slate-200 dark:hover:bg-slate-700 focus:outline-none transition-all active:scale-95" aria-label="Open Menu">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>

        </div>
    </div>
</header>

<div id="mobile-backdrop" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 pointer-events-none"></div>

<aside id="mobile-sidebar" class="fixed top-0 right-0 bottom-0 w-4/5 max-w-xs bg-white dark:bg-slate-900 z-50 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col justify-between md:hidden border-l border-slate-100 dark:border-slate-800">
    
    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 via-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-brand-500/20">
                <i class="fa-solid fa-hand-holding-heart text-base"></i>
            </div>
            <span class="font-extrabold text-slate-900 dark:text-white text-base">Peduli<span class="text-brand-600">Umat</span></span>
        </div>
        <button id="mobile-close-btn" type="button" class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition-all">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    <div class="p-6 flex-1 overflow-y-auto space-y-1.5 font-semibold text-sm">
        <?php
        $mobileNavItems = [
            ['url' => '/PeduliUmat/index.php', 'label' => 'Beranda', 'icon' => 'fa-house'],
            ['url' => '/PeduliUmat/views/program.php', 'label' => 'Program Donasi', 'icon' => 'fa-hand-holding-heart'],
            ['url' => '/PeduliUmat/views/kalkulator.php', 'label' => 'Kalkulator Zakat', 'icon' => 'fa-calculator'],
            ['url' => '/PeduliUmat/views/penyaluran.php', 'label' => 'Peta Penyaluran', 'icon' => 'fa-map-location-dot'],
            ['url' => '/PeduliUmat/views/berita.php', 'label' => 'Berita & Artikel', 'icon' => 'fa-newspaper'],
            ['url' => '/PeduliUmat/views/laporan.php', 'label' => 'Laporan Transparansi', 'icon' => 'fa-file-invoice'],
        ];

        foreach ($mobileNavItems as $mItem):
            $mActive = isActiveNav($mItem['url'], $currentUri);
        ?>
            <a href="<?= $mItem['url']; ?>" class="flex items-center justify-between px-4 py-3 rounded-2xl transition-all <?= $mActive ? 'bg-brand-50 dark:bg-slate-800 text-brand-600 dark:text-brand-400 font-bold border-l-4 border-brand-600' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/50 hover:text-brand-600' ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid <?= $mItem['icon']; ?> w-5 text-brand-600"></i>
                    <span><?= $mItem['label']; ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center gap-5">
        <?php if ($isLoggedIn): ?>
            <?php if ($userRole === 'admin'): ?>
                <div class="relative group">
                    <a href="/PeduliUmat/admin/index.php" class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-slate-800 to-slate-900 text-amber-400 flex items-center justify-center shadow-md active:scale-95 transition-all" aria-label="Panel Admin">
                        <i class="fa-solid fa-chart-line text-lg"></i>
                    </a>
                    <div class="absolute bottom-15 left-1/2 -translate-x-1/2 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                        Panel Admin
                    </div>
                </div>
            <?php endif; ?>
            <div class="relative group">
                <a href="/PeduliUmat/auth/logout.php" class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-500 text-white flex items-center justify-center shadow-md active:scale-95 transition-all" aria-label="Keluar">
                    <i class="fa-solid fa-right-from-bracket text-lg"></i>
                </a>
                <div class="absolute bottom-15 left-1/2 -translate-x-1/2 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                    Keluar
                </div>
            </div>
        <?php else: ?>
            <div class="relative group">
                <a href="/PeduliUmat/auth/login.php" class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-500 to-blue-600 text-white flex items-center justify-center shadow-md active:scale-95 transition-all" aria-label="Masuk">
                    <i class="fa-solid fa-right-to-bracket text-lg"></i>
                </a>
                <div class="absolute bottom-15 left-1/2 -translate-x-1/2 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                    Masuk
                </div>
            </div>

            <div class="relative group">
                <a href="/PeduliUmat/auth/register.php" class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-500 to-brand-600 text-white flex items-center justify-center shadow-md active:scale-95 transition-all" aria-label="Daftar Akun Baru">
                    <i class="fa-solid fa-user-plus text-lg"></i>
                </a>
                <div class="absolute bottom-15 left-1/2 -translate-x-1/2 hidden group-hover:block bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-100 text-[11px] font-semibold py-1 px-2.5 rounded-lg shadow-xl whitespace-nowrap z-50">
                    Daftar Akun
                </div>
            </div>
        <?php endif; ?>
    </div>

</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('mobile-close-btn');
    const sidebar = document.getElementById('mobile-sidebar');
    const backdrop = document.getElementById('mobile-backdrop');

    function openMobileMenu() {
        backdrop.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            backdrop.classList.add('opacity-100', 'pointer-events-auto');
            sidebar.classList.remove('translate-x-full');
            sidebar.classList.add('translate-x-0');
        }, 10);
    }

    function closeMobileMenu() {
        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('translate-x-full');
        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0');
        setTimeout(() => {
            backdrop.classList.add('hidden');
            backdrop.classList.remove('pointer-events-auto');
        }, 300);
    }

    if (openBtn && closeBtn && sidebar && backdrop) {
        openBtn.addEventListener('click', openMobileMenu);
        closeBtn.addEventListener('click', closeMobileMenu);
        backdrop.addEventListener('click', closeMobileMenu);
    }

    const toggleBtns = document.querySelectorAll('.theme-toggle-btn');
    const darkIcons = document.querySelectorAll('.theme-toggle-dark-icon');
    const lightIcons = document.querySelectorAll('.theme-toggle-light-icon');

    function updateIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        darkIcons.forEach(icon => isDark ? icon.classList.add('hidden') : icon.classList.remove('hidden'));
        lightIcons.forEach(icon => isDark ? icon.classList.remove('hidden') : icon.classList.add('hidden'));
    }

    updateIcons();

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
            updateIcons();
        });
    });
});
</script>