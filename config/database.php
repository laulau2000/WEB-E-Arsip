<?php
/**
 * Koneksi database menggunakan PDO.
 * Sesuaikan kredensial di bawah bila berbeda dengan instalasi XAMPP Anda.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'db_earsip_pnm');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Mengembalikan objek PDO (singleton).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}
