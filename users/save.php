<?php
/**
 * Handler simpan (tambah / ubah) user. Khusus Super Admin.
 */
require_once __DIR__ . '/../includes/auth.php';
require_superadmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users/index.php');
}
verify_csrf();

$id           = (int) ($_POST['id'] ?? 0);
$namaLengkap  = trim((string) ($_POST['nama_lengkap'] ?? ''));
$username     = trim((string) ($_POST['username'] ?? ''));
$password     = (string) ($_POST['password'] ?? '');
$role         = ($_POST['role'] ?? 'user') === 'superadmin' ? 'superadmin' : 'user';
$status       = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
$unit         = trim((string) ($_POST['unit'] ?? ''));

if ($namaLengkap === '' || $username === '') {
    set_flash('error', 'Nama lengkap dan username wajib diisi.');
    redirect('users/index.php');
}

$pdo = db();

// Cek keunikan username.
$cek = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
$cek->execute([$username, $id]);
if ($cek->fetch()) {
    set_flash('error', 'Username "' . $username . '" sudah digunakan.');
    redirect('users/index.php');
}

if ($id > 0) {
    // ---------- Ubah user ----------
    $row = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $row->execute([$id]);
    $existing = $row->fetch();

    if (!$existing) {
        set_flash('error', 'Data user tidak ditemukan.');
        redirect('users/index.php');
    }

    // Cegah Super Admin menurunkan role / menonaktifkan akunnya sendiri.
    if ($id === (int) current_user()['id']) {
        $role   = 'superadmin';
        $status = 'aktif';
    }

    $sql = 'UPDATE users SET nama_lengkap = ?, username = ?, role = ?, status = ?, unit = ?';
    $params = [$namaLengkap, $username, $role, $status, $unit !== '' ? $unit : null];

    if ($password !== '') {
        if (strlen($password) < 5) {
            set_flash('error', 'Password minimal 5 karakter.');
            redirect('users/index.php');
        }
        $sql .= ', password = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $sql .= ' WHERE id = ?';
    $params[] = $id;

    $pdo->prepare($sql)->execute($params);

    // Perbarui session bila mengubah akun sendiri.
    if ($id === (int) current_user()['id']) {
        $_SESSION['user']['nama_lengkap'] = $namaLengkap;
        $_SESSION['user']['username']     = $username;
        $_SESSION['user']['unit']         = $unit;
    }

    set_flash('success', 'Data user "' . $namaLengkap . '" berhasil diperbarui.');
} else {
    // ---------- Tambah user ----------
    if (strlen($password) < 5) {
        set_flash('error', 'Password minimal 5 karakter.');
        redirect('users/index.php');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password, nama_lengkap, role, unit, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $username,
        password_hash($password, PASSWORD_DEFAULT),
        $namaLengkap,
        $role,
        $unit !== '' ? $unit : null,
        $status,
    ]);

    set_flash('success', 'User "' . $namaLengkap . '" berhasil ditambahkan.');
}

redirect('users/index.php');
