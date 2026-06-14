</div><!-- /#cl-page-content -->

        <footer id="cl-footer">
            <span>Empresa Constructora &copy; <?= date('Y') ?></span>
            <span style="background:#D5F5E3;color:#1E8449;font-size:11px;
                         font-weight:700;padding:3px 10px;border-radius:99px;">
                Sesion Cliente
            </span>
        </footer>

    </div><!-- /#cl-main -->
</div><!-- layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.8/js/dataTables.bootstrap5.min.js"></script>

<script>
// ── Sidebar ───────────────────────────────────────────────
const clSidebar = document.getElementById('cl-sidebar');
const clMain    = document.getElementById('cl-main');
const clOverlay = document.getElementById('cl-overlay');

function clToggleSidebar() {
    const isMobile = window.innerWidth < 768;
    if (isMobile) {
        clSidebar.classList.toggle('mobile-open');
        clOverlay.style.display =
            clSidebar.classList.contains('mobile-open') ? 'block' : 'none';
    } else {
        const collapsed = clSidebar.classList.toggle('collapsed');
        clMain.classList.toggle('expanded', collapsed);
        localStorage.setItem('client_sidebar', collapsed ? '0' : '1');
    }
}

// Restaurar sidebar
(function(){
    if (window.innerWidth >= 768
        && localStorage.getItem('client_sidebar') === '0') {
        clSidebar.classList.add('collapsed');
        clMain.classList.add('expanded');
    }
})();

// ── Modal perfil ──────────────────────────────────────────
const clModal = document.getElementById('cl-profile-modal');

function clOpenProfile() {
    clModal.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function clCloseProfile() {
    clModal.classList.remove('open');
    document.body.style.overflow = '';
}
clModal.addEventListener('click', function(e) {
    if (e.target === this) clCloseProfile();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') clCloseProfile();
});

// ── Tabs modal ────────────────────────────────────────────
function clSwitchTab(tab, btn) {
    ['info','avatar','config'].forEach(t => {
        document.getElementById('cl-tab-' + t).style.display = 'none';
    });
    document.querySelectorAll('#cl-profile-modal .profile-tab').forEach(b => {
        b.classList.remove('active');
    });
    document.getElementById('cl-tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

// ── Avatar ────────────────────────────────────────────────
function clSelectAvatar(emoji, el) {
    document.querySelectorAll('#cl-profile-modal .avatar-option').forEach(o => {
        o.classList.remove('selected');
    });
    el.classList.add('selected');
    document.getElementById('cl-avatar-input').value  = emoji;
    document.getElementById('cl-modal-avatar').textContent   = emoji;
    document.getElementById('cl-sb-avatar').textContent      = emoji;
    document.getElementById('cl-topbar-avatar').textContent  = emoji;
}

// ── Modo oscuro / claro ───────────────────────────────────
function clApplyTheme(t) {
    const val = t === 'auto'
        ? (window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light')
        : t;
    document.documentElement.setAttribute('data-bs-theme', val);
    localStorage.setItem('client-theme', t);

    const icon  = document.querySelector('#cl-topbar-theme i');
    const lbl   = document.getElementById('cl-theme-label');
    const isDark = val === 'dark';

    if (icon) icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    if (lbl)  lbl.textContent = isDark ? 'Cambiar a claro' : 'Cambiar a oscuro';
}

// Inicializar tema
clApplyTheme(localStorage.getItem('client-theme') || 'auto');

// Botón topbar
document.getElementById('cl-topbar-theme').addEventListener('click', function() {
    const current = document.documentElement.getAttribute('data-bs-theme');
    clApplyTheme(current === 'dark' ? 'light' : 'dark');
});

// Botón en modal config
const clThemeBtn = document.getElementById('cl-theme-btn');
if (clThemeBtn) {
    clThemeBtn.addEventListener('click', function() {
        const current = document.documentElement.getAttribute('data-bs-theme');
        clApplyTheme(current === 'dark' ? 'light' : 'dark');
    });
}

// ══════════════════════════════════════════
// DATATABLES GLOBAL — buscador tiempo real
// ══════════════════════════════════════════
$(document).ready(function () {
    const dtLang = {
        url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
    };

    $('table.table').each(function () {
        const $t = $(this);
        if ($t.hasClass('no-dt')) return;
        if ($.fn.dataTable.isDataTable($t)) return;
        if ($t.find('tbody tr').length < 1) return;

        const dt = $t.DataTable({
            language:    { url: dtLang.url },
            order:       [],
            pageLength:  15,
            lengthMenu:  [10, 15, 25, 50, 100],
            searchDelay: 0,
            dom: '<"dt-toolbar d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"lf>rtip',
            columnDefs: [{ orderable: false, targets: -1 }],
            responsive: true,
        });

        // Reemplazar input generado por DataTables
        // con uno propio que busca en tiempo real
        const wrapper  = $t.closest('.dataTables_wrapper');
        const $dtInput = wrapper.find('input[type="search"]');

        // Estilizar
        $dtInput.addClass('dt-search-custom');
        $dtInput.attr('placeholder', 'Buscar...');

        // ── CLAVE: escuchar 'input' nativo, no eventos de DT ──
        $dtInput[0].addEventListener('input', function () {
            dt.search(this.value, true, true).draw(false);
        }, true);  // useCapture = true para interceptar antes que DT
    });
});
</script>
</body>
</html>