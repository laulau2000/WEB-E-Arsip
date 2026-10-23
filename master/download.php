<?php
/**
 * Handler download dokumen.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT * FROM files WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    set_flash('error', 'File tidak ditemukan.');
    redirect('index.php');
}

$path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $file['stored_name'];

if (!is_file($path)) {
    set_flash('error', 'File fisik tidak ditemukan di server.');
    $kode = trim((string) ($_GET['kode'] ?? ''));
    if ($kode !== '') {
        redirect('master/kategori.php?kode=' . urlencode($kode));
    }
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

// Bersihkan buffer agar isi file tidak rusak.
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file['nama_file']) . '"');
header('Content-Length: ' . filesize($path));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('X-Content-Type-Options: nosniff');

readfile($path);
exit;
