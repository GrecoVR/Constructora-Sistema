<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_inventarios');
registrarAccion(LOG_VER_INVENTARIO);

$pdo = conectar();
$permisos = $_SESSION['permisos'];
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

<?php require_once '../../modules/layouts/header.php'; ?>

<div class="content">

    <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
              <li class="breadcrumb-item">
                  <a href="../../modules/dashboard/dashboard.php" class="text-decoration-none">
                      <i class="bi bi-house-door me-1"></i>Dashboard
                  </a>
              </li>
              <li class="breadcrumb-item">
                  <a href="dashboard.php" class="text-decoration-none"> Reporte general
                  </a>
              </li>
              <li class="breadcrumb-item active">Reporte inventarios</li>
          </ol>
      </nav>



    <h2 class="mb-4 fw-semibold"><i class="bi bi-boxes me-2"></i> Reporte de Inventario</h2>
    <p class="mb-4">Estado actual de stock, alertas y movimientos de materiales en almacenes.</p>

    <!-- KPIs -->
    <?php
        $total_almacenes  = count($stock_almacen);
        $total_stock      = array_sum(array_column($stock_almacen, 'stock_total'));
        $total_bajo_min   = count($bajo_minimo);
        $total_agotados   = count($agotados);
    ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2 fw-semibold"> 
                <i class="bi bi-building"></i>
                Almacenes activos
                </div>
                <div class="card-body text-primary">
                  <h4><?= $total_almacenes ?></h4>
                </div>
           </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2 fw-semibold"> 
                <i class="bi bi-stack"></i>
                Stock total
                </div>
                <div class="card-body text-success d-flex justify-content-between">
                  <h4><?= number_format($total_stock, 0, '.', ',') ?></h4>
                  unidades globales
                </div>
           </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2 fw-semibold"> 
                <i class="bi bi-exclamation-triangle"></i>
                Bajo mínimo
                </div>
                <div class="card-body text-warning d-flex justify-content-between">
                  <h4><?= $total_bajo_min ?></h4>
                  materiales
                </div>
           </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2 fw-semibold"> 
                <i class="bi bi-x-octagon"></i>
                Agotados
                </div>
                <div class="card-body text-primary d-flex justify-content-between">
                  <h4><?= $total_agotados ?></h4>
                  sin stock
                </div>
           </div>
        </div>
    </div>

    <!-- ALERTAS -->
    <?php if ($agotados || $bajo_minimo): ?>
    <div class="row g-3 mb-4">
        <?php if ($agotados): ?>
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between gap-2">
                    <div class="section-icon" style="background:var(--red-dim);color:var(--red)"><i class="bi bi-x-octagon-fill"></i></div>
                    <h5>Materiales agotados</h5>
                    <span class="ms-auto badge" style="background:var(--red-dim);color:var(--red);font-size:.75rem"><?= count($agotados) ?></span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table bordered">
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
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="bi bi-building"></i>
                     Stock por almacén
                </div>
                <div class="card-body table-responsive">
                <table class="table table-striped table-bordered">
                  <tr>
                      <th>Almacén</th>
                      <th>Materiales distintos</th>
                      <th>Stock total</th>
                  </tr>
                  <?php foreach ($stock_almacen as $sa): ?>
                      <tr>
                          <td><?= htmlspecialchars($sa['almacen']) ?></td>
                          <td><?= $sa['total_materiales'] ?></td>
                          <td><?= number_format($sa['stock_total'], 2) ?></td>
                      </tr>
                  <?php endforeach; ?>
              </table>
              </div>
            </div>
        </div>

        <!-- Top 10 materiales más usados -->
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="bi bi-graph-up"></i>
                    Top 10 materiales más usados en proyectos
                </div>
                <ol class="list-group list-group-numbered">
                <?php
                $max_uso = max(array_column($mas_usados, 'total')) ?: 1;
                foreach ($mas_usados as $mu):
                    $pct = round(($mu['total'] / $max_uso) * 100);
                ?>
                
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= htmlspecialchars($mu['nombre']) ?></span>
                        <span><?= number_format($mu['total'], 2) ?> u. &nbsp;·&nbsp; <?= $mu['en_proyectos'] ?> proy.</span>
                    </li>
                
                <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>

    <!-- ÚLTIMOS MOVIMIENTOS -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">
                    <i class="bi bi-arrow-left-right"></i>
                    Últimos 15 movimientos
                </div>
                <div class="card-body table-responsive">
                    <table id="tabla-datos" class="table table-striped table-bordered">
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
                                $color = $es_entrada ? 'text-success-emphasis' : 'text-danger-emphasis';
                                $bg    = $es_entrada ? 'bg-success-subtle' : 'bg-danger-subtle';
                                $icon  = $es_entrada ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle';
                            ?>
                            <tr>
                                <td><?= $mv['fecha'] ?></td>
                                <td><?= htmlspecialchars($mv['material']) ?></td>
                                <td><?= htmlspecialchars($mv['almacen']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $bg ?> <?= $color ?>">
                                        <i class="bi <?= $icon ?> me-1"></i><?= $mv['tipo_movimiento'] ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold"><?= $mv['cantidad'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
$(document).ready(function() {
   var table = $('#tabla-datos').DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        }
    });
});    
</script>
<?php require_once '../../modules/layouts/footer.php'; ?>