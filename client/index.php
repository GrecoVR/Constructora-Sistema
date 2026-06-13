<?php
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';
require_once '../utils/fecha.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];

// Sus proyectos
$proyectos = $pdo->prepare("
    SELECT p.id_proyecto, p.nombre, p.estado,
           p.fecha_inicio, p.fecha_fin_estimada,
           tp.nombre as tipo,
           AVG(e.porcentaje_avance) as avance_promedio
    FROM proyectos p
    JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
    JOIN contratos c ON c.id_contrato = p.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    LEFT JOIN etapas_proyecto e ON e.id_proyecto = p.id_proyecto
    WHERE co.id_cliente = ?
    GROUP BY p.id_proyecto, p.nombre, p.estado,
             p.fecha_inicio, p.fecha_fin_estimada, tp.nombre
    ORDER BY p.fecha_inicio DESC
");
$proyectos->execute([$id_cliente]);
$proyectos = $proyectos->fetchAll();

// Sus pagos pendientes
$pagos_pendientes = $pdo->prepare("
    SELECT pc.monto, pc.fecha_pago
    FROM pagos_cliente pc
    JOIN contratos c ON c.id_contrato = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    WHERE co.id_cliente = ? AND pc.estado = 'pendiente'
    ORDER BY pc.fecha_pago ASC
");
$pagos_pendientes->execute([$id_cliente]);
$pagos_pendientes = $pagos_pendientes->fetchAll();

// Sus notificaciones
$notificaciones = $pdo->prepare("
    SELECT titulo, contenido
    FROM notificaciones_clientes
    WHERE id_cliente = ?
    ORDER BY id_notificacion DESC
    LIMIT 5
");
$notificaciones->execute([$id_cliente]);
$notificaciones = $notificaciones->fetchAll();

// Totales
$total_pagado = $pdo->prepare("
    SELECT COALESCE(SUM(pc.monto),0) as total
    FROM pagos_cliente pc
    JOIN contratos c ON c.id_contrato = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    WHERE co.id_cliente = ? AND pc.estado = 'completado'
");
$total_pagado->execute([$id_cliente]);
$total_pagado = $total_pagado->fetch()['total'];

$total_pendiente = array_sum(array_column($pagos_pendientes, 'monto'));
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Cliente — Vértice</title>

    <!-- Anti-parpadeo modo oscuro -->
    <script>
    (function(){
        var t = localStorage.getItem('theme') || 'auto';
        var val = t === 'auto'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : t;
        document.documentElement.setAttribute('data-bs-theme', val);
    })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #F4F6F9;
            min-height: 100vh;
        }

        /* ── Topbar cliente ── */
        #client-topbar {
            height: 62px;
            background: #fff;
            border-bottom: 1px solid #E8ECF0;
            display: flex;
            align-items: center;
            padding: 0 28px;
            gap: 14px;
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
        }

        .client-logo {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 900;
            color: #fff;
            flex-shrink: 0;
        }

        .client-brand {
            font-size: 15px;
            font-weight: 800;
            color: #1C1C1E;
        }

        .client-nav {
            display: flex;
            gap: 4px;
            margin-left: 20px;
        }

        .client-nav a {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: #7F8C8D;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .client-nav a:hover,
        .client-nav a.active {
            background: #EBF5FB;
            color: #2980B9;
            font-weight: 600;
        }

        .client-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .client-user-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px 5px 6px;
            border-radius: 99px;
            background: #F4F6F9;
            border: 1px solid #E8ECF0;
            font-size: 13px;
            font-weight: 600;
            color: #1C1C1E;
        }

        .client-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27AE60, #2ECC71);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 800;
            color: #fff;
        }

        /* ── Page content ── */
        .client-content {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px 20px 48px;
        }

        /* ── KPI cards ── */
        .client-kpi {
            background: #fff;
            border-radius: 14px;
            padding: 20px 22px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            border-left: 4px solid transparent;
            opacity: 0;
            transform: translateY(20px);
            animation: cardIn 0.5s cubic-bezier(.22,1,.36,1) forwards;
        }
        @keyframes cardIn {
            to { opacity: 1; transform: translateY(0); }
        }
        .client-kpi-label {
            font-size: 10.5px;
            font-weight: 700;
            color: #95A5A6;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .client-kpi-value {
            font-size: 24px;
            font-weight: 800;
            color: #1C1C1E;
            line-height: 1.1;
        }

        /* ── Section cards ── */
        .client-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-bottom: 20px;
            transition: box-shadow 0.22s, transform 0.22s;
        }
        .client-card:hover {
            box-shadow: 0 6px 24px rgba(0,0,0,0.09);
            transform: translateY(-2px);
        }
        .client-card-head {
            padding: 16px 22px;
            border-bottom: 1px solid #F0F2F4;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14.5px;
            font-weight: 700;
            color: #1C1C1E;
        }
        .client-card-body {
            padding: 0;
        }

        /* ── Progress bar ── */
        .prog-wrap {
            background: #E8ECF2;
            border-radius: 99px;
            height: 7px;
            overflow: hidden;
            flex: 1;
        }
        .prog-fill {
            height: 100%;
            border-radius: 99px;
            width: 0;
            transition: width 1.1s cubic-bezier(.22,1,.36,1);
        }

        /* ── Row item ── */
        .client-row {
            padding: 14px 22px;
            border-bottom: 1px solid #F6F7F9;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .client-row:last-child { border-bottom: none; }

        /* ── Estado badge ── */
        .est-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 99px;
            white-space: nowrap;
        }

        /* ── Notif item ── */
        .notif-item {
            padding: 14px 22px;
            border-bottom: 1px solid #F6F7F9;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-title {
            font-size: 13px;
            font-weight: 700;
            color: #1C1C1E;
            margin-bottom: 3px;
        }
        .notif-body {
            font-size: 12.5px;
            color: #7F8C8D;
            line-height: 1.5;
        }

        /* ── Logout btn ── */
        .btn-logout {
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid #E8ECF0;
            background: none;
            font-size: 12.5px;
            color: #E74C3C;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s;
        }
        .btn-logout:hover {
            background: #FDECEA;
        }

        /* ── Modo oscuro cliente ── */
        [data-bs-theme="dark"] body {
            background: #0f1117 !important;
        }
        [data-bs-theme="dark"] #client-topbar {
            background: #161b27 !important;
            border-bottom-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .client-brand {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .client-nav a {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .client-nav a:hover,
        [data-bs-theme="dark"] .client-nav a.active {
            background: #1a2d3d !important;
            color: #56aee8 !important;
        }
        [data-bs-theme="dark"] .client-user-badge {
            background: #1e2535 !important;
            border-color: #2d3a4a !important;
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .client-kpi {
            background: #161b27 !important;
        }
        [data-bs-theme="dark"] .client-kpi-value {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .client-card {
            background: #161b27 !important;
        }
        [data-bs-theme="dark"] .client-card-head {
            color: #e2e8f0 !important;
            border-bottom-color: #1e2535 !important;
        }
        [data-bs-theme="dark"] .client-row {
            border-bottom-color: #1a1f2e !important;
        }
        [data-bs-theme="dark"] .notif-item {
            border-bottom-color: #1a1f2e !important;
        }
        [data-bs-theme="dark"] .notif-title {
            color: #e2e8f0 !important;
        }
        [data-bs-theme="dark"] .notif-body {
            color: #6b7a8d !important;
        }
        [data-bs-theme="dark"] .prog-wrap {
            background: #1e2535 !important;
        }

        /* ── Toggle modo oscuro ── */
        .theme-toggle {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1px solid #E8ECF0;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #7F8C8D;
            transition: background 0.15s;
        }
        .theme-toggle:hover { background: #F4F6F9; }

        [data-bs-theme="dark"] .theme-toggle {
            border-color: #1e2535 !important;
            color: #8899aa !important;
        }
        [data-bs-theme="dark"] .theme-toggle:hover {
            background: #1e2535 !important;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<header id="client-topbar">
    <div class="client-logo">V</div>
    <span class="client-brand">Vértice</span>

    <nav class="client-nav">
        <a href="index.php" class="active">
            <i class="bi bi-house me-1"></i>Inicio
        </a>
        <a href="mis_proyectos.php">
            <i class="bi bi-building me-1"></i>Proyectos
        </a>
        <a href="mis_pagos.php">
            <i class="bi bi-credit-card me-1"></i>Pagos
        </a>
        <a href="mis_notificaciones.php">
            <i class="bi bi-bell me-1"></i>Notificaciones
        </a>
    </nav>

    <div class="client-right">
        <!-- Toggle oscuro/claro -->
        <button class="theme-toggle" id="client-theme-btn" title="Cambiar modo">
            <i class="bi bi-circle-half"></i>
        </button>

        <div class="client-user-badge">
            <div class="client-avatar">
                <?= mb_strtoupper(mb_substr($_SESSION['nombre_cliente'], 0, 1)) ?>
            </div>
            <?= htmlspecialchars(explode(' ', $_SESSION['nombre_cliente'])[0]) ?>
        </div>

        <a href="../modules/auth/logout_cliente.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i> Salir
        </a>
    </div>
</header>

<!-- CONTENIDO -->
<div class="client-content">

    <!-- Saludo -->
    <div style="margin-bottom:24px;">
        <h1 style="font-size:21px;font-weight:800;color:#1C1C1E;">
            Bienvenido, <?= htmlspecialchars(explode(' ', $_SESSION['nombre_cliente'])[0]) ?>
        </h1>
        <p style="font-size:13px;color:#95A5A6;margin-top:3px;">
            Portal de seguimiento de proyectos · <?= date('d/m/Y') ?>
        </p>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="client-kpi" style="border-left-color:#27AE60;animation-delay:0s;">
                <div class="client-kpi-label">Proyectos activos</div>
                <div class="client-kpi-value">
                    <?= count(array_filter($proyectos, fn($p) => $p['estado'] === 'ejecucion')) ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="client-kpi" style="border-left-color:#3498DB;animation-delay:0.08s;">
                <div class="client-kpi-label">En planificación</div>
                <div class="client-kpi-value">
                    <?= count(array_filter($proyectos, fn($p) => $p['estado'] === 'planificacion')) ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="client-kpi" style="border-left-color:#27AE60;animation-delay:0.16s;">
                <div class="client-kpi-label">Total pagado</div>
                <div class="client-kpi-value" style="color:#27AE60;">
                    Bs <?= number_format($total_pagado, 0, '.', ',') ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="client-kpi" style="border-left-color:#E67E22;animation-delay:0.24s;">
                <div class="client-kpi-label">Saldo pendiente</div>
                <div class="client-kpi-value" style="color:#E67E22;">
                    Bs <?= number_format($total_pendiente, 0, '.', ',') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        <!-- Proyectos -->
        <div class="col-12 col-lg-7">
            <div class="client-card">
                <div class="client-card-head">
                    <i class="bi bi-building-fill-gear" style="color:#3498DB;"></i>
                    Mis proyectos
                    <span style="margin-left:auto;font-size:11px;font-weight:700;
                                 background:#EBF5FB;color:#2980B9;
                                 padding:3px 10px;border-radius:99px;">
                        <?= count($proyectos) ?>
                    </span>
                </div>
                <div class="client-card-body">
                    <?php if ($proyectos): ?>
                        <?php foreach ($proyectos as $p):
                            $avance = round($p['avance_promedio'] ?? 0);
                            $dias   = dias_restantes($p['fecha_fin_estimada']);
                            $est    = $p['estado'];
                            $est_bg    = $est === 'ejecucion' ? '#D4EDDA' : ($est === 'planificacion' ? '#D1ECF1' : '#D6D8D9');
                            $est_color = $est === 'ejecucion' ? '#155724' : ($est === 'planificacion' ? '#0C5460' : '#383D41');
                            $est_label = $est === 'ejecucion' ? 'En ejecución' : ($est === 'planificacion' ? 'Planificación' : ucfirst($est));
                            $prog_color = $est === 'planificacion' ? '#95A5A6' : ($avance < 30 ? '#E74C3C' : '#27AE60');
                        ?>
                        <div class="client-row">
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;justify-content:space-between;
                                            align-items:flex-start;gap:8px;margin-bottom:7px;">
                                    <div>
                                        <div style="font-size:13.5px;font-weight:700;color:#1C1C1E;">
                                            <?= htmlspecialchars($p['nombre']) ?>
                                        </div>
                                        <div style="font-size:11.5px;color:#95A5A6;margin-top:2px;">
                                            <?= htmlspecialchars($p['tipo']) ?>
                                            · <?= formatoFechaCorta($p['fecha_inicio']) ?>
                                        </div>
                                    </div>
                                    <span class="est-badge"
                                          style="background:<?= $est_bg ?>;color:<?= $est_color ?>;">
                                        <?= $est_label ?>
                                    </span>
                                </div>

                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="prog-wrap">
                                        <div class="prog-fill"
                                             data-width="<?= $avance ?>"
                                             style="background:<?= $prog_color ?>;"></div>
                                    </div>
                                    <span style="font-size:11.5px;font-weight:700;
                                                 color:#2C3E50;min-width:30px;text-align:right;">
                                        <?= $avance ?>%
                                    </span>
                                    <span style="font-size:11px;min-width:65px;text-align:right;
                                                 color:<?= $dias < 0 ? '#E74C3C' : ($dias < 30 ? '#E67E22' : '#95A5A6') ?>;">
                                        <?php if ($dias < 0): ?>
                                            Vencido <?= abs($dias) ?>d
                                        <?php elseif ($dias === 0): ?>
                                            Vence hoy
                                        <?php else: ?>
                                            <?= $dias ?>d restantes
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:30px;text-align:center;color:#95A5A6;font-size:13.5px;">
                            No tienes proyectos registrados.
                        </div>
                    <?php endif; ?>

                    <div style="padding:14px 22px;">
                        <a href="mis_proyectos.php"
                           style="display:block;text-align:center;padding:9px;
                                  border-radius:9px;border:1.5px dashed #27AE60;
                                  background:#D5F5E3;color:#1E8449;font-size:13px;
                                  font-weight:600;text-decoration:none;
                                  transition:background 0.15s;"
                           onmouseover="this.style.background='#ABEBC6'"
                           onmouseout="this.style.background='#D5F5E3'">
                            Ver detalle de proyectos →
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna derecha -->
        <div class="col-12 col-lg-5">

            <!-- Pagos pendientes -->
            <div class="client-card">
                <div class="client-card-head">
                    <i class="bi bi-clock-history" style="color:#E67E22;"></i>
                    Pagos pendientes
                    <?php if ($pagos_pendientes): ?>
                    <span style="margin-left:auto;font-size:11px;font-weight:700;
                                 background:#FDECEA;color:#E74C3C;
                                 padding:3px 10px;border-radius:99px;">
                        <?= count($pagos_pendientes) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="client-card-body">
                    <?php if ($pagos_pendientes): ?>
                        <?php foreach ($pagos_pendientes as $pg):
                            $dias = dias_restantes($pg['fecha_pago']);
                        ?>
                        <div class="client-row" style="align-items:center;">
                            <div style="flex:1;">
                                <div style="font-size:15px;font-weight:800;color:#E74C3C;">
                                    Bs <?= number_format($pg['monto'], 2) ?>
                                </div>
                                <div style="font-size:11.5px;color:#95A5A6;margin-top:2px;">
                                    Vence: <?= formatoFechaCorta($pg['fecha_pago']) ?>
                                </div>
                            </div>
                            <span style="font-size:11px;font-weight:700;
                                         color:<?= $dias < 0 ? '#E74C3C' : '#E67E22' ?>;">
                                <?= $dias < 0 ? 'Vencido' : "En {$dias}d" ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding:24px;text-align:center;color:#27AE60;font-size:13px;">
                            <i class="bi bi-check-circle-fill"
                               style="font-size:22px;display:block;margin-bottom:6px;"></i>
                            Sin pagos pendientes
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notificaciones -->
            <div class="client-card">
                <div class="client-card-head">
                    <i class="bi bi-bell-fill" style="color:#3498DB;"></i>
                    Notificaciones recientes
                </div>
                <div class="client-card-body">
                    <?php if ($notificaciones): ?>
                        <?php foreach ($notificaciones as $n): ?>
                        <div class="notif-item">
                            <div class="notif-title">
                                <?= htmlspecialchars($n['titulo']) ?>
                            </div>
                            <div class="notif-body">
                                <?= htmlspecialchars($n['contenido']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div style="padding:12px 22px;">
                            <a href="mis_notificaciones.php"
                               style="font-size:12.5px;color:#3498DB;
                                      font-weight:600;text-decoration:none;">
                                Ver todas las notificaciones →
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="padding:24px;text-align:center;
                                    color:#95A5A6;font-size:13px;">
                            No tienes notificaciones.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Barras de progreso animadas
window.addEventListener('load', function() {
    document.querySelectorAll('.prog-fill[data-width]').forEach(function(bar) {
        setTimeout(function() {
            bar.style.width = bar.dataset.width + '%';
        }, 200);
    });
});

// Toggle modo oscuro
(function(){
    var btn = document.getElementById('client-theme-btn');
    var icon = btn.querySelector('i');

    function applyTheme(t) {
        var val = t === 'auto'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : t;
        document.documentElement.setAttribute('data-bs-theme', val);
        icon.className = val === 'dark'
            ? 'bi bi-sun-fill'
            : 'bi bi-moon-stars-fill';
        localStorage.setItem('theme', t);
    }

    // Aplicar estado inicial
    var saved = localStorage.getItem('theme') || 'auto';
    applyTheme(saved);

    // Toggle entre dark y light
    btn.addEventListener('click', function() {
        var current = document.documentElement.getAttribute('data-bs-theme');
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });
})();
</script>
</body>
</html>