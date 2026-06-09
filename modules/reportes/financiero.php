<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';

requierePermiso('ver_reportes_financieros');
registrarAccion('Vio reporte financiero');

$pdo = conectar();
$permisos = $_SESSION['permisos'];
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
                  <a href="dashboard.php" class="text-decoration-none">
                      Reporte general
                  </a>
              </li>
              <li class="breadcrumb-item active">Reporte Financiero</li>
          </ol>
      </nav>

    <h2 class="mb-4 fw-semibold"><i class="bi bi-cash-coin me-2"></i> Reporte Financiero</h2>
    
    
    
    <p class="mb-4">Análisis de ingresos, gastos de obra y seguimiento de pagos.</p>
    
    <!-- KPIs RÁPIDOS -->
    <?php
        $total_ingresos_sum = array_sum(array_column($ingresos, 'total_ingresos'));
        $total_pagos_sum    = array_sum(array_column($ingresos, 'total_pagos'));
        $total_pendiente    = array_sum(array_column($pendientes_clientes, 'monto'));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2"> 
                <i class="bi bi-graph-up-arrow"></i>
                <h6>Ingresos totales</h6>
                </div>
                <div class="card-body text-primary">
                  <h5>Bs <?= number_format($total_ingresos_sum, 0, '.', ',') ?></h5>
                </div>
           </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2 fw-semibold"> 
                <i class="bi bi-building"></i>
                 Gastos de obra 
                </div>
                <div class="card-body text-danger">
                  <h5>Bs <?= number_format($gastos['total_gastos'], 0, '.', ',') ?></h5>
                </div>
           </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2 fw-semibold"> 
                <i class="bi bi-receipt"></i>
                Pagos completados
                </div>
                <div class="card-body text-success">
                  <h5><?= $total_pagos_sum ?></h5>
                </div>
           </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header d-flex gap-2 fw-semibold"> 
                <i class="bi bi-receipt"></i>
                Pendiente clientes
                </div>
                <div class="card-body text-warning">
                  <h5>Bs <?= number_format($total_pendiente, 0, '.', ',') ?></h5>
                </div>
           </div>
        </div>
    </div>

    <!-- INGRESOS + PAGOS EMPLEADOS MES -->
    <div class="row g-3 mb-4">
        <!-- Ingresos por proyecto -->
        <div class="col-12 col-lg-7">
        <!-- FILTRO -->
          <form method="GET">
          <div class="mb-3">
              <label class="form-label fw-semibold" for="id_proyecto"><i class="bi bi-funnel me-1"></i> Filtrar por proyecto:</label>
              <div class="input-group mb-3">
                  <select id="id_proyecto" name="id_proyecto" class="form-select">
                      <option value="">— Todos los proyectos —</option>
                      <?php foreach ($proyectos as $p): ?>
                          <option value="<?= $p['id_proyecto'] ?>"
                              <?= $p['id_proyecto'] == $id_proyecto ? 'selected' : '' ?>>
                              <?= htmlspecialchars($p['nombre']) ?>
                          </option>
                      <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-success"><i class="bi bi-search me-1"></i> Filtrar</button>
                  <?php if ($id_proyecto): ?>
                      <a href="financiero.php" class="btn btn-secondary"><i class="bi bi-x me-1"></i> Limpiar</a>
                  <?php endif; ?>
               </div>
          </div>
          </form>
            <div class="card">
                <div class="card-header d-flex gap-2">
                    <i class="bi bi-check-circle"></i>
                    <h5>Ingresos por proyecto</h5>
                </div>
                <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <tr>
                        <th>Proyecto</th>
                        <th>Total pagos</th>
                        <th>Ingresos (Bs)</th>
                    </tr>
                    <?php foreach ($ingresos as $ing): ?>
                        <tr>
                            <td><?= htmlspecialchars($ing['proyecto']) ?></td>
                            <td><?= $ing['total_pagos'] ?></td>
                            <td><?= number_format($ing['total_ingresos'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                </div>
            </div>
        </div>

        <!-- Pagos empleados por mes -->
        <div class="col-12 col-lg-5 pt-2">
            <div class="card mt-4">
                <div class="card-header fw-semibold">
                    <i class="bi bi-people"></i> Pagos a empleados por mes
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between fw-semibold">
                    <div>
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Pagos pendientes de clientes
                    </div>
                    <?php if ($pendientes_clientes): ?>
                        <span class="badge bg-secondary">
                            <?= count($pendientes_clientes) ?> pendiente<?= count($pendientes_clientes) > 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($pendientes_clientes): ?>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered">
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

<?php require_once '../../modules/layouts/footer.php'; ?>