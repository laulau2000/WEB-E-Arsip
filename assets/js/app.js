/* =====================================================================
   E-Arsip PNM - JavaScript aplikasi
   ===================================================================== */
(function () {
    'use strict';

    /* ---------------- Sidebar (mobile & desktop toggle) ---------------- */
    const sidebar  = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const main     = document.querySelector('.main');

    const toggleSidebar = (open) => {
        if (window.innerWidth <= 900) {
            // Mobile: slide in/out
            sidebar.classList.toggle('open', open);
            backdrop.classList.toggle('show', open);
        } else {
            // Desktop: toggle collapsed state
            sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded');
        }
    };

    const hamburger = document.getElementById('hamburger');
    if (hamburger) hamburger.addEventListener('click', () => {
        const isOpen = window.innerWidth <= 900 ? sidebar.classList.contains('open') : !sidebar.classList.contains('collapsed');
        toggleSidebar(!isOpen);
    });

    if (backdrop) backdrop.addEventListener('click', () => toggleSidebar(false));

    /* ---------------- Submenu Arsip Mekaar ---------------- */
    document.querySelectorAll('.nav-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            btn.closest('.nav-group').classList.toggle('open');
        });
    });

    /* ---------------- Modal ---------------- */
    const overlay = document.getElementById('modalOverlay');
    const modalBox = document.getElementById('modalBox');

    function closeModal() {
        if (!overlay) return;
        overlay.classList.remove('show');
        setTimeout(() => { overlay.hidden = true; modalBox.innerHTML = ''; }, 200);
    }

    function openModal(templateId) {
        const tpl = document.getElementById(templateId);
        if (!tpl || !overlay) return;
        modalBox.innerHTML = tpl.innerHTML;
        overlay.hidden = false;
        requestAnimationFrame(() => overlay.classList.add('show'));
        bindModal();
    }

    function bindModal() {
        modalBox.querySelectorAll('[data-modal-close]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });
        const dz = modalBox.querySelector('.dropzone');
        if (dz) initDropzone(dz);
        const first = modalBox.querySelector('input, select, textarea');
        if (first) setTimeout(() => first.focus(), 60);
    }

    document.querySelectorAll('[data-modal]').forEach((btn) => {
        btn.addEventListener('click', () => openModal(btn.getAttribute('data-modal')));
    });

    if (overlay) {
        overlay.addEventListener('mousedown', (ev) => { if (ev.target === overlay) closeModal(); });
    }
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') closeModal(); });

    // Buka otomatis bila ada parameter ?modal=
    const params = new URLSearchParams(location.search);
    if (params.get('modal')) openModal(params.get('modal'));

    /* ---------------- Dropzone upload ---------------- */
    function initDropzone(dz) {
        const input = dz.querySelector('input[type="file"]');
        const label = dz.querySelector('.dz-file');
        if (!input) return;

        const show = () => {
            if (input.files.length && label) {
                label.querySelector('.dz-filename').textContent = input.files[0].name;
                label.classList.add('show');
            }
        };

        dz.addEventListener('click', (ev) => { if (ev.target !== input) input.click(); });
        input.addEventListener('change', show);
        ['dragenter', 'dragover'].forEach((e) =>
            dz.addEventListener(e, (ev) => { ev.preventDefault(); dz.classList.add('dragover'); }));
        ['dragleave', 'drop'].forEach((e) =>
            dz.addEventListener(e, (ev) => { ev.preventDefault(); dz.classList.remove('dragover'); }));
        dz.addEventListener('drop', (ev) => {
            if (ev.dataTransfer.files.length) {
                input.files = ev.dataTransfer.files;
                show();
            }
        });
    }
    document.querySelectorAll('.dropzone').forEach(initDropzone);

    /* ---------------- Realtime Clock ---------------- */
    function updateClock() {
        const now = new Date();
        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        
        const dayName = days[now.getDay()];
        const day = String(now.getDate()).padStart(2, '0');
        const monthName = months[now.getMonth()];
        const year = now.getFullYear();
        
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        const timeEl = document.getElementById('clock-time');
        const dateEl = document.getElementById('clock-date');
        
        if (timeEl) timeEl.textContent = `${hours}:${minutes}:${seconds}`;
        if (dateEl) dateEl.textContent = `${dayName}, ${day} ${monthName} ${year}`;
    }
    
    if (document.getElementById('realtime-clock')) {
        updateClock();
        setInterval(updateClock, 1000);
    }

    /* ---------------- Konfirmasi hapus ---------------- */
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (ev) => {
            if (!confirm(form.getAttribute('data-confirm'))) ev.preventDefault();
        });
    });

    /* ---------------- Filter tabel client-side ---------------- */
    const tableSearch = document.querySelector('[data-table-search]');
    if (tableSearch) {
        const table = document.getElementById(tableSearch.getAttribute('data-table-search'));
        tableSearch.addEventListener('input', () => {
            const q = tableSearch.value.toLowerCase().trim();
            table.querySelectorAll('tbody tr').forEach((tr) => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }
})();
