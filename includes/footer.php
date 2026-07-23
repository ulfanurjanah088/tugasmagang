<?php
/**
 * =====================================================
 * FILE: includes/footer.php
 * FUNGSI: Footer
 * VERSION: FINAL
 * =====================================================
 */
?>

<footer class="footer">
    <div class="footer-brand">
        <strong style="font-size:16px;font-family:var(--font-display);">Sistem Perizinan<span style="color:var(--clr-primary);">Cuti</span></strong>
        <br>
        <small style="color:var(--clr-muted);font-size:12px;">© <?= date('Y') ?> sistem.perizinan.cuti - Manajemen Cuti Karyawan</small>
    </div>
    <div class="footer-links">
        <a href="#">Panduan</a>
        <a href="#">Privasi</a>
        <a href="#">Kontak</a>
    </div>
</footer>

<script src="assets/js/app.js"></script>
<script>
function showToast(message, icon = 'ri-information-line') {
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${message}`;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}
<?php if (isset($_SESSION['flash_message'])): ?>
    showToast('<?= addslashes($_SESSION['flash_message']) ?>');
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>
</script>

</body>
</html>
