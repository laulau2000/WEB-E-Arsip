<?php
/**
 * Kumpulan fungsi bantu (helper) aplikasi.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/** Escape output HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Bangun URL absolut berdasarkan BASE_URL. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Redirect ke halaman lain lalu hentikan eksekusi. */
function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/** Simpan pesan flash (notifikasi) ke session. */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Ambil & hapus semua pesan flash. */
function take_flash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/** Ambil / buat token CSRF. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Field input hidden berisi token CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Validasi token CSRF dari request POST. */
function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Token CSRF tidak valid. Silakan muat ulang halaman.');
    }
}

/** Format ukuran byte menjadi satuan yang mudah dibaca. */
function format_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    $units = ['KB', 'MB', 'GB', 'TB'];
    $value = $bytes / 1024;
    $i = 0;
    while ($value >= 1024 && $i < count($units) - 1) {
        $value /= 1024;
        $i++;
    }
    return number_format($value, 1, ',', '.') . ' ' . $units[$i];
}

/** Format tanggal & waktu ke format Indonesia. */
function format_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
    ];
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '-';
    }
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y H:i', $ts);
}

/** Pastikan folder upload tersedia. */
function ensure_upload_dir(): void
{
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
}

/** Ambil data user yang sedang login (dari session). */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Cek apakah user yang login adalah superadmin. */
function is_superadmin(): bool
{
    $user = current_user();
    return $user !== null && ($user['role'] ?? '') === 'superadmin';
}

/** Ambil daftar kategori Arsip Mekaar berdasarkan kode. */
function find_category(string $kode): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE kode = ? LIMIT 1');
    $stmt->execute([$kode]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Ambil semua kategori terurut. */
function all_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY urutan ASC, id ASC')->fetchAll();
}

/** Ikon sederhana (SVG inline) berdasarkan nama. */
function icon(string $name, int $size = 20): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'folder'    => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/>',
        'report'    => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'users'     => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 6.5a3 3 0 0 1 0 6"/><path d="M17.5 20a5 5 0 0 0-3-4.6"/>',
        'upload'    => '<path d="M12 16V4"/><path d="M7 9l5-5 5 5"/><path d="M5 20h14"/>',
        'download'  => '<path d="M12 4v12"/><path d="M7 11l5 5 5-5"/><path d="M5 20h14"/>',
        'trash'     => '<path d="M4 7h16"/><path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"/>',
        'logout'    => '<path d="M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'file'      => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/>',
        'close'     => '<path d="M6 6l12 12M18 6L6 18"/>',
        'edit'      => '<path d="M4 20h4L19 9a2 2 0 0 0-3-3L5 17v3z"/><path d="M14 6l4 4"/>',
        'check'     => '<path d="M5 13l4 4L19 7"/>',
        'shield'    => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/>',
    ];
    $d = $paths[$name] ?? $paths['file'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
}
