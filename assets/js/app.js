/**
 * =====================================================
 * FILE: assets/js/app.js
 * FUNGSI: JavaScript untuk interaksi frontend
 * VERSION: 2.0 - Fixed duplicate declaration
 * =====================================================
 */

// ── State ──────────────────────────────────────────
// 🔥 PERBAIKAN: Gunakan var atau const, dan cek apakah sudah ada
if (typeof window.currentPage === 'undefined') {
    window.currentPage = 'page-login';
}
if (typeof window.isMobile === 'undefined') {
    window.isMobile = window.innerWidth <= 768;
}

// ── Page Navigation ────────────────────────────────
function gotoPage(pageId) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const target = document.getElementById(pageId);
    if (target) target.classList.add('active');
    window.currentPage = pageId;
    window.scrollTo(0, 0);
}

// ── Dashboard Layout ──────────────────────────────
function updateDashboardLayout() {
    const mobile = document.getElementById('mobile-dashboard-view');
    const desktop = document.getElementById('desktop-dashboard-view');
    if (!mobile || !desktop) return;
    if (window.innerWidth <= 768) {
        mobile.style.display = 'block';
        desktop.style.display = 'none';
    } else {
        mobile.style.display = 'none';
        desktop.style.display = 'flex';
    }
}

function updateHistoryLayout() {
    const mobile = document.getElementById('mobile-history-view');
    const desktop = document.getElementById('desktop-history-view');
    if (!mobile || !desktop) return;
    if (window.innerWidth <= 768) {
        mobile.style.display = 'block';
        desktop.style.display = 'none';
    } else {
        mobile.style.display = 'none';
        desktop.style.display = 'flex';
    }
}

// ── Tab Switching (login) ──────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const btn = [...document.querySelectorAll('.tab-btn')].find(b => 
        b.textContent.trim().toLowerCase().includes(tab === 'masuk' ? 'masuk' : 'daftar')
    );
    if (btn) btn.classList.add('active');
    
    const formMasuk = document.getElementById('form-masuk');
    const formDaftar = document.getElementById('form-daftar');
    if (formMasuk) formMasuk.style.display = tab === 'masuk' ? 'flex' : 'none';
    if (formDaftar) formDaftar.style.display = tab === 'masuk' ? 'none' : 'flex';
}

// ── Password Toggle ────────────────────────────────
function togglePwd(inputId) {
    const inp = document.getElementById(inputId);
    const eye = document.getElementById(inputId + '-eye');
    if (!inp) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        if (eye) { eye.classList.remove('ri-eye-off-line'); eye.classList.add('ri-eye-line'); }
    } else {
        inp.type = 'password';
        if (eye) { eye.classList.remove('ri-eye-line'); eye.classList.add('ri-eye-off-line'); }
    }
}

// ── Toast ──────────────────────────────────────────
let toastTimer = null;
function showToast(msg, icon = 'ri-information-line') {
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${msg}`;
    t.style.display = 'flex';
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}

// ── Pagination ─────────────────────────────────────
document.addEventListener('click', function(e) {
    const pageBtn = e.target.closest('.page-btn');
    if (pageBtn && !pageBtn.querySelector('i')) {
        const group = pageBtn.closest('.pagination-btns');
        if (group) {
            group.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
            pageBtn.classList.add('active');
            const pageNum = pageBtn.textContent.trim();
            if (pageNum && !isNaN(pageNum)) {
                showToast(`📄 Menampilkan halaman ${pageNum}`, 'ri-file-list-3-line');
            }
        }
        e.preventDefault();
    }
});

// ── Responsive handler ─────────────────────────────
window.addEventListener('resize', function() {
    window.isMobile = window.innerWidth <= 768;
    if (window.currentPage === 'page-dashboard') updateDashboardLayout();
    if (window.currentPage === 'page-history') updateHistoryLayout();
});

// ── Init ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Login page is default
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    const loginPage = document.getElementById('page-login');
    if (loginPage) loginPage.classList.add('active');
    
    // Enter key on login
    document.querySelectorAll('input[type="email"], input[type="password"]').forEach(inp => {
        inp.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const form = this.closest('form');
                if (form) form.submit();
            }
        });
    });
});

// Export functions for global use
window.gotoPage = gotoPage;
window.switchTab = switchTab;
window.togglePwd = togglePwd;
window.showToast = showToast;
window.updateDashboardLayout = updateDashboardLayout;
window.updateHistoryLayout = updateHistoryLayout;