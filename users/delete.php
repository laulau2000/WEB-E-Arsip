<?php
/**
 * Handler hapus user. Khusus Super Admin.
 */
require_once __DIR__ . '/../includes/auth.php';
require_superadmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users/index.php');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id === (int) current_user()['id']) {
    set_flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
    redirect('users/index.php');
}

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'Data user tidak ditemukan.');
    redirect('users/index.php');
}

// Jangan biarkan sistem kehilangan seluruh Super Admin.
if ($user['role'] === 'superadmin') {
    $sisa = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")->fetchColumn();
    if ($sisa <= 1) {
        set_flash('error', 'Tidak dapat menghapus Super Admin terakhir.');
        redirect('users/index.php');
    }
}

$del = $pdo->prepare('DELETE FROM users WHERE id = ?');
$del->execute([$id]);

set_flash('success', 'User "' . $user['nama_lengkap'] . '" berhasil dihapus.');
redirect('users/index.php');
