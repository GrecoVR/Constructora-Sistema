<?php
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../config/database.php';
require_once '../../utils/permisos.php';


$pdo = conectar();
$permisos = $_SESSION['permisos'];
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

<?php require_once '../../modules/layouts/header.php'; ?>


<div class="content">

      <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="../../modules/dashboard/dashboard.php" class="text-decoration-none">
                        <i class="bi bi-house-door me-1"></i>Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item active">Reportes</li>
            </ol>
        </nav>

    <h2 class="mb-3 fw-semibold"><i class="bi bi-speedometer2 me-2"></i>Reporte General</h2>
    
    <p class="mb-3">Resumen ejecutivo del estado operativo y financiero de la empresa.</p>
    
    <a class="btn btn-secondary me-2 mb-4" href="financiero.php" class="btn-nav"><i class="bi bi-cash-coin me-1"></i> Financiero</a>
    <a class="btn btn-success mb-4" href="inventario.php" class="btn-nav"><i class="bi bi-boxes me-1"></i> Inventario</a>
    
    

    <!-- MÉTRICAS FINANCIERAS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card">
            <div class="card-header">
                <i class="bi bi-arrow-down-circle-fill"></i> Ingresos recibidos
            </div>
            <div class="card-body text-primary">
                Bs <?= number_format($financiero['ingresos'], 0, '.', ',') ?>
            </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
            <div class="card-header">
                <i class="bi bi-arrow-up-circle-fill"></i> Total gastos
            </div> 
            <div class="card-body text-danger">
                Bs <?= number_format($total_gastos, 0, '.', ',') ?>
            </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-wallet2"></i>
                    Balance neto
                </div>
                <div class="card-body" style="color:<?= $balance >= 0 ? 'green' : 'red' ?>">
                    Bs <?= number_format($balance, 0, '.', ',') ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i>
                    Pagos pendientes
                </div>
                <div class="card-body text-warning d-flex justify-content-between">
                Bs <?= number_format($pagos_pendientes['monto_total'], 0, '.', ',') ?>
                <div class="small text-muted"><?= $pagos_pendientes['total'] ?> pagos</div>
                </div>
            </div>
        </div>
    </div><!-- end row-->

    <!-- DESGLOSE GASTOS + PROYECTOS ESTADO -->
    <div class="row g-3 mb-4">
        <!-- Desglose gastos -->
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-header d-flex gap-2">
                    <i class="bi bi-pie-chart"></i>
                    <h5>Desglose de gastos</h5>
                </div>
                <div class="card-body table-responsive table-bordered">
                <table class="table table-striped">
                    <thead><tr><th>Concepto</th><th class="text-end">Monto (Bs)</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><i class="bi bi-people me-2"></i>Personal</td>
                            <td class="text-end"><?= number_format($financiero['gastos_personal'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-building me-2"></i>Obra</td>
                            <td class="text-end"><?= number_format($financiero['gastos_obra'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><i class="bi bi-cart me-2"></i>Pedidos</td>
                            <td class="text-end"><?= number_format($financiero['gastos_pedidos'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="text-end"><strong style="color:red"><?= number_format($total_gastos, 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Proyectos por estado -->
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-header d-flex gap-2">
                    <i class="bi bi-folder2-open"></i>
                    <h5>Proyectos por estado</h5>
                </div>
                <div class="card-body">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Estado</th><th class="text-end">Cantidad</th></tr></thead>
                    <tbody>
                        <?php
                        $estado_colors = ['activo'=>'text-primary-emphasis','finalizado'=>'text-success-emphasis',
                                          'pausado'=>'text-warning-emphasis','cancelado'=>'text-danger-emphasis'];
                        $estado_bg     = ['activo'=>'bg-primary-subtle','finalizado'=>'bg-success-subtle',
                                          'pausado'=>'bg-warning-subtle','cancelado'=>'bg-danger-subtle'];
                        foreach ($proyectos_estado as $pe):
                            $e = strtolower($pe['estado']);
                            $c = $estado_colors[$e] ?? 'text-secondary-emphasis';
                            $bg= $estado_bg[$e]     ?? 'bg-secondary-subtle';
                        ?>
                        <tr>
                            <td>
                                <span class="badge <?= $bg ?> <?= $c ?>">
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
    </div><!-- end row -->

    <!-- TOP PROYECTOS + TOP MATERIALES -->
    <div class="row g-3 mb-4">
        <!-- Top proyectos por gasto -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header d-flex gap-2">
                    <i class="bi bi-trophy"></i>
                    <h5>Top 5 proyectos con más gastos</h5>
                </div>
                <div class="card-body">
                <ol class="list-group list-group-numbered">
                <?php
                $max_gasto = max(array_column($top_proyectos, 'total_gastos')) ?: 1;
                foreach ($top_proyectos as $i => $tp):
                    $pct = round(($tp['total_gastos'] / $max_gasto) * 100);
                ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><?= htmlspecialchars($tp['nombre']) ?></span>
                    <span>Bs <?= number_format($tp['total_gastos'], 2) ?></span>
                </li>
                <?php endforeach; ?>
                </ol>
                </div>
            </div>
        </div>

        <!-- Top materiales -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header d-flex gap-2">
                    <i class="bi bi-boxes"></i>
                    <h5>Top 5 materiales más usados</h5>
                </div>
                <div class="card-body">
                <ol class="list-group list-group-numbered">
                <?php
                $max_mat = max(array_column($top_materiales, 'total_usado')) ?: 1;
                foreach ($top_materiales as $tm):
                    $pct = round(($tm['total_usado'] / $max_mat) * 100);
                ?>
                <li class="list-group-item d-flex justify-content-between">
                      <span><?= htmlspecialchars($tm['nombre']) ?></span>
                      <span><?= number_format($tm['total_usado'], 2) ?> unid.</span>
                </li>
                <?php endforeach; ?>
                </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- COTIZACIONES -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex gap-2">
                    <i class="bi bi-file-earmark-text"></i>
                    <h5>Cotizaciones por estado</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">Monto total (Bs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cotizaciones_estado as $ce):
                            $estado_colors = ['aprobada'=>'text-success-emphasis','rechazada'=>'text-danger-emphasis'];
                            $estado_bg     = ['aprobada'=>'bg-success-subtle','rechazada'=>'bg-danger-subtle'];
                                $e  = strtolower($ce['estado']);
                                $c  = $estado_colors[$e] ?? 'text-secondary-emphasis';
                                $bg = $estado_bg[$e]     ?? 'bg-secondary-subtle';
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $bg ?> <?= $c ?>">
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
    </div><!-- end row -->

</div><!-- /content -->

<?php require_once '../../modules/layouts/footer.php'; ?>