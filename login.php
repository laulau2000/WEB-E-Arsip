<?php
/**
 * Halaman login.
 */
require_once __DIR__ . '/includes/auth.php';

// Jika sudah login, langsung ke dashboard.
if (current_user()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (attempt_login($username, $password)) {
        set_flash('success', 'Selamat datang, ' . current_user()['nama_lengkap'] . '!');
        redirect('index.php');
    } else {
        $error = 'Username atau password salah, atau akun tidak aktif.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &middot; <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<div class="login-page">

    <!-- Panel kiri: branding -->
    <section class="login-hero">
        <div class="lh-logo">
            <span>PNM</span>
            <div>
                <div style="font-weight:800;font-size:19px;"><?= e(APP_NAME) ?></div>
                <div style="font-size:11.5px;color:#9dc0e0;text-transform:uppercase;letter-spacing:.5px;"><?= e(APP_TAGLINE) ?></div>
            </div>
        </div>

        <div>
            <h1>Solusi Efisiensi<br>Penyimpanan Dokumen</h1>
            <p>Kelola, simpan, dan distribusikan dokumen kantor Anda dalam satu sistem yang aman dan terpadu.</p>
            <ul class="lh-list">
                <li><span class="tick"><?= icon('check', 14) ?></span> Arsip Mekaar: Daily Closing, Denom, FP4</li>
                <li><span class="tick"><?= icon('check', 14) ?></span> Upload &amp; download file per pengguna</li>
                <li><span class="tick"><?= icon('check', 14) ?></span> Manajemen pengguna oleh Super Admin</li>
                <li><span class="tick"><?= icon('check', 14) ?></span> Laporan riwayat &amp; statistik dokumen</li>
            </ul>
        </div>

        <div style="font-size:12px;color:#9dc0e0;">
            &copy; <?= date('Y') ?> PT Permodalan Nasional Madani by AUL
        </div>
    </section>

    <!-- Panel kanan: form login -->
    <section class="login-panel">
        <form class="login-card" method="post" action="<?= e(url('login.php')) ?>" autocomplete="off">
            <?= csrf_field() ?>
            <h2>Masuk ke Sistem</h2>
            <p class="lc-sub">Silakan masukkan kredensial akun Anda.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="field">
                <label for="username">Username</label>
                <input class="input" type="text" id="username" name="username" placeholder="contoh: superadmin" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input class="input" type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button class="btn btn-primary" type="submit">
                <?= icon('logout', 18) ?> Masuk
            </button>
        </form>
    </section>
</div>
</body>
</html>
