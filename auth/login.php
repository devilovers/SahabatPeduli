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
                header("Location: /PeduliUmat/admin/index.php");
            } else {
                header("Location: /PeduliUmat/index.php");
            }
            exit;
        } else {
            $error = "Email atau password tidak sesuai.";
        }
    }
}
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-slate-50">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-3xl border border-slate-100 shadow-xl">
        <div class="text-center space-y-2">
            <a href="/PeduliUmat/index.php" class="inline-flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-emerald-400 flex items-center justify-center text-white shadow-md">
                    <i class="fa-solid fa-hand-holding-heart text-xl"></i>
                </div>
                <span class="font-extrabold text-2xl text-slate-900">Peduli<span class="text-brand-600">Umat</span></span>
            </a>
            <h2 class="text-xl font-bold text-slate-900 pt-2">Selamat Datang Kembali</h2>
            <p class="text-xs text-slate-500">Masuk untuk melanjutkan aktivitas donasi Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-xs">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
                <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="nama@email.com">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Masukkan password">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-500/20 transition-all">
                Masuk Akun
            </button>
        </form>

        <p class="text-center text-xs text-slate-500">
            Belum punya akun? <a href="register.php" class="text-brand-600 font-bold hover:underline">Daftar sekarang</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>