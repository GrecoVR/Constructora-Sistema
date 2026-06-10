<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../config/database.php';

registrarAccion('Accedió al dashboard principal');

$pdo      = conectar();
$permisos = $_SESSION['permisos'];
$roles    = $_SESSION['roles'];
$nombre   = $_SESSION['nombre'];
$avatar   = $_SESSION['avatar'] ?? '🏗️';

// ── Financiero ──────────────────────────────────────────────────────────────
$fin = $pdo->query("
    SELECT
        (SELECT COALESCE(SUM(monto),0)
         FROM pagos_cliente WHERE estado = 'completado')          AS ingresos,
        (SELECT COALESCE(SUM(monto),0)
         FROM pagos_empleados WHERE estado = 'completado')        AS gastos_personal,
        (SELECT COALESCE(SUM(monto),0) FROM gastos)               AS gastos_obra,
        (SELECT COALESCE(SUM(monto),0)
         FROM pagos_pedidos WHERE estado = 'completado')          AS gastos_pedidos,
        (SELECT COUNT(*) FROM pagos_cliente WHERE estado='pendiente') AS pagos_pend_count,
        (SELECT COALESCE(SUM(monto),0)
         FROM pagos_cliente WHERE estado='pendiente')             AS pagos_pend_monto
")->fetch();

$total_gastos = $fin['gastos_personal'] + $fin['gastos_obra'] + $fin['gastos_pedidos'];
$balance      = $fin['ingresos'] - $total_gastos;

// ── Proyectos ───────────────────────────────────────────────────────────────
// Gerentes ven todos; operativos solo los suyos
if (in_array('gestionar_contratos', $permisos)) {
    $stmt_proy = $pdo->query("
        SELECT p.id_proyecto, p.nombre, p.estado,
               p.fecha_fin_estimada,
               tp.nombre  AS tipo,
               cl.nombre  AS cliente,
               COALESCE(AVG(ep.porcentaje_avance), 0) AS avance
        FROM proyectos p
        JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
        JOIN contratos c       ON c.id_contrato       = p.id_contrato
        JOIN cotizaciones co   ON co.id_cotizacion    = c.id_cotizacion
        JOIN clientes cl       ON cl.id_cliente       = co.id_cliente
        LEFT JOIN etapas_proyecto ep ON ep.id_proyecto = p.id_proyecto
        WHERE p.estado IN ('ejecucion','planificacion')
        GROUP BY p.id_proyecto, p.nombre, p.estado,
                 p.fecha_fin_estimada, tp.nombre, cl.nombre
        ORDER BY p.fecha_fin_estimada ASC
        LIMIT 8
    ");
} else {
    $stmt_proy = $pdo->prepare("
        SELECT p.id_proyecto, p.nombre, p.estado,
               p.fecha_fin_estimada,
               tp.nombre  AS tipo,
               cl.nombre  AS cliente,
               COALESCE(AVG(ep.porcentaje_avance), 0) AS avance
        FROM proyectos p
        JOIN tipos_proyecto tp ON tp.id_tipo_proyecto = p.id_tipo_proyecto
        JOIN contratos c       ON c.id_contrato       = p.id_contrato
        JOIN cotizaciones co   ON co.id_cotizacion    = c.id_cotizacion
        JOIN clientes cl       ON cl.id_cliente       = co.id_cliente
        JOIN asignaciones a    ON a.id_proyecto       = p.id_proyecto
        JOIN usuarios_sistema us ON us.id_empleado    = a.id_empleado
        LEFT JOIN etapas_proyecto ep ON ep.id_proyecto = p.id_proyecto
        WHERE us.id_usuario_sistema = ?
          AND p.estado IN ('ejecucion','planificacion')
        GROUP BY p.id_proyecto, p.nombre, p.estado,
                 p.fecha_fin_estimada, tp.nombre, cl.nombre
        ORDER BY p.fecha_fin_estimada ASC
        LIMIT 8
    ");
    $stmt_proy->execute([$_SESSION['id_usuario']]);
}
$proyectos = $stmt_proy->fetchAll();

// Conteo por estado
$estados_proy = $pdo->query("
    SELECT estado, COUNT(*) AS total FROM proyectos GROUP BY estado
")->fetchAll(PDO::FETCH_KEY_PAIR);

// ── Stock bajo mínimo ────────────────────────────────────────────────────────
$stock_bajo = $pdo->query("
    SELECT m.nombre AS material,
           a.nombre AS almacen,
           i.stock,
           i.stock_minimo,
           (i.stock_minimo - i.stock) AS deficit
    FROM inventarios i
    JOIN materiales m ON m.id_material = i.id_material
    JOIN almacenes  a ON a.id_almacen  = i.id_almacen
    WHERE i.stock <= i.stock_minimo
    ORDER BY deficit DESC
    LIMIT 6
")->fetchAll();

// ── Empleados activos en obra ────────────────────────────────────────────────
$empleados_activos = $pdo->query("
    SELECT e.nombre,
           c.nombre AS cargo,
           p.nombre AS proyecto
    FROM asignaciones a
    JOIN empleados e  ON e.id_empleado  = a.id_empleado
    JOIN cargos c     ON c.id_cargo     = a.id_cargo
    JOIN proyectos p  ON p.id_proyecto  = a.id_proyecto
    WHERE a.fecha_fin IS NULL
      AND e.estado = 'activo'
      AND p.estado  = 'ejecucion'
    ORDER BY e.nombre ASC
    LIMIT 8
")->fetchAll();

// ── Últimos logs ─────────────────────────────────────────────────────────────
$logs = $pdo->query("
    SELECT rs.accion, rs.fecha_hora, us.nombre_usuario
    FROM registros_sistema rs
    JOIN usuarios_sistema us ON us.id_usuario_sistema = rs.id_usuario_sistema
    ORDER BY rs.fecha_hora DESC
    LIMIT 6
")->fetchAll();

// ── Pagos pendientes clientes ────────────────────────────────────────────────
$pagos_pend = $pdo->query("
    SELECT cl.nombre AS cliente,
           pc.monto,
           pc.fecha_pago,
           p.nombre  AS proyecto
    FROM pagos_cliente pc
    JOIN contratos c    ON c.id_contrato    = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl    ON cl.id_cliente    = co.id_cliente
    LEFT JOIN proyectos p ON p.id_contrato  = c.id_contrato
    WHERE pc.estado = 'pendiente'
    ORDER BY pc.fecha_pago ASC
    LIMIT 5
")->fetchAll();

// ── Helpers ──────────────────────────────────────────────────────────────────
function fmt($n)  { return number_format($n, 0, '.', ','); }
function fmtb($n) { return 'Bs ' . number_format($n, 0, '.', ','); }

function dias_restantes($fecha) {
    $hoy  = new DateTime(date('Y-m-d'));
    $fin  = new DateTime($fecha);
    $diff = $hoy->diff($fin);
    return $diff->invert ? -$diff->days : $diff->days;
}
?>
<?php require_once '../layouts/header.php'; ?>

<style>
/* ── KPI cards ── */
.kpi-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px 22px 18px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    border-left: 4px solid transparent;
    position: relative;
    overflow: hidden;
    opacity: 0;
    transform: translateY(24px);
    animation: cardIn 0.55s cubic-bezier(.22,1,.36,1) forwards;
}
@keyframes cardIn {
    to { opacity: 1; transform: translateY(0); }
}
.kpi-card .watermark {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 52px;
    opacity: 0.05;
    pointer-events: none;
    user-select: none;
}
.kpi-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #95A5A6;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.kpi-value {
    font-size: 26px;
    font-weight: 800;
    color: #1C1C1E;
    line-height: 1.1;
    margin-bottom: 5px;
}
.kpi-delta {
    font-size: 12px;
    font-weight: 600;
}

/* ── Section cards ── */
.section-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    overflow: hidden;
    transition: box-shadow 0.22s, transform 0.22s;
}
.section-card:hover {
    box-shadow: 0 6px 28px rgba(0,0,0,0.10);
    transform: translateY(-2px);
}
.section-head {
    padding: 16px 20px;
    border-bottom: 1px solid #F0F2F4;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-head h5 {
    font-size: 14px;
    font-weight: 700;
    color: #1C1C1E;
    margin: 0;
}
.section-head .badge-count {
    margin-left: auto;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 99px;
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
    background: #3498DB;
    width: 0;
    transition: width 1.1s cubic-bezier(.22,1,.36,1);
}

/* ── Estado badges ── */
.badge-estado {
    font-size: 10.5px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 99px;
    white-space: nowrap;
}
.badge-ejecucion    { background:#D4EDDA; color:#155724; }
.badge-planificacion{ background:#D1ECF1; color:#0C5460; }
.badge-finalizado   { background:#D6D8D9; color:#383D41; }
.badge-pausado      { background:#FFF3CD; color:#856404; }

/* ── Row items ── */
.row-item {
    padding: 13px 20px;
    border-bottom: 1px solid #F6F7F9;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.row-item:last-child { border-bottom: none; }

/* ── Avatar inicial ── */
.ini-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
    flex-shrink: 0;
    background: linear-gradient(135deg,#EBF5FB,#D6EAF8);
    color: #2980B9;
    border: 1.5px solid #AED6F1;
}

/* ── Tabla DataTables mejorada ── */
.dataTables_wrapper .dataTables_filter input {
    border-radius: 9px;
    border: 1.5px solid #E8ECF0;
    padding: 6px 12px;
    font-size: 13px;
}
.dataTables_wrapper .dataTables_length select {
    border-radius: 9px;
    border: 1.5px solid #E8ECF0;
    padding: 4px 8px;
    font-size: 13px;
}

/* ── Animación delay helpers ── */
.delay-1 { animation-delay: 0.08s; }
.delay-2 { animation-delay: 0.16s; }
.delay-3 { animation-delay: 0.24s; }
.delay-4 { animation-delay: 0.32s; }
</style>

<!-- ══════════════════════════════════════
     ENCABEZADO
══════════════════════════════════════ -->
<div class="mb-4">
    <h1 style="font-size:22px;font-weight:800;color:#1C1C1E;letter-spacing:-0.3px;">
        Bienvenido, <?= htmlspecialchars(explode(' ', $nombre)[0]) ?>
    </h1>
    <p style="font-size:13.5px;color:#95A5A6;margin-top:4px;">
        Vista general del estado operativo ·
        <?= ucfirst(strftime('%A %d de %B de %Y') ?: date('d/m/Y')) ?>
    </p>

    <!-- Roles -->
    <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;">
        <?php foreach ($roles as $r): ?>
        <span style="font-size:11px;font-weight:700;background:#EBF5FB;color:#2980B9;
                     padding:3px 10px;border-radius:99px;">
            <?= htmlspecialchars($r) ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════════════════════════════
     KPI CARDS
══════════════════════════════════════ -->
<?php if (in_array('ver_reportes_financieros', $permisos)): ?>
<div class="row g-3 mb-4">

    <div class="col-6 col-lg-3">
        <div class="kpi-card delay-1" style="border-left-color:#27AE60;">
            <div class="watermark">↑</div>
            <div class="kpi-label">Ingresos recibidos</div>
            <div class="kpi-value" data-count="<?= $fin['ingresos'] ?>">
                Bs 0
            </div>
            <div class="kpi-delta" style="color:#27AE60;">
                Pagos completados
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="kpi-card delay-2" style="border-left-color:#E74C3C;">
            <div class="watermark">↓</div>
            <div class="kpi-label">Gastos totales</div>
            <div class="kpi-value" data-count="<?= $total_gastos ?>">
                Bs 0
            </div>
            <div class="kpi-delta" style="color:#95A5A6;">
                Personal + obra + pedidos
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="kpi-card delay-3"
             style="border-left-color:<?= $balance >= 0 ? '#3498DB' : '#E74C3C' ?>;">
            <div class="watermark">=</div>
            <div class="kpi-label">Balance neto</div>
            <div class="kpi-value"
                 data-count="<?= abs($balance) ?>"
                 style="color:<?= $balance >= 0 ? '#3498DB' : '#E74C3C' ?>">
                Bs 0
            </div>
            <div class="kpi-delta"
                 style="color:<?= $balance >= 0 ? '#27AE60' : '#E74C3C' ?>">
                <?= $balance >= 0 ? 'Superávit' : 'Déficit' ?>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="kpi-card delay-4" style="border-left-color:#E67E22;">
            <div class="watermark">!</div>
            <div class="kpi-label">Cobros pendientes</div>
            <div class="kpi-value" data-count="<?= $fin['pagos_pend_monto'] ?>">
                Bs 0
            </div>
            <div class="kpi-delta" style="color:#E67E22;">
                <?= $fin['pagos_pend_count'] ?> pago(s) por cobrar
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     PROYECTOS + STOCK
══════════════════════════════════════ -->
<?php if (in_array('ver_proyectos', $permisos)): ?>
<div class="row g-3 mb-3">

    <!-- Proyectos activos -->
    <div class="col-12 col-lg-7">
        <div class="section-card h-100">
            <div class="section-head">
                <i class="bi bi-building-fill-gear"
                   style="color:#3498DB;font-size:16px;"></i>
                <h5>Proyectos activos</h5>
                <span class="badge-count ms-auto"
                      style="background:#EBF5FB;color:#2980B9;">
                    <?= count($proyectos) ?> proyectos
                </span>
                <a href="../../modules/proyectos/index.php"
                   style="font-size:12px;color:#3498DB;font-weight:600;
                          text-decoration:none;margin-left:10px;">
                    Ver todos →
                </a>
            </div>

            <div>
                <?php foreach ($proyectos as $i => $p):
                    $avance = round($p['avance']);
                    $dias   = dias_restantes($p['fecha_fin_estimada']);
                    $clase_est = 'badge-' . $p['estado'];
                    $color_prog = $p['estado'] === 'planificacion'
                        ? '#95A5A6' : ($avance < 30 ? '#E74C3C' : '#3498DB');
                ?>
                <div class="row-item">
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;
                                    align-items:flex-start;margin-bottom:6px;gap:8px;">
                            <div>
                                <a href="../../modules/proyectos/detalle.php?id=<?= $p['id_proyecto'] ?>"
                                   style="font-size:13.5px;font-weight:700;
                                          color:#1C1C1E;text-decoration:none;">
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </a>
                                <div style="font-size:11.5px;color:#95A5A6;margin-top:2px;">
                                    <?= htmlspecialchars($p['cliente']) ?>
                                    · <span style="color:#7F8C8D;">
                                        <?= htmlspecialchars($p['tipo']) ?>
                                    </span>
                                </div>
                            </div>
                            <span class="badge-estado <?= $clase_est ?>">
                                <?= ucfirst($p['estado']) ?>
                            </span>
                        </div>

                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="prog-wrap">
                                <div class="prog-fill"
                                     data-width="<?= $avance ?>"
                                     style="background:<?= $color_prog ?>;"></div>
                            </div>
                            <span style="font-size:11.5px;font-weight:700;
                                         color:#2C3E50;min-width:30px;text-align:right;">
                                <?= $avance ?>%
                            </span>
                            <span style="font-size:11px;min-width:70px;text-align:right;
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

                <?php if (empty($proyectos)): ?>
                <div style="padding:30px;text-align:center;color:#95A5A6;font-size:13.5px;">
                    No tienes proyectos activos asignados.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stock bajo mínimo -->
    <div class="col-12 col-lg-5">
        <div class="section-card h-100">
            <div class="section-head">
                <i class="bi bi-exclamation-triangle-fill"
                   style="color:#E67E22;font-size:15px;"></i>
                <h5>Stock bajo mínimo</h5>
                <?php if (count($stock_bajo)): ?>
                <span class="badge-count ms-auto"
                      style="background:#FDECEA;color:#E74C3C;">
                    <?= count($stock_bajo) ?> alertas
                </span>
                <?php endif; ?>
            </div>

            <?php if ($stock_bajo): ?>
            <div>
                <?php foreach ($stock_bajo as $s):
                    $pct = $s['stock_minimo'] > 0
                        ? min(($s['stock'] / $s['stock_minimo']) * 100, 100)
                        : 0;
                    $col = $s['stock'] <= 0 ? '#E74C3C'
                        : ($pct < 50 ? '#E67E22' : '#F39C12');
                ?>
                <div class="row-item" style="align-items:center;">
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;justify-content:space-between;
                                    margin-bottom:5px;">
                            <span style="font-size:13px;font-weight:600;
                                         color:#1C1C1E;overflow:hidden;
                                         text-overflow:ellipsis;white-space:nowrap;
                                         max-width:180px;"
                                  title="<?= htmlspecialchars($s['material']) ?>">
                                <?= htmlspecialchars($s['material']) ?>
                            </span>
                            <span style="font-size:12.5px;font-weight:700;color:<?= $col ?>;">
                                <?= $s['stock'] ?> / <?= $s['stock_minimo'] ?>
                            </span>
                        </div>
                        <div style="font-size:11px;color:#95A5A6;margin-bottom:5px;">
                            <?= htmlspecialchars($s['almacen']) ?>
                        </div>
                        <div class="prog-wrap">
                            <div class="prog-fill"
                                 data-width="<?= round($pct) ?>"
                                 style="background:<?= $col ?>;"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="padding:12px 20px;">
                <a href="../../modules/reportes/inventario.php"
                   style="display:block;text-align:center;padding:9px;
                          border-radius:9px;border:1.5px dashed #3498DB;
                          background:#EBF5FB;color:#3498DB;font-size:13px;
                          font-weight:600;text-decoration:none;
                          transition:background 0.15s;"
                   onmouseover="this.style.background='#D6EAF8'"
                   onmouseout="this.style.background='#EBF5FB'">
                    Ver reporte completo de inventario →
                </a>
            </div>

            <?php else: ?>
            <div style="padding:30px;text-align:center;color:#27AE60;font-size:13.5px;">
                <i class="bi bi-check-circle-fill" style="font-size:24px;
                   display:block;margin-bottom:8px;"></i>
                Todo el inventario sobre niveles mínimos
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     EMPLEADOS + PAGOS PENDIENTES
══════════════════════════════════════ -->
<div class="row g-3 mb-3">

    <!-- Empleados en obra -->
    <?php if (in_array('ver_empleados', $permisos)): ?>
    <div class="col-12 col-lg-5">
        <div class="section-card h-100">
            <div class="section-head">
                <i class="bi bi-people-fill"
                   style="color:#8E44AD;font-size:15px;"></i>
                <h5>Equipo en obra</h5>
                <span class="badge-count ms-auto"
                      style="background:#F5EEF8;color:#8E44AD;">
                    <?= count($empleados_activos) ?> activos
                </span>
                <a href="../../modules/empleados/index.php"
                   style="font-size:12px;color:#3498DB;font-weight:600;
                          text-decoration:none;margin-left:10px;">
                    Ver todos →
                </a>
            </div>
            <div>
                <?php foreach ($empleados_activos as $emp): ?>
                <div class="row-item">
                    <div class="ini-avatar">
                        <?= mb_strtoupper(mb_substr($emp['nombre'], 0, 1)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:700;
                                    color:#1C1C1E;overflow:hidden;
                                    text-overflow:ellipsis;white-space:nowrap;">
                            <?= htmlspecialchars($emp['nombre']) ?>
                        </div>
                        <div style="font-size:11.5px;color:#95A5A6;margin-top:1px;">
                            <?= htmlspecialchars($emp['cargo']) ?>
                        </div>
                        <div style="font-size:11px;color:#7F8C8D;margin-top:1px;">
                            <?= htmlspecialchars($emp['proyecto']) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($empleados_activos)): ?>
                <div style="padding:30px;text-align:center;
                            color:#95A5A6;font-size:13.5px;">
                    Sin empleados asignados a proyectos activos.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cobros pendientes -->
    <?php if (in_array('ver_reportes_financieros', $permisos) && $pagos_pend): ?>
    <div class="col-12 col-lg-7">
        <div class="section-card h-100">
            <div class="section-head">
                <i class="bi bi-clock-history"
                   style="color:#E74C3C;font-size:15px;"></i>
                <h5>Cobros pendientes a clientes</h5>
                <span class="badge-count ms-auto"
                      style="background:#FDECEA;color:#E74C3C;">
                    <?= count($pagos_pend) ?> pendientes
                </span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Proyecto</th>
                            <th class="text-end">Monto (Bs)</th>
                            <th class="text-center">Vencimiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos_pend as $pp):
                            $dias = dias_restantes($pp['fecha_pago']);
                            $col  = $dias < 0 ? '#E74C3C'
                                : ($dias < 7 ? '#E67E22' : '#95A5A6');
                        ?>
                        <tr>
                            <td>
                                <span style="font-size:13px;font-weight:600;
                                             color:#1C1C1E;">
                                    <?= htmlspecialchars($pp['cliente']) ?>
                                </span>
                            </td>
                            <td style="font-size:12.5px;color:#95A5A6;">
                                <?= htmlspecialchars($pp['proyecto'] ?? '—') ?>
                            </td>
                            <td class="text-end"
                                style="font-weight:700;color:#E74C3C;font-size:13px;">
                                <?= fmtb($pp['monto']) ?>
                            </td>
                            <td class="text-center"
                                style="font-size:12px;font-weight:600;color:<?= $col ?>;">
                                <?php if ($dias < 0): ?>
                                    Vencido (<?= abs($dias) ?>d)
                                <?php elseif ($dias === 0): ?>
                                    Vence hoy
                                <?php else: ?>
                                    <?= date('d/m/Y', strtotime($pp['fecha_pago'])) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ══════════════════════════════════════
     ACTIVIDAD RECIENTE
══════════════════════════════════════ -->
<?php if (in_array('ver_auditoria', $permisos)): ?>
<div class="section-card mb-3">
    <div class="section-head">
        <i class="bi bi-activity" style="color:#3498DB;font-size:15px;"></i>
        <h5>Actividad reciente del sistema</h5>
        <a href="../../modules/logs/index.php"
           style="font-size:12px;color:#3498DB;font-weight:600;
                  text-decoration:none;margin-left:auto;">
            Ver todos los registros →
        </a>
    </div>
    <div>
        <?php foreach ($logs as $log): ?>
        <div class="row-item">
            <div class="ini-avatar"
                 style="background:linear-gradient(135deg,#EBF5FB,#D5F5E3);
                        color:#1E8449;border-color:#A9DFBF;">
                <?= mb_strtoupper(mb_substr($log['nombre_usuario'], 0, 1)) ?>
            </div>
            <div style="flex:1;min-width:0;">
                <span style="font-weight:700;color:#1C1C1E;font-size:13px;">
                    <?= htmlspecialchars($log['nombre_usuario']) ?>
                </span>
                <span style="color:#7F8C8D;font-size:13px;">
                    — <?= htmlspecialchars($log['accion']) ?>
                </span>
            </div>
            <div style="font-size:11.5px;color:#95A5A6;white-space:nowrap;
                        margin-left:10px;margin-top:1px;">
                <?= date('d/m H:i', strtotime($log['fecha_hora'])) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     RESUMEN PROYECTOS POR ESTADO
══════════════════════════════════════ -->
<?php if (in_array('ver_proyectos', $permisos)): ?>
<div class="row g-3 mb-2">
    <?php
    $estado_meta = [
        'ejecucion'    => ['label'=>'En ejecución',  'bg'=>'#D4EDDA','color'=>'#155724','icon'=>'bi-hammer'],
        'planificacion'=> ['label'=>'Planificación',  'bg'=>'#D1ECF1','color'=>'#0C5460','icon'=>'bi-clipboard2'],
        'finalizado'   => ['label'=>'Finalizados',    'bg'=>'#D6D8D9','color'=>'#383D41','icon'=>'bi-check-circle'],
        'pausado'      => ['label'=>'Pausados',       'bg'=>'#FFF3CD','color'=>'#856404','icon'=>'bi-pause-circle'],
        'cancelado'    => ['label'=>'Cancelados',     'bg'=>'#FDECEA','color'=>'#721C24','icon'=>'bi-x-circle'],
    ];
    foreach ($estado_meta as $est => $meta):
        $count = $estados_proy[$est] ?? 0;
    ?>
    <div class="col-6 col-sm-4 col-lg">
        <div class="section-card text-center" style="padding:18px 10px;">
            <i class="bi <?= $meta['icon'] ?>"
               style="font-size:22px;color:<?= $meta['color'] ?>;
                      display:block;margin-bottom:8px;"></i>
            <div style="font-size:26px;font-weight:800;color:#1C1C1E;">
                <?= $count ?>
            </div>
            <div style="font-size:11px;font-weight:700;color:#95A5A6;
                        text-transform:uppercase;letter-spacing:0.5px;margin-top:2px;">
                <?= $meta['label'] ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════
     SCRIPTS DEL DASHBOARD
══════════════════════════════════════ -->
<script>
// ── Animación contadores KPI ──────────────────────────────────────────────
document.querySelectorAll('.kpi-value[data-count]').forEach(function(el) {
    const target = parseFloat(el.dataset.count) || 0;
    if (!target) return;
    let start = 0;
    const dur  = 1200;
    const step = Math.ceil(target / (dur / 16));
    const prefix = el.closest('.kpi-card')
        .querySelector('.kpi-label')
        .textContent.toLowerCase().includes('cobro') ||
        el.closest('.kpi-card')
        .querySelector('.kpi-label')
        .textContent.toLowerCase().includes('ingreso') ||
        el.closest('.kpi-card')
        .querySelector('.kpi-label')
        .textContent.toLowerCase().includes('gasto') ||
        el.closest('.kpi-card')
        .querySelector('.kpi-label')
        .textContent.toLowerCase().includes('balance')
        ? 'Bs ' : '';

    const timer = setInterval(function() {
        start = Math.min(start + step, target);
        el.textContent = prefix + start.toLocaleString('es-BO');
        if (start >= target) clearInterval(timer);
    }, 16);
});

// ── Barras de progreso animadas ───────────────────────────────────────────
window.addEventListener('load', function() {
    document.querySelectorAll('.prog-fill[data-width]').forEach(function(bar) {
        setTimeout(function() {
            bar.style.width = bar.dataset.width + '%';
        }, 200);
    });
});
</script>

<?php require_once '../layouts/footer.php'; ?>