<?php
/**
 * Autentikasi & otorisasi.
 */

require_once __DIR__ . '/functions.php';

/** Wajib login; jika belum, alihkan ke halaman login. */
function require_login(): void
{
    if (!current_user()) {
        set_flash('warning', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
}

/** Wajib superadmin; jika tidak, tolak akses. */
function require_superadmin(): void
{
    require_login();
    if (!is_superadmin()) {
        http_response_code(403);
        set_flash('error', 'Akses ditolak. Halaman ini khusus Super Admin.');
        redirect('index.php');
    }
}

/** Proses login berdasarkan username & password. */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    if ($user['status'] !== 'aktif') {
        return false;
    }

    // Simpan data penting ke session (tanpa password).
    $_SESSION['user'] = [
        'id'           => (int) $user['id'],
        'username'     => $user['username'],
        'nama_lengkap' => $user['nama_lengkap'],
        'role'         => $user['role'],
        'unit'         => $user['unit'],
    ];

    return true;
}

/** Hapus session login. */
function logout(): void
{
    unset($_SESSION['user']);
    unset($_SESSION['csrf_token']);
}
