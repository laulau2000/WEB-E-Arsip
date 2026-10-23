<?php
/**
 * Layout header + sidebar + topbar.
 * Variabel yang dipakai: $page_title (string), $active (string kunci menu aktif).
 */
require_once __DIR__ . '/auth.php';
require_login();

$user       = current_user();
$page_title = $page_title ?? 'Dashboard';
$active     = $active ?? '';
$masterKeys = ['daily_closing', 'denom', 'fp4'];
$masterOpen = in_array($active, $masterKeys, true);
$flash      = take_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> &middot; <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app">

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-logo">PNM</div>
            <div class="brand-text">
                <span class="brand-name">E-Arsip</span>
                <span class="brand-sub"><?= e(APP_TAGLINE) ?></span>
            </div>
        </div>

        <nav class="nav">
            <a class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('index.php')) ?>">
                <?= icon('dashboard') ?><span>Dashboard</span>
            </a>

            <div class="nav-group <?= $masterOpen ? 'open' : '' ?>">
                <button type="button" class="nav-item nav-toggle <?= $masterOpen ? 'active' : '' ?>" data-toggle>
                    <?= icon('folder') ?><span>Arsip Mekaar</span>
                    <svg class="chev" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="nav-sub">
                    <a class="nav-subitem <?= $active === 'daily_closing' ? 'active' : '' ?>" href="<?= e(url('master/kategori.php?kode=daily_closing')) ?>">
                        <span class="dot"></span>Daily Closing
                    </a>
                    <a class="nav-subitem <?= $active === 'denom' ? 'active' : '' ?>" href="<?= e(url('master/kategori.php?kode=denom')) ?>">
                        <span class="dot"></span>Denom
                    </a>
                    <a class="nav-subitem <?= $active === 'fp4' ? 'active' : '' ?>" href="<?= e(url('master/kategori.php?kode=fp4')) ?>">
                        <span class="dot"></span>FP4
                    </a>
                </div>
            </div>

            <a class="nav-item <?= $active === 'report' ? 'active' : '' ?>" href="<?= e(url('report/index.php')) ?>">
                <?= icon('report') ?><span>Report</span>
            </a>

            <?php if (is_superadmin()): ?>
                <a class="nav-item <?= $active === 'users' ? 'active' : '' ?>" href="<?= e(url('users/index.php')) ?>">
                    <?= icon('users') ?><span>Manajemen User</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-foot">
            <div class="sidebar-user">
                <div class="avatar"><?= e(strtoupper(substr($user['nama_lengkap'], 0, 1))) ?></div>
                <div class="su-info">
                    <span class="su-name"><?= e($user['nama_lengkap']) ?></span>
                    <span class="su-role"><?= e($user['role'] === 'superadmin' ? 'Super Admin' : 'Pengguna') ?></span>
                </div>
            </div>
            <button type="button" class="logout-btn" data-modal="modalLogout" title="Keluar"><?= icon('logout', 18) ?></button>
        </div>
    </aside>

    <div class="backdrop" id="sidebar-backdrop"></div>

    <!-- ================= MAIN ================= -->
    <div class="main">
        <header class="topbar">
            <button type="button" class="hamburger" id="hamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">
                <h1><?= e($page_title) ?></h1>
                <p class="topbar-sub"><?= e(APP_NAME) ?> &middot; <?= e(APP_TAGLINE) ?></p>
            </div>
            <div class="topbar-center">
                <form action="<?= e(url('report/index.php')) ?>" method="get" class="search-box global-search">
                    <?= icon('search', 18) ?>
                    <input type="text" name="q" class="input" placeholder="Cari dokumen..." value="<?= e($_GET['q'] ?? '') ?>">
                </form>
            </div>
            <div class="topbar-right">
                <div class="topbar-info-group">
                    <span class="chip chip-unit">
                        <?= icon('folder', 14) ?>
                        <?= e($user['unit'] ?? 'Pusat') ?>
                    </span>
                    <span class="chip chip-role">
                        <?= icon(is_superadmin() ? 'shield' : 'users', 16) ?>
                        <?= e($user['role'] === 'superadmin' ? 'Super Admin' : 'Pengguna') ?>
                    </span>
                </div>
            </div>
        </header>

        <main class="content">
            <?php foreach ($flash as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>">
                    <span><?= e($f['message']) ?></span>
                </div>
            <?php endforeach; ?>
