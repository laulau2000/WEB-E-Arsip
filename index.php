<?php
/**
 * Dashboard - ringkasan sistem.
 */
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo  = db();
$user = current_user();
$isSA = is_superadmin();

// Query filter unit
$whereUnit = $isSA ? '' : ' AND user_id IN (SELECT id FROM users WHERE unit = ' . $pdo->quote($user['unit']) . ')';
$whereFileUnit = $isSA ? '' : ' WHERE unit = ' . $pdo->quote($user['unit']);

$totalFiles = (int) $pdo->query('SELECT COUNT(*) FROM files' . ($isSA ? '' : $whereFileUnit))->fetchColumn();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users' . ($isSA ? '' : ' WHERE unit = ' . $pdo->quote($user['unit'])))->fetchColumn();
$totalSize  = (int) $pdo->query('SELECT COALESCE(SUM(ukuran),0) FROM files' . ($isSA ? '' : $whereFileUnit))->fetchColumn();

$perKategori = $pdo->query(
    'SELECT c.id, c.kode, c.nama, 
     (SELECT COUNT(*) FROM files f WHERE f.category_id = c.id' . $whereUnit . ') AS jumlah
     FROM categories c
     ORDER BY c.urutan ASC'
)->fetchAll();

$recent = $pdo->query(
    'SELECT f.*, c.nama AS kategori, c.kode
     FROM files f
     JOIN categories c ON c.id = f.category_id' .
     ($isSA ? '' : ' WHERE f.unit = ' . $pdo->quote($user['unit'])) .
    ' ORDER BY f.uploaded_at DESC, f.id DESC
     LIMIT 8'
)->fetchAll();

$page_title = 'Dashboard';
$active     = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="kpi-grid fade-in">
    <div class="kpi">
        <div class="kpi-icon"><?= icon('file', 24) ?></div>
        <div>
            <div class="kpi-value"><?= number_format($totalFiles, 0, ',', '.') ?></div>
            <div class="kpi-label">Total Dokumen</div>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-icon green"><?= icon('folder', 24) ?></div>
        <div>
            <div class="kpi-value"><?= count($perKategori) ?></div>
            <div class="kpi-label">Arsip Mekaar</div>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-icon mixed"><?= icon('users', 24) ?></div>
        <div>
            <div class="kpi-value"><?= number_format($totalUsers, 0, ',', '.') ?></div>
            <div class="kpi-label">Total Pengguna</div>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-icon"><?= icon('upload', 24) ?></div>
        <div>
            <div class="kpi-value"><?= e(format_size($totalSize)) ?></div>
            <div class="kpi-label">Total Penyimpanan</div>
        </div>
    </div>
</div>

<div class="grid-2 fade-in" style="display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px;">
    <div class="card chart-card">
        <div class="card-head">
            <div>
                <h2>Statistik Dokumen</h2>
                <p>Distribusi dokumen berdasarkan kategori</p>
            </div>
        </div>
        <div class="card-body">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    <div class="card chart-card">
        <div class="card-head">
            <div>
                <h2>Aktivitas Penyimpanan</h2>
                <p>Penggunaan kuota per unit (Top 5)</p>
            </div>
        </div>
        <div class="card-body">
            <canvas id="unitChart"></canvas>
        </div>
    </div>
</div>

<div class="card fade-in" style="margin-bottom:22px;">
    <div class="card-head">
        <div>
            <h2>Dokumen per Arsip Mekaar</h2>
            <p>Ringkasan jumlah file pada setiap kategori Arsip Mekaar</p>
        </div>
    </div>
    <div class="card-body">
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Jumlah Dokumen</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perKategori as $k): ?>
                        <tr>
                            <td>
                                <div class="cell-file">
                                    <div class="file-badge" style="background:rgba(0,83,155,.08);color:var(--pnm-blue);"><?= icon('folder', 18) ?></div>
                                    <div class="file-name"><?= e($k['nama']) ?></div>
                                </div>
                            </td>
                            <td><span class="badge badge-green"><?= (int) $k['jumlah'] ?> file</span></td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn btn-ghost btn-sm" href="<?= e(url('master/kategori.php?kode=' . $k['kode'])) ?>">
                                        Buka <?= icon('folder', 15) ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card fade-in">
    <div class="card-head">
        <div>
            <h2>Dokumen Terbaru</h2>
            <p>8 dokumen terakhir yang diunggah</p>
        </div>
        <div class="spacer"></div>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('report/index.php')) ?>">Lihat Report</a>
    </div>

    <?php if (!$recent): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon('file', 32) ?></div>
            <h3>Belum ada dokumen</h3>
            <p>Mulai dengan mengunggah dokumen pada menu Arsip Mekaar.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Kategori</th>
                        <th>Diunggah Oleh</th>
                        <th>Tanggal</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $f): ?>
                        <?php $ext = strtolower(pathinfo($f['nama_file'], PATHINFO_EXTENSION)); ?>
                        <tr>
                            <td>
                                <div class="cell-file">
                                    <div class="file-badge <?= e($ext) ?>"><?= e($ext ?: 'file') ?></div>
                                    <div>
                                        <div class="file-name"><?= e($f['nama_file']) ?></div>
                                        <div class="file-meta"><?= e(format_size((int) $f['ukuran'])) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-blue"><?= e($f['kategori']) ?></span></td>
                            <td><?= e($f['uploader_name']) ?></td>
                            <td class="mono" style="font-size:12.5px;color:var(--text-soft);"><?= e(format_datetime($f['uploaded_at'])) ?></td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn btn-green btn-sm" href="<?= e(url('master/download.php?id=' . $f['id'] . '&kode=' . $f['kode'])) ?>">
                                        <?= icon('download', 15) ?> Unduh
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Data untuk Chart Kategori
$catLabels = [];
$catData   = [];
foreach ($perKategori as $k) {
    $catLabels[] = $k['nama'];
    $catData[]   = (int) $k['jumlah'];
}

// Data untuk Chart Unit
$unitStats = $pdo->query(
    'SELECT unit, SUM(ukuran) as total FROM files GROUP BY unit ORDER BY total DESC LIMIT 5'
)->fetchAll();
$unitLabels = [];
$unitData   = [];
foreach ($unitStats as $u) {
    $unitLabels[] = $u['unit'] ?: 'Pusat';
    $unitData[]   = round($u['total'] / (1024 * 1024), 2); // dalam MB
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart Kategori (Doughnut)
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catData) ?>,
                backgroundColor: ['#00539b', '#00a651', '#1e7bd6', '#4cc47a', '#062a4d'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
            },
            cutout: '70%'
        }
    });

    // Chart Unit (Bar)
    new Chart(document.getElementById('unitChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($unitLabels) ?>,
            datasets: [{
                label: 'Ukuran (MB)',
                data: <?= json_encode($unitData) ?>,
                backgroundColor: 'rgba(0, 166, 81, 0.7)',
                borderColor: '#00a651',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { display: false } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
