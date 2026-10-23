        </main>

        <footer class="footer">
            <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; PT Permodalan Nasional Madani</span>
            <span class="mono">v1.0</span>
        </footer>
    </div><!-- /.main -->
</div><!-- /.app -->

<!-- ================= MODAL ================= -->
<div class="modal-overlay" id="modalOverlay" hidden>
    <div class="modal" id="modalBox"></div>
</div>

<!-- ============ Template Modal Logout ============ -->
<template id="modalLogout">
    <div class="modal-head" style="border-bottom:none; padding-bottom:0;">
        <div class="kpi-icon" style="width:48px; height:48px; border-radius:14px; background:rgba(214,69,69,0.1); color:var(--danger); margin: 10px auto 0;">
            <?= icon('logout', 24) ?>
        </div>
        <button type="button" class="modal-close" data-modal-close style="position:absolute; right:15px; top:15px;"><?= icon('close', 18) ?></button>
    </div>
    <div class="modal-body" style="text-align:center; padding-top:10px;">
        <h3 style="font-size:18px; margin-bottom:8px;">Konfirmasi Keluar</h3>
        <p style="color:var(--text-soft); font-size:14px;">Apakah Anda yakin ingin keluar dari sistem E-Arsip PNM?</p>
    </div>
    <div class="modal-foot" style="background:none; border-top:none; justify-content:center; padding:0 20px 24px; gap:12px;">
        <button type="button" class="btn btn-ghost" data-modal-close style="flex:1;">Batal</button>
        <a href="<?= e(url('logout.php')) ?>" class="btn btn-primary" style="flex:1; background:var(--danger); box-shadow:0 8px 18px rgba(214,69,69,0.25);">Keluar</a>
    </div>
</template>

<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
