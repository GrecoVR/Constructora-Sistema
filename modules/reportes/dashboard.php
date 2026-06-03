<?php
require_once '../../config/database.php';

$pdo = conectar();

// Total proyectos por estado
$proyectos_estado = $pdo->query("
    SELECT estado, COUNT(*) as total
    FROM proyectos
    GROUP BY estado
")->fetchAll();

// Total ingresos vs gastos
$financiero = $pdo->query("
    SELECT
        (SELECT COALESCE(SUM(monto),0) FROM pagos_cliente WHERE estado = 'completado') as ingresos,
        (SELECT COALESCE(SUM(monto),0) FROM pagos_empleados WHERE estado = 'completado') as gastos_personal,
        (SELECT COALESCE(SUM(monto),0) FROM gastos) as gastos_obra,
        (SELECT COALESCE(SUM(monto),0) FROM pagos_pedidos WHERE estado = 'completado') as gastos_pedidos
")->fetch();

$total_gastos  = $financiero['gastos_personal'] + $financiero['gastos_obra'] + $financiero['gastos_pedidos'];
$balance       = $financiero['ingresos'] - $total_gastos;

// Top 5 proyectos con más gastos
$top_proyectos = $pdo->query("
    SELECT p.nombre, COALESCE(SUM(g.monto), 0) as total_gastos
    FROM proyectos p
    LEFT JOIN gastos g ON g.id_proyecto = p.id_proyecto
    GROUP BY p.id_proyecto, p.nombre
    ORDER BY total_gastos DESC
    LIMIT 5
")->fetchAll();

// Materiales más usados
$top_materiales = $pdo->query("
    SELECT m.nombre, SUM(um.cantidad) as total_usado
    FROM uso_materiales um
    JOIN materiales m ON m.id_material = um.id_material
    GROUP BY um.id_material, m.nombre
    ORDER BY total_usado DESC
    LIMIT 5
")->fetchAll();

// Pagos pendientes
$pagos_pendientes = $pdo->query("
    SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total
    FROM pagos_empleados
    WHERE estado = 'pendiente'
")->fetch();

// Cotizaciones por estado
$cotizaciones_estado = $pdo->query("
    SELECT estado, COUNT(*) as total, COALESCE(SUM(monto_total), 0) as monto
    FROM cotizaciones
    GROUP BY estado
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Dashboard — Vértice</title>
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
        body {
            background: var(--bg);
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            min-height: 100vh;
        }
        /* topbar */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .topbar-brand {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--text);
            text-decoration: none;
        }
        .topbar-brand span { color: var(--blue); }
        .topbar-nav { display: flex; gap: 8px; margin-left: auto; flex-wrap: wrap; }
        .btn-nav {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: .82rem;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-nav:hover, .btn-nav.active { background: var(--blue-dim); border-color: rgba(31,111,235,.4); color: var(--text); }

        .content { padding: 36px 28px; max-width: 1280px; margin: 0 auto; }

        /* page title */
        .page-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.75rem;
            margin-bottom: 4px;
        }
        .page-sub { color: var(--muted); font-size: .9rem; margin-bottom: 32px; }

        /* metric cards */
        .metric-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px 22px;
            height: 100%;
        }
        .metric-label { font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-bottom: 8px; }
        .metric-value { font-family: 'Syne', sans-serif; font-size: 1.65rem; font-weight: 700; line-height: 1; margin-bottom: 4px; }
        .metric-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; float: right; margin-top: -4px;
        }

        /* section card */
        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }
        .section-head {
            padding: 18px 22px 14px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section-head h5 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
        }
        .section-icon {
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem;
        }

        /* table */
        .table-dark-custom { margin: 0; }
        .table-dark-custom thead th {
            background: var(--surface2);
            border-color: var(--border);
            color: var(--muted);
            font-size: .77rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            padding: 12px 22px;
        }
        .table-dark-custom tbody td {
            border-color: var(--border);
            padding: 12px 22px;
            font-size: .88rem;
            color: var(--text);
            vertical-align: middle;
        }
        .table-dark-custom tbody tr:hover td { background: var(--surface2); }

        /* badge estado */
        .estado-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .75rem;
            font-weight: 600;
        }

        /* bar simple */
        .bar-row { padding: 10px 22px; border-bottom: 1px solid var(--border); }
        .bar-row:last-child { border-bottom: none; }
        .bar-label { font-size: .85rem; margin-bottom: 5px; display: flex; justify-content: space-between; }
        .bar-label span:last-child { color: var(--muted); font-size: .8rem; }
        .bar-track { background: var(--surface2); border-radius: 4px; height: 6px; }
        .bar-fill  { height: 6px; border-radius: 4px; }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <a href="../../dashboard.php" class="topbar-brand">Constructora<span></span></a>
    <div class="topbar-nav">
        <a href="../../dashboard.php" class="btn-nav"><i class="bi bi-arrow-left me-1"></i>Panel principal</a>
        <a href="index.php" class="btn-nav"><i class="bi bi-grid me-1"></i>Reportes</a>
        <a href="dashboard.php" class="btn-nav active"><i class="bi bi-speedometer2 me-1"></i>General</a>
        <a href="financiero.php" class="btn-nav"><i class="bi bi-cash-coin me-1"></i>Financiero</a>
        <a href="inventario.php" class="btn-nav"><i class="bi bi-boxes me-1"></i>Inventario</a>
    </div>
</nav>

<div class="content">
    <div class="page-title"><i class="bi bi-speedometer2 me-2" style="color:var(--blue)"></i>Reporte General</div>
    <p class="page-sub">Resumen ejecutivo del estado operativo y financiero de la empresa.</p>

    <!-- MÉTRICAS FINANCIERAS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--green-dim);color:var(--green)">
                    <i class="bi bi-arrow-down-circle-fill"></i>
                </div>
                <div class="metric-label">Ingresos recibidos</div>
                <div class="metric-value" style="color:var(--green)">Bs <?= number_format($financiero['ingresos'], 0, '.', ',') ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--red-dim);color:var(--red)">
                    <i class="bi bi-arrow-up-circle-fill"></i>
                </div>
                <div class="metric-label">Total gastos</div>
                <div class="metric-value" style="color:var(--red)">Bs <?= number_format($total_gastos, 0, '.', ',') ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:<?= $balance >= 0 ? 'var(--green-dim)' : 'var(--red-dim)' ?>;color:<?= $balance >= 0 ? 'var(--green)' : 'var(--red)' ?>">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div class="metric-label">Balance neto</div>
                <div class="metric-value" style="color:<?= $balance >= 0 ? 'var(--green)' : 'var(--red)' ?>">
                    Bs <?= number_format($balance, 0, '.', ',') ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--yellow-dim);color:var(--yellow)">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="metric-label">Pagos pendientes</div>
                <div class="metric-value" style="color:var(--yellow)">Bs <?= number_format($pagos_pendientes['monto_total'], 0, '.', ',') ?></div>
                <div style="font-size:.78rem;color:var(--muted)"><?= $pagos_pendientes['total'] ?> pagos</div>
            </div>
        </div>
    </div>

    <!-- DESGLOSE GASTOS + PROYECTOS ESTADO -->
    <div class="row g-3 mb-4">
        <!-- Desglose gastos -->
        <div class="col-12 col-lg-5">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--red-dim);color:var(--red)"><i class="bi bi-pie-chart"></i></div>
                    <h5>Desglose de gastos</h5>
                </div>
                <table class="table table-dark-custom">
                    <thead><tr><th>Concepto</th><th class="text-end">Monto (Bs)</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><i class="bi bi-people me-2" style="color:var(--blue)"></i>Personal</td>
                            <td class="text-end"><?= number_format($financiero['gastos_personal'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-building me-2" style="color:var(--yellow)"></i>Obra</td>
                            <td class="text-end"><?= number_format($financiero['gastos_obra'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-cart me-2" style="color:var(--green)"></i>Pedidos</td>
                            <td class="text-end"><?= number_format($financiero['gastos_pedidos'], 2) ?></td>
                        </tr>
                        <tr style="background:var(--surface2)">
                            <td><strong>Total</strong></td>
                            <td class="text-end"><strong style="color:var(--red)"><?= number_format($total_gastos, 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Proyectos por estado -->
        <div class="col-12 col-lg-7">
            <div class="section-card h-100">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--blue-dim);color:var(--blue)"><i class="bi bi-folder2-open"></i></div>
                    <h5>Proyectos por estado</h5>
                </div>
                <table class="table table-dark-custom">
                    <thead><tr><th>Estado</th><th class="text-end">Cantidad</th></tr></thead>
                    <tbody>
                        <?php
                        $estado_colors = ['activo'=>'var(--green)','finalizado'=>'var(--blue)','pausado'=>'var(--yellow)','cancelado'=>'var(--red)'];
                        $estado_bg     = ['activo'=>'var(--green-dim)','finalizado'=>'var(--blue-dim)','pausado'=>'var(--yellow-dim)','cancelado'=>'var(--red-dim)'];
                        foreach ($proyectos_estado as $pe):
                            $e = strtolower($pe['estado']);
                            $c = $estado_colors[$e] ?? 'var(--muted)';
                            $bg= $estado_bg[$e]     ?? 'rgba(255,255,255,.07)';
                        ?>
                        <tr>
                            <td>
                                <span class="estado-badge" style="background:<?= $bg ?>;color:<?= $c ?>">
                                    <?= ucfirst($pe['estado']) ?>
                                </span>
                            </td>
                            <td class="text-end fw-semibold"><?= $pe['total'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TOP PROYECTOS + TOP MATERIALES -->
    <div class="row g-3 mb-4">
        <!-- Top proyectos por gasto -->
        <div class="col-12 col-lg-6">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--yellow-dim);color:var(--yellow)"><i class="bi bi-trophy"></i></div>
                    <h5>Top 5 proyectos con más gastos</h5>
                </div>
                <?php
                $max_gasto = max(array_column($top_proyectos, 'total_gastos')) ?: 1;
                foreach ($top_proyectos as $i => $tp):
                    $pct = round(($tp['total_gastos'] / $max_gasto) * 100);
                ?>
                <div class="bar-row">
                    <div class="bar-label">
                        <span><?= htmlspecialchars($tp['nombre']) ?></span>
                        <span>Bs <?= number_format($tp['total_gastos'], 2) ?></span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= $pct ?>%;background:var(--yellow)"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top materiales -->
        <div class="col-12 col-lg-6">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--green-dim);color:var(--green)"><i class="bi bi-boxes"></i></div>
                    <h5>Top 5 materiales más usados</h5>
                </div>
                <?php
                $max_mat = max(array_column($top_materiales, 'total_usado')) ?: 1;
                foreach ($top_materiales as $tm):
                    $pct = round(($tm['total_usado'] / $max_mat) * 100);
                ?>
                <div class="bar-row">
                    <div class="bar-label">
                        <span><?= htmlspecialchars($tm['nombre']) ?></span>
                        <span><?= number_format($tm['total_usado'], 2) ?> unid.</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= $pct ?>%;background:var(--green)"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- COTIZACIONES -->
    <div class="row g-3">
        <div class="col-12">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--blue-dim);color:var(--blue)"><i class="bi bi-file-earmark-text"></i></div>
                    <h5>Cotizaciones por estado</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-dark-custom">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Monto total (Bs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cotizaciones_estado as $ce):
                                $e  = strtolower($ce['estado']);
                                $c  = $estado_colors[$e] ?? 'var(--muted)';
                                $bg = $estado_bg[$e]     ?? 'rgba(255,255,255,.07)';
                            ?>
                            <tr>
                                <td>
                                    <span class="estado-badge" style="background:<?= $bg ?>;color:<?= $c ?>">
                                        <?= ucfirst($ce['estado']) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= $ce['total'] ?></td>
                                <td class="text-end">Bs <?= number_format($ce['monto'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div><!-- /content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
