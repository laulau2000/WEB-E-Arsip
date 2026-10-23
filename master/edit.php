<?php
/**
 * Handler edit dokumen (nama file & keterangan).
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf();

$id         = (int) ($_POST['id'] ?? 0);
$kode       = trim((string) ($_POST['kode'] ?? ''));
$namaBaru   = trim((string) ($_POST['nama_file'] ?? ''));
$keterangan = trim((string) ($_POST['keterangan'] ?? ''));

$pdo  = db();
$stmt = $pdo->prepare('SELECT * FROM files WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    set_flash('error', 'File tidak ditemukan.');
    redirect('master/kategori.php?kode=' . urlencode($kode));
}

$bolehEdit = is_superadmin() || (int) $file['user_id'] === (int) current_user()['id'];
if (!$bolehEdit) {
    set_flash('error', 'Anda tidak berhak mengedit file ini.');
    redirect('master/kategori.php?kode=' . urlencode($kode));
}

if ($namaBaru === '') {
    set_flash('error', 'Nama file tidak boleh kosong.');
    redirect('master/kategori.php?kode=' . urlencode($kode));
}

// Tambahkan ekstensi asli kembali
$ext = pathinfo($file['nama_file'], PATHINFO_EXTENSION);
$namaFileLengkap = $namaBaru . ($ext ? '.' . $ext : '');

$upd = $pdo->prepare('UPDATE files SET nama_file = ?, keterangan = ? WHERE id = ?');
$upd->execute([$namaFileLengkap, $keterangan !== '' ? $keterangan : null, $id]);

set_flash('success', 'Informasi dokumen berhasil diperbarui.');
redirect('master/kategori.php?kode=' . urlencode($kode));
