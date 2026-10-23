<?php
/**
 * Manajemen User (khusus Super Admin).
 */
require_once __DIR__ . '/../includes/auth.php';
require_superadmin();

$users = db()->query(
    'SELECT u.*, (SELECT COUNT(*) FROM files f WHERE f.user_id = u.id) AS jumlah_file
     FROM users u
     ORDER BY u.role ASC, u.nama_lengkap ASC'
)->fetchAll();

$page_title = 'Manajemen User';
$active     = 'users';
require __DIR__ . '/../includes/header.php';
?>

<div class="kpi-grid">
    <div class="kpi">
        <div class="kpi-icon"><?= icon('users', 24) ?></div>
        <div>
            <div class="kpi-value"><?= count($users) ?></div>
            <div class="kpi-label">Total Pengguna</div>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-icon mixed"><?= icon('shield', 24) ?></div>
        <div>
            <div class="kpi-value"><?= count(array_filter($users, fn($u) => $u['role'] === 'superadmin')) ?></div>
            <div class="kpi-label">Super Admin</div>
        </div>
    </div>
    <div class="kpi">
        <div class="kpi-icon green"><?= icon('check', 24) ?></div>
        <div>
            <div class="kpi-value"><?= count(array_filter($users, fn($u) => $u['status'] === 'aktif')) ?></div>
            <div class="kpi-label">Akun Aktif</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <div>
            <h2>Daftar Pengguna</h2>
            <p>Tambah, ubah, dan hapus akun pengguna sistem</p>
        </div>
        <div class="spacer"></div>
        <button class="btn btn-green" data-modal="modalTambahUser"><?= icon('plus', 17) ?> Tambah User</button>
    </div>

    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Dokumen</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="cell-file">
                                <div class="avatar" style="width:34px;height:34px;font-size:13px;"><?= e(strtoupper(substr($u['nama_lengkap'], 0, 1))) ?></div>
                                <div class="file-name"><?= e($u['nama_lengkap']) ?></div>
                            </div>
                        </td>
                        <td class="mono" style="font-size:12.5px;"><?= e($u['username']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'superadmin'): ?>
                                <span class="badge badge-blue"><?= icon('shield', 13) ?> Super Admin</span>
                            <?php else: ?>
                                <span class="badge badge-grey">Pengguna</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['unit'] ?: '-') ?></td>
                        <td>
                            <?php if ($u['status'] === 'aktif'): ?>
                                <span class="badge badge-green">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-red">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-blue"><?= (int) $u['jumlah_file'] ?></span></td>
                        <td>
                            <div class="row-actions">
                                <button class="btn btn-ghost btn-sm btn-icon" data-modal="editUser<?= (int) $u['id'] ?>" title="Ubah"><?= icon('edit', 15) ?></button>
                                <?php if ((int) $u['id'] !== (int) current_user()['id']): ?>
                                    <form method="post" action="<?= e(url('users/delete.php')) ?>" data-confirm="Hapus akun &quot;<?= e($u['nama_lengkap']) ?>&quot;?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="btn btn-danger btn-sm btn-icon" type="submit" title="Hapus"><?= icon('trash', 15) ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============ Modal Tambah User ============ -->
<template id="modalTambahUser">
    <form method="post" action="<?= e(url('users/save.php')) ?>">
        <?= csrf_field() ?>
        <div class="modal-head">
            <div class="kpi-icon green" style="width:38px;height:38px;border-radius:10px;"><?= icon('plus', 19) ?></div>
            <h3>Tambah Pengguna Baru</h3>
            <button type="button" class="modal-close" data-modal-close><?= icon('close', 18) ?></button>
        </div>
        <div class="modal-body">
            <div class="field">
                <label>Nama Lengkap *</label>
                <input class="input" type="text" name="nama_lengkap" maxlength="100" required>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label>Username *</label>
                    <input class="input" type="text" name="username" maxlength="50" required autocomplete="off">
                </div>
                <div class="field">
                    <label>Password *</label>
                    <input class="input" type="text" name="password" minlength="5" required autocomplete="new-password">
                </div>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label>Role *</label>
                    <select class="select" name="role" required>
                        <option value="user">Pengguna</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>
                <div class="field">
                    <label>Status *</label>
                    <select class="select" name="status" required>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label>Unit / Bagian</label>
                <input class="input" type="text" name="unit" maxlength="100" placeholder="contoh: Unit Mikro">
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
            <button type="submit" class="btn btn-primary"><?= icon('check', 17) ?> Simpan</button>
        </div>
    </form>
</template>

<!-- ============ Modal Edit User ============ -->
<?php foreach ($users as $u): ?>
    <template id="editUser<?= (int) $u['id'] ?>">
        <form method="post" action="<?= e(url('users/save.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
            <div class="modal-head">
                <div class="kpi-icon" style="width:38px;height:38px;border-radius:10px;"><?= icon('edit', 19) ?></div>
                <h3>Ubah Pengguna</h3>
                <button type="button" class="modal-close" data-modal-close><?= icon('close', 18) ?></button>
            </div>
            <div class="modal-body">
                <div class="field">
                    <label>Nama Lengkap *</label>
                    <input class="input" type="text" name="nama_lengkap" maxlength="100" value="<?= e($u['nama_lengkap']) ?>" required>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Username *</label>
                        <input class="input" type="text" name="username" maxlength="50" value="<?= e($u['username']) ?>" required>
                    </div>
                    <div class="field">
                        <label>Password <span style="font-weight:400;">(kosongkan bila tidak diubah)</span></label>
                        <input class="input" type="text" name="password" minlength="5" placeholder="••••••" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label>Role *</label>
                        <select class="select" name="role" required>
                            <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>Pengguna</option>
                            <option value="superadmin" <?= $u['role'] === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Status *</label>
                        <select class="select" name="status" required>
                            <option value="aktif" <?= $u['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $u['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label>Unit / Bagian</label>
                    <input class="input" type="text" name="unit" maxlength="100" value="<?= e($u['unit']) ?>">
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary"><?= icon('check', 17) ?> Simpan Perubahan</button>
            </div>
        </form>
    </template>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
