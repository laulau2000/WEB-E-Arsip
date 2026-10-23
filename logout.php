<?php
/**
 * Proses logout.
 */
require_once __DIR__ . '/includes/auth.php';

logout();
set_flash('info', 'Anda telah keluar dari sistem.');
redirect('login.php');
