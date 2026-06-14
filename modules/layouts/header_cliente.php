<?php
require_once __DIR__ . '/../../middleware/auth_cliente.php';
require_once __DIR__ . '/../../config/database.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];
$nombre     = $_SESSION['nombre_cliente'];

if (!isset($_SESSION['avatar_cliente'])) {
    $_SESSION['avatar_cliente'] = '🏢';
}
$avatar = $_SESSION['avatar_cliente'];

$show_splash = $_SESSION['show_splash'] ?? false;
if ($show_splash) {
    $_SESSION['show_splash'] = false;
}

// Info cliente
$info = $pdo->prepare("
    SELECT c.nit, c.email, c.telefono, c.direccion
    FROM clientes c
    WHERE c.id_cliente = ?
");
$info->execute([$id_cliente]);
$info = $info->fetch();

// Notificaciones sin leer (conteo)
$notif_count = $pdo->prepare("
    SELECT COUNT(*) FROM notificaciones_clientes
    WHERE id_cliente = ?
");
$notif_count->execute([$id_cliente]);
$notif_count = $notif_count->fetchColumn();

$current_url = basename($_SERVER['PHP_SELF']);

$AVATARES = [
    ['emoji'=>'🏢','label'=>'Empresa'],
    ['emoji'=>'👔','label'=>'Ejecutivo'],
    ['emoji'=>'🏗️','label'=>'Constructor'],
    ['emoji'=>'📋','label'=>'Gestor'],
    ['emoji'=>'🌟','label'=>'Premium'],
    ['emoji'=>'🏛️','label'=>'Institución'],
];
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Cliente — Empresa Constructora</title>

    <!-- Anti-parpadeo modo oscuro -->
    <script>
    (function(){
        var t   = localStorage.getItem('client-theme') || 'auto';
        var val = t === 'auto'
            ? (window.matchMedia('(prefers-color-scheme:dark)').matches ? 'dark':'light')
            : t;
        document.documentElement.setAttribute('data-bs-theme', val);
    })();
    </script>

    <!-- Anti-parpadeo sidebar -->
    <script>
    (function(){
        var s = localStorage.getItem('client_sidebar');
        if (s === '0' && window.innerWidth >= 768) {
            document.write(
                '<style>' +
                '#cl-sidebar{width:60px!important;min-width:60px!important}' +
                '#cl-main{margin-left:60px!important}' +
                '.sb-item-label,.sb-logo-text,.sb-user-info,.sb-footer-label' +
                '{opacity:0!important;width:0!important}' +
                '</style>'
            );
        }
    })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.3.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #F4F6F9;
        }

        /* ══ SIDEBAR CLIENTE (verde) ══ */
        #cl-sidebar {
            width: 245px;
            min-width: 245px;
            height: 100vh;
            position: fixed;
            left: 0; top: 0;
            z-index: 100;
            background: linear-gradient(160deg, #1a3a2a 0%, #0d2018 100%);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width 0.38s cubic-bezier(.4,0,.2,1),
                        min-width 0.38s cubic-bezier(.4,0,.2,1),
                        box-shadow 0.38s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.18);
        }
        #cl-sidebar.collapsed {
            width: 60px;
            min-width: 60px;
            box-shadow: 2px 0 12px rgba(0,0,0,0.12);
        }
        #cl-main {
            margin-left: 245px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: margin-left 0.38s cubic-bezier(.4,0,.2,1);
        }
        #cl-main.expanded { margin-left: 60px; }

        @media (max-width: 767px) {
            #cl-sidebar { width: 0; min-width: 0; }
            #cl-sidebar.mobile-open { width: 245px; min-width: 245px; }
            #cl-main, #cl-main.expanded { margin-left: 0 !important; }
        }

        /* Logo */
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
            width: 38px; min-width: 38px; height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
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
        #cl-sidebar.collapsed .sb-logo-text { opacity: 0; width: 0; }
        .sb-logo-text strong { display: block; color: #fff; font-size: 15px; font-weight: 800; }
        .sb-logo-text span   { color: rgba(255,255,255,0.4); font-size: 10px; letter-spacing: 2px; }

        /* Badge portal */
        .cl-badge {
            font-size: 9px;
            font-weight: 700;
            background: rgba(39,174,96,0.25);
            color: #6fcf97;
            padding: 2px 7px;
            border-radius: 99px;
            letter-spacing: 0.5px;
            margin-top: 2px;
            display: inline-block;
            transition: opacity 0.25s;
        }
        #cl-sidebar.collapsed .cl-badge { opacity: 0; }

        /* Mini perfil */
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
            width: 38px; min-width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.18);
        }
        .sb-user-info {
            overflow: hidden;
            transition: opacity 0.25s ease, width 0.38s ease;
            white-space: nowrap;
        }
        #cl-sidebar.collapsed .sb-user-info { opacity: 0; width: 0; }
        .sb-user-name { color: #fff; font-size: 13px; font-weight: 600; }
        .sb-user-role { color: rgba(255,255,255,0.42); font-size: 11px; }

        /* Nav */
        .sb-nav {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 10px 8px;
        }
        .sb-nav::-webkit-scrollbar { width: 3px; }
        .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 99px; }

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
            position: relative;
        }
        .sb-item:hover {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.85);
        }
        .sb-item.active {
            background: rgba(39,174,96,0.18);
            color: #fff;
            font-weight: 700;
            border-left-color: #27AE60;
        }
        .sb-item i {
            font-size: 17px;
            flex-shrink: 0;
            min-width: 20px;
            text-align: center;
        }
        .sb-item-label {
            overflow: hidden;
            transition: opacity 0.25s ease, width 0.38s ease;
            white-space: nowrap;
        }
        #cl-sidebar.collapsed .sb-item-label { opacity: 0; width: 0; }

        /* Tooltip al colapsar */
        .sb-item::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 68px;
            top: 50%;
            transform: translateY(-50%);
            background: #0d2018;
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
        #cl-sidebar.collapsed .sb-item:hover::after { opacity: 1; }

        /* Footer */
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
        .sb-footer a:hover { color: #E74C3C; background: rgba(231,76,60,0.08); }
        .sb-footer a i     { font-size: 16px; flex-shrink: 0; min-width: 20px; text-align: center; }
        .sb-footer-label   {
            overflow: hidden;
            transition: opacity 0.25s ease, width 0.38s ease;
            white-space: nowrap;
        }
        #cl-sidebar.collapsed .sb-footer-label { opacity: 0; width: 0; }

        /* ══ TOPBAR CLIENTE ══ */
        #cl-topbar {
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
            width: 38px; height: 38px;
            border-radius: 10px;
            border: 1.5px solid #E8ECF0;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            color: #7F8C8D;
            transition: background 0.15s;
            flex-shrink: 0;
        }
        .topbar-toggle:hover { background: #F4F6F9; }
        .topbar-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .topbar-icon-btn {
            width: 38px; height: 38px;
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
            top: 7px; right: 7px;
            width: 8px; height: 8px;
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
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
            display: flex; align-items: center; justify-content: center;
            font-size: 17px;
        }
        .topbar-user-name { font-size: 13px; font-weight: 700; color: #1C1C1E; line-height: 1.2; }
        .topbar-user-role { font-size: 11px; color: #95A5A6; }

        /* ══ MODAL PERFIL CLIENTE ══ */
        #cl-profile-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 500;
            align-items: center;
            justify-content: center;
        }
        #cl-profile-modal.open { display: flex; animation: fadeIn 0.2s ease; }
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
            background: linear-gradient(135deg, #1a3a2a, #0d2018);
            padding: 28px 28px 22px;
            text-align: center;
            position: relative;
        }
        .profile-close {
            position: absolute; top: 14px; right: 14px;
            width: 30px; height: 30px;
            border-radius: 50%;
            background: rgba(255,255,255,0.12);
            border: none; color: #fff;
            font-size: 17px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        }
        .profile-close:hover { background: rgba(255,255,255,0.22); }
        .profile-avatar-wrap {
            width: 74px; height: 74px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
            display: flex; align-items: center; justify-content: center;
            font-size: 36px;
            margin: 0 auto 12px;
            border: 3px solid rgba(255,255,255,0.22);
        }
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #E8ECF0;
        }
        .profile-tab {
            flex: 1; padding: 12px 4px;
            border: none; background: none;
            font-size: 12.5px; font-weight: 600;
            color: #95A5A6; cursor: pointer;
            border-bottom: 2.5px solid transparent;
            transition: color 0.18s, border-color 0.18s;
        }
        .profile-tab.active { color: #27AE60; border-bottom-color: #27AE60; }
        .profile-body { padding: 20px 24px 24px; }
        .info-row { margin-bottom: 14px; }
        .info-row label {
            font-size: 10.5px; font-weight: 700;
            color: #95A5A6; letter-spacing: 0.8px;
            text-transform: uppercase; display: block; margin-bottom: 3px;
        }
        .info-row .val { font-size: 14px; font-weight: 600; color: #1C1C1E; }
        .avatar-grid {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: 10px;
        }
        .avatar-option {
            padding: 14px 8px; border-radius: 12px;
            border: 2px solid #E8ECF0;
            background: #F8F9FA; cursor: pointer;
            text-align: center; transition: all 0.18s;
        }
        .avatar-option:hover,
        .avatar-option.selected {
            border-color: #27AE60;
            background: #D5F5E3;
        }
        .av-emoji { font-size: 28px; display: block; }
        .av-label { font-size: 10px; color: #95A5A6; font-weight: 600; margin-top: 4px; }
        .config-row {
            display: flex; justify-content: space-between;
            align-items: center; padding: 13px 0;
            border-bottom: 1px solid #F0F2F4;
            font-size: 13.5px; color: #1C1C1E;
        }
        .config-row:last-child { border-bottom: none; }
        .toggle-sw {
            width: 42px; height: 24px; border-radius: 99px;
            background: #CCD1D9; position: relative;
            cursor: pointer; border: none; transition: background 0.2s;
        }
        .toggle-sw.on { background: #27AE60; }
        .toggle-knob {
            width: 18px; height: 18px; border-radius: 50%;
            background: #fff; position: absolute;
            top: 3px; left: 3px; transition: left 0.2s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.18);
        }
        .toggle-sw.on .toggle-knob { left: 21px; }

        /* ══ PAGE CONTENT ══ */
        #cl-page-content { flex: 1; padding: 28px; }

        /* Cards */
        .card {
            border: none; border-radius: 14px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #F0F2F4;
            border-radius: 14px 14px 0 0 !important;
            padding: 16px 20px;
            font-weight: 700; font-size: 14.5px;
        }
        .table { font-size: 13.5px; }
        .table thead th {
            font-size: 11px; font-weight: 700;
            color: #95A5A6; text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #E8ECF0;
            padding: 10px 12px;
        }
        .table tbody td { padding: 11px 12px; vertical-align: middle; }
        .btn { border-radius: 9px; font-size: 13.5px; font-weight: 600; }
        .btn-success { background: #27AE60; border-color: #27AE60; }
        .btn-success:hover { background: #1E8449; border-color: #1E8449; }

        /* Footer */
        #cl-footer {
            border-top: 1px solid #E8ECF0;
            padding: 14px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #95A5A6;
            background: #fff;
        }
        /* ══════════════════════════════════════════
        DROPDOWN NOTIFICACIONES
        ══════════════════════════════════════════ */
        .notif-dropdown {
            width: 340px;
            max-width: 95vw;
            padding: 0;
            border-radius: 14px !important;
            border: 1px solid #E8ECF0 !important;
            box-shadow: 0 12px 40px rgba(0,0,0,0.13) !important;
            overflow: hidden;
            animation: slideDown 0.22s cubic-bezier(.22,1,.36,1) both;
        }
        @keyframes slideDown {
            from { transform: translateY(-8px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .notif-drop-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px 12px;
            border-bottom: 1px solid #F0F2F4;
        }
        .notif-drop-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #1C1C1E;
        }
        .notif-drop-badge {
            font-size: 10.5px;
            font-weight: 700;
            background: #3498DB;
            color: #fff;
            padding: 2px 8px;
            border-radius: 99px;
        }
        .notif-drop-item {
            display: flex;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid #F6F7F9;
            align-items: flex-start;
            transition: background 0.15s;
            cursor: default;
        }
        .notif-drop-item:last-of-type { border-bottom: none; }
        .notif-drop-item:hover        { background: #F8F9FA; }
        .notif-drop-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #3498DB;
            margin-top: 5px;
            flex-shrink: 0;
            box-shadow: 0 0 0 3px rgba(52,152,219,.18);
        }
        .notif-drop-body  { flex: 1; min-width: 0; }
        .notif-drop-item-title {
            font-size: 13px;
            font-weight: 700;
            color: #1C1C1E;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .notif-drop-item-text {
            font-size: 12px;
            color: #7F8C8D;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .notif-drop-empty {
            padding: 28px;
            text-align: center;
            color: #95A5A6;
            font-size: 13px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .notif-drop-empty i {
            font-size: 26px;
            opacity: 0.4;
        }
        .notif-drop-footer {
            padding: 11px 18px;
            border-top: 1px solid #F0F2F4;
            text-align: center;
        }
        .notif-drop-footer a {
            font-size: 12.5px;
            font-weight: 600;
            color: #3498DB;
            text-decoration: none;
        }
        .notif-drop-footer a:hover { text-decoration: underline; }

        /* Modo oscuro dropdown */
        [data-bs-theme="dark"] .notif-dropdown {
            background: #161b27 !important;
            border-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .notif-drop-head {
            border-bottom-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .notif-drop-title {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .notif-drop-item {
            border-bottom-color: #1a1f2e !important;
        }
        [data-bs-theme="dark"] .notif-drop-item:hover {
            background: #1a1f2e !important;
        }
        [data-bs-theme="dark"] .notif-drop-item-title {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .notif-drop-item-text {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .notif-drop-footer {
            border-top-color: #1e2535 !important;
        }
        /* ══ MODO OSCURO ══ */
        [data-bs-theme="dark"] body    { background: #0f1117 !important; }
        [data-bs-theme="dark"] #cl-sidebar { background: linear-gradient(160deg,#0d2018,#061008) !important; }
        [data-bs-theme="dark"] #cl-topbar  { background: #161b27 !important; border-bottom-color: #1e2535 !important; }
        [data-bs-theme="dark"] .topbar-toggle { border-color: #1e2535 !important; color: #8899aa !important; }
        [data-bs-theme="dark"] .topbar-toggle:hover { background: #1e2535 !important; }
        [data-bs-theme="dark"] .topbar-icon-btn { border-color: #1e2535 !important; color: #8899aa !important; }
        [data-bs-theme="dark"] .topbar-icon-btn:hover { background: #1e2535 !important; }
        [data-bs-theme="dark"] .topbar-user { border-color: #1e2535 !important; }
        [data-bs-theme="dark"] .topbar-user:hover { background: #1e2535 !important; }
        [data-bs-theme="dark"] .topbar-user-name { color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .card { background: #161b27 !important; border: 1px solid #1e2535 !important; }
        [data-bs-theme="dark"] .card-header { background: #161b27 !important; border-bottom-color: #1e2535 !important; color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .table { color: #cbd5e1 !important; }
        [data-bs-theme="dark"] .table thead th { background: #1a1f2e !important; color: #6b7a8d !important; border-bottom-color: #1e2535 !important; }
        [data-bs-theme="dark"] .table tbody tr:hover { background: #1a1f2e !important; }
        [data-bs-theme="dark"] .profile-card { background: #161b27 !important; }
        [data-bs-theme="dark"] .profile-tabs { border-bottom-color: #1e2535 !important; }
        [data-bs-theme="dark"] .profile-tab { color: #6b7a8d !important; }
        [data-bs-theme="dark"] .profile-tab.active { color: #27AE60 !important; border-bottom-color: #27AE60 !important; }
        [data-bs-theme="dark"] .info-row label { color: #6b7a8d !important; }
        [data-bs-theme="dark"] .info-row .val  { color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .config-row { border-bottom-color: #1e2535 !important; color: #cbd5e1 !important; }
        [data-bs-theme="dark"] .avatar-option { background: #1e2535 !important; border-color: #2d3a4a !important; }
        [data-bs-theme="dark"] .avatar-option:hover,
        [data-bs-theme="dark"] .avatar-option.selected { background: #0d2018 !important; border-color: #27AE60 !important; }
        [data-bs-theme="dark"] #cl-footer { background: #161b27 !important; border-top-color: #1e2535 !important; color: #4a5568 !important; }
        [data-bs-theme="dark"] .form-control,
        [data-bs-theme="dark"] .form-select { background: #1e2535 !important; border-color: #2d3a4a !important; color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .form-control:focus { border-color: #27AE60 !important; box-shadow: 0 0 0 3px rgba(39,174,96,0.15) !important; }
        /* ══════════════════════════════════════════
        MODO OSCURO — FIX LETRAS GENERALES
        ══════════════════════════════════════════ */

        /* Texto base del body */
        [data-bs-theme="dark"] body {
            color: #cbd5e1 !important;
        }

        /* Títulos y headings */
        [data-bs-theme="dark"] h1,
        [data-bs-theme="dark"] h2,
        [data-bs-theme="dark"] h3,
        [data-bs-theme="dark"] h4,
        [data-bs-theme="dark"] h5,
        [data-bs-theme="dark"] h6 {
            color: #e2e8f0 !important;
        }

        /* Párrafos y spans genéricos */
        [data-bs-theme="dark"] p,
        [data-bs-theme="dark"] span:not(.badge):not(.est-badge):not(.pago-estado):not(.log-badge):not(.notif-drop-badge) {
            color: inherit;
        }

        /* Colores fijos que no se invierten solos */
        [data-bs-theme="dark"] [style*="color:#1C1C1E"],
        [data-bs-theme="dark"] [style*="color: #1C1C1E"] {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] [style*="color:#2C3E50"],
        [data-bs-theme="dark"] [style*="color: #2C3E50"] {
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] [style*="color:#34495E"],
        [data-bs-theme="dark"] [style*="color: #34495E"] {
            color: #a0aec0 !important;
        }
        [data-bs-theme="dark"] [style*="color:#7F8C8D"],
        [data-bs-theme="dark"] [style*="color: #7F8C8D"] {
            color: #718096 !important;
        }

        /* Breadcrumbs */
        [data-bs-theme="dark"] .breadcrumb-item,
        [data-bs-theme="dark"] .breadcrumb-item a {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .breadcrumb-item.active {
            color: #4a5568 !important;
        }
        [data-bs-theme="dark"] .breadcrumb-item + .breadcrumb-item::before {
            color: #4a5568 !important;
        }

        /* Encabezados de página */
        [data-bs-theme="dark"] #page-content h1,
        [data-bs-theme="dark"] #cl-page-content h1 {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] #page-content p,
        [data-bs-theme="dark"] #cl-page-content p {
            color: #6b7a8d !important;
        }

        /* Labels de formularios */
        [data-bs-theme="dark"] label,
        [data-bs-theme="dark"] .form-label {
            color: #8899aa !important;
        }

        /* Texto dentro de cards */
        [data-bs-theme="dark"] .card-body,
        [data-bs-theme="dark"] .card-body p,
        [data-bs-theme="dark"] .card-body span,
        [data-bs-theme="dark"] .card-body div {
            color: #cbd5e1;
        }

        /* KPI values en dashboard */
        [data-bs-theme="dark"] .kpi-label { color: #6b7a8d !important; }
        [data-bs-theme="dark"] .kpi-value { color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .kpi-delta { color: #8899aa !important; }
        [data-bs-theme="dark"] .kpi-lbl   { color: #6b7a8d !important; }
        [data-bs-theme="dark"] .kpi-val   { color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .kpi-sub   { color: #6b7a8d !important; }

        /* Section cards texto */
        [data-bs-theme="dark"] .section-card,
        [data-bs-theme="dark"] .cl-card,
        [data-bs-theme="dark"] .proy-card,
        [data-bs-theme="dark"] .detalle-panel {
            color: #cbd5e1 !important;
        }

        /* Tabla celdas td */
        [data-bs-theme="dark"] .table td {
            color: #cbd5e1 !important;
            border-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .table th {
            color: #6b7a8d !important;
        }

        /* Inputs y selects */
        [data-bs-theme="dark"] input[type="text"],
        [data-bs-theme="dark"] input[type="email"],
        [data-bs-theme="dark"] input[type="password"],
        [data-bs-theme="dark"] input[type="date"],
        [data-bs-theme="dark"] input[type="search"],
        [data-bs-theme="dark"] select,
        [data-bs-theme="dark"] textarea {
            color: #e2e8f0 !important;
            background-color: #1e2535 !important;
            border-color: #2d3a4a !important;
        }
        [data-bs-theme="dark"] input::placeholder,
        [data-bs-theme="dark"] textarea::placeholder {
            color: #4a5568 !important;
        }

        /* Dropdown genérico de Bootstrap */
        [data-bs-theme="dark"] .dropdown-menu {
            background: #161b27 !important;
            border-color: #1e2535 !important;
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .dropdown-item {
            color: #cbd5e1 !important;
        }
        [data-bs-theme="dark"] .dropdown-item:hover,
        [data-bs-theme="dark"] .dropdown-item:focus {
            background: #1e2535 !important;
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .dropdown-divider {
            border-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .dropdown-header {
            color: #6b7a8d !important;
        }

        /* Alert boxes */
        [data-bs-theme="dark"] .alert {
            background: #1e2535 !important;
            border-color: #2d3a4a !important;
            color: #cbd5e1 !important;
        }

        /* Badges de Bootstrap */
        [data-bs-theme="dark"] .badge.bg-secondary {
            background: #2d3a4a !important;
            color: #cbd5e1 !important;
        }

        /* Modal de Bootstrap */
        [data-bs-theme="dark"] .modal-backdrop {
            background: rgba(0,0,0,0.6) !important;
        }

        /* Paginación DataTables */
        [data-bs-theme="dark"] .pagination .page-link {
            background: #1e2535 !important;
            border-color: #2d3a4a !important;
            color: #8899aa !important;
        }
        [data-bs-theme="dark"] .pagination .page-link:hover {
            background: #2d3a4a !important;
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .pagination .page-item.active .page-link {
            background: #3498DB !important;
            border-color: #3498DB !important;
            color: #fff !important;
        }
        [data-bs-theme="dark"] .pagination .page-item.disabled .page-link {
            background: #161b27 !important;
            color: #4a5568 !important;
        }

        /* DataTables info y length */
        [data-bs-theme="dark"] .dataTables_info {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .dataTables_length label,
        [data-bs-theme="dark"] .dataTables_filter label {
            color: #8899aa !important;
        }

        /* Texto específico del dashboard con estilos inline */
        [data-bs-theme="dark"] .section-head h5 { color: #e2e8f0 !important; }
        [data-bs-theme="dark"] .row-item         { color: #cbd5e1 !important; }

        /* Fix cliente: cards de proyectos */
        [data-bs-theme="dark"] .proy-card:hover  { color: #e2e8f0 !important; }

        /* Scrollbar modo oscuro */
        [data-bs-theme="dark"] ::-webkit-scrollbar-track {
            background: #0f1117;
        }
        [data-bs-theme="dark"] ::-webkit-scrollbar-thumb {
            background: #2d3a4a;
        }
        [data-bs-theme="dark"] ::-webkit-scrollbar-thumb:hover {
            background: #3d4a5a;
        }

        /* Fix botones outline en modo oscuro */
        [data-bs-theme="dark"] .btn-outline-primary {
            color: #3498DB !important;
            border-color: #3498DB !important;
        }
        [data-bs-theme="dark"] .btn-outline-primary:hover {
            background: #3498DB !important;
            color: #fff !important;
        }
        [data-bs-theme="dark"] .btn-outline-secondary {
            color: #8899aa !important;
            border-color: #2d3a4a !important;
        }
        [data-bs-theme="dark"] .btn-outline-secondary:hover {
            background: #1e2535 !important;
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .btn-outline-danger {
            color: #E74C3C !important;
            border-color: #E74C3C !important;
        }

        /* Fix filter-card de logs */
        [data-bs-theme="dark"] .filter-card {
            background: #161b27 !important;
            color: #cbd5e1 !important;
        }

        /* Fix footer page */
        [data-bs-theme="dark"] #page-footer,
        [data-bs-theme="dark"] #cl-footer {
            color: #4a5568 !important;
        }
        /* Overlay móvil */
        #cl-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 99;
        }

        .dt-search-custom {
            border: 1.5px solid #E8ECF0;
            border-radius: 9px;
            padding: 6px 12px 6px 34px;
            font-size: 13px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16'%3E%3Cpath fill='%2395A5A6' d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 10px center;
            transition: border-color 0.18s;
        }
        .dt-search-custom:focus {
            border-color: #27AE60;
            box-shadow: 0 0 0 3px rgba(39,174,96,0.12);
            outline: none;
        }
        [data-bs-theme="dark"] .dt-search-custom {
            background-color: #1e2535 !important;
            border-color: #2d3a4a !important;
            color: #e2e8f0 !important;
        }
    </style>
</head>
<body>

<!-- MODAL PERFIL CLIENTE -->
<div id="cl-profile-modal">
    <div class="profile-card">
        <div class="profile-header">
            <button class="profile-close" onclick="clCloseProfile()">×</button>
            <div class="profile-avatar-wrap" id="cl-modal-avatar">
                <?= htmlspecialchars($avatar) ?>
            </div>
            <div style="color:#fff;font-size:16px;font-weight:700;">
                <?= htmlspecialchars($nombre) ?>
            </div>
            <div style="color:rgba(255,255,255,0.5);font-size:12px;margin-top:3px;">
                Sistema Cliente
            </div>
        </div>

        <div class="profile-tabs">
            <button class="profile-tab active" onclick="clSwitchTab('info',this)">Mi perfil</button>
            <button class="profile-tab" onclick="clSwitchTab('avatar',this)">Avatar</button>
            <button class="profile-tab" onclick="clSwitchTab('config',this)">Configuración</button>
        </div>

        <div class="profile-body">

            <!-- Info -->
            <div id="cl-tab-info">
                <div class="info-row">
                    <label>Nombre completo</label>
                    <div class="val"><?= htmlspecialchars($nombre) ?></div>
                </div>
                <div class="info-row">
                    <label>NIT / CI</label>
                    <div class="val"><?= htmlspecialchars($info['nit'] ?? '—') ?></div>
                </div>
                <div class="info-row">
                    <label>Correo electrónico</label>
                    <div class="val"><?= htmlspecialchars($info['email'] ?? '—') ?></div>
                </div>
                <div class="info-row">
                    <label>Teléfono</label>
                    <div class="val"><?= htmlspecialchars($info['telefono'] ?? '—') ?></div>
                </div>
                <div class="info-row">
                    <label>Dirección</label>
                    <div class="val"><?= htmlspecialchars($info['direccion'] ?? '—') ?></div>
                </div>
            </div>

            <!-- Avatar -->
            <div id="cl-tab-avatar" style="display:none;">
                <p style="font-size:13px;color:#95A5A6;margin-bottom:14px;">
                    Elige tu avatar de perfil:
                </p>
                <div class="avatar-grid">
                    <?php foreach ($AVATARES as $av):
                        $sel = ($av['emoji'] === $avatar) ? 'selected' : '';
                    ?>
                    <div class="avatar-option <?= $sel ?>"
                         onclick="clSelectAvatar('<?= $av['emoji'] ?>',this)">
                        <span class="av-emoji"><?= $av['emoji'] ?></span>
                        <div class="av-label"><?= $av['label'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <form method="POST"
                      action="/Constructora-Sistema/modules/auth/save_avatar_cliente.php"
                      style="margin-top:16px;">
                    <input type="hidden" name="avatar" id="cl-avatar-input"
                           value="<?= htmlspecialchars($avatar) ?>">
                    <button type="submit"
                            class="btn btn-success w-100">
                        Guardar avatar
                    </button>
                </form>
            </div>

            <!-- Config -->
            <div id="cl-tab-config" style="display:none;">
                <div class="config-row">
                    <span>Notificaciones del proyecto</span>
                    <button class="toggle-sw on" onclick="this.classList.toggle('on')">
                        <div class="toggle-knob"></div>
                    </button>
                </div>
                <div class="config-row">
                    <span>Alertas de pago</span>
                    <button class="toggle-sw on" onclick="this.classList.toggle('on')">
                        <div class="toggle-knob"></div>
                    </button>
                </div>
                <div style="margin-top:18px;">
                    <button id="cl-theme-btn"
                            style="display:flex;align-items:center;gap:8px;
                                   width:100%;padding:10px 14px;border-radius:10px;
                                   border:1.5px solid #E8ECF0;background:#F8F9FA;
                                   font-size:13.5px;font-weight:600;color:#1C1C1E;
                                   cursor:pointer;transition:background 0.15s;">
                        <i class="bi bi-circle-half"></i>
                        <span id="cl-theme-label">Cambiar modo</span>
                    </button>
                </div>
                <div style="margin-top:10px;">
                    <a href="../../modules/auth/logout_cliente.php"
                       style="display:flex;align-items:center;gap:8px;color:#E74C3C;
                              font-size:13.5px;font-weight:600;text-decoration:none;
                              padding:10px 14px;border-radius:10px;
                              border:1.5px solid #FDECEA;background:#FDECEA22;">
                        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Overlay móvil -->
<div id="cl-overlay" onclick="clToggleSidebar()"></div>

<!-- LAYOUT -->
<div style="display:flex;min-height:100vh;">

    <!-- SIDEBAR -->
    <aside id="cl-sidebar">
        <div class="sb-logo">
            <div class="sb-logo-icon">EC</div>
            <div class="sb-logo-text">
                <strong>Empresa Constructora</strong>
                <span>SISTEMA CLIENTE</span>
            </div>
        </div>

        <div class="sb-user" onclick="clOpenProfile()">
            <div class="sb-avatar" id="cl-sb-avatar">
                <?= htmlspecialchars($avatar) ?>
            </div>
            <div class="sb-user-info">
                <div class="sb-user-name">
                    <?= htmlspecialchars(explode(' ', $nombre)[0]) ?>
                </div>
                <div class="sb-user-role">Sistema Cliente</div>
            </div>
        </div>

        <nav class="sb-nav">
            <a href="/Constructora-Sistema/client/index.php"
               class="sb-item <?= $current_url === 'index.php' ? 'active' : '' ?>"
               data-tooltip="Inicio">
                <i class="bi bi-house"></i>
                <span class="sb-item-label">Inicio</span>
            </a>
            <a href="/Constructora-Sistema/client/mis_proyectos.php"
               class="sb-item <?= $current_url === 'mis_proyectos.php' ? 'active' : '' ?>"
               data-tooltip="Mis proyectos">
                <i class="bi bi-building"></i>
                <span class="sb-item-label">Mis proyectos</span>
            </a>
            <a href="/Constructora-Sistema/client/mis_pagos.php"
               class="sb-item <?= $current_url === 'mis_pagos.php' ? 'active' : '' ?>"
               data-tooltip="Mis pagos">
                <i class="bi bi-credit-card"></i>
                <span class="sb-item-label">Mis pagos</span>
            </a>
            <a href="/Constructora-Sistema/client/mis_notificaciones.php"
               class="sb-item <?= $current_url === 'mis_notificaciones.php' ? 'active' : '' ?>"
               data-tooltip="Notificaciones">
                <i class="bi bi-bell"></i>
                <span class="sb-item-label">Notificaciones</span>
                <?php if ($notif_count > 0): ?>
                <span style="margin-left:auto;background:#E74C3C;color:#fff;
                             font-size:10px;font-weight:700;padding:1px 7px;
                             border-radius:99px;flex-shrink:0;">
                    <?= $notif_count ?>
                </span>
                <?php endif; ?>
            </a>

        </nav>

        <div class="sb-footer">
            <a href="/Constructora-Sistema/modules/auth/logout_cliente.php">
                <i class="bi bi-box-arrow-right"></i>
                <span class="sb-footer-label">Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <div id="cl-main">

        <!-- TOPBAR -->
        <header id="cl-topbar">
            <button class="topbar-toggle" onclick="clToggleSidebar()">
                <i class="bi bi-list"></i>
            </button>


            <div class="topbar-right">
                <!-- Toggle tema -->
                <button class="topbar-icon-btn" id="cl-topbar-theme" title="Cambiar modo">
                    <i class="bi bi-circle-half"></i>
                </button>

                <!-- Notificaciones -->
                <!-- Notificaciones cliente -->
                <div class="dropdown">
                    <button class="topbar-icon-btn dropdown-toggle"
                            id="cl-notif-btn"
                            data-bs-toggle="dropdown"
                            data-bs-auto-close="outside"
                            aria-expanded="false"
                            title="Notificaciones">
                        <i class="bi bi-bell"></i>
                        <?php if ($notif_count > 0): ?>
                        <span class="notif-dot"></span>
                        <?php endif; ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end notif-dropdown shadow"
                        aria-labelledby="cl-notif-btn">

                        <div class="notif-drop-head">
                            <span class="notif-drop-title">Notificaciones</span>
                            <?php if ($notif_count > 0): ?>
                            <span class="notif-drop-badge"
                                style="background:#27AE60;">
                                <?= $notif_count ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php
                        $stmt_cl_notif = $pdo->prepare("
                            SELECT titulo, contenido
                            FROM notificaciones_clientes
                            WHERE id_cliente = ?
                            ORDER BY id_notificacion DESC
                            LIMIT 6
                        ");
                        $stmt_cl_notif->execute([$id_cliente]);
                        $cl_nlist = $stmt_cl_notif->fetchAll();
                        ?>

                        <?php if ($cl_nlist): ?>
                            <?php foreach ($cl_nlist as $nn): ?>
                            <div class="notif-drop-item">
                                <div class="notif-drop-dot"
                                    style="background:#27AE60;
                                            box-shadow:0 0 0 3px rgba(39,174,96,.18);">
                                </div>
                                <div class="notif-drop-body">
                                    <div class="notif-drop-item-title">
                                        <?= htmlspecialchars($nn['titulo']) ?>
                                    </div>
                                    <div class="notif-drop-item-text">
                                        <?= htmlspecialchars($nn['contenido']) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notif-drop-empty">
                                <i class="bi bi-bell-slash"></i>
                                Sin notificaciones nuevas
                            </div>
                        <?php endif; ?>

                        <div class="notif-drop-footer">
                            <a href="../client/mis_notificaciones.php"
                            style="color:#27AE60;">
                                Ver todas las notificaciones →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Usuario -->
                <button class="topbar-user" onclick="clOpenProfile()">
                    <div class="topbar-user-avatar" id="cl-topbar-avatar">
                        <?= htmlspecialchars($avatar) ?>
                    </div>
                    <div>
                        <div class="topbar-user-name">
                            <?= htmlspecialchars(explode(' ', $nombre)[0]) ?>
                        </div>
                        <div class="topbar-user-role">Cliente</div>
                    </div>
                </button>
            </div>
        </header>

        <!-- CONTENIDO -->
        <div id="cl-page-content">