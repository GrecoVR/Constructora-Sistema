</div><!-- /#page-content -->

        <!-- Footer -->
        <footer id="page-footer">
            <span>Empresa Constructora &copy; <?= date('Y') ?></span>
            <span>Taller de Base de Datos — Gestión 2026</span>
        </footer>

    </div><!-- /#main-content -->

</div><!-- /#app-wrapper -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.8/js/dataTables.bootstrap5.min.js"></script>

<script>
// ══════════════════════════════════════════
// SPLASH SCREEN
// ══════════════════════════════════════════
(function () {
    const splash = document.getElementById('splash-screen');
    if (!splash) return;
    setTimeout(() => splash.classList.add('hidden'), 1800);
    setTimeout(() => splash.remove(), 2350);
})();


// ══════════════════════════════════════════
// SIDEBAR TOGGLE — iconos visibles al colapsar
// ══════════════════════════════════════════
const sidebar     = document.getElementById('sidebar');
const mainContent = document.getElementById('main-content');
const overlay     = document.getElementById('sb-overlay');

function toggleSidebar() {
    const isMobile = window.innerWidth < 768;

    if (isMobile) {
        sidebar.classList.toggle('mobile-open');
        // en móvil quitar collapsed si existía
        sidebar.classList.remove('collapsed');
        overlay.style.display =
            sidebar.classList.contains('mobile-open') ? 'block' : 'none';
    } else {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded', isCollapsed);
        localStorage.setItem('sidebar_open', isCollapsed ? '0' : '1');
    }
}

// Restaurar estado guardado
(function () {
    const isMobile = window.innerWidth < 768;
    if (!isMobile) {
        const saved = localStorage.getItem('sidebar_open');
        if (saved === '0') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    }
})();

// ══════════════════════════════════════════
// MODAL DE PERFIL
// ══════════════════════════════════════════
const profileModal = document.getElementById('profile-modal');

function openProfile() {
    profileModal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeProfile() {
    profileModal.classList.remove('open');
    document.body.style.overflow = '';
}

// Cerrar al hacer clic en el fondo
profileModal.addEventListener('click', function (e) {
    if (e.target === this) closeProfile();
});

// Cerrar con Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeProfile();
});

// ── Tabs del modal ──
function switchTab(tab, btn) {
    // Ocultar todos los tabs
    ['info', 'avatar', 'config'].forEach(t => {
        document.getElementById('tab-' + t).style.display = 'none';
    });
    // Desactivar todos los botones
    document.querySelectorAll('.profile-tab').forEach(b => {
        b.classList.remove('active');
    });
    // Mostrar el seleccionado
    document.getElementById('tab-' + tab).style.display = 'block';
    btn.classList.add('active');
}

// ── Selección de avatar ──
function selectAvatar(emoji, el) {
    // Quitar selección anterior
    document.querySelectorAll('.avatar-option').forEach(o => {
        o.classList.remove('selected');
    });
    el.classList.add('selected');

    // Actualizar input y previews en tiempo real
    document.getElementById('avatar-input').value = emoji;
    document.getElementById('modal-avatar-display').textContent  = emoji;
    document.getElementById('sb-avatar-display').textContent     = emoji;
    document.getElementById('topbar-avatar-display').textContent = emoji;
}

// ══════════════════════════════════════════
// TEMAS BOOTSWATCH
// ══════════════════════════════════════════
(function () {
    const $themeLink    = document.getElementById('themeStylesheet');
    const themeOptions  = document.querySelectorAll('.theme-option');
    const savedTheme    = localStorage.getItem('bootswatch-theme') || 'bootstrap';

    function getThemeUrl(theme) {
        if (theme === 'bootstrap') {
            return 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css';
        }
        return `https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/${theme}/bootstrap.min.css`;
    }

    function setTheme(theme) {
        const preload = document.createElement('link');
        preload.rel   = 'stylesheet';
        preload.href  = getThemeUrl(theme);
        document.body.classList.add('theme-loading');

        preload.onload = function () {
            $themeLink.href = getThemeUrl(theme);
            preload.remove();
            localStorage.setItem('bootswatch-theme', theme);
            document.body.classList.remove('theme-loading');

            themeOptions.forEach(o => o.classList.remove('active'));
            const active = document.querySelector(`.theme-option[data-theme="${theme}"]`);
            if (active) active.classList.add('active');
        };
        document.head.appendChild(preload);
    }

    setTheme(savedTheme);

    themeOptions.forEach(opt => {
        opt.addEventListener('click', function (e) {
            e.preventDefault();
            setTheme(this.dataset.theme);
        });
    });
})();

// ══════════════════════════════════════════
// MODO CLARO / OSCURO
// ══════════════════════════════════════════
(function () {
    const getStored  = () => localStorage.getItem('theme');
    const setStored  = v  => localStorage.setItem('theme', v);
    const getPreferred = () =>
        window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

    function applyTheme(t) {
        const val = t === 'auto' && getPreferred() === 'dark' ? 'dark' : t;
        document.documentElement.setAttribute('data-bs-theme',
            val === 'auto' ? getPreferred() : val);
    }

    applyTheme(getStored() || 'auto');

    document.querySelectorAll('[data-bs-theme-value]').forEach(btn => {
        btn.addEventListener('click', function () {
            const t = this.dataset.bsThemeValue;
            setStored(t);
            applyTheme(t);
            document.querySelectorAll('[data-bs-theme-value]').forEach(b =>
                b.classList.remove('active'));
            this.classList.add('active');
        });
    });
})();
// ══════════════════════════════════════════
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