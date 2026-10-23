<?php
/**
 * Konfigurasi umum aplikasi E-Arsip PNM.
 */

// Nama aplikasi
define('APP_NAME', 'E-Arsip PNM');
define('APP_TAGLINE', 'Bangka Belitung');

// Base URL aplikasi (sesuai lokasi folder di htdocs)
define('BASE_URL', '/inventory');

// Path fisik aplikasi & folder penyimpanan file
define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads');

// Batas ukuran file upload (dalam byte). 0 = tanpa batas
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20 MB

// Ekstensi yang diizinkan
define('ALLOWED_EXTENSIONS', [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'csv', 'jpg', 'jpeg', 'png', 'zip', 'rar',
]);

// Zona waktu
date_default_timezone_set('Asia/Jakarta');

// Mulai session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
