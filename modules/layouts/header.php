<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../config/database.php';

$pdo    = conectar();
$nombre = $_SESSION['nombre'];
$roles  = $_SESSION['roles'] ?? [];

// Avatar guardado en sesión (se puede cambiar desde el perfil)
if (!isset($_SESSION['avatar'])) {
    $_SESSION['avatar'] = '🏗️';
}
$avatar = $_SESSION['avatar'];

// Splash: solo se muestra una vez al iniciar sesión
$show_splash = $_SESSION['show_splash'] ?? false;
if ($show_splash) {
    $_SESSION['show_splash'] = false;
}

// Info del empleado para el perfil
$stmt_emp = $pdo->prepare("
    SELECT e.email, e.telefono, e.ci, e.direccion,
           c.nombre as cargo
    FROM empleados e
    JOIN usuarios_sistema us ON us.id_empleado = e.id_empleado
    LEFT JOIN asignaciones a ON a.id_empleado = e.id_empleado AND a.fecha_fin IS NULL
    LEFT JOIN cargos c ON c.id_cargo = a.id_cargo
    WHERE us.id_usuario_sistema = ?
    LIMIT 1
");
$stmt_emp->execute([$_SESSION['id_usuario']]);
$emp_info = $stmt_emp->fetch();

$cargo_actual = $emp_info['cargo'] ?? ($roles[0] ?? 'Sin cargo');
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresa Constructora</title>
    <!-- ANTI-PARPADEO: aplicar tema guardado ANTES de renderizar -->
<script>
(function () {
    var theme = localStorage.getItem('bootswatch-theme') || 'bootstrap';
    var url = theme === 'bootstrap'
        ? 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css'
        : 'https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/' + theme + '/bootstrap.min.css';

    // Poner el href correcto ANTES de que el browser pinte
    document.write(
        '<link id="themeStylesheet" rel="stylesheet" href="' + url + '">'
    );
})();
</script>
<!-- ANTI-PARPADEO sidebar: ocultar hasta que JS restaure estado -->
<script>
(function () {
    var saved = localStorage.getItem('sidebar_open');
    var isMobile = window.innerWidth < 768;

    if (!isMobile && saved === '0') {
        // Inyectar estilos inline antes de pintar
        document.write(
            '<style>' +
            '#sidebar { width: 60px !important; min-width: 60px !important; }' +
            '#main-content { margin-left: 60px !important; }' +
            '.sb-item-label, .sb-logo-text, .sb-user-info, .sb-footer-label ' +
            '{ opacity: 0 !important; width: 0 !important; }' +
            '</style>'
        );
    }
})();
</script>
<!-- ANTI-PARPADEO modo claro/oscuro -->
<script>
(function () {
    var t = localStorage.getItem('theme') || 'auto';
    var val = t;
    if (t === 'auto') {
        val = window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-bs-theme', val);
})();
</script>
    <link rel="icon" type="image/x-icon" href="../../public/assets/favicon.png">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- DataTables -->
    <link rel="stylesheet"
          href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.min.css">

    <style>
        /* ── Fuente base ── */
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #F4F6F9;
            margin: 0;
        }

        /* ══════════════════════════════════════════
           SPLASH SCREEN
        ══════════════════════════════════════════ */
        #splash-screen {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #2C3E50 0%, #1a2535 60%, #0d1520 100%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        #splash-screen.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .splash-logo {
            width: 90px;
            height: 90px;
            border-radius: 24px;
            background: linear-gradient(135deg, #3498DB, #E67E22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            font-weight: 900;
            color: #fff;
            box-shadow: 0 0 60px rgba(52,152,219,0.5);
            animation: splashPop 0.65s cubic-bezier(.22,1,.36,1) both;
        }
        .splash-title {
            color: #fff;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 3px;
            margin-top: 18px;
            animation: splashPop 0.65s cubic-bezier(.22,1,.36,1) 0.1s both;
        }
        .splash-sub {
            color: rgba(255,255,255,0.45);
            font-size: 11px;
            letter-spacing: 5px;
            text-transform: uppercase;
            margin-top: 5px;
            animation: splashPop 0.65s cubic-bezier(.22,1,.36,1) 0.2s both;
        }
        .splash-bar {
            width: 0;
            height: 3px;
            border-radius: 99px;
            background: linear-gradient(90deg, #3498DB, #E67E22);
            margin-top: 22px;
            animation: growBar 1.2s ease 0.35s forwards;
        }
        @keyframes splashPop {
            from { transform: scale(0.75) translateY(16px); opacity: 0; }
            to   { transform: scale(1) translateY(0);       opacity: 1; }
        }
        @keyframes growBar {
            from { width: 0; }
            to   { width: 60px; }
        }

        /* ══════════════════════════════════════════
           LAYOUT
        ══════════════════════════════════════════ */
        #app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ══════════════════════════════════════════
           SIDEBAR
        ══════════════════════════════════════════ */
        #sidebar {
            width: 245px;
            min-width: 245px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            background: linear-gradient(160deg, #2C3E50 0%, #1a2535 100%);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width 0.38s cubic-bezier(.4,0,.2,1),
                        min-width 0.38s cubic-bezier(.4,0,.2,1),
                        box-shadow 0.38s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.18);
        }
        /* NUEVO: colapsa a 60px mostrando solo iconos */
        #sidebar.collapsed {
            width: 60px;
            min-width: 60px;
            box-shadow: 2px 0 12px rgba(0,0,0,0.12);
        }
        /* En móvil sí se oculta del todo */
        @media (max-width: 767px) {
            #sidebar.collapsed {
                width: 0;
                min-width: 0;
                box-shadow: none;
            }
        }

        /* Logo dentro del sidebar */
        .sb-logo {
            padding: 24px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
            overflow: hidden;
            flex-shrink: 0;
        }
        .sb-logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3498DB, #E67E22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 900;
            color: #fff;
            flex-shrink: 0;
        }
        .sb-logo-text strong {
            display: block;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
        }
        .sb-logo-text span {
            color: rgba(255,255,255,0.4);
            font-size: 10px;
            letter-spacing: 2px;
        }

        /* Mini-perfil en sidebar */
        /* Logo dentro del sidebar */
        .sb-logo {
            padding: 22px 14px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
            overflow: hidden;
            flex-shrink: 0;
            min-height: 72px;
        }
        .sb-logo-icon {
            width: 38px;
            min-width: 38px;   /* ← no se encoge */
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3498DB, #E67E22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 900;
            color: #fff;
            flex-shrink: 0;
        }
        .sb-logo-text {
            overflow: hidden;
            transition: opacity 0.25s ease, width 0.38s ease;
            white-space: nowrap;
        }
        /* Ocultar texto cuando colapsa */
        #sidebar.collapsed .sb-logo-text {
            opacity: 0;
            width: 0;
        }
        .sb-logo-text strong {
            display: block;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
        }
        .sb-logo-text span {
            color: rgba(255,255,255,0.4);
            font-size: 10px;
            letter-spacing: 2px;
        }

        /* Mini-perfil en sidebar */
        .sb-user {
            padding: 12px 11px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            flex-shrink: 0;
            transition: background 0.18s;
            min-height: 62px;
        }
        .sb-user:hover { background: rgba(255,255,255,0.05); }
        .sb-avatar {
            width: 38px;
            min-width: 38px;   /* ← no se encoge */
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E67E22, #3498DB);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.18);
        }
        .sb-user-info {
            overflow: hidden;
            transition: opacity 0.25s ease, width 0.38s ease;
            white-space: nowrap;
        }
        #sidebar.collapsed .sb-user-info {
            opacity: 0;
            width: 0;
        }
        .sb-user-name {
            color: #fff;
            font-size: 13px;
            font-weight: 600;
        }
        .sb-user-role {
            color: rgba(255,255,255,0.42);
            font-size: 11px;
        }

        /* Nav items */
        .sb-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 10px 8px;
        }
        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
            border-radius: 99px;
        }

        .sb-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 11px;
            border-radius: 10px;
            color: rgba(255,255,255,0.52);
            font-size: 13.5px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            margin-bottom: 2px;
            border-left: 3px solid transparent;
            transition: background 0.18s, color 0.18s, border-color 0.18s;
            position: relative;    /* para tooltip */
        }
        .sb-item:hover {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.85);
        }
        .sb-item.active {
            background: rgba(52,152,219,0.18);
            color: #fff;
            font-weight: 700;
            border-left-color: #3498DB;
        }
        .sb-item i {
            font-size: 17px;
            flex-shrink: 0;
            min-width: 20px;
            text-align: center;
        }
        /* Texto del item: se oculta al colapsar */
        .sb-item-label {
            overflow: hidden;
            transition: opacity 0.25s ease, width 0.38s ease;
            white-space: nowrap;
        }
        #sidebar.collapsed .sb-item-label {
            opacity: 0;
            width: 0;
        }

        /* Tooltip que aparece al colapsar */
        .sb-item::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 68px;
            top: 50%;
            transform: translateY(-50%);
            background: #1a2535;
            color: #fff;
            font-size: 12.5px;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 8px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.18s ease;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
            z-index: 200;
            border: 1px solid rgba(255,255,255,0.08);
        }
        #sidebar.collapsed .sb-item:hover::after {
            opacity: 1;
        }

        /* Footer sidebar */
        .sb-footer {
            padding: 10px 8px 16px;
            border-top: 1px solid rgba(255,255,255,0.06);
            white-space: nowrap;
            overflow: hidden;
        }
        .sb-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.38);
            font-size: 12.5px;
            text-decoration: none;
            padding: 8px 11px;
            border-radius: 8px;
            transition: color 0.15s, background 0.15s;
        }
        .sb-footer a:hover {
            color: #E74C3C;
            background: rgba(231,76,60,0.08);
        }
        .sb-footer a i {
            font-size: 16px;
            flex-shrink: 0;
            min-width: 20px;
            text-align: center;
        }
        .sb-footer-label {
            overflow: hidden;
            transition: opacity 0.25s ease, width 0.38s ease;
            white-space: nowrap;
        }
        #sidebar.collapsed .sb-footer-label {
            opacity: 0;
            width: 0;
        }

        /* ══════════════════════════════════════════
           MAIN CONTENT
        ══════════════════════════════════════════ */
        #main-content {
            margin-left: 245px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: margin-left 0.38s cubic-bezier(.4,0,.2,1);
        }
        #main-content.expanded {
            margin-left: 60px;  /* ← ya no es 0, es 60px */
        }
        @media (max-width: 767px) {
            #main-content,
            #main-content.expanded {
                margin-left: 0 !important;
            }
        }

        /* ══════════════════════════════════════════
           TOPBAR
        ══════════════════════════════════════════ */
        #topbar {
            height: 62px;
            background: #fff;
            border-bottom: 1px solid #E8ECF0;
            display: flex;
            align-items: center;
            padding: 0 22px;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
        }

        .topbar-toggle {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1.5px solid #E8ECF0;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            color: #7F8C8D;
            transition: background 0.15s, border-color 0.15s;
            flex-shrink: 0;
        }
        .topbar-toggle:hover { background: #F4F6F9; border-color: #CCD1D9; }

        .topbar-brand {
            font-size: 15px;
            font-weight: 800;
            color: #1C1C1E;
            letter-spacing: 0.2px;
            display: none;
        }

        .topbar-search {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #F4F6F9;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 13.5px;
            color: #95A5A6;
            max-width: 340px;
            flex: 1;
            border: 1.5px solid transparent;
            transition: border-color 0.18s, background 0.18s;
            cursor: text;
        }
        .topbar-search:hover { border-color: #CCD1D9; }

        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: 1.5px solid #E8ECF0;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: #7F8C8D;
            position: relative;
            transition: background 0.15s;
        }
        .topbar-icon-btn:hover { background: #F4F6F9; }

        .notif-dot {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #E74C3C;
            border: 2px solid #fff;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 5px 10px 5px 6px;
            border-radius: 12px;
            cursor: pointer;
            border: 1.5px solid #E8ECF0;
            background: none;
            transition: background 0.15s;
        }
        .topbar-user:hover { background: #F4F6F9; }
        .topbar-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E67E22, #3498DB);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }
        .topbar-user-name {
            font-size: 13px;
            font-weight: 700;
            color: #1C1C1E;
            line-height: 1.2;
        }
        .topbar-user-role {
            font-size: 11px;
            color: #95A5A6;
        }

        /* ══════════════════════════════════════════
           MODAL PERFIL
        ══════════════════════════════════════════ */
        #profile-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 500;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }
        #profile-modal.open { display: flex; }

        @keyframes fadeIn { from{opacity:0} to{opacity:1} }

        .profile-card {
            background: #fff;
            border-radius: 20px;
            width: 430px;
            max-width: 95vw;
            box-shadow: 0 24px 80px rgba(0,0,0,0.22);
            overflow: hidden;
            animation: slideUp 0.32s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes slideUp {
            from { transform: translateY(40px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .profile-header {
            background: linear-gradient(135deg, #2C3E50, #1a2535);
            padding: 28px 28px 22px;
            text-align: center;
            position: relative;
        }
        .profile-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            border: none;
            color: #fff;
            font-size: 17px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
        }
        .profile-close:hover { background: rgba(255,255,255,0.22); }

        .profile-avatar-wrap {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E67E22, #3498DB);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin: 0 auto 12px;
            border: 3px solid rgba(255,255,255,0.22);
        }
        .profile-name {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
        }
        .profile-role {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            margin-top: 3px;
        }

        /* Tabs del modal */
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #E8ECF0;
        }
        .profile-tab {
            flex: 1;
            padding: 12px 4px;
            border: none;
            background: none;
            font-size: 12.5px;
            font-weight: 600;
            color: #95A5A6;
            cursor: pointer;
            border-bottom: 2.5px solid transparent;
            transition: color 0.18s, border-color 0.18s;
        }
        .profile-tab.active {
            color: #3498DB;
            border-bottom-color: #3498DB;
        }

        .profile-body {
            padding: 20px 24px 24px;
        }

        /* Avatares */
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .avatar-option {
            padding: 14px 8px;
            border-radius: 12px;
            border: 2px solid #E8ECF0;
            background: #F8F9FA;
            cursor: pointer;
            text-align: center;
            transition: all 0.18s;
        }
        .avatar-option:hover {
            border-color: #3498DB;
            background: #EBF5FB;
        }
        .avatar-option.selected {
            border-color: #3498DB;
            background: #EBF5FB;
        }
        .avatar-option .av-emoji { font-size: 28px; display: block; }
        .avatar-option .av-label {
            font-size: 10px;
            color: #95A5A6;
            font-weight: 600;
            margin-top: 4px;
        }

        /* Toggle switch config */
        .config-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 0;
            border-bottom: 1px solid #F0F2F4;
            font-size: 13.5px;
            color: #1C1C1E;
        }
        .config-row:last-child { border-bottom: none; }

        .toggle-sw {
            width: 42px;
            height: 24px;
            border-radius: 99px;
            background: #CCD1D9;
            position: relative;
            cursor: pointer;
            border: none;
            transition: background 0.2s;
            flex-shrink: 0;
        }
        .toggle-sw.on { background: #3498DB; }
        .toggle-knob {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fff;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: left 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.18);
        }
        .toggle-sw.on .toggle-knob { left: 21px; }

        /* Info row */
        .info-row {
            margin-bottom: 14px;
        }
        .info-row label {
            font-size: 10.5px;
            font-weight: 700;
            color: #95A5A6;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 3px;
        }
        .info-row .val {
            font-size: 14px;
            font-weight: 600;
            color: #1C1C1E;
        }

        /* ══════════════════════════════════════════
           CONTENIDO PRINCIPAL
        ══════════════════════════════════════════ */
        #page-content {
            flex: 1;
            padding: 28px;
        }

        /* Breadcrumb mejorado */
        .breadcrumb { font-size: 13px; }
        .breadcrumb-item a { color: #3498DB; text-decoration: none; }
        .breadcrumb-item.active { color: #95A5A6; }

        /* Cards generales */
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #F0F2F4;
            border-radius: 14px 14px 0 0 !important;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 14.5px;
        }

        /* Tables */
        .table { font-size: 13.5px; }
        .table thead th {
            font-size: 11px;
            font-weight: 700;
            color: #95A5A6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #E8ECF0;
            padding: 10px 12px;
        }
        .table tbody td { padding: 11px 12px; vertical-align: middle; }

        /* Botones */
        .btn { border-radius: 9px; font-size: 13.5px; font-weight: 600; }
        .btn-primary {
            background: #3498DB;
            border-color: #3498DB;
        }
        .btn-primary:hover { background: #2980B9; border-color: #2980B9; }

        /* Toast de alertas */
        .toast { border-radius: 12px; }

        /* Footer */
        #page-footer {
            border-top: 1px solid #E8ECF0;
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #95A5A6;
            background: #fff;
        }

        /* ── Responsive ── */
        @media (max-width: 767px) {
            #sidebar { width: 0; }
            #sidebar.mobile-open { width: 245px; }
            #main-content { margin-left: 0 !important; }
            #sb-overlay { display: block; }
            .topbar-brand { display: block; }
            .topbar-search { display: none; }
        }

        /* ── Theme loading ── */
        body.theme-loading { opacity: 0.4; pointer-events: none; }
        .theme-check { width: 18px; display: inline-block; opacity: 0; transition: opacity 0.2s; }
        .dropdown-item.active .theme-check { opacity: 1; }

        /* ══════════════════════════════════════════
        MODO OSCURO COMPLETO
        ══════════════════════════════════════════ */
        [data-bs-theme="dark"] body {
            background: #0f1117 !important;
        }

        /* Sidebar en modo oscuro */
        [data-bs-theme="dark"] #sidebar {
            background: linear-gradient(160deg, #1a1f2e 0%, #0d1117 100%) !important;
            box-shadow: 4px 0 20px rgba(0,0,0,0.4) !important;
        }
        [data-bs-theme="dark"] .sb-logo {
            border-bottom-color: rgba(255,255,255,0.05) !important;
        }
        [data-bs-theme="dark"] .sb-user {
            border-bottom-color: rgba(255,255,255,0.04) !important;
        }
        [data-bs-theme="dark"] .sb-item:hover {
            background: rgba(255,255,255,0.04) !important;
        }
        [data-bs-theme="dark"] .sb-footer {
            border-top-color: rgba(255,255,255,0.04) !important;
        }

        /* Topbar en modo oscuro */
        [data-bs-theme="dark"] #topbar {
            background: #161b27 !important;
            border-bottom-color: #1e2535 !important;
            box-shadow: 0 1px 8px rgba(0,0,0,0.3) !important;
        }
        [data-bs-theme="dark"] .topbar-toggle {
            border-color: #1e2535 !important;
            color: #8899aa !important;
        }
        [data-bs-theme="dark"] .topbar-toggle:hover {
            background: #1e2535 !important;
        }
        [data-bs-theme="dark"] .topbar-search {
            background: #1e2535 !important;
            color: #6b7a8d !important;
            border-color: transparent !important;
        }
        [data-bs-theme="dark"] .topbar-search:hover {
            border-color: #2d3a4a !important;
        }
        [data-bs-theme="dark"] .topbar-icon-btn {
            border-color: #1e2535 !important;
            color: #8899aa !important;
        }
        [data-bs-theme="dark"] .topbar-icon-btn:hover {
            background: #1e2535 !important;
        }
        [data-bs-theme="dark"] .topbar-user {
            border-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .topbar-user:hover {
            background: #1e2535 !important;
        }
        [data-bs-theme="dark"] .topbar-user-name {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .topbar-user-role {
            color: #6b7a8d !important;
        }

        /* Cards en modo oscuro */
        [data-bs-theme="dark"] .card {
            background: #161b27 !important;
            border: 1px solid #1e2535 !important;
            box-shadow: 0 2px 14px rgba(0,0,0,0.25) !important;
        }
        [data-bs-theme="dark"] .card-header {
            background: #161b27 !important;
            border-bottom-color: #1e2535 !important;
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .card-body {
            color: #cbd5e1 !important;
        }

        /* Section cards dashboard */
        [data-bs-theme="dark"] .section-card {
            background: #161b27 !important;
            box-shadow: 0 2px 14px rgba(0,0,0,0.25) !important;
        }
        [data-bs-theme="dark"] .section-card:hover {
            box-shadow: 0 6px 28px rgba(0,0,0,0.35) !important;
        }
        [data-bs-theme="dark"] .section-head {
            border-bottom-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .section-head h5 {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .row-item {
            border-bottom-color: #1a1f2e !important;
        }
        [data-bs-theme="dark"] .kpi-card {
            background: #161b27 !important;
            box-shadow: 0 2px 14px rgba(0,0,0,0.25) !important;
        }
        [data-bs-theme="dark"] .kpi-value {
            color: #e2e8f0 !important;
        }

        /* Tablas en modo oscuro */
        [data-bs-theme="dark"] .table {
            color: #cbd5e1 !important;
            border-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .table thead th {
            background: #1a1f2e !important;
            color: #6b7a8d !important;
            border-bottom-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .table tbody tr:hover {
            background: #1a1f2e !important;
        }
        [data-bs-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: rgba(255,255,255,0.02) !important;
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .table-bordered {
            border-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .table-bordered td,
        [data-bs-theme="dark"] .table-bordered th {
            border-color: #1e2535 !important;
        }

        /* DataTables en modo oscuro */
        [data-bs-theme="dark"] .dataTables_wrapper {
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_filter input,
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_length select {
            background: #1e2535 !important;
            border-color: #2d3a4a !important;
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_info,
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #8899aa !important;
        }
        [data-bs-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3498DB !important;
            border-color: #3498DB !important;
            color: #fff !important;
        }
        [data-bs-theme="dark"] .dt-search-custom {
            background: #1e2535 !important;
            border-color: #2d3a4a !important;
            color: #e2e8f0 !important;
        }

        /* Forms en modo oscuro */
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select {
            background: #1e2535 !important;
            border-color: #2d3a4a !important;
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .form-control:focus,
        [data-bs-theme="dark"] .form-select:focus {
            background: #1e2535 !important;
            border-color: #3498DB !important;
            color: #e2e8f0 !important;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15) !important;
        }
        [data-bs-theme="dark"] .form-control::placeholder {
            color: #4a5568 !important;
        }
        [data-bs-theme="dark"] .form-label {
            color: #8899aa !important;
        }

        /* Modales en modo oscuro */
        [data-bs-theme="dark"] .modal-content {
            background: #161b27 !important;
            border-color: #1e2535 !important;
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .modal-header {
            border-bottom-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .modal-footer {
            border-top-color: #1e2535 !important;
        }

        /* Dropdown en modo oscuro */
        [data-bs-theme="dark"] .dropdown-menu {
            background: #161b27 !important;
            border-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .dropdown-item {
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .dropdown-item:hover {
            background: #1e2535 !important;
            color: #e2e8f0 !important;
        }

        /* Breadcrumb en modo oscuro */
        [data-bs-theme="dark"] .breadcrumb-item.active {
            color: #6b7a8d !important;
        }

        /* Botones en modo oscuro */
        [data-bs-theme="dark"] .btn-outline-secondary {
            border-color: #2d3a4a !important;
            color: #8899aa !important;
        }
        [data-bs-theme="dark"] .btn-outline-secondary:hover {
            background: #1e2535 !important;
            color: #e2e8f0 !important;
        }

        /* Footer en modo oscuro */
        [data-bs-theme="dark"] #page-footer {
            background: #161b27 !important;
            border-top-color: #1e2535 !important;
            color: #4a5568 !important;
        }

        /* Badges en modo oscuro */
        [data-bs-theme="dark"] .badge-ejecucion {
            background: rgba(39,174,96,0.15) !important;
            color: #6fcf97 !important;
        }
        [data-bs-theme="dark"] .badge-planificacion {
            background: rgba(52,152,219,0.15) !important;
            color: #56aee8 !important;
        }
        [data-bs-theme="dark"] .badge-finalizado {
            background: rgba(149,165,166,0.15) !important;
            color: #95A5A6 !important;
        }

        /* Toast en modo oscuro */
        [data-bs-theme="dark"] .toast {
            background: #161b27 !important;
            border-color: #1e2535 !important;
            color: #cbd5e1 !important;
        }

        /* Progress bar en modo oscuro */
        [data-bs-theme="dark"] .prog-wrap {
            background: #1e2535 !important;
        }

        /* Ini-avatar en modo oscuro */
        [data-bs-theme="dark"] .ini-avatar {
            background: linear-gradient(135deg,#1a2535,#1e2d3d) !important;
            border-color: #2d3a4a !important;
        }

        /* Profile modal en modo oscuro */
        [data-bs-theme="dark"] .profile-card {
            background: #161b27 !important;
        }
        [data-bs-theme="dark"] .profile-tabs {
            border-bottom-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .profile-tab {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .profile-tab.active {
            color: #3498DB !important;
            border-bottom-color: #3498DB !important;
        }
        [data-bs-theme="dark"] .profile-body {
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .info-row label {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .info-row .val {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .config-row {
            border-bottom-color: #1e2535 !important;
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .avatar-option {
            background: #1e2535 !important;
            border-color: #2d3a4a !important;
        }
        [data-bs-theme="dark"] .avatar-option:hover,
        [data-bs-theme="dark"] .avatar-option.selected {
            background: #1a2d3d !important;
            border-color: #3498DB !important;
        }
        [data-bs-theme="dark"] .avatar-option .av-label {
            color: #6b7a8d !important;
        }

        /* Input de búsqueda custom */
        .dt-search-custom {
            border: 1.5px solid #E8ECF0;
            border-radius: 9px;
            padding: 6px 12px 6px 34px;
            font-size: 13px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16'%3E%3Cpath fill='%2395A5A6' d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 10px center;
            transition: border-color 0.18s, box-shadow 0.18s;
        }
        .dt-search-custom:focus {
            border-color: #3498DB;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
            outline: none;
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════
     SPLASH SCREEN (solo primer login)
══════════════════════════════════════ -->
<?php if ($show_splash): ?>
<div id="splash-screen">
    <div class="splash-logo">EC</div>
    <div class="splash-title">Empresa Constructora</div>
    <div class="splash-sub">Sistema de Gestión</div>
    <div class="splash-bar"></div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     MODAL DE PERFIL
══════════════════════════════════════ -->
<div id="profile-modal">
    <div class="profile-card">

        <div class="profile-header">
            <button class="profile-close" onclick="closeProfile()">×</button>
            <div class="profile-avatar-wrap" id="modal-avatar-display">
                <?= htmlspecialchars($avatar) ?>
            </div>
            <div class="profile-name"><?= htmlspecialchars($nombre) ?></div>
            <div class="profile-role"><?= htmlspecialchars($cargo_actual) ?></div>
        </div>

        <div class="profile-tabs">
            <button class="profile-tab active" onclick="switchTab('info', this)">Mi perfil</button>
            <button class="profile-tab" onclick="switchTab('avatar', this)">Avatar</button>
            <button class="profile-tab" onclick="switchTab('config', this)">Configuración</button>
        </div>

        <div class="profile-body">

            <!-- TAB: Info -->
            <div id="tab-info">
                <div class="info-row">
                    <label>Nombre completo</label>
                    <div class="val"><?= htmlspecialchars($nombre) ?></div>
                </div>
                <div class="info-row">
                    <label>Cargo actual</label>
                    <div class="val"><?= htmlspecialchars($cargo_actual) ?></div>
                </div>
                <div class="info-row">
                    <label>Correo electrónico</label>
                    <div class="val"><?= htmlspecialchars($emp_info['email'] ?? '—') ?></div>
                </div>
                <div class="info-row">
                    <label>Teléfono</label>
                    <div class="val"><?= htmlspecialchars($emp_info['telefono'] ?? '—') ?></div>
                </div>
                <div class="info-row">
                    <label>Roles en el sistema</label>
                    <div class="val">
                        <?php foreach ($roles as $r): ?>
                            <span style="display:inline-block;background:#EBF5FB;color:#2980B9;font-size:11px;font-weight:700;padding:3px 9px;border-radius:99px;margin:2px;">
                                <?= htmlspecialchars($r) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- TAB: Avatar -->
            <div id="tab-avatar" style="display:none;">
                <p style="font-size:13px;color:#95A5A6;margin-bottom:14px;">
                    Elige tu avatar de perfil:
                </p>
                <div class="avatar-grid">
                    <?php
                    $avatars = [
                        ['emoji' => '🏗️', 'label' => 'Constructor'],
                        ['emoji' => '🏢', 'label' => 'Arquitecto'],
                        ['emoji' => '⚙️', 'label' => 'Ingeniero'],
                        ['emoji' => '📐', 'label' => 'Diseñador'],
                        ['emoji' => '🪖', 'label' => 'Jefe de Obra'],
                        ['emoji' => '📊', 'label' => 'Director'],
                    ];
                    foreach ($avatars as $av):
                        $sel = ($av['emoji'] === $avatar) ? 'selected' : '';
                    ?>
                    <div class="avatar-option <?= $sel ?>"
                         onclick="selectAvatar('<?= $av['emoji'] ?>', this)">
                        <span class="av-emoji"><?= $av['emoji'] ?></span>
                        <div class="av-label"><?= $av['label'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="../../modules/auth/save_avatar.php"
                      id="avatar-form" style="margin-top:16px;">
                    <input type="hidden" name="avatar" id="avatar-input"
                           value="<?= htmlspecialchars($avatar) ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        Guardar avatar
                    </button>
                </form>
            </div>

            <!-- TAB: Configuración -->
            <div id="tab-config" style="display:none;">
                <div class="config-row">
                    <span>Notificaciones en sistema</span>
                    <button class="toggle-sw on" onclick="this.classList.toggle('on')">
                        <div class="toggle-knob"></div>
                    </button>
                </div>
                <div class="config-row">
                    <span>Notificaciones por correo</span>
                    <button class="toggle-sw on" onclick="this.classList.toggle('on')">
                        <div class="toggle-knob"></div>
                    </button>
                </div>
                <div class="config-row">
                    <span>Mostrar stock bajo en dashboard</span>
                    <button class="toggle-sw" onclick="this.classList.toggle('on')">
                        <div class="toggle-knob"></div>
                    </button>
                </div>
                <div style="margin-top:18px;">
                    <a href="../../modules/auth/logout.php"
                       style="display:flex;align-items:center;gap:8px;color:#E74C3C;font-size:13.5px;font-weight:600;text-decoration:none;padding:10px 14px;border-radius:10px;border:1.5px solid #FDECEA;background:#FDECEA22;transition:background 0.15s;"
                       onmouseover="this.style.background='#FDECEA'"
                       onmouseout="this.style.background='#FDECEA22'">
                        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     OVERLAY MÓVIL
══════════════════════════════════════ -->
<div id="sb-overlay" onclick="toggleSidebar()"></div>

<!-- ══════════════════════════════════════
     LAYOUT PRINCIPAL
══════════════════════════════════════ -->
<div id="app-wrapper">

    <!-- SIDEBAR -->
 <!-- SIDEBAR -->
    <aside id="sidebar">
        <!-- Logo -->
        <div class="sb-logo">
            <div class="sb-logo-icon">EC</div>
            <div class="sb-logo-text">
                <strong>Empresa Constructora</strong>
                <span>Sistema de Gestión</span>
            </div>
        </div>

        <!-- Mini perfil -->
        <div class="sb-user" onclick="openProfile()">
            <div class="sb-avatar" id="sb-avatar-display">
                <?= htmlspecialchars($avatar) ?>
            </div>
            <div class="sb-user-info">
                <div class="sb-user-name">
                    <?= htmlspecialchars(explode(' ', $nombre)[0]) ?>
                </div>
                <div class="sb-user-role">
                    <?= htmlspecialchars($cargo_actual) ?>
                </div>
            </div>
        </div>

        <!-- Navegación -->
        <nav class="sb-nav">
            <?php require 'menu.php'; ?>
        </nav>

        <!-- Footer -->
        <div class="sb-footer">
            <a href="../../modules/auth/logout.php">
                <i class="bi bi-box-arrow-right"></i>
                <span class="sb-footer-label">Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div id="main-content">

        <!-- TOPBAR -->
        <header id="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()" title="Menú">
                <i class="bi bi-list"></i>
            </button>

            <span class="topbar-brand">Empresa Constructora</span>

            <div class="topbar-right">
                <!-- Tema -->
                <div class="dropdown">
                    <button class="topbar-icon-btn dropdown-toggle"
                            data-bs-toggle="dropdown"
                            title="Cambiar tema"
                            style="font-size:14px;">
                        <i class="bi bi-palette" id="theme-icon-active"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <?php
                        $themes = ['bootstrap','cerulean','cosmo','flatly',
                                   'journal','litera','lumen','pulse',
                                   'sandstone','simplex','spacelab','united','zephyr'];
                        foreach ($themes as $t):
                        ?>
                        <li>
                            <a class="dropdown-item theme-option d-flex align-items-center gap-2"
                               data-theme="<?= $t ?>" href="#">
                                <span class="theme-check">✓</span>
                                <?= ucfirst($t) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Modo claro/oscuro -->
                <div class="dropdown">
                    <button class="topbar-icon-btn dropdown-toggle"
                            data-bs-toggle="dropdown"
                            id="bd-theme"
                            title="Modo de color">
                        <i class="bi bi-circle-half"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2"
                                    data-bs-theme-value="light">
                                <i class="bi bi-sun-fill"></i> Claro
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2"
                                    data-bs-theme-value="dark">
                                <i class="bi bi-moon-stars-fill"></i> Oscuro
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item d-flex align-items-center gap-2 active"
                                    data-bs-theme-value="auto">
                                <i class="bi bi-circle-half"></i> Auto
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Notificaciones -->
                <button class="topbar-icon-btn" title="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span class="notif-dot"></span>
                </button>

                <!-- Usuario -->
                <button class="topbar-user" onclick="openProfile()">
                    <div class="topbar-user-avatar" id="topbar-avatar-display">
                        <?= htmlspecialchars($avatar) ?>
                    </div>
                    <div>
                        <div class="topbar-user-name">
                            <?= htmlspecialchars(explode(' ', $nombre)[0]) ?>
                        </div>
                        <div class="topbar-user-role">
                            <?= htmlspecialchars($cargo_actual) ?>
                        </div>
                    </div>
                </button>
            </div>
        </header>

        <!-- Contenido de la página -->
        <div id="page-content">