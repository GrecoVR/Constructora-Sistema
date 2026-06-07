<?php
require_once '../../config/database.php';
require_once '../../middleware/auth.php';
require_once '../../middleware/logger.php';
require_once '../../middleware/roles.php';
require_once '../../utils/permisos.php';
require_once '../../config/database.php';
require_once '../../utils/fecha.php';


$pdo   = conectar();
$permisos = $_SESSION['permisos'];
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido = intval($_POST['id_pedido'] ?? 0);
    $id_metodo = intval($_POST['id_metodo_pago'] ?? 0);
    $fecha     = $_POST['fecha_pago'] ?? date('Y-m-d');
    $monto     = floatval($_POST['monto'] ?? 0);
    $estado    = $_POST['estado'] ?? 'completado';

    if ($id_pedido && $id_metodo && $monto > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO pagos_pedidos (id_pedido, id_metodo_pago, fecha_pago, monto, estado)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_pedido, $id_metodo, $fecha, $monto, $estado]);
        registrarAccion("Registró pago pedido ID: $id_pedido por Bs $monto");
        $exito = 'Pago registrado correctamente';
    } else {
        $error = 'Completa todos los campos';
    }
}

$pedidos = $pdo->query("
    SELECT p.id_pedido, p.fecha_pedido, p.estado as estado_pedido,
           pr.nombre as proveedor,
           COALESCE(SUM(dp.cantidad * dp.precio_unitario), 0) as total_pedido,
           COALESCE(SUM(pp.monto), 0) as total_pagado
    FROM pedidos p
    JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
    LEFT JOIN detalle_pedido dp ON dp.id_pedido = p.id_pedido
    LEFT JOIN pagos_pedidos pp ON pp.id_pedido = p.id_pedido AND pp.estado = 'completado'
    GROUP BY p.id_pedido, p.fecha_pedido, p.estado, pr.nombre
    ORDER BY p.fecha_pedido DESC
    LIMIT 30
")->fetchAll();

$metodos = $pdo->query("SELECT * FROM metodos_pago ORDER BY nombre ASC")->fetchAll();
?>

<?php require_once '../../modules/layouts/header.php'; ?>

    <!-- Encabezado -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-4">
                <i class="bi bi-box-seam-fill text-success me-2"></i>Pagos a Proveedores
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="../../modules/dashboard/dashboard.php" class="text-decoration-none">
                            <i class="bi bi-house-door me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="index.php" class="text-decoration-none">Pagos</a>
                    </li>
                    <li class="breadcrumb-item active">Pedidos</li>
                </ol>
            </nav>
        </div>
        <a href="empleados.php" class="btn btn-secondary">
            <i class="bi bi-person-badge me-1"></i>Ver pagos empleados
        </a>
    </div>

    <!-- Alertas -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
            <span><?= $error ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($exito): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <span><?= $exito ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">

        <!-- FORMULARIO REGISTRAR PAGO -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle-fill fs-5"></i>
                    <h5 class="mb-0">Registrar pago a proveedor</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-bag me-1"></i>Pedido <span class="text-danger">*</span>
                            </label>
                            <select name="id_pedido" class="form-select" required>
                                <option value="">— Selecciona pedido —</option>
                                <?php foreach ($pedidos as $p): ?>
                                    <option value="<?= $p['id_pedido'] ?>">
                                        #<?= $p['id_pedido'] ?> — <?= htmlspecialchars($p['proveedor']) ?>
                                        (<?= formatoFechaCorta($p['fecha_pedido']) ?>
                                        — Total: Bs <?= number_format($p['total_pedido'], 2) ?>
                                        — Pagado: Bs <?= number_format($p['total_pagado'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-wallet2 me-1"></i>Método de pago <span class="text-danger">*</span>
                            </label>
                            <select name="id_metodo_pago" class="form-select" required>
                                <option value="">— Selecciona —</option>
                                <?php foreach ($metodos as $m): ?>
                                    <option value="<?= $m['id_metodo_pago'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-currency-dollar me-1"></i>Monto (Bs) <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="monto" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar3 me-1"></i>Fecha
                                </label>
                                <input type="date" name="fecha_pago" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-flag me-1"></i>Estado
                            </label>
                            <select name="estado" class="form-select">
                                <option value="completado">Completado</option>
                                <option value="pendiente">Pendiente</option>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send me-2"></i>Registrar pago
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- RESUMEN RÁPIDO -->
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up fs-5"></i>
                    <h5 class="mb-0">Resumen de pedidos</h5>
                </div>
                <div class="card-body">
                    <?php if ($pedidos): ?>
                        <?php
                            $total_global   = array_sum(array_column($pedidos, 'total_pedido'));
                            $pagado_global  = array_sum(array_column($pedidos, 'total_pagado'));
                            $saldo_global   = $total_global - $pagado_global;
                            $pct            = $total_global > 0 ? round(($pagado_global / $total_global) * 100) : 0;
                        ?>
                        <div class="p-4 border-bottom">
                            <div class="row text-center g-3">
                                <div class="col-4">
                                    <div class="fs-5 fw-bold text-dark"><?= number_format($total_global, 2) ?></div>
                                    <div class="small text-muted">Total pedidos (Bs)</div>
                                </div>
                                <div class="col-4">
                                    <div class="fs-5 fw-bold text-success"><?= number_format($pagado_global, 2) ?></div>
                                    <div class="small text-muted">Total pagado (Bs)</div>
                                </div>
                                <div class="col-4">
                                    <div class="fs-5 fw-bold <?= $saldo_global > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($saldo_global, 2) ?></div>
                                    <div class="small text-muted">Saldo pendiente (Bs)</div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Avance de pagos</span>
                                    <span><?= $pct ?>%</span>
                                </div>
                                <div class="progress" style="height:10px">
                                    <div class="progress-bar bg-success" role="progressbar"
                                         style="width: <?= $pct ?>%"
                                         aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="p-3 text-muted small text-center">
                        <i class="bi bi-info-circle me-1"></i>Basado en los últimos 30 pedidos
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TABLA ESTADO DE PAGOS -->
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-table fs-5"></i>
            <h5 class="mb-0">Estado de pagos por pedido</h5>
        </div>
        <div class="card-body">
            <?php if ($pedidos): ?>
                <div class="table-responsive">
                    <table id="tabla-datos" class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Pedido</th>
                                <th>Proveedor</th>
                                <th>Fecha</th>
                                <th class="text-end">Total (Bs)</th>
                                <th class="text-end">Pagado (Bs)</th>
                                <th class="text-end">Saldo (Bs)</th>
                                <th class="text-center">Estado pedido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <?php $saldo = $p['total_pedido'] - $p['total_pagado']; ?>
                                <tr>
                                    <td class="ps-3 fw-semibold text-muted">#<?= $p['id_pedido'] ?></td>
                                    <td>
                                        <i class="bi bi-building text-success me-1"></i><?= htmlspecialchars($p['proveedor']) ?>
                                    </td>
                                    <td class="text-muted"><?= formatoFechaCorta($p['fecha_pedido']) ?></td>
                                    <td class="text-end"><?= number_format($p['total_pedido'], 2) ?></td>
                                    <td class="text-end fw-semibold text-success"><?= number_format($p['total_pagado'], 2) ?></td>
                                    <td class="text-end fw-bold <?= $saldo > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($saldo, 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                            $est = strtolower($p['estado_pedido']);
                                            $badge = match($est) {
                                                'completado', 'entregado' => 'bg-success-subtle text-success-emphasis',
                                                'pendiente'               => 'bg-warning-subtle text-warning-emphasis',
                                                'cancelado'               => 'bg-danger-subtle text-danger-emphasis',
                                                default                   => 'bg-secondary-subtle text-secondary-emphasis',
                                            };
                                        ?>
                                        <span class="badge <?= $badge ?>"><?= ucfirst($p['estado_pedido']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                    <p class="mb-0">No hay pedidos registrados aún.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
$(document).ready(function() {
   var table = $('#tabla-datos').DataTable({
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
        },
        order: [],
        columnDefs: [
        {
          targets: -1,
          orderable: false
        }
        ]
    });
});    
</script>

<?php require_once '../../modules/layouts/footer.php'; ?>