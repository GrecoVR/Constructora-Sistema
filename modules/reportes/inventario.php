<?php
require_once '../../config/database.php';

$pdo = conectar();

// Stock actual por almacén
$stock_almacen = $pdo->query("
    SELECT a.nombre as almacen,
           COUNT(i.id_material) as total_materiales,
           SUM(i.stock) as stock_total
    FROM inventarios i
    JOIN almacenes a ON a.id_almacen = i.id_almacen
    GROUP BY i.id_almacen, a.nombre
    ORDER BY a.nombre ASC
")->fetchAll();

// Materiales bajo stock mínimo
$bajo_minimo = $pdo->query("
    SELECT m.nombre as material, a.nombre as almacen,
           i.stock, i.stock_minimo,
           (i.stock_minimo - i.stock) as diferencia
    FROM inventarios i
    JOIN materiales m ON m.id_material = i.id_material
    JOIN almacenes a ON a.id_almacen = i.id_almacen
    WHERE i.stock <= i.stock_minimo
    ORDER BY diferencia DESC
")->fetchAll();

// Materiales agotados
$agotados = $pdo->query("
    SELECT m.nombre as material, a.nombre as almacen
    FROM inventarios i
    JOIN materiales m ON m.id_material = i.id_material
    JOIN almacenes a ON a.id_almacen = i.id_almacen
    WHERE i.stock <= 0
")->fetchAll();

// Últimos movimientos
$ultimos_movimientos = $pdo->query("
    SELECT mi.fecha, mi.tipo_movimiento, mi.cantidad,
           m.nombre as material, a.nombre as almacen
    FROM movimientos_inventario mi
    JOIN materiales m ON m.id_material = mi.id_material
    JOIN almacenes a ON a.id_almacen = mi.id_almacen
    ORDER BY mi.fecha DESC, mi.id_movimiento DESC
    LIMIT 15
")->fetchAll();

// Materiales más usados en proyectos
$mas_usados = $pdo->query("
    SELECT m.nombre, SUM(um.cantidad) as total,
           COUNT(DISTINCT um.id_proyecto) as en_proyectos
    FROM uso_materiales um
    JOIN materiales m ON m.id_material = um.id_material
    GROUP BY um.id_material, m.nombre
    ORDER BY total DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Inventario — Vértice</title>
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
            --purple:    #8957e5;
            --purple-dim:rgba(137,87,229,.15);
            --text:      #e6edf3;
            --muted:     #7d8590;
        }
        body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); min-height: 100vh; }

        .topbar {
            background: var(--surface); border-bottom: 1px solid var(--border);
            padding: 14px 28px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        }
        .topbar-brand { font-family:'Syne',sans-serif; font-weight:800; font-size:1.15rem; color:var(--text); text-decoration:none; }
        .topbar-brand span { color:var(--blue); }
        .topbar-nav { display:flex; gap:8px; margin-left:auto; flex-wrap:wrap; }
        .btn-nav {
            background:var(--surface2); border:1px solid var(--border); color:var(--muted);
            font-size:.82rem; padding:6px 14px; border-radius:8px; text-decoration:none; transition:all .2s;
        }
        .btn-nav:hover, .btn-nav.active { background:var(--yellow-dim); border-color:rgba(227,179,65,.4); color:var(--text); }

        .content { padding:36px 28px; max-width:1280px; margin:0 auto; }
        .page-title { font-family:'Syne',sans-serif; font-weight:800; font-size:1.75rem; margin-bottom:4px; }
        .page-sub { color:var(--muted); font-size:.9rem; margin-bottom:28px; }

        /* metric */
        .metric-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:22px 20px; height:100%; }
        .metric-label { font-size:.78rem; font-weight:600; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:8px; }
        .metric-value { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:700; line-height:1; }
        .metric-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; float:right; margin-top:-2px; }
        .metric-sub { font-size:.78rem; color:var(--muted); margin-top:4px; }

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
        .table-vc tbody td { border-color:var(--border); padding:11px 20px; font-size:.87rem; color:var(--text); vertical-align:middle; }
        .table-vc tbody tr:hover td { background:var(--surface2); }

        /* stock bar inline */
        .stock-bar-wrap { display:flex; align-items:center; gap:10px; }
        .stock-mini-track { flex:1; background:var(--surface2); border-radius:3px; height:5px; }
        .stock-mini-fill  { height:5px; border-radius:3px; }

        /* movimiento tipo */
        .mov-badge {
            display:inline-block; padding:2px 9px; border-radius:20px;
            font-size:.72rem; font-weight:600; text-transform:capitalize;
        }

        /* bar horizontal */
        .bar-row { padding:10px 22px; border-bottom:1px solid var(--border); }
        .bar-row:last-child { border-bottom:none; }
        .bar-label { font-size:.84rem; margin-bottom:5px; display:flex; justify-content:space-between; }
        .bar-label span:last-child { color:var(--muted); font-size:.8rem; }
        .bar-track { background:var(--surface2); border-radius:4px; height:6px; }
        .bar-fill  { height:6px; border-radius:4px; }

        .alert-empty { padding:20px; text-align:center; color:var(--muted); font-size:.88rem; }
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
        <a href="financiero.php" class="btn-nav"><i class="bi bi-cash-coin me-1"></i>Financiero</a>
        <a href="inventario.php" class="btn-nav active"><i class="bi bi-boxes me-1"></i>Inventario</a>
    </div>
</nav>

<div class="content">
    <div class="page-title"><i class="bi bi-boxes me-2" style="color:var(--yellow)"></i>Reporte de Inventario</div>
    <p class="page-sub">Estado actual de stock, alertas y movimientos de materiales en almacenes.</p>

    <!-- KPIs -->
    <?php
        $total_almacenes  = count($stock_almacen);
        $total_stock      = array_sum(array_column($stock_almacen, 'stock_total'));
        $total_bajo_min   = count($bajo_minimo);
        $total_agotados   = count($agotados);
    ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--blue-dim);color:var(--blue)"><i class="bi bi-building"></i></div>
                <div class="metric-label">Almacenes activos</div>
                <div class="metric-value" style="color:var(--blue)"><?= $total_almacenes ?></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--green-dim);color:var(--green)"><i class="bi bi-stack"></i></div>
                <div class="metric-label">Stock total</div>
                <div class="metric-value" style="color:var(--green)"><?= number_format($total_stock, 0, '.', ',') ?></div>
                <div class="metric-sub">unidades globales</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--yellow-dim);color:var(--yellow)"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="metric-label">Bajo mínimo</div>
                <div class="metric-value" style="color:var(--yellow)"><?= $total_bajo_min ?></div>
                <div class="metric-sub">materiales</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="metric-icon" style="background:var(--red-dim);color:var(--red)"><i class="bi bi-x-octagon"></i></div>
                <div class="metric-label">Agotados</div>
                <div class="metric-value" style="color:var(--red)"><?= $total_agotados ?></div>
                <div class="metric-sub">sin stock</div>
            </div>
        </div>
    </div>

    <!-- ALERTAS -->
    <?php if ($agotados || $bajo_minimo): ?>
    <div class="row g-3 mb-4">
        <?php if ($agotados): ?>
        <div class="col-12 col-lg-5">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--red-dim);color:var(--red)"><i class="bi bi-x-octagon-fill"></i></div>
                    <h5>Materiales agotados</h5>
                    <span class="ms-auto badge" style="background:var(--red-dim);color:var(--red);font-size:.75rem"><?= count($agotados) ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-vc">
                        <thead><tr><th>Material</th><th>Almacén</th></tr></thead>
                        <tbody>
                            <?php foreach ($agotados as $ag): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-dash-circle-fill me-2" style="color:var(--red)"></i>
                                    <?= htmlspecialchars($ag['material']) ?>
                                </td>
                                <td style="color:var(--muted)"><?= htmlspecialchars($ag['almacen']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($bajo_minimo): ?>
        <div class="col-12 <?= $agotados ? 'col-lg-7' : '' ?>">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--yellow-dim);color:var(--yellow)"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h5>Materiales bajo stock mínimo</h5>
                    <span class="ms-auto badge" style="background:var(--yellow-dim);color:var(--yellow);font-size:.75rem"><?= count($bajo_minimo) ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-vc">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Almacén</th>
                                <th class="text-center">Stock actual</th>
                                <th class="text-center">Mínimo</th>
                                <th class="text-center">Déficit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bajo_minimo as $bm): ?>
                            <tr>
                                <td><?= htmlspecialchars($bm['material']) ?></td>
                                <td style="color:var(--muted)"><?= htmlspecialchars($bm['almacen']) ?></td>
                                <td class="text-center" style="color:var(--yellow)"><?= $bm['stock'] ?></td>
                                <td class="text-center"><?= $bm['stock_minimo'] ?></td>
                                <td class="text-center">
                                    <span style="color:var(--red);font-weight:600">-<?= $bm['diferencia'] ?></span>
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
    <?php else: ?>
    <div class="section-card mb-4 p-3">
        <span style="color:var(--green)"><i class="bi bi-check-circle-fill me-2"></i>Todos los materiales están sobre el stock mínimo. No hay alertas activas.</span>
    </div>
    <?php endif; ?>

    <!-- STOCK POR ALMACÉN -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-5">
            <div class="section-card h-100">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--blue-dim);color:var(--blue)"><i class="bi bi-building"></i></div>
                    <h5>Stock por almacén</h5>
                </div>
                <?php
                $max_stock = max(array_column($stock_almacen, 'stock_total')) ?: 1;
                foreach ($stock_almacen as $sa):
                    $pct = round(($sa['stock_total'] / $max_stock) * 100);
                ?>
                <div class="bar-row">
                    <div class="bar-label">
                        <span><?= htmlspecialchars($sa['almacen']) ?></span>
                        <span><?= number_format($sa['stock_total'], 0) ?> u. &nbsp;·&nbsp; <?= $sa['total_materiales'] ?> mat.</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= $pct ?>%;background:var(--blue)"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top 10 materiales más usados -->
        <div class="col-12 col-lg-7">
            <div class="section-card h-100">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--purple-dim);color:var(--purple)"><i class="bi bi-graph-up"></i></div>
                    <h5>Top 10 materiales más usados en proyectos</h5>
                </div>
                <?php
                $max_uso = max(array_column($mas_usados, 'total')) ?: 1;
                foreach ($mas_usados as $mu):
                    $pct = round(($mu['total'] / $max_uso) * 100);
                ?>
                <div class="bar-row">
                    <div class="bar-label">
                        <span><?= htmlspecialchars($mu['nombre']) ?></span>
                        <span><?= number_format($mu['total'], 2) ?> u. &nbsp;·&nbsp; <?= $mu['en_proyectos'] ?> proy.</span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?= $pct ?>%;background:var(--purple)"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ÚLTIMOS MOVIMIENTOS -->
    <div class="row g-3">
        <div class="col-12">
            <div class="section-card">
                <div class="section-head">
                    <div class="section-icon" style="background:var(--green-dim);color:var(--green)"><i class="bi bi-arrow-left-right"></i></div>
                    <h5>Últimos 15 movimientos</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-vc">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Material</th>
                                <th>Almacén</th>
                                <th class="text-center">Tipo</th>
                                <th class="text-end">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_movimientos as $mv):
                                $tipo = strtolower($mv['tipo_movimiento']);
                                $es_entrada = str_contains($tipo, 'entrada') || str_contains($tipo, 'ingreso') || str_contains($tipo, 'compra');
                                $color = $es_entrada ? 'var(--green)' : 'var(--red)';
                                $bg    = $es_entrada ? 'var(--green-dim)' : 'var(--red-dim)';
                                $icon  = $es_entrada ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle';
                            ?>
                            <tr>
                                <td style="color:var(--muted);font-size:.82rem"><?= $mv['fecha'] ?></td>
                                <td><?= htmlspecialchars($mv['material']) ?></td>
                                <td style="color:var(--muted)"><?= htmlspecialchars($mv['almacen']) ?></td>
                                <td class="text-center">
                                    <span class="mov-badge" style="background:<?= $bg ?>;color:<?= $color ?>">
                                        <i class="bi <?= $icon ?> me-1"></i><?= $mv['tipo_movimiento'] ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold" style="color:<?= $color ?>"><?= $mv['cantidad'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
