<?php
/**
 * Handler hapus dokumen.
 * Hak hapus: Super Admin (semua file) atau pengunggah file tersebut.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf();

$id   = (int) ($_POST['id'] ?? 0);
$kode = trim((string) ($_POST['kode'] ?? ''));

$stmt = db()->prepare('SELECT * FROM files WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    set_flash('error', 'File tidak ditemukan.');
    if ($kode !== '') {
        redirect('master/kategori.php?kode=' . urlencode($kode));
    }
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

$bolehHapus = is_superadmin() || (int) $file['user_id'] === (int) current_user()['id'];
if (!$bolehHapus) {
    set_flash('error', 'Anda tidak berhak menghapus file ini.');
    if ($kode !== '') {
        redirect('master/kategori.php?kode=' . urlencode($kode));
    }
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

// Hapus file fisik lalu catatan database.
$path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $file['stored_name'];
if (is_file($path)) {
    unlink($path);
}

$del = db()->prepare('DELETE FROM files WHERE id = ?');
$del->execute([$id]);

set_flash('success', 'File "' . $file['nama_file'] . '" berhasil dihapus.');
if ($kode !== '') {
    redirect('master/kategori.php?kode=' . urlencode($kode));
}
redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
