<?php
require_once '../../config/database.php';

$pdo = conectar();

// Filtro por proyecto
$id_proyecto = intval($_GET['id_proyecto'] ?? 0);

// Lista proyectos para el filtro
$proyectos = $pdo->query("
    SELECT id_proyecto, nombre FROM proyectos ORDER BY nombre ASC
")->fetchAll();

// Ingresos por proyecto
if ($id_proyecto) {
    $stmt_ingresos = $pdo->prepare("
        SELECT p.nombre as proyecto, 
               COALESCE(SUM(pc.monto), 0) as total_ingresos,
               COUNT(pc.id_pago_cliente) as total_pagos
        FROM proyectos p
        LEFT JOIN contratos c ON c.id_contrato = p.id_contrato
        LEFT JOIN pagos_cliente pc ON pc.id_contrato = c.id_contrato
            AND pc.estado = 'completado'
        WHERE p.id_proyecto = ?
        GROUP BY p.id_proyecto, p.nombre
    ");
    $stmt_ingresos->execute([$id_proyecto]);

    $stmt_gastos = $pdo->prepare("
        SELECT COALESCE(SUM(monto), 0) as total_gastos
        FROM gastos WHERE id_proyecto = ?
    ");
    $stmt_gastos->execute([$id_proyecto]);

} else {
    $stmt_ingresos = $pdo->query("
        SELECT p.nombre as proyecto,
               COALESCE(SUM(pc.monto), 0) as total_ingresos,
               COUNT(pc.id_pago_cliente) as total_pagos
        FROM proyectos p
        LEFT JOIN contratos c ON c.id_contrato = p.id_contrato
        LEFT JOIN pagos_cliente pc ON pc.id_contrato = c.id_contrato
            AND pc.estado = 'completado'
        GROUP BY p.id_proyecto, p.nombre
        ORDER BY total_ingresos DESC
    ");

    $stmt_gastos = $pdo->query("
        SELECT COALESCE(SUM(monto), 0) as total_gastos FROM gastos
    ");
}

$ingresos = $stmt_ingresos->fetchAll();
$gastos   = $stmt_gastos->fetch();

// Pagos empleados por mes
$pagos_mes = $pdo->query("
    SELECT DATE_FORMAT(fecha_pago, '%Y-%m') as mes,
           SUM(monto) as total
    FROM pagos_empleados
    WHERE estado = 'completado'
    GROUP BY mes
    ORDER BY mes DESC
    LIMIT 6
")->fetchAll();

// Pagos pendientes clientes
$pendientes_clientes = $pdo->query("
    SELECT c.id_contrato, cl.nombre as cliente,
           pc.monto, pc.fecha_pago
    FROM pagos_cliente pc
    JOIN contratos c ON c.id_contrato = pc.id_contrato
    JOIN cotizaciones co ON co.id_cotizacion = c.id_cotizacion
    JOIN clientes cl ON cl.id_cliente = co.id_cliente
    WHERE pc.estado = 'pendiente'
    ORDER BY pc.fecha_pago ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero — Vértice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #0d1117;
            --surface:   #161b22;
            --surface2:  #1c2333;
            --border:    rgba(255,255,255,0.08);
            --green:     #2ea043;
            --green-dim: rgba(46,160,67,.15);
            --blue:      #1f6feb;
            --blue-dim:  rgba(31,111,235,.15);
            --red:       #da3633;
            --red-dim:   rgba(218,54,51,.15);
            --yellow:    #e3b341;
            --yellow-dim:rgba(227,179,65,.15);
            --text:      #e6edf3;
            --muted:     #7d8590;
        }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); min-height: 100vh; }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .topbar-brand { font-family:'Syne',sans-serif; font-weight:800; font-size:1.15rem; color:var(--text); text-decoration:none; }
        .topbar-brand span { color:var(--blue); }
        .topbar-nav { display:flex; gap:8px; margin-left:auto; flex-wrap:wrap; }
        .btn-nav {
            background:var(--surface2); border:1px solid var(--border); color:var(--muted);
            font-size:.82rem; padding:6px 14px; border-radius:8px; text-decoration:none; transition:all .2s;
        }
        .btn-nav:hover, .btn-nav.active { background:var(--green-dim); border-color:rgba(46,160,67,.4); color:var(--text); }

        .content { padding:36px 28px; max-width:1280px; margin:0 auto; }
        .page-title { font-family:'Syne',sans-serif; font-weight:800; font-size:1.75rem; margin-bottom:4px; }
        .page-sub { color:var(--muted); font-size:.9rem; margin-bottom:28px; }

        /* filtro */
        .filter-bar {
            background:var(--surface); border:1px solid var(--border); border-radius:12px;
            padding:16px 20px; margin-bottom:28px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;
        }
        .filter-bar label { color:var(--muted); font-size:.85rem; margin:0; }
        .form-select-dark {
            background:var(--surface2); border:1px solid var(--border); color:var(--text);
            border-radius:8px; padding:6px 12px; font-size:.88rem; min-width:220px;
        }
        .form-select-dark:focus { border-color:rgba(46,160,67,.5); outline:none; box-shadow:none; }
        .btn-filter {
            background:var(--green); border:none; color:#fff; padding:7px 18px;
            border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; transition:opacity .2s;
        }
        .btn-filter:hover { opacity:.85; }
        .btn-clear {
            background:transparent; border:1px solid var(--border); color:var(--muted);
            padding:6px 14px; border-radius:8px; font-size:.82rem; text-decoration:none; transition:all .2s;
        }
        .btn-clear:hover { border-color:rgba(218,54,51,.4); color:var(--red); }

        /* metric */
        .metric-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:24px 22px; height:100%; }
        .metric-label { font-size:.78rem; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:8px; }
        .metric-value { font-family:'Syne',sans-serif; font-size:1.5rem; font-weight:700; line-height:1; }
        .metric-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; float:right; margin-top:-4px; }

        /* section */
        .section-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
        .section-head { padding:18px 22px 14px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
        .section-head h5 { font-family:'Syne',sans-serif; font-weight:700; font-size:1rem; margin:0; }
        .section-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.95rem; }

        /* table */
        .table-vc { margin:0; }
        .table-vc thead th {
            background:var(--surface2); border-color:var(--border); color:var(--muted);
            font-size:.77rem; font-weight:600; text-transform:uppercase; letter-spacing:.07em; padding:12px 20px;
        }
        .table-vc tbody td { border-color:var(--border); padding:11px 20px; font-size:.88rem; color:var(--text); vertical-align:middle; }
        .table-vc tbody tr:hover td { background:var(--surface2); }

        /* bar */
        .bar-row { padding:10px 22px; border-bottom:1px solid var(--border); }
        .bar-row:last-child { border-bottom:none; }
        .bar-label { font-size:.84rem; margin-bottom:5px; display:flex; justify-content:space-between; }
        .bar-label span:last-child { color:var(--muted); font-size:.8rem; }
        .bar-track { background:var(--surface2); border-radius:4px; height:6px; }
        .bar-fill  { height:6px; border-radius:4px; }

        /* vencimiento */
        .due-badge {
            display:inline-block; padding:2px 9px; border-radius:20px; font-size:.72rem; font-weight:600;
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <a href="../../dashboard.php" class="topbar-brand">Constructora<span></span></a>
    <div class="topbar-nav">
        <a href="../../dashboard.php" class="btn-nav"><i class="bi bi-arrow-left me-1"></i>Panel principal</a>
        <a href="index.php" class="btn-nav"><i class="bi bi-grid me-1"></i>Reportes</a>
        <a href="dashboard.php" class="btn-nav"><i class="bi bi-speedometer2 me-1"></i>General</a>
        <a href="financiero.php" class="btn-nav active"><i class="bi bi-cash-coin me-1"></i>Financiero</a>
        <a href="inventario.php" class="btn-nav"><i class="bi bi-boxes me-1"></i>Inventario</a>
    </div>
</nav>

<div class="content">
    <div class="page-title"><i class="bi bi-cash-coin me-2" style="color:var(--green)"></i>Reporte Financiero</div>
    <p class="page-sub">Análisis de ingresos, gastos de obra y seguimiento de pagos.</p>

    <!-- FILTRO -->
    <div class="filter-bar">
        <label><i class="bi bi-funnel me-1"></i>Filtrar por proyecto:</label>
        <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
            <select name="id_proyecto" class="form-select-dark">
                <option value="">— Todos los proyectos —</option>
                <?php foreach ($proyectos as $p): ?>
                    <option value="<?= $p['id_proyecto'] ?>"
                        <?= $p['id_proyecto'] == $id_proyecto ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter"><i class="bi bi-search me-1"></i>Filtrar</button>
            <?php if ($id_proyecto): ?>
                <a href="financiero.php" class="btn-clear"><i class="bi bi-x me-1"></i>Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- KPIs RÁPIDOS -->
    <?php
        $total_ingresos_sum = array_sum(array_column($ingresos, 'total_ingresos'));
        $total_pagos_sum    = array_sum(array_column($ingresos, 'total_pagos'));
        $total_pendiente    = array_sum(array_column($pendientes_clientes, 'monto'));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--green-dim);color:var(--green)"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="metric-label">Ingresos totales</div>
                <div class="metric-value" style="color:var(--green)">Bs <?= number_format($total_ingresos_sum, 0, '.', ',') ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--red-dim);color:var(--red)"><i class="bi bi-building"></i></div>
                <div class="metric-label">Gastos de obra</div>
                <div class="metric-value" style="color:var(--red)">Bs <?= number_format($gastos['total_gastos'], 0, '.', ',') ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--blue-dim);color:var(--blue)"><i class="bi bi-receipt"></i></div>
                <div class="metric-label">Pagos completados</div>
                <div class="metric-value" style="color:var(--blue)"><?= $total_pagos_sum ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--yellow-dim);color:var(--yellow)"><i class="bi bi-hourglass-split"></i></div>
                <div class="metric-label">Pendiente clientes</div>
                <div class="metric-value" style="color:var(--yellow)">Bs <?= number_format($total_pendiente, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>

    <!-- INGRESOS + PAGOS EMPLEADOS MES -->
    <div class="row g-3 mb-4">
        <!-- Ingresos por proyecto -->
        <div class="col-12 col-lg-7">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--green-dim);color:var(--green)"><i class="bi bi-check-circle"></i></div>
                    <h5>Ingresos por proyecto</h5>
                </div>
                <?php if ($ingresos):
                    $max_ing = max(array_column($ingresos, 'total_ingresos')) ?: 1;
                    foreach ($ingresos as $ing):
                        $pct = round(($ing['total_ingresos'] / $max_ing) * 100);
                ?>
                <div class="bar-row">
                    <div class="bar-label">
                        <span><?= htmlspecialchars($ing['proyecto']) ?></span>
                        <span>Bs <?= number_format($ing['total_ingresos'], 2) ?> &nbsp;·&nbsp; <?= $ing['total_pagos'] ?> pagos</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= $pct ?>%;background:var(--green)"></div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <div class="p-4 text-center" style="color:var(--muted)">No hay ingresos registrados.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagos empleados por mes -->
        <div class="col-12 col-lg-5">
            <div class="section-card h-100">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--blue-dim);color:var(--blue)"><i class="bi bi-people"></i></div>
                    <h5>Pagos a empleados por mes</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-vc">
                        <thead><tr><th>Mes</th><th class="text-end">Total (Bs)</th></tr></thead>
                        <tbody>
                            <?php foreach ($pagos_mes as $pm): ?>
                            <tr>
                                <td><i class="bi bi-calendar3 me-2" style="color:var(--blue)"></i><?= $pm['mes'] ?></td>
                                <td class="text-end fw-semibold"><?= number_format($pm['total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- PAGOS PENDIENTES CLIENTES -->
    <div class="row g-3">
        <div class="col-12">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--yellow-dim);color:var(--yellow)"><i class="bi bi-exclamation-triangle"></i></div>
                    <h5>Pagos pendientes de clientes</h5>
                    <?php if ($pendientes_clientes): ?>
                        <span class="ms-auto badge" style="background:var(--yellow-dim);color:var(--yellow);font-size:.78rem">
                            <?= count($pendientes_clientes) ?> pendiente<?= count($pendientes_clientes) > 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($pendientes_clientes): ?>
                <div class="table-responsive">
                    <table class="table table-vc">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th class="text-end">Monto (Bs)</th>
                                <th class="text-center">Fecha esperada</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendientes_clientes as $pc):
                                $hoy     = new DateTime();
                                $fecha   = new DateTime($pc['fecha_pago']);
                                $vencido = $fecha < $hoy;
                                $dias    = (int)$hoy->diff($fecha)->days;
                            ?>
                            <tr>
                                <td>
                                    <i class="bi bi-person me-2" style="color:var(--muted)"></i>
                                    <?= htmlspecialchars($pc['cliente']) ?>
                                </td>
                                <td class="text-end fw-semibold" style="color:var(--red)">
                                    Bs <?= number_format($pc['monto'], 2) ?>
                                </td>
                                <td class="text-center"><?= $pc['fecha_pago'] ?></td>
                                <td class="text-center">
                                    <?php if ($vencido): ?>
                                        <span class="due-badge" style="background:var(--red-dim);color:var(--red)">
                                            <i class="bi bi-clock me-1"></i>Vencido
                                        </span>
                                    <?php else: ?>
                                        <span class="due-badge" style="background:var(--yellow-dim);color:var(--yellow)">
                                            <?= $dias ?> día<?= $dias != 1 ? 's' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-4 text-center">
                    <i class="bi bi-check-circle-fill me-2" style="color:var(--green)"></i>
                    <span style="color:var(--muted)">No hay pagos pendientes de clientes.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
