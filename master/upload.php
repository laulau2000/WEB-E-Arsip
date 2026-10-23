<?php
/**
 * Handler upload dokumen.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf();

$kode      = trim((string) ($_POST['kode'] ?? ''));
$keterangan = trim((string) ($_POST['keterangan'] ?? ''));
$kategori  = find_category($kode);

if (!$kategori) {
    set_flash('error', 'Kategori tidak ditemukan.');
    redirect('index.php');
}

$backUrl = 'master/kategori.php?kode=' . urlencode($kategori['kode']);

// Pastikan ada file yang diunggah.
if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    set_flash('error', 'Pilih file yang akan diunggah.');
    redirect($backUrl);
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    set_flash('error', 'Gagal mengunggah file (kode error: ' . (int) $file['error'] . ').');
    redirect($backUrl);
}

if ($file['size'] > MAX_UPLOAD_SIZE) {
    set_flash('error', 'Ukuran file melebihi batas ' . format_size(MAX_UPLOAD_SIZE) . '.');
    redirect($backUrl);
}

$namaAsli = basename((string) $file['name']);
$ext      = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
    set_flash('error', 'Tipe file .' . e($ext) . ' tidak diizinkan.');
    redirect($backUrl);
}

ensure_upload_dir();

// Nama file unik di disk agar tidak bentrok.
$storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$target     = UPLOAD_DIR . DIRECTORY_SEPARATOR . $storedName;

if (!move_uploaded_file($file['tmp_name'], $target)) {
    set_flash('error', 'Gagal menyimpan file ke server.');
    redirect($backUrl);
}

$mime = function_exists('mime_content_type') ? (mime_content_type($target) ?: null) : null;

$stmt = db()->prepare(
    'INSERT INTO files (category_id, user_id, uploader_name, unit, nama_file, stored_name, ukuran, tipe, keterangan)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    (int) $kategori['id'],
    (int) current_user()['id'],
    current_user()['nama_lengkap'],
    current_user()['unit'],
    $namaAsli,
    $storedName,
    (int) $file['size'],
    $mime,
    $keterangan !== '' ? $keterangan : null,
]);

set_flash('success', 'File "' . $namaAsli . '" berhasil diunggah.');
redirect($backUrl);
