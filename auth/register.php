<?php
$page_title = "Daftar Akun";
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = 'muzakki';

    if (empty($name)) $errors[] = "Nama lengkap wajib diisi.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";
    if (empty($password) || strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";

    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        $errors[] = "Email sudah terdaftar. Silakan gunakan email lain.";
    }

    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
            $stmtUser->execute([$name, $email, $hashedPassword, $role, $phone]);

            $success = "Pendaftaran berhasil! Silakan masuk ke akun Anda.";
        } catch (Exception $e) {
            $errors[] = "Gagal mendaftar. Terjadi kesalahan sistem: " . $e->getMessage();
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
            <h2 class="text-xl font-bold text-slate-900 pt-2">Buat Akun Donatur</h2>
            <p class="text-xs text-slate-500">Bergabung bersama kami untuk kebaikan bersama</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl text-xs space-y-1">
                <?php foreach ($errors as $error): ?>
                    <p><i class="fa-solid fa-circle-exclamation mr-1"></i> <?= htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-brand-800 px-4 py-3 rounded-2xl text-xs flex justify-between items-center">
                <span><i class="fa-solid fa-circle-check mr-1"></i> <?= htmlspecialchars($success); ?></span>
                <a href="login.php" class="font-bold underline">Masuk</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Masukkan nama lengkap">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
                <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="nama@email.com">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp / HP</label>
                <input type="text" name="phone" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="08xxxxxxxxxx">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-brand-500 focus:outline-none" placeholder="Minimal 6 karakter">
            </div>

            <button type="submit" class="w-full py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-500/20 transition-all">
                Daftar Sekarang
            </button>
        </form>

        <p class="text-center text-xs text-slate-500">
            Sudah punya akun? <a href="login.php" class="text-brand-600 font-bold hover:underline">Masuk di sini</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>