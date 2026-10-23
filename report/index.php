<?php
/**
 * Report - riwayat upload & statistik dokumen.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();
$user = current_user();
$isSA = is_superadmin();

$fKategori = trim((string) ($_GET['kategori'] ?? ''));
$fUser     = (int) ($_GET['user'] ?? 0);
$fDari     = trim((string) ($_GET['dari'] ?? ''));
$fSampai   = trim((string) ($_GET['sampai'] ?? ''));

$where  = [];
$params = [];

if (!$isSA) {
    $where[] = 'f.unit = ?';
    $params[] = $user['unit'];
}

if ($fKategori !== '') {
    $where[]  = 'c.kode = ?';
    $params[] = $fKategori;
}
if ($fUser > 0) {
    $where[]  = 'f.user_id = ?';
    $params[] = $fUser;
}
if ($fDari !== '') {
    $where[]  = 'DATE(f.uploaded_at) >= ?';
    $params[] = $fDari;
}
if ($fSampai !== '') {
    $where[]  = 'DATE(f.uploaded_at) <= ?';
    $params[] = $fSampai;
}

$sql = 'SELECT f.*, c.nama AS kategori, c.kode
        FROM files f
        JOIN categories c ON c.id = f.category_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY f.uploaded_at DESC, f.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$totalFile = count($rows);
$totalSize = array_sum(array_column($rows, 'ukuran'));
$totalUploader = count(array_unique(array_column($rows, 'uploader_name')));

// Statistik per kategori (mengikuti filter user & tanggal, tanpa filter kategori).
$statSql = 'SELECT c.nama, COUNT(f.id) AS jumlah, COALESCE(SUM(f.ukuran),0) AS ukuran
            FROM categories c
            LEFT JOIN files f ON f.category_id = c.id';
$statWhere  = [];
$statParams = [];

if (!$isSA) {
    $statWhere[] = 'f.unit = ?';
    $statParams[] = $user['unit'];
}

if ($fUser > 0) {
    $statWhere[]  = 'f.user_id = ?';
    $statParams[] = $fUser;
}
if ($fDari !== '') {
    $statWhere[]  = 'DATE(f.uploaded_at) >= ?';
    $statParams[] = $fDari;
}
if ($fSampai !== '') {
    $statWhere[]  = 'DATE(f.uploaded_at) <= ?';
    $statParams[] = $fSampai;
}
if ($statWhere) {
    $statSql .= ' AND ' . implode(' AND ', $statWhere);
}
$statSql .= ' GROUP BY c.id, c.nama ORDER BY c.urutan ASC';

$statKategori = $pdo->prepare($statSql);
$statKategori->execute($statParams);
$statKategori = $statKategori->fetchAll();

// Statistik per pengunggah.
$statUserSql = 'SELECT uploader_name, COUNT(*) AS jumlah, COALESCE(SUM(ukuran),0) AS ukuran
                FROM files';
if (!$isSA) {
    $statUserSql .= ' WHERE unit = ' . $pdo->quote($user['unit']);
}
$statUserSql .= ' GROUP BY uploader_name ORDER BY jumlah DESC LIMIT 10';

$statUser = $pdo->query($statUserSql)->fetchAll();

$kategoris   = all_categories();
$userOptSql = 'SELECT id, nama_lengkap FROM users';
if (!$isSA) {
    $userOptSql .= ' WHERE unit = ' . $pdo->quote($user['unit']);
}
$userOptSql .= ' ORDER BY nama_lengkap ASC';
$userOptions = $pdo->query($userOptSql)->fetchAll();

$page_title = 'Report';
$active     = 'report';
require __DIR__ . '/../includes/header.php';
?>

<!-- ============ Filter ============ -->
<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <div>
            <h2>Filter Report</h2>
            <p>Saring riwayat dokumen berdasarkan kategori, pengguna, dan rentang tanggal</p>
        </div>
    </div>
    <div class="card-body">
        <form method="get" action="<?= e(url('report/index.php')) ?>">
            <div class="form-grid" style="grid-template-columns:repeat(4,1fr);">
                <div class="field">
                    <label>Kategori</label>
                    <select class="select" name="kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoris as $k): ?>
                            <option value="<?= e($k['kode']) ?>" <?= $fKategori === $k['kode'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Pengguna</label>
                    <select class="select" name="user">
                        <option value="0">Semua Pengguna</option>
                        <?php foreach ($userOptions as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?= $fUser === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['nama_lengkap']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Dari Tanggal</label>
                    <input class="input" type="date" name="dari" value="<?= e($fDari) ?>">
                </div>
                <div class="field">
                    <label>Sampai Tanggal</label>
                    <input class="input" type="date" name="sampai" value="<?= e($fSampai) ?>">
                </div>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn btn-primary" type="submit"><?= icon('search', 17) ?> Terapkan Filter</button>
                <a class="btn btn-ghost" href="<?= e(url('report/index.php')) ?>">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- ============ Ringkasan ============ -->
<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-icon"><?= icon('file', 24) ?></div>
        <div>
            <div class="kpi-value"><?= number_format($totalFile, 0, ',', '.') ?></div>
            <div class="kpi-label">Dokumen (sesuai filter)</div>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-icon green"><?= icon('upload', 24) ?></div>
        <div>
            <div class="kpi-value"><?= e(format_size((int) $totalSize)) ?></div>
            <div class="kpi-label">Total Ukuran</div>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-icon mixed"><?= icon('users', 24) ?></div>
        <div>
            <div class="kpi-value"><?= number_format($totalUploader, 0, ',', '.') ?></div>
            <div class="kpi-label">Pengunggah Unik</div>
        </div>
    </div>
</div>

<div class="form-grid" style="grid-template-columns:1fr 1fr;align-items:start;gap:18px;">
    <div class="card">
        <div class="card-head"><div><h2>Rekap per Kategori</h2><p>Jumlah dan ukuran dokumen</p></div></div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>Kategori</th><th>Jumlah</th><th>Ukuran</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($statKategori as $s): ?>
                        <tr>
                            <td class="file-name"><?= e($s['nama']) ?></td>
                            <td><span class="badge badge-green"><?= (int) $s['jumlah'] ?></span></td>
                            <td class="mono" style="font-size:12.5px;"><?= e(format_size((int) $s['ukuran'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><div><h2>Top Pengunggah</h2><p>10 pengguna dengan dokumen terbanyak</p></div></div>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr><th>Pengunggah</th><th>Jumlah</th><th>Ukuran</th></tr>
                </thead>
                <tbody>
                    <?php if (!$statUser): ?>
                        <tr><td colspan="3" style="text-align:center;color:var(--text-faint);">Belum ada data</td></tr>
                    <?php else: ?>
                        <?php foreach ($statUser as $s): ?>
                            <tr>
                                <td class="file-name"><?= e($s['uploader_name']) ?></td>
                                <td><span class="badge badge-blue"><?= (int) $s['jumlah'] ?></span></td>
                                <td class="mono" style="font-size:12.5px;"><?= e(format_size((int) $s['ukuran'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============ Riwayat ============ -->
<div class="card" style="margin-top:18px;">
    <div class="card-head">
        <div>
            <h2>Riwayat Upload</h2>
            <p><?= number_format($totalFile, 0, ',', '.') ?> dokumen ditemukan</p>
        </div>
        <div class="spacer"></div>
        <div class="search-box">
            <?= icon('search', 18) ?>
            <input class="input" type="text" placeholder="Cari cepat..." data-table-search="tblRiwayat">
        </div>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon('report', 32) ?></div>
            <h3>Tidak ada data</h3>
            <p>Tidak ada dokumen yang sesuai dengan filter yang dipilih.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data" id="tblRiwayat">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama File</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th>Pengunggah</th>
                        <th>Ukuran</th>
                        <th>Tanggal</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $i => $r): ?>
                        <?php $ext = strtolower(pathinfo($r['nama_file'], PATHINFO_EXTENSION)); ?>
                        <tr>
                            <td class="mono" style="font-size:12.5px;color:var(--text-faint);"><?= $i + 1 ?></td>
                            <td>
                                <div class="cell-file">
                                    <div class="file-badge <?= e($ext) ?>"><?= e($ext ?: 'file') ?></div>
                                    <div class="file-name"><?= e($r['nama_file']) ?></div>
                                </div>
                            </td>
                            <td><span class="badge badge-blue"><?= e($r['kategori']) ?></span></td>
                            <td><?= e($r['keterangan'] ?: '-') ?></td>
                            <td><?= e($r['uploader_name']) ?></td>
                            <td class="mono" style="font-size:12.5px;"><?= e(format_size((int) $r['ukuran'])) ?></td>
                            <td class="mono" style="font-size:12.5px;color:var(--text-soft);"><?= e(format_datetime($r['uploaded_at'])) ?></td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn btn-green btn-sm" href="<?= e(url('master/download.php?id=' . $r['id'] . '&kode=' . $r['kode'])) ?>"><?= icon('download', 15) ?> Unduh</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
