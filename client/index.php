<?php
require_once '../config/session.php';
require_once '../middleware/auth_cliente.php';
require_once '../config/database.php';

$pdo        = conectar();
$id_cliente = $_SESSION['id_cliente'];
$nombre     = $_SESSION['nombre_cliente'];

// Proyectos del cliente
$stmt = $pdo->prepare("
    SELECT p.id_proyecto, p.nombre, p.estado,
           p.fecha_inicio, p.fecha_fin_estimada,
           tp.nombre AS tipo,
           COALESCE(AVG(ep.porcentaje_avance), 0) AS avance
    FROM proyectos p
    JOIN tipos_proyecto tp  ON tp.id_tipo_proyecto = p.id_tipo_proyecto
    JOIN contratos c        ON c.id_contrato       = p.id_contrato
    JOIN cotizaciones co    ON co.id_cotizacion    = c.id_cotizacion
    LEFT JOIN etapas_proyecto ep ON ep.id_proyecto = p.id_proyecto
    WHERE co.id_cliente = ?
    GROUP BY p.id_proyecto, p.nombre, p.estado,
             p.fecha_inicio, p.fecha_fin_estimada, tp.nombre
    ORDER BY p.fecha_inicio DESC
");
$stmt->execute([$id_cliente]);
$proyectos = $stmt->fetchAll();

// Pagos completados
$stmt2 = $pdo->prepare("
    SELECT COALESCE(SUM(pc.monto), 0) AS total
    FROM pagos_cliente pc
    JOIN contratos c    ON c.id_contrato    = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    WHERE co.id_cliente = ? AND pc.estado = 'completado'
");
$stmt2->execute([$id_cliente]);
$total_pagado = $stmt2->fetchColumn();

// Pagos pendientes
$stmt3 = $pdo->prepare("
    SELECT pc.monto, pc.fecha_pago
    FROM pagos_cliente pc
    JOIN contratos c    ON c.id_contrato    = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    WHERE co.id_cliente = ? AND pc.estado = 'pendiente'
    ORDER BY pc.fecha_pago ASC
");
$stmt3->execute([$id_cliente]);
$pagos_pendientes = $stmt3->fetchAll();
$total_pendiente  = array_sum(array_column($pagos_pendientes, 'monto'));

// Notificaciones recientes
$stmt4 = $pdo->prepare("
    SELECT titulo, contenido
    FROM notificaciones_clientes
    WHERE id_cliente = ?
    ORDER BY id_notificacion DESC
    LIMIT 4
");
$stmt4->execute([$id_cliente]);
$notifs = $stmt4->fetchAll();

// Última etapa activa
$stmt5 = $pdo->prepare("
    SELECT ep.nombre AS etapa, ep.porcentaje_avance,
           p.nombre AS proyecto
    FROM etapas_proyecto ep
    JOIN proyectos p    ON p.id_proyecto   = ep.id_proyecto
    JOIN contratos c    ON c.id_contrato   = p.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    WHERE co.id_cliente = ?
      AND ep.estado = 'ejecucion'
    ORDER BY ep.porcentaje_avance DESC
    LIMIT 3
");
$stmt5->execute([$id_cliente]);
$etapas_activas = $stmt5->fetchAll();

function diasRestantes($fecha) {
    $hoy  = new DateTime(date('Y-m-d'));
    $fin  = new DateTime($fecha);
    $diff = $hoy->diff($fin);
    return $diff->invert ? -$diff->days : $diff->days;
}
?>
<?php require_once '../modules/layouts/header_cliente.php'; ?>

<style>
/* ── Animaciones de entrada ── */
.fade-up {
    opacity: 0;
    transform: translateY(22px);
    animation: fadeUp 0.55s cubic-bezier(.22,1,.36,1) forwards;
}
@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}
.delay-1 { animation-delay: 0.05s; }
.delay-2 { animation-delay: 0.12s; }
.delay-3 { animation-delay: 0.19s; }
.delay-4 { animation-delay: 0.26s; }
.delay-5 { animation-delay: 0.33s; }
.delay-6 { animation-delay: 0.40s; }

/* ── KPI cards ── */
.cl-kpi {
    background: #fff;
    border-radius: 14px;
    padding: 20px 22px 18px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    border-left: 4px solid transparent;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.22s, transform 0.22s;
}
.cl-kpi:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.10);
    transform: translateY(-2px);
}
.cl-kpi .wm {
    position: absolute;
    right: 14px; top: 50%;
    transform: translateY(-50%);
    font-size: 48px;
    opacity: 0.05;
    pointer-events: none;
    user-select: none;
}
.kpi-lbl {
    font-size: 10.5px;
    font-weight: 700;
    color: #95A5A6;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.kpi-val {
    font-size: 24px;
    font-weight: 800;
    color: #1C1C1E;
    line-height: 1.1;
    margin-bottom: 4px;
}
.kpi-sub {
    font-size: 12px;
    font-weight: 600;
    color: #95A5A6;
}

/* ── Section cards ── */
.cl-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: box-shadow 0.22s, transform 0.22s;
}
.cl-card:hover {
    box-shadow: 0 6px 24px rgba(0,0,0,0.09);
    transform: translateY(-2px);
}
.cl-card-head {
    padding: 16px 20px;
    border-bottom: 1px solid #F0F2F4;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 700;
    color: #1C1C1E;
}
.cl-row {
    padding: 14px 20px;
    border-bottom: 1px solid #F6F7F9;
}
.cl-row:last-child { border-bottom: none; }

/* ── Progress ── */
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

/* ── Estado badge ── */
.est-badge {
    font-size: 10.5px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 99px;
    white-space: nowrap;
}

/* ── Timeline etapas ── */
.timeline-item {
    display: flex;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid #F6F7F9;
    align-items: flex-start;
}
.timeline-item:last-child { border-bottom: none; }
.timeline-dot {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #27AE60, #2ECC71);
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(39,174,96,0.3);
}

/* ── Notif item ── */
.notif-row {
    padding: 13px 20px;
    border-bottom: 1px solid #F6F7F9;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.notif-row:last-child { border-bottom: none; }
.notif-dot-icon {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #27AE60;
    margin-top: 5px;
    flex-shrink: 0;
}

/* ── Modo oscuro ── */
[data-bs-theme="dark"] .cl-kpi,
[data-bs-theme="dark"] .cl-card {
    background: #161b27 !important;
    box-shadow: 0 2px 14px rgba(0,0,0,0.25) !important;
}
[data-bs-theme="dark"] .cl-card-head {
    color: #e2e8f0 !important;
    border-bottom-color: #1e2535 !important;
}
[data-bs-theme="dark"] .kpi-val  { color: #e2e8f0 !important; }
[data-bs-theme="dark"] .cl-row,
[data-bs-theme="dark"] .timeline-item,
[data-bs-theme="dark"] .notif-row { border-bottom-color: #1a1f2e !important; }
[data-bs-theme="dark"] .prog-wrap { background: #1e2535 !important; }
</style>

<!-- ══ ENCABEZADO ══ -->
<div class="fade-up" style="margin-bottom:26px;">
    <h1 style="font-size:21px;font-weight:800;color:#1C1C1E;letter-spacing:-0.3px;">
        Bienvenido, <?= htmlspecialchars(explode(' ', $nombre)[0]) ?>
    </h1>
    <p style="font-size:13px;color:#95A5A6;margin-top:4px;">
        Portal de seguimiento de proyectos
        · <?= date('d/m/Y') ?>
    </p>
</div>

<!-- ══ KPIs ══ -->
<div class="row g-3 mb-4">

    <div class="col-6 col-lg-3 fade-up delay-1">
        <div class="cl-kpi" style="border-left-color:#3498DB;">
            <div class="wm">◫</div>
            <div class="kpi-lbl">Proyectos totales</div>
            <div class="kpi-val"><?= count($proyectos) ?></div>
            <div class="kpi-sub">
                <?= count(array_filter($proyectos, fn($p) => $p['estado'] === 'ejecucion')) ?>
                en ejecución
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3 fade-up delay-2">
        <div class="cl-kpi" style="border-left-color:#27AE60;">
            <div class="wm">↑</div>
            <div class="kpi-lbl">Total pagado</div>
            <div class="kpi-val" style="color:#27AE60;font-size:20px;">
                Bs <?= number_format($total_pagado, 0, '.', ',') ?>
            </div>
            <div class="kpi-sub">Pagos completados</div>
        </div>
    </div>

    <div class="col-6 col-lg-3 fade-up delay-3">
        <div class="cl-kpi" style="border-left-color:#E67E22;">
            <div class="wm">!</div>
            <div class="kpi-lbl">Saldo pendiente</div>
            <div class="kpi-val" style="color:#E67E22;font-size:20px;">
                Bs <?= number_format($total_pendiente, 0, '.', ',') ?>
            </div>
            <div class="kpi-sub">
                <?= count($pagos_pendientes) ?> pago(s) por realizar
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3 fade-up delay-4">
        <div class="cl-kpi" style="border-left-color:#8E44AD;">
            <div class="wm">◈</div>
            <div class="kpi-lbl">Notificaciones</div>
            <div class="kpi-val"><?= count($notifs) ?></div>
            <div class="kpi-sub">Mensajes recientes</div>
        </div>
    </div>

</div>

<!-- ══ PROYECTOS + ETAPAS ACTIVAS ══ -->
<div class="row g-3 mb-3">

    <!-- Mis proyectos -->
    <div class="col-12 col-lg-7 fade-up delay-2">
        <div class="cl-card h-100">
            <div class="cl-card-head">
                <i class="bi bi-building-fill-gear" style="color:#3498DB;"></i>
                Mis proyectos
                <span style="margin-left:auto;font-size:11px;font-weight:700;
                             background:#EBF5FB;color:#2980B9;
                             padding:3px 10px;border-radius:99px;">
                    <?= count($proyectos) ?>
                </span>
                <a href="mis_proyectos.php"
                   style="font-size:12px;color:#27AE60;font-weight:600;
                          text-decoration:none;margin-left:8px;">
                    Ver todos →
                </a>
            </div>

            <?php if ($proyectos): ?>
                <?php foreach ($proyectos as $p):
                    $avance = round($p['avance']);
                    $dias   = diasRestantes($p['fecha_fin_estimada']);
                    $est    = $p['estado'];
                    $est_bg    = match($est) {
                        'ejecucion'    => '#D4EDDA',
                        'planificacion'=> '#D1ECF1',
                        'finalizado'   => '#D6D8D9',
                        default        => '#F4F6F9'
                    };
                    $est_color = match($est) {
                        'ejecucion'    => '#155724',
                        'planificacion'=> '#0C5460',
                        'finalizado'   => '#383D41',
                        default        => '#555'
                    };
                    $est_lbl = match($est) {
                        'ejecucion'    => 'En ejecución',
                        'planificacion'=> 'Planificación',
                        'finalizado'   => 'Finalizado',
                        default        => ucfirst($est)
                    };
                    $prog_color = $est === 'planificacion'
                        ? '#95A5A6'
                        : ($avance >= 70 ? '#27AE60' : ($avance >= 30 ? '#3498DB' : '#E74C3C'));
                ?>
                <div class="cl-row">
                    <div style="display:flex;justify-content:space-between;
                                align-items:flex-start;gap:8px;margin-bottom:8px;">
                        <div>
                            <a href="mis_proyectos.php?id=<?= $p['id_proyecto'] ?>"
                               style="font-size:13.5px;font-weight:700;
                                      color:#1C1C1E;text-decoration:none;">
                                <?= htmlspecialchars($p['nombre']) ?>
                            </a>
                            <div style="font-size:11.5px;color:#95A5A6;margin-top:2px;">
                                <?= htmlspecialchars($p['tipo']) ?>
                                · Inicio: <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?>
                            </div>
                        </div>
                        <span class="est-badge"
                              style="background:<?= $est_bg ?>;color:<?= $est_color ?>;">
                            <?= $est_lbl ?>
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
                        <span style="font-size:11px;min-width:72px;text-align:right;
                                     color:<?= $dias < 0 ? '#E74C3C'
                                         : ($dias < 30 ? '#E67E22' : '#95A5A6') ?>;">
                            <?php if ($dias < 0): ?>
                                Vencido (<?= abs($dias) ?>d)
                            <?php elseif ($dias === 0): ?>
                                Vence hoy
                            <?php else: ?>
                                <?= $dias ?>d restantes
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div style="padding:32px;text-align:center;color:#95A5A6;font-size:13.5px;">
                    <i class="bi bi-building"
                       style="font-size:28px;display:block;margin-bottom:10px;opacity:0.4;"></i>
                    No tienes proyectos registrados aún.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Etapas activas -->
    <div class="col-12 col-lg-5 fade-up delay-3">
        <div class="cl-card h-100">
            <div class="cl-card-head">
                <i class="bi bi-layers-half" style="color:#27AE60;"></i>
                Etapas en curso
            </div>

            <?php if ($etapas_activas): ?>
                <?php foreach ($etapas_activas as $etapa): ?>
                <div class="timeline-item">
                    <div class="timeline-dot">
                        <i class="bi bi-hammer" style="font-size:14px;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:700;color:#1C1C1E;
                                    margin-bottom:2px;">
                            <?= htmlspecialchars($etapa['etapa']) ?>
                        </div>
                        <div style="font-size:11.5px;color:#95A5A6;margin-bottom:7px;">
                            <?= htmlspecialchars($etapa['proyecto']) ?>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="prog-wrap">
                                <div class="prog-fill"
                                     data-width="<?= $etapa['porcentaje_avance'] ?>"
                                     style="background:#27AE60;"></div>
                            </div>
                            <span style="font-size:11.5px;font-weight:700;
                                         color:#27AE60;min-width:30px;text-align:right;">
                                <?= $etapa['porcentaje_avance'] ?>%
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div style="padding:32px;text-align:center;color:#95A5A6;font-size:13.5px;">
                    <i class="bi bi-layers"
                       style="font-size:28px;display:block;margin-bottom:10px;opacity:0.4;"></i>
                    Sin etapas activas en este momento.
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ══ PAGOS + NOTIFICACIONES ══ -->
<div class="row g-3">

    <!-- Pagos pendientes -->
    <div class="col-12 col-lg-5 fade-up delay-4">
        <div class="cl-card">
            <div class="cl-card-head">
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

            <?php if ($pagos_pendientes): ?>
                <?php foreach ($pagos_pendientes as $pg):
                    $dias = diasRestantes($pg['fecha_pago']);
                ?>
                <div class="cl-row" style="display:flex;align-items:center;gap:14px;">
                    <div style="width:36px;height:36px;border-radius:50%;
                                background:linear-gradient(135deg,#FDECEA,#FADBD8);
                                display:flex;align-items:center;justify-content:center;
                                flex-shrink:0;">
                        <i class="bi bi-receipt"
                           style="color:#E74C3C;font-size:16px;"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:15px;font-weight:800;color:#E74C3C;">
                            Bs <?= number_format($pg['monto'], 2, '.', ',') ?>
                        </div>
                        <div style="font-size:11.5px;color:#95A5A6;margin-top:1px;">
                            Vence: <?= date('d/m/Y', strtotime($pg['fecha_pago'])) ?>
                        </div>
                    </div>
                    <span style="font-size:11px;font-weight:700;white-space:nowrap;
                                 color:<?= $dias < 0 ? '#E74C3C'
                                     : ($dias < 7 ? '#E67E22' : '#95A5A6') ?>;">
                        <?php if ($dias < 0): ?>
                            Vencido
                        <?php elseif ($dias === 0): ?>
                            Hoy
                        <?php else: ?>
                            En <?= $dias ?>d
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>

                <div style="padding:14px 20px;">
                    <a href="mis_pagos.php"
                       style="display:block;text-align:center;padding:9px;
                              border-radius:9px;border:1.5px dashed #27AE60;
                              background:#D5F5E3;color:#1E8449;font-size:13px;
                              font-weight:600;text-decoration:none;
                              transition:background 0.15s;"
                       onmouseover="this.style.background='#ABEBC6'"
                       onmouseout="this.style.background='#D5F5E3'">
                        Ver historial de pagos →
                    </a>
                </div>

            <?php else: ?>
                <div style="padding:28px;text-align:center;color:#27AE60;font-size:13px;">
                    <i class="bi bi-check-circle-fill"
                       style="font-size:26px;display:block;margin-bottom:8px;"></i>
                    Sin pagos pendientes
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notificaciones -->
    <div class="col-12 col-lg-7 fade-up delay-5">
        <div class="cl-card">
            <div class="cl-card-head">
                <i class="bi bi-bell-fill" style="color:#3498DB;"></i>
                Notificaciones recientes
                <a href="mis_notificaciones.php"
                   style="font-size:12px;color:#27AE60;font-weight:600;
                          text-decoration:none;margin-left:auto;">
                    Ver todas →
                </a>
            </div>

            <?php if ($notifs): ?>
                <?php foreach ($notifs as $n): ?>
                <div class="notif-row">
                    <div class="notif-dot-icon"></div>
                    <div>
                        <div style="font-size:13px;font-weight:700;color:#1C1C1E;
                                    margin-bottom:3px;">
                            <?= htmlspecialchars($n['titulo']) ?>
                        </div>
                        <div style="font-size:12.5px;color:#7F8C8D;line-height:1.5;">
                            <?= htmlspecialchars($n['contenido']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div style="padding:28px;text-align:center;color:#95A5A6;font-size:13px;">
                    <i class="bi bi-bell-slash"
                       style="font-size:26px;display:block;margin-bottom:8px;opacity:0.4;"></i>
                    Sin notificaciones recientes.
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
// Barras de progreso animadas
window.addEventListener('load', function () {
    document.querySelectorAll('.prog-fill[data-width]').forEach(function (bar) {
        setTimeout(function () {
            bar.style.width = bar.dataset.width + '%';
        }, 300);
    });
});
</script>

<?php require_once '../modules/layouts/footer_cliente.php'; ?>