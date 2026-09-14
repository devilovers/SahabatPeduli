<?php
$page_title = "Masuk Akun";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email dan password wajib diisi.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: /SahabatPeduli/admin/index.php");
            } else {
                header("Location: /SahabatPeduli/index.php");
            }
            exit;
        } else {
            $error = "Email atau password tidak sesuai.";
        }
    }
}
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-md w-full space-y-8 bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xl transition-colors">
        <div class="text-center space-y-2">
            <a href="/SahabatPeduli/index.php" class="inline-flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white shadow-md">
                    <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                </div>
                <span class="font-extrabold text-2xl text-slate-900 dark:text-white">Sahabat<span class="text-brand-600 dark:text-brand-400">Peduli</span></span>
            </a>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white pt-2">Selamat Datang Kembali</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Masuk untuk melanjutkan aktivitas donasi Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-2xl text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alamat Email</label>
                <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none transition-colors" placeholder="nama@email.com">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none transition-colors" placeholder="Masukkan password">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-500/20 transition-all">
                Masuk Akun
            </button>
        </form>

        <p class="text-center text-xs text-slate-500 dark:text-slate-400">
            Belum punya akun? <a href="register.php" class="text-brand-600 dark:text-brand-400 font-bold hover:underline">Daftar sekarang</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>