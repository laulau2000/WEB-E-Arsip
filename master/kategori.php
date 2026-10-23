<?php
/**
 * Halaman Master: daftar dokumen per kategori (Daily Closing / Denom / FP4).
 * Parameter: ?kode=<kode_kategori>
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$kode = trim((string) ($_GET['kode'] ?? ''));
$kategori = find_category($kode);

if (!$kategori) {
    set_flash('error', 'Kategori tidak ditemukan.');
    redirect('index.php');
}

$user = current_user();
$isSA = is_superadmin();
$q    = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT f.* FROM files f WHERE f.category_id = ?';
$params = [(int) $kategori['id']];

if (!$isSA) {
    $sql .= ' AND f.unit = ?';
    $params[] = $user['unit'];
}

if ($q !== '') {
    $sql .= ' AND (f.nama_file LIKE ? OR f.uploader_name LIKE ? OR f.keterangan LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$sql .= ' ORDER BY f.uploaded_at DESC, f.id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$files = $stmt->fetchAll();

$page_title = $kategori['nama'];
$active     = $kategori['kode'];
require __DIR__ . '/../includes/header.php';
?>

<div class="card" style="margin-bottom:18px;">
    <div class="card-head">
        <div class="kpi-icon" style="width:44px;height:44px;border-radius:12px;"><?= icon('folder', 22) ?></div>
        <div>
            <h2><?= e($kategori['nama']) ?></h2>
            <p><?= e($kategori['deskripsi'] ?: 'Arsip Mekaar') ?></p>
        </div>
        <div class="spacer"></div>
        <button class="btn btn-green" data-modal="modalUpload"><?= icon('upload', 17) ?> Upload File</button>
    </div>
</div>

<div class="toolbar">
    <form class="search-box" method="get" action="<?= e(url('master/kategori.php')) ?>">
        <input type="hidden" name="kode" value="<?= e($kategori['kode']) ?>">
        <?= icon('search', 18) ?>
        <input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama file / pengunggah...">
    </form>
    <?php if ($q !== ''): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('master/kategori.php?kode=' . $kategori['kode'])) ?>"><?= icon('close', 15) ?> Reset</a>
    <?php endif; ?>
    <div class="spacer"></div>
    <span class="badge badge-blue"><?= count($files) ?> dokumen</span>
</div>

<div class="card">
    <?php if (!$files): ?>
        <div class="empty">
            <div class="empty-icon"><?= icon('upload', 32) ?></div>
            <h3>Belum ada dokumen</h3>
            <p>Klik tombol <b>Upload File</b> untuk menambahkan dokumen pada kategori ini.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Ukuran</th>
                        <th>Keterangan</th>
                        <th>Diunggah Oleh</th>
                        <th>Tanggal</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $f): ?>
                        <?php
                        $ext = strtolower(pathinfo($f['nama_file'], PATHINFO_EXTENSION));
                        $canDelete = is_superadmin() || (int) $f['user_id'] === (int) $user['id'];
                        ?>
                        <tr>
                            <td>
                                <div class="cell-file">
                                    <div class="file-badge <?= e($ext) ?>"><?= e($ext ?: 'file') ?></div>
                                    <div class="file-name"><?= e($f['nama_file']) ?></div>
                                </div>
                            </td>
                            <td class="mono" style="font-size:12.5px;"><?= e(format_size((int) $f['ukuran'])) ?></td>
                            <td><?= e($f['keterangan'] ?: '-') ?></td>
                            <td><?= e($f['uploader_name']) ?></td>
                            <td class="mono" style="font-size:12.5px;color:var(--text-soft);"><?= e(format_datetime($f['uploaded_at'])) ?></td>
                            <td>
                                <div class="row-actions">
                                    <button class="btn btn-ghost btn-sm btn-icon" data-modal="modalLihat<?= (int) $f['id'] ?>" title="Lihat Detail"><?= icon('search', 15) ?></button>
                                    <?php if ($canDelete): ?>
                                        <button class="btn btn-ghost btn-sm btn-icon" data-modal="modalEdit<?= (int) $f['id'] ?>" title="Edit"><?= icon('edit', 15) ?></button>
                                    <?php endif; ?>
                                    <a class="btn btn-green btn-sm" href="<?= e(url('master/download.php?id=' . $f['id'] . '&kode=' . $kategori['kode'])) ?>" title="Download">
                                        <?= icon('download', 15) ?> Unduh
                                    </a>
                                    <?php if ($canDelete): ?>
                                        <form method="post" action="<?= e(url('master/delete.php')) ?>" data-confirm="Hapus file &quot;<?= e($f['nama_file']) ?>&quot; secara permanen?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                            <input type="hidden" name="kode" value="<?= e($kategori['kode']) ?>">
                                            <button class="btn btn-danger btn-sm btn-icon" type="submit" title="Hapus"><?= icon('trash', 15) ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Lihat Detail -->
                        <template id="modalLihat<?= (int) $f['id'] ?>">
                            <div class="modal-head">
                                <div class="kpi-icon" style="width:38px;height:38px;border-radius:10px;"><?= icon('file', 19) ?></div>
                                <h3>Detail Dokumen</h3>
                                <button type="button" class="modal-close" data-modal-close><?= icon('close', 18) ?></button>
                            </div>
                            <div class="modal-body">
                                <table class="data" style="width:100%">
                                    <tr><td style="width:30%;font-weight:600">Nama File</td><td><?= e($f['nama_file']) ?></td></tr>
                                    <tr><td style="font-weight:600">Kategori</td><td><?= e($kategori['nama']) ?></td></tr>
                                    <tr><td style="font-weight:600">Ukuran</td><td><?= e(format_size((int) $f['ukuran'])) ?></td></tr>
                                    <tr><td style="font-weight:600">Tipe</td><td><?= e($f['tipe'] ?: '-') ?></td></tr>
                                    <tr><td style="font-weight:600">Keterangan</td><td><?= e($f['keterangan'] ?: '-') ?></td></tr>
                                    <tr><td style="font-weight:600">Pengunggah</td><td><?= e($f['uploader_name']) ?></td></tr>
                                    <tr><td style="font-weight:600">Unit</td><td><?= e($f['unit'] ?: '-') ?></td></tr>
                                    <tr><td style="font-weight:600">Tanggal Upload</td><td><?= e(format_datetime($f['uploaded_at'])) ?></td></tr>
                                </table>
                            </div>
                            <div class="modal-foot">
                                <button type="button" class="btn btn-ghost" data-modal-close>Tutup</button>
                                <a class="btn btn-primary" href="<?= e(url('master/download.php?id=' . $f['id'] . '&kode=' . $kategori['kode'])) ?>"><?= icon('download', 15) ?> Unduh</a>
                            </div>
                        </template>

                        <!-- Modal Edit Dokumen -->
                        <template id="modalEdit<?= (int) $f['id'] ?>">
                            <form method="post" action="<?= e(url('master/edit.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
                                <input type="hidden" name="kode" value="<?= e($kategori['kode']) ?>">
                                <div class="modal-head">
                                    <div class="kpi-icon" style="width:38px;height:38px;border-radius:10px;"><?= icon('edit', 19) ?></div>
                                    <h3>Edit Dokumen</h3>
                                    <button type="button" class="modal-close" data-modal-close><?= icon('close', 18) ?></button>
                                </div>
                                <div class="modal-body">
                                    <div class="field">
                                        <label>Nama File</label>
                                        <input class="input" type="text" name="nama_file" value="<?= e(pathinfo($f['nama_file'], PATHINFO_FILENAME)) ?>" required>
                                        <p style="font-size:11px;color:var(--text-faint);margin-top:4px">Ekstensi (.<?= e($ext) ?>) akan ditambahkan otomatis.</p>
                                    </div>
                                    <div class="field">
                                        <label>Keterangan</label>
                                        <input class="input" type="text" name="keterangan" value="<?= e($f['keterangan']) ?>" placeholder="Deskripsi singkat...">
                                    </div>
                                </div>
                                <div class="modal-foot">
                                    <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                                    <button type="submit" class="btn btn-primary"><?= icon('check', 17) ?> Simpan Perubahan</button>
                                </div>
                            </form>
                        </template>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============ Template Modal Upload ============ -->
<template id="modalUpload">
    <form method="post" action="<?= e(url('master/upload.php')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="kode" value="<?= e($kategori['kode']) ?>">

        <div class="modal-head">
            <div class="kpi-icon" style="width:38px;height:38px;border-radius:10px;"><?= icon('upload', 19) ?></div>
            <h3>Upload Dokumen &middot; <?= e($kategori['nama']) ?></h3>
            <button type="button" class="modal-close" data-modal-close><?= icon('close', 18) ?></button>
        </div>

        <div class="modal-body">
            <div class="dropzone">
                <?= icon('upload', 30) ?>
                <div class="dz-title">Klik atau seret file ke sini</div>
                <div class="dz-sub">Maks <?= e(format_size(MAX_UPLOAD_SIZE)) ?> &middot; <?= e(implode(', ', ALLOWED_EXTENSIONS)) ?></div>
                <input type="file" name="file" required>
                <div class="dz-file"><?= icon('check', 16) ?> <span class="dz-filename"></span></div>
            </div>

            <div class="field" style="margin-top:16px;">
                <label for="keterangan">Keterangan (opsional)</label>
                <input class="input" type="text" id="keterangan" name="keterangan" maxlength="255" placeholder="Deskripsi singkat dokumen...">
            </div>
        </div>

        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
            <button type="submit" class="btn btn-primary"><?= icon('upload', 17) ?> Upload</button>
        </div>
    </form>
</template>

<?php require __DIR__ . '/../includes/footer.php'; ?>
